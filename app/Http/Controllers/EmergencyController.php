<?php

namespace App\Http\Controllers;

use App\Models\Emergency;
use App\Models\EmergencyService;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Payment;
use App\Models\Appointment;
use App\Models\EmergencyPatient;
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

        return view('emergency.index', compact('emergencies', 'emergencyServices', 'labTests', 'radiologyTypes'));
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
        $doctors = Doctor::with('user')
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('specialization', 'LIKE', '%طوارئ%')
                      ->orWhere('specialization', 'LIKE', '%emergency%')
                      ->orWhere('type', 'emergency');
            })
            ->get();

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
            'admission_time' => now(),
            'status' => 'waiting',
        ]);

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
        ]);

        $currentVitals = $emergency->vital_signs ?? [];
        $currentVitals['updated_at'] = now()->toISOString();
        $currentVitals = array_merge($currentVitals, $request->only([
            'blood_pressure', 'heart_rate', 'temperature', 'respiratory_rate', 'oxygen_saturation'
        ]));

        $emergency->update(['vital_signs' => $currentVitals]);

        return response()->json(['success' => true, 'message' => 'تم تحديث علامات الحياة']);
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

        // التحقق من عدم وجود موعد استشاري سابق
        $existingConsultation = Appointment::where('emergency_id', $emergency->id)->first();
        if ($existingConsultation) {
            return redirect()->back()->with('error', 'يوجد موعد استشاري سابق لهذه الحالة');
        }

        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'doctor_id' => 'required|exists:doctors,id',
            'reason' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        // التحقق من أن الطبيب استشاري
        $doctor = Doctor::find($request->doctor_id);
        if ($doctor->type !== 'consultant') {
            return redirect()->back()->with('error', 'يجب اختيار طبيب استشاري');
        }

        // التحقق من أن الطبيب متاح اليوم
        if (!$doctor->is_available_today) {
            return redirect()->back()->with('error', 'الطبيب المختار غير متاح اليوم');
        }

        // التحقق من توفر الموعد
        $appointmentDateTime = $request->appointment_date . ' ' . $request->appointment_time . ':00';
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->where('appointment_date', $appointmentDateTime)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->exists();

        if ($existingAppointment) {
            return redirect()->back()->with('error', 'يوجد موعد محجوز مسبقاً لهذا الطبيب في هذا التاريخ والوقت');
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

        // إنشاء الموعد الاستشاري
        $appointment = Appointment::create([
            'patient_id' => $emergency->patient_id,
            'doctor_id' => $request->doctor_id,
            'department_id' => $doctor->department_id,
            'emergency_id' => $emergency->id,
            'appointment_date' => $appointmentDateTime,
            'reason' => $request->reason,
            'notes' => $request->notes,
            'consultation_fee' => $emergencyConsultationFee,
            'duration' => 30,
            'status' => 'scheduled'
        ]);

        // لا ننشئ Payment طوارئ هنا: أجرة الاستشاري تُدفع كموعد مستقل في الكاشير

        // تحديث حالة الطوارئ إلى محولة
        $emergency->update(['status' => 'transferred']);

        return redirect()->back()->with('success', 'تم إنشاء موعد استشاري بنجاح، وسيظهر كدفعة مستقلة في الكاشير');
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
     */
    private function upsertUnifiedEmergencyPayment(Emergency $emergency): void
    {
        $emergency->loadMissing([
            'services',
            'labRequests.labTests',
            'radiologyRequests.radiologyTypes',
        ]);

        $servicesAmount = $emergency->services->sum('price');

        $labAmount = $emergency->labRequests->sum(function ($request) {
            return $request->labTests->sum('price');
        });

        $radiologyAmount = $emergency->radiologyRequests->sum(function ($request) {
            return $request->radiologyTypes->sum('price');
        });

        $totalAmount = $servicesAmount + $labAmount + $radiologyAmount;

        $serviceNames = $emergency->services->pluck('name')->filter()->values();
        $labNames = $emergency->labRequests
            ->flatMap(function ($request) {
                return $request->labTests->pluck('name');
            })
            ->filter()
            ->unique()
            ->values();
        $radiologyNames = $emergency->radiologyRequests
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

        $description = $descriptionParts
            ? implode(' | ', $descriptionParts)
            : 'رسوم طوارئ';

        $existingPayment = Payment::where('emergency_id', $emergency->id)
            ->where('payment_type', 'emergency')
            ->whereNull('appointment_id')
            ->whereNull('paid_at')
            ->latest('id')
            ->first();

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

            return;
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
    }
}
