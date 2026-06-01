<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SurgeryRadiologyTest;
use App\Models\Surgery;

echo "=== Checking Surgery Radiology Tests for Orphaned Records ===\n\n";

// Check for tests with null surgery_id
$nullSurgeryTests = SurgeryRadiologyTest::whereNull('surgery_id')->get();
echo "Tests with null surgery_id: " . $nullSurgeryTests->count() . "\n";

if ($nullSurgeryTests->count() > 0) {
    echo "IDs: " . $nullSurgeryTests->pluck('id')->implode(', ') . "\n";
}
echo "\n";

// Check for tests where surgery has been deleted
$allTests = SurgeryRadiologyTest::all();
$orphanedTests = [];

foreach ($allTests as $test) {
    if ($test->surgery_id && !$test->surgery) {
        $orphanedTests[] = $test->id;
    }
}

echo "Tests with deleted surgery: " . count($orphanedTests) . "\n";
if (count($orphanedTests) > 0) {
    echo "IDs: " . implode(', ', $orphanedTests) . "\n";
}
echo "\n";

// Check for surgeries without patient
$testsWithInvalidSurgery = [];
foreach ($allTests as $test) {
    if ($test->surgery && !$test->surgery->patient) {
        $testsWithInvalidSurgery[] = [
            'test_id' => $test->id,
            'surgery_id' => $test->surgery_id
        ];
    }
}

echo "Tests where surgery has no patient: " . count($testsWithInvalidSurgery) . "\n";
if (count($testsWithInvalidSurgery) > 0) {
    foreach ($testsWithInvalidSurgery as $info) {
        echo "  Test ID: {$info['test_id']}, Surgery ID: {$info['surgery_id']}\n";
    }
}
echo "\n";

// Check for surgeries where patient has no user
$testsWithInvalidPatient = [];
foreach ($allTests as $test) {
    if ($test->surgery && $test->surgery->patient && !$test->surgery->patient->user) {
        $testsWithInvalidPatient[] = [
            'test_id' => $test->id,
            'surgery_id' => $test->surgery_id,
            'patient_id' => $test->surgery->patient_id
        ];
    }
}

echo "Tests where patient has no user: " . count($testsWithInvalidPatient) . "\n";
if (count($testsWithInvalidPatient) > 0) {
    foreach ($testsWithInvalidPatient as $info) {
        echo "  Test ID: {$info['test_id']}, Surgery ID: {$info['surgery_id']}, Patient ID: {$info['patient_id']}\n";
    }
}
echo "\n";

echo "=== Check Complete ===\n";
