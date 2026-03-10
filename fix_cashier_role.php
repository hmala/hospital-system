<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// إيجاد جميع المستخدمين الذين لديهم دور cashier في spatie
$cashierUsers = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'cashier');
})->get();

echo "=== Fixing Cashier Users ===\n\n";

foreach ($cashierUsers as $user) {
    echo "User: {$user->name} (ID: {$user->id})\n";
    echo "Before - Role field: {$user->role}\n";
    echo "Before - Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
    
    // تحديث حقل role في جدول users
    $user->role = 'cashier';
    $user->save();
    
    // إزالة جميع الأدوار ثم إعادة تعيين cashier فقط
    $user->syncRoles(['cashier']);
    
    // إعادة تحميل العلاقات
    $user->load('roles');
    
    echo "After - Role field: {$user->role}\n";
    echo "After - Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
    echo "After - Permissions: " . $user->getAllPermissions()->pluck('name')->implode(', ') . "\n";
    echo "\n✓ Fixed!\n\n";
}

echo "Done!\n";
