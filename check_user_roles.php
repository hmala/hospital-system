<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص المستخدمين والصلاحيات ===" . PHP_EOL . PHP_EOL;

$users = App\Models\User::with('roles')->limit(5)->get();

foreach ($users as $user) {
    echo "المستخدم: {$user->name} ({$user->email})" . PHP_EOL;
    echo "الصلاحيات: " . ($user->roles->pluck('name')->implode(', ') ?: 'لا توجد صلاحيات') . PHP_EOL;
    echo "hasRole('admin'): " . ($user->hasRole('admin') ? 'نعم' : 'لا') . PHP_EOL;
    echo "hasRole('lab_staff'): " . ($user->hasRole('lab_staff') ? 'نعم' : 'لا') . PHP_EOL;
    echo str_repeat('-', 50) . PHP_EOL;
}
