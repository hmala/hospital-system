<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;
use App\Models\User;

echo "=== Testing Notification System ===\n\n";

// Get all visits
$visits = Visit::all();
echo "Total visits: " . $visits->count() . "\n";

foreach ($visits as $visit) {
    echo "Visit ID: {$visit->id}, Status: {$visit->status}\n";
}

// Get users
$users = User::all();
echo "\nTotal users: " . $users->count() . "\n";

foreach ($users as $user) {
    echo "User: {$user->name} ({$user->email}) - Role: {$user->role}\n";
}

echo "\n=== Creating Test Visit for Cancellation ===\n";

// Create a new visit for testing cancellation
$doctor = User::where('role', 'doctor')->first();
$patient = User::where('role', 'patient')->first();

if ($doctor && $patient && $doctor->doctor && $patient->patient) {
    $newVisit = Visit::create([
        'patient_id' => $patient->patient->id,
        'doctor_id' => $doctor->doctor->id,
        'department_id' => $doctor->doctor->department_id,
        'visit_date' => now()->addDays(1)->format('Y-m-d'),
        'visit_time' => '10:00',
        'visit_type' => 'checkup',
        'chief_complaint' => 'Test visit for cancellation',
        'status' => 'scheduled'
    ]);

    echo "Created test visit ID: {$newVisit->id}\n";

    // Now test cancellation
    echo "\n=== Testing Visit Cancellation ===\n";
    $newVisit->update(['status' => 'cancelled']);
    $patient->notify(new \App\Notifications\VisitCancelledNotification($newVisit));

    echo "Visit cancelled and notification sent!\n";

    // Check notifications
    $notifications = $patient->notifications()->get();
    echo "Patient has " . $notifications->count() . " notifications\n";

    foreach ($notifications as $notification) {
        $data = $notification->data;
        echo "- " . $data['title'] . " (" . ($notification->read_at ? 'read' : 'unread') . ")\n";
    }

} else {
    echo "Could not find doctor or patient for testing (Doctor: " . ($doctor ? 'found' : 'not found') . ", Patient: " . ($patient ? 'found' : 'not found') . ")\n";
}

echo "\n=== Testing Medical Request Creation ===\n";

// Create a test medical request
$testVisit = Visit::first();

if ($testVisit) {
    $request = new \App\Models\Request([
        'visit_id' => $testVisit->id,
        'type' => 'lab',
        'description' => 'Test blood work',
        'details' => ['tests' => ['CBC', 'Blood Sugar']],
        'status' => 'pending'
    ]);

    $request->save();

    echo "Created medical request ID: {$request->id}\n";

    // Send notification to lab technicians
    $labTechs = User::where('role', 'lab_staff')->get();

    foreach ($labTechs as $tech) {
        $tech->notify(new \App\Notifications\RequestCreatedNotification($request));
        echo "Notification sent to lab technician: {$tech->name}\n";
    }

} else {
    echo "No visits found to create medical request\n";
}

echo "\n=== Test Complete ===\n";