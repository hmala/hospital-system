<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;

echo "Checking appointment ID 5:\n";
echo "========================\n";

$appointment = Appointment::with('visit')->find(5);

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
        
        // تحديث حالة الموعد
        if ($appointment->visit->status === 'completed' && $appointment->status !== 'completed') {
            echo "\nUpdating appointment status to completed...\n";
            $appointment->complete();
            echo "✓ Appointment updated to completed\n";
        }
    }
} else {
    echo "Appointment not found\n";
}
