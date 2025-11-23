<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;

echo "Checking appointment statuses:\n";
echo "=============================\n";

$appointments = Appointment::all();
foreach($appointments as $appt) {
    echo "ID: {$appt->id}, Status: {$appt->status}, Date: {$appt->appointment_date->format('Y-m-d')}\n";
}

echo "\nCompleted appointments:\n";
$completed = Appointment::where('status', 'completed')->get();
foreach($completed as $appt) {
    echo "ID: {$appt->id}, Date: {$appt->appointment_date->format('Y-m-d')}\n";
}

echo "\nScheduled appointments:\n";
$scheduled = Appointment::where('status', 'scheduled')->get();
foreach($scheduled as $appt) {
    echo "ID: {$appt->id}, Date: {$appt->appointment_date->format('Y-m-d')}\n";
}