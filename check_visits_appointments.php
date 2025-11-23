<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;
use App\Models\Appointment;

echo "Checking completed visits and their appointments:\n";
echo "==============================================\n";

$completedVisits = Visit::where('status', 'completed')->with('appointment')->get();

foreach($completedVisits as $visit) {
    echo "Visit ID: {$visit->id}, Status: {$visit->status}\n";
    if ($visit->appointment) {
        echo "  Appointment ID: {$visit->appointment->id}, Status: {$visit->appointment->status}\n";
    } else {
        echo "  No appointment linked\n";
    }
    echo "\n";
}

echo "Checking appointments with completed visits:\n";
echo "=========================================\n";

$appointmentsWithCompletedVisits = Appointment::whereHas('visit', function($query) {
    $query->where('status', 'completed');
})->with('visit')->get();

foreach($appointmentsWithCompletedVisits as $appt) {
    echo "Appointment ID: {$appt->id}, Status: {$appt->status}\n";
    if ($appt->visit) {
        echo "  Visit ID: {$appt->visit->id}, Status: {$appt->visit->status}\n";
    }
    echo "\n";
}