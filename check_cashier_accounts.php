<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== حسابات الكاشير في النظام ===\n\n";

// التحقق من الأدوار المتاحة
echo "1. الأدوار المتاحة في النظام:\n";
$roles = \Spatie\Permission\Models\Role::all();
foreach ($roles as $role) {
    $usersCount = \App\Models\User::role($role->name)->count();
    echo "   - {$role->name} ({$usersCount} مستخدم)\n";
}

echo "\n2. المستخدمين بدور Receptionist (الكاشير):\n";
$receptionists = \App\Models\User::role('receptionist')->get();
if ($receptionists->count() > 0) {
    foreach ($receptionists as $user) {
        echo "   - الاسم: {$user->name}\n";
        echo "     البريد: {$user->email}\n";
        echo "     الحالة: " . ($user->is_active ? 'نشط' : 'غير نشط') . "\n\n";
    }
} else {
    echo "   لا يوجد مستخدمين بدور receptionist\n\n";
}

// التحقق من وجود دور cashier
echo "3. التحقق من دور Cashier:\n";
$cashierRole = \Spatie\Permission\Models\Role::where('name', 'cashier')->first();
if ($cashierRole) {
    echo "   ✓ دور cashier موجود\n";
    $cashiers = \App\Models\User::role('cashier')->get();
    if ($cashiers->count() > 0) {
        echo "   المستخدمين:\n";
        foreach ($cashiers as $user) {
            echo "   - {$user->name} ({$user->email})\n";
        }
    } else {
        echo "   لا يوجد مستخدمين بهذا الدور\n";
    }
} else {
    echo "   ✗ دور cashier غير موجود\n";
    echo "   يستخدم النظام حالياً دور 'receptionist' للكاشير\n";
}

echo "\n4. معلومات إضافية:\n";
echo "   - إجمالي المستخدمين: " . \App\Models\User::count() . "\n";
echo "   - المستخدمين النشطين: " . \App\Models\User::where('is_active', true)->count() . "\n";

echo "\n=== انتهى التحقق ===\n";
