<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;

echo "فحص الزيارة المكتملة:\n";
echo "=====================\n";

$visits = Visit::where('status', 'completed')->with('appointment')->get();

foreach ($visits as $visit) {
    echo "زيارة ID: {$visit->id}\n";
    echo "حالة الزيارة: {$visit->status}\n";
    echo "appointment_id: " . ($visit->appointment_id ?: 'null') . "\n";

    if ($visit->appointment) {
        echo "حالة الموعد: {$visit->appointment->status}\n";
        echo "يجب أن تكون حالة الموعد: completed\n";
    } else {
        echo "لا يوجد موعد مرتبط\n";
    }

    echo "---\n";
}