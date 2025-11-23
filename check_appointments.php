<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;

echo "فحص حالة المواعيد:\n";
echo "==================\n";

$appointments = Appointment::with('visit')->get();

foreach ($appointments as $appointment) {
    echo "موعد ID: {$appointment->id}\n";
    echo "التاريخ: {$appointment->appointment_date}\n";
    echo "الحالة: {$appointment->status}\n";

    if ($appointment->visit) {
        echo "له زيارة: نعم (حالة الزيارة: {$appointment->visit->status})\n";
    } else {
        echo "له زيارة: لا\n";
    }

    echo "---\n";
}