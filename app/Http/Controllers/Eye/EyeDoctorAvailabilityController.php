<?php

namespace App\Http\Controllers\Eye;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Eye\EyeAppointment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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

    /**
     * استمارة إضافة طبيب واستشاري عيون جديد خاصة بمركز العيون
     */
    public function createDoctor()
    {
        $eyeSpecializations = [
            'جراحة الشبكية والجسم الزجاجي (Vitreoretinal Surgery)',
            'جراحة الساد (الماء الأبيض) والفاكو وزراعة العدسات (Phaco & IOL)',
            'أمراض وجراحة القرنية والليزك وتصحيح البصر (Cornea & Refractive)',
            'تشخيص وعلاج الجلوكوما وضغط العين (Glaucoma)',
            'طب عيون الأطفال والحول (Pediatric Ophthalmology & Strabismus)',
            'جراحة تجميل العين وتقويم الجفون ومجرى الدمع (Oculoplastics)',
            'فحص البصريات والعدسات الطبية اللاصقة (Optometry)',
            'طب وجراحة العيون العامة (Comprehensive Ophthalmology)',
        ];

        $weekDays = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];

        return view('eye.availability.create_doctor', compact('eyeSpecializations', 'weekDays'));
    }

    /**
     * حفظ طبيب عيون جديد في مركز العيون
     */
    public function storeDoctor(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'phone'            => 'required|string|max:20',
            'password'         => 'nullable|string|min:6',
            'specialization'   => 'required|string|max:255',
            'custom_specialization' => 'nullable|string|max:255',
            'type'             => 'required|in:consultant,surgeon,resident,optometrist',
            'consultation_fee' => 'required|numeric|min:0',
            'hi_price'         => 'nullable|numeric|min:0',
            'is_hi_active'     => 'nullable',
            'moi_price'        => 'nullable|numeric|min:0',
            'is_moi_active'    => 'nullable',
            'start_time'       => 'required',
            'end_time'         => 'required',
            'working_days'     => 'required|array|min:1',
            'working_days.*'   => 'string',
            'is_available_today' => 'nullable',
        ]);

        DB::beginTransaction();
        try {
            // جلب أو إنشاء قسم مركز العيون
            $eyeDepartment = Department::where(function ($q) {
                $q->where('name', 'like', '%عيون%')->orWhere('name', 'like', '%Eye%');
            })->first();

            if (!$eyeDepartment) {
                $firstHospitalId = DB::table('hospitals')->value('id') ?? 1;
                $eyeDepartment = Department::create([
                    'hospital_id'         => $firstHospitalId,
                    'name'                => 'مركز وجراحة العيون',
                    'type'                => 'surgery',
                    'room_number'         => 'EYE-101',
                    'consultation_fee'    => 25000,
                    'working_hours_start' => '08:00:00',
                    'working_hours_end'   => '20:00:00',
                    'is_active'           => true,
                ]);
            }

            // إنشاء حساب المستخدم
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password ?: 'password'),
                'role'     => 'doctor',
                'phone'    => $request->phone,
            ]);

            // إسناد دور طبيب
            if (Role::where('name', 'doctor')->exists()) {
                $user->assignRole('doctor');
            }

            $finalSpecialization = ($request->specialization === 'other' && $request->filled('custom_specialization'))
                ? $request->custom_specialization
                : $request->specialization;

            $isAvailableToday = $request->boolean('is_available_today');

            // إنشاء سجل الطبيب التابع لمركز العيون
            Doctor::create([
                'user_id'            => $user->id,
                'department_id'      => $eyeDepartment->id,
                'type'               => $request->type === 'optometrist' ? 'consultant' : $request->type,
                'phone'              => $request->phone,
                'specialization'     => $finalSpecialization,
                'qualification'      => 'استشاري طب وجراحة العيون',
                'license_number'     => 'EYE-LIC-' . $user->id . '-' . rand(1000, 9999),
                'consultation_fee'   => $request->consultation_fee,
                'hi_price'           => $request->hi_price ?? ($request->consultation_fee * 0.9),
                'is_hi_active'       => $request->has('is_hi_active'),
                'moi_price'          => $request->moi_price ?? ($request->consultation_fee * 0.8),
                'is_moi_active'      => $request->has('is_moi_active'),
                'start_time'         => $request->start_time,
                'end_time'           => $request->end_time,
                'working_days'       => $request->working_days,
                'is_active'          => true,
                'is_available_today' => $isAvailableToday,
                'available_date'     => $isAvailableToday ? now()->toDateString() : null,
            ]);

            DB::commit();

            return redirect()->route('eye.availability.index')
                ->with('success', "تمت إضافة د. {$request->name} إلى أطباء مركز العيون بنجاح!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء إضافة الطبيب: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * استمارة تعديل بيانات ودوام طبيب العيون
     */
    public function editDoctor(Doctor $doctor)
    {
        $doctor->load(['user', 'department']);

        $eyeSpecializations = [
            'جراحة الشبكية والجسم الزجاجي (Vitreoretinal Surgery)',
            'جراحة الساد (الماء الأبيض) والفاكو وزراعة العدسات (Phaco & IOL)',
            'أمراض وجراحة القرنية والليزك وتصحيح البصر (Cornea & Refractive)',
            'تشخيص وعلاج الجلوكوما وضغط العين (Glaucoma)',
            'طب عيون الأطفال والحول (Pediatric Ophthalmology & Strabismus)',
            'جراحة تجميل العين وتقويم الجفون ومجرى الدمع (Oculoplastics)',
            'فحص البصريات والعدسات الطبية اللاصقة (Optometry)',
            'طب وجراحة العيون العامة (Comprehensive Ophthalmology)',
        ];

        $weekDays = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];

        return view('eye.availability.edit_doctor', compact('doctor', 'eyeSpecializations', 'weekDays'));
    }

    /**
     * تحديث بيانات ودوام وأجور طبيب العيون
     */
    public function updateDoctorSettings(Request $request, Doctor $doctor)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'phone'            => 'required|string|max:20',
            'specialization'   => 'required|string|max:255',
            'type'             => 'required|in:consultant,surgeon,resident,optometrist',
            'consultation_fee' => 'required|numeric|min:0',
            'hi_price'         => 'nullable|numeric|min:0',
            'moi_price'        => 'nullable|numeric|min:0',
            'start_time'       => 'required',
            'end_time'         => 'required',
            'working_days'     => 'required|array|min:1',
            'is_active'        => 'nullable',
        ]);

        $doctor->user->update([
            'name'  => $request->name,
            'phone' => $request->phone,
        ]);

        $doctor->update([
            'phone'            => $request->phone,
            'specialization'   => $request->specialization,
            'type'             => $request->type === 'optometrist' ? 'consultant' : $request->type,
            'consultation_fee' => $request->consultation_fee,
            'hi_price'         => $request->hi_price ?? $doctor->hi_price,
            'is_hi_active'     => $request->has('is_hi_active'),
            'moi_price'        => $request->moi_price ?? $doctor->moi_price,
            'is_moi_active'    => $request->has('is_moi_active'),
            'start_time'       => $request->start_time,
            'end_time'         => $request->end_time,
            'working_days'     => $request->working_days,
            'is_active'        => $request->has('is_active'),
        ]);

        return redirect()->route('eye.availability.index')
            ->with('success', "تم تحديث بيانات ودوام د. {$request->name} بنجاح!");
    }
}
