<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;

echo "إصلاح حالة المواعيد للزيارات المكتملة:\n";
echo "=======================================\n";

$completedVisits = Visit::where('status', 'completed')->whereHas('appointment')->with('appointment')->get();

$updated = 0;
foreach ($completedVisits as $visit) {
    if ($visit->appointment && $visit->appointment->status !== 'completed') {
        echo "تحديث موعد ID {$visit->appointment->id} من {$visit->appointment->status} إلى completed\n";
        $visit->appointment->complete();
        $updated++;
    }
}

// أيضاً الزيارات الملغاة
$cancelledVisits = Visit::where('status', 'cancelled')->whereHas('appointment')->with('appointment')->get();

foreach ($cancelledVisits as $visit) {
    if ($visit->appointment && $visit->appointment->status !== 'cancelled') {
        echo "تحديث موعد ID {$visit->appointment->id} من {$visit->appointment->status} إلى cancelled\n";
        $visit->appointment->cancel('تم إلغاء الزيارة');
        $updated++;
    }
}

// تحديث المواعيد المجدولة التي لها زيارات مع بيانات محفوظة
$visitsWithData = Visit::where('status', '!=', 'completed')
    ->where('status', '!=', 'cancelled')
    ->whereHas('appointment', function($query) {
        $query->where('status', 'scheduled');
    })
    ->where(function($query) {
        $query->whereNotNull('diagnosis')
              ->orWhereNotNull('treatment_plan')
              ->orWhereHas('prescribedMedications');
    })
    ->with('appointment')
    ->get();

foreach ($visitsWithData as $visit) {
    if ($visit->appointment) {
        echo "تحديث موعد ID {$visit->appointment->id} من scheduled إلى confirmed (زيارة مع بيانات)\n";
        $visit->appointment->confirm();
        $updated++;
    }
}

echo "\nتم تحديث {$updated} موعد\n";