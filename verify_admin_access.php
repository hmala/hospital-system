<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "==========================================\n";
echo "       فحص صلاحيات المدير (Admin)       \n";
echo "==========================================\n\n";

// البحث عن مستخدمين لديهم دور admin
$admins = User::role('admin')->with('roles')->get();

if ($admins->isEmpty()) {
    echo "⚠️  لا يوجد أي مستخدم لديه دور 'admin'!\n\n";
    echo "جميع المستخدمين وأدوارهم:\n";
    echo "----------------------------------------\n";
    
    $allUsers = User::with('roles')->get();
    foreach ($allUsers as $user) {
        echo "المستخدم: {$user->name} ({$user->email})\n";
        echo "  - الأدوار: " . $user->roles->pluck('name')->join(', ') . "\n";
        echo "  - النشط: " . ($user->is_active ? 'نعم' : 'لا') . "\n\n";
    }
} else {
    echo "✓ تم العثور على " . $admins->count() . " مدير/ين:\n";
    echo "----------------------------------------\n";
    
    foreach ($admins as $admin) {
        echo "\nالمستخدم: {$admin->name}\n";
        echo "  البريد: {$admin->email}\n";
        echo "  الأدوار: " . $admin->roles->pluck('name')->join(', ') . "\n";
        echo "  النشط: " . ($admin->is_active ? '✓ نعم' : '✗ لا') . "\n";
        
        // اختبار الصلاحيات
        echo "\n  اختبار الصلاحيات:\n";
        echo "    - hasRole('admin'): " . ($admin->hasRole('admin') ? '✓' : '✗') . "\n";
        echo "    - hasRole(['admin', 'doctor']): " . ($admin->hasRole(['admin', 'doctor']) ? '✓' : '✗') . "\n";
        echo "    - isAdmin(): " . ($admin->isAdmin() ? '✓' : '✗') . "\n";
        
        // اختبار Gate::before
        echo "\n  اختبار Gate (يجب أن يكون true):\n";
        try {
            \Illuminate\Support\Facades\Gate::forUser($admin);
            echo "    - Gate configured: ✓\n";
        } catch (\Exception $e) {
            echo "    - Gate error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n==========================================\n";
echo "       نصائح لحل المشكلة               \n";
echo "==========================================\n";

if ($admins->isEmpty()) {
    echo "\nلإضافة دور admin لمستخدم:\n";
    echo "1. ادخل إلى مسار Laravel:\n";
    echo "   cd c:\\wamp64\\www\\hospital-system\n\n";
    echo "2. شغل Artisan Tinker:\n";
    echo "   php artisan tinker\n\n";
    echo "3. أضف الدور:\n";
    echo "   \$user = User::find(1); // أو أي رقم مستخدم\n";
    echo "   \$user->assignRole('admin');\n\n";
    echo "أو استخدم الأمر:\n";
    echo "   php artisan db:seed --class=RolesAndPermissionsSeeder\n\n";
}

echo "\n✓ التحقق اكتمل!\n";
