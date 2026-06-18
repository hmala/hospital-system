<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = App\Models\User::where('name', 'احمد شهاب')->first();

if ($user) {
    echo "User: " . $user->name . PHP_EOL;
    echo "Roles: " . $user->roles->pluck('name')->implode(', ') . PHP_EOL;
    echo "Has Role 'التخدير': " . ($user->hasRole('التخدير') ? 'Yes' : 'No') . PHP_EOL;
    echo "Has Permission 'view anesthesia station': " . ($user->can('view anesthesia station') ? 'Yes' : 'No') . PHP_EOL;
    echo "Has Permission 'view surgeries': " . ($user->can('view surgeries') ? 'Yes' : 'No') . PHP_EOL;
    echo "Has Permission 'edit surgeries': " . ($user->can('edit surgeries') ? 'Yes' : 'No') . PHP_EOL;
} else {
    echo "User not found" . PHP_EOL;
}
