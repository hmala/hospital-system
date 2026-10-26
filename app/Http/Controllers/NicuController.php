<?php

namespace App\Http\Controllers;

use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuService;
use App\Models\NicuServiceLog;
use App\Models\NicuVital;
use App\Models\NicuConsultation;
use App\Models\NicuMedicalOrder;
use App\Models\NicuMedicationAdministration;
use App\Models\NicuCareSchedule;
use App\Models\NicuAttachment;
use App\Models\NicuDoctorNote;
use App\Models\LabTest;
use App\Models\RadiologyType;
use App\Models\Medicine;
use App\Models\Doctor;
use App\Models\Room;
use App\Models\Request as MedicalRequest;
use App\Models\LabResult;
use App\Models\UserLabTestGroup;
use App\Services\NicuBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

        if (!$isAdmin && (!$user || (!$user->can('view incubator reservations') && !$user->hasRole(['doctor', 'nurse', 'receptionist', 'nicu_staff', 'nicu_doctor', 'nicu_nurse'])))) {
            abort(403, 'غير مصرح لك بالوصول لقسم الخدج والحاضنات');
        }

        $query = Incubator::with([
            'room',
            'activeReservation.nicuService',
            'activeReservation.patient.user',
            'activeReservation.doctor.user',
            'activeReservation.vitals' => function ($q) {
                $q->latest('recorded_at')->limit(1);
            },
            'activeReservation.careSchedules' => function ($q) {
                $q->where('is_active', true);
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

        if (!$isAdmin && (!$user || (!$user->can('view incubator reservations') && !$user->hasRole(['doctor', 'nurse', 'receptionist', 'nicu_staff', 'nicu_doctor', 'nicu_nurse'])))) {
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
            'medicalOrders.processedByUser',
            'medicationAdministrations.nurse',
            'medicalRequests.labResults',
            'medicalRequests.payments',
            'labResults',
            'careSchedules.creator',
            'attachments.uploader',
            'doctorNotes.doctor',
        ]);

        $stayDetails = $this->billingService->calculateStayDetails($reservation);
        $nicuServices = NicuService::active()->get();
        $doctors = Doctor::with('user')->get();

        // جلب قائمة التحاليل والأشعة والأدوية لمحاكاة محطة عمل الطبيب
        $labTests = LabTest::where('is_active', true)
            ->orderBy('name')
            ->get();

        $radiologyTypes = RadiologyType::where('is_active', true)
            ->orderBy('name')
            ->get();

        $medicines = Medicine::where('is_active', true)
            ->select('id', 'name', 'dosage_form', 'strength')
            ->orderBy('name')
            ->get();

        // جلب باقات ومجموعات التحاليل الخاصة بقسم الخدج والحاضنات حصراً (department = 'nicu')
        $user = Auth::user();
        $labTestGroups = collect();
        if ($user) {
            $doctorUserId = $reservation->doctor?->user_id;

            $labTestGroups = UserLabTestGroup::with('labTests')
                ->where('department', 'nicu')
                ->where(function ($q) use ($user, $doctorUserId) {
                    $q->where('is_public', true)
                      ->orWhere('user_id', $user->id);
                    if ($doctorUserId && $doctorUserId !== $user->id) {
                        $q->orWhere('user_id', $doctorUserId);
                    }
                })
                ->orderBy('name')
                ->get();

            // في حال عدم وجود باقات خاصة، نجلب أي باقات عامة معرفة للخدج
            if ($labTestGroups->isEmpty()) {
                $labTestGroups = UserLabTestGroup::with('labTests')
                    ->where('department', 'nicu')
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('nicu.show', compact(
            'reservation', 
            'stayDetails', 
            'nicuServices', 
            'doctors',
            'labTests',
            'labTestGroups',
            'radiologyTypes',
            'medicines'
        ));
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

        // تحديث جدول الرضاعة النشط وموعد الوجبة القادمة تلقائياً إن وُجدت رضعة
        if ($request->filled('feeding_amount_ml') && (float)$request->feeding_amount_ml > 0) {
            $activeFeeding = $reservation->careSchedules()
                ->where('type', 'feeding')
                ->where('is_active', true)
                ->first();

            if ($activeFeeding) {
                $activeFeeding->update([
                    'last_administered_at' => now(),
                    'next_due_at' => now()->addHours($activeFeeding->interval_hours),
                ]);
            }
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
            'unit' => 'nullable|string|max:50',
            'reference_range' => 'nullable|string|max:100',
            'result_status' => 'nullable|in:normal,high,low,abnormal',
            'technician_notes' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
        ]);

        $order->update([
            'result' => $validated['result'],
            'unit' => $validated['unit'] ?? $order->unit,
            'reference_range' => $validated['reference_range'] ?? $order->reference_range,
            'result_status' => $validated['result_status'] ?? ($order->result_status ?: 'normal'),
            'technician_notes' => $validated['technician_notes'] ?? $order->technician_notes,
            'price' => $validated['price'] ?? $order->price,
            'status' => NicuMedicalOrder::STATUS_COMPLETED,
            'processed_by' => Auth::id(),
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'تم توثيق نتيجة الطلب الطبي بنجاح.');
    }

    /**
     * طلب فحوصات مخبرية للطفل بنمط محطة الطبيب
     */
    public function storeLabOrder(Request $request, IncubatorReservation $reservation)
    {
        $request->validate([
            'tests' => 'nullable|array',
            'tests.*' => 'string',
            'item_name' => 'nullable|string|max:255',
            'priority' => 'required|in:normal,urgent,stat',
            'instructions' => 'nullable|string|max:1000',
            'doctor_notes' => 'nullable|string|max:1000',
        ]);

        $docNotes = $request->input('doctor_notes') ?? $request->input('instructions');

        $testsToCreate = collect();
        if ($request->filled('tests') && is_array($request->tests)) {
            foreach ($request->tests as $t) {
                if (trim($t) !== '') {
                    $testsToCreate->push(trim($t));
                }
            }
        } elseif ($request->filled('item_name')) {
            $testsToCreate->push(trim($request->item_name));
        }

        if ($testsToCreate->isEmpty()) {
            return redirect()->back()->with('error', 'يرجى اختيار أو كتابة فحص مخبري واحد على الأقل.');
        }

        // جلب أسعار ومطابقة التحاليل تلقائياً إن وجدت
        $existingTests = LabTest::whereIn('name', $testsToCreate->all())
            ->orWhereIn('code', $testsToCreate->all())
            ->orWhereIn('id', $testsToCreate->all())
            ->with('references')
            ->get()
            ->keyBy(fn($item) => strtolower($item->name));

        $testNames = [];
        $testIds = [];
        $totalPrice = 0;
        $orderItemsData = [];

        foreach ($testsToCreate as $testName) {
            $matchedTest = $existingTests->get(strtolower($testName));
            $price = 0;
            $unit = null;
            $refRange = null;

            if ($matchedTest) {
                if ($reservation->is_insured && $matchedTest->is_hi_active && $matchedTest->hi_price > 0) {
                    $price = (float)$matchedTest->hi_price;
                } else {
                    $price = (float)$matchedTest->price;
                }
                $unit = $matchedTest->unit;
                $refObj = $matchedTest->references->first();
                $refRange = $refObj ? ($refObj->range_display ?: ($refObj->ref_min . ' - ' . $refObj->ref_max)) : null;
                $testIds[] = $matchedTest->id;
                $testNames[] = $matchedTest->name;
            } else {
                $testNames[] = $testName;
            }

            $totalPrice += $price;

            $orderItemsData[] = [
                'name' => $matchedTest ? $matchedTest->name : $testName,
                'lab_test_id' => $matchedTest?->id,
                'price' => $price,
                'unit' => $unit,
                'ref_range' => $refRange,
            ];
        }

        // 1. إنشاء الطلب الطبي المركزي الموحد في جدول requests (مطابق لطلب الطبيب الاستشاري)
        $medReq = MedicalRequest::create([
            'incubator_reservation_id' => $reservation->id,
            'visit_id' => null,
            'type' => 'lab',
            'subtype' => null,
            'description' => 'تحاليل خديج: ' . implode(', ', $testNames),
            'details' => [
                'tests' => $testNames,
                'lab_test_ids' => $testIds,
                'priority' => $request->priority,
                'doctor_notes' => $docNotes,
                'total_amount' => $totalPrice,
                'incubator_reservation_id' => $reservation->id,
            ],
            'status' => 'pending',
            'payment_status' => 'pending',
            'insurance_type' => $reservation->insurance_type ?? 'none',
            'requested_by' => Auth::id(),
        ]);

        // 2. إنشاء السجلات التوافقية في nicu_medical_orders
        $groupId = 'REQ-' . $medReq->id;
        foreach ($orderItemsData as $item) {
            NicuMedicalOrder::create([
                'reservation_id' => $reservation->id,
                'order_type' => NicuMedicalOrder::TYPE_LAB,
                'item_name' => $item['name'],
                'lab_test_id' => $item['lab_test_id'],
                'price' => $item['price'],
                'priority' => $request->priority,
                'instructions' => $docNotes,
                'order_group_id' => $groupId,
                'unit' => $item['unit'],
                'reference_range' => $item['ref_range'],
                'status' => NicuMedicalOrder::STATUS_PENDING,
                'ordered_by' => Auth::id(),
                'ordered_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', "تم إرسال طلب الفحوصات المخبرية (" . count($testNames) . " فحص) لقسم التحاليل المركزي بنجاح (طلب #{$medReq->id}).");
    }

    /**
     * طلب فحوصات شعاعية وسونار للطفل بنمط محطة الطبيب
     */
    public function storeRadiologyOrder(Request $request, IncubatorReservation $reservation)
    {
        $request->validate([
            'radiology_types' => 'nullable|array',
            'radiology_types.*' => 'string',
            'item_name' => 'nullable|string|max:255',
            'priority' => 'required|in:normal,urgent,stat',
            'instructions' => 'nullable|string|max:1000',
            'doctor_notes' => 'nullable|string|max:1000',
        ]);

        $docNotes = $request->input('doctor_notes') ?? $request->input('instructions');

        $examsToCreate = collect();
        if ($request->filled('radiology_types') && is_array($request->radiology_types)) {
            foreach ($request->radiology_types as $r) {
                if (trim($r) !== '') {
                    $examsToCreate->push(trim($r));
                }
            }
        } elseif ($request->filled('item_name')) {
            $examsToCreate->push(trim($request->item_name));
        }

        if ($examsToCreate->isEmpty()) {
            return redirect()->back()->with('error', 'يرجى اختيار أو كتابة فحص شعاعي واحد على الأقل.');
        }

        $existingRad = RadiologyType::whereIn('name', $examsToCreate->all())
            ->orWhereIn('id', $examsToCreate->all())
            ->get()
            ->keyBy(fn($item) => strtolower($item->name));

        $radNames = [];
        $radTypeIds = [];
        $totalPrice = 0;
        $orderItemsData = [];
        $detectedSubtype = 'general';

        foreach ($examsToCreate as $examIdentifier) {
            $matchedRad = is_numeric($examIdentifier) 
                ? RadiologyType::find($examIdentifier) 
                : $existingRad->get(strtolower($examIdentifier));

            $examName = $matchedRad ? $matchedRad->name : $examIdentifier;
            $price = 0;
            if ($matchedRad) {
                if ($reservation->is_insured && $matchedRad->is_hi_active && $matchedRad->hi_price > 0) {
                    $price = (float)$matchedRad->hi_price;
                } else {
                    $price = (float)$matchedRad->price;
                }
                $radTypeIds[] = $matchedRad->id;
                $radNames[] = $matchedRad->name;

                // كشف الفئة الفرعية (سونار، إيكو، رنين، مفراس، أشعة)
                $cat = mb_strtolower(trim($matchedRad->main_category ?? ''));
                $sub = mb_strtolower(trim($matchedRad->subcategory ?? ''));
                $nm = mb_strtolower(trim($matchedRad->name ?? ''));
                if (str_contains($cat, 'سونار') || str_contains($sub, 'سونار') || str_contains($nm, 'سونار') || str_contains($nm, 'sonar') || str_contains($nm, 'ultrasound')) {
                    $detectedSubtype = 'ultrasound';
                } elseif (str_contains($cat, 'إيكو') || str_contains($sub, 'إيكو') || str_contains($nm, 'إيكو') || str_contains($nm, 'echo')) {
                    $detectedSubtype = 'echo';
                } elseif (str_contains($cat, 'رنين') || str_contains($sub, 'رنين') || str_contains($nm, 'رنين') || str_contains($nm, 'mri')) {
                    $detectedSubtype = 'mri';
                } elseif (str_contains($cat, 'مفراس') || str_contains($sub, 'مفراس') || str_contains($nm, 'مفراس') || str_contains($nm, 'ct')) {
                    $detectedSubtype = 'ct';
                }
            } else {
                $radNames[] = $examName;
            }

            $totalPrice += $price;

            $orderItemsData[] = [
                'name' => $examName,
                'radiology_type_id' => $matchedRad?->id,
                'price' => $price,
            ];
        }

        // 1. إنشاء الطلب المركزي الموحد في جدول requests
        $medReq = MedicalRequest::create([
            'incubator_reservation_id' => $reservation->id,
            'visit_id' => null,
            'type' => 'radiology',
            'subtype' => $detectedSubtype,
            'description' => 'طلب أشعة وسونار خديج: ' . implode(', ', $radNames),
            'details' => [
                'radiology_types' => $radTypeIds,
                'radiology_type_ids' => $radTypeIds,
                'radiology_names' => $radNames,
                'priority' => $request->priority,
                'doctor_notes' => $docNotes,
                'total_amount' => $totalPrice,
                'incubator_reservation_id' => $reservation->id,
            ],
            'status' => 'pending',
            'payment_status' => 'pending',
            'insurance_type' => $reservation->insurance_type ?? 'none',
            'requested_by' => Auth::id(),
        ]);

        // 2. إنشاء السجلات التوافقية في nicu_medical_orders
        $groupId = 'REQ-' . $medReq->id;
        foreach ($orderItemsData as $item) {
            NicuMedicalOrder::create([
                'reservation_id' => $reservation->id,
                'order_type' => NicuMedicalOrder::TYPE_RADIOLOGY,
                'item_name' => $item['name'],
                'radiology_type_id' => $item['radiology_type_id'],
                'price' => $item['price'],
                'priority' => $request->priority,
                'instructions' => $docNotes,
                'order_group_id' => $groupId,
                'status' => NicuMedicalOrder::STATUS_PENDING,
                'ordered_by' => Auth::id(),
                'ordered_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', "تم إرسال طلب الفحص الشعاعي (" . count($radNames) . " فحص) لقسم الأشعة والتصوير الطبي بنجاح (طلب #{$medReq->id}).");
    }

    /**
     * كتابة واعتماد الوصفة والعلاجات الدوائية للطفل
     */
    public function storePrescription(Request $request, IncubatorReservation $reservation)
    {
        $request->validate([
            'medications' => 'nullable|array',
            'medications.*.name' => 'required_with:medications|string|max:255',
            'medications.*.dose' => 'nullable|string|max:100',
            'medications.*.route' => 'nullable|string|max:50',
            'medications.*.frequency' => 'nullable|string|max:100',
            'medications.*.duration' => 'nullable|string|max:100',
            'medications.*.instructions' => 'nullable|string|max:500',
            // Or single direct item
            'item_name' => 'nullable|string|max:255',
            'dose' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:50',
            'frequency' => 'nullable|string|max:100',
            'duration' => 'nullable|string|max:100',
            'instructions' => 'nullable|string|max:500',
            'price' => 'nullable|numeric|min:0',
        ]);

        $medsList = collect();
        if ($request->filled('medications') && is_array($request->medications)) {
            foreach ($request->medications as $m) {
                if (!empty($m['name'])) {
                    $medsList->push($m);
                }
            }
        } elseif ($request->filled('item_name')) {
            $medsList->push([
                'name' => $request->item_name,
                'dose' => $request->dose,
                'route' => $request->route,
                'frequency' => $request->frequency,
                'duration' => $request->duration,
                'instructions' => $request->instructions,
                'price' => $request->price ?? 0,
            ]);
        }

        if ($medsList->isEmpty()) {
            return redirect()->back()->with('error', 'يرجى إدخال اسم الدواء أو العلاج المطلوب.');
        }

        $count = 0;
        foreach ($medsList as $med) {
            $instructionsFormatted = [];
            if (!empty($med['dose'])) $instructionsFormatted[] = "الجرعة: " . $med['dose'];
            if (!empty($med['route'])) $instructionsFormatted[] = "طريقة الإعطاء: " . $med['route'];
            if (!empty($med['frequency'])) $instructionsFormatted[] = "التكرار: " . $med['frequency'];
            if (!empty($med['duration'])) $instructionsFormatted[] = "المدة: " . $med['duration'];
            if (!empty($med['instructions'])) $instructionsFormatted[] = "ملاحظات: " . $med['instructions'];

            NicuMedicalOrder::create([
                'reservation_id' => $reservation->id,
                'order_type' => NicuMedicalOrder::TYPE_MEDICATION,
                'item_name' => $med['name'],
                'price' => isset($med['price']) ? (float)$med['price'] : 0,
                'priority' => 'urgent',
                'instructions' => implode(' | ', $instructionsFormatted),
                'status' => NicuMedicalOrder::STATUS_PENDING,
                'ordered_by' => Auth::id(),
                'ordered_at' => now(),
            ]);
            $count++;
        }

        return redirect()->back()->with('success', "تم اعتماد {$count} علاج دوائي في الوصفة الطبية للطفل بنجاح.");
    }

    /**
     * تسجيل وتوثيق إعطاء وتطبيق العلاج للطفل في محطة التمريض (MAR)
     */
    public function storeMedicationAdministration(Request $request, IncubatorReservation $reservation)
    {
        $validated = $request->validate([
            'medication_name' => 'required|string|max:255',
            'route' => 'required|string|max:50',
            'dose' => 'required|string|max:100',
            'given_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['reservation_id'] = $reservation->id;
        $validated['nurse_id'] = Auth::id();
        $validated['given_at'] = $request->filled('given_at') 
            ? Carbon::parse($request->given_at) 
            : now();

        NicuMedicationAdministration::create($validated);

        // مطابقة وتحديث جدول العلاج النشط وموعد الجرعة القادمة تلقائياً
        $medName = trim($validated['medication_name']);
        $matchedSchedule = $reservation->careSchedules()
            ->where('type', 'medication')
            ->where('is_active', true)
            ->where(function($q) use ($medName) {
                $q->where('title', 'like', "%{$medName}%")
                  ->orWhereRaw("? LIKE CONCAT('%', title, '%')", [$medName]);
            })
            ->first();

        if ($matchedSchedule) {
            $matchedSchedule->update([
                'last_administered_at' => $validated['given_at'],
                'next_due_at' => Carbon::parse($validated['given_at'])->addHours($matchedSchedule->interval_hours),
            ]);
        }

        return redirect()->back()->with('success', 'تم توثيق إعطاء وتطبيق الجرعة العلاجية في سجل التمريض بنجاح.');
    }

    /**
     * طباعة الوصفة الطبية الرسمية للخديج (RX Print)
     */
    public function printPrescription(IncubatorReservation $reservation)
    {
        $reservation->load([
            'patient.user',
            'doctor.user',
            'incubator.room',
            'medicalOrders' => function ($q) {
                $q->where('order_type', NicuMedicalOrder::TYPE_MEDICATION)
                  ->where('status', '!=', NicuMedicalOrder::STATUS_CANCELLED);
            }
        ]);

        return view('nicu.print.prescription', compact('reservation'));
    }

    /**
     * حفظ توصية جديدة أو جدول رعاية (رضاعة / دواء)
     */
    public function storeCareSchedule(Request $request, IncubatorReservation $reservation)
    {
        $validated = $request->validate([
            'type' => 'required|in:feeding,medication',
            'title' => 'required|string|max:255',
            'dose_or_amount' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:100',
            'interval_hours' => 'required|integer|min:1|max:48',
            'start_time' => 'nullable|date',
            'instructions' => 'nullable|string|max:1000',
        ]);

        $startTime = $request->filled('start_time') ? Carbon::parse($request->start_time) : now();
        $nextDueAt = $startTime;

        // في حال كان جدول رضاعة جديد، يتم إلغاء تنشيط جداول الرضاعة السابقة لتكون هناك خطة رضاعة معتمدة واحدة
        if ($validated['type'] === 'feeding') {
            $reservation->careSchedules()->where('type', 'feeding')->update(['is_active' => false]);
        }

        NicuCareSchedule::create([
            'reservation_id' => $reservation->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'dose_or_amount' => $validated['dose_or_amount'] ?? null,
            'route' => $validated['route'] ?? null,
            'interval_hours' => $validated['interval_hours'],
            'start_time' => $startTime,
            'next_due_at' => $nextDueAt,
            'instructions' => $validated['instructions'] ?? null,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        $typeName = $validated['type'] === 'feeding' ? 'جدول الرضاعة' : 'جدول إعطاء العلاج';
        return redirect()->back()->with('success', "تم اعتماد {$typeName} بنجاح وتفعيل التنبيهات على لوحة الحاضنات.");
    }

    /**
     * حفظ دفعة توصيات متعددة بوقت واحد (Batch)
     */
    public function storeCareSchedulesBatch(Request $request, IncubatorReservation $reservation)
    {
        $request->validate([
            'schedules' => 'required|array|min:1',
            'schedules.*.type' => 'required|in:feeding,medication',
            'schedules.*.title' => 'required|string|max:255',
            'schedules.*.dose_or_amount' => 'nullable|string|max:100',
            'schedules.*.route' => 'nullable|string|max:100',
            'schedules.*.interval_hours' => 'required|integer|min:1|max:48',
            'schedules.*.instructions' => 'nullable|string|max:1000',
        ]);

        $createdCount = 0;
        foreach ($request->schedules as $schedData) {
            if (empty(trim($schedData['title'] ?? ''))) {
                continue;
            }

            if ($schedData['type'] === 'feeding') {
                $reservation->careSchedules()->where('type', 'feeding')->update(['is_active' => false]);
            }

            NicuCareSchedule::create([
                'reservation_id' => $reservation->id,
                'type' => $schedData['type'],
                'title' => trim($schedData['title']),
                'dose_or_amount' => $schedData['dose_or_amount'] ?? null,
                'route' => $schedData['route'] ?? null,
                'interval_hours' => (int) $schedData['interval_hours'],
                'start_time' => now(),
                'next_due_at' => now(),
                'instructions' => $schedData['instructions'] ?? null,
                'is_active' => true,
                'created_by' => Auth::id(),
            ]);
            $createdCount++;
        }

        return redirect()->back()->with('success', "تم اعتماد وإدراج {$createdCount} من التوصيات وجداول الرعاية بنجاح.");
    }

    /**
     * تفعيل أو إيقاف التوصية
     */
    public function toggleCareSchedule(NicuCareSchedule $schedule)
    {
        $schedule->update([
            'is_active' => !$schedule->is_active,
        ]);

        $statusText = $schedule->is_active ? 'تفعيل' : 'إيقاف';
        return redirect()->back()->with('success', "تم {$statusText} التوصية بنجاح.");
    }

    /**
     * حذف التوصية
     */
    public function destroyCareSchedule(NicuCareSchedule $schedule)
    {
        $schedule->delete();
        return redirect()->back()->with('success', 'تم حذف التوصية من الخطة العلاجية بنجاح.');
    }

    /**
     * توثيق إعطاء سريع للجرعة أو الرضعة وتحديث الموعد القادم تلقائياً
     */
    public function administerCareScheduleQuick(Request $request, NicuCareSchedule $schedule)
    {
        $now = now();

        if ($schedule->type === 'medication') {
            NicuMedicationAdministration::create([
                'reservation_id' => $schedule->reservation_id,
                'medication_name' => $schedule->title,
                'dose' => $schedule->dose_or_amount ?: 'جرعة موصوفة',
                'route' => $schedule->route ?: 'وريدي IV',
                'given_at' => $now,
                'notes' => 'توثيق تطبيق سريع استناداً إلى جدول العلاج',
                'nurse_id' => Auth::id(),
            ]);
        } elseif ($schedule->type === 'feeding') {
            $amountMl = null;
            if (preg_match('/(\d+(\.\d+)?)/', $schedule->dose_or_amount ?? '', $matches)) {
                $amountMl = (float)$matches[1];
            }
            NicuVital::create([
                'reservation_id' => $schedule->reservation_id,
                'nurse_id' => Auth::id(),
                'recorded_at' => $now,
                'feeding_type' => $schedule->title,
                'feeding_amount_ml' => $amountMl,
                'feeding_notes' => 'توثيق رضاعة سريع وفق جدول الرضاعة',
            ]);
        }

        $nextDue = $now->copy()->addHours($schedule->interval_hours);
        $schedule->update([
            'last_administered_at' => $now,
            'next_due_at' => $nextDue,
        ]);

        return redirect()->back()->with('success', "تم توثيق الإعطاء بنجاح. الموعد القادم: الساعة " . $nextDue->format('H:i') . ".");
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

        // صمام الأمان المالي: منع الخروج في حال وجود رصيد متبقي بذمة الحاضنة
        if (($stayDetails['net_balance'] ?? 0) > 0) {
            return redirect()->back()
                             ->with('error', '⛔ لا يمكن إتمام الخروج الطبي للطفل لوجود رصيد مالي متبقي بذمة الحاضنة بمبلغ (' . number_format($stayDetails['net_balance']) . ' د.ع). يرجى التوجيه لكاشير الخدج لتسوية الحساب كاملاً أولاً.');
        }

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

    /**
     * رفع وأرشفة مستند/ملف جديد للطفل الخديج
     */
    public function storeAttachment(Request $request, IncubatorReservation $reservation)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || (!$user->can('manage nicu attachments') && !$user->can('manage incubator reservations') && !$user->hasRole(['nicu_doctor', 'nicu_nurse', 'doctor', 'nurse', 'nicu_staff'])))) {
            abort(403, 'غير مصرح لك برفع مرفقات بالخدج');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'notes' => 'nullable|string|max:1000',
        ], [
            'title.required' => 'يرجى كتابة اسم أو وصف للمستند المرفق',
            'file.required' => 'يرجى اختيار ملف لرفعه',
            'file.mimes' => 'يجب أن يكون الملف بصيغة PDF أو صورة (JPG, PNG, WebP)',
            'file.max' => 'الحد الأقصى لحجم الملف هو 10 ميغابايت',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();
        $fileSize = $file->getSize();

        $storageName = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('nicu_attachments/' . $reservation->id, $storageName, 'public');

        NicuAttachment::create([
            'incubator_reservation_id' => $reservation->id,
            'patient_id' => $reservation->patient_id,
            'user_id' => $user?->id,
            'title' => $validated['title'],
            'file_path' => $path,
            'file_name' => $fileName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'تم حفظ وأرشفة المستند بنجاح في السجل الطبي للطفل');
    }

    /**
     * تحميل مستند من الأرشيف
     */
    public function downloadAttachment(NicuAttachment $attachment)
    {
        if (!Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'الملف غير موجود في الخادم');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
    }

    /**
     * حذف مستند من الأرشيف
     */
    public function destroyAttachment(NicuAttachment $attachment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || (!$user->can('manage nicu attachments') && !$user->can('manage incubator reservations') && !$user->hasRole(['nicu_doctor', 'doctor', 'nicu_staff'])))) {
            abort(403, 'غير مصرح لك بحذف مرفقات الخدج');
        }

        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return redirect()->back()->with('success', 'تم حذف المستند بنجاح من الأرشيف');
    }

    /**
     * تدوين ملاحظة / توجيه جديد للطبيب الاختصاص
     */
    public function storeDoctorNote(Request $request, IncubatorReservation $reservation)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || (!$user->can('manage nicu doctor notes') && !$user->can('manage incubator reservations') && !$user->hasRole(['doctor', 'nicu_doctor', 'nicu_staff'])))) {
            abort(403, 'غير مصرح لك بتدوين ملاحظات الطبيب الاختصاص');
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'is_urgent' => 'nullable|boolean',
            'is_pinned' => 'nullable|boolean',
        ], [
            'content.required' => 'يرجى كتابة نص الملاحظة أو التوجيه السريري',
        ]);

        NicuDoctorNote::create([
            'incubator_reservation_id' => $reservation->id,
            'doctor_id' => $user?->id,
            'doctor_name' => $user?->name,
            'content' => $validated['content'],
            'is_urgent' => $request->boolean('is_urgent'),
            'is_pinned' => $request->boolean('is_pinned'),
        ]);

        return redirect()->back()->with('success', 'تم تدوين وحفظ توجيه وملاحظة الطبيب الاختصاص بنجاح');
    }

    /**
     * تثبيت أو إلغاء تثبيت ملاحظة الطبيب
     */
    public function togglePinDoctorNote(NicuDoctorNote $note)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || (!$user->can('manage nicu doctor notes') && !$user->can('manage incubator reservations') && !$user->hasRole(['doctor', 'nicu_doctor', 'nicu_staff'])))) {
            abort(403, 'غير مصرح لك بتعديل حالة الملاحظة');
        }

        $note->update(['is_pinned' => !$note->is_pinned]);

        return redirect()->back()->with('success', $note->is_pinned ? 'تم تثبيت التوجيه كإشعار رئيسي بنجاح' : 'تم إلغاء التثبيت');
    }

    /**
     * حذف ملاحظة طبيب
     */
    public function destroyDoctorNote(NicuDoctorNote $note)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || (!$user->can('manage nicu doctor notes') && !$user->can('manage incubator reservations') && !$user->hasRole(['doctor', 'nicu_doctor', 'nicu_staff'])))) {
            abort(403, 'غير مصرح لك بحذف ملاحظة الطبيب');
        }

        $note->delete();

        return redirect()->back()->with('success', 'تم حذف ملاحظة الطبيب بنجاح');
    }
}
