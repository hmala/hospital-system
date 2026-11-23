<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationCustodianHistory;
use App\Models\StockMovement;
use App\Models\StockTransferRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NursingSubStoreController extends Controller
{
    /**
     * التحقق من صلاحية مشرف التمريض والإدارة
     */
    protected function authorizeSupervisor(): User
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        $canAccess = $user->can('manage nursing sub stores')
            || $user->hasRole(['head_nurse', 'admin', 'admin-hsop', 'hospital_admin', 'super_admin'])
            || in_array($user->role, ['head_nurse', 'admin']);

        if (! $canAccess) {
            abort(403, 'غير مصرح لك بالوصول لإدارة ورقابة المخازن الفرعية، الصلاحية مخصصة لرئيس ومشرف التمريض والإدارة.');
        }

        return $user;
    }

    /**
     * الواجهة الرئيسية لرقابة المخازن الفرعية والعهد
     */
    public function index(Request $request)
    {
        $this->authorizeSupervisor();

        $search = trim((string) $request->query('search', ''));

        $query = Location::where('type', 'sub')
            ->where(function ($q) {
                $q->whereHas('staff', function ($sq) {
                    $sq->whereHas('roles', fn ($rq) => $rq->where('name', 'nurse'))
                        ->orWhere('role', 'nurse');
                })->orWhere(function ($nameQ) {
                    $nameQ->where(function ($nq) {
                        $nq->where('name', 'like', '%رده%')
                            ->orWhere('name', 'like', '%طابق%');
                    });
                });
            })
            ->where('name', 'not like', '%عمليات%')
            ->where('name', 'not like', '%صالة%')
            ->where('name', 'not like', '%خدج%')
            ->where('name', 'not like', '%ولادة%')
            ->where('name', 'not like', '%نواظير%')
            ->where('name', 'not like', '%طوارئ%')
            ->where('name', 'not like', '%طوارىء%')
            ->where('name', 'not like', '%عيون%')
            ->where('name', 'not like', '%اسنان%')
            ->where('name', 'not like', '%حسابات%')
            ->where('name', 'not like', '%صيانة%')
            ->where('name', 'not like', '%فندقة%')
            ->where('name', 'not like', '%احصاء%')
            ->where('name', 'not like', '%رسبشن%')
            ->where('name', 'not like', '%ادارة%')
            ->where('name', 'not like', '%صيدل%')
            ->where('name', 'not like', '%مختبر%')
            ->where('name', 'not like', '%استشار%')
            ->with([
                'custodian',
                'staff' => function ($sq) {
                    $sq->where(function ($nq) {
                        $nq->whereHas('roles', fn ($rq) => $rq->where('name', 'nurse'))
                            ->orWhere('role', 'nurse');
                    });
                },
                'stockBatches' => function ($q) {
                    $q->where('current_qty', '>', 0);
                },
            ]);

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        $subStores = $query->orderBy('name')->get();

        // حساب الإحصائيات الشهرية والطلبيات المعلقة لكل مخزن
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $storeIds = $subStores->pluck('id')->toArray();

        // سحوبات المرضى والاستهلاك التمريضي خلال الشهر الحالي
        $monthlyConsumptions = StockMovement::whereIn('from_location_id', $storeIds)
            ->where('type', 'dispense')
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->selectRaw('from_location_id, count(*) as count, sum(quantity) as total_qty')
            ->groupBy('from_location_id')
            ->get()
            ->keyBy('from_location_id');

        // طلبيات التعزيز المعلقة وبانتظار الاستلام
        $pendingTransfers = StockTransferRequest::whereIn('to_location_id', $storeIds)
            ->whereIn('status', ['pending', 'approved'])
            ->selectRaw('to_location_id, count(*) as count')
            ->groupBy('to_location_id')
            ->get()
            ->keyBy('to_location_id');

        // قائمة كادر التمريض المتاحين لتكليف العهدة مع موقعهم الحالي
        $availableNurses = User::where(function ($q) {
            $q->whereHas('roles', fn ($rq) => $rq->whereIn('name', ['nurse', 'head_nurse']))
                ->orWhere('role', 'nurse');
        })
            ->whereDoesntHave('roles', fn ($rq) => $rq->whereIn('name', ['admin', 'admin-hsop', 'hospital_admin', 'super_admin', 'surgery_staff']))
            ->where('is_active', true)
            ->with('location')
            ->orderBy('name')
            ->get();

        // مؤشرات القيادة العامة
        $stats = [
            'total_stores' => $subStores->count(),
            'assigned_custodians' => $subStores->whereNotNull('custodian_id')->count(),
            'unassigned_custodians' => $subStores->whereNull('custodian_id')->count(),
            'total_items_in_stock' => $subStores->sum(fn ($s) => $s->stockBatches->sum('current_qty')),
            'total_monthly_consumptions' => $monthlyConsumptions->sum('count'),
            'total_pending_requests' => $pendingTransfers->sum('count'),
        ];

        return view('nursing-sub-stores.index', compact(
            'subStores',
            'monthlyConsumptions',
            'pendingTransfers',
            'availableNurses',
            'stats',
            'search'
        ));
    }

    /**
     * تعيين وتغيير أمين عهدة المخزن الفرعي
     */
    public function assignCustodian(Request $request, Location $location)
    {
        $user = $this->authorizeSupervisor();

        $validated = $request->validate([
            'custodian_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
            'sync_user_location' => 'nullable|boolean',
        ], [
            'custodian_id.required' => 'يرجى اختيار الممرض المسؤول عن العهدة.',
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
                ? "نقل المسؤولية من ({$previousCustodian->name}) إلى ({$newCustodian->name})"
                : "تكليف أولي بالمسؤولية لـ ({$newCustodian->name})"),
        ]);

        return redirect()->route('nursing-sub-stores.index')
            ->with('success', "تم تعيين الممرض ({$newCustodian->name}) مسؤولاً رسمياً عن عهدة ({$location->name}) بنجاح.");
    }

    /**
     * صفحة تفاصيل المخزن الفرعي وأرصدته وسجل الصرفيات
     */
    public function show(Request $request, Location $location)
    {
        $this->authorizeSupervisor();

        $location->load([
            'custodian',
            'staff',
            'custodianHistories.user',
            'custodianHistories.assignedBy',
            'stockBatches.product',
        ]);

        // الوجبات المتوفرة حالياً
        $activeBatches = $location->stockBatches->where('current_qty', '>', 0);

        // سجل حركات السحب والاستهلاك السريري
        $movements = StockMovement::with(['batch.product', 'user'])
            ->where('from_location_id', $location->id)
            ->latest()
            ->paginate(20);

        // طلبيات تعزيز الرصيد الخاصة بهذا المخزن
        $transferRequests = StockTransferRequest::with(['requestedBy', 'approvedBy', 'receivedBy'])
            ->where('to_location_id', $location->id)
            ->latest()
            ->take(15)
            ->get();

        // قائمة الممرضين في حال الرغبة بتغيير المسؤول من داخل صفحة التفاصيل
        $availableNurses = User::where(function ($q) {
            $q->whereHas('roles', fn ($rq) => $rq->whereIn('name', ['nurse', 'head_nurse']))
                ->orWhere('role', 'nurse');
        })
            ->whereDoesntHave('roles', fn ($rq) => $rq->whereIn('name', ['admin', 'admin-hsop', 'hospital_admin', 'super_admin', 'surgery_staff']))
            ->where('is_active', true)
            ->with('location')
            ->orderBy('name')
            ->get();

        return view('nursing-sub-stores.show', compact(
            'location',
            'activeBatches',
            'movements',
            'transferRequests',
            'availableNurses'
        ));
    }
}
