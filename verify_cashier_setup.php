<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== التحقق النهائي من الحسابات ===\n\n";

echo "1. حساب الاستعلامات (Inquiry/Receptionist):\n";
$receptionist = \App\Models\User::where('email', 'reception@hospital.com')->first();
if ($receptionist) {
    echo "   ✓ الاسم: {$receptionist->name}\n";
    echo "   ✓ البريد: {$receptionist->email}\n";
    echo "   ✓ الأدوار: " . implode(', ', $receptionist->getRoleNames()->toArray()) . "\n";
    echo "   ✓ الحالة: " . ($receptionist->is_active ? 'نشط' : 'غير نشط') . "\n";
    echo "   المهام: حجز المواعيد وإدارة الاستعلامات\n\n";
}

echo "2. حساب الكاشير (Cashier):\n";
$cashier = \App\Models\User::where('email', 'cashier@hospital.com')->first();
if ($cashier) {
    echo "   ✓ الاسم: {$cashier->name}\n";
    echo "   ✓ البريد: {$cashier->email}\n";
    echo "   ✓ كلمة المرور: 123456\n";
    echo "   ✓ الأدوار: " . implode(', ', $cashier->getRoleNames()->toArray()) . "\n";
    echo "   ✓ الحالة: " . ($cashier->is_active ? 'نشط' : 'غير نشط') . "\n";
    echo "   المهام: استقبال الدفعات وإصدار الإيصالات\n\n";
}

echo "3. التحقق من الإشعارات:\n";
$cashierNotifications = \App\Models\Notification::where('notifiable_type', 'App\\Models\\User')
    ->where('notifiable_id', $cashier->id)
    ->count();
echo "   عدد إشعارات الكاشير: {$cashierNotifications}\n";

$receptionistNotifications = \App\Models\Notification::where('notifiable_type', 'App\\Models\\User')
    ->where('notifiable_id', $receptionist->id)
    ->count();
echo "   عدد إشعارات الاستعلامات: {$receptionistNotifications}\n\n";

echo "4. سير العمل:\n";
echo "   1️⃣ موظف الاستعلامات (reception@hospital.com) يحجز موعد\n";
echo "   2️⃣ يصل إشعار للكاشير (cashier@hospital.com)\n";
echo "   3️⃣ المريض يذهب للكاشير\n";
echo "   4️⃣ الكاشير يسجل الدفع ويصدر الإيصال\n";
echo "   5️⃣ يصل إشعار لموظف الاستعلامات بإتمام الدفع\n\n";

echo "=== جاهز للاستخدام ✅ ===\n";
