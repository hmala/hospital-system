<?php

namespace App\Http\Controllers\Eye;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Eye\EyeAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EyeDoctorAvailabilityController extends Controller
{
    /**
     * عرض قائمة أطباء واستشاريي العيون وتوفرهم اليومي وطابور الانتظار
     */
    public function index(Request $request)
    {
        $weekDays = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];

        $daysMap = [
            'Saturday'  => 'السبت',
            'Sunday'    => 'الأحد',
            'Monday'    => 'الإثنين',
            'Tuesday'   => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday'  => 'الخميس',
            'Friday'    => 'الجمعة',
        ];

        $defaultDay = $daysMap[date('l')] ?? 'السبت';
        $selectedDay = $request->query('day', $defaultDay);
        if (!in_array($selectedDay, $weekDays)) {
            $selectedDay = $defaultDay;
        }

        // جلب أطباء العيون المعتمدين في المركز
        $query = Doctor::with(['user', 'department'])
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->where('doctors.is_active', true)
            ->where(function ($q) {
                $q->whereHas('department', function ($dq) {
                    $dq->where('name', 'like', '%عيون%')->orWhere('name', 'like', '%Eye%');
                })
                ->orWhere('doctors.specialization', 'like', '%عيون%')
                ->orWhere('doctors.specialization', 'like', '%Eye%')
                ->orWhere('doctors.specialization', 'like', '%شبكية%')
                ->orWhere('doctors.specialization', 'like', '%قرنية%')
                ->orWhere('doctors.specialization', 'like', '%ساد%')
                ->orWhere('doctors.specialization', 'like', '%بصريات%')
                ->orWhere('doctors.specialization', 'like', '%جلوكوما%')
                ->orWhere('doctors.specialization', 'like', '%حول%');
            })
            ->select('doctors.*')
            ->orderBy('doctors.specialization')
            ->orderBy('users.name');

        if ($request->filled('day')) {
            $query->workingOnDay($selectedDay);
        }

        $eyeDoctors = $query->get();

        // تجميع الأطباء حسب التخصص الدقيق في طب العيون
        $groupedDoctors = $eyeDoctors->groupBy(function ($doc) {
            return $doc->specialization ?: 'طب وجراحة العيون العامة';
        });

        // مواعيد وطابور مراجعي العيون لليوم
        $today = Carbon::today();
        $todayAppointments = EyeAppointment::with(['patient', 'doctor.user', 'latestInvoice'])
            ->whereDate('created_at', $today)
            ->whereIn('status', ['waiting', 'dilated', 'in_clinic', 'in_investigation'])
            ->orderByRaw("CASE WHEN status = 'in_clinic' THEN 1 WHEN status = 'dilated' THEN 2 ELSE 3 END")
            ->orderBy('queue_number', 'asc')
            ->get();

        // الإحصائيات السريعة
        $allEyeDoctors = Doctor::where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('department', function ($dq) {
                    $dq->where('name', 'like', '%عيون%')->orWhere('name', 'like', '%Eye%');
                })
                ->orWhere('specialization', 'like', '%عيون%')
                ->orWhere('specialization', 'like', '%شبكية%')
                ->orWhere('specialization', 'like', '%ساد%')
                ->orWhere('specialization', 'like', '%قرنية%');
            })->get();

        $stats = [
            'total'             => $allEyeDoctors->count(),
            'available_today'   => $allEyeDoctors->where('is_available_today', true)->count(),
            'unavailable_today' => $allEyeDoctors->where('is_available_today', false)->count(),
            'today_queue'       => $todayAppointments->count(),
        ];

        return view('eye.availability.index', compact(
            'eyeDoctors',
            'groupedDoctors',
            'todayAppointments',
            'weekDays',
            'selectedDay',
            'stats'
        ));
    }

    /**
     * تحديث توفر طبيب عيون فردي
     */
    public function update(Request $request, Doctor $doctor)
    {
        $request->validate([
            'is_available_today' => 'required|in:0,1,true,false',
        ]);

        $isAvailable = filter_var($request->is_available_today, FILTER_VALIDATE_BOOLEAN);

        $doctor->update([
            'is_available_today' => $isAvailable,
            'available_date'     => now()->toDateString(),
        ]);

        $doctorName = $doctor->user->name ?? 'طبيب العيون';
        $statusText = $isAvailable ? 'متاح لاستقبال المرضى' : 'غير متاح حالياً';

        return back()->with('success', "تم تحديث حالة (د. {$doctorName}) إلى: {$statusText}");
    }

    /**
     * التحديث الجماعي لتوفر أطباء العيون
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'is_available_today' => 'required|in:0,1,true,false',
            'doctor_ids'         => 'nullable|array',
            'doctor_ids.*'       => 'exists:doctors,id',
        ]);

        $isAvailable = filter_var($request->is_available_today, FILTER_VALIDATE_BOOLEAN);
        $doctorIds = $request->doctor_ids ?? [];

        $query = Doctor::where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('department', function ($dq) {
                    $dq->where('name', 'like', '%عيون%')->orWhere('name', 'like', '%Eye%');
                })
                ->orWhere('specialization', 'like', '%عيون%')
                ->orWhere('specialization', 'like', '%شبكية%')
                ->orWhere('specialization', 'like', '%ساد%')
                ->orWhere('specialization', 'like', '%قرنية%');
            });

        if (!empty($doctorIds)) {
            $query->whereIn('id', $doctorIds);
        }

        $affected = $query->update([
            'is_available_today' => $isAvailable,
            'available_date'     => now()->toDateString(),
        ]);

        $message = $isAvailable
            ? "تم تفعيل التوفر لجميع أطباء واستشاريي العيون بنجاح ({$affected} طبيب)."
            : "تم إلغاء التوفر لجميع أطباء واستشاريي العيون بنجاح ({$affected} طبيب).";

        return back()->with('success', $message);
    }

    /**
     * مناداة واستدعاء مريض العيون للشاشة الخارجية (Call / Recall)
     */
    public function callPatient(Request $request, EyeAppointment $appointment)
    {
        // تحديث أو إبقاء الحالة وإرجاع تأكيد الاستدعاء
        return response()->json([
            'success'      => true,
            'message'      => "تمت مناداة المريض ({$appointment->patient->name}) على شاشة العيادة الخارجية.",
            'queue_number' => $appointment->queue_number,
            'doctor_name'  => $appointment->doctor->user->name ?? 'طبيب العيون',
        ]);
    }

    /**
     * إدخال مريض العيون إلى عيادة الطبيب (In Clinic)
     */
    public function admitPatient(Request $request, EyeAppointment $appointment)
    {
        $appointment->update([
            'status' => 'in_clinic',
        ]);

        return back()->with('success', "تم إدخال المريض ({$appointment->patient->name}) إلى عيادة د. " . ($appointment->doctor->user->name ?? 'طبيب العيون'));
    }

    /**
     * توثيق توسيع الحدقة لمريض العيون بقطرات الميدرياسيل (Dilated)
     */
    public function dilatePatient(Request $request, EyeAppointment $appointment)
    {
        $appointment->update([
            'status' => 'dilated',
        ]);

        return back()->with('success', "تم وضع قطرات توسيع الحدقة للمريض ({$appointment->patient->name}) وتحديث حالته في الطابور.");
    }
}
