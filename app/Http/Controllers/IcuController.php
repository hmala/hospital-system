<?php

namespace App\Http\Controllers;

use App\Models\CcuService;
use App\Models\Doctor;
use App\Models\IcuAdmission;
use App\Models\IcuBed;
use App\Models\IcuCareSchedule;
use App\Models\IcuFluidBalance;
use App\Models\IcuMedicalOrder;
use App\Models\IcuVitalSign;
use App\Services\CcuBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IcuController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('view icu radar')) {
            abort(403, 'غير مصرح لك بالوصول إلى رادار وحدة العناية المركزة');
        }

        $query = IcuBed::with([
            'currentAdmission.patient.user',
            'currentAdmission.doctor.user',
            'currentAdmission.latestVital',
            'currentAdmission.latestFluidBalance',
        ])->where('is_active', true);

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('bed_type', $request->type);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('bed_number', 'like', "%{$search}%")
                    ->orWhere('room_number', 'like', "%{$search}%")
                    ->orWhereHas('currentAdmission.patient', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($uq) use ($search) {
                                $uq->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $beds = $query->orderByNumber()->get();

        // إحصائيات KPI الحية
        $stats = [
            'total_beds' => IcuBed::where('is_active', true)->count(),
            'occupied_beds' => IcuBed::where('status', IcuBed::STATUS_OCCUPIED)->count(),
            'available_beds' => IcuBed::where('status', IcuBed::STATUS_AVAILABLE)->count(),
            'sterilizing_beds' => IcuBed::where('status', IcuBed::STATUS_STERILIZING)->count(),
            'ventilated_count' => IcuAdmission::active()->where('is_ventilated', true)->count(),
            'isolated_count' => IcuAdmission::active()->where('is_isolated', true)->count(),
            'critical_gcs_count' => IcuAdmission::active()->whereHas('latestVital', function ($vq) {
                $vq->where('gcs', '<=', 8);
            })->count(),
        ];

        return view('icu.radar', compact('beds', 'stats'));
    }

    /**
     * إعادة التوجيه مع الحفاظ على التبويب النشط وموضع التمرير
     */
    protected function redirectBackWithActiveState(?Request $request = null, ?string $defaultTab = null, ?string $fallbackUrl = null)
    {
        $req = $request ?: request();
        $tab = $req->input('_active_tab', $defaultTab);

        $redirect = $fallbackUrl ? redirect()->back(fallback: $fallbackUrl) : redirect()->back();
        if ($tab) {
            $redirect->with('active_tab', $tab);
        }

        return $redirect;
    }

    public function show(IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('view icu radar')) {
            abort(403, 'غير مصرح لك بالاطلاع على إضبارة مريض العناية');
        }

        $admission->load([
            'patient.user',
            'bed',
            'doctor.user',
            'vitals.recordedByUser',
            'fluidBalances.recordedByUser',
            'medicalOrders.doctor.user',
            'medicalOrders.completedByUser',
            'medicalOrders.labTest',
            'medicalOrders.radiologyType',
            'medicalOrders.orderedByUser',
            'careSchedules.doneByUser',
            'ccuDoctorNotes.doctor.user',
            'ccuDoctorNotes.creator',
            'ccuMedicationSchedules.administeredBy',
            'ccuMedicationSchedules.doctor.user',
            'ccuConsultations.doctor.user',
            'ccuConsultations.requestedByUser',
            'ccuConsultations.respondedByUser',
            'ccuAttachments.uploader',
            'ccuService',
            'ccuServiceLogs.ccuService',
            'ccuPayments.cashier',
            'dischargedByUser',
        ]);

        $billingService = app(CcuBillingService::class);
        $billingService->ensureDefaultsExist();
        $stayDetails = $billingService->calculateStayDetails($admission);
        $ccuPackageServices = CcuService::active()->where('service_type', CcuService::TYPE_PACKAGE)->orderBy('id')->get();
        $ccuAddonServices = CcuService::active()->where('service_type', CcuService::TYPE_ADDON)->orderBy('id')->get();

        $activeDoctors = Doctor::with('user')->where('is_active', true)->get();
        $medicines = \App\Models\Medicine::where('is_active', true)->orderBy('name')->take(200)->get(['id', 'name', 'strength', 'dosage_form']);

        // ملخص السوائل لليوم الحالي
        $todayBalances = $admission->fluidBalances->filter(function ($fb) {
            return $fb->recorded_at && $fb->recorded_at->isToday();
        });

        $todayIntake = $todayBalances->sum('intake_total');
        $todayOutput = $todayBalances->sum('output_total');
        $todayNet = $todayIntake - $todayOutput;

        $labTests = \App\Models\LabTest::where('is_active', true)->orderBy('main_category')->orderBy('name')->get();
        $radiologyTypes = \App\Models\RadiologyType::where('is_active', true)->orderBy('main_category')->orderBy('name')->get();
        $labOrders = $admission->medicalOrders->where('order_type', 'lab');
        $radiologyOrders = $admission->medicalOrders->where('order_type', 'radiology');

        $pinnedNotes = $admission->ccuPinnedDoctorNotes;
        $doctorNotes = $admission->ccuDoctorNotes;
        $medicationSchedules = $admission->ccuMedicationSchedules;
        $consultations = $admission->ccuConsultations;
        $attachments = $admission->ccuAttachments;

        return view('icu.show', compact(
            'admission',
            'activeDoctors',
            'medicines',
            'todayIntake',
            'todayOutput',
            'todayNet',
            'labTests',
            'radiologyTypes',
            'labOrders',
            'radiologyOrders',
            'pinnedNotes',
            'doctorNotes',
            'medicationSchedules',
            'consultations',
            'attachments',
            'ccuPackageServices',
            'ccuAddonServices',
            'stayDetails'
        ));
    }

    public function storeVitals(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('record icu vitals')) {
            abort(403, 'غير مصرح لك بتسجيل مؤشرات العناية');
        }

        $validated = $request->validate([
            'recorded_at' => 'nullable|date',
            'gcs' => 'nullable|integer|min:3|max:15',
            'heart_rate' => 'nullable|integer|min:20|max:300',
            'systolic_bp' => 'nullable|integer|min:30|max:300',
            'diastolic_bp' => 'nullable|integer|min:10|max:200',
            'mean_arterial_pressure' => 'nullable|integer|min:20|max:250',
            'respiratory_rate' => 'nullable|integer|min:4|max:80',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'spo2' => 'nullable|integer|min:30|max:100',
            'oxygen_device' => 'nullable|string|max:100',
            'fio2' => 'nullable|integer|min:21|max:100',
            'peep' => 'nullable|numeric|min:0|max:30',
            'ventilator_mode' => 'nullable|string|max:100',
            'blood_sugar' => 'nullable|integer|min:20|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['icu_admission_id'] = $admission->id;
        $validated['recorded_at'] = ! empty($validated['recorded_at']) ? Carbon::parse($validated['recorded_at']) : Carbon::now();
        $validated['recorded_by'] = Auth::id();

        // حساب MAP تلقائياً إن لم يدخل
        if (empty($validated['mean_arterial_pressure']) && ! empty($validated['systolic_bp']) && ! empty($validated['diastolic_bp'])) {
            $validated['mean_arterial_pressure'] = (int) round(($validated['systolic_bp'] + (2 * $validated['diastolic_bp'])) / 3);
        }

        IcuVitalSign::create($validated);

        // إذا كان الجهاز تنفس اصطناعي، يتم تحديث شارة المريض تلقائياً
        if (! empty($validated['ventilator_mode']) || (! empty($validated['oxygen_device']) && str_contains(strtolower($validated['oxygen_device']), 'ventilator'))) {
            $admission->update(['is_ventilated' => true]);
        }

        return $this->redirectBackWithActiveState($request, 'vitalsTabPane', route('icu.show', $admission->id))->with('success', 'تم تدوين العلامات الحيوية ومعايير العناية بنجاح.');
    }

    public function storeFluidBalance(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('record icu fluid balance')) {
            abort(403, 'غير مصرح لك بتسجيل ميزان السوائل');
        }

        $validated = $request->validate([
            'recorded_at' => 'nullable|date',
            'intake_iv_fluids' => 'nullable|integer|min:0',
            'intake_blood_products' => 'nullable|integer|min:0',
            'intake_enteral_feeding' => 'nullable|integer|min:0',
            'intake_medications' => 'nullable|integer|min:0',
            'output_urine' => 'nullable|integer|min:0',
            'output_drains' => 'nullable|integer|min:0',
            'output_nasogastric' => 'nullable|integer|min:0',
            'output_other' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['icu_admission_id'] = $admission->id;
        $validated['recorded_at'] = ! empty($validated['recorded_at']) ? Carbon::parse($validated['recorded_at']) : Carbon::now();
        $validated['recorded_by'] = Auth::id();

        IcuFluidBalance::create($validated);

        return $this->redirectBackWithActiveState($request, 'fluidsTabPane', route('icu.show', $admission->id))->with('success', 'تم تسجيل قراءة ميزان السوائل واحتساب الصافي بنجاح.');
    }

    public function storeMedicalOrder(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('manage icu medical orders')) {
            abort(403, 'غير مصرح لك بكتابة أوامر الطبيب');
        }

        $validated = $request->validate([
            'order_type' => 'required|string|in:medication,investigation,nursing_care,ventilator_setting,diet,discharge',
            'order_text' => 'required|string|max:2000',
            'priority' => 'required|string|in:routine,urgent,stat',
            'doctor_id' => 'nullable|exists:doctors,id',
        ]);

        $doctorId = $validated['doctor_id'] ?? null;
        if (! $doctorId && $user->doctor) {
            $doctorId = $user->doctor->id;
        }
        if (! $doctorId) {
            $doctorId = $admission->doctor_id;
        }

        IcuMedicalOrder::create([
            'icu_admission_id' => $admission->id,
            'doctor_id' => $doctorId,
            'order_type' => $validated['order_type'],
            'order_text' => $validated['order_text'],
            'priority' => $validated['priority'],
            'status' => IcuMedicalOrder::STATUS_PENDING,
            'ordered_by' => Auth::id(),
            'ordered_at' => now(),
        ]);

        return $this->redirectBackWithActiveState($request, 'ordersTabPane', route('icu.show', $admission->id))->with('success', 'تم إصدار وتوثيق أمر الطبيب بنجاح.');
    }

    public function completeMedicalOrder(Request $request, IcuMedicalOrder $order)
    {
        $order->update([
            'status' => IcuMedicalOrder::STATUS_COMPLETED,
            'completed_at' => Carbon::now(),
            'completed_by' => Auth::id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم إنجاز الأمر بنجاح']);
        }

        $fallback = $order->icu_admission_id ? route('icu.show', $order->icu_admission_id) : route('icu.index');

        return $this->redirectBackWithActiveState($request, 'ordersTabPane', $fallback)->with('success', 'تم تأكيد إنجاز وتطبيق أمر الطبيب.');
    }

    public function storeCareSchedule(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('manage icu care plans')) {
            abort(403, 'غير مصرح لك بإدارة خطة رعاية التمريض');
        }

        $validated = $request->validate([
            'care_type' => 'required|string|max:255',
            'scheduled_time' => 'required|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['icu_admission_id'] = $admission->id;

        IcuCareSchedule::create($validated);

        return $this->redirectBackWithActiveState($request, 'nursingTabPane', route('icu.show', $admission->id))->with('success', 'تمت إضافة مهمة الرعاية التمريضية بنجاح.');
    }

    public function toggleCareSchedule(Request $request, IcuCareSchedule $schedule)
    {
        $isDone = ! $schedule->is_done;
        $schedule->update([
            'is_done' => $isDone,
            'done_at' => $isDone ? Carbon::now() : null,
            'done_by' => $isDone ? Auth::id() : null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'is_done' => $isDone]);
        }

        $fallback = $schedule->icu_admission_id ? route('icu.show', $schedule->icu_admission_id) : route('icu.index');

        return $this->redirectBackWithActiveState($request, 'nursingTabPane', $fallback)->with('success', 'تم تحديث حالة الرعاية التمريضية.');
    }

    /**
     * طلب فحوصات مخبرية لمريض العناية المركزة
     */
    public function storeLabOrder(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('manage icu medical orders')) {
            abort(403, 'غير مصرح لك بطلب تحاليل العناية المركزة');
        }

        if ($admission->status === IcuAdmission::STATUS_DISCHARGED) {
            return $this->redirectBackWithActiveState($request, 'labTabPane')
                ->with('error', '⛔ تم تخريج المريض من العناية. لا يمكن طلب تحاليل مخبرية.');
        }

        $request->validate([
            'lab_test_ids' => 'nullable|array',
            'custom_tests' => 'nullable|array',
            'tests' => 'nullable|array',
            'item_name' => 'nullable|string|max:255',
            'priority' => 'required|in:normal,urgent,stat',
            'instructions' => 'nullable|string|max:1000',
            'doctor_notes' => 'nullable|string|max:1000',
        ]);

        $docNotes = $request->input('doctor_notes') ?? $request->input('instructions');

        $rawItems = array_merge(
            (array) $request->input('lab_test_ids', []),
            (array) $request->input('custom_tests', []),
            (array) $request->input('tests', [])
        );
        if ($request->filled('item_name')) {
            $rawItems[] = $request->input('item_name');
        }

        $testsToCreate = collect($rawItems)->map(fn ($item) => is_string($item) ? trim($item) : $item)->filter(fn ($item) => $item !== '' && $item !== null)->values();

        if ($testsToCreate->isEmpty()) {
            return $this->redirectBackWithActiveState($request, 'labTabPane', route('icu.show', $admission->id))->with('error', 'يرجى اختيار أو كتابة فحص مخبري واحد على الأقل.');
        }

        $existingTests = \App\Models\LabTest::whereIn('name', $testsToCreate->all())
            ->orWhereIn('code', $testsToCreate->all())
            ->orWhereIn('id', $testsToCreate->all())
            ->with('references')
            ->get()
            ->keyBy(fn ($item) => strtolower($item->name));

        $testNames = [];
        $testIds = [];
        $totalPrice = 0;
        $orderItemsData = [];

        foreach ($testsToCreate as $testIdentifier) {
            $matchedTest = is_numeric($testIdentifier)
                ? \App\Models\LabTest::with('references')->find($testIdentifier)
                : ($existingTests->get(strtolower($testIdentifier)) ?: \App\Models\LabTest::where('code', $testIdentifier)->with('references')->first());

            $testName = $matchedTest ? $matchedTest->name : $testIdentifier;
            $price = 0;
            $unit = null;
            $refRange = null;

            if ($matchedTest) {
                if ($admission->is_insured && $matchedTest->is_hi_active && $matchedTest->hi_price > 0) {
                    $price = (float) $matchedTest->hi_price;
                } else {
                    $price = (float) $matchedTest->price;
                }
                $unit = $matchedTest->unit;
                $refObj = $matchedTest->references->first();
                $refRange = $refObj ? ($refObj->range_display ?: ($refObj->ref_min.' - '.$refObj->ref_max)) : null;
                $testIds[] = $matchedTest->id;
                $testNames[] = $matchedTest->name;
            } else {
                $testNames[] = $testName;
            }

            $totalPrice += $price;

            $orderItemsData[] = [
                'name' => $testName,
                'lab_test_id' => $matchedTest?->id,
                'price' => $price,
                'unit' => $unit,
                'ref_range' => $refRange,
            ];
        }

        // 1. إنشاء الطلب الطبي المركزي في جدول requests
        $medReq = \App\Models\Request::create([
            'icu_admission_id' => $admission->id,
            'visit_id' => null,
            'type' => 'lab',
            'subtype' => null,
            'description' => 'تحاليل مريض العناية المركزة (سرير #'.optional($admission->bed)->bed_number.'): '.implode(', ', $testNames),
            'details' => [
                'tests' => $testNames,
                'lab_test_ids' => $testIds,
                'priority' => $request->priority,
                'doctor_notes' => $docNotes,
                'total_amount' => $totalPrice,
                'icu_admission_id' => $admission->id,
            ],
            'status' => 'pending',
            'payment_status' => 'pending',
            'insurance_type' => $admission->insurance_category ?? ($admission->is_insured ? 'hi' : 'none'),
            'requested_by' => Auth::id(),
        ]);

        // 2. إنشاء البنود في جدول icu_medical_orders
        $groupId = 'REQ-'.$medReq->id;
        foreach ($orderItemsData as $item) {
            IcuMedicalOrder::create([
                'icu_admission_id' => $admission->id,
                'doctor_id' => $admission->doctor_id,
                'order_type' => 'lab',
                'item_name' => $item['name'],
                'lab_test_id' => $item['lab_test_id'],
                'price' => $item['price'],
                'original_price' => $item['price'],
                'billing_mode' => 'auto',
                'priority' => $request->priority,
                'order_text' => $item['name'].($docNotes ? ' - '.$docNotes : ''),
                'instructions' => $docNotes,
                'order_group_id' => $groupId,
                'unit' => $item['unit'],
                'reference_range' => $item['ref_range'],
                'status' => IcuMedicalOrder::STATUS_PENDING,
                'ordered_by' => Auth::id(),
                'ordered_at' => now(),
            ]);
        }

        return $this->redirectBackWithActiveState($request, 'labTabPane', route('icu.show', $admission->id))
            ->with('success', 'تم إرسال طلب الفحوصات المخبرية ('.count($testNames)." فحص) لقسم التحاليل المركزي بنجاح (طلب #{$medReq->id}).");
    }

    /**
     * طلب فحوصات شعاعية وتصوير لمريض العناية المركزة
     */
    public function storeRadiologyOrder(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('manage icu medical orders')) {
            abort(403, 'غير مصرح لك بطلب أشعة العناية المركزة');
        }

        if ($admission->status === IcuAdmission::STATUS_DISCHARGED) {
            return $this->redirectBackWithActiveState($request, 'radiologyTabPane', route('icu.show', $admission->id))
                ->with('error', '⛔ تم تخريج المريض من العناية. لا يمكن طلب فحوصات أشعة.');
        }

        $request->validate([
            'radiology_type_ids' => 'nullable|array',
            'custom_radiology' => 'nullable|array',
            'radiology_types' => 'nullable|array',
            'item_name' => 'nullable|string|max:255',
            'priority' => 'required|in:normal,urgent,stat',
            'instructions' => 'nullable|string|max:1000',
            'doctor_notes' => 'nullable|string|max:1000',
        ]);

        $docNotes = $request->input('doctor_notes') ?? $request->input('instructions');

        $rawItems = array_merge(
            (array) $request->input('radiology_type_ids', []),
            (array) $request->input('custom_radiology', []),
            (array) $request->input('radiology_types', [])
        );
        if ($request->filled('item_name')) {
            $rawItems[] = $request->input('item_name');
        }

        $examsToCreate = collect($rawItems)->map(fn ($item) => is_string($item) ? trim($item) : $item)->filter(fn ($item) => $item !== '' && $item !== null)->values();

        if ($examsToCreate->isEmpty()) {
            return $this->redirectBackWithActiveState($request, 'radiologyTabPane', route('icu.show', $admission->id))->with('error', 'يرجى اختيار أو كتابة فحص شعاعي واحد على الأقل.');
        }

        $existingRad = \App\Models\RadiologyType::whereIn('name', $examsToCreate->all())
            ->orWhereIn('id', $examsToCreate->all())
            ->get()
            ->keyBy(fn ($item) => strtolower($item->name));

        $radNames = [];
        $radTypeIds = [];
        $totalPrice = 0;
        $orderItemsData = [];
        $detectedSubtype = 'general';

        foreach ($examsToCreate as $examIdentifier) {
            $matchedRad = is_numeric($examIdentifier)
                ? \App\Models\RadiologyType::find($examIdentifier)
                : ($existingRad->get(strtolower($examIdentifier)) ?: \App\Models\RadiologyType::where('code', $examIdentifier)->first());

            $examName = $matchedRad ? $matchedRad->name : $examIdentifier;
            $price = 0;
            if ($matchedRad) {
                if ($admission->is_insured && $matchedRad->is_hi_active && $matchedRad->hi_price > 0) {
                    $price = (float) $matchedRad->hi_price;
                } else {
                    $price = (float) $matchedRad->price;
                }
                $radTypeIds[] = $matchedRad->id;
                $radNames[] = $matchedRad->name;

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
        $medReq = \App\Models\Request::create([
            'icu_admission_id' => $admission->id,
            'visit_id' => null,
            'type' => 'radiology',
            'subtype' => $detectedSubtype,
            'description' => 'طلب أشعة وتصوير مريض العناية (سرير #'.optional($admission->bed)->bed_number.'): '.implode(', ', $radNames),
            'details' => [
                'radiology_types' => $radTypeIds,
                'radiology_type_ids' => $radTypeIds,
                'radiology_names' => $radNames,
                'priority' => $request->priority,
                'doctor_notes' => $docNotes,
                'total_amount' => $totalPrice,
                'icu_admission_id' => $admission->id,
            ],
            'status' => 'pending',
            'payment_status' => 'pending',
            'insurance_type' => $admission->insurance_category ?? ($admission->is_insured ? 'hi' : 'none'),
            'requested_by' => Auth::id(),
        ]);

        // 2. إنشاء السجلات في جدول icu_medical_orders
        $groupId = 'REQ-'.$medReq->id;
        foreach ($orderItemsData as $item) {
            IcuMedicalOrder::create([
                'icu_admission_id' => $admission->id,
                'doctor_id' => $admission->doctor_id,
                'order_type' => 'radiology',
                'item_name' => $item['name'],
                'radiology_type_id' => $item['radiology_type_id'],
                'price' => $item['price'],
                'original_price' => $item['price'],
                'billing_mode' => 'auto',
                'priority' => $request->priority,
                'order_text' => $item['name'].($docNotes ? ' - '.$docNotes : ''),
                'instructions' => $docNotes,
                'order_group_id' => $groupId,
                'status' => IcuMedicalOrder::STATUS_PENDING,
                'ordered_by' => Auth::id(),
                'ordered_at' => now(),
            ]);
        }

        return $this->redirectBackWithActiveState($request, 'radiologyTabPane', route('icu.show', $admission->id))
            ->with('success', 'تم إرسال طلب الفحص الشعاعي ('.count($radNames)." فحص) لقسم الأشعة والتصوير الطبي بنجاح (طلب #{$medReq->id}).");
    }
}
