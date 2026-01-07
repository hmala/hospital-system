<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cashier = \App\Models\User::where('email', 'cashier@hospital.com')->first();
$cashier->is_active = true;
$cashier->save();

echo "✓ تم تفعيل حساب الكاشير\n";
echo "\nمعلومات الحساب:\n";
echo "الاسم: {$cashier->name}\n";
echo "البريد: {$cashier->email}\n";
echo "كلمة المرور: 123456\n";
echo "الحالة: نشط\n";
