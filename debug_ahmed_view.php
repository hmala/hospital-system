<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::find(162);
auth()->login($user);

echo "User: " . $user->name . PHP_EOL;
echo "Role 'surgery_staff': " . ($user->hasRole('surgery_staff') ? 'Yes' : 'No') . PHP_EOL;
echo "Role 'الجراح': " . ($user->hasRole('الجراح') ? 'Yes' : 'No') . PHP_EOL;
echo "isAnesthesia(): " . ($user->isAnesthesia() ? 'Yes' : 'No') . PHP_EOL;
echo "isStaff(): " . ($user->isStaff() ? 'Yes' : 'No') . PHP_EOL;
echo "Doctor ID: " . ($user->doctor ? $user->doctor->id : 'None') . PHP_EOL;
echo "Doctor Type: " . ($user->doctor ? $user->doctor->type : 'None') . PHP_EOL;
echo "User Active (is_active): " . ($user->is_active ? 'Yes' : 'No') . PHP_EOL;
echo "User Status: " . ($user->status ?? 'None') . PHP_EOL;
echo "Can 'view anesthesia station': " . ($user->can('view anesthesia station') ? 'Yes' : 'No') . PHP_EOL;

// Check blade condition manually
$hasRole = $user->hasRole('التخدير');
echo "Blade condition (hasRole('التخدير')): " . ($hasRole ? 'TRUE - Form should show' : 'FALSE') . PHP_EOL;
