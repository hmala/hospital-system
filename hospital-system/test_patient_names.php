<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Visit;

try {
    // Test appointment patient name
    $appointment = Appointment::with(['patient.user'])->first();
    if ($appointment) {
        echo "Appointment patient name: " . ($appointment->patient ? $appointment->patient->user->name : 'No patient') . "\n";
    } else {
        echo "No appointments found\n";
    }

    // Test visit patient name
    $visit = Visit::with(['patient.user'])->first();
    if ($visit) {
        echo "Visit patient name: " . ($visit->patient ? $visit->patient->user->name : 'No patient') . "\n";
    } else {
        echo "No visits found\n";
    }

    echo "Patient name access test completed successfully!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}