<?php

namespace App\Http\Controllers;

use App\Models\Request;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Appointment;
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
        if (!in_array($user->role, ['receptionist', 'staff'])) {
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

        if (!in_array($user->role, ['receptionist', 'staff'])) {
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
            ->get();

        return view('inquiry.create', compact('patient', 'requestTypes', 'doctors'));
    }

    /**
     * حفظ الطلب الجديد وإنشاء زيارة في قسم الاستعلامات
     */
    public function store(HttpRequest $httpRequest)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['receptionist', 'staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $httpRequest->validate([
            'patient_id' => 'required|exists:patients,id',
            'request_type' => 'required|in:lab,radiology,pharmacy,checkup',
            'description' => 'required|string|max:1000',
            'doctor_id' => 'nullable|exists:doctors,id',
            'department_id' => 'nullable|exists:departments,id',
            'appointment_date' => 'nullable|date',
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
                'notes' => 'تم الحجز من الاستعلامات',
                'consultation_fee' => $doctor->consultation_fee ?? $department->consultation_fee ?? 0,
                'duration' => 30,
                'status' => 'scheduled'
            ]);

            return redirect()->route('appointments.show', $appointment->id)
                ->with('success', 'تم حجز الموعد بنجاح! رقم الموعد: #' . $appointment->id);
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
        $visit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $inquiryDept->id,
            'doctor_id' => $httpRequest->doctor_id,
            'visit_date' => Carbon::now(),
            'visit_time' => Carbon::now(),
            'visit_type' => $requestType,
            'chief_complaint' => $httpRequest->description,
            'status' => 'in_progress',
            'notes' => 'طلب من الاستعلامات - نوع: ' . $requestType
        ]);

        // إنشاء الطلب الطبي
        $medicalRequest = Request::create([
            'visit_id' => $visit->id,
            'type' => $requestType,
            'description' => $httpRequest->description,
            'status' => 'pending',
            'details' => json_encode([
                'created_by' => $user->id,
                'created_at_inquiry' => true,
                'auto_refer' => $httpRequest->auto_refer ?? false
            ])
        ]);

        // إذا كان التحويل التلقائي مفعّلاً
        if ($httpRequest->auto_refer) {
            return redirect()->route('staff.referrals.create', ['request_id' => $medicalRequest->id])
                ->with('success', 'تم إنشاء الطلب بنجاح! جاري التحويل...');
        }

        return redirect()->route('inquiry.index')
            ->with('success', 'تم إنشاء الطلب بنجاح! رقم الطلب: #' . $medicalRequest->id);
    }

    /**
     * البحث عن مريض
     */
    public function search()
    {
        $user = Auth::user();

        if (!in_array($user->role, ['receptionist', 'staff'])) {
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

        if (!in_array($user->role, ['receptionist', 'staff'])) {
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

        if (!in_array($user->role, ['receptionist', 'staff'])) {
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

        if (!in_array($user->role, ['receptionist', 'staff'])) {
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

        if (!in_array($user->role, ['receptionist', 'staff'])) {
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
