<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appointment;
use App\Models\Visit;

echo "Fixing old appointments with completed visits:\n";
echo "===========================================\n";

// العثور على المواعيد التي لها زيارات مكتملة ولكن حالة الموعد ليست completed
$appointments = Appointment::whereHas('visit', function($query) {
    $query->where('status', 'completed');
})->where('status', '!=', 'completed')->with('visit')->get();

$updated = 0;
foreach($appointments as $appointment) {
    echo "Appointment ID: {$appointment->id}, Status: {$appointment->status}\n";
    echo "  Visit ID: {$appointment->visit->id}, Visit Status: {$appointment->visit->status}\n";
    echo "  Updating appointment to completed...\n";
    
    $appointment->complete();
    $updated++;
    echo "  ✓ Updated\n\n";
}

echo "Total updated: {$updated} appointments\n";
