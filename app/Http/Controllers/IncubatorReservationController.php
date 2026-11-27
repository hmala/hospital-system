<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuAttachment;
use App\Models\Patient;
use Illuminate\Http\Request;

class IncubatorReservationController extends Controller
{
    /**
     * عرض قائمة حجوزات الحاضنات
     */
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('view incubator reservations'))) {
            abort(403, 'غير مصرح لك بعرض حجوزات الحاضنات');
        }

        $reservations = IncubatorReservation::with([
            'patient.user',
            'incubator',
            'doctor.user',
            'department',
        ])
            ->latest()
            ->paginate(20);

        return view('incubator_reservations.index', compact('reservations'));
    }

    /**
     * عرض نموذج إنشاء حجز جديد
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('create incubator reservations'))) {
            abort(403, 'غير مصرح لك بإنشاء حجز حاضنة خدج');
        }

        // الحصول على المريض إذا تم تمريره مسبقاً في الرابط أو عبر تحويل من معاينة الساعتين للوليد
        $patient = null;
        $existingReservation = null;
        $prefillObservationBaby = null;

        if ($request->filled('observation_baby_id')) {
            $prefillObservationBaby = \App\Models\NicuDeliveryObservationBaby::with(['observation.patient.user', 'observation.surgery'])
                ->find($request->observation_baby_id);
        }

        $targetPatientId = $request->input('patient_id') ?: $prefillObservationBaby?->observation?->patient_id;

        if ($targetPatientId) {
            $patient = Patient::with(['user', 'healthInsuranceCategory'])->find($targetPatientId);

            if ($patient) {
                // تحقق سريع: هل للطفل/الأم حجز نشط بالفعل؟ (حتى نظهر تحذير فور دخول الصفحة)
                $existingReservation = IncubatorReservation::where('patient_id', $patient->id)
                    ->whereIn('status', [IncubatorReservation::STATUS_PENDING, IncubatorReservation::STATUS_ADMITTED])
                    ->with('incubator')
                    ->first();
            }
        }

        // تم الاستغناء عن جلب كل المرضى مسبقاً، واعتمدنا البحث المباشر السريع (Live Search)
        $patients = $patient ? collect([$patient]) : collect();

        // اختيار نوع الحاضنة كمحدد للفرز
        $selectedType = $request->input('incubator_type');
        $incubatorTypes = [
            Incubator::TYPE_NORMAL => 'حاضنة',
            Incubator::TYPE_CAPSULE => 'كبسولة علاج ضوئي',
        ];

        // إذا تم تمرير حاضنة مسبقاً، نستخدم نوعها كتحديد افتراضي
        if (! $selectedType && $request->has('incubator_id')) {
            $selectedIncubator = Incubator::find($request->incubator_id);
            if ($selectedIncubator) {
                $selectedType = $selectedIncubator->incubator_type;
            }
        }

        $incubatorsQuery = Incubator::available();
        if ($selectedType) {
            $incubatorsQuery->ofType($selectedType);
        }

        $incubators = $incubatorsQuery->orderByNumber()->get();
        $doctors = Doctor::with('user')->get();
        $departments = Department::where('is_active', true)->get();
        $nicuServices = \App\Models\NicuService::active()->get();
        $healthInsuranceCategories = \App\Models\HealthInsuranceCategory::where('is_active', true)->orderBy('sort_order')->get();

        return view('incubator_reservations.create', compact(
            'patient',
            'patients',
            'incubators',
            'doctors',
            'departments',
            'selectedType',
            'incubatorTypes',
            'existingReservation',
            'nicuServices',
            'healthInsuranceCategories',
            'prefillObservationBaby'
        ));
    }

    /**
     * حفظ حجز جديد
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('create incubator reservations'))) {
            abort(403, 'غير مصرح لك بحفظ حجز حاضنة خدج');
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'is_independent_patient' => 'nullable|boolean',
            'baby_name' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'birth_certificate_no' => 'nullable|string|max:100',
            'birth_certificate_date' => 'nullable|date',
            'incubator_id' => 'required|exists:incubators,id',
            'incubator_type' => 'nullable|in:normal,capsule',
            'nicu_service_id' => 'nullable|exists:nicu_services,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'department_id' => 'nullable|exists:departments,id',
            'birth_weight' => 'nullable|integer',
            'gestational_age' => 'nullable|string|max:50',
            'admission_source' => 'nullable|string|max:50',
            'admission_date' => 'required|date',
            'admission_time' => 'required|date_format:H:i',
            'medical_notes' => 'nullable|string',
            'admission_notes' => 'nullable|string',
            'insurance_type' => 'nullable|string|in:none,hi,moi,health_insurance',
            'policy_category' => 'nullable|string|max:50',
            'encounter_number' => 'nullable|string|max:100',
            'insurance_card_no' => 'nullable|string|max:100',
            'birth_certificate_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'scanned_birth_certificate' => 'nullable|string',
            'parent_id_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'scanned_parent_id' => 'nullable|string',
            'referral_doc_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'scanned_referral_doc' => 'nullable|string',
            'admission_consent_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'scanned_admission_consent' => 'nullable|string',
            'print_consent_after_save' => 'nullable|boolean',
            'observation_baby_id' => 'nullable|integer|exists:nicu_delivery_observation_babies,id',
        ]);

        $printConsentAfterSave = $request->boolean('print_consent_after_save');
        unset($validated['print_consent_after_save']);

        $observationBabyId = $validated['observation_baby_id'] ?? null;
        unset($validated['observation_baby_id']);

        if ($observationBabyId) {
            $obsBabyPrefill = \App\Models\NicuDeliveryObservationBaby::with('observation')->find($observationBabyId);
            if ($obsBabyPrefill) {
                if (empty($validated['birth_weight']) && $obsBabyPrefill->weight_grams) {
                    $validated['birth_weight'] = $obsBabyPrefill->weight_grams;
                }
                if (empty($validated['admission_reason']) && $obsBabyPrefill->observation?->assessment) {
                    $validated['admission_reason'] = $obsBabyPrefill->observation->assessment;
                }
                if (empty($validated['maternal_history']) && $obsBabyPrefill->observation?->history_of_illness) {
                    $validated['maternal_history'] = $obsBabyPrefill->observation->history_of_illness;
                }
            }
        }

        // سحب وتوليد اسم الطفل وتحديد صفة المريض (طفل مستقل أو تابع للأم/الأب)
        $patientUser = Patient::with('user')->findOrFail($validated['patient_id']);
        $isIndependent = $request->input('is_independent_patient');
        if ($isIndependent === null || $isIndependent === '') {
            if ($patientUser->age !== null) {
                $isIndependent = ($patientUser->age < 1);
            } else {
                $pName = optional($patientUser->user)->name ?? '';
                $isIndependent = str_starts_with($pName, 'طفل') || str_starts_with($pName, 'مولود') || str_starts_with($pName, 'رضيع');
            }
        } else {
            $isIndependent = filter_var($isIndependent, FILTER_VALIDATE_BOOLEAN);
            if ($patientUser->age !== null && $patientUser->age < 1) {
                $isIndependent = true;
            }
        }
        $validated['is_independent_patient'] = $isIndependent;

        if ($isIndependent) {
            // المريض المسجل هو الطفل نفسه بشكل مستقل (محول أو مسجل مباشرة)
            if (! empty($validated['baby_name'])) {
                $validated['baby_name'] = trim($validated['baby_name']);
            } else {
                $validated['baby_name'] = optional($patientUser->user)->name ?? 'طفل خديج';
            }
        } else {
            // المريض المسجل هو الأم أو الأب
            $motherOrGuardianName = optional($patientUser->user)->name ?? 'غير معروف';
            if (! empty($validated['baby_name'])) {
                $validated['baby_name'] = trim($validated['baby_name']);
            } else {
                $validated['baby_name'] = str_starts_with($motherOrGuardianName, 'طفل')
                    ? $motherOrGuardianName
                    : ('طفل '.$motherOrGuardianName);
            }
        }

        $validated['guardian_name'] = ! empty($validated['guardian_name'])
            ? trim($validated['guardian_name'])
            : (! $isIndependent ? (optional($patientUser->user)->name ?: null) : null);

        $validated['current_weight'] = $validated['birth_weight'] ?? null;
        $validated['insurance_type'] = $validated['insurance_type'] ?? 'none';

        if ($validated['insurance_type'] === 'hi' || ! empty($validated['policy_category'])) {
            if (empty($validated['policy_category'])) {
                $validated['policy_category'] = 'E';
            } elseif (is_numeric($validated['policy_category'])) {
                $catModel = \App\Models\HealthInsuranceCategory::find($validated['policy_category']);
                $validated['policy_category'] = $catModel ? $catModel->code : 'E';
            }
        }

        // تم إلغاء شرط العمر للسماح بحجز الحاضنة باسم الأم أو المسؤول

        // التحقق من أن المريض ليس لديه حجز نشط حالياً لنفس الطفل
        $existingPatientReservation = IncubatorReservation::where('patient_id', $validated['patient_id'])
            ->whereIn('status', [IncubatorReservation::STATUS_PENDING, IncubatorReservation::STATUS_ADMITTED])
            ->where('baby_name', $validated['baby_name'])
            ->first();

        if ($existingPatientReservation) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'هذا المريض لديه حجز نشط بالفعل في الحاضنة رقم '.$existingPatientReservation->incubator->incubator_number.'. يجب إنهاء الحجز الحالي أولاً.');
        }

        // التحقق من توفر الحاضنة
        $incubator = Incubator::findOrFail($validated['incubator_id']);
        if (! $incubator->isAvailable()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'الحاضنة المحددة غير متاحة حالياً');
        }

        // التحقق من عدم وجود حجوزات نشطة للحاضنة
        $existingIncubatorReservation = IncubatorReservation::where('incubator_id', $validated['incubator_id'])
            ->whereIn('status', [IncubatorReservation::STATUS_PENDING, IncubatorReservation::STATUS_ADMITTED])
            ->first();

        if ($existingIncubatorReservation) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'الحاضنة رقم '.$incubator->incubator_number.' محجوزة بالفعل للطفل '.$existingIncubatorReservation->baby_name.'. يرجى اختيار حاضنة أخرى.');
        }

        // توليد رمز كاميرا المشاهدة المباشرة للأب
        $validated['camera_token'] = \Illuminate\Support\Str::random(32);
        $validated['camera_active'] = true;
        $validated['status'] = IncubatorReservation::STATUS_ADMITTED;

        // إنشاء الحجز
        $reservation = IncubatorReservation::create($validated);

        if ($observationBabyId) {
            $obsBaby = \App\Models\NicuDeliveryObservationBaby::with('observation')->find($observationBabyId);
            if ($obsBaby) {
                $obsBaby->update([
                    'disposition' => \App\Models\NicuDeliveryObservationBaby::DISPOSITION_ADMITTED_TO_NICU,
                    'incubator_reservation_id' => $reservation->id,
                ]);
                $obsBaby->observation?->recalculateStatus();
            }
        }

        // حفظ الوثائق والمرفقات الرسمية المرفوعة أو الممسوحة بالسكانر
        $attachmentSpecs = [
            [
                'file' => 'birth_certificate_file',
                'scan' => 'scanned_birth_certificate',
                'type' => NicuAttachment::TYPE_BIRTH_CERTIFICATE,
                'title' => 'بيان الولادة الرسمي'.(! empty($reservation->birth_certificate_no) ? ' (رقم '.$reservation->birth_certificate_no.')' : ''),
                'notes' => 'مستند بيان الولادة المرفق أثناء تسجيل واستقبال الخديج',
            ],
            [
                'file' => 'parent_id_file',
                'scan' => 'scanned_parent_id',
                'type' => NicuAttachment::TYPE_PARENT_ID,
                'title' => 'البطاقة الموحدة / هوية الأحوال للأم أو الأب',
                'notes' => 'مستند الهوية المرفق أثناء تسجيل واستقبال الخديج',
            ],
            [
                'file' => 'referral_doc_file',
                'scan' => 'scanned_referral_doc',
                'type' => NicuAttachment::TYPE_REFERRAL_DOC,
                'title' => 'تقرير الإحالة / وثيقة التحويل الطبي',
                'notes' => 'وثيقة التحويل الطبي أو الإحالة من خارج المستشفى',
            ],
            [
                'file' => 'admission_consent_file',
                'scan' => 'scanned_admission_consent',
                'type' => NicuAttachment::TYPE_ADMISSION_CONSENT,
                'title' => 'تعهد دخول الخدج الموقّع (ولي الأمر)',
                'notes' => 'تعهد ولي أمر المريض بالتدخلات العلاجية لمرضى دخول الخدج',
            ],
        ];

        foreach ($attachmentSpecs as $spec) {
            if ($request->hasFile($spec['file']) || $request->filled($spec['scan'])) {
                NicuAttachment::storeForReservation(
                    $reservation,
                    $user,
                    $spec['title'],
                    $spec['type'],
                    $request->file($spec['file']),
                    $request->input($spec['scan']),
                    $spec['notes']
                );
            }
        }

        // تحديث حالة الحاضنة إلى مشغولة فور الدخول
        $incubator->update(['status' => Incubator::STATUS_OCCUPIED]);

        // إنشاء سجل الخدمة الأولي
        if ($reservation->nicu_service_id) {
            $service = \App\Models\NicuService::find($reservation->nicu_service_id);
            if ($service) {
                $effectiveFee = (float) $service->getEffectiveFee($reservation->insurance_type ?? 'none');
                \App\Models\NicuServiceLog::create([
                    'reservation_id' => $reservation->id,
                    'nicu_service_id' => $service->id,
                    'daily_fee' => $effectiveFee,
                    'start_time' => \Carbon\Carbon::parse($reservation->admission_date->format('Y-m-d').' '.$reservation->admission_time->format('H:i')),
                    'end_time' => null,
                    'days_count' => 1,
                    'total_cost' => $effectiveFee,
                    'reason_for_switch' => 'تسجيل دخول واستقبال أولي',
                    'switched_by' => $user->id,
                ]);
            }
        }

        // حساب تفاصيل الإقامة والحصص التأمينية
        app(\App\Services\NicuBillingService::class)->calculateStayDetails($reservation);

        $printConsentAfterSave = $request->boolean('print_consent_after_save');
        $canViewNicu = $user && ($user->can('view incubator reservations') || $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'doctor', 'nurse', 'nicu_staff', 'nicu_doctor', 'nicu_nurse']));

        if ($printConsentAfterSave) {
            return redirect()->route('nicu.print.admission-consent', ['reservation' => $reservation, 'autoprint' => 1])
                ->with('success', 'تم حجز واستقبال الخديج بنجاح في قسم الخدج والحاضنات.');
        }

        if (! $canViewNicu) {
            return redirect()->route('incubator-reservations.create')
                ->with('success', 'تم حجز واستقبال الخديج بنجاح في قسم الخدج والحاضنات.');
        }

        return redirect()->route('nicu.show', $reservation)
            ->with('success', 'تم حجز واستقبال الخديج بنجاح في قسم الخدج والحاضنات.')
            ->with('show_consent_prompt', true);
    }

    /**
     * عرض تفاصيل حجز معين
     */
    public function show(IncubatorReservation $incubatorReservation)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('view incubator reservations'))) {
            abort(403, 'غير مصرح لك بعرض تفاصيل حجز الحاضنة');
        }

        $incubatorReservation->load([
            'patient.user',
            'incubator.room',
            'doctor.user',
            'department',
        ]);

        return view('incubator_reservations.show', compact('incubatorReservation'));
    }

    /**
     * تأكيد دخول الطفل للحاضنة
     */
    public function admit(IncubatorReservation $incubatorReservation)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('manage incubator reservations'))) {
            abort(403, 'غير مصرح لك بتسجيل دخول الطفل للحاضنة');
        }

        if ($incubatorReservation->status !== IncubatorReservation::STATUS_PENDING) {
            return redirect()->back()
                ->with('error', 'الحجز ليس في حالة الانتظار');
        }

        $incubatorReservation->update([
            'status' => IncubatorReservation::STATUS_ADMITTED,
            'admission_date' => now()->toDateString(),
            'admission_time' => now()->format('H:i'),
        ]);

        return redirect()->back()
            ->with('success', 'تم تسجيل دخول الطفل للحاضنة بنجاح');
    }

    /**
     * تسجيل خروج الطفل من الحاضنة
     */
    public function discharge(Request $request, IncubatorReservation $incubatorReservation)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('manage incubator reservations'))) {
            abort(403, 'غير مصرح لك بتسجيل خروج الطفل من الحاضنة');
        }

        if ($incubatorReservation->status !== IncubatorReservation::STATUS_ADMITTED) {
            return redirect()->back()
                ->with('error', 'الطفل ليس داخل الحاضنة حالياً');
        }

        $validated = $request->validate([
            'discharge_notes' => 'nullable|string',
        ]);

        $billingService = app(\App\Services\NicuBillingService::class);
        $stayDetails = $billingService->calculateStayDetails($incubatorReservation);

        // صمام الأمان المالي: منع الخروج في حال وجود رصيد متبقي بذمة الحاضنة
        if (($stayDetails['net_balance'] ?? 0) > 0) {
            return redirect()->back()
                ->with('error', '⛔ لا يمكن إتمام خروج الطفل لوجود رصيد مالي متبقي بذمة الحاضنة بمبلغ ('.number_format($stayDetails['net_balance']).' د.ع). يرجى التوجيه لكاشير الخدج لتسوية الحساب كاملاً أولاً.');
        }

        $incubatorReservation->update([
            'status' => IncubatorReservation::STATUS_DISCHARGED,
            'discharge_date' => now()->toDateString(),
            'discharge_time' => now()->format('H:i'),
            'discharge_notes' => $validated['discharge_notes'] ?? null,
            'total_cost' => $incubatorReservation->calculateActualCost(),
        ]);

        return redirect()->back()
            ->with('success', 'تم تسجيل خروج الطفل من الحاضنة بنجاح');
    }

    /**
     * إلغاء حجز
     */
    public function cancel(IncubatorReservation $incubatorReservation)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('manage incubator reservations'))) {
            abort(403, 'غير مصرح لك بإلغاء حجز الحاضنة');
        }

        if (in_array($incubatorReservation->status, [
            IncubatorReservation::STATUS_DISCHARGED,
            IncubatorReservation::STATUS_CANCELLED,
        ])) {
            return redirect()->back()
                ->with('error', 'لا يمكن إلغاء هذا الحجز');
        }

        $incubatorReservation->update([
            'status' => IncubatorReservation::STATUS_CANCELLED,
        ]);

        return redirect()->back()
            ->with('success', 'تم إلغاء الحجز بنجاح');
    }

    /**
     * نقل الطفل إلى حاضنة أخرى
     */
    public function transfer(Request $request, IncubatorReservation $incubatorReservation)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('manage incubator reservations'))) {
            abort(403, 'غير مصرح لك بنقل الطفل بين الحاضنات');
        }

        $validated = $request->validate([
            'new_incubator_id' => 'required|exists:incubators,id',
            'transfer_reason' => 'nullable|string',
        ]);

        $newIncubator = Incubator::findOrFail($validated['new_incubator_id']);

        if (! $newIncubator->isAvailable()) {
            return redirect()->back()
                ->with('error', 'الحاضنة الجديدة غير متاحة');
        }

        // تحديث الحجز القديم
        $incubatorReservation->update([
            'status' => IncubatorReservation::STATUS_TRANSFERRED,
            'discharge_date' => now()->toDateString(),
            'discharge_time' => now()->format('H:i'),
            'discharge_notes' => 'تم النقل إلى حاضنة رقم '.$newIncubator->incubator_number.
                                ': '.($validated['transfer_reason'] ?? ''),
        ]);

        // إنشاء حجز جديد
        $newReservation = IncubatorReservation::create([
            'patient_id' => $incubatorReservation->patient_id,
            'baby_name' => $incubatorReservation->baby_name,
            'incubator_id' => $validated['new_incubator_id'],
            'doctor_id' => $incubatorReservation->doctor_id,
            'department_id' => $incubatorReservation->department_id,
            'admission_date' => now()->toDateString(),
            'admission_time' => now()->format('H:i'),
            'expected_duration' => $incubatorReservation->expected_duration,
            'birth_weight' => $incubatorReservation->birth_weight,
            'gestational_age' => $incubatorReservation->gestational_age,
            'medical_notes' => $incubatorReservation->medical_notes,
            'admission_notes' => 'منقول من حاضنة رقم '.$incubatorReservation->incubator->incubator_number,
            'status' => IncubatorReservation::STATUS_ADMITTED,
        ]);

        return redirect()->route('incubator-reservations.show', $newReservation)
            ->with('success', 'تم نقل الطفل إلى الحاضنة الجديدة بنجاح');
    }

    /**
     * البحث المباشر السريع عن المرضى (الأم أو المسؤول) لحجز الحاضنة (AJAX)
     */
    public function searchPatients(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || (! $user->can('create incubator reservations') && ! $user->hasAnyRole(['doctor', 'nicu_staff'])))) {
            abort(403, 'غير مصرح لك بالبحث لحجز حاضنات الخدج');
        }

        $query = trim($request->get('query') ?? $request->get('term') ?? '');

        if (empty($query)) {
            return response()->json([]);
        }

        $patients = Patient::with(['user', 'healthInsuranceCategory'])
            ->where(function ($q) use ($query) {
                $q->whereHas('user', function ($uq) use ($query) {
                    $uq->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('phone', 'LIKE', "%{$query}%");
                })
                    ->orWhere('id', $query)
                    ->orWhere('national_id', 'LIKE', "%{$query}%");
            })
            ->limit(15)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => optional($p->user)->name ?? 'غير معروف',
                    'phone' => optional($p->user)->phone ?? ($p->phone ?? 'لا يوجد هاتف'),
                    'age' => $p->age,
                    'gender' => $p->gender,
                    'national_id' => $p->national_id,
                    'insurance_type' => $p->insurance_type ?? 'none',
                    'policy_category' => $p->healthInsuranceCategory?->code ?? null,
                    'insurance_card_no' => $p->insurance_card_no ?? null,
                ];
            });

        return response()->json($patients);
    }

    /**
     * عرض الحاضنات المشغولة حالياً
     */
    public function occupied()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('view incubator reservations'))) {
            abort(403, 'غير مصرح لك بعرض الحاضنات المشغولة');
        }

        $activeReservations = IncubatorReservation::with([
            'patient.user',
            'incubator',
            'doctor.user',
        ])
            ->active()
            ->latest()
            ->get();

        return view('incubator_reservations.occupied', compact('activeReservations'));
    }
}
