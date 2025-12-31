<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Current Users and Their Roles:\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("%-5s | %-30s | %-30s | %-15s\n", "ID", "Name", "Email", "Role");
echo str_repeat("-", 80) . "\n";

$users = DB::table('users')->select('id', 'name', 'email', 'role')->get();

foreach ($users as $user) {
    echo sprintf("%-5s | %-30s | %-30s | %-15s\n", 
        $user->id, 
        $user->name, 
        $user->email, 
        $user->role
    );
}

echo str_repeat("-", 80) . "\n";
echo "Total users: " . $users->count() . "\n";
