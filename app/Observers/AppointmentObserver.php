<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Services\UserActivityLogger;

class AppointmentObserver
{
    public function creating(Appointment $appointment): void
    {
        if (empty($appointment->queue_number) && !empty($appointment->doctor_id)) {
            $date = $appointment->appointment_date ? \Carbon\Carbon::parse($appointment->appointment_date) : today();
            $maxQueue = Appointment::where('doctor_id', $appointment->doctor_id)
                ->whereDate('appointment_date', $date)
                ->whereNotNull('queue_number')
                ->max('queue_number') ?? 0;
            $appointment->queue_number = $maxQueue + 1;
        }
    }

    public function created(Appointment $appointment): void
    {
        $patientName = $appointment->patient?->name ?? 'مريض';
        $doctorName = $appointment->doctor?->user?->name ?? 'طبيب غير محدد';
        $queueNo = $appointment->queue_number ? " (دور #{$appointment->queue_number})" : '';
        $recheckText = $appointment->is_free_recheck ? ' [مراجعة مجانية]' : '';

        UserActivityLogger::log(
            action: 'create_appointment',
            category: 'reception',
            description: "حجز كشفية للمريض: {$patientName} لدى د. {$doctorName}{$queueNo}{$recheckText}",
            subject: $appointment,
            isSensitive: false,
            properties: [
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'queue_number' => $appointment->queue_number,
                'is_free_recheck' => $appointment->is_free_recheck,
            ]
        );
    }

    public function updated(Appointment $appointment): void
    {
        if ($appointment->wasChanged('status') && $appointment->status === 'cancelled') {
            $patientName = $appointment->patient?->name ?? 'مريض';
            UserActivityLogger::log(
                action: 'cancel_appointment',
                category: 'reception',
                description: "إلغاء حجز الكشفية للمريض: {$patientName}",
                subject: $appointment,
                isSensitive: true
            );
        }
    }

    public function deleted(Appointment $appointment): void
    {
        $patientName = $appointment->patient?->name ?? 'مريض';
        UserActivityLogger::log(
            action: 'delete_appointment',
            category: 'reception',
            description: "حذف سجل حجز المريض: {$patientName}",
            subject: $appointment,
            isSensitive: true
        );
    }
}
