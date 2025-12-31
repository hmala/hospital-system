<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'surgery@hospital.com';
$password = '12345678';

$user = User::where('email', $email)->first();

if ($user) {
    echo "المستخدم موجود:\n";
    echo "ID: {$user->id}\n";
    echo "الاسم: {$user->name}\n";
    echo "البريد: {$user->email}\n";
    
    // تحديث كلمة المرور
    $user->password = Hash::make($password);
    $user->save();
    echo "\n✓ تم تحديث كلمة المرور إلى: $password\n";
    
    // التحقق من الصلاحية
    $roles = $user->roles->pluck('name')->toArray();
    echo "الصلاحيات: " . implode(', ', $roles) . "\n";
    
    if (!in_array('surgery_staff', $roles)) {
        $user->assignRole('surgery_staff');
        echo "✓ تم إضافة صلاحية surgery_staff\n";
    }
} else {
    echo "إنشاء مستخدم جديد...\n";
    
    $user = User::create([
        'name' => 'موظف العمليات',
        'email' => $email,
        'password' => Hash::make($password),
    ]);
    
    $user->assignRole('surgery_staff');
    
    echo "✓ تم إنشاء المستخدم بنجاح!\n";
    echo "ID: {$user->id}\n";
    echo "الاسم: {$user->name}\n";
    echo "البريد: {$user->email}\n";
    echo "كلمة المرور: $password\n";
    echo "الصلاحية: surgery_staff\n";
}

echo "\n========================================\n";
echo "يمكنك تسجيل الدخول بـ:\n";
echo "البريد الإلكتروني: $email\n";
echo "كلمة المرور: $password\n";
echo "========================================\n";
