<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;

echo "Checking appointment ID 3:\n";
echo "========================\n";

$appointment = Appointment::with('visit')->find(3);

if ($appointment) {
    echo "Appointment ID: {$appointment->id}\n";
    echo "Status: {$appointment->status}\n";
    echo "Date: {$appointment->appointment_date}\n";
    echo "Has visit: " . ($appointment->visit ? 'Yes' : 'No') . "\n";
    
    if ($appointment->visit) {
        echo "\nVisit Details:\n";
        echo "  Visit ID: {$appointment->visit->id}\n";
        echo "  Visit Status: {$appointment->visit->status}\n";
        echo "  Visit Date: {$appointment->visit->visit_date}\n";
    }
} else {
    echo "Appointment not found\n";
}
