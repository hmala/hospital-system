<?php

namespace App\Http\Controllers;

use App\Models\Request;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\LabTest;
use App\Models\RadiologyType;
use App\Models\Emergency;
use App\Models\ServiceType;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Inquiry Controller
 * 
 * يدير عملية استقبال المرضى في قسم الاستعلامات
 * ويسمح بإنشاء طلبات جديدة وتحويل المرضى للأقسام المناسبة
 */
class InquiryController extends Controller
{
    /**
     * عرض صفحة الاستعلامات الرئيسية
     */
    public function index()
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'inquiry_staff', 'consultation_receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // جلب آخر الزيارات في الاستعلامات ومصرف الدم (اليوم)
        $todayInquiries = Visit::whereIn('department_id', function($query) {
            $query->select('id')
                  ->from('departments')
                  ->where('name', 'LIKE', '%استعلامات%')
                  ->orWhere('name', 'LIKE', '%استقبال%')
                  ->orWhere('name', 'LIKE', '%مصرف دم%');
        })
        ->whereDate('visit_date', Carbon::today())
        ->with(['patient.user', 'doctor.user'])
        ->latest()
        ->paginate(15);

        return view('inquiry.index', compact('todayInquiries'));
    }

    /**
     * عرض نموذج إنشاء طلب جديد للمريض
     */
    public function create(HttpRequest $httpRequest)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'inquiry_staff', 'consultation_receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // البحث عن المريض
        $patientId = $httpRequest->query('patient_id');
        
        if (!$patientId) {
            return redirect()->route('inquiry.search')->with('error', 'يجب اختيار مريض أولاً');
        }

        $patient = Patient::with('user')->find($patientId);

        if (!$patient || !$patient->user) {
            return redirect()->route('inquiry.search')->with('error', 'المريض غير موجود أو بياناته غير مكتملة');
        }

        // جلب أنواع الخدمات النشطة مع التحقق من الصلاحيات
        $serviceTypes = ServiceType::active()->ordered()->get();

        $requestTypes = [];
        foreach ($serviceTypes as $serviceType) {
            // التحقق من الصلاحية إذا كانت محددة
            if ($serviceType->required_permission && !$user->can($serviceType->required_permission)) {
                continue;
            }

            // تحديد الأقسام بناءً على نوع الخدمة
            $departments = $this->getDepartmentsForServiceType($serviceType->name);

            $requestTypes[$serviceType->name] = [
                'label' => $serviceType->label,
                'icon' => $serviceType->icon,
                'color' => $serviceType->color,
                'departments' => $departments
            ];
        }

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

        $doctors = Doctor::with(['user', 'department'])
            ->whereHas('user', function($query) {
                $query->where('is_active', true);
            })
            ->where('is_active', true)
            ->where('type', 'consultant')
            ->whereJsonContains('working_days', [$todayArabic])
            ->where('is_available_today', true)
            ->orderBy('specialization')
            ->orderBy('user_id')
            ->get();

        // جلب أطباء الطوارئ المتاحين
        $emergencyDoctors = Doctor::with(['user', 'department'])
            ->whereHas('user', function($query) {
                $query->where('is_active', true);
            })
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('specialization', 'LIKE', '%طوارئ%')
                      ->orWhere('specialization', 'LIKE', '%emergency%')
                      ->orWhere('type', 'emergency')
                      ->orWhereHas('department', function($q) {
                          $q->where('name', 'LIKE', '%طوارئ%')
                            ->orWhere('name', 'LIKE', '%emergency%');
                      });
            })
            ->get();

        // جلب أنواع التحاليل والأشعة
        $labTests = LabTest::where('is_active', true)->orderBy('main_category')->orderBy('name')->get();
        $radiologyTypes = RadiologyType::where('is_active', true)->orderBy('main_category')->orderBy('name')->get();

        // قيود خاصة بدور موظف الاستعلامات الاستشارية: يمكنه فقط إنشاء طلب كشف طبي
        $isConsultationReceptionist = $user->hasRole('consultation_receptionist');
        if ($isConsultationReceptionist) {
            $requestTypes = [
                'checkup' => $requestTypes['checkup']
            ];
        }

        return view('inquiry.create', compact('patient', 'requestTypes', 'doctors', 'labTests', 'radiologyTypes', 'emergencyDoctors', 'isConsultationReceptionist'));
    }

    /**
     * حفظ الطلب الجديد وإنشاء زيارة في قسم الاستعلامات
     */
    public function store(HttpRequest $httpRequest)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'inquiry_staff', 'consultation_receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // جلب أنواع الخدمات المتاحة للمستخدم للتحقق من الصلاحيات
        $availableServiceTypes = ServiceType::active()->get()->pluck('name')->toArray();

        $httpRequest->validate([
            'patient_id' => 'required|exists:patients,id',
            'request_type' => 'required|array|min:1',
            'request_type.*' => 'required|in:' . implode(',', $availableServiceTypes),
            'description' => 'nullable|string|max:1000',
            'blood_bank_room_no' => 'nullable|string|max:50',
            'blood_bank_donor_group' => 'nullable|string|max:20',
            'blood_bank_patient_group' => 'nullable|string|max:20',
            'blood_bank_donor_weight' => 'nullable|numeric|min:0',
            'blood_bank_recipient_weight' => 'nullable|numeric|min:0',
            'blood_bank_at_room_temp' => 'nullable|string|max:50',
            'blood_bank_bovine_albumin' => 'nullable|string|max:50',
            'blood_bank_anti_human_globulin' => 'nullable|string|max:50',
            'blood_bank_compatibility' => 'nullable|string|max:50',
            'blood_bank_bottle_no' => 'nullable|string|max:50',
            'blood_bank_operative_date' => 'nullable|date',
            'blood_bank_exp_date' => 'nullable|date',
            'blood_bank_doctor_in_charge' => 'nullable|string|max:100',
            'doctor_id' => 'nullable|exists:doctors,id',
            'department_id' => 'nullable|exists:departments,id',
            'appointment_date' => 'nullable|date',
            'lab_test_ids' => 'nullable|array',  // اختياري - سيحدده موظف المختبر لاحقاً
            'lab_test_ids.*' => 'exists:lab_tests,id',
            'radiology_type_ids' => 'nullable|array',  // اختياري - سيحدده موظف الأشعة لاحقاً
            'radiology_type_ids.*' => 'exists:radiology_types,id',
            'auto_refer' => 'nullable|boolean',
            // حقول الطوارئ
            'emergency_priority' => 'nullable|in:critical,urgent,semi_urgent,non_urgent',
            'emergency_type' => 'nullable|in:trauma,cardiac,respiratory,neurological,poisoning,burns,allergic,pediatric,obstetric,general',
            'emergency_symptoms' => 'nullable|string|max:2000',
            'emergency_doctor_id' => 'nullable|exists:doctors,id',
            'vital_signs' => 'nullable|string|max:500'
        ]);

        $patient = Patient::find($httpRequest->patient_id);
        $requestTypes = $httpRequest->request_type;

        // التحقق من الصلاحيات لكل نوع طلب
        foreach ($requestTypes as $requestType) {
            $serviceType = ServiceType::where('name', $requestType)->first();
            if (!$serviceType || !$serviceType->is_active) {
                abort(403, 'نوع الخدمة غير متاح: ' . $requestType);
            }
            if ($serviceType->required_permission && !$user->can($serviceType->required_permission)) {
                abort(403, 'ليس لديك صلاحية إنشاء طلب من نوع: ' . $serviceType->label);
            }
        }

        // إذا كان المستخدم من موظفي الاستعلامات الاستشارية، فتقيّد الطلبات لتكون كشف طبي فقط
        if ($user->hasRole('consultation_receptionist')) {
            $selectedTypes = collect($requestTypes)->unique()->values();
            if ($selectedTypes->count() !== 1 || $selectedTypes->first() !== 'checkup') {
                abort(403, 'ليس لديك صلاحية إنشاء هذا النوع من الطلبات.');
            }
            $requestTypes = ['checkup'];
        }

        $messages = [];
        $totalRequests = 0;

        // معالجة كل نوع طلب على حدة
        foreach ($requestTypes as $requestType) {
            $totalRequests++;

            // ========================================
            // إذا كان النوع "كشف طبي" → إنشاء موعد
            // ========================================
            if ($requestType === 'checkup') {
                // التحقق من البيانات المطلوبة للموعد
                if (!$httpRequest->doctor_id) {
                    $messages[] = "❌ فشل في إنشاء موعد الكشف الطبي: يجب تحديد الطبيب";
                    continue;
                }

                $doctor = Doctor::find($httpRequest->doctor_id);
                if (!$doctor) {
                    $messages[] = "❌ فشل في إنشاء موعد الكشف الطبي: الطبيب المحدد غير موجود";
                    continue;
                }

                $department = null;
                if ($httpRequest->department_id) {
                    $department = Department::find($httpRequest->department_id);
                }
                if (!$department && $doctor->department_id) {
                    $department = Department::find($doctor->department_id);
                }

                if (!$department) {
                    $messages[] = "❌ فشل في إنشاء موعد الكشف الطبي: لم يتم العثور على العيادة المرتبطة بالطبيب";
                    continue;
                }

                // تحديد تاريخ الموعد (إما من النموذج أو اليوم)
                $appointmentDate = $httpRequest->appointment_date ? Carbon::parse($httpRequest->appointment_date) : Carbon::today();

                // إنشاء موعد
                $appointment = Appointment::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'department_id' => $department->id,
                    'appointment_date' => $appointmentDate,
                    'reason' => $httpRequest->description ?? 'كشف طبي عام',
                    'notes' => 'تم الحجز من الاستعلامات - بانتظار الدفع',
                    'consultation_fee' => $doctor->consultation_fee ?? $department->consultation_fee ?? 0,
                    'duration' => 30,
                    'status' => 'scheduled',
                    'payment_status' => 'pending' // حالة الدفع: معلق
                ]);

                $messages[] = "✅ تم حجز الموعد بنجاح! رقم الموعد: #" . $appointment->id . " - كشف طبي";
                continue;
            }
        // ========================================
        // إذا كان النوع "طوارئ" → إنشاء زيارة طوارئ فورية
        // ========================================
        if ($requestType === 'emergency') {
            // البحث عن قسم الطوارئ
            $emergencyDept = Department::where('name', 'LIKE', '%طوارئ%')
                ->orWhere('name', 'LIKE', '%emergency%')
                ->first();

            if (!$emergencyDept) {
                // إنشاء قسم طوارئ إذا لم يوجد
                $hospital = \App\Models\Hospital::first();
                $emergencyDept = Department::create([
                    'name' => 'الطوارئ',
                    'hospital_id' => $hospital->id ?? 1,
                    'type' => 'emergency',
                    'room_number' => 'ER-001',
                    'consultation_fee' => 50.00, // رسوم طوارئ
                    'working_hours_start' => '00:00:00',
                    'working_hours_end' => '23:59:59',
                    'is_active' => true
                ]);
            }

            // تحديد طبيب الطوارئ
            $emergencyDoctor = null;
            if ($httpRequest->emergency_doctor_id) {
                $emergencyDoctor = Doctor::find($httpRequest->emergency_doctor_id);
            } else {
                // البحث عن طبيب طوارئ متاح تلقائياً
                $emergencyDoctor = Doctor::whereHas('user', function($query) {
                        $query->where('is_active', true);
                    })
                    ->where('is_active', true)
                    ->where('is_available_today', true)
                    ->where(function($query) {
                        $query->where('specialization', 'LIKE', '%طوارئ%')
                              ->orWhere('specialization', 'LIKE', '%emergency%')
                              ->orWhere('type', 'emergency');
                    })
                    ->first();
            }

            // تحديد الأولوية بناءً على تصنيف الحالة
            $priorityWeight = [
                'critical' => 1,    // أولوية عليا
                'urgent' => 2,      // أولوية عالية
                'semi_urgent' => 3, // أولوية متوسطة
                'non_urgent' => 4   // أولوية منخفضة
            ];

            // إنشاء زيارة طوارئ
            $visit = Visit::create([
                'patient_id' => $patient->id,
                'department_id' => $emergencyDept->id,
                'doctor_id' => $emergencyDoctor ? $emergencyDoctor->id : null,
                'visit_date' => Carbon::now(),
                'visit_time' => Carbon::now(),
                'visit_type' => 'emergency',
                'chief_complaint' => $httpRequest->emergency_symptoms,
                'status' => ($httpRequest->emergency_priority === 'critical') ? 'active' : 'pending_payment',
                'priority' => $priorityWeight[$httpRequest->emergency_priority] ?? 4,
                'notes' => "طوارئ - {$httpRequest->emergency_type} - تصنيف: {$httpRequest->emergency_priority}"
            ]);

            // إنشاء طلب طوارئ
            $details = [
                'emergency_priority' => $httpRequest->emergency_priority,
                'emergency_type' => $httpRequest->emergency_type,
                'vital_signs' => $httpRequest->vital_signs,
                'symptoms_description' => $httpRequest->emergency_symptoms,
                'assigned_doctor' => $emergencyDoctor ? $emergencyDoctor->user->name : 'سيتم التخصيص',
                'admission_time' => Carbon::now()->format('H:i:s')
            ];

            $request = Request::create([
                'patient_id' => $patient->id,
                'visit_id' => $visit->id,
                'type' => 'emergency',
                'description' => $httpRequest->emergency_symptoms,
                'status' => ($httpRequest->emergency_priority === 'critical') ? 'approved' : 'pending',
                'priority' => $priorityWeight[$httpRequest->emergency_priority] ?? 4,
                'details' => json_encode($details),
                'requested_by' => $user->id,
                'payment_status' => ($httpRequest->emergency_priority === 'critical') ? 'paid' : 'pending'
            ]);

            // للحالات الحرجة: تسجيل دخول فوري بدون انتظار دفع
            if ($httpRequest->emergency_priority === 'critical') {
                // إنشاء سجل طوارئ مباشرة للحالات الحرجة
                \App\Models\Emergency::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $emergencyDoctor ? $emergencyDoctor->id : null,
                    'priority' => $details['emergency_priority'],
                    'emergency_type' => $details['emergency_type'],
                    'symptoms' => $details['symptoms_description'],
                    'vital_signs' => $details['vital_signs'] ?? [],
                    'admission_time' => now(),
                    'status' => 'waiting',
                    'is_active' => true,
                ]);
                
                $messages[] = "تم تسجيل حالة طوارئ حرجة! رقم الزيارة: #{$visit->id} - سيتم التعامل معها فوراً في قسم الطوارئ.";
            } else {
                $messages[] = "تم تسجيل حالة الطوارئ بنجاح! رقم الطلب: #{$request->id} - يرجى توجيه المريض للكاشير أولاً لتسديد الرسوم ثم التوجه لقسم الطوارئ.";
            }
            continue;
        }

        // ========================================
        // طلب مصرف الدم
        // ========================================
        if ($requestType === 'blood_bank') {
            $inquiryDept = Department::where('name', 'LIKE', '%مصرف دم%')
                ->orWhere('name', 'LIKE', '%دم%')
                ->first();

            if (!$inquiryDept) {
                $hospital = \App\Models\Hospital::first();
                if (!$hospital) {
                    $hospital = \App\Models\Hospital::create([
                        'name' => 'مستشفى افتراضي',
                        'address' => 'غير محدد',
                        'phone' => 'غير محدد',
                        'email' => 'admin@example.com'
                    ]);
                }
                $inquiryDept = Department::create([
                    'name' => 'مصرف الدم',
                    'hospital_id' => $hospital->id ?? 1,
                    'type' => 'laboratory', // لتجنب enum error وقبول النوع الموجود في الجدول
                    'room_number' => 'BB-001',
                    'consultation_fee' => 0.00,
                    'working_hours_start' => '00:00:00',
                    'working_hours_end' => '23:59:59',
                    'is_active' => true,
                ]);
            }

            $visit = Visit::create([
                'patient_id' => $patient->id,
                'department_id' => $inquiryDept->id,
                'doctor_id' => $httpRequest->doctor_id,
                'visit_date' => Carbon::now(),
                'visit_time' => Carbon::now(),
                'visit_type' => 'lab', // استخدم lab لتجنب التحذير في حال enum القديم
                'chief_complaint' => 'طلب مصرف الدم',
                'status' => 'pending_payment',
                'notes' => 'طلب مصرف الدم من الاستعلامات',
            ]);

            $details = [
                'created_by' => $user->id,
                'created_at_inquiry' => true,
                'blood_bank' => true,
                'summary' => 'تم إنشاء طلب مصرف الدم عبر الاستعلامات. التفاصيل ستتم في المختبر.',
                'requested_at' => Carbon::now()->toDateTimeString(),
            ];

            $medicalRequest = Request::create([
                'visit_id' => $visit->id,
                'patient_id' => $patient->id,
                'type' => 'blood_bank',
                'description' => 'طلب مصرف الدم',
                'status' => 'pending',
                'payment_status' => 'pending',
                'details' => json_encode($details),
                'requested_by' => $user->id,
            ]);

            // إنشاء جدول منفصل لتفاصيل مصارف الدم (سعر وبيانات عملية)
            \App\Models\BloodBankRequest::create([
                'request_id' => $medicalRequest->id,
                'visit_id' => $visit->id,
                'patient_id' => $patient->id,
                'department_id' => $inquiryDept->id,
                'doctor_id' => $httpRequest->doctor_id,
                'room_no' => $httpRequest->blood_bank_room_no,
                'donor_group' => $httpRequest->blood_bank_donor_group,
                'patient_group' => $httpRequest->blood_bank_patient_group,
                'donor_weight' => $httpRequest->blood_bank_donor_weight,
                'recipient_weight' => $httpRequest->blood_bank_recipient_weight,
                'at_room_temp' => $httpRequest->blood_bank_at_room_temp,
                'bovine_albumin' => $httpRequest->blood_bank_bovine_albumin,
                'anti_human_globulin' => $httpRequest->blood_bank_anti_human_globulin,
                'compatibility' => $httpRequest->blood_bank_compatibility,
                'bottle_no' => $httpRequest->blood_bank_bottle_no,
                'operative_date' => $httpRequest->blood_bank_operative_date,
                'exp_date' => $httpRequest->blood_bank_exp_date,
                'doctor_in_charge' => $httpRequest->blood_bank_doctor_in_charge,
                'total_amount' => $httpRequest->blood_bank_total_amount ?? 0,
                'status' => 'pending',
                'notes' => $details['summary'] ?? 'طلب مصرف الدم من الاستعلامات',
            ]);

            $messages[] = "✅ تم إنشاء طلب مصرف الدم بنجاح! رقم الطلب: #{$medicalRequest->id} في قسم المختبر";
            continue;
        }

        // ========================================
        // باقي الأنواع (تحاليل، أشعة، صيدلية) → طلب مباشر
        // ========================================
        
        // البحث عن قسم الاستعلامات
        $inquiryDept = Department::where('name', 'LIKE', '%استعلامات%')
            ->orWhere('name', 'LIKE', '%استقبال%')
            ->first();

        if (!$inquiryDept) {
            $hospital = \App\Models\Hospital::first();
            
            if (!$hospital) {
                // إنشاء مستشفى افتراضي إذا لم يوجد
                $hospital = \App\Models\Hospital::create([
                    'name' => 'مستشفى افتراضي',
                    'address' => 'غير محدد',
                    'phone' => 'غير محدد',
                    'email' => 'admin@example.com'
                ]);
            }
            
            $inquiryDept = Department::create([
                'name' => 'الاستعلامات',
                'hospital_id' => $hospital->id,
                'type' => 'other',
                'room_number' => 'Reception-001',
                'consultation_fee' => 0.00,
                'working_hours_start' => '08:00:00',
                'working_hours_end' => '17:00:00',
                'is_active' => true
            ]);
        }

        // إنشاء زيارة في قسم الاستعلامات
        $description = $httpRequest->description ?? 'طلب ' . ($requestType === 'lab' ? 'تحاليل' : ($requestType === 'radiology' ? 'أشعة' : 'خدمة'));
        
        // تحديد حالة الزيارة بناءً على ما إذا تم تحديد الخدمات أم لا
        $hasServices = ($requestType === 'lab' && $httpRequest->lab_test_ids) || 
                       ($requestType === 'radiology' && $httpRequest->radiology_type_ids);
        
        $visitStatus = $hasServices ? 'pending_payment' : 'pending_service_selection';
        
        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $inquiryDept->id,
            'doctor_id' => $httpRequest->doctor_id,
            'visit_date' => Carbon::now(),
            'visit_time' => Carbon::now(),
            'visit_type' => $requestType,
            'chief_complaint' => $description,
            'status' => $visitStatus,
            'notes' => 'طلب من الاستعلامات - نوع: ' . $requestType . ($hasServices ? ' - تم تحديد الخدمات' : ' - بانتظار تحديد الخدمات')
        ]);

        // إنشاء الطلب الطبي
        $details = [
            'created_by' => $user->id,
            'created_at_inquiry' => true,
            'auto_refer' => $httpRequest->auto_refer ?? false,
            'services_selected' => $hasServices  // هل تم تحديد الخدمات؟
        ];
        
        // إضافة تفاصيل التحاليل أو الأشعة إذا كانت موجودة
        if ($requestType === 'lab' && $httpRequest->lab_test_ids) {
            $details['lab_test_ids'] = $httpRequest->lab_test_ids;
        }
        
        if ($requestType === 'radiology' && $httpRequest->radiology_type_ids) {
            $details['radiology_type_ids'] = $httpRequest->radiology_type_ids;
        }
        
        // تحديد الحالة: إذا تم تحديد الخدمات -> pending للدفع، وإلا -> pending_service_selection
        $requestStatus = $hasServices ? 'pending' : 'pending_service_selection';
        
        $medicalRequest = Request::create([
            'visit_id' => $visit->id,
            'type' => $requestType,
            'description' => $description,
            'status' => $requestStatus,
            'payment_status' => $hasServices ? 'pending' : 'not_applicable',
            'details' => json_encode($details)
        ]);

        // رسالة نجاح مفصلة
        $typeArabic = [
            'lab' => 'تحاليل طبية',
            'radiology' => 'أشعة',
            'pharmacy' => 'صيدلية'
        ];
        
        $message = '✅ تم إنشاء طلب ' . ($typeArabic[$requestType] ?? $requestType) . ' بنجاح!<br>';
        $message .= '📋 رقم الطلب: <strong>#' . $medicalRequest->id . '</strong><br>';
        $message .= '👤 المريض: <strong>' . (optional($patient->user)->name ?? 'غير معروف') . '</strong><br>';
        
        if ($requestType === 'lab' && isset($details['lab_test_ids'])) {
            $labCount = count($details['lab_test_ids']);
            $message .= "🧪 عدد التحاليل: <strong>{$labCount}</strong><br>";
        }
        
        if ($requestType === 'radiology' && isset($details['radiology_type_ids'])) {
            $radiologyCount = count($details['radiology_type_ids']);
            $message .= "📷 عدد الأشعة: <strong>{$radiologyCount}</strong><br>";
        }
        
        $message .= '<br>💰 <strong>يرجى توجيه المريض للكاشير لدفع الأجور</strong>';
        
        $messages[] = $message;
        }

        // تجميع جميع الرسائل
        $finalMessage = 'تم إنشاء ' . $totalRequests . ' طلبات بنجاح';

        // إرجاع إلى صفحة الاستعلامات مع الرسالة
        return redirect()->route('inquiry.index')
            ->with('success', $finalMessage);
    }

    /**
     * البحث عن مريض
     */
    public function search()
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'inquiry_staff', 'consultation_receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        return view('inquiry.search');
    }

    /**
     * البحث عن المرضى (AJAX)
     */
    public function searchPatients(HttpRequest $httpRequest)
    {
        $query = $httpRequest->get('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $patients = Patient::with('user')
            ->whereHas('user', function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('phone', 'LIKE', "%{$query}%");
            })
            ->limit(10)
            ->get();

        return response()->json($patients);
    }

    /**
     * عرض تفاصيل الاستعلام
     */
    public function show($id)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'inquiry_staff', 'consultation_receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // البحث عن الزيارة في قسم الاستعلامات
        $inquiryDept = Department::where('name', 'LIKE', '%استعلامات%')
            ->orWhere('name', 'LIKE', '%استقبال%')
            ->first();

        if (!$inquiryDept) {
            abort(404, 'قسم الاستعلامات غير موجود');
        }

        $visit = Visit::where('id', $id)
            ->where('department_id', $inquiryDept->id)
            ->with(['patient.user', 'doctor.user', 'department', 'requests'])
            ->first();

        if (!$visit) {
            abort(404, 'الاستعلام غير موجود');
        }

        return view('inquiry.show', compact('visit'));
    }

    /**
     * عرض نموذج تعديل الاستعلام
     */
    public function edit($id)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'inquiry_staff', 'consultation_receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // البحث عن الزيارة في قسم الاستعلامات
        $inquiryDept = Department::where('name', 'LIKE', '%استعلامات%')
            ->orWhere('name', 'LIKE', '%استقبال%')
            ->first();

        if (!$inquiryDept) {
            abort(404, 'قسم الاستعلامات غير موجود');
        }

        $visit = Visit::where('id', $id)
            ->where('department_id', $inquiryDept->id)
            ->with(['patient.user', 'doctor.user', 'department', 'requests'])
            ->first();

        if (!$visit) {
            abort(404, 'الاستعلام غير موجود');
        }

        // أنواع الطلبات مع الأقسام المناسبة
        $requestTypes = [
            'lab' => [
                'label' => 'تحاليل طبية',
                'icon' => 'flask',
                'color' => 'primary',
                'departments' => Department::where('name', 'LIKE', '%مختبر%')->where('is_active', true)->get()
            ],
            'radiology' => [
                'label' => 'أشعة',
                'icon' => 'x-ray',
                'color' => 'info',
                'departments' => Department::where('name', 'LIKE', '%أشعة%')->orWhere('name', 'LIKE', '%راديولوجي%')->where('is_active', true)->get()
            ],
            'pharmacy' => [
                'label' => 'صيدلية',
                'icon' => 'pills',
                'color' => 'success',
                'departments' => Department::where('name', 'LIKE', '%صيدلية%')->where('is_active', true)->get()
            ],
            'checkup' => [
                'label' => 'كشف طبي',
                'icon' => 'stethoscope',
                'color' => 'warning',
                'departments' => Department::whereNotIn('name', ['مختبر', 'أشعة', 'صيدلية'])->where('is_active', true)->get()
            ]
        ];

        $doctors = Doctor::with(['user', 'department'])
            ->whereHas('user', function($query) {
                $query->where('is_active', true);
            })
            ->where('is_active', true)
            ->get();

        return view('inquiry.edit', compact('visit', 'requestTypes', 'doctors'));
    }

    /**
     * تحديث الاستعلام
     */
    public function update(HttpRequest $httpRequest, $id)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'inquiry_staff', 'consultation_receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $httpRequest->validate([
            'chief_complaint' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'doctor_id' => 'nullable|exists:doctors,id'
        ]);

        // البحث عن الزيارة في قسم الاستعلامات
        $inquiryDept = Department::where('name', 'LIKE', '%استعلامات%')
            ->orWhere('name', 'LIKE', '%استقبال%')
            ->first();

        if (!$inquiryDept) {
            abort(404, 'قسم الاستعلامات غير موجود');
        }

        $visit = Visit::where('id', $id)
            ->where('department_id', $inquiryDept->id)
            ->first();

        if (!$visit) {
            abort(404, 'الاستعلام غير موجود');
        }

        // تحديث الزيارة
        $visit->update([
            'chief_complaint' => $httpRequest->chief_complaint,
            'notes' => $httpRequest->notes,
            'status' => $httpRequest->status,
            'doctor_id' => $httpRequest->doctor_id
        ]);

        return redirect()->route('inquiry.show', $visit->id)
            ->with('success', 'تم تحديث الاستعلام بنجاح!');
    }

    /**
     * حذف الاستعلام
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'inquiry_staff', 'consultation_receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // البحث عن الزيارة في قسم الاستعلامات
        $inquiryDept = Department::where('name', 'LIKE', '%استعلامات%')
            ->orWhere('name', 'LIKE', '%استقبال%')
            ->first();

        if (!$inquiryDept) {
            abort(404, 'قسم الاستعلامات غير موجود');
        }

        $visit = Visit::where('id', $id)
            ->where('department_id', $inquiryDept->id)
            ->first();

        if (!$visit) {
            abort(404, 'الاستعلام غير موجود');
        }

        // حذف الطلبات المرتبطة أولاً
        if ($visit->requests) {
            $visit->requests()->delete();
        }

        // حذف الزيارة
        $visit->delete();

        return redirect()->route('inquiry.index')
            ->with('success', 'تم حذف الاستعلام بنجاح!');
    }

    /**
     * عرض المرضى المقيمين في المستشفى والغرف المحجوزة
     */
    public function occupancy()
    {
        $user = Auth::user();

        // التحقق من الصلاحيات - يمكن للموظفين المختصين بالاستعلامات الوصول
        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'inquiry_staff', 'consultation_receptionist', 'doctor', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // جلب حجوزات الرقود (المؤكدة أو في الانتظار) مع المرضى والغرف
        $bedReservations = \App\Models\BedReservation::with(['patient.user', 'room', 'doctor.user', 'department'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNotNull('room_id')
            ->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->get();

        // جلب العمليات الجراحية التي لها غرف محجوزة
        $surgeries = \App\Models\Surgery::with(['patient.user', 'room', 'doctor.user', 'department'])
            ->whereNotNull('room_id')
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress', 'completed'])
            ->whereNull('discharged_at') // لم يخرج المريض بعد
            ->orderBy('scheduled_date', 'desc')
            ->get();

        // تجميع البيانات حسب الغرفة
        $roomsData = [];
        $allOccupancies = [];
        
        // إضافة حجوزات الرقود المباشر
        foreach ($bedReservations as $reservation) {
            $roomId = $reservation->room_id;
            if (!isset($roomsData[$roomId])) {
                $roomsData[$roomId] = [
                    'room' => $reservation->room,
                    'patients' => []
                ];
            }
            $occupancyData = [
                'type' => 'رقود',
                'type_en' => 'bed_reservation',
                'data' => $reservation,
                'badge_class' => 'bg-info',
                'icon' => 'fa-bed'
            ];
            $roomsData[$roomId]['patients'][] = $occupancyData;
            $allOccupancies[] = $occupancyData;
        }
        
        // إضافة العمليات الجراحية
        foreach ($surgeries as $surgery) {
            $roomId = $surgery->room_id;
            if (!isset($roomsData[$roomId])) {
                $roomsData[$roomId] = [
                    'room' => $surgery->room,
                    'patients' => []
                ];
            }
            $occupancyData = [
                'type' => 'عملية جراحية',
                'type_en' => 'surgery',
                'data' => $surgery,
                'badge_class' => 'bg-danger',
                'icon' => 'fa-procedures'
            ];
            $roomsData[$roomId]['patients'][] = $occupancyData;
            $allOccupancies[] = $occupancyData;
        }

        return view('inquiry.occupancy', compact('roomsData', 'allOccupancies'));
    }

    /**
     * الحصول على الأقسام المناسبة لنوع الخدمة
     */
    private function getDepartmentsForServiceType($serviceTypeName)
    {
        switch ($serviceTypeName) {
            case 'lab':
                return Department::where('name', 'LIKE', '%مختبر%')->where('is_active', true)->orderBy('name')->get();
            case 'radiology':
                return Department::where('name', 'LIKE', '%أشعة%')->orWhere('name', 'LIKE', '%راديولوجي%')->where('is_active', true)->orderBy('name')->get();
            case 'pharmacy':
                return Department::where('name', 'LIKE', '%صيدلية%')->where('is_active', true)->orderBy('name')->get();
            case 'checkup':
                return Department::whereNotIn('name', ['مختبر', 'أشعة', 'صيدلية'])->where('is_active', true)->orderBy('name')->get();
            case 'blood_bank':
                return Department::where('name', 'LIKE', '%مصرف دم%')
                    ->orWhere('name', 'LIKE', '%دم%')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
            default:
                return collect(); // مجموعة فارغة إذا لم يكن نوع معروف
        }
    }
}
