<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;

echo "فحص المواعيد الموجودة للطبيب 5 في تاريخ 2025-11-07:\n";
echo "====================================================\n";

$appointments = Appointment::where('doctor_id', 5)
    ->where('appointment_date', '2025-11-07')
    ->get();

foreach ($appointments as $appointment) {
    echo "موعد ID: {$appointment->id}\n";
    echo "الوقت: {$appointment->appointment_date}\n";
    echo "الحالة: {$appointment->status}\n";
    echo "---\n";
}

if ($appointments->isEmpty()) {
    echo "لا توجد مواعيد لهذا الطبيب في هذا التاريخ\n";
}