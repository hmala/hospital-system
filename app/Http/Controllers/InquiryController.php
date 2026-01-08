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
        if (!$user->hasRole(['admin', 'receptionist', 'staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // جلب آخر الزيارات في الاستعلامات (اليوم)
        $todayInquiries = Visit::where('department_id', function($query) {
            $query->select('id')
                  ->from('departments')
                  ->where('name', 'LIKE', '%استعلامات%')
                  ->orWhere('name', 'LIKE', '%استقبال%')
                  ->limit(1);
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

        if (!$user->hasRole(['admin', 'receptionist', 'staff'])) {
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
            ->where('type', 'consultant')
            ->get();

        // جلب أنواع التحاليل والأشعة
        $labTests = LabTest::where('is_active', true)->orderBy('main_category')->orderBy('name')->get();
        $radiologyTypes = RadiologyType::where('is_active', true)->orderBy('main_category')->orderBy('name')->get();

        return view('inquiry.create', compact('patient', 'requestTypes', 'doctors', 'labTests', 'radiologyTypes'));
    }

    /**
     * حفظ الطلب الجديد وإنشاء زيارة في قسم الاستعلامات
     */
    public function store(HttpRequest $httpRequest)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $httpRequest->validate([
            'patient_id' => 'required|exists:patients,id',
            'request_type' => 'required|in:lab,radiology,pharmacy,checkup',
            'description' => 'required_if:request_type,checkup,pharmacy|nullable|string|max:1000',
            'doctor_id' => 'nullable|exists:doctors,id',
            'department_id' => 'nullable|exists:departments,id',
            'appointment_date' => 'nullable|date',
            'lab_test_ids' => 'required_if:request_type,lab|array',
            'lab_test_ids.*' => 'exists:lab_tests,id',
            'radiology_type_ids' => 'required_if:request_type,radiology|array',
            'radiology_type_ids.*' => 'exists:radiology_types,id',
            'auto_refer' => 'nullable|boolean'
        ]);

        $patient = Patient::find($httpRequest->patient_id);
        $requestType = $httpRequest->request_type;

        // ========================================
        // إذا كان النوع "كشف طبي" → إنشاء موعد
        // ========================================
        if ($requestType === 'checkup') {
            // التحقق من البيانات المطلوبة للموعد
            if (!$httpRequest->doctor_id || !$httpRequest->department_id) {
                return redirect()->back()
                    ->with('error', 'يجب تحديد الطبيب والعيادة لحجز موعد الكشف الطبي')
                    ->withInput();
            }

            $doctor = Doctor::find($httpRequest->doctor_id);
            $department = Department::find($httpRequest->department_id);

            // تحديد تاريخ الموعد (إما من النموذج أو اليوم)
            $appointmentDate = $httpRequest->appointment_date ?? Carbon::today();

            // إنشاء موعد
            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'department_id' => $department->id,
                'appointment_date' => Carbon::now(),
                'reason' => $httpRequest->description ?? 'كشف طبي عام',
                'notes' => 'تم الحجز من الاستعلامات - بانتظار الدفع',
                'consultation_fee' => $doctor->consultation_fee ?? $department->consultation_fee ?? 0,
                'duration' => 30,
                'status' => 'scheduled',
                'payment_status' => 'pending' // حالة الدفع: معلق
            ]);

            return redirect()->route('inquiry.index')
                ->with('success', 'تم حجز الموعد بنجاح! رقم الموعد: #' . $appointment->id . ' - المريض: ' . $patient->user->name . '. يرجى توجيه المريض للكاشير.');
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
                return redirect()->back()->with('error', 'لا توجد مستشفيات في النظام. يرجى إضافة مستشفى أولاً.');
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
        
        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $inquiryDept->id,
            'doctor_id' => $httpRequest->doctor_id,
            'visit_date' => Carbon::now(),
            'visit_time' => Carbon::now(),
            'visit_type' => $requestType,
            'chief_complaint' => $description,
            'status' => 'pending_payment', // تعليق الزيارة حتى يتم الدفع في الكاشير
            'notes' => 'طلب من الاستعلامات - نوع: ' . $requestType
        ]);

        // إنشاء الطلب الطبي
        $details = [
            'created_by' => $user->id,
            'created_at_inquiry' => true,
            'auto_refer' => $httpRequest->auto_refer ?? false
        ];
        
        // إضافة تفاصيل التحاليل أو الأشعة إذا كانت موجودة
        if ($requestType === 'lab' && $httpRequest->lab_test_ids) {
            $details['lab_test_ids'] = $httpRequest->lab_test_ids;
        }
        
        if ($requestType === 'radiology' && $httpRequest->radiology_type_ids) {
            $details['radiology_type_ids'] = $httpRequest->radiology_type_ids;
        }
        
        $medicalRequest = Request::create([
            'visit_id' => $visit->id,
            'type' => $requestType,
            'description' => $description,
            'status' => 'pending',
            'payment_status' => 'pending',
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
        $message .= '👤 المريض: <strong>' . $patient->user->name . '</strong><br>';
        
        if ($requestType === 'lab' && isset($details['lab_test_ids'])) {
            $labCount = count($details['lab_test_ids']);
            $message .= "🧪 عدد التحاليل: <strong>{$labCount}</strong><br>";
        }
        
        if ($requestType === 'radiology' && isset($details['radiology_type_ids'])) {
            $radiologyCount = count($details['radiology_type_ids']);
            $message .= "📷 عدد الأشعة: <strong>{$radiologyCount}</strong><br>";
        }
        
        $message .= '<br>💰 <strong>يرجى توجيه المريض للكاشير لدفع الأجور</strong>';

        return redirect()->route('inquiry.index')
            ->with('success', $message);
    }

    /**
     * البحث عن مريض
     */
    public function search()
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'staff'])) {
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

        if (!$user->hasRole(['admin', 'receptionist', 'staff'])) {
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

        if (!$user->hasRole(['admin', 'receptionist', 'staff'])) {
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

        if (!$user->hasRole(['admin', 'receptionist', 'staff'])) {
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

        if (!$user->hasRole(['admin', 'receptionist', 'staff'])) {
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
}
