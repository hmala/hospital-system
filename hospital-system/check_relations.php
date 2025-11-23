<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;

echo "=== Checking User Relations ===\n\n";

$doctor = User::where('role', 'doctor')->first();
if ($doctor) {
    echo "Doctor user: {$doctor->name}\n";
    echo "Has doctor relation: " . ($doctor->doctor ? 'yes' : 'no') . "\n";
    if ($doctor->doctor) {
        echo "Doctor ID: {$doctor->doctor->id}\n";
        echo "Department ID: {$doctor->doctor->department_id}\n";
    }
} else {
    echo "No doctor found\n";
}

echo "\n";

$patient = User::where('role', 'patient')->first();
if ($patient) {
    echo "Patient user: {$patient->name}\n";
    echo "Has patient relation: " . ($patient->patient ? 'yes' : 'no') . "\n";
    if ($patient->patient) {
        echo "Patient ID: {$patient->patient->id}\n";
    }
} else {
    echo "No patient found\n";
}

echo "\n=== Creating Missing Relations ===\n";

// Create doctor relation if missing
if ($doctor && !$doctor->doctor) {
    $department = \App\Models\Department::first();
    if ($department) {
        Doctor::create([
            'user_id' => $doctor->id,
            'department_id' => $department->id,
            'specialization' => 'General Medicine',
            'license_number' => 'DOC001',
            'phone' => '1234567890',
            'is_active' => true
        ]);
        echo "Created doctor relation\n";
    }
}

// Create patient relation if missing
if ($patient && !$patient->patient) {
    Patient::create([
        'user_id' => $patient->id,
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
        'phone' => '0987654321',
        'address' => 'Test Address',
        'emergency_contact' => 'Emergency Contact',
        'emergency_phone' => '1111111111',
        'blood_type' => 'O+',
        'medical_history' => 'None'
    ]);
    echo "Created patient relation\n";
}

echo "\n=== Test Complete ===\n";