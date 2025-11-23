<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrescribedMedication;
use App\Models\Visit;

// Test creating a prescribed medication
try {
    // First, let's check if there's a visit to use
    $visit = Visit::first();
    if (!$visit) {
        echo "No visits found in database. Cannot test prescribed medication creation.\n";
        exit(1);
    }

    echo "Testing PrescribedMedication creation...\n";

    $medication = PrescribedMedication::create([
        'visit_id' => $visit->id,
        'item_type' => 'medication',
        'name' => 'Test Medication',
        'type' => 'tablet',
        'dosage' => '500mg',
        'frequency' => '3',
        'times' => 'after meals',
        'duration' => '7 days',
        'instructions' => 'Take with water'
    ]);

    echo "SUCCESS: PrescribedMedication created with ID: " . $medication->id . "\n";

    // Clean up
    $medication->delete();
    echo "Test record cleaned up.\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "All tests passed!\n";