<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Surgery;

echo "Testing surgery treatments display in table...\n\n";

$surgeries = Surgery::with(['surgeryTreatments'])->take(3)->get();

foreach($surgeries as $surgery) {
    echo "Surgery ID: " . $surgery->id . " - Patient: " . $surgery->patient->user->name . "\n";
    echo "Treatments count: " . $surgery->surgeryTreatments->count() . "\n";

    if ($surgery->surgeryTreatments->count() > 0) {
        echo "Should show badge with count: " . $surgery->surgeryTreatments->count() . "\n";
        echo "Modal should contain:\n";
        foreach($surgery->surgeryTreatments as $treatment) {
            echo "  - " . $treatment->description . " (" . ($treatment->dosage ?? 'N/A') . ")\n";
        }
    } else {
        echo "Should show: لا يوجد\n";
    }
    echo "\n";
}

echo "Test completed!\n";