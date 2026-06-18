<?php
namespace App\Http\Controllers;

// app/Http/Controllers/DashboardController.php

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use App\Models\Appointment;
use App\Models\Visit;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // إحصائيات خاصة بموظف الأشعة
        if ($user->hasRole('radiology_staff')) {
            $pendingRadiology = \App\Models\RadiologyRequest::where('status', 'pending')->count();
            $scheduledRadiology = \App\Models\RadiologyRequest::where('status', 'scheduled')->count();
            $inProgressRadiology = \App\Models\RadiologyRequest::where('status', 'in_progress')->count();
            $completedTodayRadiology = \App\Models\RadiologyRequest::where('status', 'completed')
                ->whereDate('performed_at', today())
                ->count();

            $radiologyStats = [
                'pending' => $pendingRadiology,
                'scheduled' => $scheduledRadiology,
                'in_progress' => $inProgressRadiology,
                'completed_today' => $completedTodayRadiology,
                'total' => \App\Models\RadiologyRequest::count(),
            ];

            $userStats = [
                'name' => $user->name,
                'role' => 'Radiology Staff',
                'your_todo' => $pendingRadiology,
                'processed_today' => $completedTodayRadiology,
                'total_tasks' => $radiologyStats['total'],
            ];

            return view('dashboard', compact('radiologyStats', 'userStats'));
        }

        // إحصائيات خاصة بموظف المختبر
        if ($user->hasRole('lab_staff')) {
            $pendingLab = \App\Models\Request::whereIn('type', ['lab', 'blood_bank'])
                ->where('status', 'pending')
                ->count();
            $completedTodayLab = \App\Models\Request::whereIn('type', ['lab', 'blood_bank'])
                ->where('status', 'completed')
                ->whereDate('updated_at', today())
                ->count();

            $labStats = [
                'pending' => $pendingLab,
                'completed_today' => $completedTodayLab,
            ];

            $userStats = [
                'name' => $user->name,
                'role' => 'Lab Staff',
                'your_todo' => $pendingLab,
                'processed_today' => $completedTodayLab,
            ];

            return view('dashboard', compact('labStats', 'userStats'));
        }

        // إحصائيات خاصة بمقيم التخدير
        if ($user->hasRole('التخدير')) {
            $anesthesiaStationCount = \App\Models\Surgery::whereHas('surgeonStation', function($q) {
                    $q->where('status', 'completed');
                })
                ->where(function($q) {
                    $q->whereDoesntHave('anesthesiaStation')
                      ->orWhereHas('anesthesiaStation', function($sq) {
                          $sq->where('status', '!=', 'completed');
                      });
                })->count();

            $completedToday = \App\Models\AnesthesiaStation::where('status', 'completed')
                ->whereDate('updated_at', today())
                ->count();

            $userStats = [
                'name' => $user->name,
                'role' => 'مقيم تخدير',
                'your_todo' => $anesthesiaStationCount,
                'processed_today' => $completedToday,
            ];

            $stats = [
                'totalPatients' => User::role('patient')->count(),
                'totalDoctors' => User::role('doctor')->count(),
                'totalDepartments' => Department::count(),
                'todayAppointments' => Appointment::whereDate('appointment_date', today())->count(),
                'pendingAppointments' => Appointment::where('status', 'pending')->count(),
                'completedAppointments' => Appointment::where('status', 'completed')->count(),
                'cancelledAppointments' => Appointment::where('status', 'cancelled')->count(),
                'totalVisits' => Visit::count(),
                'todayVisits' => Visit::whereDate('visit_date', today())->count(),
            ];

            return view('dashboard', compact('userStats', 'stats'));
        }

        // إحصائيات عامة للمستخدمين الآخرين
        $stats = [
            'totalPatients' => User::role('patient')->count(),
            'totalDoctors' => User::role('doctor')->count(),
            'totalDepartments' => Department::count(),
            'todayAppointments' => Appointment::whereDate('appointment_date', today())->count(),
            'pendingAppointments' => Appointment::where('status', 'pending')->count(),
            'completedAppointments' => Appointment::where('status', 'completed')->count(),
            'cancelledAppointments' => Appointment::where('status', 'cancelled')->count(),
            'totalVisits' => Visit::count(),
            'todayVisits' => Visit::whereDate('visit_date', today())->count(),
        ];

        $userStats = ['name' => $user->name, 'role' => $user->roles->pluck('name')->join(', ')];

        if ($user->hasRole('doctor') && $user->doctor) {
            $userStats['your_appointments'] = Appointment::where('doctor_id', $user->doctor->id)->count();
            $userStats['your_visits'] = Visit::where('doctor_id', $user->doctor->id)->count();
        }

        if ($user->hasRole('consultation_receptionist')) {
            $userStats['requests_created'] = \App\Models\Request::where('requested_by', $user->id)->count();
        }

        if ($user->hasRole('receptionist')) {
            $userStats['today_checkin'] = Visit::whereDate('visit_date', today())->count();
        }

        if ($user->hasRole('cashier')) {
            // لا توجد عمود status في payments، نعتبر غير المدفوعة إذا كان paid_at فارغ
            $userStats['pending_payments'] = \App\Models\Payment::whereNull('paid_at')->count();
        }


        // إحصائيات الغرف
        $roomStats = [
            'total' => Room::count(),
            'active' => Room::where('is_active', true)->count(),
            'available' => Room::where('status', 'available')->where('is_active', true)->count(),
            'occupied' => Room::where('status', 'occupied')->where('is_active', true)->count(),
            'maintenance' => Room::where('status', 'maintenance')->where('is_active', true)->count(),
        ];

        // مخطط الزيارات الأسبوعية (آخر 7 أيام)
        $weeklyVisits = Visit::select(DB::raw('DATE(visit_date) as date'), DB::raw('count(*) as count'))
            ->whereBetween('visit_date', [now()->subDays(6), now()])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ملء الأيام المفقودة بصفر
        $visitsByDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $visitsByDay[$date] = 0;
        }
        foreach ($weeklyVisits as $visit) {
            $visitsByDay[$visit->date] = $visit->count;
        }

        // مخطط المواعيد حسب الحالة
        $appointmentsByStatus = [
            'pending' => $stats['pendingAppointments'],
            'completed' => $stats['completedAppointments'],
            'cancelled' => $stats['cancelledAppointments'],
        ];

        // توزيع المرضى حسب العيادات
        $patientsByDepartment = Department::withCount(['doctors' => function($query) {
            $query->withCount('patients');
        }])->get()->map(function($dept) {
            return [
                'name' => $dept->name,
                'count' => $dept->doctors->sum('patients_count') ?? 0
            ];
        });

        // أكثر الأطباء نشاطاً (بناءً على عدد الزيارات)
        $topDoctors = User::role('doctor')
            ->withCount('visits')
            ->orderByDesc('visits_count')
            ->limit(5)
            ->get();

        // إحصائيات شهرية للمواعيد (آخر 6 أشهر)
        $monthlyAppointments = Appointment::select(
                DB::raw('DATE_FORMAT(appointment_date, "%Y-%m") as month'),
                DB::raw('count(*) as count')
            )
            ->whereBetween('appointment_date', [now()->subMonths(5)->startOfMonth(), now()])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return view('dashboard', compact(
            'stats',
            'roomStats',
            'visitsByDay',
            'appointmentsByStatus',
            'patientsByDepartment',
            'topDoctors',
            'monthlyAppointments',
            'userStats'
        ));
    }
}