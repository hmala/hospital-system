<?php

namespace App\Observers;

use App\Models\Visit;
use App\Services\UserActivityLogger;

class VisitObserver
{
    public function created(Visit $visit): void
    {
        $patientName = $visit->patient?->name ?? 'مريض';
        $doctorName = $visit->doctor?->user?->name ?? 'طبيب';

        UserActivityLogger::log(
            action: 'start_visit',
            category: 'doctor',
            description: "فتح سجل زيارة كشف طبي للمريض: {$patientName} عند د. {$doctorName}",
            subject: $visit,
            isSensitive: false
        );
    }

    public function updated(Visit $visit): void
    {
        if ($visit->wasChanged('status') && $visit->status === 'completed') {
            $patientName = $visit->patient?->name ?? 'مريض';
            $diagnosis = $visit->diagnosis ? " (التشخيص: {$visit->diagnosis})" : '';

            UserActivityLogger::log(
                action: 'complete_consultation',
                category: 'doctor',
                description: "إكمال فحص وكشف المريض: {$patientName}{$diagnosis}",
                subject: $visit,
                isSensitive: false
            );
        }
    }
}
