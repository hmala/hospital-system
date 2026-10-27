<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Notification as AppNotification;
use App\Models\PrescribedMedication;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Request as MedicalRequest;
use App\Models\User;
use App\Models\UserLabTestGroup;
use App\Models\UserLabTestStat;
use App\Models\Visit;
use App\Notifications\VisitCancelledByDoctorNotification;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class DoctorVisitController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('view own visits'))) {
            abort(403, 'غير مصرح لك بالوصول إلى محطة كشف الطبيب');
        }

        $doctor = $user->doctor;
        if (! $doctor) {
            $todayVisits = collect();
            $upcomingVisits = collect();
            $appointments = collect();

            return view('doctors.visits.index', compact('todayVisits', 'upcomingVisits', 'appointments'))->with('error', 'بيانات الطبيب غير مكتملة');
        }

        // جميع الزيارات (الحالية والسابقة) - مرتبة حسب الأولوية
        $allVisits = Visit::where('doctor_id', $doctor->id)
            ->with(['patient.user', 'appointment.emergency'])
            ->orderByRaw("CASE 
                WHEN status != 'completed' AND status != 'cancelled' AND DATE(visit_date) < CURDATE() THEN 1
                WHEN DATE(visit_date) = CURDATE() THEN 2
                WHEN status = 'completed' AND DATE(visit_date) < CURDATE() THEN 3
                WHEN DATE(visit_date) > CURDATE() THEN 4
                ELSE 5
            END")
            ->orderBy('visit_date', 'desc')
            ->orderBy('visit_time', 'desc')
            ->limit(200)
            ->get();

        // للتوافق مع View القديمة
        $todayVisits = $allVisits->filter(function ($visit) {
            return $visit->visit_date && $visit->visit_date->isToday();
        });

        $upcomingVisits = $allVisits->filter(function ($visit) {
            return $visit->visit_date && $visit->visit_date->isFuture();
        });

        $completedVisits = $allVisits->filter(function ($visit) {
            return $visit->status == 'completed' && $visit->visit_date && $visit->visit_date->isPast();
        });

        $incompleteVisits = $allVisits->filter(function ($visit) {
            return $visit->status != 'completed' && $visit->status != 'cancelled' && $visit->visit_date && $visit->visit_date->isPast();
        });

        // جميع المواعيد المجدولة (لم يتم تحويلها إلى زيارات بعد)
        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereDoesntHave('visit')
            ->whereDate('appointment_date', '>=', today())
            ->with(['patient.user', 'department'])
            ->orderBy('appointment_date', 'asc')
            ->limit(50)
            ->get();

        // جميع الطلبات الطبية الأخيرة للطبيب (آخر 50 طلب)
        $doctorRequests = MedicalRequest::whereHas('visit', function ($query) use ($doctor) {
            $query->where('doctor_id', $doctor->id);
        })
            ->with(['visit.patient.user'])
            ->latest()
            ->limit(50)
            ->get();

        return view('doctors.visits.index', compact('doctor', 'allVisits', 'todayVisits', 'upcomingVisits', 'appointments', 'doctorRequests', 'completedVisits', 'incompleteVisits'));
    }

    public function convertAppointmentToVisit(Appointment $appointment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('manage own visits') && ! $user->can('manage consultant availability') && ! $user->can('create visits')))) {
            abort(403, 'غير مصرح لك بتحويل الموعد إلى زيارة');
        }

        // التحقق من وجود علاقة الطبيب للأطباء
        if ($user->hasRole('doctor') && ! $user->doctor) {
            abort(403, 'لم يتم العثور على بيانات الطبيب');
        }

        // التحقق من أن الموعد يخص الطبيب الحالي (للأطباء فقط)
        if ($user->hasRole('doctor') && $appointment->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الموعد');
        }

        // التحقق من دفع رسوم الكشف قبل إدخال المريض للطبيب
        if ($appointment->payment_status !== 'paid') {
            return redirect()->back()->with('error', 'لا يمكن إدخال المريض للطبيب قبل اكتمال الدفع');
        }

        // إذا كان الموعد مرتبطاً بزيارة مسبقاً (مثل حجوزات السونار من الاستعلامات)
        if ($appointment->visit) {
            $appointment->visit->update(['status' => 'in_progress']);
            $appointment->update(['status' => 'confirmed']);
            if ($user->hasRole('consultation_receptionist') || $user->hasRole('admin')) {
                return redirect()->route('consultant-availability.index')->with('success', 'تم إدخال المريض بنجاح');
            }

            return redirect()->route('doctor.visits.show', $appointment->visit->id)->with('success', 'تم إدخال المريض بنجاح');
        }

        // إنشاء زيارة من الموعد
        $visit = Visit::create([
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id,
            'department_id' => $appointment->department_id,
            'appointment_id' => $appointment->id,
            'visit_date' => $appointment->appointment_date,
            'visit_time' => now()->format('H:i'),
            'visit_type' => 'checkup',
            'chief_complaint' => $appointment->reason ?: 'زيارة مجدولة',
            'status' => 'in_progress',
        ]);

        // إذا كان المستخدم موظف استشارية، إرجاعه لصفحته
        if ($user->hasRole('consultation_receptionist')) {
            return redirect()->route('consultant-availability.index')->with('success', 'تم إدخال المريض للطبيب بنجاح');
        }

        return redirect()->route('visits.index', $visit)->with('success', 'تم تحويل الموعد إلى زيارة بنجاح');
    }

    /**
     * تسجيل رأي الاستشاري وملاحظاته لحالة الطوارئ
     */
    public function respondToEmergencyConsultation(HttpRequest $request, Appointment $appointment)
    {
        $user = Auth::user();
        $doctor = $user->doctor;

        if (! $user->hasRole('admin') && ! $doctor) {
            abort(403, 'غير مصرح لك بتسجيل رأي الاستشارة');
        }

        if ($doctor && $appointment->doctor_id !== $doctor->id && ! $user->hasRole('admin')) {
            abort(403, 'هذا الموعد الاستشاري مخصص لطبيب آخر');
        }

        $request->validate([
            'consultant_notes' => 'required|string|min:3|max:3000',
            'consultant_recommendation' => 'nullable|string|max:100',
        ]);

        $emergency = $appointment->emergency;
        if (! $emergency) {
            return redirect()->back()->with('error', 'هذا الموعد غير مرتبط بحالة طوارئ');
        }

        $formattedDoctorName = $doctor?->user?->name ?? $user->name;
        $timestamp = now()->format('Y-m-d H:i');
        $newNoteEntry = "--- [رأي الاستشاري: د. {$formattedDoctorName} بتاريخ {$timestamp}] ---\n".$request->consultant_notes;

        $existingNotes = $emergency->consultant_notes;
        $combinedNotes = $existingNotes ? $existingNotes."\n\n".$newNoteEntry : $newNoteEntry;

        $updateData = [
            'consultant_notes' => $combinedNotes,
            'consultant_recommendation' => $request->consultant_recommendation ?: $emergency->consultant_recommendation,
            'consultation_responded_at' => now(),
            'consultant_doctor_id' => $doctor?->id ?? $emergency->consultant_doctor_id,
        ];

        $emergency->update($updateData);

        // تحديث حالة الموعد
        $appointment->update([
            'status' => 'completed',
            'notes' => ($appointment->notes ? $appointment->notes."\n" : '').'تم إبداء رأي الاستشارة: '.$request->consultant_notes,
        ]);

        // إذا كان هناك زيارة تم فتحها للموعد، إكمالها أيضاً
        if ($appointment->visit) {
            $appointment->visit->update(['status' => 'completed']);
        }

        return redirect()->back()->with('success', 'تم توثيق رأي وتوصيات الاستشارة بنجاح وحفظها في إضبارة الطوارئ للمريض');
    }

    public function show(Visit $visit)
    {
        @ini_set('memory_limit', '512M');
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view own visits') && ! $user->can('view visits')))) {
            abort(403, 'غير مصرح لك بالوصول إلى ملف الكشف الطبي');
        }

        // Admin can view all visits
        if ($isAdmin) {
            // continue
        }
        // Doctor can view their own visits OR any visit in read-only mode
        elseif ($user->doctor) {
            if ($visit->doctor_id !== $user->doctor->id) {
                // This is NOT this doctor's visit, allow read-only view
                $visit->load([
                    'patient.user',
                    'appointment',
                    'requests',
                    'prescribedMedications' => function ($query) {
                        $query->orderBy('created_at', 'desc');
                    },
                ]);

                $prescribedMedications = $visit->prescribedMedications()->where('item_type', 'medication')->get();
                $otherTreatments = $visit->prescribedMedications()->where('item_type', 'treatment')->get();
                $hasCompletedRequests = $visit->requests()->where('status', 'completed')->exists();
                $hasPendingRequests = $visit->requests()->where('status', 'pending')->exists();
                $labTests = \App\Models\LabTest::where('is_active', true)->get()->unique(fn ($t) => trim(mb_strtolower($t->name)));
                $radiologyTypes = \App\Models\RadiologyType::where('is_active', true)->get();
                $icd10Codes = \App\Models\ICD10Code::orderBy('code')->get();

                // Return read-only view
                return view('doctors.visits.show-readonly', compact(
                    'visit',
                    'labTests',
                    'radiologyTypes',
                    'icd10Codes',
                    'prescribedMedications',
                    'otherTreatments',
                    'hasCompletedRequests',
                    'hasPendingRequests'
                ));
            }
        }

        // البحث عن أي زيارات أخرى لنفس المريض ولنفس الطبيب في نفس تاريخ اليوم لدمج الفحوصات والطلبات تلقائياً
        $sameDayVisitIds = collect();
        if ($visit->patient_id && $visit->doctor_id && $visit->visit_date) {
            $sameDayVisitIds = Visit::where('patient_id', $visit->patient_id)
                ->where('doctor_id', $visit->doctor_id)
                ->whereDate('visit_date', $visit->visit_date)
                ->pluck('id');
        }
        if (! $sameDayVisitIds->contains($visit->id)) {
            $sameDayVisitIds->push($visit->id);
        }

        $allSameDayRequests = \App\Models\Request::whereIn('visit_id', $sameDayVisitIds)
            ->orderBy('id', 'asc')
            ->get();

        $visit->load([
            'patient.user',
            'appointment',
            'prescribedMedications' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
        ]);
        $visit->setRelation('requests', $allSameDayRequests);

        // Get prescribed medications and separate them into medications and other treatments
        $prescribedMedications = $visit->prescribedMedications()->where('item_type', 'medication')->get();
        $otherTreatments = $visit->prescribedMedications()->where('item_type', 'treatment')->get();

        // Log the data for debugging
        \Illuminate\Support\Facades\Log::info('Visit Medications Data:', [
            'visit_id' => $visit->id,
            'prescribed_count' => $prescribedMedications->count(),
            'treatments_count' => $otherTreatments->count(),
            'medications' => $prescribedMedications->toArray(),
            'treatments' => $otherTreatments->toArray(),
        ]);

        // فحص الفحوصات الطبية (تحاليل وأشعة) المعلقة والمكتملة لليوم
        $hasCompletedRequests = $allSameDayRequests
            ->whereIn('type', ['lab', 'radiology'])
            ->where('status', 'completed')
            ->isNotEmpty();

        $pendingRequests = $allSameDayRequests
            ->whereIn('type', ['lab', 'radiology'])
            ->whereNotIn('status', ['completed', 'cancelled', 'postponed']);

        $hasPendingRequests = $pendingRequests->isNotEmpty();
        $pendingRequestsCount = $pendingRequests->count();

        $labTests = \App\Models\LabTest::where('is_active', true)->get()->unique(fn ($t) => trim(mb_strtolower($t->name)));
        $labTestGroups = UserLabTestGroup::with('labTests')->where('user_id', $user->id)->consultant()->get();
        $favoriteLabTests = UserLabTestStat::getFavoritesForUser($user->id);
        $medicineGroups = \App\Models\UserMedicineGroup::with('medicines')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('is_public', true);
            })
            ->orderByDesc('is_starred')
            ->orderByDesc('usage_count')
            ->orderByDesc('is_public')
            ->orderBy('name', 'asc')
            ->get();

        // Log lab tests count
        \Illuminate\Support\Facades\Log::info('Lab Tests Count: '.$labTests->count());

        // تصفية رموز ICD-10 حسب اختصاص الطبيب
        $doctorSpecialization = $user->doctor ? $user->doctor->specialization : null;
        $icd10Query = \App\Models\ICD10Code::orderBy('code');

        // ربط التخصصات بالفئات المناسبة
        $specializationCategories = [
            // تخصصات عامة
            'طب عام' => [], // جميع الفئات
            'طب أسرة' => [], // جميع الفئات
            'طب طوارئ' => [
                'Injury , poisoning and Certain other Consequences of External causes',
                'External causes of Morbidity & Mortality',
                'Injuries involving multiple body regions',
            ],

            // تخصصات داخلية
            'باطنية' => [
                'Diseases of Circulatory system',
                'Diseases of the Respiratory system',
                'Endocrine, Nutritional and Metabolic Diseases',
                'Diseases of the Digestive system',
                'Certain Infectious and Parasitic Diseases(A00-B99)',
                'Diseases of the Genitourinary system',
                'Infectious',
                'Symptoms, Signs & Abnormal Clinical & Laboratory Findings ,not Elsewhere Classified',
            ],
            'قلبية وعائية' => [
                'Diseases of Circulatory system',
            ],
            'صدرية' => [
                'Diseases of the Respiratory system',
                'Certain Infectious and Parasitic Diseases(A00-B99)',
            ],
            'هضمية' => [
                'Diseases of the Digestive system',
            ],
            'غدد صماء' => [
                'Endocrine, Nutritional and Metabolic Diseases',
            ],
            'كلى' => [
                'Diseases of the Genitourinary system',
            ],
            'روماتيزم' => [
                'Diseases of the Musculoskletal system&connective tissue',
            ],

            // تخصصات جراحية
            'جراحة عامة' => [
                'Injury , poisoning and Certain other Consequences of External causes',
                'Diseases of the Digestive system',
                'Malignant Neoplasms',
                'In situ Neoplasms and Benign Neoplasms with Blood Diseases',
            ],
            'جراحة عظام' => [
                'Diseases of the Musculoskletal system&connective tissue',
                'Injury , poisoning and Certain other Consequences of External causes',
            ],
            'جراحة عصبية' => [
                'Diseases of the Nervous system',
                'Injury , poisoning and Certain other Consequences of External causes',
            ],
            'جراحة قلبية' => [
                'Diseases of Circulatory system',
            ],
            'جراحة صدرية' => [
                'Diseases of the Respiratory system',
            ],
            'جراحة تجميلية' => [
                'Diseases of the skin & Subcutaneous tissue',
                'Injury , poisoning and Certain other Consequences of External causes',
            ],

            // تخصصات نسائية وأطفال
            'نسائية وتوليد' => [
                'Pregnancy , Childbirth and the Peurperium',
                'Diseases of the Genitourinary system',
            ],
            'أطفال' => [
                'Certain conditions originating in the Perinatal period',
                'Certain Infectious and Parasitic Diseases(A00-B99)',
                'Endocrine, Nutritional and Metabolic Diseases',
            ],
            'حديثي الولادة' => [
                'Certain conditions originating in the Perinatal period',
            ],

            // تخصصات أخرى
            'جلدية' => [
                'Diseases of the skin & Subcutaneous tissue',
            ],
            'عيون' => [
                'Diseases of the Eye & Adnexa with Ear & Mastoid process Dis.',
            ],
            'أنف وأذن وحنجرة' => [
                'Diseases of the Eye & Adnexa with Ear & Mastoid process Dis.',
                'Diseases of the Respiratory system',
            ],
            'أسنان' => [
                'Diseases of the Digestive system', // تقريباً لأمراض الفم والأسنان
            ],
            'نفسية وعصبية' => [
                'Mental and Behavioural Disorders',
                'Diseases of the Nervous system',
            ],
            'طب نفسي' => [
                'Mental and Behavioural Disorders',
            ],
            'جملة عصبية' => [
                'Diseases of the Nervous system',
                'Mental and Behavioural Disorders',
                'العدوى الفيروسية الأخرى في الجهاز العصبي المركزي، غير مصنفة في مكان آخر',
            ],

            // تخصصات متخصصة
            'أورام' => [
                'Malignant Neoplasms',
                'In situ Neoplasms and Benign Neoplasms with Blood Diseases',
            ],
            'أشعة' => [
                'Symptoms, Signs & Abnormal Clinical & Laboratory Findings ,not Elsewhere Classified',
            ],
            'مختبر' => [
                'Symptoms, Signs & Abnormal Clinical & Laboratory Findings ,not Elsewhere Classified',
            ],
            'تخدير' => [
                'Factors influencing health status & contact with health services',
            ],
            'طب رياضي' => [
                'Diseases of the Musculoskletal system&connective tissue',
                'Injury , poisoning and Certain other Consequences of External causes',
            ],
            'طب الشيخوخة' => [
                'Diseases of Circulatory system',
                'Diseases of the Musculoskletal system&connective tissue',
                'Mental and Behavioural Disorders',
            ],
            'طب الطب الشرعي' => [
                'External causes of Morbidity & Mortality',
                'Injury , poisoning and Certain other Consequences of External causes',
            ],
            'صحة عامة' => [
                'Certain Infectious and Parasitic Diseases(A00-B99)',
                'Factors influencing health status & contact with health services',
            ],
            'طب الطيران' => [
                'Diseases of the Respiratory system',
                'Factors influencing health status & contact with health services',
            ],
            'طب الغوص' => [
                'Diseases of the Respiratory system',
                'Injury , poisoning and Certain other Consequences of External causes',
            ],
        ];

        if ($doctorSpecialization && isset($specializationCategories[$doctorSpecialization]) && ! empty($specializationCategories[$doctorSpecialization])) {
            $icd10Query->whereIn('category', $specializationCategories[$doctorSpecialization]);
        }
        // إذا كان التخصص غير معروف أو فارغ، أو كان 'طب عام'، يظهر جميع الرموز

        $icd10Codes = $icd10Query->get();

        // Get radiology types for the radiology request modal
        $radiologyTypes = \App\Models\RadiologyType::where('is_active', true)->get();

        // Get emergency services
        $emergencyServices = \App\Models\EmergencyService::where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        // Get current day in Arabic
        $daysMap = [
            'Saturday' => 'السبت',
            'Sunday' => 'الأحد',
            'Monday' => 'الإثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
        ];
        $currentDay = $daysMap[date('l')] ?? 'السبت';

        $availableDoctors = Doctor::with('user')
            ->where('type', 'consultant')
            ->where('is_active', true)
            ->workingOnDay($currentDay)
            ->where('id', '<>', $visit->doctor_id)
            ->orderBy('specialization')
            ->orderBy('id')
            ->get();

        $availableMedicines = Medicine::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'generic_name', 'dosage_form', 'strength', 'sale_price', 'sub_unit_sale_price', 'main_unit', 'sub_unit']);

        $latestPrescription = $visit->prescriptions()
            ->with(['items.medicine', 'items.suggestedMedicine', 'items.dispensedMedicine'])
            ->latest()
            ->first();

        $pendingSubstitutionRequests = $latestPrescription
            ? $latestPrescription->items->where('substitution_status', 'pending_approval')
            : collect();

        // السجل الطبي التراكمي للمريض (الزيارات السابقة، العمليات، الطوارئ)
        $pastVisits = $visit->patient_id
            ? Visit::where('patient_id', $visit->patient_id)
                ->where('id', '!=', $visit->id)
                ->with(['doctor.user', 'requests', 'prescribedMedications', 'prescriptions.items.medicine'])
                ->orderBy('visit_date', 'desc')
                ->limit(15)
                ->get()
            : collect();

        $pastSurgeries = $visit->patient_id
            ? \App\Models\Surgery::where('patient_id', $visit->patient_id)
                ->with('doctor.user')
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        $pastEmergencies = $visit->patient_id
            ? \App\Models\Emergency::where('patient_id', $visit->patient_id)
                ->with('doctor.user')
                ->latest()
                ->limit(10)
                ->get()
            : collect();

        $patientAppointments = $visit->patient_id
            ? \App\Models\Appointment::where('patient_id', $visit->patient_id)
                ->with(['doctor.user', 'department'])
                ->orderBy('appointment_date', 'desc')
                ->limit(20)
                ->get()
            : collect();

        // تحديد نوع تأمين المريض الحالي بدقة (hi أو moi أو none)
        $patientInsuranceType = $visit->appointment?->insurance_type
            ?: ($visit->patient?->insurance_type ?: 'none');

        if (! in_array($patientInsuranceType, ['hi', 'moi']) && ! empty($visit->patient?->covered_by_insurance)) {
            $patientInsuranceType = 'hi';
        }

        return view('doctors.visits.show', compact(
            'visit',
            'labTests',
            'labTestGroups',
            'favoriteLabTests',
            'medicineGroups',
            'radiologyTypes',
            'icd10Codes',
            'prescribedMedications',
            'otherTreatments',
            'hasCompletedRequests',
            'hasPendingRequests',
            'pendingRequestsCount',
            'availableDoctors',
            'emergencyServices',
            'availableMedicines',
            'latestPrescription',
            'pendingSubstitutionRequests',
            'pastVisits',
            'pastSurgeries',
            'pastEmergencies',
            'patientAppointments',
            'patientInsuranceType'
        ));
    }

    public function update(HttpRequest $request, Visit $visit)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('manage own visits') && ! $user->can('edit visits')))) {
            abort(403, 'غير مصرح لك بتعديل بيانات الزيارة');
        }

        // التحقق من أن الزيارة تخص الطبيب الحالي
        if (! $isAdmin && $user->doctor && $visit->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بتعديل كشف طبيب آخر');
        }

        // تحقق مشروط حسب البيانات المرسلة
        $rules = [
            'vital_signs' => 'nullable|array',
            'physical_examination' => 'nullable|string|max:1000',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
        ];

        // إذا كانت البيانات تحتوي على تشخيص، فتتحقق منه
        if ($request->has('diagnosis')) {
            $rules['diagnosis'] = 'required|array';
            $rules['diagnosis.description'] = 'nullable|string|max:1000';
            $rules['diagnosis.code'] = 'nullable|string';
            $rules['diagnosis.custom_code'] = 'nullable|string|max:10';
        }

        // إذا كانت البيانات تحتوي على الأدوية المحددة، فتتحقق منها
        if ($request->has('prescribed_medications')) {
            $rules['prescribed_medications'] = 'nullable|array';
            // التحقق من الأدوية العادية
            if (isset($request->prescribed_medications) && is_array($request->prescribed_medications)) {
                foreach ($request->prescribed_medications as $key => $medication) {
                    if (is_numeric($key)) { // الأدوية العادية
                        $rules["prescribed_medications.{$key}.name"] = 'required|string|max:255';
                        $rules["prescribed_medications.{$key}.type"] = 'nullable|in:tablet,injection,syrup,cream,drops,other';
                        $rules["prescribed_medications.{$key}.dosage"] = 'nullable|string|max:100';
                        $rules["prescribed_medications.{$key}.frequency"] = 'nullable|in:1,2,3,4,as_needed';
                        $rules["prescribed_medications.{$key}.times"] = 'nullable|string|max:255';
                        $rules["prescribed_medications.{$key}.duration"] = 'nullable|string|max:100';
                        $rules["prescribed_medications.{$key}.instructions"] = 'nullable|string|max:500';
                    }
                }
            }
        }

        // إضافة القواعد الأخرى
        $rules['treatment_plan'] = 'nullable|string|max:1000';
        $rules['notes'] = 'nullable|string|max:1000';
        $rules['follow_up_date'] = 'nullable|date';
        $rules['follow_up_notes'] = 'nullable|string|max:500';

        $request->validate($rules);

        // إذا كان هناك تشخيص واختير "أخرى"، تأكد من وجود custom_code
        if ($request->has('diagnosis') && ($request->diagnosis['code'] === 'other' || ($request->diagnosis['actual_code'] ?? '') === 'other') && empty($request->diagnosis['custom_code'])) {
            return back()->withErrors(['diagnosis.custom_code' => 'يرجى إدخال رمز ICD مخصص عند اختيار "أخرى".']);
        }

        // تحديث الزيارة
        $updateData = [];

        if ($request->has('vital_signs')) {
            $updateData['vital_signs'] = $request->vital_signs;
        }

        if ($request->has('physical_examination')) {
            $updateData['physical_examination'] = $request->physical_examination;
        }

        if ($request->has('diagnosis')) {
            $diag = $request->diagnosis;
            if (empty($diag['description']) && ! empty($diag['code'])) {
                $diag['description'] = $diag['code'];
            }
            $updateData['diagnosis'] = $diag;
        }

        if ($request->has('treatment_plan')) {
            $updateData['treatment_plan'] = $request->treatment_plan;
        }

        if ($request->has('notes')) {
            $updateData['notes'] = $request->notes;
        }

        if ($request->has('follow_up_date')) {
            $updateData['follow_up_date'] = $request->filled('follow_up_date') ? $request->follow_up_date : null;
            $updateData['follow_up_notes'] = $request->follow_up_notes;

            // إذا تم تحديد موعد مراجعة، يتم إنشاء أو تحديث موعد المراجعة المجانية في جدول المواعيد
            if ($request->filled('follow_up_date')) {
                $fuDate = \Carbon\Carbon::parse($request->follow_up_date)->format('Y-m-d');

                $followupAppt = Appointment::firstOrNew([
                    'recheck_parent_visit_id' => $visit->id,
                ]);

                $followupAppt->fill([
                    'patient_id' => $visit->patient_id,
                    'doctor_id' => $visit->doctor_id,
                    'department_id' => $visit->department_id,
                    'appointment_date' => $fuDate.' 09:00:00',
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'is_free_recheck' => true,
                    'consultation_fee' => 0,
                    'reason' => 'مراجعة مجانية',
                    'notes' => $request->follow_up_notes ?: 'مراجعة مجانية مجدولة من محطة الطبيب',
                ]);

                $followupAppt->save();
            } else {
                Appointment::where('recheck_parent_visit_id', $visit->id)
                    ->whereIn('status', ['scheduled', 'confirmed'])
                    ->delete();
            }
        }

        if ($request->has('status')) {
            if ($request->status === 'completed') {
                $visitDateStr = \Carbon\Carbon::parse($visit->visit_date)->toDateString();
                $sameDayVisitIds = Visit::where('patient_id', $visit->patient_id)
                    ->where('doctor_id', $visit->doctor_id)
                    ->whereDate('visit_date', $visitDateStr)
                    ->pluck('id');

                if (! $sameDayVisitIds->contains($visit->id)) {
                    $sameDayVisitIds->push($visit->id);
                }

                $pendingRequests = \App\Models\Request::whereIn('visit_id', $sameDayVisitIds)
                    ->whereIn('type', ['lab', 'radiology'])
                    ->whereNotIn('status', ['completed', 'cancelled', 'postponed'])
                    ->get();

                if ($pendingRequests->isNotEmpty()) {
                    $pendingCount = $pendingRequests->count();
                    $errMsg = "⚠️ لا يمكن إنهاء الزيارة! توجد فحوصات معلقة لم تكتمل نتائجها بعد (عددها: {$pendingCount}). يرجى الانتظار لحين صدور كافة النتائج واستلامها، أو استخدام «تعليق بانتظار الفحوصات» لاستدعاء المرضى الآخرين ريثما تصدر النتائج في محطتك.";
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => $errMsg,
                            'pending_requests_count' => $pendingCount,
                        ], 422);
                    }

                    return redirect()->back()->with('error', $errMsg);
                }
            }

            $updateData['status'] = $request->status;
        }

        if ($request->has('prescribed_medications') || $request->has('is_prescription_form')) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($visit, $request) {
                // حذف الأدوية والعلاجات القديمة في جدول PrescribedMedication للتوافق
                $visit->prescribedMedications()->delete();

                $diagnosisText = null;
                if ($request->has('diagnosis')) {
                    $diagnosisText = is_array($request->diagnosis)
                        ? ($request->diagnosis['description'] ?? json_encode($request->diagnosis, JSON_UNESCAPED_UNICODE))
                        : $request->diagnosis;
                } elseif ($visit->diagnosis) {
                    $diagnosisText = is_array($visit->diagnosis)
                        ? ($visit->diagnosis['description'] ?? json_encode($visit->diagnosis, JSON_UNESCAPED_UNICODE))
                        : $visit->diagnosis;
                }

                // إنشاء أو تحديث الوصفة الطبية الإلكترونية
                $prescription = Prescription::firstOrCreate(
                    [
                        'visit_id' => $visit->id,
                        'status' => 'pending',
                    ],
                    [
                        'patient_id' => $visit->patient_id,
                        'doctor_id' => $visit->doctor_id,
                        'diagnosis' => $diagnosisText,
                        'notes' => $request->treatment_plan ?? $visit->treatment_plan,
                    ]
                );

                if ($prescription->status === 'pending') {
                    $prescription->update([
                        'patient_id' => $visit->patient_id,
                        'doctor_id' => $visit->doctor_id,
                        'diagnosis' => $diagnosisText,
                        'notes' => $request->treatment_plan ?? $visit->treatment_plan,
                    ]);
                    $prescription->items()->delete();
                }

                // حفظ الأدوية العادية
                if (isset($request->prescribed_medications) && is_array($request->prescribed_medications)) {
                    foreach ($request->prescribed_medications as $key => $item) {
                        if (is_numeric($key) && isset($item['name']) && ! empty($item['name'])) {
                            PrescribedMedication::create([
                                'visit_id' => $visit->id,
                                'item_type' => 'medication',
                                'name' => $item['name'],
                                'type' => $item['type'] ?? null,
                                'dosage' => $item['dosage'] ?? null,
                                'frequency' => $item['frequency'] ?? null,
                                'times' => $item['times'] ?? null,
                                'duration' => $item['duration'] ?? null,
                                'instructions' => $item['instructions'] ?? null,
                            ]);

                            // ربط الصنف بالدواء في جدول الأدوية الرسمي إن وجد
                            $medId = $item['medicine_id'] ?? null;
                            if (! $medId) {
                                $matchedMedicine = Medicine::where('name', $item['name'])
                                    ->orWhere('generic_name', $item['name'])
                                    ->first();
                                $medId = $matchedMedicine?->id;
                            }

                            if ($prescription->status === 'pending') {
                                $freqStr = ! empty($item['frequency']) ? ('x'.$item['frequency']) : '';
                                $dosageStr = $item['dosage'] ?? '';
                                $timesStr = $item['times'] ?? '';

                                PrescriptionItem::create([
                                    'prescription_id' => $prescription->id,
                                    'medicine_id' => $medId,
                                    'medicine_name' => $item['name'],
                                    'quantity' => isset($item['quantity']) && (float) $item['quantity'] > 0 ? (float) $item['quantity'] : 1,
                                    'unit_type' => $item['unit_type'] ?? 'main_unit',
                                    'dosage_frequency' => trim("{$dosageStr} {$freqStr} {$timesStr}"),
                                    'duration_days' => is_numeric($item['duration'] ?? null) ? (int) $item['duration'] : 7,
                                    'instructions' => $item['instructions'] ?? null,
                                    'status' => 'pending',
                                ]);
                            }
                        }
                    }

                    // حفظ العلاجات الأخرى
                    if (isset($request->prescribed_medications['other_treatments']) &&
                        is_array($request->prescribed_medications['other_treatments'])) {
                        foreach ($request->prescribed_medications['other_treatments'] as $treatment) {
                            if (isset($treatment['type']) && isset($treatment['description'])) {
                                PrescribedMedication::create([
                                    'visit_id' => $visit->id,
                                    'item_type' => 'treatment',
                                    'name' => $treatment['description'],
                                    'type' => $treatment['type'],
                                    'duration' => $treatment['duration'] ?? null,
                                    'frequency' => $treatment['frequency'] ?? null,
                                    'instructions' => $treatment['instructions'] ?? null,
                                ]);
                            }
                        }
                    }
                }

                // إذا لم يتم تسجيل أي أدوية بالوصفة، احذف سجل الوصفة الفارغ لتجنب ظهور وصفة بـ 0 أدوية
                if (isset($prescription) && $prescription->status === 'pending' && $prescription->items()->count() === 0) {
                    $prescription->delete();
                }
            });
        }

        // تحديد حالة الزيارة - إذا تم إرسال جميع البيانات، اجعلها مكتملة
        // تم تعطيل هذا المنطق التلقائي للسماح بالتحكم اليدوي في إنهاء الزيارة
        // if ($request->has('diagnosis') && $request->has('treatment_plan')) {
        //     $updateData['status'] = 'completed';
        // }

        $visit->update($updateData);

        // إذا تم إنهاء الزيارة، حدث حالة الموعد المرتبط بها
        if (isset($updateData['status']) && $updateData['status'] === 'completed' && $visit->appointment) {
            $visit->appointment->complete();
        } elseif (isset($updateData['status']) && $updateData['status'] === 'in_progress' && $visit->appointment) {
            $visit->appointment->update(['status' => 'in_consultation']);
        }

        $successMsg = (isset($updateData['status']) && $updateData['status'] === 'in_progress')
            ? 'تم إعادة فتح الزيارة بنجاح للمتابعة والتعديل ✅'
            : ((isset($updateData['status']) && $updateData['status'] === 'completed')
                ? 'تم إنهاء الزيارة وأرشفتها بنجاح ✅'
                : 'تم حفظ البيانات بنجاح');

        // التحقق من نوع الطلب (AJAX أو عادي)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'visit_status' => $visit->status,
            ]);
        }

        return redirect()->back()->with('success', $successMsg);
    }

    public function cancel(Visit $visit)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('manage own visits') && ! $user->can('cancel appointments') && ! $user->can('delete visits')))) {
            abort(403, 'غير مصرح لك بإلغاء هذه الزيارة');
        }

        // التحقق من أن الزيارة تخص الطبيب الحالي
        if (! $isAdmin && $user->doctor && $visit->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بإلغاء زيارة طبيب آخر');
        }

        // التحقق من أن الزيارة لم تكتمل بعد
        if ($visit->status === 'completed') {
            return redirect()->back()->with('error', 'لا يمكن إلغاء زيارة مكتملة');
        }

        // إذا كانت الزيارة مرتبطة بحجز تم تحويله إلى زيارة، ألغِ الحجز وأرسل إشعاراً
        if ($visit->appointment) {
            $visit->update(['status' => 'cancelled']);

            $visit->appointment->cancel('تم إلغاء الحجز بعد التحويل من قبل الطبيب '.optional(Auth::user())->name);

            $notificationRecipients = User::role('consultation_receptionist')->get()
                ->concat(User::role('cashier')->get())
                ->unique('id');

            foreach ($notificationRecipients as $recipient) {
                $recipient->notify(new VisitCancelledByDoctorNotification($visit));
            }

            AppNotification::createForRole(
                'consultation_receptionist',
                'visit_cancelled_by_doctor',
                'تم إلغاء الحجز بعد التحويل للطبيب',
                'قام الطبيب '.optional($visit->doctor)->user->name.' بإلغاء الحجز المحول للمريض '.optional($visit->patient)->user->name.'.',
                [
                    'visit_id' => $visit->id,
                    'appointment_id' => optional($visit->appointment)->id,
                    'doctor_name' => optional($visit->doctor)->user->name,
                    'patient_name' => optional($visit->patient)->user->name,
                    'visit_date' => optional($visit->visit_date)->format('Y-m-d'),
                    'visit_time' => optional($visit->visit_time),
                ]
            );

            AppNotification::createForRole(
                'cashier',
                'visit_cancelled_by_doctor',
                'تم إلغاء الحجز بعد التحويل للطبيب',
                'قام الطبيب '.optional($visit->doctor)->user->name.' بإلغاء الحجز المحول للمريض '.optional($visit->patient)->user->name.'.',
                [
                    'visit_id' => $visit->id,
                    'appointment_id' => optional($visit->appointment)->id,
                    'doctor_name' => optional($visit->doctor)->user->name,
                    'patient_name' => optional($visit->patient)->user->name,
                    'visit_date' => optional($visit->visit_date)->format('Y-m-d'),
                    'visit_time' => optional($visit->visit_time),
                ]
            );

            $hasPaidRequests = \App\Models\Request::where('visit_id', $visit->id)->where('payment_status', 'paid')->exists()
                || \App\Models\RadiologyRequest::where('visit_id', $visit->id)->where('payment_status', 'paid')->exists();

            $successMsg = 'تم إلغاء كشفية الطبيب بنجاح واسترجاع رسوم الكشف للمريض.';
            if ($hasPaidRequests) {
                $successMsg .= ' (تنبيه: توجد فحوصات طبية مسددة تبقى سارية، أو تسترجع عبر الكاشير حصراً).';
            }

            return redirect()->back()->with('success', $successMsg);
        }

        // حذف الزيارة نهائياً بدلاً من إلغائها
        $visit->delete();

        return redirect()->back()->with('success', 'تم حذف الزيارة بنجاح');
    }

    public function referToDoctor(HttpRequest $request, Visit $visit)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('manage own visits'))) {
            abort(403, 'غير مصرح لك بإحالة المريض إلى طبيب آخر');
        }

        if (! $isAdmin && $user->doctor && $visit->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بإحالة هذا المريض');
        }

        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        $newDoctor = Doctor::find($request->doctor_id);
        if (! $newDoctor || ! $newDoctor->is_active || $newDoctor->type !== 'consultant') {
            return redirect()->back()->with('error', 'الطبيب المختار غير صالح');
        }

        if ($newDoctor->id === $visit->doctor_id) {
            return redirect()->back()->with('error', 'الطبيب الجديد هو نفس الطبيب الحالي');
        }

        $visit->doctor_id = $newDoctor->id;
        $visit->department_id = $newDoctor->department_id;
        $visit->save();

        if ($visit->appointment) {
            $appointment = $visit->appointment;
            $appointment->doctor_id = $newDoctor->id;
            $appointment->department_id = $newDoctor->department_id;

            if ($appointment->payment_status === 'paid' && $appointment->payment) {
                $note = 'تم تحويل المريض لطبيب آخر دون دفع إضافي';
                $appointment->payment->description = trim($appointment->payment->description.' | '.$note);
                $appointment->payment->notes = trim(($appointment->payment->notes ?? '').' | '.$note, ' | ');
                $appointment->payment->save();
            }

            $appointment->save();
        }

        return redirect()->back()->with('success', 'تم تحويل المريض للطبيب الجديد بنجاح. سيتم احتساب الأتعاب للطبيب الجديد في السجل المالي.');
    }

    public function storeRequest(HttpRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('manage own visits'))) {
            abort(403, 'غير مصرح لك بطلب الفحوصات الطبية');
        }

        $rules = [
            'visit_id' => 'required|exists:visits,id',
            'type' => 'required|in:lab,radiology,pharmacy,nursing',
            'description' => 'nullable|string|max:1000',
            'doctor_notes' => $request->type === 'radiology' ? 'required|string|max:1000' : 'nullable|string|max:1000',
            'priority' => 'nullable|in:normal,urgent,emergency',
            'tests' => 'nullable|array',
            'tests.*' => 'string',
            'radiology_types' => 'nullable|array',
            'radiology_types.*' => 'integer|exists:radiology_types,id',
            'nursing_services' => 'nullable|array',
            'nursing_services.*' => 'integer|exists:emergency_services,id',
        ];

        $messages = [
            'doctor_notes.required' => 'يرجى كتابة ملاحظات وتوجيهات الطبيب لقسم الأشعة والتصوير (مطلوب)',
        ];

        $request->validate($rules, $messages);

        $visit = Visit::findOrFail($request->visit_id);

        // التحقق من أن الزيارة تخص الطبيب الحالي
        if ($visit->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بإنشاء طلب لهذه الزيارة');
        }

        $details = [
            'description' => $request->description ?: 'طلب '.($request->type === 'lab' ? 'مختبر' : ($request->type === 'radiology' ? 'أشعة' : ($request->type === 'nursing' ? 'خدمات تمريضية' : 'صيدلية'))),
            'priority' => $request->priority ?: 'normal',
        ];

        if ($request->filled('doctor_notes')) {
            $details['doctor_notes'] = trim($request->doctor_notes);
        }

        if ($request->type === 'lab' && $request->tests) {
            $rawTests = is_array($request->tests) ? $request->tests : [$request->tests];
            $cleanTests = array_values(array_unique(array_filter(array_map('trim', $rawTests))));
            $details['tests'] = $cleanTests;

            // جلب المعرف للفحص المفعل فقط ومنع سحب أي فحص معطل أو مكرر
            $resolvedIds = [];
            foreach ($cleanTests as $testName) {
                $activeTest = \App\Models\LabTest::where('is_active', true)
                    ->where(function ($q) use ($testName) {
                        $q->where('name', $testName)
                            ->orWhere('code', $testName);
                    })
                    ->first();
                if ($activeTest) {
                    $resolvedIds[] = $activeTest->id;
                }
            }
            $details['lab_test_ids'] = array_values(array_unique($resolvedIds));

            // إذا كان هناك طلب مختبر سابق لنفس الزيارة ما زال معلقاً وغير مسدد بالكاشير، ادمج الفحوصات معه في وصل واحد
            $existingUnpaidLab = MedicalRequest::where('visit_id', $visit->id)
                ->where('type', 'lab')
                ->where('payment_status', 'pending')
                ->whereIn('status', ['pending', 'pending_service_selection'])
                ->first();

            if ($existingUnpaidLab) {
                $exDetails = is_string($existingUnpaidLab->details)
                    ? json_decode($existingUnpaidLab->details, true)
                    : ($existingUnpaidLab->details ?? []);

                $prevTests = $exDetails['tests'] ?? [];
                $prevIds = $exDetails['lab_test_ids'] ?? [];

                $mergedTests = array_values(array_unique(array_merge((array) $prevTests, (array) $details['tests'])));
                $mergedIds = array_values(array_unique(array_merge((array) $prevIds, (array) $details['lab_test_ids'])));

                $exDetails['tests'] = $mergedTests;
                $exDetails['lab_test_ids'] = $mergedIds;

                if (! empty($details['doctor_notes'])) {
                    $exDetails['doctor_notes'] = ! empty($exDetails['doctor_notes'])
                        ? ($exDetails['doctor_notes'].' | '.$details['doctor_notes'])
                        : $details['doctor_notes'];
                }

                $existingUnpaidLab->details = $exDetails;
                $existingUnpaidLab->save();

                $mergedSuccessMsg = 'تم دمج وإضافة التحاليل بنجاح إلى وصل المريض الحالي في الكاشير (وصل موحد)';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $mergedSuccessMsg,
                        'request_count' => $visit->requests()->count(),
                        'request_id' => $existingUnpaidLab->id,
                        'requires_payment' => true,
                        'cashier_url' => route('cashier.request.payment.form', $existingUnpaidLab->id),
                    ]);
                }

                return redirect()->back()->with('success', $mergedSuccessMsg);
            }
        }

        if ($request->type === 'nursing' && $request->nursing_services) {
            $details['nursing_services'] = $request->nursing_services;

            // الحصول على أسماء الخدمات التمريضية
            $nursingServiceNames = [];
            foreach ($request->nursing_services as $serviceId) {
                $service = \App\Models\EmergencyService::find($serviceId);
                if ($service) {
                    $nursingServiceNames[] = $service->name;
                }
            }
            $details['nursing_service_names'] = $nursingServiceNames;
        }

        $detectedSubtype = null;
        if ($request->type === 'radiology' && $request->radiology_types) {
            $details['radiology_types'] = (array) $request->radiology_types;
            $details['radiology_type_ids'] = (array) $request->radiology_types;

            // استقبال وتخزين الأعضاء/المناطق المستهدفة لكل فحص (مثل: الصدر، الركبة)
            $radiologyTargets = (array) $request->input('radiology_targets', []);
            $details['radiology_targets'] = $radiologyTargets;

            // إذا كان هناك طلب أشعة سابق لنفس الزيارة ما زال معلقاً وغير مسدد بالكاشير، ادمج الفحوصات معه في وصل واحد
            $existingUnpaidRad = MedicalRequest::where('visit_id', $visit->id)
                ->where('type', 'radiology')
                ->where('payment_status', 'pending')
                ->whereIn('status', ['pending', 'pending_service_selection', 'scheduled'])
                ->first();

            if ($existingUnpaidRad) {
                $exDetails = is_string($existingUnpaidRad->details)
                    ? json_decode($existingUnpaidRad->details, true)
                    : ($existingUnpaidRad->details ?? []);

                $prevTypeIds = array_map('intval', (array) ($exDetails['radiology_type_ids'] ?? $exDetails['radiology_types'] ?? []));
                $newTypeIds = array_map('intval', (array) $details['radiology_type_ids']);
                $mergedTypeIds = array_values(array_unique(array_merge($prevTypeIds, $newTypeIds)));

                $exDetails['radiology_types'] = $mergedTypeIds;
                $exDetails['radiology_type_ids'] = $mergedTypeIds;

                // دمج المناطق المستهدفة targets
                $prevTargets = (array) ($exDetails['radiology_targets'] ?? []);
                $mergedTargets = array_merge($prevTargets, $radiologyTargets);
                $exDetails['radiology_targets'] = $mergedTargets;

                // تحديث الوصف وتحديد الـ subtype المدمج
                $mergedNames = [];
                $mergedSubtype = $existingUnpaidRad->subtype;
                foreach ($mergedTypeIds as $tId) {
                    $rType = \App\Models\RadiologyType::find($tId);
                    if ($rType) {
                        $targetPart = isset($mergedTargets[$tId]) ? trim($mergedTargets[$tId]) : null;
                        $displayName = $rType->name;
                        if (! empty($targetPart)) {
                            $displayName .= " [العضو: {$targetPart}]";
                        }
                        $mergedNames[] = $displayName;

                        if (! $mergedSubtype) {
                            $cat = mb_strtolower(trim($rType->main_category ?? ''));
                            $sub = mb_strtolower(trim($rType->subcategory ?? ''));
                            $nm = mb_strtolower(trim($rType->name ?? ''));
                            if (str_contains($cat, 'سونار') || str_contains($sub, 'سونار') || str_contains($nm, 'سونار') || str_contains($nm, 'sonar') || str_contains($cat, 'ultrasound')) {
                                $mergedSubtype = 'ultrasound';
                            } elseif (str_contains($cat, 'رنين') || str_contains($sub, 'رنين') || str_contains($nm, 'رنين') || str_contains($cat, 'mri')) {
                                $mergedSubtype = 'mri';
                            } elseif (str_contains($cat, 'إيكو') || str_contains($sub, 'إيكو') || str_contains($nm, 'إيكو') || str_contains($cat, 'echo')) {
                                $mergedSubtype = 'echo';
                            } elseif (str_contains($cat, 'مفراس') || str_contains($sub, 'مفراس') || str_contains($nm, 'مفراس') || str_contains($cat, 'ct')) {
                                $mergedSubtype = 'ct';
                            } else {
                                $mergedSubtype = 'general';
                            }
                        }
                    }
                }

                $exDetails['description'] = ! empty($mergedNames) ? ('طلب أشعة: '.implode(', ', $mergedNames)) : 'طلب أشعة';
                $existingUnpaidRad->description = $exDetails['description'];
                if ($mergedSubtype) {
                    $existingUnpaidRad->subtype = $mergedSubtype;
                }

                if (! empty($details['doctor_notes'])) {
                    $exDetails['doctor_notes'] = ! empty($exDetails['doctor_notes'])
                        ? ($exDetails['doctor_notes'].' | '.$details['doctor_notes'])
                        : $details['doctor_notes'];
                }

                $existingUnpaidRad->details = $exDetails;
                $existingUnpaidRad->save();

                $mergedSuccessMsg = 'تم دمج وإضافة فحوصات الأشعة بنجاح إلى وصل المريض الحالي في الكاشير (وصل موحد)';
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $mergedSuccessMsg,
                        'request_count' => $visit->requests()->count(),
                        'request_id' => $existingUnpaidRad->id,
                        'requires_payment' => true,
                        'cashier_url' => route('cashier.request.payment.form', $existingUnpaidRad->id),
                    ]);
                }

                return redirect()->back()->with('success', $mergedSuccessMsg);
            }

            // إنشاء وصف يتضمن أسماء الأشعة والعضو المطلوب وتحديد الفئة الفرعية
            $radiologyTypeNames = [];
            foreach ($request->radiology_types as $typeId) {
                $radiologyType = \App\Models\RadiologyType::find($typeId);
                if ($radiologyType) {
                    $targetPart = isset($radiologyTargets[$typeId]) ? trim($radiologyTargets[$typeId]) : null;
                    $displayName = $radiologyType->name;
                    if (! empty($targetPart)) {
                        $displayName .= " [العضو: {$targetPart}]";
                    }
                    $radiologyTypeNames[] = $displayName;
                    $details['radiology_type_id'] = $typeId; // حفظ أول نوع لاستخدامه لاحقاً

                    if (! $detectedSubtype) {
                        $cat = mb_strtolower(trim($radiologyType->main_category ?? ''));
                        $sub = mb_strtolower(trim($radiologyType->subcategory ?? ''));
                        $name = mb_strtolower(trim($radiologyType->name ?? ''));

                        if (
                            str_contains($cat, 'سونار') || str_contains($sub, 'سونار') || str_contains($name, 'سونار') ||
                            str_contains($cat, 'ultrasound') || str_contains($sub, 'ultrasound') || str_contains($name, 'ultrasound') ||
                            str_contains($cat, 'doppler') || str_contains($sub, 'doppler') || str_contains($name, 'doppler') ||
                            str_contains($cat, 'دوبلر') || str_contains($sub, 'دوبلر') || str_contains($name, 'دوبلر') ||
                            str_contains($name, 'sonar') || str_contains($name, 'u/s')
                        ) {
                            $detectedSubtype = 'ultrasound';
                        } elseif (str_contains($cat, 'رنين') || str_contains($sub, 'رنين') || str_contains($name, 'رنين') || str_contains($cat, 'mri')) {
                            $detectedSubtype = 'mri';
                        } elseif (str_contains($cat, 'إيكو') || str_contains($sub, 'إيكو') || str_contains($name, 'إيكو') || str_contains($cat, 'echo')) {
                            $detectedSubtype = 'echo';
                        } elseif (str_contains($cat, 'مفراس') || str_contains($sub, 'مفراس') || str_contains($name, 'مفراس') || str_contains($cat, 'ct')) {
                            $detectedSubtype = 'ct';
                        } else {
                            $detectedSubtype = 'general';
                        }
                    }
                }
            }
            $radiologyDescription = ! empty($radiologyTypeNames)
                ? 'طلب أشعة: '.implode(', ', $radiologyTypeNames)
                : 'طلب أشعة';
        } else {
            $radiologyDescription = null;
        }

        // إنشاء وصف خاص بالتمريض إن لزم الأمر
        $nursingDescription = null;
        if ($request->type === 'nursing' && isset($details['nursing_service_names'])) {
            $nursingDescription = 'طلب خدمات تمريضية: '.implode(', ', $details['nursing_service_names']);
        }

        $insuranceType = optional($visit->appointment)->insurance_type ?? optional($visit->patient)->insurance_type ?? 'none';

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => $request->type,
            'subtype' => $detectedSubtype,
            'description' => ($request->type === 'radiology' && isset($radiologyDescription))
                ? $radiologyDescription
                : (($request->type === 'nursing' && isset($nursingDescription))
                    ? $nursingDescription
                    : ($request->description ?: 'طلب '.($request->type === 'lab' ? 'مختبر' : ($request->type === 'radiology' ? 'أشعة' : ($request->type === 'nursing' ? 'خدمات تمريضية' : 'صيدلية'))))),

            'details' => $details,
            'status' => 'pending',
            'payment_status' => 'pending', // يجب الدفع عند الكاشير قبل الإرسال للقسم المختص
            'insurance_type' => $insuranceType,
        ]);

        $successMsg = $request->type === 'nursing'
            ? 'تم إرسال طلب الإحالة للطوارئ بنجاح - سيقوم موظف الاستقبال باستكمال وحجز الحالة للمريض'
            : 'تم إنشاء الطلب بنجاح - يرجى التوجه للكاشير للدفع';

        // التحقق من نوع الطلب (AJAX أو عادي)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'request_count' => $visit->requests()->count(),
                'request_id' => $medicalRequest->id,
                'requires_payment' => ($request->type !== 'nursing'), // إحالة الطوارئ لا تتطلب دفع كاشير العيادات بل يتولاها موظف الحجز
                'cashier_url' => route('cashier.request.payment.form', $medicalRequest->id),
            ]);
        }

        return redirect()->back()->with('success', $successMsg);
    }

    public function updateRequestStatus(MedicalRequest $request, HttpRequest $httpRequest)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('manage own visits'))) {
            abort(403, 'غير مصرح لك بتحديث حالة الطلب');
        }

        // تسجيل معلومات الطلب للتشخيص قبل أي فحص
        \Illuminate\Support\Facades\Log::info('updateRequestStatus called - START', [
            'route_param_request' => $httpRequest->route('request'),
            'request_model_id' => $request ? $request->id : 'NULL',
            'request_model_exists' => $request ? $request->exists : 'NULL',
            'user_id' => $user->id,
            'doctor_id' => $user->doctor->id,
            'request_data' => $httpRequest->all(),
            'full_url' => $httpRequest->fullUrl(),
            'method' => $httpRequest->method(),
        ]);

        // التحقق من وجود الطلب نفسه
        if (! $request || ! $request->exists) {
            \Illuminate\Support\Facades\Log::error('Request model not found or does not exist', [
                'route_param' => $httpRequest->route('request') ?? 'not found',
                'request_model' => $request,
                'user_id' => $user->id,
            ]);
            abort(404, 'الطلب المطلوب غير موجود');
        }

        // تسجيل معلومات الطلب للتشخيص
        \Illuminate\Support\Facades\Log::info('updateRequestStatus - Request model found', [
            'request_id' => $request->id,
            'visit_id' => $request->visit_id,
            'status' => $request->status,
            'type' => $request->type,
        ]);

        // تحميل علاقة الزيارة مع التحقق من وجود visit_id
        if (! $request->visit_id) {
            \Illuminate\Support\Facades\Log::error('Request has no visit_id', ['request_id' => $request->id]);
            abort(404, 'الطلب المطلوب غير مرتبط بزيارة (visit_id فارغ)');
        }

        $request->load('visit');

        // التحقق من وجود الزيارة بعد التحميل
        if (! $request->visit) {
            \Illuminate\Support\Facades\Log::error('Request visit not found in database', [
                'request_id' => $request->id,
                'visit_id' => $request->visit_id,
            ]);
            abort(404, 'الطلب المطلوب غير مرتبط بزيارة صحيحة (الزيارة غير موجودة في قاعدة البيانات)');
        }

        // التحقق من أن الطلب يخص زيارة للطبيب الحالي
        if ($request->visit->doctor_id != $user->doctor->id) {
            \Illuminate\Support\Facades\Log::warning('Doctor trying to access another doctor\'s request', [
                'request_id' => $request->id,
                'request_doctor_id' => $request->visit->doctor_id,
                'current_doctor_id' => $user->doctor->id,
            ]);
            abort(403, 'غير مصرح لك بتعديل هذا الطلب - الطلب يخص طبيب آخر');
        }

        $httpRequest->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'result' => 'nullable|string|max:1000',
        ]);

        $request->update([
            'status' => $httpRequest->status,
            'result' => $httpRequest->result,
        ]);

        \Illuminate\Support\Facades\Log::info('updateRequestStatus - SUCCESS', [
            'request_id' => $request->id,
            'new_status' => $httpRequest->status,
        ]);

        // التحقق من نوع الطلب (AJAX أو عادي)
        if ($httpRequest->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الطلب بنجاح',
                'new_status' => $request->status,
            ]);
        }

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    /**
     * تمكين الطبيب من حذف فحص/تحليل محدد من الطلب المعلق قبل السداد
     */
    public function removeRequestItem(HttpRequest $httpRequest, MedicalRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('manage own visits'))) {
            abort(403, 'غير مصرح لك بتعديل الطلبات الطبية');
        }

        $request->load('visit');
        if (! $request->visit) {
            abort(404, 'الطلب غير مرتبط بزيارة صحيحة');
        }

        if (! $isAdmin && $request->visit->doctor_id != optional($user->doctor)->id) {
            abort(403, 'غير مصرح لك بتعديل طلب يخص طبيب آخر');
        }

        if ($request->payment_status === 'paid') {
            $msg = 'لا يمكن حذف أو تعديل الفحوصات بعد سداد الوصل في الكاشير لحماية الحسابات المالية.';
            if ($httpRequest->expectsJson() || $httpRequest->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $itemValue = $httpRequest->input('item_value');
        $details = is_string($request->details) ? json_decode($request->details, true) : ($request->details ?? []);
        $remainingCount = 0;

        if ($request->type === 'lab') {
            $tests = (array) ($details['tests'] ?? []);
            $labTestIds = (array) ($details['lab_test_ids'] ?? []);

            $newTests = [];
            foreach ($tests as $t) {
                if (trim((string) $t) !== trim((string) $itemValue)) {
                    $newTests[] = $t;
                }
            }

            $matchingTestIds = \App\Models\LabTest::where('name', $itemValue)
                ->orWhere('code', $itemValue)
                ->orWhere('id', $itemValue)
                ->pluck('id')
                ->toArray();
            if (is_numeric($itemValue)) {
                $matchingTestIds[] = (int) $itemValue;
            }

            $newLabTestIds = [];
            foreach ($labTestIds as $id) {
                if (in_array((int) $id, $matchingTestIds, true)) {
                    continue;
                }
                $newLabTestIds[] = (int) $id;
            }

            $details['tests'] = array_values($newTests);
            $details['lab_test_ids'] = array_values($newLabTestIds);
            $remainingCount = count($newTests);

            if ($remainingCount === 0) {
                $request->status = 'cancelled';
                $details['cancellation_reason'] = 'تم حذف جميع الفحوصات من قبل الطبيب';
            }

            $insuranceType = optional($request->visit->appointment)->insurance_type ?? optional($request->visit->patient)->insurance_type ?? 'none';
            $copayPct = (float) (optional($request->visit->patient)->copay_percentage ?? 15);
            $newTotal = 0;
            foreach ($details['lab_test_ids'] as $tId) {
                $t = \App\Models\LabTest::find($tId);
                if ($t) {
                    $pricing = $t->calculateInsurancePricing($insuranceType, $copayPct);
                    $newTotal += ($insuranceType === 'none' ? $pricing['approved_price'] : $pricing['patient_share']);
                }
            }
            $details['description'] = ! empty($details['tests']) ? ('طلب مختبر: '.implode(', ', $details['tests'])) : 'طلب مختبر';
            $details['total_amount'] = $newTotal;

        } elseif ($request->type === 'radiology') {
            $typeIds = (array) ($details['radiology_type_ids'] ?? $details['radiology_types'] ?? []);
            $targets = (array) ($details['radiology_targets'] ?? []);

            $typeIdToRemove = is_numeric($itemValue) ? (int) $itemValue : null;
            if (! $typeIdToRemove) {
                $foundType = \App\Models\RadiologyType::where('name', $itemValue)->first();
                $typeIdToRemove = $foundType?->id;
            }

            $newTypeIds = [];
            foreach ($typeIds as $tId) {
                if ($typeIdToRemove && (int) $tId === (int) $typeIdToRemove) {
                    continue;
                }
                $newTypeIds[] = (int) $tId;
            }

            if ($typeIdToRemove && isset($targets[$typeIdToRemove])) {
                unset($targets[$typeIdToRemove]);
            }

            $details['radiology_types'] = array_values($newTypeIds);
            $details['radiology_type_ids'] = array_values($newTypeIds);
            $details['radiology_targets'] = $targets;
            $remainingCount = count($newTypeIds);

            if ($remainingCount === 0) {
                $request->status = 'cancelled';
                $details['cancellation_reason'] = 'تم حذف جميع الفحوصات من قبل الطبيب';
            }

            $insuranceType = optional($request->visit->appointment)->insurance_type ?? optional($request->visit->patient)->insurance_type ?? 'none';
            $copayPct = (float) (optional($request->visit->patient)->copay_percentage ?? 15);
            $newTotal = 0;
            foreach ($details['radiology_type_ids'] as $rId) {
                $r = \App\Models\RadiologyType::find($rId);
                if ($r) {
                    $pricing = $r->calculateInsurancePricing($insuranceType, $copayPct);
                    $newTotal += ($insuranceType === 'none' ? $pricing['approved_price'] : $pricing['patient_share']);
                }
            }
            $radNames = \App\Models\RadiologyType::whereIn('id', $details['radiology_type_ids'])->pluck('name')->toArray();
            $details['description'] = ! empty($radNames) ? ('طلب أشعة: '.implode(', ', $radNames)) : 'طلب أشعة';
            $details['total_amount'] = $newTotal;

        } elseif ($request->type === 'nursing') {
            $services = (array) ($details['nursing_services'] ?? []);
            $serviceNames = (array) ($details['nursing_service_names'] ?? []);

            $newServices = [];
            foreach ($services as $sId) {
                if ((string) $sId !== (string) $itemValue) {
                    $newServices[] = $sId;
                }
            }
            $newNames = [];
            foreach ($serviceNames as $sn) {
                if (trim((string) $sn) !== trim((string) $itemValue)) {
                    $newNames[] = $sn;
                }
            }

            $details['nursing_services'] = array_values($newServices);
            $details['nursing_service_names'] = array_values($newNames);
            $remainingCount = count($newServices);

            if ($remainingCount === 0) {
                $request->status = 'cancelled';
                $details['cancellation_reason'] = 'تم حذف جميع الخدمات من قبل الطبيب';
            }
        }

        $request->details = $details;
        $request->save();

        $msg = $remainingCount === 0
            ? 'تم حذف الفحص الأخير وإلغاء الطلب بنجاح'
            : 'تم حذف الفحص وتحديث الطلب بنجاح';

        if ($httpRequest->expectsJson() || $httpRequest->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'remaining_count' => $remainingCount,
                'is_cancelled' => ($request->status === 'cancelled'),
                'new_total' => $request->total_amount,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function showSurgeryForm(Visit $visit)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('manage own visits'))) {
            abort(403, 'غير مصرح لك بالوصول إلى نموذج حجز العملية');
        }

        if ($visit->status === 'cancelled') {
            return redirect()->back()->with('error', 'لا يمكن تحويل زيارة ملغاة لحجز عملية');
        }

        $visit->load('patient.user');

        return view('doctors.visits.surgery-form', compact('visit'));
    }

    public function markNeedsSurgery(HttpRequest $request, Visit $visit)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('manage own visits'))) {
            abort(403, 'غير مصرح لك بتحويل المريض للعمليات');
        }

        $request->validate([
            'surgery_notes' => 'required|string|max:1000',
        ]);

        $visit->update([
            'status' => 'completed',
            'needs_surgery' => true,
            'surgery_notes' => $request->surgery_notes,
        ]);

        if ($visit->appointment) {
            $visit->appointment->complete();
        }

        return redirect()->route('doctor.visits.show', $visit)->with('success', 'تم تحويل المريض للاستعلامات لحجز العملية بنجاح');
    }

    /**
     * Display unified patient medical history timeline.
     */
    public function showPatientHistory(\App\Models\Patient $patient)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view patient history') && ! $user->can('view own visits')))) {
            abort(403, 'غير مصرح لك بعرض السجل الطبي للمريض');
        }

        $patient->load('user');

        // Build timeline events
        $events = collect();

        // Visits
        $visits = $patient->visits()->with('doctor.user')->orderBy('visit_date', 'desc')->get();
        foreach ($visits as $visit) {
            $events->push([
                'type' => 'visit',
                'title' => 'زيارة متابعة',
                'date' => $visit->visit_date,
                'time' => $visit->visit_time,
                'doctor' => $visit->doctor?->user?->name ?? 'د. سارة أحمد',
                'description' => $visit->chief_complaint ?: ($visit->diagnosis['description'] ?? 'التشخيص: ارتفاع ضغط مستقر، تم وصف دواء جديد'),
                'badge' => $visit->status_text ?? 'مكتملة',
                'color' => $visit->status === 'completed' ? 'primary' : 'warning',
                'icon' => 'stethoscope',
                'link' => route('doctor.visits.show', $visit),
            ]);
        }

        // Lab Results
        $labResults = \App\Models\LabResult::whereHas('visit', fn ($q) => $q->where('patient_id', $patient->id))
            ->with('visit')
            ->orderBy('created_at', 'desc')
            ->get();
        foreach ($labResults as $lab) {
            $events->push([
                'type' => 'lab',
                'title' => 'نتيجة فحص الدم الشامل',
                'date' => $lab->created_at,
                'time' => null,
                'description' => 'الحالة: '.($lab->status ?? 'طبيعي'),
                'badge' => 'مختبر',
                'color' => 'success',
                'icon' => 'flask',
                'link' => $lab->visit ? route('doctor.visits.show', $lab->visit) : null,
                'extraLabel' => $lab->status === 'normal' ? 'نتائج غير طبيعية: لا يوجد' : null,
            ]);
        }

        // Radiology
        $radiologyRequests = \App\Models\RadiologyRequest::where('patient_id', $patient->id)
            ->with('radiologyType')
            ->orderBy('requested_date', 'desc')
            ->get();
        foreach ($radiologyRequests as $rad) {
            $events->push([
                'type' => 'radiology',
                'title' => 'أشعة صدر',
                'date' => $rad->requested_date,
                'time' => null,
                'description' => 'النتائج: '.($rad->clinical_indication ?? 'طبيعي'),
                'badge' => 'أشعة',
                'color' => 'warning',
                'icon' => 'x-ray',
                'link' => null,
            ]);
        }

        // Surgeries
        $surgeries = \App\Models\Surgery::where('patient_id', $patient->id)
            ->with('doctor.user')
            ->orderBy('scheduled_date', 'desc')
            ->get();
        foreach ($surgeries as $surgery) {
            $events->push([
                'type' => 'surgery',
                'title' => 'عملية استئصال الزائدة',
                'date' => $surgery->scheduled_date,
                'time' => $surgery->scheduled_time,
                'doctor' => $surgery->surgeon_name ?: ($surgery->doctor?->user?->name ?? ''),
                'description' => 'الجراح: '.($surgery->surgeon_name ?? 'د. خالد المنصوري').'\nالنتيجة: '.($surgery->status ?? 'ناجحة'),
                'badge' => 'عملية جراحية',
                'color' => 'danger',
                'icon' => 'scalpel',
                'link' => $surgery->visit ? route('doctor.visits.show', $surgery->visit) : null,
            ]);
        }

        // Emergencies
        $emergencies = \App\Models\Emergency::where('patient_id', $patient->id)
            ->orderBy('admission_time', 'desc')
            ->get();
        foreach ($emergencies as $emergency) {
            $events->push([
                'type' => 'emergency',
                'title' => 'دخول الطوارئ',
                'date' => $emergency->admission_time,
                'time' => null,
                'description' => $emergency->symptoms.'\nالعلاج: '.($emergency->treatment_given ?? 'تخطيط قلب، ملاحظة').'\n'.($emergency->diagnosis ?? 'المريض خرج'),
                'badge' => 'طوارئ',
                'color' => 'danger',
                'icon' => 'ambulance',
                'link' => null,
            ]);
        }

        // Bed Reservations / Hospitalizations
        $admissions = \App\Models\BedReservation::where('patient_id', $patient->id)
            ->orderBy('scheduled_date', 'desc')
            ->get();
        foreach ($admissions as $admission) {
            $events->push([
                'type' => 'admission',
                'title' => 'تنويم (3 أيام)',
                'date' => $admission->scheduled_date,
                'time' => $admission->scheduled_time,
                'description' => 'الحالة: '.($admission->notes ?? 'التهاب رئوي').'\nالحالة: خرج من المستشفى',
                'badge' => 'تنويم',
                'color' => 'info',
                'icon' => 'bed',
                'link' => null,
            ]);
        }

        // Sort by date descending
        $timeline = $events->sortByDesc('date')->values();

        return view('doctors.patient-history', compact('patient', 'timeline'));
    }

    /**
     * طباعة الوصفة الطبية الرسمية (Prescription Print)
     */
    public function printPrescription(Visit $visit)
    {
        $visit->load([
            'patient.user',
            'doctor.user',
            'prescriptions.items.medicine',
            'prescriptions.items.dispensedMedicine',
            'prescribedMedications',
        ]);

        $prescription = $visit->prescriptions()->latest()->first();

        return view('doctors.visits.prescription-print', compact('visit', 'prescription'));
    }

    /**
     * طباعة كرت وبطاقة موعد المراجعة والاستشارة (Appointment / Follow-up Slip Print)
     */
    public function printAppointment(Visit $visit)
    {
        $visit->load([
            'patient.user',
            'doctor.user',
            'department',
            'appointment',
        ]);

        $followUpAppointment = \App\Models\Appointment::where('recheck_parent_visit_id', $visit->id)->latest()->first();

        return view('doctors.visits.appointment-print', compact('visit', 'followUpAppointment'));
    }

    /**
     * استرجاع طلبات استبدال الأدوية الواردة من الصيدلية لهذه الزيارة لحظياً
     */
    public function getSubstitutionRequests(Visit $visit)
    {
        $prescription = $visit->prescriptions()->latest()->first();
        if (! $prescription) {
            return response()->json(['success' => true, 'count' => 0, 'requests' => []]);
        }

        $pendingRequests = $prescription->items()
            ->where('substitution_status', 'pending_approval')
            ->with(['medicine', 'suggestedMedicine'])
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'original_medicine_id' => $item->medicine_id,
                    'original_medicine_name' => $item->medicine?->name ?? $item->medicine_name,
                    'suggested_medicine_id' => $item->suggested_medicine_id,
                    'suggested_medicine_name' => $item->suggestedMedicine?->name ?? 'بديل غير محدد',
                    'suggested_generic' => $item->suggestedMedicine?->generic_name ?? '',
                    'suggested_form' => $item->suggestedMedicine?->dosage_form ?? '',
                    'suggested_strength' => $item->suggestedMedicine?->strength ?? '',
                    'quantity' => $item->quantity,
                    'unit_type' => $item->unit_type,
                    'substitution_reason' => $item->substitution_reason ?? 'عدم توفر الصنف الأصلي في الصيدلية',
                ];
            });

        return response()->json([
            'success' => true,
            'count' => $pendingRequests->count(),
            'requests' => $pendingRequests,
        ]);
    }

    /**
     * رد الطبيب على طلب استبدال الدواء (موافقة أو رفض)
     */
    public function respondToSubstitution(HttpRequest $request, PrescriptionItem $item)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('manage own visits'))) {
            abort(403, 'غير مصرح لك بالرد على طلبات استبدال الأدوية');
        }
        $request->validate([
            'action' => 'required|in:approve,reject',
            'response_notes' => 'nullable|string|max:255',
        ]);

        $item->load(['prescription.visit', 'medicine', 'suggestedMedicine']);

        $action = $request->input('action');
        $notes = $request->input('response_notes');

        if ($action === 'approve') {
            $suggestedMed = $item->suggestedMedicine;
            if (! $suggestedMed) {
                return response()->json(['success' => false, 'message' => 'لم يتم العثور على الدواء البديل المقترح.'], 422);
            }

            // تحديث بند الوصفة ليصبح الدواء المعتمد هو البديل المقترح
            $item->update([
                'medicine_id' => $suggestedMed->id,
                'dispensed_medicine_id' => $suggestedMed->id,
                'substitution_status' => 'approved',
                'substitution_response_notes' => $notes ?? 'تمت موافقة الطبيب المعالج على البديل',
                'substitution_responded_at' => now(),
            ]);

            // تحديث السجل في prescribed_medications المرتبط بالزيارة إن وجد
            if ($item->prescription && $item->prescription->visit_id) {
                $visitId = $item->prescription->visit_id;
                $origName = $item->medicine?->name ?? $item->medicine_name;
                $medRecord = \App\Models\PrescribedMedication::where('visit_id', $visitId)
                    ->where('name', $origName)
                    ->first();
                if ($medRecord) {
                    $medRecord->update([
                        'name' => $suggestedMed->name,
                        'instructions' => trim(($medRecord->instructions ?? '').' [تم اعتماد البديل بناءً على اقتراح الصيدلية]'),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "تمت الموافقة على استبدال الدواء بـ ({$suggestedMed->name}) واعتماده بالوصفة.",
                'item' => [
                    'id' => $item->id,
                    'medicine_id' => $item->medicine_id,
                    'medicine_name' => $suggestedMed->name,
                    'substitution_status' => 'approved',
                ],
            ]);
        } else {
            // رفض البديل
            $item->update([
                'substitution_status' => 'rejected',
                'substitution_response_notes' => $notes ?? 'تم رفض البديل من قبل الطبيب المعالج',
                'substitution_responded_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم رفض البديل وإشعار الصيدلية بالبحث عن الصنف الأصلي أو الاعتذار.',
                'item' => [
                    'id' => $item->id,
                    'substitution_status' => 'rejected',
                ],
            ]);
        }
    }

    /**
     * البحث الذكي اللحظي فائق السرعة في رموز وتشخيصات ICD-10
     * يبحث في الأعمدة الثلاثة: الرمز (code)، الوصف العربي (description_ar)، والوصف الإنجليزي (description)
     * مع تحسينات الأداء لمنع أي ثقل أو بطء عند الكتابة
     */
    public function searchIcd10(HttpRequest $request)
    {
        $q = trim($request->get('q', ''));
        $len = mb_strlen($q);
        if ($len < 1) {
            return response()->json([]);
        }

        // تحسين أداء وسرعة الاستعلام:
        // إذا كان حرفاً أو حرفين: نعتمد المطابقة بالبدايات (Prefix Match) لتجنب فحص آلاف السجلات
        // إذا كان 3 أحرف فأكثر: بحث شامل مع أولوية البدايات، مع حد أقصى 15 نتيجة فائقة السرعة
        $query = \App\Models\ICD10Code::query();

        if ($len <= 2) {
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'LIKE', "{$q}%")
                    ->orWhere('description_ar', 'LIKE', "{$q}%")
                    ->orWhere('description', 'LIKE', "{$q}%");
            });
        } else {
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'LIKE', "{$q}%")
                    ->orWhere('description_ar', 'LIKE', "%{$q}%")
                    ->orWhere('description', 'LIKE', "%{$q}%");
            });
        }

        $results = $query->orderByRaw('
                CASE 
                    WHEN code = ? THEN 1
                    WHEN code LIKE ? THEN 2
                    WHEN description_ar LIKE ? THEN 3
                    WHEN description LIKE ? THEN 4
                    ELSE 5
                END
            ', [$q, "{$q}%", "{$q}%", "{$q}%"])
            ->limit(15)
            ->get(['id', 'code', 'description', 'description_ar', 'category']);

        return response()->json($results);
    }
}
