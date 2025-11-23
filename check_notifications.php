<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking Notifications in Database ===\n\n";

$totalNotifications = DB::table('notifications')->count();
echo "Total notifications: $totalNotifications\n\n";

$notifications = DB::table('notifications')
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

foreach ($notifications as $notification) {
    $data = is_string($notification->data) ? json_decode($notification->data) : $notification->data;
    echo "ID: {$notification->id}\n";
    echo "Type: {$notification->type}\n";
    echo "Notifiable Type: {$notification->notifiable_type}\n";
    echo "Notifiable ID: {$notification->notifiable_id}\n";
    echo "Title: {$data->title}\n";
    echo "Message: {$data->message}\n";
    echo "Created: {$notification->created_at}\n";
    echo "Read: " . ($notification->read_at ? 'Yes' : 'No') . "\n";
    echo "---\n";
}

echo "\n=== Checking User Notification Counts ===\n";

$users = DB::table('users')->select('id', 'name', 'email')->get();

foreach ($users as $user) {
    $unreadCount = DB::table('notifications')
        ->where('notifiable_type', 'App\\Models\\User')
        ->where('notifiable_id', $user->id)
        ->whereNull('read_at')
        ->count();

    $totalCount = DB::table('notifications')
        ->where('notifiable_type', 'App\\Models\\User')
        ->where('notifiable_id', $user->id)
        ->count();

    if ($totalCount > 0) {
        echo "{$user->name}: {$unreadCount} unread, {$totalCount} total\n";
    }
}

echo "\n=== Test Complete ===\n";