<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// البحث عن مستخدم الكاشير
$cashierUsers = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'cashier');
})->get();

echo "=== Cashier Users ===\n\n";

foreach ($cashierUsers as $user) {
    echo "User: {$user->name} (ID: {$user->id})\n";
    echo "Role field: {$user->role}\n";
    echo "Assigned Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
    echo "Permissions: " . $user->getAllPermissions()->pluck('name')->implode(', ') . "\n";
    echo "\n";
}

// فحص الصلاحيات المحددة
if ($cashierUsers->isNotEmpty()) {
    $user = $cashierUsers->first();
    echo "=== Permission Checks for {$user->name} ===\n";
    echo "view cashier: " . ($user->can('view cashier') ? 'YES' : 'NO') . "\n";
    echo "view patients: " . ($user->can('view patients') ? 'YES' : 'NO') . "\n";
    echo "view doctors: " . ($user->can('view doctors') ? 'YES' : 'NO') . "\n";
    echo "view departments: " . ($user->can('view departments') ? 'YES' : 'NO') . "\n";
}
