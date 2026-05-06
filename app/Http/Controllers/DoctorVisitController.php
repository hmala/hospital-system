<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Request as MedicalRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Notification as AppNotification;
use App\Models\PrescribedMedication;
use App\Models\User;
use App\Models\UserLabTestGroup;
use App\Models\UserLabTestStat;
use App\Notifications\VisitCancelledByDoctorNotification;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class DoctorVisitController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor'])) {
            $todayVisits = collect();
            $upcomingVisits = collect();
            $appointments = collect();
            return view('doctors.visits.index', compact('todayVisits', 'upcomingVisits', 'appointments'))->with('error', 'يجب أن تكون طبيباً للوصول إلى هذه الصفحة');
        }

        $doctor = $user->doctor;
        if (!$doctor) {
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
        $todayVisits = $allVisits->filter(function($visit) {
            return $visit->visit_date && $visit->visit_date->isToday();
        });
        
        $upcomingVisits = $allVisits->filter(function($visit) {
            return $visit->visit_date && $visit->visit_date->isFuture();
        });
        
        $completedVisits = $allVisits->filter(function($visit) {
            return $visit->status == 'completed' && $visit->visit_date && $visit->visit_date->isPast();
        });
        
        $incompleteVisits = $allVisits->filter(function($visit) {
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
        $doctorRequests = MedicalRequest::whereHas('visit', function($query) use ($doctor) {
            $query->where('doctor_id', $doctor->id);
        })
        ->with(['visit.patient.user'])
        ->latest()
        ->limit(50)
        ->get();

        return view('doctors.visits.index', compact('allVisits', 'todayVisits', 'upcomingVisits', 'appointments', 'doctorRequests', 'completedVisits', 'incompleteVisits'));
    }
    public function convertAppointmentToVisit(Appointment $appointment)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات - الأطباء أو موظفي الاستقبال أو موظفي الاستشارية أو admin
        if (!$user->hasRole(['admin', 'doctor', 'receptionist', 'consultation_receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الوظيفة');
        }

        // التحقق من وجود علاقة الطبيب للأطباء
        if ($user->hasRole('doctor') && !$user->doctor) {
            abort(403, 'لم يتم العثور على بيانات الطبيب');
        }

        // التحقق من أن الموعد يخص الطبيب الحالي (للأطباء فقط)
        if ($user->hasRole('doctor') && $appointment->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذا الموعد');
        }

        // التحقق من أن الموعد لم يتم تحويله إلى زيارة بعد
        if ($appointment->visit) {
            return redirect()->back()->with('error', 'هذا الموعد تم تحويله إلى زيارة بالفعل');
        }

        // التحقق من دفع رسوم الكشف قبل إدخال المريض للطبيب
        if ($appointment->payment_status !== 'paid') {
            return redirect()->back()->with('error', 'لا يمكن إدخال المريض للطبيب قبل اكتمال الدفع');
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
            'status' => 'in_progress'
        ]);

        // إذا كان المستخدم موظف استشارية، إرجاعه لصفحته
        if ($user->hasRole('consultation_receptionist')) {
            return redirect()->route('consultant-availability.index')->with('success', 'تم إدخال المريض للطبيب بنجاح');
        }

        return redirect()->route('visits.index', $visit)->with('success', 'تم تحويل الموعد إلى زيارة بنجاح');
    }
    public function show(Visit $visit)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor'])) {
            abort(403, 'يجب أن تكون طبيباً للوصول إلى هذه الصفحة');
        }

        // Admin can view all visits
        if ($user->hasRole('admin')) {
            // continue
        }
        // Doctor can view their own visits OR any visit in read-only mode
        elseif ($user->hasRole('doctor')) {
            if ($user->doctor && $visit->doctor_id !== $user->doctor->id) {
                // This is NOT this doctor's visit, allow read-only view
                $visit->load([
                    'patient.user', 
                    'appointment', 
                    'requests', 
                    'prescribedMedications' => function($query) {
                        $query->orderBy('created_at', 'desc');
                    }
                ]);

                $prescribedMedications = $visit->prescribedMedications()->where('item_type', 'medication')->get();
                $otherTreatments = $visit->prescribedMedications()->where('item_type', 'treatment')->get();
                $hasCompletedRequests = $visit->requests()->where('status', 'completed')->exists();
                $hasPendingRequests = $visit->requests()->where('status', 'pending')->exists();
                $labTests = \App\Models\LabTest::where('is_active', true)->get();
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

        $visit->load([
            'patient.user', 
            'appointment', 
            'requests', 
            'prescribedMedications' => function($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        // Get prescribed medications and separate them into medications and other treatments
        $prescribedMedications = $visit->prescribedMedications()->where('item_type', 'medication')->get();
        $otherTreatments = $visit->prescribedMedications()->where('item_type', 'treatment')->get();

        // Log the data for debugging
        \Illuminate\Support\Facades\Log::info('Visit Medications Data:', [
            'visit_id' => $visit->id,
            'prescribed_count' => $prescribedMedications->count(),
            'treatments_count' => $otherTreatments->count(),
            'medications' => $prescribedMedications->toArray(),
            'treatments' => $otherTreatments->toArray()
        ]);

        // Check if there are any completed requests
        $hasCompletedRequests = $visit->requests()->where('status', 'completed')->exists();
        $hasPendingRequests = $visit->requests()->where('status', 'pending')->exists();

        $labTests = \App\Models\LabTest::where('is_active', true)->get();
        $labTestGroups = UserLabTestGroup::with('labTests')->where('user_id', $user->id)->get();
        $favoriteLabTests = UserLabTestStat::getFavoritesForUser($user->id);

        // Log lab tests count
        \Illuminate\Support\Facades\Log::info('Lab Tests Count: ' . $labTests->count());

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
                'Injuries involving multiple body regions'
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
                'Symptoms, Signs & Abnormal Clinical & Laboratory Findings ,not Elsewhere Classified'
            ],
            'قلبية وعائية' => [
                'Diseases of Circulatory system'
            ],
            'صدرية' => [
                'Diseases of the Respiratory system',
                'Certain Infectious and Parasitic Diseases(A00-B99)'
            ],
            'هضمية' => [
                'Diseases of the Digestive system'
            ],
            'غدد صماء' => [
                'Endocrine, Nutritional and Metabolic Diseases'
            ],
            'كلى' => [
                'Diseases of the Genitourinary system'
            ],
            'روماتيزم' => [
                'Diseases of the Musculoskletal system&connective tissue'
            ],

            // تخصصات جراحية
            'جراحة عامة' => [
                'Injury , poisoning and Certain other Consequences of External causes',
                'Diseases of the Digestive system',
                'Malignant Neoplasms',
                'In situ Neoplasms and Benign Neoplasms with Blood Diseases'
            ],
            'جراحة عظام' => [
                'Diseases of the Musculoskletal system&connective tissue',
                'Injury , poisoning and Certain other Consequences of External causes'
            ],
            'جراحة عصبية' => [
                'Diseases of the Nervous system',
                'Injury , poisoning and Certain other Consequences of External causes'
            ],
            'جراحة قلبية' => [
                'Diseases of Circulatory system'
            ],
            'جراحة صدرية' => [
                'Diseases of the Respiratory system'
            ],
            'جراحة تجميلية' => [
                'Diseases of the skin & Subcutaneous tissue',
                'Injury , poisoning and Certain other Consequences of External causes'
            ],

            // تخصصات نسائية وأطفال
            'نسائية وتوليد' => [
                'Pregnancy , Childbirth and the Peurperium',
                'Diseases of the Genitourinary system'
            ],
            'أطفال' => [
                'Certain conditions originating in the Perinatal period',
                'Certain Infectious and Parasitic Diseases(A00-B99)',
                'Endocrine, Nutritional and Metabolic Diseases'
            ],
            'حديثي الولادة' => [
                'Certain conditions originating in the Perinatal period'
            ],

            // تخصصات أخرى
            'جلدية' => [
                'Diseases of the skin & Subcutaneous tissue'
            ],
            'عيون' => [
                'Diseases of the Eye & Adnexa with Ear & Mastoid process Dis.'
            ],
            'أنف وأذن وحنجرة' => [
                'Diseases of the Eye & Adnexa with Ear & Mastoid process Dis.',
                'Diseases of the Respiratory system'
            ],
            'أسنان' => [
                'Diseases of the Digestive system' // تقريباً لأمراض الفم والأسنان
            ],
            'نفسية وعصبية' => [
                'Mental and Behavioural Disorders',
                'Diseases of the Nervous system'
            ],
            'طب نفسي' => [
                'Mental and Behavioural Disorders'
            ],
            'جملة عصبية' => [
                'Diseases of the Nervous system',
                'Mental and Behavioural Disorders',
                'العدوى الفيروسية الأخرى في الجهاز العصبي المركزي، غير مصنفة في مكان آخر'
            ],

            // تخصصات متخصصة
            'أورام' => [
                'Malignant Neoplasms',
                'In situ Neoplasms and Benign Neoplasms with Blood Diseases'
            ],
            'أشعة' => [
                'Symptoms, Signs & Abnormal Clinical & Laboratory Findings ,not Elsewhere Classified'
            ],
            'مختبر' => [
                'Symptoms, Signs & Abnormal Clinical & Laboratory Findings ,not Elsewhere Classified'
            ],
            'تخدير' => [
                'Factors influencing health status & contact with health services'
            ],
            'طب رياضي' => [
                'Diseases of the Musculoskletal system&connective tissue',
                'Injury , poisoning and Certain other Consequences of External causes'
            ],
            'طب الشيخوخة' => [
                'Diseases of Circulatory system',
                'Diseases of the Musculoskletal system&connective tissue',
                'Mental and Behavioural Disorders'
            ],
            'طب الطب الشرعي' => [
                'External causes of Morbidity & Mortality',
                'Injury , poisoning and Certain other Consequences of External causes'
            ],
            'صحة عامة' => [
                'Certain Infectious and Parasitic Diseases(A00-B99)',
                'Factors influencing health status & contact with health services'
            ],
            'طب الطيران' => [
                'Diseases of the Respiratory system',
                'Factors influencing health status & contact with health services'
            ],
            'طب الغوص' => [
                'Diseases of the Respiratory system',
                'Injury , poisoning and Certain other Consequences of External causes'
            ]
        ];

        if ($doctorSpecialization && isset($specializationCategories[$doctorSpecialization]) && !empty($specializationCategories[$doctorSpecialization])) {
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
            ->whereJsonContains('working_days', [$currentDay])
            ->where('id', '<>', $visit->doctor_id)
            ->orderBy('specialization')
            ->orderBy('id')
            ->get();

        return view('doctors.visits.show', compact(
            'visit',
            'labTests',
            'labTestGroups',
            'favoriteLabTests',
            'radiologyTypes',
            'icd10Codes',
            'prescribedMedications',
            'otherTreatments',
            'hasCompletedRequests',
            'hasPendingRequests',
            'availableDoctors',
            'emergencyServices'
        ));
    }
    public function update(HttpRequest $request, Visit $visit)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor'])) {
            abort(403, 'يجب أن تكون طبيباً للوصول إلى هذه الصفحة');
        }

        // التحقق من وجود علاقة الطبيب
        if (!$user->doctor) {
            abort(403, 'لم يتم العثور على بيانات الطبيب');
        }

        // التحقق من أن الزيارة تخص الطبيب الحالي
        if ($visit->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بتعديل هذه الزيارة');
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
            $rules['diagnosis.description'] = 'required|string|max:1000';
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

        $request->validate($rules);

        // إذا كان هناك تشخيص واختير "أخرى"، تأكد من وجود custom_code
        if ($request->has('diagnosis') && $request->diagnosis['code'] === 'other' && empty($request->diagnosis['custom_code'])) {
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
            $updateData['diagnosis'] = $request->diagnosis;
        }

        if ($request->has('treatment_plan')) {
            $updateData['treatment_plan'] = $request->treatment_plan;
        }

        if ($request->has('notes')) {
            $updateData['notes'] = $request->notes;
        }

        if ($request->has('status')) {
            $updateData['status'] = $request->status;
        }

        if ($request->has('prescribed_medications')) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($visit, $request) {
                // حذف الأدوية والعلاجات القديمة
                $visit->prescribedMedications()->delete();

                // حفظ الأدوية العادية
                if (isset($request->prescribed_medications) && is_array($request->prescribed_medications)) {
                    foreach ($request->prescribed_medications as $key => $item) {
                        if (is_numeric($key) && isset($item['name'])) {
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
        }

        // التحقق من نوع الطلب (AJAX أو عادي)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ البيانات بنجاح',
                'visit_status' => $visit->status
            ]);
        }

        return redirect()->back()->with('success', 'تم حفظ البيانات بنجاح');
    }

    public function cancel(Visit $visit)
    {
        $user = Auth::user();
        if (!$user->hasRole(['doctor', 'receptionist', 'admin'])) {
            abort(403, 'غير مصرح لك بإلغاء هذه الزيارة');
        }

        // التحقق من الصلاحيات حسب نوع المستخدم
        if ($user->hasRole('doctor')) {
            // التحقق من وجود علاقة الطبيب
            if (!$user->doctor) {
                abort(403, 'لم يتم العثور على بيانات الطبيب');
            }

            // التحقق من أن الزيارة تخص الطبيب الحالي
            if ($visit->doctor_id !== $user->doctor->id) {
                abort(403, 'غير مصرح لك بإلغاء هذه الزيارة');
            }
        }

        // التحقق من أن الزيارة لم تكتمل بعد
        if ($visit->status === 'completed') {
            return redirect()->back()->with('error', 'لا يمكن إلغاء زيارة مكتملة');
        }

        // إذا كانت الزيارة مرتبطة بحجز تم تحويله إلى زيارة، ألغِ الحجز وأرسل إشعاراً
        if ($visit->appointment) {
            $visit->update(['status' => 'cancelled']);

            $visit->appointment->cancel('تم إلغاء الحجز بعد التحويل من قبل الطبيب ' . optional(Auth::user())->name);

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
                'قام الطبيب ' . optional($visit->doctor)->user->name . ' بإلغاء الحجز المحول للمريض ' . optional($visit->patient)->user->name . '.',
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
                'قام الطبيب ' . optional($visit->doctor)->user->name . ' بإلغاء الحجز المحول للمريض ' . optional($visit->patient)->user->name . '.',
                [
                    'visit_id' => $visit->id,
                    'appointment_id' => optional($visit->appointment)->id,
                    'doctor_name' => optional($visit->doctor)->user->name,
                    'patient_name' => optional($visit->patient)->user->name,
                    'visit_date' => optional($visit->visit_date)->format('Y-m-d'),
                    'visit_time' => optional($visit->visit_time),
                ]
            );

            return redirect()->back()->with('success', 'تم إلغاء الحجز وإرسال إشعار لموظف الاستعلامات والكاشير');
        }

        // حذف الزيارة نهائياً بدلاً من إلغائها
        $visit->delete();

        return redirect()->back()->with('success', 'تم حذف الزيارة بنجاح');
    }

    public function referToDoctor(HttpRequest $request, Visit $visit)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor'])) {
            abort(403, 'غير مصرح لك بإحالة المريض إلى طبيب آخر');
        }

        if ($user->hasRole('doctor') && (!$user->doctor || $visit->doctor_id !== $user->doctor->id)) {
            abort(403, 'غير مصرح لك بإحالة هذا المريض');
        }

        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        $newDoctor = Doctor::find($request->doctor_id);
        if (!$newDoctor || !$newDoctor->is_active || $newDoctor->type !== 'consultant') {
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
                $appointment->payment->description = trim($appointment->payment->description . ' | ' . $note);
                $appointment->payment->notes = trim(($appointment->payment->notes ?? '') . ' | ' . $note, ' | ');
                $appointment->payment->save();
            }

            $appointment->save();
        }

        return redirect()->back()->with('success', 'تم تحويل المريض للطبيب الجديد بنجاح. سيتم احتساب الأتعاب للطبيب الجديد في السجل المالي.');
    }

    public function storeRequest(HttpRequest $request)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor'])) {
            abort(403, 'يجب أن تكون طبيباً للوصول إلى هذه الصفحة');
        }

        // التحقق من وجود علاقة الطبيب
        if (!$user->doctor) {
            abort(403, 'لم يتم العثور على بيانات الطبيب');
        }

        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'type' => 'required|in:lab,radiology,pharmacy,nursing',
            'description' => 'nullable|string|max:1000',
            'priority' => 'nullable|in:normal,urgent,emergency',
            'tests' => 'nullable|array',
            'tests.*' => 'string',
            'radiology_types' => 'nullable|array',
            'radiology_types.*' => 'integer|exists:radiology_types,id',
            'nursing_services' => 'nullable|array',
            'nursing_services.*' => 'integer|exists:emergency_services,id'
        ]);

        $visit = Visit::findOrFail($request->visit_id);

        // التحقق من أن الزيارة تخص الطبيب الحالي
        if ($visit->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بإنشاء طلب لهذه الزيارة');
        }

        $details = [
            'description' => $request->description ?: 'طلب ' . ($request->type === 'lab' ? 'مختبر' : ($request->type === 'radiology' ? 'أشعة' : ($request->type === 'nursing' ? 'خدمات تمريضية' : 'صيدلية'))),
            'priority' => $request->priority ?: 'normal'
        ];

        if ($request->type === 'lab' && $request->tests) {
            $details['tests'] = $request->tests;
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
        
        if ($request->type === 'radiology' && $request->radiology_types) {
            $details['radiology_types'] = $request->radiology_types;
            $details['radiology_type_ids'] = $request->radiology_types;
            
            // إنشاء وصف يتضمن أسماء الأشعة
            $radiologyTypeNames = [];
            foreach ($request->radiology_types as $typeId) {
                $radiologyType = \App\Models\RadiologyType::find($typeId);
                if ($radiologyType) {
                    $radiologyTypeNames[] = $radiologyType->name;
                    $details['radiology_type_id'] = $typeId; // حفظ أول نوع لاستخدامه لاحقاً
                }
            }
            $radiologyDescription = !empty($radiologyTypeNames) 
                ? 'طلب أشعة: ' . implode(', ', $radiologyTypeNames)
                : 'طلب أشعة';
        } else {
            $radiologyDescription = null;
        }

        // إنشاء وصف خاص بالتمريض إن لزم الأمر
        $nursingDescription = null;
        if ($request->type === 'nursing' && isset($details['nursing_service_names'])) {
            $nursingDescription = 'طلب خدمات تمريضية: ' . implode(', ', $details['nursing_service_names']);
        }

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => $request->type,
            'description' => ($request->type === 'radiology' && isset($radiologyDescription)) 
                ? $radiologyDescription 
                : (($request->type === 'nursing' && isset($nursingDescription)) 
                    ? $nursingDescription 
                    : ($request->description ?: 'طلب ' . ($request->type === 'lab' ? 'مختبر' : ($request->type === 'radiology' ? 'أشعة' : ($request->type === 'nursing' ? 'خدمات تمريضية' : 'صيدلية'))))),

            'details' => $details,
            'status' => 'pending',
            'payment_status' => 'pending' // يجب الدفع عند الكاشير قبل الإرسال للقسم المختص
        ]);

        // التحقق من نوع الطلب (AJAX أو عادي)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الطلب بنجاح - يرجى التوجه للكاشير للدفع',
                'request_count' => $visit->requests()->count(),
                'request_id' => $medicalRequest->id,
                'requires_payment' => true, // إشارة للواجهة بأن الطلب يحتاج دفع
                'cashier_url' => route('cashier.request.payment.form', $medicalRequest->id)
            ]);
        }

        return redirect()->back()->with('success', 'تم إنشاء الطلب بنجاح - يرجى التوجه للكاشير للدفع');
    }
    public function updateRequestStatus(MedicalRequest $request, HttpRequest $httpRequest)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor'])) {
            abort(403, 'يجب أن تكون طبيباً للوصول إلى هذه الصفحة');
        }

        // التحقق من وجود علاقة الطبيب
        if (!$user->doctor) {
            abort(403, 'لم يتم العثور على بيانات الطبيب');
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
            'method' => $httpRequest->method()
        ]);

        // التحقق من وجود الطلب نفسه
        if (!$request || !$request->exists) {
            \Illuminate\Support\Facades\Log::error('Request model not found or does not exist', [
                'route_param' => $httpRequest->route('request') ?? 'not found',
                'request_model' => $request,
                'user_id' => $user->id
            ]);
            abort(404, 'الطلب المطلوب غير موجود');
        }

        // تسجيل معلومات الطلب للتشخيص
        \Illuminate\Support\Facades\Log::info('updateRequestStatus - Request model found', [
            'request_id' => $request->id,
            'visit_id' => $request->visit_id,
            'status' => $request->status,
            'type' => $request->type
        ]);

        // تحميل علاقة الزيارة مع التحقق من وجود visit_id
        if (!$request->visit_id) {
            \Illuminate\Support\Facades\Log::error('Request has no visit_id', ['request_id' => $request->id]);
            abort(404, 'الطلب المطلوب غير مرتبط بزيارة (visit_id فارغ)');
        }

        $request->load('visit');

        // التحقق من وجود الزيارة بعد التحميل
        if (!$request->visit) {
            \Illuminate\Support\Facades\Log::error('Request visit not found in database', [
                'request_id' => $request->id,
                'visit_id' => $request->visit_id
            ]);
            abort(404, 'الطلب المطلوب غير مرتبط بزيارة صحيحة (الزيارة غير موجودة في قاعدة البيانات)');
        }

        // التحقق من أن الطلب يخص زيارة للطبيب الحالي
        if ($request->visit->doctor_id != $user->doctor->id) {
            \Illuminate\Support\Facades\Log::warning('Doctor trying to access another doctor\'s request', [
                'request_id' => $request->id,
                'request_doctor_id' => $request->visit->doctor_id,
                'current_doctor_id' => $user->doctor->id
            ]);
            abort(403, 'غير مصرح لك بتعديل هذا الطلب - الطلب يخص طبيب آخر');
        }

        $httpRequest->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'result' => 'nullable|string|max:1000'
        ]);

        $request->update([
            'status' => $httpRequest->status,
            'result' => $httpRequest->result
        ]);

        \Illuminate\Support\Facades\Log::info('updateRequestStatus - SUCCESS', [
            'request_id' => $request->id,
            'new_status' => $httpRequest->status
        ]);

        // التحقق من نوع الطلب (AJAX أو عادي)
        if ($httpRequest->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الطلب بنجاح',
                'new_status' => $request->status
            ]);
        }

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    public function showSurgeryForm(Visit $visit)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor'])) {
            abort(403, 'يجب أن تكون طبيباً للوصول إلى هذه الصفحة');
        }

        if (!$user->doctor) {
            abort(403, 'لم يتم العثور على بيانات الطبيب');
        }

        if ($visit->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الزيارة');
        }

        if ($visit->status !== 'completed') {
            return redirect()->back()->with('error', 'يجب إنهاء الزيارة أولاً');
        }

        $visit->load('patient.user');

        return view('doctors.visits.surgery-form', compact('visit'));
    }

    public function markNeedsSurgery(HttpRequest $request, Visit $visit)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor'])) {
            abort(403, 'يجب أن تكون طبيباً للوصول إلى هذه الصفحة');
        }

        if (!$user->doctor) {
            abort(403, 'لم يتم العثور على بيانات الطبيب');
        }

        if ($visit->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بتعديل هذه الزيارة');
        }

        $request->validate([
            'surgery_notes' => 'required|string|max:1000'
        ]);

        $visit->update([
            'needs_surgery' => true,
            'surgery_notes' => $request->surgery_notes
        ]);

        return redirect()->route('doctor.visits.show', $visit)->with('success', 'تم تحويل المريض للاستعلامات لحجز العملية بنجاح');
    }

    /**
     * Display unified patient medical history timeline.
     */
    public function showPatientHistory(\App\Models\Patient $patient)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor'])) {
            abort(403, 'يجب أن تكون طبيباً للوصول إلى هذه الصفحة');
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
        $labResults = \App\Models\LabResult::whereHas('visit', fn($q) => $q->where('patient_id', $patient->id))
            ->with('visit')
            ->orderBy('created_at', 'desc')
            ->get();
        foreach ($labResults as $lab) {
            $events->push([
                'type' => 'lab',
                'title' => 'نتيجة فحص الدم الشامل',
                'date' => $lab->created_at,
                'time' => null,
                'description' => 'الحالة: ' . ($lab->status ?? 'طبيعي'),
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
                'description' => 'النتائج: ' . ($rad->clinical_indication ?? 'طبيعي'),
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
                'description' => 'الجراح: ' . ($surgery->surgeon_name ?? 'د. خالد المنصوري') . '\nالنتيجة: ' . ($surgery->status ?? 'ناجحة'),
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
                'description' => $emergency->symptoms . '\nالعلاج: ' . ($emergency->treatment_given ?? 'تخطيط قلب، ملاحظة') . '\n' . ($emergency->diagnosis ?? 'المريض خرج'),
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
                'description' => 'الحالة: ' . ($admission->notes ?? 'التهاب رئوي') . '\nالحالة: خرج من المستشفى',
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
}
