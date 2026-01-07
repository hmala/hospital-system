<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== إضافة صلاحيات للكاشير ===\n\n";

// الحصول على دور الكاشير
$cashierRole = \Spatie\Permission\Models\Role::where('name', 'cashier')->first();

if (!$cashierRole) {
    echo "✗ دور cashier غير موجود!\n";
    exit;
}

echo "1. الصلاحيات المطلوبة للكاشير:\n";

$permissions = [
    'view appointments',
    'view patients',
    'create payments',
    'view payments',
    'edit payments',
];

foreach ($permissions as $permissionName) {
    // إنشاء الصلاحية إذا لم تكن موجودة
    $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permissionName]);
    
    // إضافة الصلاحية للدور
    if (!$cashierRole->hasPermissionTo($permissionName)) {
        $cashierRole->givePermissionTo($permissionName);
        echo "   ✓ تمت إضافة: {$permissionName}\n";
    } else {
        echo "   - موجود مسبقاً: {$permissionName}\n";
    }
}

echo "\n2. جميع صلاحيات دور الكاشير:\n";
$allPermissions = $cashierRole->permissions->pluck('name');
foreach ($allPermissions as $perm) {
    echo "   - {$perm}\n";
}

echo "\n=== تم بنجاح ✅ ===\n";
