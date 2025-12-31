<?php

namespace App\Http\Controllers;

use App\Models\Request;
use App\Models\Visit;
use App\Models\Patient;
use App\Models\Department;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    /**
     * عرض قائمة الاستعلامات المعلقة للتحويل
     */
    public function index()
    {
        $user = Auth::user();

        // التحقق من أن المستخدم موظف استقبال أو موظف طبي أو admin
        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'lab_technician'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // جلب الاستعلامات المعلقة التي تحتاج تحويل
        $pendingRequests = Request::where('status', 'pending')
            ->with(['visit.patient.user', 'visit.doctor.user'])
            ->latest()
            ->paginate(15);

        $departments = Department::where('is_active', true)->get();

        return view('staff.referrals.index', compact('pendingRequests', 'departments'));
    }

    /**
     * عرض نموذج التحويل للاستعلام
     */
    public function create(HttpRequest $httpRequest)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'staff', 'lab_technician'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // الحصول على request_id من query string
        $requestId = $httpRequest->query('request_id');
        
        if (!$requestId) {
            return redirect()->back()->with('error', 'معرف الاستعلام مفقود');
        }

        $medicalRequest = Request::find($requestId);

        if (!$medicalRequest) {
            return redirect()->back()->with('error', 'الاستعلام غير موجود');
        }

        $medicalRequest->load(['visit.patient.user', 'visit.doctor.user']);

        $departments = Department::where('is_active', true)->get();
        $visitTypes = [
            'checkup' => 'كشف دوري',
            'followup' => 'متابعة',
            'emergency' => 'طوارئ',
            'surgery' => 'عملية جراحية',
            'lab' => 'مختبر',
            'radiology' => 'أشعة'
        ];

        return view('staff.referrals.create', compact('medicalRequest', 'departments', 'visitTypes'));
    }

    /**
     * حفظ التحويل وإنشاء زيارة جديدة
     */
    public function store(HttpRequest $httpRequest)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'lab_staff', 'radiology_staff', 'pharmacy_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $httpRequest->validate([
            'request_id' => 'required|exists:requests,id',
            'department_id' => 'required|exists:departments,id',
            'visit_type' => 'required|in:checkup,followup,emergency,surgery,lab,radiology',
            'visit_date' => 'required|date|after:today',
            'notes' => 'nullable|string|max:1000'
        ]);

        $medicalRequest = Request::find($httpRequest->request_id);
        $visit = $medicalRequest->visit;
        $patient = $visit->patient;

        // إنشاء زيارة جديدة للمريض
        $newVisit = Visit::create([
            'patient_id' => $patient->id,
            'department_id' => $httpRequest->department_id,
            'visit_date' => $httpRequest->visit_date,
            'visit_type' => $httpRequest->visit_type,
            'chief_complaint' => $medicalRequest->description,
            'status' => 'pending',
            'notes' => $httpRequest->notes
        ]);

        // تحديث حالة الاستعلام إلى "في التنفيذ"
        $medicalRequest->update(['status' => 'in_progress']);

        return redirect()->route('staff.referrals.index')
            ->with('success', 'تم تحويل المريض بنجاح! الزيارة الجديدة: #' . $newVisit->id);
    }

    /**
     * عرض تفاصيل التحويل
     */
    public function show($requestId)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'receptionist', 'lab_staff', 'radiology_staff', 'pharmacy_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $medicalRequest = Request::with(['visit.patient.user', 'visit.doctor.user', 'visit.department'])
            ->findOrFail($requestId);

        // البحث عن الزيارة الجديدة التي تم إنشاؤها من هذا الاستعلام
        $referralVisit = Visit::where('patient_id', $medicalRequest->visit->patient_id)
            ->where('created_at', '>=', $medicalRequest->updated_at)
            ->where('id', '!=', $medicalRequest->visit_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$referralVisit) {
            $referralVisit = $medicalRequest->visit;
        }

        return view('staff.referrals.show', compact('medicalRequest', 'referralVisit'));
    }
}
