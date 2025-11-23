<?php
namespace App\Http\Controllers;

// app/Http/Controllers/DashboardController.php

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use App\Models\Appointment;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'totalPatients' => User::where('role', 'patient')->count(),
            'totalDoctors' => User::where('role', 'doctor')->count(),
            'totalDepartments' => Department::count(),
            'todayAppointments' => Appointment::whereDate('appointment_date', today())->count(),
            'pendingAppointments' => Appointment::where('status', 'pending')->count(),
            'completedAppointments' => Appointment::where('status', 'completed')->count(),
            'cancelledAppointments' => Appointment::where('status', 'cancelled')->count(),
            'totalRevenue' => Appointment::where('status', 'completed')->sum('fee'),
            'activeDepartments' => Department::where('is_active', true)->count(),
        ];

        return view('dashboard', compact('stats'));
    }
}