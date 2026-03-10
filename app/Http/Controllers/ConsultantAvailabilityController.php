<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConsultantAvailabilityController extends Controller
{
    public function __construct()
    {
        // تطبيق middleware للوظائف العادية فقط، وليس للـ API
        $this->middleware(function ($request, $next) {
            // تجاهل التحقق للـ API endpoints
            if ($request->is('api/*')) {
                return $next($request);
            }
            
            // التحقق من أن المستخدم لديه صلاحية إدارة توفر الأطباء الاستشاريين
            if (!auth()->user() || !auth()->user()->can('manage consultant availability')) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
            }
            return $next($request);
        });
    }

    /**
     * عرض قائمة الأطباء الاستشاريين وتوفرهم اليومي
     */
    public function index()
    {
        $consultantDoctors = Doctor::with(['user', 'department'])
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->where('doctors.type', 'consultant')
            ->where('doctors.is_active', true)
            ->orderBy('doctors.specialization')
            ->orderBy('users.name')
            ->select('doctors.*')
            ->get();

        // تجميع الأطباء حسب التخصص للعرض
        $groupedDoctors = $consultantDoctors->groupBy('specialization');

        // جلب المواعيد المحجوزة اليوم للأطباء الاستشاريين
        $todayAppointments = \App\Models\Appointment::with(['patient.user', 'doctor.user', 'emergency'])
            ->whereHas('doctor', function($q) {
                $q->where('type', 'consultant');
            })
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('appointment_date')
            ->get();

        return view('consultant-availability.index', compact('consultantDoctors', 'groupedDoctors', 'todayAppointments'));
    }

    /**
     * صفحة اختبار بسيطة
     */
    public function test()
    {
        $consultantDoctors = Doctor::with(['user', 'department'])
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->where('doctors.type', 'consultant')
            ->where('doctors.is_active', true)
            ->orderBy('doctors.specialization')
            ->orderBy('users.name')
            ->select('doctors.*')
            ->get();

        return view('consultant-availability.test', compact('consultantDoctors'));
    }

    /**
     * صفحة مبسطة بدون JavaScript معقد
     */
    public function simple()
    {
        $consultantDoctors = Doctor::with(['user', 'department'])
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->where('doctors.type', 'consultant')
            ->where('doctors.is_active', true)
            ->orderBy('doctors.specialization')
            ->orderBy('users.name')
            ->select('doctors.*')
            ->get();

        // تجميع الأطباء حسب التخصص للعرض
        $groupedDoctors = $consultantDoctors->groupBy('specialization');

        return view('consultant-availability.simple', compact('consultantDoctors', 'groupedDoctors'));
    }

    /**
     * تحديث توفر طبيب استشاري
     */
    public function updateAvailability(Request $request, Doctor $doctor)
    {
        // التحقق من أن الطبيب استشاري
        if ($doctor->type !== 'consultant') {
            return redirect()->back()->with('error', 'يمكن تحديث توفر الأطباء الاستشاريين فقط');
        }

        $request->validate([
            'is_available_today' => 'required|in:0,1,true,false',
        ]);

        $isAvailable = filter_var($request->is_available_today, FILTER_VALIDATE_BOOLEAN);

        $doctor->update([
            'is_available_today' => $isAvailable,
            'available_date' => today(),
        ]);

        $statusText = $isAvailable ? 'متوفر' : 'غير متوفر';

        return redirect()->back()->with('success', "تم تحديث توفر الطبيب: {$statusText}");
    }

    /**
     * تحديث توفر جميع الأطباء الاستشاريين
     */
    public function bulkUpdate(Request $request)
    {
        try {
            // التحقق من الصلاحيات (للـ web routes)
            if (auth()->check() && !auth()->user()->can('manage consultant availability')) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
            }

            $request->validate([
                'is_available_today' => 'required|in:0,1,true,false',
                'doctor_ids' => 'nullable|array',
                'doctor_ids.*' => 'exists:doctors,id',
            ]);

            $isAvailable = filter_var($request->is_available_today, FILTER_VALIDATE_BOOLEAN);
            $doctorIds = $request->doctor_ids ?? [];

            if (empty($doctorIds)) {
                // تحديث جميع الأطباء الاستشاريين
                $affected = Doctor::where('type', 'consultant')
                    ->where('is_active', true)
                    ->update([
                        'is_available_today' => $isAvailable,
                        'available_date' => today(),
                    ]);

                $message = $isAvailable ?
                    'تم تفعيل التوفر لجميع الأطباء الاستشاريين' :
                    'تم إلغاء التوفر لجميع الأطباء الاستشاريين';
            } else {
                // تحديث الأطباء المحددين فقط
                $affected = Doctor::whereIn('id', $doctorIds)
                    ->where('type', 'consultant')
                    ->where('is_active', true)
                    ->update([
                        'is_available_today' => $isAvailable,
                        'available_date' => today(),
                    ]);

                $message = $isAvailable ?
                    'تم تفعيل التوفر للأطباء المحددين' :
                    'تم إلغاء التوفر للأطباء المحددين';
            }

            // إذا كان API call، أعد JSON response
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'affected_doctors' => $affected
                ]);
            }

            // إعادة توجيه للـ web interface
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Bulk update error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'user' => auth()->check() ? auth()->user()->name : 'guest',
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}
