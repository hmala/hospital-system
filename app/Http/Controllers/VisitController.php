<?php
// app/Http/Controllers/VisitController.php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Appointment;
use App\Models\Surgery;
use App\Exports\VisitsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    /**
     * Export visits list to Excel.
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('view visits'))) {
            abort(403, 'غير مصرح لك بتصدير الزيارات');
        }

        $fileName = 'سجل_زيارات_المرضى_' . date('Y_m_d') . '.xlsx';
        return Excel::download(new VisitsExport($request), $fileName);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('view visits'))) {
            abort(403, 'غير مصرح لك بعرض الزيارات');
        }

        $query = Visit::with(['patient.user', 'doctor.user', 'department', 'appointment', 'latestPrescription', 'surgery'])
            ->latest('visit_date')
            ->orderBy('visit_time', 'desc');

        // البحث السريع
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('file_number', 'like', "%{$search}%")
                       ->orWhere('national_id', 'like', "%{$search}%")
                       ->orWhereHas('user', function ($uq) use ($search) {
                           $uq->where('name', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%");
                       });
                })
                ->orWhere('chief_complaint', 'like', "%{$search}%");
            });
        }

        // فلترة الطبيب
        if ($request->filled('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }

        // فلترة القسم / العيادة
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // فلترة حالة الزيارة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة نوع الزيارة
        if ($request->filled('visit_type')) {
            $query->where('visit_type', $request->visit_type);
        }

        // فلترة الحاجة لعملية جراحية
        if ($request->filled('needs_surgery')) {
            if ($request->needs_surgery === '1') {
                $query->where('needs_surgery', true);
            } elseif ($request->needs_surgery === '0') {
                $query->where('needs_surgery', false);
            }
        }

        // فلترة التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('visit_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('visit_date', '<=', $request->date_to);
        }

        $visits = $query->paginate(20)->withQueryString();

        // الزيارات التي تحتاج حجز عملية ولم تُحجز بعد
        $needsSurgeryVisits = Visit::with(['patient.user', 'doctor.user', 'department'])
            ->where('needs_surgery', true)
            ->whereDoesntHave('surgery')
            ->latest('updated_at')
            ->get();

        // إحصائيات سريعة للوحة
        $stats = [
            'total' => Visit::count(),
            'today' => Visit::whereDate('visit_date', today())->count(),
            'completed' => Visit::where('status', 'completed')->count(),
            'in_progress' => Visit::where('status', 'in_progress')->count(),
            'needs_surgery' => $needsSurgeryVisits->count(),
        ];

        $doctors = Doctor::with('user')->where('is_active', true)->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('visits.index', compact('visits', 'needsSurgeryVisits', 'stats', 'doctors', 'departments'));
    }

    public function create($patientId = null, $appointmentId = null)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('create visits'))) {
            abort(403, 'غير مصرح لك بإنشاء زيارة');
        }

        $patients = Patient::with('user')->get();
        $doctors = Doctor::with('user')
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('type', '!=', 'consultant')
                      ->orWhere('is_available_today', true);
            })
            ->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $selectedPatient = $patientId ? Patient::find($patientId) : null;
        $appointment = $appointmentId ? Appointment::with(['patient.user', 'doctor.user', 'department'])->find($appointmentId) : null;

        // تحديد القيم الافتراضية
        if ($appointment) {
            // إذا جاء من موعد مسبق
            $defaultDate = $appointment->appointment_date->format('Y-m-d');
            $defaultTime = now()->format('H:i'); // وقت افتراضي بدلاً من وقت الموعد
            $defaultDoctor = $appointment->doctor_id;
            $defaultDepartment = $appointment->department_id;
            $defaultComplaint = $appointment->reason;
        } else {
            // إذا كانت زيارة جديدة
            $defaultDate = now()->format('Y-m-d');
            $defaultTime = now()->format('H:i');
            $defaultDoctor = null;
            $defaultDepartment = null;
            $defaultComplaint = null;
        }

        return view('visits.create', compact(
            'patients', 'doctors', 'departments', 
            'selectedPatient', 'appointment',
            'defaultDate', 'defaultTime', 'defaultDoctor', 'defaultDepartment', 'defaultComplaint'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('create visits'))) {
            abort(403, 'غير مصرح لك بإنشاء زيارة');
        }

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'visit_date' => 'required|date',
            'visit_time' => 'required|date_format:H:i',
            'visit_type' => 'required|in:checkup,followup,emergency,surgery,lab,radiology,blood_bank',
            'chief_complaint' => 'required|string|max:1000',
            'diagnosis' => 'nullable|string|max:1000',
            'treatment' => 'nullable|string|max:1000',
            'prescription' => 'nullable|string|max:1000',
        ]);

        $visitData = [
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'department_id' => $request->department_id,
            'appointment_id' => $request->appointment_id,
            'visit_date' => $request->visit_date,
            'visit_time' => $request->visit_time,
            'visit_type' => $request->visit_type,
            'chief_complaint' => $request->chief_complaint,
            'diagnosis' => $request->diagnosis,
            'treatment' => $request->treatment,
            'prescription' => $request->prescription,
            'notes' => $request->notes,
            'vital_signs' => $request->vital_signs ? (is_string($request->vital_signs) ? json_decode($request->vital_signs) : $request->vital_signs) : null,
            'next_visit_date' => $request->next_visit_date,
            'is_completed' => true
        ];

        // إذا كانت الزيارة مرتبطة بموعد، نقوم بتحديث حالة الموعد
        if ($request->appointment_id) {
            $appointment = Appointment::find($request->appointment_id);
            if ($appointment) {
                $appointment->update(['status' => 'completed']);
            }
        }

        Visit::create($visitData);

        return redirect()->route('visits.index')
            ->with('success', 'تم تسجيل الزيارة بنجاح');
    }

    public function show(Visit $visit)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('view visits'))) {
            abort(403, 'غير مصرح لك بعرض بيانات الزيارة');
        }

        $visit->load(['patient', 'doctor.user', 'department', 'appointment']);
        return view('visits.show', compact('visit'));
    }

    public function edit(Visit $visit)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('edit visits'))) {
            abort(403, 'غير مصرح لك بتعديل بيانات الزيارة');
        }

        $patients = Patient::with('user')->get();
        $doctors = Doctor::with('user')->where('is_active', true)->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('visits.edit', compact('visit', 'patients', 'doctors', 'departments'));
    }

    public function update(Request $request, Visit $visit)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('edit visits'))) {
            abort(403, 'غير مصرح لك بتعديل بيانات الزيارة');
        }

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'visit_date' => 'required|date',
            'visit_time' => 'required|date_format:H:i',
            'visit_type' => 'required|in:checkup,followup,emergency,surgery,lab,radiology,blood_bank',
            'chief_complaint' => 'required|string|max:1000',
            'diagnosis' => 'nullable|string|max:1000',
            'treatment' => 'nullable|string|max:1000',
            'prescription' => 'nullable|string|max:1000',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        $visit->update([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'department_id' => $request->department_id,
            'visit_date' => $request->visit_date,
            'visit_time' => $request->visit_time,
            'visit_type' => $request->visit_type,
            'chief_complaint' => $request->chief_complaint,
            'diagnosis' => $request->diagnosis,
            'treatment' => $request->treatment,
            'prescription' => $request->prescription,
            'notes' => $request->notes,
            'vital_signs' => $request->vital_signs ? (is_string($request->vital_signs) ? json_decode($request->vital_signs) : $request->vital_signs) : null,
            'next_visit_date' => $request->next_visit_date,
            'status' => $request->status,
        ]);

        // إذا تم إنهاء الزيارة، حدث حالة الموعد المرتبط بها
        if ($request->status === 'completed' && $visit->appointment) {
            $visit->appointment->complete();
        }

        // إذا تم إلغاء الزيارة، ألغِ الموعد المرتبط بها
        if ($request->status === 'cancelled' && $visit->appointment) {
            $visit->appointment->cancel('تم إلغاء الزيارة');
        }

        return redirect()->route('visits.show', $visit)
            ->with('success', 'تم تحديث الزيارة بنجاح');
    }

    public function destroy(Visit $visit)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('delete visits'))) {
            abort(403, 'غير مصرح لك بحذف الزيارة');
        }

        $visit->delete();

        return redirect()->route('visits.index')
            ->with('success', 'تم حذف الزيارة بنجاح');
    }

    public function createFromAppointment(Appointment $appointment)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('create visits'))) {
            abort(403, 'غير مصرح لك بإنشاء زيارة من موعد');
        }

        // التحقق من أن الموعد مدفوع
        if ($appointment->payment_status !== 'paid') {
            return redirect()->back()->with('error', 'لا يمكن تحويل الموعد إلى زيارة قبل الدفع');
        }

        // التحقق من عدم وجود زيارة مسبقة لهذا الموعد
        if ($appointment->visit) {
            return redirect()->route('visits.show', $appointment->visit)
                ->with('info', 'تم تحويل هذا الموعد إلى زيارة مسبقاً');
        }

        // إنشاء الزيارة تلقائياً
        $visit = Visit::create([
            'patient_id' => $appointment->patient_id,
            'doctor_id' => $appointment->doctor_id,
            'department_id' => $appointment->department_id,
            'appointment_id' => $appointment->id,
            'visit_date' => now()->format('Y-m-d'),
            'visit_time' => now()->format('H:i'),
            'visit_type' => 'checkup',
            'chief_complaint' => $appointment->reason ?? 'زيارة من موعد',
            'is_completed' => false,
            'status' => 'in_progress'
        ]);

        // تحديث حالة الموعد
        $appointment->update([
            'status' => 'completed'
        ]);

        return redirect()->route('visits.show', $visit)
            ->with('success', 'تم تحويل الموعد إلى زيارة بنجاح');
    }

    public function requestSurgery(Visit $visit)
    {
        // التحقق من أن الزيارة مكتملة
        if ($visit->status !== 'completed') {
            return redirect()->back()->with('error', 'لا يمكن طلب عملية إلا بعد إتمام الزيارة');
        }

        // إنشاء طلب عملية وإعادة توجيه لصفحة إنشاء العملية مع البيانات
        return redirect()->route('surgeries.create', [
            'visit_id' => $visit->id,
            'patient_id' => $visit->patient_id,
            'doctor_id' => $visit->doctor_id,
            'department_id' => $visit->department_id
        ]);
    }
}