<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== إنشاء دور وحساب الكاشير ===\n\n";

// 1. إنشاء دور cashier
echo "1. إنشاء دور الكاشير...\n";
$cashierRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'cashier']);
echo "   ✓ تم إنشاء/العثور على دور: cashier\n\n";

// 2. إنشاء حساب مستخدم للكاشير
echo "2. إنشاء حساب الكاشير...\n";

// التحقق من عدم وجود الحساب مسبقاً
$existingCashier = \App\Models\User::where('email', 'cashier@hospital.com')->first();
if ($existingCashier) {
    echo "   ⚠ الحساب موجود مسبقاً، سيتم تحديث الدور فقط\n";
    $cashier = $existingCashier;
    // إزالة جميع الأدوار القديمة
    $cashier->syncRoles([]);
} else {
    // إنشاء المستخدم
    $cashier = \App\Models\User::create([
        'name' => 'موظف الكاشير',
        'email' => 'cashier@hospital.com',
        'password' => bcrypt('123456'),
        'is_active' => true,
    ]);
    echo "   ✓ تم إنشاء الحساب: cashier@hospital.com\n";
}

// إضافة الدور
$cashier->assignRole('cashier');
echo "   ✓ تم تعيين دور cashier للمستخدم\n\n";

echo "3. معلومات الحساب:\n";
echo "   الاسم: {$cashier->name}\n";
echo "   البريد: {$cashier->email}\n";
echo "   كلمة المرور: 123456\n";
echo "   الدور: cashier\n";
echo "   الحالة: " . ($cashier->is_active ? 'نشط' : 'غير نشط') . "\n\n";

echo "4. تحديث حساب الاستعلامات...\n";
$receptionist = \App\Models\User::where('email', 'reception@hospital.com')->first();
if ($receptionist) {
    echo "   الاسم: {$receptionist->name}\n";
    echo "   البريد: {$receptionist->email}\n";
    echo "   الدور: receptionist (موظف الاستعلامات)\n";
    echo "   ✓ سيبقى هذا الحساب لحجز المواعيد فقط\n\n";
}

echo "=== تم الإنشاء بنجاح ===\n";
echo "\nالآن لديك:\n";
echo "1. حساب الاستعلامات: reception@hospital.com (لحجز المواعيد)\n";
echo "2. حساب الكاشير: cashier@hospital.com (لمعالجة الدفعات)\n";
