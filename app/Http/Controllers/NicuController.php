<?php

namespace App\Http\Controllers;

use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuService;
use App\Models\NicuServiceLog;
use App\Models\NicuVital;
use App\Models\NicuConsultation;
use App\Models\NicuMedicalOrder;
use App\Models\Doctor;
use App\Models\Room;
use App\Services\NicuBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NicuController extends Controller
{
    protected NicuBillingService $billingService;

    public function __construct(NicuBillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * لوحة إشغال الحاضنات اللحظية (NICU Live Board)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || (!$user->can('view incubator reservations') && !$user->hasRole(['doctor', 'nurse', 'receptionist', 'nicu_staff'])))) {
            abort(403, 'غير مصرح لك بالوصول لقسم الخدج والحاضنات');
        }

        $query = Incubator::with([
            'room',
            'activeReservation.nicuService',
            'activeReservation.patient.user',
            'activeReservation.doctor.user',
            'activeReservation.vitals' => function ($q) {
                $q->latest('recorded_at')->limit(1);
            }
        ]);

        if ($request->filled('type')) {
            $query->where('incubator_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $incubators = $query->orderBy('incubator_number')->get();

        // خدمات الخدج النشطة
        $nicuServices = NicuService::active()->get();

        // إحصائيات لوحة التحكم
        $stats = [
            'total' => $incubators->count(),
            'available' => $incubators->where('status', Incubator::STATUS_AVAILABLE)->count(),
            'occupied' => $incubators->where('status', Incubator::STATUS_OCCUPIED)->count(),
            'sterilizing' => $incubators->where('status', Incubator::STATUS_STERILIZING)->count(),
            'maintenance' => $incubators->where('status', Incubator::STATUS_MAINTENANCE)->count(),
            'capsule' => $incubators->where('incubator_type', Incubator::TYPE_CAPSULE)->count(),
        ];

        // غرف ردهة الأطفال المرتبطة
        $pediatricRooms = Room::where('is_active', true)
            ->where(function($q) {
                $q->where('room_purpose', 'incubators')
                  ->orWhere('room_type', 'incubator')
                  ->orWhere('room_type', 'capsule');
            })->get();

        return view('nicu.index', compact('incubators', 'stats', 'nicuServices', 'pediatricRooms'));
    }

    /**
     * سجل وإضبارات حجوزات الخدج (NICU Reservations Registry)
     */
    public function reservationsIndex(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || (!$user->can('view incubator reservations') && !$user->hasRole(['doctor', 'nurse', 'receptionist', 'nicu_staff'])))) {
            abort(403, 'غير مصرح لك بالوصول لسجل وإضبارات الخدج');
        }

        $query = IncubatorReservation::with([
            'patient.user',
            'incubator.room',
            'nicuService',
            'doctor.user',
            'department',
            'vitals' => function ($q) {
                $q->latest('recorded_at')->limit(1);
            }
        ]);

        // البحث (Search)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('baby_name', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhereHas('patient.user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('incubator', function ($iq) use ($search) {
                      $iq->where('incubator_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('doctor.user', function ($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // فلترة الحالة (Status Filter)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // فلترة باقة الخدمة (Service Filter)
        if ($request->filled('nicu_service_id') && $request->nicu_service_id !== 'all') {
            $query->where('nicu_service_id', $request->nicu_service_id);
        }

        // فلترة نوع الحاضنة (Incubator Type)
        if ($request->filled('incubator_type') && $request->incubator_type !== 'all') {
            $query->whereHas('incubator', function ($iq) use ($request) {
                $iq->where('incubator_type', $request->incubator_type);
            });
        }

        // فلترة التواريخ (Date Filter)
        if ($request->filled('date_from')) {
            $query->whereDate('admission_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('admission_date', '<=', $request->date_to);
        }

        // الترتيب: النشطون أولاً ثم الانتظار ثم المخرجين ثم الملغي، ثم الأحدث
        $reservations = $query->orderByRaw("CASE status WHEN 'admitted' THEN 1 WHEN 'pending' THEN 2 WHEN 'transferred' THEN 3 WHEN 'discharged' THEN 4 ELSE 5 END")
                              ->latest('admission_date')
                              ->latest('id')
                              ->paginate(15)
                              ->withQueryString();

        // إحصائيات سريعة للرأس
        $kpis = [
            'total' => IncubatorReservation::count(),
            'admitted' => IncubatorReservation::where('status', IncubatorReservation::STATUS_ADMITTED)->count(),
            'discharged' => IncubatorReservation::where('status', IncubatorReservation::STATUS_DISCHARGED)->count(),
            'discharged_month' => IncubatorReservation::where('status', IncubatorReservation::STATUS_DISCHARGED)
                ->whereMonth('discharge_date', now()->month)
                ->whereYear('discharge_date', now()->year)
                ->count(),
            'pending' => IncubatorReservation::where('status', IncubatorReservation::STATUS_PENDING)->count(),
        ];

        $services = NicuService::active()->orderBy('name')->get();

        return view('nicu.reservations.index', compact('reservations', 'kpis', 'services'));
    }

    /**
     * ملف الطفل والمركز السريري للحاضنة
     */
    public function show(IncubatorReservation $reservation)
    {
        $reservation->load([
            'patient.user',
            'incubator.room',
            'nicuService',
            'doctor.user',
            'serviceLogs.nicuService',
            'serviceLogs.switchedByUser',
            'vitals.nurse',
            'consultations.internalDoctor.user',
            'consultations.requestedByUser',
            'medicalOrders.orderedByUser',
        ]);

        $stayDetails = $this->billingService->calculateStayDetails($reservation);
        $nicuServices = NicuService::active()->get();
        $doctors = Doctor::with('user')->get();

        return view('nicu.show', compact('reservation', 'stayDetails', 'nicuServices', 'doctors'));
    }

    /**
     * تبديل الخدمة السريرية للطفل عند 12:00 ظهراً
     */
    public function switchService(Request $request, IncubatorReservation $reservation)
    {
        $request->validate([
            'nicu_service_id' => 'required|exists:nicu_services,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $newService = NicuService::findOrFail($request->nicu_service_id);
        $this->billingService->switchService($reservation, $newService, $request->reason, Auth::id());

        return redirect()->back()->with('success', 'تم تبديل الخدمة السريرية إلى: ' . $newService->name . ' بنجاح.');
    }

    /**
     * تعديل سجل خدمة مفتوح
     */
    public function updateServiceLog(Request $request, IncubatorReservation $reservation, NicuServiceLog $serviceLog)
    {
        if ($serviceLog->reservation_id !== $reservation->id) {
            abort(403, 'السجل لا ينتمي لهذا الحجز');
        }

        $request->validate([
            'nicu_service_id' => 'required|exists:nicu_services,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $newService = NicuService::findOrFail($request->nicu_service_id);

        $serviceLog->update([
            'nicu_service_id' => $newService->id,
            'daily_fee' => $newService->daily_fee,
            'total_cost' => ($serviceLog->days_count ?: 1) * (float)$newService->daily_fee,
            'reason_for_switch' => $request->reason ?: $serviceLog->reason_for_switch,
            'switched_by' => Auth::id(),
            'notes' => 'تم تعديل السجل يدوياً من محطة الرعاية.',
        ]);

        if (is_null($serviceLog->end_time)) {
            $reservation->update(['nicu_service_id' => $newService->id]);
        }

        return redirect()->back()->with('success', 'تم تعديل باقة الخدمة السريرية بنجاح.');
    }

    /**
     * حذف سجل تبديل والتراجع عنه
     */
    public function destroyServiceLog(IncubatorReservation $reservation, NicuServiceLog $serviceLog)
    {
        if ($serviceLog->reservation_id !== $reservation->id) {
            abort(403, 'السجل لا ينتمي لهذا الحجز');
        }

        // إذا كان هناك سجل سابق، نعيد فتحه ونستعيد الخدمة
        $previousLog = $reservation->serviceLogs()
            ->where('id', '<', $serviceLog->id)
            ->latest('id')
            ->first();

        $serviceLog->delete();

        if ($previousLog) {
            $previousLog->update([
                'end_time' => null,
            ]);
            $reservation->update([
                'nicu_service_id' => $previousLog->nicu_service_id,
            ]);
        }

        return redirect()->back()->with('success', 'تم التراجع عن التبديل وحذف السجل بنجاح.');
    }

    /**
     * تسجيل العلامات الحيوية والوزن والرضاعة
     */
    public function storeVitals(Request $request, IncubatorReservation $reservation)
    {
        $validated = $request->validate([
            'temperature' => 'nullable|numeric|between:30,45',
            'incubator_temperature' => 'nullable|numeric|between:25,42',
            'heart_rate' => 'nullable|integer|between:40,250',
            'respiratory_rate' => 'nullable|integer|between:10,120',
            'spo2' => 'nullable|integer|between:50,100',
            'blood_pressure' => 'nullable|string|max:30',
            'weight' => 'nullable|integer|min:300|max:10000',
            'feeding_type' => 'nullable|string|max:100',
            'feeding_amount_ml' => 'nullable|numeric|min:0',
            'feeding_notes' => 'nullable|string|max:500',
        ]);

        $validated['reservation_id'] = $reservation->id;
        $validated['nurse_id'] = Auth::id();
        $validated['recorded_at'] = now();

        NicuVital::create($validated);

        if (!empty($validated['weight'])) {
            $reservation->update(['current_weight' => $validated['weight']]);
        }

        return redirect()->back()->with('success', 'تم توثيق العلامات الحيوية والوزن بنجاح.');
    }

    /**
     * طلب استشارة طبيب (داخلية / خارجية)
     */
    public function storeConsultation(Request $request, IncubatorReservation $reservation)
    {
        $validated = $request->validate([
            'consultation_type' => 'required|in:internal,external',
            'doctor_id' => 'nullable|required_if:consultation_type,internal|exists:doctors,id',
            'external_doctor_name' => 'nullable|required_if:consultation_type,external|string|max:200',
            'specialty' => 'nullable|required_if:consultation_type,external|string|max:150',
            'fee' => 'required|numeric|min:0',
            'reason' => 'required|string|max:1000',
        ]);

        // في الاستشارة الداخلية يتم اعتماد التخصص الدقيق للطبيب تلقائياً من بياناته المسجلة
        if ($validated['consultation_type'] === 'internal' && !empty($validated['doctor_id'])) {
            if (empty($validated['specialty'])) {
                $doc = Doctor::find($validated['doctor_id']);
                $validated['specialty'] = $doc?->specialization ?? 'أخصائي استشاري';
            }
        }

        $validated['reservation_id'] = $reservation->id;
        $validated['requested_by'] = Auth::id();
        $validated['status'] = NicuConsultation::STATUS_REQUESTED;

        NicuConsultation::create($validated);

        return redirect()->back()->with('success', 'تم إرسال طلب الاستشارة الطبية بنجاح.');
    }

    /**
     * إكمال استشارة وتدوين التقرير
     */
    public function completeConsultation(Request $request, NicuConsultation $consultation)
    {
        $validated = $request->validate([
            'findings' => 'required|string',
            'recommendations' => 'nullable|string',
        ]);

        $consultation->update([
            'findings' => $validated['findings'],
            'recommendations' => $validated['recommendations'] ?? null,
            'status' => NicuConsultation::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تم توثيق فحص وتوصيات الاستشارة الطبية بنجاح.');
    }

    /**
     * إصدار طلب فحص مخبري، أشعة، أو علاج
     */
    public function storeMedicalOrder(Request $request, IncubatorReservation $reservation)
    {
        $validated = $request->validate([
            'order_type' => 'required|in:lab,radiology,medication',
            'item_name' => 'required|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'priority' => 'required|in:normal,urgent,stat',
            'instructions' => 'nullable|string|max:500',
        ]);

        $validated['reservation_id'] = $reservation->id;
        $validated['ordered_by'] = Auth::id();
        $validated['ordered_at'] = now();
        $validated['price'] = $validated['price'] ?? 0;
        $validated['status'] = NicuMedicalOrder::STATUS_PENDING;

        NicuMedicalOrder::create($validated);

        return redirect()->back()->with('success', 'تم إرسال الطلب الطبي لقسم المعني بنجاح.');
    }

    /**
     * إكمال طلب طبي وتدوين النتيجة
     */
    public function completeMedicalOrder(Request $request, NicuMedicalOrder $order)
    {
        $validated = $request->validate([
            'result' => 'required|string',
            'price' => 'nullable|numeric|min:0',
        ]);

        $order->update([
            'result' => $validated['result'],
            'price' => $validated['price'] ?? $order->price,
            'status' => NicuMedicalOrder::STATUS_COMPLETED,
            'processed_by' => Auth::id(),
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تم توثيق نتيجة الطلب الطبي بنجاح.');
    }

    /**
     * أمر الخروج الطبي وحساب التصفية
     */
    public function discharge(Request $request, IncubatorReservation $reservation)
    {
        $request->validate([
            'discharge_type' => 'required|in:improved,step_down,transferred_out,dama,expired',
            'discharge_notes' => 'nullable|string|max:1000',
        ]);

        $stayDetails = $this->billingService->calculateStayDetails($reservation);

        $now = now();
        $reservation->update([
            'status' => IncubatorReservation::STATUS_DISCHARGED,
            'discharge_date' => $now->toDateString(),
            'discharge_time' => $now->format('H:i'),
            'discharge_type' => $request->discharge_type,
            'discharge_notes' => $request->discharge_notes,
            'total_cost' => $stayDetails['grand_total'],
        ]);

        return redirect()->route('nicu.statement', $reservation)
                         ->with('success', 'تم تسجيل الخروج الطبي للطفل بنجاح والحاضنة الآن في مرحلة التعقيم.');
    }

    /**
     * تأكيد تعقيم وتطهير الحاضنة وتحويلها إلى متاحة 🟢
     */
    public function markSterilized(Incubator $incubator)
    {
        $incubator->update(['status' => Incubator::STATUS_AVAILABLE]);

        return redirect()->back()->with('success', 'تم تأكيد تعقيم وتطهير الحاضنة رقم ' . $incubator->incubator_number . ' وهي الآن جاهزة للاستقبال 🟢');
    }

    /**
     * توليد رابط كاميرا الأب لدقيقة واحدة
     */
    public function generateBabyCamToken(IncubatorReservation $reservation)
    {
        $token = Str::random(32);
        $reservation->update([
            'camera_token' => $token,
            'camera_active' => true,
        ]);

        $camUrl = route('nicu.baby-cam', ['token' => $token]);

        return response()->json([
            'success' => true,
            'token' => $token,
            'url' => $camUrl,
            'baby_name' => $reservation->baby_name,
            'phone' => optional($reservation->patient?->user)->phone,
        ]);
    }

    /**
     * شاشة بث كاميرا الحاضنة للأب لدقيقة واحدة
     */
    public function babyCamView(string $token)
    {
        $reservation = IncubatorReservation::where('camera_token', $token)
            ->where('camera_active', true)
            ->with(['incubator', 'patient.user'])
            ->firstOrFail();

        return view('nicu.baby_cam', compact('reservation'));
    }

    /**
     * كشف الحساب التراكمي الشفاف القابل للطباعة
     */
    public function statement(IncubatorReservation $reservation)
    {
        $reservation->load([
            'patient.user',
            'incubator.room',
            'nicuService',
            'doctor.user',
            'serviceLogs.nicuService',
            'consultations',
            'medicalOrders',
        ]);

        $stayDetails = $this->billingService->calculateStayDetails($reservation);

        return view('nicu.statement', compact('reservation', 'stayDetails'));
    }

    /**
     * طباعة أسورة المعصم للطفل
     */
    public function wristbandPrint(IncubatorReservation $reservation)
    {
        $reservation->load(['patient.user', 'incubator', 'doctor.user']);
        return view('nicu.print.wristband', compact('reservation'));
    }

    /**
     * طباعة كارت الحاضنة الشفاف
     */
    public function cribCardPrint(IncubatorReservation $reservation)
    {
        $reservation->load(['patient.user', 'incubator.room', 'nicuService', 'doctor.user']);
        return view('nicu.print.crib_card', compact('reservation'));
    }
}
