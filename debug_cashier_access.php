<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== فحص حساب الكاشير ===\n\n";

$cashier = \App\Models\User::where('email', 'cashier@hospital.com')->first();

if (!$cashier) {
    echo "✗ حساب الكاشير غير موجود!\n";
    exit;
}

echo "1. معلومات الحساب:\n";
echo "   الاسم: {$cashier->name}\n";
echo "   البريد: {$cashier->email}\n";
echo "   الحالة: " . ($cashier->is_active ? 'نشط ✓' : 'غير نشط ✗') . "\n\n";

echo "2. الأدوار (Roles):\n";
$roles = $cashier->getRoleNames();
if ($roles->count() > 0) {
    foreach ($roles as $role) {
        echo "   ✓ {$role}\n";
    }
} else {
    echo "   ✗ لا يوجد أدوار!\n";
}
echo "\n";

echo "3. الصلاحيات (Permissions):\n";
$permissions = $cashier->getAllPermissions();
if ($permissions->count() > 0) {
    foreach ($permissions as $permission) {
        echo "   ✓ {$permission->name}\n";
    }
} else {
    echo "   ✗ لا يوجد صلاحيات!\n";
}
echo "\n";

echo "4. فحص الأدوار المطلوبة:\n";
echo "   hasRole('cashier'): " . ($cashier->hasRole('cashier') ? '✓ نعم' : '✗ لا') . "\n";
echo "   hasRole('admin'): " . ($cashier->hasRole('admin') ? '✓ نعم' : '✗ لا') . "\n";
echo "   hasRole('receptionist'): " . ($cashier->hasRole('receptionist') ? '✓ نعم' : '✗ لا') . "\n\n";

echo "5. فحص الصلاحيات المطلوبة:\n";
$requiredPermissions = ['view appointments', 'view patients', 'create payments'];
foreach ($requiredPermissions as $perm) {
    $has = $cashier->hasPermissionTo($perm);
    echo "   {$perm}: " . ($has ? '✓' : '✗') . "\n";
}

echo "\n=== انتهى الفحص ===\n";
