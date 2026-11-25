<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\LocationCustodianHistory;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockTransferRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NicuStoreController extends Controller
{
    /**
     * التحقق الصارم من صلاحية إدارة والاطلاع على مخزن وعُهدة الخدج.
     * محصورة بصلاحية manage nicu store أو manage inventory أو الإدارة العليا فقط.
     */
    protected function authorizeNicuStore(): User
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        $canAccess = $user->can('manage nicu store')
            || $user->can('manage inventory')
            || $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])
            || $user->role === 'admin';

        if (! $canAccess) {
            abort(403, 'غير مصرح لك بالوصول لمخزن وعُهدة قسم الخدج (NICU). تتطلب هذه الشاشة صلاحية «إدارة وصرف عهدة ومخزن قسم الخدج».');
        }

        return $user;
    }

    /**
     * هل المستخدم مدير نظام أو مدير مخازن عام
     */
    protected function isStoreAdmin(User $user): bool
    {
        return $user->can('manage inventory')
            || $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])
            || $user->role === 'admin';
    }

    /**
     * التحقق من أن الموقع المطلوب هو فعلاً مخزن عهدة المستخدم أو مخزن الخدج المعتمد لغير المدراء
     */
    protected function ensureAuthorizedNicuLocation(User $user, Location $location): void
    {
        if ($this->isStoreAdmin($user)) {
            return;
        }

        $allowedStore = Location::resolveNicuStoreForUser($user, false);
        if (! $allowedStore || $location->id !== $allowedStore->id) {
            abort(403, 'غير مصرح لك بالوصول لمخازن أقسام أخرى خارج مخزن عهدتك المعتمد.');
        }
    }

    /**
     * الدخول المباشر إلى مخزن وعُهدة الخدج المرتبط بالمستخدم (location_id) أو المعتمد ديناميكياً
     */
    public function index(Request $request): View
    {
        $user = $this->authorizeNicuStore();
        $location = Location::resolveNicuStoreForUser($user, true);

        return $this->renderStoreView($request, $location, $user);
    }

    /**
     * عرض تفاصيل موقع مخزن الخدج مباشرة (للتوافق الخلفي والوصول المباشر)
     */
    public function show(Request $request, Location $location): View
    {
        $user = $this->authorizeNicuStore();
        $this->ensureAuthorizedNicuLocation($user, $location);

        return $this->renderStoreView($request, $location, $user);
    }

    /**
     * تجهيز وعرض لوحة مخزن وعُهدة الخدج المتكاملة
     */
    protected function renderStoreView(Request $request, Location $location, User $user): View
    {
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

        $fromDate = trim((string) $request->query('from_date', ''));
        $toDate = trim((string) $request->query('to_date', ''));

        $movementsQuery = StockMovement::with([
            'batch.product',
            'user',
            'incubatorReservation.patient.user',
            'incubatorReservation.incubator',
        ])
            ->where('from_location_id', $location->id)
            ->whereIn('type', ['dispense', 'issue_to_patient', 'transfer']);

        if ($fromDate !== '') {
            $movementsQuery->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate !== '') {
            $movementsQuery->whereDate('created_at', '<=', $toDate);
        }

        $movements = $movementsQuery->latest()->paginate(20)->withQueryString();

        $transferRequests = StockTransferRequest::with(['fromLocation', 'toLocation', 'requestedBy', 'approvedBy', 'receivedBy'])
            ->where(function ($q) use ($location) {
                $q->where('to_location_id', $location->id)
                    ->orWhere('from_location_id', $location->id);
            })
            ->latest()
            ->take(25)
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

        $monthlyConsumptionStats = StockMovement::where('from_location_id', $location->id)
            ->whereIn('type', ['dispense', 'issue_to_patient'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('count(*) as count, COALESCE(sum(quantity), 0) as total_qty')
            ->first();

        $pendingTransfersCount = $transferRequests
            ->where('to_location_id', $location->id)
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        $lowStockCount = $activeBatches->filter(function ($batch) {
            $threshold = max(1, (int) ($batch->product?->alert_quantity ?? 5));

            return $batch->current_qty <= $threshold;
        })->count();

        $expiringSoonCount = $activeBatches->filter(function ($batch) {
            return $batch->expiry_date && \Carbon\Carbon::parse($batch->expiry_date)->lte(now()->addDays(60));
        })->count();

        $canManageStoreLocation = $this->isStoreAdmin($user);
        $allSubLocations = $canManageStoreLocation
            ? Location::where('type', 'sub')->excludePharmacy()->orderBy('name')->get()
            : collect();

        $stats = [
            'total_items_in_stock' => (int) $activeBatches->sum('current_qty'),
            'unique_products' => $activeBatches->pluck('product_id')->unique()->count(),
            'low_stock_count' => $lowStockCount,
            'expiring_soon_count' => $expiringSoonCount,
            'monthly_consumptions_count' => (int) ($monthlyConsumptionStats->count ?? 0),
            'monthly_consumptions_qty' => (int) ($monthlyConsumptionStats->total_qty ?? 0),
            'pending_transfers_count' => $pendingTransfersCount,
        ];

        return view('nicu-store.show', compact(
            'location',
            'activeBatches',
            'movements',
            'transferRequests',
            'mainLocation',
            'availableStaff',
            'stats',
            'canManageStoreLocation',
            'allSubLocations',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * صرف مستهلك عام لقسم الخدج (بدون ربطه بطفل محدد وبدون أثر مالي)
     */
    public function dispenseDepartmentItem(Request $request, Location $location): RedirectResponse
    {
        $user = $this->authorizeNicuStore();
        $this->ensureAuthorizedNicuLocation($user, $location);

        $validated = $request->validate([
            'batch_id' => 'required|exists:stock_batches,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
        ], [
            'batch_id.required' => 'يرجى اختيار المادة من رصيد مخزن الخدج.',
            'quantity.required' => 'يرجى تحديد الكمية المصروفة.',
            'quantity.min' => 'الكمية المصروفة يجب أن تكون 1 على الأقل.',
        ]);

        try {
            DB::transaction(function () use ($validated, $location, $user) {
                $batch = StockBatch::with('product')
                    ->where('id', $validated['batch_id'])
                    ->where('location_id', $location->id)
                    ->lockForUpdate()
                    ->first();

                if (! $batch) {
                    throw new \Exception('الوجبة المحددة غير موجودة في رصيد مخزن الخدج.');
                }

                if ($batch->current_qty < $validated['quantity']) {
                    throw new \Exception("الكمية المطلوبة ({$validated['quantity']}) تتجاوز الرصيد المتوفر في مخزن الخدج ({$batch->current_qty}).");
                }

                $batch->decrement('current_qty', $validated['quantity']);

                $notes = 'صرف مستهلك عام لقسم الخدج (NICU) - توثيق عهدة بدون أثر مالي';
                if (! empty($validated['notes'])) {
                    $notes .= ' | '.trim($validated['notes']);
                }

                StockMovement::create([
                    'batch_id' => $batch->id,
                    'from_location_id' => $location->id,
                    'to_location_id' => null,
                    'quantity' => $validated['quantity'],
                    'type' => 'dispense',
                    'user_id' => $user->id,
                    'incubator_reservation_id' => null,
                    'notes' => $notes,
                ]);
            });

            return redirect()->back()
                ->with('success', 'تم تسجيل صرف المستهلك العام لقسم الخدج وخصمه من العهدة بنجاح.');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * تغيير وربط الموقع الفرعي المعتمد كمخزن لقسم الخدج (للإدارة العليا / مدير المخازن)
     */
    public function updateStoreLocation(Request $request): RedirectResponse
    {
        $user = $this->authorizeNicuStore();

        if (! $this->isStoreAdmin($user)) {
            abort(403, 'صلاحية تغيير موقع مخزن الخدج المعتمد مخصصة للإدارة أو مدير المخازن فقط.');
        }

        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
        ], [
            'location_id.required' => 'يرجى اختيار الموقع المخزني الفرعي المعتمد لقسم الخدج.',
            'location_id.exists' => 'الموقع المختار غير موجود في جدول المواقع.',
        ]);

        $newLocation = Location::where('type', 'sub')->find($validated['location_id']);
        if (! $newLocation) {
            return redirect()->back()
                ->with('error', 'يجب اختيار مخزن فرعي (Sub Location) وليس المخزن الرئيسي.');
        }

        Location::setNicuStore($newLocation);

        return redirect()->route('nicu-store.index')
            ->with('success', "تم اعتماد وربط الموقع ({$newLocation->name}) كمخزن وعُهدة قسم الخدج (NICU) بنجاح.");
    }

    /**
     * تعيين أو تغيير أمين عهدة مخزن الخدج
     */
    public function assignCustodian(Request $request, Location $location): RedirectResponse
    {
        $user = $this->authorizeNicuStore();
        $this->ensureAuthorizedNicuLocation($user, $location);

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
