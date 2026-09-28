<?php

namespace App\Http\Controllers\Eye;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Eye\EyeAppointment;
use Illuminate\Http\Request;

class EyeQueueController extends Controller
{
    /**
     * Show central TV display for all active Eye clinics in waiting hall
     */
    public function allClinicsDisplay()
    {
        return view('eye.queue.all-clinics-display');
    }

    /**
     * Realtime JSON data for ALL Eye clinics (Central waiting hall screen)
     */
    public function allClinicsData()
    {
        $today = today();
        $daysMap = [
            'Saturday'  => 'السبت',
            'Sunday'    => 'الأحد',
            'Monday'    => 'الاثنين',
            'Tuesday'   => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday'  => 'الخميس',
            'Friday'    => 'الجمعة',
        ];
        $todayArabicDay = $daysMap[date('l')] ?? 'السبت';

        $doctors = Doctor::with(['user', 'department'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereHas('department', function ($dq) {
                    $dq->where('name', 'like', '%عيون%')->orWhere('name', 'like', '%Eye%');
                })
                ->orWhere('specialization', 'like', '%شبكية%')
                ->orWhere('specialization', 'like', '%قرنية%')
                ->orWhere('specialization', 'like', '%فاكو%')
                ->orWhere('specialization', 'like', '%جلوكوما%');
            })
            ->workingOnDay($todayArabicDay)
            ->where('is_available_today', true)
            ->get();

        $clinics = [];
        foreach ($doctors as $doctor) {
            $current = EyeAppointment::with(['patient.user'])
                ->where('doctor_id', $doctor->id)
                ->whereDate('created_at', $today)
                ->whereIn('status', ['in_clinic'])
                ->orderBy('updated_at', 'desc')
                ->first();

            $waitingCount = EyeAppointment::where('doctor_id', $doctor->id)
                ->whereDate('created_at', $today)
                ->whereIn('status', ['waiting', 'dilated'])
                ->count();

            $clinics[] = [
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->user->name ?? 'غير معروف',
                'specialization' => $doctor->specialization,
                'department_name' => $doctor->department->name ?? 'عيادة عيون',
                'waiting_count' => $waitingCount,
                'current_patient' => $current ? [
                    'queue_number' => $current->queue_number ?? '-',
                    'name' => $current->patient->user->name ?? 'مريض بدون اسم',
                    'status' => $current->status, 
                ] : null,
            ];
        }

        return response()->json([
            'success' => true,
            'clinics' => $clinics,
        ]);
    }
}
