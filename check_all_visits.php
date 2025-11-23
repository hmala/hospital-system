<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;

echo "Checking all visits:\n";
echo "===================\n";

$visits = Visit::with('appointment')->get();

foreach($visits as $visit) {
    echo "Visit ID: {$visit->id}, Status: {$visit->status}, Date: {$visit->visit_date}\n";
    echo "  Appointment ID: " . ($visit->appointment_id ?? 'NULL') . "\n";
    
    if ($visit->appointment_id) {
        $appt = \App\Models\Appointment::find($visit->appointment_id);
        if ($appt) {
            echo "  Appointment Status: {$appt->status}\n";
        } else {
            echo "  Appointment not found!\n";
        }
    }
    echo "\n";
}
