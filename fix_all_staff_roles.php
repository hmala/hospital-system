<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Fixing All Users with Multiple Roles ===\n\n";

$mapping = [
    'موظف المختبر' => 'lab_staff',
    'موظف الأشعة' => 'radiology_staff',
    'موظف الصيدلية' => 'pharmacy_staff',
    'موظف العمليات' => 'surgery_staff',
    'موظف استعلامات الاستشارية' => 'consultation_receptionist',
];

foreach ($mapping as $userName => $correctRole) {
    $user = \App\Models\User::where('name', $userName)->first();
    
    if ($user) {
        echo "Fixing: {$userName}\n";
        echo "  Before - Role field: {$user->role}\n";
        echo "  Before - Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
        
        // تحديث حقل role
        $user->role = $correctRole;
        $user->save();
        
        // تعيين الدور الصحيح فقط
        $user->syncRoles([$correctRole]);
        
        // إعادة تحميل
        $user->load('roles');
        
        echo "  After - Role field: {$user->role}\n";
        echo "  After - Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
        echo "  After - Permissions: " . $user->getAllPermissions()->pluck('name')->implode(', ') . "\n";
        echo "  ✓ Fixed!\n\n";
    } else {
        echo "✗ User '{$userName}' not found!\n\n";
    }
}

echo "=== Verification ===\n\n";

// التحقق من عدم وجود مستخدمين بأدوار متعددة
$usersWithMultipleRoles = \App\Models\User::has('roles')->get()->filter(function($user) {
    return $user->roles->count() > 1;
});

if ($usersWithMultipleRoles->count() > 0) {
    echo "⚠ Still found " . $usersWithMultipleRoles->count() . " users with multiple roles:\n";
    foreach ($usersWithMultipleRoles as $user) {
        echo "  - {$user->name}: " . $user->roles->pluck('name')->implode(', ') . "\n";
    }
} else {
    echo "✓ No users with multiple roles!\n";
}

echo "\n=== Done ===\n";
