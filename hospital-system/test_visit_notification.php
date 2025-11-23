<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;
use App\Models\User;

echo "=== Testing Visit Created Notification ===\n\n";

// Get first visit
$visit = Visit::first();

if ($visit) {
    echo "Found visit ID: {$visit->id}\n";
    echo "Doctor: {$visit->doctor->user->name}\n";
    echo "Patient: {$visit->patient->user->name}\n";
    echo "Date: {$visit->visit_date}\n";
    echo "Time: {$visit->visit_time}\n\n";

    // Send notification to doctor
    $doctor = $visit->doctor->user;
    $doctor->notify(new \App\Notifications\VisitCreatedNotification($visit));

    echo "✅ Notification sent to doctor: {$doctor->name}\n";

    // Check notifications
    $notifications = $doctor->notifications()->get();
    echo "Doctor has " . $notifications->count() . " notifications\n";

    foreach ($notifications as $notification) {
        $data = $notification->data;
        echo "- " . $data['title'] . " (" . ($notification->read_at ? 'read' : 'unread') . ")\n";
    }

} else {
    echo "❌ No visits found to test with\n";
}

echo "\n=== Test Complete ===\n";