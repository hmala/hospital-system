<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FINAL VERIFICATION REPORT ===\n\n";

// 1. التحقق من عدم وجود أدوار متعددة
echo "1. Checking for Multiple Roles:\n";
$usersWithMultipleRoles = \App\Models\User::has('roles')->get()->filter(function($user) {
    return $user->roles->count() > 1;
});

if ($usersWithMultipleRoles->count() > 0) {
    echo "   ⚠ Found " . $usersWithMultipleRoles->count() . " users with multiple roles\n";
} else {
    echo "   ✓ All users have exactly one role\n";
}

// 2. التحقق من توافق role field مع الأدوار المعينة
echo "\n2. Checking Role Field Consistency:\n";
$inconsistent = \App\Models\User::has('roles')->get()->filter(function($user) {
    $assignedRoles = $user->roles->pluck('name')->toArray();
    return $user->role && !in_array($user->role, $assignedRoles);
});

if ($inconsistent->count() > 0) {
    echo "   ⚠ Found " . $inconsistent->count() . " users with mismatched role field\n";
} else {
    echo "   ✓ All role fields match assigned roles\n";
}

// 3. عرض ملخص لكل دور
echo "\n3. Role Summary:\n\n";

$roles = [
    'admin' => 'مدير النظام',
    'doctor' => 'طبيب',
    'patient' => 'مريض',
    'receptionist' => 'موظف استقبال',
    'cashier' => 'كاشير',
    'lab_staff' => 'موظف مختبر',
    'radiology_staff' => 'موظف أشعة',
    'pharmacy_staff' => 'موظف صيدلية',
    'surgery_staff' => 'موظف عمليات',
    'nurse' => 'ممرض',
    'emergency_staff' => 'موظف طوارئ',
    'consultation_receptionist' => 'موظف استعلامات استشارية',
];

foreach ($roles as $roleName => $roleArabic) {
    $role = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
    
    if ($role) {
        $userCount = $role->users()->count();
        $permissions = $role->permissions->pluck('name')->toArray();
        
        echo "   {$roleArabic} ({$roleName}): {$userCount} مستخدم\n";
        echo "   الصلاحيات: " . (count($permissions) > 0 ? implode(', ', $permissions) : 'لا يوجد') . "\n\n";
    }
}

// 4. فحص موظفي الأقسام الخاصة
echo "\n4. Staff Permissions Check:\n\n";

$staffRoles = [
    'cashier' => 'الكاشير',
    'lab_staff' => 'موظف المختبر',
    'radiology_staff' => 'موظف الأشعة',
    'pharmacy_staff' => 'موظف الصيدلية',
    'surgery_staff' => 'موظف العمليات',
    'emergency_staff' => 'موظف الطوارئ',
    'consultation_receptionist' => 'موظف استعلامات الاستشارية',
];

foreach ($staffRoles as $roleName => $roleArabic) {
    $users = \App\Models\User::whereHas('roles', function($q) use ($roleName) {
        $q->where('name', $roleName);
    })->get();
    
    if ($users->count() > 0) {
        foreach ($users as $user) {
            echo "   {$roleArabic} ({$user->name}):\n";
            
            // فحص الصلاحيات الحرجة
            $checks = [
                'view patients' => $user->can('view patients'),
                'view doctors' => $user->can('view doctors'),
                'view departments' => $user->can('view departments'),
                'view appointments' => $user->can('view appointments'),
            ];
            
            echo "     - view patients: " . ($checks['view patients'] ? 'YES' : 'NO') . "\n";
            echo "     - view doctors: " . ($checks['view doctors'] ? 'YES' : 'NO') . "\n";
            echo "     - view departments: " . ($checks['view departments'] ? 'YES' : 'NO') . "\n";
            echo "     - view appointments: " . ($checks['view appointments'] ? 'YES' : 'NO') . "\n";
            
            // عرض الأقسام التي ستظهر
            $sections = [];
            if ($user->can('view patients') || $user->can('view inquiries') || $user->can('view cashier')) {
                $sections[] = 'إدارة المرضى';
            }
            if ($user->can('view emergencies') || $user->can('create emergencies')) {
                $sections[] = 'الطوارئ';
            }
            if ($user->can('view doctors') || $user->can('view departments') || $user->can('manage own visits')) {
                $sections[] = 'الأطباء والعيادات';
            }
            if ($user->can('view appointments') || $user->can('view visits')) {
                $sections[] = 'المواعيد والزيارات';
            }
            if ($user->can('view surgeries') || $user->can('manage rooms')) {
                $sections[] = 'العمليات';
            }
            if ($user->can('view radiology') || $user->can('view lab tests')) {
                $sections[] = 'المختبر والأشعة';
            }
            if ($user->can('manage users') || $user->can('manage roles')) {
                $sections[] = 'الإعدادات';
            }
            
            echo "     الأقسام الظاهرة: " . (count($sections) > 0 ? implode(', ', $sections) : 'لا يوجد') . "\n\n";
        }
    }
}

echo "\n=== END OF REPORT ===\n";
