<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\Incubator;
use App\Models\IncubatorReservation;
use App\Models\NicuAttachment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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

        // الحصول على المريض إذا تم تمريره مسبقاً في الرابط
        $patient = null;
        $existingReservation = null;

        if ($request->filled('patient_id')) {
            $patient = Patient::with(['user', 'healthInsuranceCategory'])->find($request->patient_id);

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

        $incubators = $incubatorsQuery->orderBy('incubator_number')->get();
        $doctors = Doctor::with('user')->get();
        $departments = Department::where('is_active', true)->get();
        $nicuServices = \App\Models\NicuService::active()->get();

        return view('incubator_reservations.create', compact(
            'patient',
            'patients',
            'incubators',
            'doctors',
            'departments',
            'selectedType',
            'incubatorTypes',
            'existingReservation',
            'nicuServices'
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
        ]);

        // سحب وتوليد اسم الطفل وتحديد صفة المريض (طفل مستقل أو تابع للأم/الأب)
        $patientUser = Patient::with('user')->findOrFail($validated['patient_id']);
        $isIndependent = $request->boolean('is_independent_patient');
        if (! $isIndependent && $patientUser->age !== null && $patientUser->age < 1) {
            $isIndependent = true;
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

        $validated['current_weight'] = $validated['birth_weight'] ?? null;
        $validated['insurance_type'] = $validated['insurance_type'] ?? 'none';

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

        // حفظ الوثائق والمرفقات الرسمية المرفوعة أو الممسوحة بالسكانر
        if ($request->hasFile('birth_certificate_file') || $request->filled('scanned_birth_certificate')) {
            $certTitle = 'بيان الولادة الرسمي'.(! empty($reservation->birth_certificate_no) ? ' (رقم '.$reservation->birth_certificate_no.')' : '');
            $this->saveReservationAttachment(
                $reservation,
                $user,
                $certTitle,
                $request->file('birth_certificate_file'),
                $request->input('scanned_birth_certificate'),
                'مستند بيان الولادة المرفق أثناء تسجيل واستقبال الخديج'
            );
        }

        if ($request->hasFile('parent_id_file') || $request->filled('scanned_parent_id')) {
            $this->saveReservationAttachment(
                $reservation,
                $user,
                'البطاقة الموحدة / هوية الأحوال للأم أو الأب',
                $request->file('parent_id_file'),
                $request->input('scanned_parent_id'),
                'مستند الهوية المرفق أثناء تسجيل واستقبال الخديج'
            );
        }

        if ($request->hasFile('referral_doc_file') || $request->filled('scanned_referral_doc')) {
            $this->saveReservationAttachment(
                $reservation,
                $user,
                'تقرير الإحالة / وثيقة التحويل الطبي',
                $request->file('referral_doc_file'),
                $request->input('scanned_referral_doc'),
                'وثيقة التحويل الطبي أو الإحالة من خارج المستشفى'
            );
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

        return redirect()->route('nicu.show', $reservation)
            ->with('success', 'تم حجز واستقبال الخديج بنجاح في قسم الخدج والحاضنات.');
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

        if (! $isAdmin && (! $user || (! $user->can('create incubator reservations') && ! $user->hasAnyRole(['doctor', 'receptionist', 'nicu_staff'])))) {
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

    /**
     * حفظ مرفق بالحاضنة سواء كان ملفاً مرفوعاً أو صورة مسحوبة من السكانر
     */
    private function saveReservationAttachment(
        IncubatorReservation $reservation,
        ?User $user,
        string $title,
        $uploadedFile,
        ?string $scannedBase64,
        ?string $notes = null
    ): void {
        if ($uploadedFile && $uploadedFile->isValid()) {
            $fileName = $uploadedFile->getClientOriginalName();
            $mimeType = $uploadedFile->getClientMimeType();
            $fileSize = $uploadedFile->getSize();
            $storageName = time().'_'.Str::random(8).'.'.$uploadedFile->getClientOriginalExtension();
            $path = $uploadedFile->storeAs('nicu_attachments/'.$reservation->id, $storageName, 'public');

            NicuAttachment::create([
                'incubator_reservation_id' => $reservation->id,
                'patient_id' => $reservation->patient_id,
                'user_id' => $user?->id,
                'title' => $title,
                'file_path' => $path,
                'file_name' => $fileName,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'notes' => $notes,
            ]);
        } elseif (! empty($scannedBase64)) {
            $base64Data = $scannedBase64;
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                $ext = strtolower($type[1]);
            } else {
                $ext = 'jpg';
            }
            $decoded = base64_decode($base64Data);
            if ($decoded !== false) {
                $storageName = 'scan_'.time().'_'.Str::random(8).'.'.$ext;
                $relativePath = 'nicu_attachments/'.$reservation->id.'/'.$storageName;
                Storage::disk('public')->put($relativePath, $decoded);

                NicuAttachment::create([
                    'incubator_reservation_id' => $reservation->id,
                    'patient_id' => $reservation->patient_id,
                    'user_id' => $user?->id,
                    'title' => $title,
                    'file_path' => $relativePath,
                    'file_name' => $storageName,
                    'mime_type' => 'image/'.($ext === 'jpg' ? 'jpeg' : $ext),
                    'file_size' => strlen($decoded),
                    'notes' => $notes ?? 'مسح ضوئي مباشر عبر السكانر',
                ]);
            }
        }
    }
}
