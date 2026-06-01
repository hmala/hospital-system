<?php

namespace App\Http\Controllers;

use App\Models\Emergency;
use App\Models\EmergencyService;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\EmergencyTreatment;
use App\Models\User;
use App\Models\Payment;
use App\Models\Appointment;
use App\Models\EmergencyPatient;
use App\Models\ICD10Code;
use App\Notifications\EmergencyPatientMigratedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EmergencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * عرض قائمة حالات الطوارئ
     */
    public function index()
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'receptionist', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى قسم الطوارئ');
        }

        $query = Emergency::with([
            'patient.user',
            'emergencyPatient',
            'doctor.user',
            'nurse',
            'services',
            'labRequests.labTests',
            'radiologyRequests.radiologyTypes',
            'treatments',
            'vitalSignReadings' => function($query) {
                $query->latest()->with('recordedBy')->limit(5);
            }
        ])
            ->where('is_active', true);

        if ($search = request('search')) {
            $query->where(function($q) use ($search) {
                $q->where('id', $search)
                  ->orWhereHas('patient.user', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('emergencyPatient', function($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $emergencies = $query
            ->orderByRaw("
                CASE priority
                    WHEN 'critical' THEN 1
                    WHEN 'urgent' THEN 2
                    WHEN 'semi_urgent' THEN 3
                    WHEN 'non_urgent' THEN 4
                    ELSE 5
                END
            ")
            ->orderBy('admission_time', 'desc')
            ->paginate(15);

        $emergencyServices = EmergencyService::where('is_active', true)
            ->orderBy('name')
            ->get();

        $labTests = \App\Models\LabTest::where('is_active', true)
            ->orderBy('name')
            ->get();

        $radiologyTypes = \App\Models\RadiologyType::where('is_active', true)
            ->orderBy('name')
            ->get();

        $icd10Codes = ICD10Code::orderBy('code')->get();

        // جلب طلبات الخدمات التمريضية من جدول requests بعد الدفع
        $nursingRequests = \App\Models\Request::where('type', 'nursing')
            ->where('payment_status', 'paid')
            ->with(['visit.patient.user', 'visit.doctor.user'])
            ->whereIn('status', ['pending', 'in_progress', 'completed'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('emergency.index', compact('emergencies', 'emergencyServices', 'labTests', 'radiologyTypes', 'nursingRequests', 'icd10Codes'));
    }

    /**
     * عرض نموذج إنشاء حالة طوارئ جديدة
     */
    public function create()
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'receptionist', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بإنشاء حالات طوارئ');
        }

        $patients = Patient::with('user')->get();

        $daysMap = [
            'Saturday' => 'السبت',
            'Sunday' => 'الأحد',
            'Monday' => 'الإثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
        ];
        $todayArabic = $daysMap[date('l')] ?? 'السبت';

        $doctors = Doctor::with('user')
            ->where('is_active', true)
            ->where('is_available_today', true)
            ->whereJsonContains('working_days', [$todayArabic])
            ->where(function($query) {
                $query->where('specialization', 'LIKE', '%طوارئ%')
                      ->orWhere('specialization', 'LIKE', '%emergency%')
                      ->orWhere('type', 'emergency');
            })
            ->get();

        // إذا القائمة فارغة، عرض جميع أطباء الطوارئ (ليتم اختيار الطبيب المسؤول بدون تعطيل الحالة)
        if ($doctors->isEmpty()) {
            $doctors = Doctor::with('user')
                ->where('is_active', true)
                ->where(function($query) {
                    $query->where('specialization', 'LIKE', '%طوارئ%')
                          ->orWhere('specialization', 'LIKE', '%emergency%')
                          ->orWhere('type', 'emergency');
                })
                ->get();
        }

        $nurses = User::role('nurse')->where('is_active', true)->get();

        return view('emergency.create', compact('patients', 'doctors', 'nurses'));
    }

    /**
     * حفظ حالة طوارئ جديدة
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'receptionist', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بإنشاء حالات طوارئ');
        }

        // minimal validation; other details optional or defaulted
        $rules = [
            'priority' => 'required|in:critical,urgent,semi_urgent,non_urgent',
            'doctor_id' => 'nullable|exists:doctors,id',
            'nurse_id' => 'nullable|exists:users,id',
            'room_assigned' => 'nullable|string|max:50',
            'initial_assessment' => 'nullable|string|max:1000',
            'requires_surgery' => 'boolean',
            'doctor_follow_up' => 'boolean',
            'vital_signs.blood_pressure' => 'nullable|string|max:20',
            'vital_signs.heart_rate' => 'nullable|integer|min:1|max:300',
            'vital_signs.temperature' => 'nullable|numeric|min:30|max:45',
            'vital_signs.respiratory_rate' => 'nullable|integer|min:1|max:100',
            'vital_signs.oxygen_saturation' => 'nullable|integer|min:1|max:100',
        ];
        if ($request->filled('new_patient_name')) {
            $rules['new_patient_name'] = 'required|string|max:255';
            $rules['new_patient_phone'] = 'nullable|string|max:15';
            $rules['new_patient_gender'] = 'nullable|in:male,female';
            $rules['new_patient_dob'] = 'nullable|date';
            $rules['new_patient_ref'] = 'nullable|string|max:191';
            $rules['new_patient_notes'] = 'nullable|string|max:1000';
        } else {
            $rules['patient_id'] = 'required|exists:patients,id';
        }
        $request->validate($rules);

        if ($request->boolean('doctor_follow_up') && !$request->filled('doctor_id')) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['doctor_follow_up' => 'يرجى اختيار الطبيب قبل تفعيل متابعة الطبيب.']);
        }

        // determine how to link patient
        $patientId = $request->patient_id;
        $emergencyPatientId = null;

        if ($request->filled('new_patient_name')) {
            // create a temporary emergency patient record instead of a full patient
            $emergencyPatient = EmergencyPatient::create([
                'name' => $request->new_patient_name,
                'phone' => $request->new_patient_phone,
                'gender' => $request->new_patient_gender,
                'date_of_birth' => $request->new_patient_dob,
                'reference_number' => $request->new_patient_ref,
                'notes' => $request->new_patient_notes,
                'is_active' => true,
                'migrated' => false,
            ]);
            $emergencyPatientId = $emergencyPatient->id;
            // no main patient yet, leave patientId null
            $patientId = null;
        }

        $emergency = Emergency::create([
            'patient_id' => $patientId,
            'emergency_patient_id' => $emergencyPatientId,
            'doctor_id' => $request->doctor_id,
            'nurse_id' => $request->nurse_id,
            'priority' => $request->priority,
            // fill unspecified fields with defaults so table constraints are satisfied
            'emergency_type' => $request->input('emergency_type', 'general'),
            'symptoms' => $request->input('symptoms', ''),
            'vital_signs' => $request->vital_signs, // will be null unless provided elsewhere
            'room_assigned' => $request->room_assigned,
            'initial_assessment' => $request->initial_assessment,
            'requires_surgery' => $request->boolean('requires_surgery'),
            'doctor_follow_up_fee' => $request->boolean('doctor_follow_up') ? 30000 : 0,
            'admission_time' => now(),
            'status' => 'waiting',
        ]);

        if ($emergency->doctor_follow_up_fee > 0) {
            $this->upsertUnifiedEmergencyPayment($emergency);
        }

        // return to list instead of details so user sees the emergency table
        return redirect()->route('emergency.index')
            ->with('success', 'تم إنشاء حالة الطوارئ بنجاح');
    }

    /**
     * عرض تفاصيل حالة طوارئ
     */
    public function show(Emergency $emergency)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'receptionist', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بعرض حالات الطوارئ');
        }

        $emergency->load([
            'patient.user',
            'emergencyPatient',
            'doctor.user',
            'nurse',
            'services',
            'payment',
            'labRequests.labTests',
            'radiologyRequests.radiologyTypes',
            'vitalSignReadings' => function($query) {
                $query->latest()->with('recordedBy');
            }
        ]);

        return view('emergency.show', compact('emergency'));
    }

    /**
     * عرض نموذج تعديل حالة طوارئ
     */
    public function edit(Emergency $emergency)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بتعديل حالات الطوارئ');
        }

        $patients = Patient::with('user')->get();
        $doctors = Doctor::with('user')
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('specialization', 'LIKE', '%طوارئ%')
                      ->orWhere('specialization', 'LIKE', '%emergency%')
                      ->orWhere('type', 'emergency');
            })
            ->get();

        $nurses = User::role('nurse')->where('is_active', true)->get();

        return view('emergency.edit', compact('emergency', 'patients', 'doctors', 'nurses'));
    }

    /**
     * تحديث حالة طوارئ
     */
    public function update(Request $request, Emergency $emergency)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بتعديل حالات الطوارئ');
        }

        $request->validate([
            'priority' => 'required|in:critical,urgent,semi_urgent,non_urgent',
            'status' => 'required|in:waiting,in_progress,completed,transferred,discharged',
            // other fields optional when editing
            'vital_signs.blood_pressure' => 'nullable|string|max:20',
            'vital_signs.heart_rate' => 'nullable|integer|min:1|max:300',
            'vital_signs.temperature' => 'nullable|numeric|min:30|max:45',
            'vital_signs.respiratory_rate' => 'nullable|integer|min:1|max:100',
            'vital_signs.oxygen_saturation' => 'nullable|integer|min:1|max:100',
            'doctor_id' => 'nullable|exists:doctors,id',
            'nurse_id' => 'nullable|exists:users,id',
            'room_assigned' => 'nullable|string|max:50',
            'initial_assessment' => 'nullable|string|max:1000',
            'treatment_given' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'requires_surgery' => 'boolean',
        ]);

        $emergency->update($request->only([
            'priority', 'status', 'vital_signs',
            'doctor_id', 'nurse_id', 'room_assigned', 'initial_assessment',
            'treatment_given', 'notes', 'requires_surgery'
        ]));

        // إذا تم تغيير الحالة إلى مغادرة، أضف وقت المغادرة
        if ($request->status === 'discharged' && !$emergency->discharge_time) {
            $emergency->update(['discharge_time' => now()]);
        }

        return redirect()->route('emergency.show', $emergency)
            ->with('success', 'تم تحديث حالة الطوارئ بنجاح');
    }

    /**
     * حذف حالة طوارئ
     */
    public function destroy(Emergency $emergency)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin'])) {
            abort(403, 'غير مصرح لك بحذف حالات الطوارئ');
        }

        $emergency->delete();

        return redirect()->route('emergency.index')
            ->with('success', 'تم حذف حالة الطوارئ بنجاح');
    }

    /**
     * تحديث علامات الحياة
     */
    public function updateVitals(Request $request, Emergency $emergency)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بتحديث علامات الحياة');
        }

        $request->validate([
            'blood_pressure' => 'nullable|string|max:20',
            'heart_rate' => 'nullable|integer|min:1|max:300',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'respiratory_rate' => 'nullable|integer|min:1|max:100',
            'oxygen_saturation' => 'nullable|integer|min:1|max:100',
            'blood_glucose' => 'nullable|numeric|min:1|max:1000',
        ]);

        // Create a new vital signs record in the dedicated table
        $emergency->vitalSignReadings()->create([
            'recorded_by' => $user->id,
            'patient_id' => $emergency->patient_id,
            'blood_pressure' => $request->blood_pressure,
            'heart_rate' => $request->heart_rate,
            'temperature' => $request->temperature,
            'respiratory_rate' => $request->respiratory_rate,
            'oxygen_saturation' => $request->oxygen_saturation,
            'blood_glucose' => $request->blood_glucose,
        ]);

        return redirect()->back()->with('success', 'تم حفظ القراءة بنجاح');
    }

    /**
     * تحديث معلومات العلامات الحيوية والتشخيص والخدمة المقدمة
     */
    public function updateMedical(Request $request, Emergency $emergency)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بتحديث معلومات الطوارئ');
        }
        $request->validate([
            'blood_pressure' => 'nullable|string|max:20',
            'heart_rate' => 'nullable|integer|min:1|max:300',
            'temperature' => 'nullable|numeric|min:30|max:45',
            'respiratory_rate' => 'nullable|integer|min:1|max:100',
            'oxygen_saturation' => 'nullable|integer|min:1|max:100',
            'diagnosis' => 'nullable|string|max:1000',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:emergency_services,id',
        ]);
        $vitals = $emergency->vital_signs ?? [];
        $vitals['blood_pressure'] = $request->blood_pressure;
        $vitals['heart_rate'] = $request->heart_rate;
        $vitals['temperature'] = $request->temperature;
        $vitals['respiratory_rate'] = $request->respiratory_rate;
        $vitals['oxygen_saturation'] = $request->oxygen_saturation;
        $vitals['updated_at'] = now()->toISOString();
        $emergency->update([
            'vital_signs' => $vitals,
            'diagnosis' => $request->diagnosis,
        ]);
        $emergency->services()->sync($request->input('service_ids', []));

        // تحديث/إنشاء فاتورة طوارئ موحدة (خدمات + تحاليل + أشعة)
        $this->upsertUnifiedEmergencyPayment($emergency);

        return redirect()->route('emergency.index')->with('success', 'تم تحديث المعلومات الطبية بنجاح');
    }

    /**
     * حفظ علاج جديد لحالة الطوارئ
     */
    public function storeTreatment(Request $request, Emergency $emergency)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بتسجيل علاج الطوارئ');
        }

        $validated = $request->validate([
            'treatments' => 'required|array|min:1',
            'treatments.*.treatment_type' => 'required|in:medication,injection,drip,oxygen,other',
            'treatments.*.description' => 'required|string|max:1000',
            'treatments.*.notes' => 'nullable|string|max:1000',
            'treatments.*.frequency_per_day' => 'nullable|integer|min:1|max:24',
            'treatments.*.status' => 'required|in:planned,in_progress,completed,cancelled',
            'treatments.*.started_at' => 'nullable|date',
            'treatments.*.completed_at' => 'nullable|date|after_or_equal:started_at',
        ]);

        foreach ($validated['treatments'] as $treatmentData) {
            EmergencyTreatment::create([
                'emergency_id' => $emergency->id,
                'created_by' => $user->id,
                'treatment_type' => $treatmentData['treatment_type'],
                'description' => $treatmentData['description'],
                'notes' => $treatmentData['notes'] ?? null,
                'frequency_per_day' => $treatmentData['frequency_per_day'] ?? null,
                'status' => $treatmentData['status'],
                'started_at' => $treatmentData['started_at'] ?? null,
                'completed_at' => $treatmentData['completed_at'] ?? null,
            ]);
        }

        if (!$emergency->treatment_given && !empty($validated['treatments'][0]['description'])) {
            $emergency->update(['treatment_given' => $validated['treatments'][0]['description']]);
        }

        return redirect()->route('emergency.index')->with('success', 'تم إضافة علاج الطوارئ بنجاح');
    }

    /**
     * عرض لوحة تحكم الطوارئ
     */
    public function dashboard()
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى لوحة تحكم الطوارئ');
        }

        $stats = [
            'total_today' => Emergency::whereDate('admission_time', today())->count(),
            'waiting' => Emergency::where('status', 'waiting')->where('is_active', true)->count(),
            'in_treatment' => Emergency::where('status', 'in_progress')->where('is_active', true)->count(),
            'critical' => Emergency::where('priority', 'critical')->where('status', '!=', 'discharged')->where('is_active', true)->count(),
            'urgent' => Emergency::where('priority', 'urgent')->where('status', '!=', 'discharged')->where('is_active', true)->count(),
            'priority_critical' => Emergency::where('priority', 'critical')->where('is_active', true)->count(),
            'priority_high' => Emergency::where('priority', 'urgent')->where('is_active', true)->count(),
            'priority_medium' => Emergency::where('priority', 'semi_urgent')->where('is_active', true)->count(),
            'priority_low' => Emergency::where('priority', 'non_urgent')->where('is_active', true)->count(),
            'types' => [
                'trauma' => Emergency::where('emergency_type', 'trauma')->where('is_active', true)->count(),
                'cardiac' => Emergency::where('emergency_type', 'cardiac')->where('is_active', true)->count(),
                'respiratory' => Emergency::where('emergency_type', 'respiratory')->where('is_active', true)->count(),
                'neurological' => Emergency::where('emergency_type', 'neurological')->where('is_active', true)->count(),
                'poisoning' => Emergency::where('emergency_type', 'poisoning')->where('is_active', true)->count(),
                'burns' => Emergency::where('emergency_type', 'burns')->where('is_active', true)->count(),
                'allergic' => Emergency::where('emergency_type', 'allergic')->where('is_active', true)->count(),
                'pediatric' => Emergency::where('emergency_type', 'pediatric')->where('is_active', true)->count(),
                'obstetric' => Emergency::where('emergency_type', 'obstetric')->where('is_active', true)->count(),
                'general' => Emergency::where('emergency_type', 'general')->where('is_active', true)->count(),
            ],
        ];

        $recentEmergencies = Emergency::with(['patient.user', 'doctor.user'])
            ->where('is_active', true)
            ->orderBy('admission_time', 'desc')
            ->limit(10)
            ->get();

        $criticalEmergencies = Emergency::with(['patient.user', 'doctor.user'])
            ->where('priority', 'critical')
            ->where('status', '!=', 'discharged')
            ->where('is_active', true)
            ->orderBy('admission_time', 'desc')
            ->get();

        $waitingEmergencies = Emergency::with(['patient.user', 'doctor.user'])
            ->where('status', 'waiting')
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('admission_time', 'asc')
            ->get();

        $inTreatmentEmergencies = Emergency::with(['patient.user', 'doctor.user'])
            ->where('status', 'in_progress')
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('admission_time', 'asc')
            ->get();

        return view('emergency.dashboard', compact('stats', 'recentEmergencies', 'criticalEmergencies', 'waitingEmergencies', 'inTreatmentEmergencies'));
    }

    /**
     * بدء علاج حالة طوارئ
     */
    public function startTreatment(Emergency $emergency)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بتعديل حالات الطوارئ');
        }

        if ($emergency->status !== 'waiting') {
            return redirect()->back()->with('error', 'يمكن بدء العلاج لحالات الانتظار فقط');
        }

        $emergency->update([
            'status' => 'in_progress',
            'doctor_id' => $user->hasRole('doctor') ? $user->doctor->id : $emergency->doctor_id,
        ]);

        return redirect()->back()->with('success', 'تم بدء علاج الحالة بنجاح');
    }

    /**
     * إنهاء حالة طوارئ
     */
    public function complete(Emergency $emergency)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بتعديل حالات الطوارئ');
        }

        if ($emergency->status !== 'in_progress') {
            return redirect()->back()->with('error', 'يمكن إنهاء العلاج لحالات قيد المعالجة فقط');
        }

        $emergency->update([
            'status' => 'discharged',
            'discharge_time' => now(),
            'is_active' => false,
        ]);

        return redirect()->back()->with('success', 'تم إنهاء علاج الحالة بنجاح');
    }

    /**
     * إنشاء موعد استشاري لحالة طوارئ
     */
    public function createConsultation(Request $request, Emergency $emergency)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'receptionist', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بإنشاء مواعيد استشارية');
        }

        // لا نُجبر على الدفع لإنشاء استشارة الآن، يمكن لاحقًا إلغاء السطر أدناه إذا أردنا فقط التنبيه
        // if ($emergency->payment_status !== 'paid') {
        //     return redirect()->back()->with('error', 'لا يمكن إنشاء موعد استشاري إلا لحالات مدفوعة');
        // }

        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'doctor_ids' => 'required|array|min:1',
            'doctor_ids.*' => 'required|exists:doctors,id',
            'reason' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        $appointmentDateTime = $request->appointment_date . ' ' . $request->appointment_time . ':00';
        $selectedDoctorIds = collect($request->doctor_ids)->unique();
        $appointmentsCreated = 0;

        foreach ($selectedDoctorIds as $doctorId) {
            $doctor = Doctor::find($doctorId);
            if (!$doctor) {
                return redirect()->back()->with('error', 'تم اختيار طبيب غير موجود');
            }

            if ($doctor->type !== 'consultant') {
                return redirect()->back()->with('error', 'يجب اختيار أطباء استشاريين فقط');
            }

            if (!$doctor->is_available_today) {
                return redirect()->back()->with('error', "الطبيب {$doctor->user?->name} غير متاح اليوم");
            }

            $existingAppointment = Appointment::where('doctor_id', $doctorId)
                ->where('appointment_date', $appointmentDateTime)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->exists();

            if ($existingAppointment) {
                return redirect()->back()->with('error', "يوجد موعد محجوز مسبقاً للطبيب {$doctor->user?->name} في هذا التاريخ والوقت");
            }

            $appointmentsCreated++;
        }

        if ($appointmentsCreated === 0) {
            return redirect()->back()->with('error', 'لم يتم تحديد أي طبيب صالح لإنشاء موعد استشاري');
        }

        // migrate emergency patient to main table if necessary
        if ($emergency->emergency_patient_id && !$emergency->patient_migrated) {
            $emergencyPatient = $emergency->emergencyPatient;
            // create user and main patient
            $userAccount = User::create([
                'name' => $emergencyPatient->name,
                'email' => 'emergency_' . time() . '_' . rand(1000,9999) . '@example.com',
                'role' => 'patient',
                'password' => bcrypt(Str::random(12)),
            ]);
            $mainPatient = Patient::create([
                'user_id' => $userAccount->id,
                'date_of_birth' => $emergencyPatient->date_of_birth,
                'gender' => $emergencyPatient->gender,
                'phone' => $emergencyPatient->phone,
            ]);

            $emergency->update([
                'patient_id' => $mainPatient->id,
                'patient_migrated' => true,
            ]);

            // mark emergency patient inactive
            $emergencyPatient->update(['is_active' => false, 'migrated' => true]);

            // notify receptionists / consultation staff to complete profile
            $receptionists = User::role('receptionist')->where('is_active', true)->get();
            foreach ($receptionists as $rec) {
                $rec->notify(new \App\Notifications\EmergencyPatientMigratedNotification($emergency));
            }
        }

        // أجرة استشارة الطوارئ الثابتة (مقطوعة)
        $emergencyConsultationFee = 50000;
        $createdAppointmentIds = [];

        foreach ($selectedDoctorIds as $doctorId) {
            $doctor = Doctor::find($doctorId);
            if (!$doctor) {
                continue;
            }

            $appointment = Appointment::create([
                'patient_id' => $emergency->patient_id,
                'doctor_id' => $doctorId,
                'department_id' => $doctor->department_id,
                'emergency_id' => $emergency->id,
                'appointment_date' => $appointmentDateTime,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'consultation_fee' => $emergencyConsultationFee,
                'duration' => 30,
                'status' => 'scheduled',
                'payment_status' => 'pending'
            ]);

            $createdAppointmentIds[] = $appointment->id;
        }

        if (count($createdAppointmentIds) === 0) {
            return redirect()->back()->with('error', 'لم يتم إنشاء أي موعد استشاري. تحقق من الأطباء المحددين وحاول مرة أخرى.');
        }

        // دمج الاستشارة في فاتورة طوارئ موحدة
        $unifiedPayment = $this->upsertUnifiedEmergencyPayment($emergency);

        // ربط المواعيد بفاتورة الطوارئ الموحدة (لمساعدتنا لاحقاً في التقارير)
        Appointment::whereIn('id', $createdAppointmentIds)->update([
            'payment_id' => $unifiedPayment->id,
            'payment_status' => 'pending',
        ]);

        // تحديث حالة الطوارئ إلى محولة
        $emergency->update(['status' => 'transferred']);

        return redirect()->back()->with('success', 'تم إنشاء المواعيد الاستشارية بنجاح وإضافتها إلى فاتورة الطوارئ الموحدة');
    }

    /**
     * طلب تحاليل طبية لحالة طوارئ
     */
    public function requestLab(Request $request, Emergency $emergency)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'receptionist', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بطلب تحاليل');
        }

        $request->validate([
            'lab_test_ids' => 'required|array|min:1',
            'lab_test_ids.*' => 'exists:lab_tests,id',
            'priority' => 'required|in:urgent,critical',
            'notes' => 'nullable|string|max:1000',
        ]);

        // ترحيل المريض من emergencyPatient إلى patient الرئيسي إذا لم يتم بعد
        if (!$emergency->patient_id && $emergency->emergency_patient_id && !$emergency->patient_migrated) {
            $emergencyPatient = $emergency->emergencyPatient;
            // إنشاء حساب مستخدم ومريض رئيسي
            $userAccount = \App\Models\User::create([
                'name' => $emergencyPatient->name,
                'email' => 'emergency_' . time() . '_' . rand(1000,9999) . '@example.com',
                'role' => 'patient',
                'password' => bcrypt(\Illuminate\Support\Str::random(12)),
            ]);
            $mainPatient = \App\Models\Patient::create([
                'user_id' => $userAccount->id,
                'date_of_birth' => $emergencyPatient->date_of_birth,
                'gender' => $emergencyPatient->gender,
                'phone' => $emergencyPatient->phone,
            ]);

            $emergency->update([
                'patient_id' => $mainPatient->id,
                'patient_migrated' => true,
            ]);

            // تحديث حالة emergencyPatient
            $emergencyPatient->update(['is_active' => false, 'migrated' => true]);
        }

        // التحقق من وجود patient_id بعد المحاولة
        if (!$emergency->patient_id) {
            return redirect()->back()->with('error', 'لا يمكن إنشاء طلب تحاليل لحالة بدون مريض مسجل');
        }

        // إنشاء طلب تحاليل طوارئ
        $labRequest = \App\Models\EmergencyLabRequest::create([
            'emergency_id' => $emergency->id,
            'patient_id' => $emergency->patient_id,
            'status' => 'pending',
            'priority' => $request->priority,
            'notes' => $request->notes,
            'requested_at' => now()
        ]);

        // ربط التحاليل بالطلب
        $labRequest->labTests()->attach($request->lab_test_ids);

        // دمج الرسوم في فاتورة طوارئ واحدة
        $this->upsertUnifiedEmergencyPayment($emergency);

        return redirect()->back()->with('success', 'تم إنشاء طلب التحاليل بنجاح وإضافة الرسوم للكاشير');
    }

    /**
     * طلب أشعة لحالة طوارئ
     */
    public function requestRadiology(Request $request, Emergency $emergency)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'receptionist', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بطلب أشعة');
        }

        $request->validate([
            'radiology_type_ids' => 'required|array|min:1',
            'radiology_type_ids.*' => 'exists:radiology_types,id',
            'priority' => 'required|in:urgent,critical',
            'notes' => 'nullable|string|max:1000',
        ]);

        // ترحيل المريض من emergencyPatient إلى patient الرئيسي إذا لم يتم بعد
        if (!$emergency->patient_id && $emergency->emergency_patient_id && !$emergency->patient_migrated) {
            $emergencyPatient = $emergency->emergencyPatient;
            // إنشاء حساب مستخدم ومريض رئيسي
            $userAccount = \App\Models\User::create([
                'name' => $emergencyPatient->name,
                'email' => 'emergency_' . time() . '_' . rand(1000,9999) . '@example.com',
                'role' => 'patient',
                'password' => bcrypt(\Illuminate\Support\Str::random(12)),
            ]);
            $mainPatient = \App\Models\Patient::create([
                'user_id' => $userAccount->id,
                'date_of_birth' => $emergencyPatient->date_of_birth,
                'gender' => $emergencyPatient->gender,
                'phone' => $emergencyPatient->phone,
            ]);

            $emergency->update([
                'patient_id' => $mainPatient->id,
                'patient_migrated' => true,
            ]);

            // تحديث حالة emergencyPatient
            $emergencyPatient->update(['is_active' => false, 'migrated' => true]);
        }

        // التحقق من وجود patient_id بعد المحاولة
        if (!$emergency->patient_id) {
            return redirect()->back()->with('error', 'لا يمكن إنشاء طلب أشعة لحالة بدون مريض مسجل');
        }

        // إنشاء طلب أشعة طوارئ
        $radiologyRequest = \App\Models\EmergencyRadiologyRequest::create([
            'emergency_id' => $emergency->id,
            'patient_id' => $emergency->patient_id,
            'status' => 'pending',
            'priority' => $request->priority,
            'notes' => $request->notes,
            'requested_at' => now()
        ]);

        // ربط أنواع الأشعة بالطلب
        $radiologyRequest->radiologyTypes()->attach($request->radiology_type_ids);

        // دمج الرسوم في فاتورة طوارئ واحدة
        $this->upsertUnifiedEmergencyPayment($emergency);

        return redirect()->back()->with('success', 'تم إنشاء طلب الأشعة بنجاح وإضافة الرسوم للكاشير');
    }

    /**
     * إنشاء/تحديث فاتورة موحدة لحالة الطوارئ (خدمات + تحاليل + أشعة)
     * ملاحظة: رسوم الاستشاري تبقى منفصلة لأنها مرتبطة بموعد (appointment_id)
     * تحسب فقط الخدمات غير المدفوعة (payment_id = null)
     */
    private function upsertUnifiedEmergencyPayment(Emergency $emergency): ?\App\Models\Payment
    {
        $emergency->loadMissing([
            'services',
            'labRequests.labTests',
            'radiologyRequests.radiologyTypes',
            'appointments',
        ]);

        // حساب الخدمات غير المدفوعة فقط
        // نحتاج للتحقق من جدول emergency_emergency_service للخدمات
        $unpaidServiceIds = \DB::table('emergency_emergency_service')
            ->where('emergency_id', $emergency->id)
            ->whereNull('payment_id')
            ->pluck('emergency_service_id');
        
        $servicesAmount = $emergency->services
            ->whereIn('id', $unpaidServiceIds)
            ->sum('price');

        // حساب التحاليل غير المدفوعة فقط
        $labAmount = $emergency->labRequests
            ->whereNull('payment_id')
            ->sum(function ($request) {
                return $request->labTests->sum('price');
            });

        // حساب الأشعة غير المدفوعة فقط
        $radiologyAmount = $emergency->radiologyRequests
            ->whereNull('payment_id')
            ->sum(function ($request) {
                return $request->radiologyTypes->sum('price');
            });

        $consultationAmount = $emergency->appointments
            ->where('payment_status', 'pending')
            ->where('status', '<>', 'cancelled')
            ->sum('consultation_fee');

        // رسوم المتابعة تُحسب فقط إذا لم تُدفع بعد
        $followUpFee = ($emergency->doctor_follow_up_fee > 0 && !$emergency->follow_up_payment_id) 
            ? $emergency->doctor_follow_up_fee 
            : 0;
            
        $totalAmount = $servicesAmount + $labAmount + $radiologyAmount + $consultationAmount + $followUpFee;

        $hasPendingAmount = $totalAmount > 0;
        $existingPayment = Payment::where('emergency_id', $emergency->id)
            ->where('payment_type', 'emergency')
            ->whereNull('appointment_id')
            ->whereNull('paid_at')
            ->latest('id')
            ->first();

        if (!$hasPendingAmount) {
            if ($existingPayment) {
                $existingPayment->delete();
                $emergency->update([
                    'payment_status' => 'paid',
                    'payment_id' => null,
                ]);
            }
            return $existingPayment;
        }

        // بناء الوصف من العناصر غير المدفوعة فقط
        $serviceNames = $emergency->services
            ->whereIn('id', $unpaidServiceIds)
            ->pluck('name')
            ->filter()
            ->values();
            
        $labNames = $emergency->labRequests
            ->whereNull('payment_id')
            ->flatMap(function ($request) {
                return $request->labTests->pluck('name');
            })
            ->filter()
            ->unique()
            ->values();
            
        $radiologyNames = $emergency->radiologyRequests
            ->whereNull('payment_id')
            ->flatMap(function ($request) {
                return $request->radiologyTypes->pluck('name');
            })
            ->filter()
            ->unique()
            ->values();

        $descriptionParts = [];
        if ($serviceNames->isNotEmpty()) {
            $descriptionParts[] = 'خدمات طوارئ: ' . $serviceNames->implode('، ');
        }
        if ($labNames->isNotEmpty()) {
            $descriptionParts[] = 'تحاليل: ' . $labNames->implode('، ');
        }
        if ($radiologyNames->isNotEmpty()) {
            $descriptionParts[] = 'أشعة: ' . $radiologyNames->implode('، ');
        }

        if ($consultationAmount > 0) {
            $consultationNames = $emergency->appointments
                ->where('payment_status', 'pending')
                ->where('status', '<>', 'cancelled')
                ->pluck('reason')
                ->filter()
                ->unique()
                ->values();

            $descriptionParts[] = 'استشارة: ' . ($consultationNames->isNotEmpty() ? $consultationNames->implode('، ') : 'استشارة طوارئ');
        }

        if ($followUpFee > 0) {
            $descriptionParts[] = 'رسوم متابعة الطبيب';
        }

        $description = $descriptionParts
            ? implode(' | ', $descriptionParts)
            : 'رسوم طوارئ';

        if ($existingPayment) {
            $existingPayment->update([
                'patient_id' => $emergency->patient_id,
                'amount' => $totalAmount,
                'description' => $description,
            ]);

            $emergency->update([
                'payment_status' => 'pending',
                'payment_id' => $existingPayment->id,
            ]);

            return $existingPayment;
        }

        $payment = Payment::create([
            'emergency_id' => $emergency->id,
            'patient_id' => $emergency->patient_id,
            'amount' => $totalAmount,
            'payment_type' => 'emergency',
            'payment_method' => 'pending',
            'description' => $description,
            'receipt_number' => Payment::generateReceiptNumber(),
            'paid_at' => null,
        ]);

        $emergency->update([
            'payment_status' => 'pending',
            'payment_id' => $payment->id,
        ]);

        return $payment;
    }

    /**
     * حالة الدفع النهائية لحالات الطوارئ (for cashier live updates polling)
     */
    public function paymentStatus()
    {
        $pendingCount = Emergency::where('payment_status', 'pending')
            ->where('is_active', true)
            ->count();

        $paidCount = Emergency::where('payment_status', 'paid')
            ->where('is_active', true)
            ->count();

        $pendingAmount = Payment::where('payment_type', 'emergency')
            ->whereNull('paid_at')
            ->sum('amount');

        $paidAmount = Payment::where('payment_type', 'emergency')
            ->whereNotNull('paid_at')
            ->sum('amount');

        return response()->json([
            'pending' => $pendingCount,
            'paid' => $paidCount,
            'pending_amount' => $pendingAmount,
            'paid_amount' => $paidAmount,
        ]);
    }

    /**
     * تحديث حالة طلب الخدمة التمريضية
     */
    public function updateNursingRequest(\App\Models\Request $request)
    {
        // التحقق من الصلاحيات
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'nurse', 'emergency_staff'])) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        // التحقق من أن الطلب من نوع التمريض
        if ($request->type !== 'nursing') {
            abort(404, 'طلب غير صحيح');
        }

        // تحديث الحالة
        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled'
        ]);

        $request->update([
            'status' => request('status')
        ]);

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }
}
