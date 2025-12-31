<?php
namespace App\Http\Controllers;

// app/Http/Controllers/DashboardController.php

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use App\Models\Appointment;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // إحصائيات عامة
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
            'visitsByDay',
            'appointmentsByStatus',
            'patientsByDepartment',
            'topDoctors',
            'monthlyAppointments'
        ));
    }
}