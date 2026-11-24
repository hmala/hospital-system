<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationCustodianHistory;
use App\Models\StockMovement;
use App\Models\StockTransferRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurgeryStoreController extends Controller
{
    /**
     * التحقق من صلاحية كادر العمليات والإدارة
     */
    protected function authorizeSurgeryStaff(): User
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        $canAccess = $user->can('manage surgery store')
            || $user->hasRole(['surgery_staff', 'admin', 'admin-hsop', 'hospital_admin', 'super_admin'])
            || in_array($user->role, ['surgery_staff', 'admin']);

        if (! $canAccess) {
            abort(403, 'غير مصرح لك بالوصول لإدارة ورقابة مخازن وصالات العمليات، الصلاحية مخصصة لموظفي العمليات والإدارة.');
        }

        return $user;
    }

    /**
     * الواجهة الرئيسية لإدارة ورقابة مخازن وصالات العمليات والعهد
     */
    public function index(Request $request)
    {
        $this->authorizeSurgeryStaff();

        $search = trim((string) $request->query('search', ''));

        $query = Location::where('type', 'sub')
            ->where(function ($q) {
                $q->where('name', 'like', '%عمليات%')
                    ->orWhere('name', 'like', '%صالة العمليات%')
                    ->orWhere('name', 'like', '%صاله العمليات%')
                    ->orWhere('name', 'like', '%مخزن عمليات%');
            })
            ->where('name', 'not like', '%ولادة%')
            ->with([
                'custodian',
                'staff',
                'stockBatches' => function ($q) {
                    $q->where('current_qty', '>', 0);
                },
            ]);

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $subStores = $query->orderBy('id', 'desc')->get();

        $storeIds = $subStores->pluck('id')->toArray();

        // حساب الاستهلاك وسحوبات العمليات خلال الشهر الحالي
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $monthlyConsumptions = StockMovement::whereIn('from_location_id', $storeIds)
            ->where('type', 'dispense')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->selectRaw('from_location_id, count(*) as count, sum(quantity) as total_qty')
            ->groupBy('from_location_id')
            ->get()
            ->keyBy('from_location_id');

        // طلبيات التعزيز المعلقة الخاصة بمخازن العمليات
        $pendingTransfers = StockTransferRequest::whereIn('to_location_id', $storeIds)
            ->whereIn('status', ['pending', 'approved'])
            ->selectRaw('to_location_id, count(*) as count')
            ->groupBy('to_location_id')
            ->get()
            ->keyBy('to_location_id');

        // قائمة كادر العمليات المتاحين لتكليف العهدة
        $availableStaff = User::where(function ($q) {
            $q->whereHas('roles', fn ($rq) => $rq->whereIn('name', ['surgery_staff', 'admin', 'hospital_admin']))
                ->orWhere('role', 'surgery_staff');
        })
            ->where('is_active', true)
            ->with('location')
            ->orderBy('name')
            ->get();

        // مؤشرات القيادة العامة المخزنية
        $stats = [
            'total_stores' => $subStores->count(),
            'assigned_custodians' => $subStores->whereNotNull('custodian_id')->count(),
            'unassigned_custodians' => $subStores->whereNull('custodian_id')->count(),
            'total_items_in_stock' => $subStores->sum(fn ($s) => $s->stockBatches->sum('current_qty')),
            'total_monthly_consumptions' => $monthlyConsumptions->sum('count'),
            'total_pending_requests' => $pendingTransfers->sum('count'),
        ];

        return view('surgery-store.index', compact(
            'subStores',
            'monthlyConsumptions',
            'pendingTransfers',
            'availableStaff',
            'stats',
            'search'
        ));
    }

    /**
     * تفاصيل مخزن أو صالة عمليات وسجل الأرصدة والاستهلاك
     */
    public function show(Request $request, Location $location)
    {
        $this->authorizeSurgeryStaff();

        $location->load([
            'custodian',
            'staff',
            'custodianHistories.user',
            'custodianHistories.assignedBy',
            'stockBatches.product',
        ]);

        // الوجبات المتوفرة حالياً
        $activeBatches = $location->stockBatches->where('current_qty', '>', 0);

        // سجل حركات السحب والاستهلاك الجراحي
        $movements = StockMovement::with(['batch.product', 'user', 'surgery.patient.user', 'surgery.doctor.user'])
            ->where('from_location_id', $location->id)
            ->latest()
            ->paginate(20);

        // طلبيات تعزيز الرصيد الخاصة بهذا المخزن
        $transferRequests = StockTransferRequest::with(['requestedBy', 'approvedBy', 'receivedBy'])
            ->where(function ($q) use ($location) {
                $q->where('to_location_id', $location->id)
                    ->orWhere('from_location_id', $location->id);
            })
            ->latest()
            ->take(15)
            ->get();

        // كادر العمليات المتاحين للعهدة
        $availableStaff = User::where(function ($q) {
            $q->whereHas('roles', fn ($rq) => $rq->whereIn('name', ['surgery_staff', 'admin', 'hospital_admin']))
                ->orWhere('role', 'surgery_staff');
        })
            ->where('is_active', true)
            ->with('location')
            ->orderBy('name')
            ->get();

        return view('surgery-store.show', compact(
            'location',
            'activeBatches',
            'movements',
            'transferRequests',
            'availableStaff'
        ));
    }

    /**
     * تكليف أو تغيير أمين عهدة مخزن/صالة العمليات
     */
    public function assignCustodian(Request $request, Location $location)
    {
        $user = $this->authorizeSurgeryStaff();

        $validated = $request->validate([
            'custodian_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
            'sync_user_location' => 'nullable|boolean',
        ], [
            'custodian_id.required' => 'يرجى اختيار الموظف المسؤول عن العهدة.',
            'custodian_id.exists' => 'المستخدم المختار غير موجود بالسجلات.',
        ]);

        $newCustodian = User::findOrFail($validated['custodian_id']);
        $previousCustodian = $location->custodian;

        $location->update([
            'custodian_id' => $newCustodian->id,
        ]);

        if ($request->boolean('sync_user_location')) {
            $newCustodian->update(['location_id' => $location->id]);
        }

        // توثيق العملية في سجل أرشيف تسليم واستلام العهدة
        LocationCustodianHistory::create([
            'location_id' => $location->id,
            'user_id' => $newCustodian->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
            'notes' => $validated['notes'] ?: ($previousCustodian
                ? "نقل عهدة العمليات من ({$previousCustodian->name}) إلى ({$newCustodian->name})"
                : "تكليف رسمي بعهدة العمليات لـ ({$newCustodian->name})"),
        ]);

        return redirect()->route('surgery-store.index')
            ->with('success', "تم تعيين موظف العمليات ({$newCustodian->name}) مسؤولاً رسمياً عن عهدة ({$location->name}) بنجاح.");
    }
}
