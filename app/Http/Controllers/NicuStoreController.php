<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationCustodianHistory;
use App\Models\StockMovement;
use App\Models\StockTransferRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NicuStoreController extends Controller
{
    /**
     * التحقق من صلاحية إدارة والاطلاع على مخزن وعُهدة الخدج
     */
    protected function authorizeNicuStore(): User
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        $canAccess = $user->can('manage nicu store')
            || $user->can('manage nicu nursing')
            || $user->can('manage nicu clinical')
            || $user->can('manage inventory')
            || $user->hasRole(['nicu_nurse', 'nicu_doctor', 'nicu_staff', 'head_nurse', 'admin', 'admin-hsop', 'hospital_admin', 'super_admin'])
            || in_array($user->role, ['nicu_nurse', 'nicu_doctor', 'nicu_staff', 'head_nurse', 'admin']);

        if (! $canAccess) {
            abort(403, 'غير مصرح لك بالوصول لإدارة ورقابة مخزن وعُهدة قسم الخدج (NICU).');
        }

        return $user;
    }

    /**
     * لوحة القيادة الرئيسية لمخزن وعُهدة قسم الخدج المربوط بالمخزن الرئيسي
     */
    public function index(Request $request)
    {
        $this->authorizeNicuStore();

        $search = trim((string) $request->query('search', ''));

        $query = Location::where('type', 'sub')
            ->where(function ($q) {
                $q->where('name', 'like', '%خدج%')
                    ->orWhere('name', 'like', '%حاضن%')
                    ->orWhere('name', 'like', '%NICU%');
            })
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

        $subStores = $query->orderBy('id')->get();
        $storeIds = $subStores->pluck('id')->toArray();

        $currentMonth = now()->month;
        $currentYear = now()->year;

        // حركات الصرف للأطفال الخدج خلال الشهر الحالي
        $monthlyConsumptions = StockMovement::whereIn('from_location_id', $storeIds)
            ->whereIn('type', ['dispense', 'issue_to_patient'])
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->selectRaw('from_location_id, count(*) as count, sum(quantity) as total_qty')
            ->groupBy('from_location_id')
            ->get()
            ->keyBy('from_location_id');

        // طلبيات التزويد الجارية من المخزن الرئيسي
        $pendingTransfers = StockTransferRequest::whereIn('to_location_id', $storeIds)
            ->whereIn('status', ['pending', 'approved'])
            ->selectRaw('to_location_id, count(*) as count')
            ->groupBy('to_location_id')
            ->get()
            ->keyBy('to_location_id');

        $mainLocation = Location::where('type', 'main')->first();

        // كادر الخدج والتمريض المتاح لتكليف العهدة
        $availableStaff = User::where(function ($q) {
            $q->whereHas('roles', fn ($rq) => $rq->whereIn('name', ['nicu_nurse', 'nicu_doctor', 'nicu_staff', 'nurse', 'head_nurse', 'admin', 'hospital_admin']))
                ->orWhereIn('role', ['nicu_nurse', 'nicu_doctor', 'nicu_staff', 'nurse', 'head_nurse']);
        })
            ->where('is_active', true)
            ->with('location')
            ->orderBy('name')
            ->get();

        $stats = [
            'total_stores' => $subStores->count(),
            'assigned_custodians' => $subStores->whereNotNull('custodian_id')->count(),
            'unassigned_custodians' => $subStores->whereNull('custodian_id')->count(),
            'total_items_in_stock' => $subStores->sum(fn ($s) => $s->stockBatches->sum('current_qty')),
            'total_monthly_consumptions' => $monthlyConsumptions->sum('count'),
            'total_pending_requests' => $pendingTransfers->sum('count'),
        ];

        return view('nicu-store.index', compact(
            'subStores',
            'monthlyConsumptions',
            'pendingTransfers',
            'availableStaff',
            'mainLocation',
            'stats',
            'search'
        ));
    }

    /**
     * تفاصيل مخزن الخدج: الأرصدة، صرفيات الأطفال الخدج، سجل العهدة، وطلبيات المخزن الرئيسي
     */
    public function show(Request $request, Location $location)
    {
        $this->authorizeNicuStore();

        $location->load([
            'custodian',
            'staff',
            'custodianHistories.user',
            'custodianHistories.assignedBy',
            'stockBatches.product',
        ]);

        $activeBatches = $location->stockBatches
            ->where('current_qty', '>', 0)
            ->sortBy('expiry_date')
            ->values();

        $movements = StockMovement::with([
            'batch.product',
            'user',
            'incubatorReservation.patient.user',
            'incubatorReservation.incubator',
        ])
            ->where('from_location_id', $location->id)
            ->latest()
            ->paginate(20);

        $transferRequests = StockTransferRequest::with(['fromLocation', 'toLocation', 'requestedBy', 'approvedBy', 'receivedBy'])
            ->where(function ($q) use ($location) {
                $q->where('to_location_id', $location->id)
                    ->orWhere('from_location_id', $location->id);
            })
            ->latest()
            ->take(20)
            ->get();

        $mainLocation = Location::where('type', 'main')->first();

        $availableStaff = User::where(function ($q) {
            $q->whereHas('roles', fn ($rq) => $rq->whereIn('name', ['nicu_nurse', 'nicu_doctor', 'nicu_staff', 'nurse', 'head_nurse', 'admin', 'hospital_admin']))
                ->orWhereIn('role', ['nicu_nurse', 'nicu_doctor', 'nicu_staff', 'nurse', 'head_nurse']);
        })
            ->where('is_active', true)
            ->with('location')
            ->orderBy('name')
            ->get();

        return view('nicu-store.show', compact(
            'location',
            'activeBatches',
            'movements',
            'transferRequests',
            'mainLocation',
            'availableStaff'
        ));
    }

    /**
     * تعيين أو تغيير أمين عهدة مخزن الخدج
     */
    public function assignCustodian(Request $request, Location $location)
    {
        $user = $this->authorizeNicuStore();

        $validated = $request->validate([
            'custodian_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
            'sync_user_location' => 'nullable|boolean',
        ], [
            'custodian_id.required' => 'يرجى اختيار الموظف أو الممرض المسؤول عن عهدة الخدج.',
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

        LocationCustodianHistory::create([
            'location_id' => $location->id,
            'user_id' => $newCustodian->id,
            'assigned_by' => $user->id,
            'assigned_at' => now(),
            'notes' => $validated['notes'] ?: ($previousCustodian
                ? "نقل عهدة مخزن الخدج من ({$previousCustodian->name}) إلى ({$newCustodian->name})"
                : "تكليف رسمي بعهدة مخزن الخدج لـ ({$newCustodian->name})"),
        ]);

        return redirect()->back()
            ->with('success', "تم تعيين ({$newCustodian->name}) مسؤولاً رسمياً عن عهدة ({$location->name}) بنجاح.");
    }
}
