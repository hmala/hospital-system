<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Request as MedicalRequest;
use App\Models\Appointment;
use App\Models\PrescribedMedication;
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
            ->with(['patient.user', 'appointment'])
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

        // التحقق من الصلاحيات - الأطباء أو موظفي الاستقبال أو admin
        if (!$user->hasRole(['admin', 'doctor', 'receptionist'])) {
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

        return redirect()->route('visits.index', $visit)->with('success', 'تم تحويل الموعد إلى زيارة بنجاح');
    }
    public function show(Visit $visit)
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
            abort(403, 'غير مصرح لك بالوصول إلى هذه الزيارة');
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

        return view('doctors.visits.show', compact(
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

        // حذف الزيارة نهائياً بدلاً من إلغائها
        $visit->delete();

        return redirect()->back()->with('success', 'تم حذف الزيارة بنجاح');
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
            'type' => 'required|in:lab,radiology,pharmacy',
            'description' => 'nullable|string|max:1000',
            'priority' => 'nullable|in:normal,urgent,emergency',
            'tests' => 'nullable|array',
            'tests.*' => 'string',
            'radiology_types' => 'nullable|array',
            'radiology_types.*' => 'integer|exists:radiology_types,id'
        ]);

        $visit = Visit::findOrFail($request->visit_id);

        // التحقق من أن الزيارة تخص الطبيب الحالي
        if ($visit->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بإنشاء طلب لهذه الزيارة');
        }

        $details = [
            'description' => $request->description ?: 'طلب ' . ($request->type === 'lab' ? 'مختبر' : ($request->type === 'radiology' ? 'أشعة' : 'صيدلية')),
            'priority' => $request->priority ?: 'normal'
        ];

        if ($request->type === 'lab' && $request->tests) {
            $details['tests'] = $request->tests;
        }
        
        if ($request->type === 'radiology' && $request->radiology_types) {
            $details['radiology_types'] = $request->radiology_types;
            
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

        $medicalRequest = MedicalRequest::create([
            'visit_id' => $visit->id,
            'type' => $request->type,
            'description' => $request->type === 'radiology' && isset($radiologyDescription) 
                ? $radiologyDescription 
                : ($request->description ?: 'طلب ' . ($request->type === 'lab' ? 'مختبر' : ($request->type === 'radiology' ? 'أشعة' : 'صيدلية'))),
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
    public function updateRequestStatus(HttpRequest $request, MedicalRequest $requestModel)
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
            'route_param_request' => $request->route('request'),
            'request_model_id' => $requestModel ? $requestModel->id : 'NULL',
            'request_model_exists' => $requestModel ? $requestModel->exists : 'NULL',
            'user_id' => $user->id,
            'doctor_id' => $user->doctor->id,
            'request_data' => $request->all(),
            'full_url' => $request->fullUrl(),
            'method' => $request->method()
        ]);

        // التحقق من وجود الطلب نفسه
        if (!$requestModel || !$requestModel->exists) {
            \Illuminate\Support\Facades\Log::error('Request model not found or does not exist', [
                'route_param' => $request->route('request') ?? 'not found',
                'request_model' => $requestModel,
                'user_id' => $user->id
            ]);
            abort(404, 'الطلب المطلوب غير موجود');
        }

        // تسجيل معلومات الطلب للتشخيص
        \Illuminate\Support\Facades\Log::info('updateRequestStatus - Request model found', [
            'request_id' => $requestModel->id,
            'visit_id' => $requestModel->visit_id,
            'status' => $requestModel->status,
            'type' => $requestModel->type
        ]);

        // تحميل علاقة الزيارة مع التحقق من وجود visit_id
        if (!$requestModel->visit_id) {
            \Illuminate\Support\Facades\Log::error('Request has no visit_id', ['request_id' => $requestModel->id]);
            abort(404, 'الطلب المطلوب غير مرتبط بزيارة (visit_id فارغ)');
        }

        $requestModel->load('visit');

        // التحقق من وجود الزيارة بعد التحميل
        if (!$requestModel->visit) {
            \Illuminate\Support\Facades\Log::error('Request visit not found in database', [
                'request_id' => $requestModel->id,
                'visit_id' => $requestModel->visit_id
            ]);
            abort(404, 'الطلب المطلوب غير مرتبط بزيارة صحيحة (الزيارة غير موجودة في قاعدة البيانات)');
        }

        // التحقق من أن الطلب يخص زيارة للطبيب الحالي
        if ($requestModel->visit->doctor_id != $user->doctor->id) {
            \Illuminate\Support\Facades\Log::warning('Doctor trying to access another doctor\'s request', [
                'request_id' => $requestModel->id,
                'request_doctor_id' => $requestModel->visit->doctor_id,
                'current_doctor_id' => $user->doctor->id
            ]);
            abort(403, 'غير مصرح لك بتعديل هذا الطلب - الطلب يخص طبيب آخر');
        }

        $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'result' => 'nullable|string|max:1000'
        ]);

        $requestModel->update([
            'status' => $request->status,
            'result' => $request->result
        ]);

        \Illuminate\Support\Facades\Log::info('updateRequestStatus - SUCCESS', [
            'request_id' => $requestModel->id,
            'new_status' => $request->status
        ]);

        // التحقق من نوع الطلب (AJAX أو عادي)
        if ($request->expectsJson()) {
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
}
