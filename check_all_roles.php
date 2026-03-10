<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking All Users with Multiple Roles ===\n\n";

// جلب جميع المستخدمين الذين لديهم أكثر من دور واحد
$users = \App\Models\User::has('roles')->with('roles')->get();

$problemUsers = [];

foreach ($users as $user) {
    $assignedRoles = $user->roles->pluck('name')->toArray();
    $roleField = $user->role;
    
    // التحقق من المشاكل:
    // 1. المستخدم لديه أكثر من دور واحد
    // 2. الدور في حقل role لا يتطابق مع الأدوار المعينة
    
    $hasMultipleRoles = count($assignedRoles) > 1;
    $mismatch = !in_array($roleField, $assignedRoles) && $roleField !== null;
    
    if ($hasMultipleRoles || $mismatch) {
        $problemUsers[] = [
            'id' => $user->id,
            'name' => $user->name,
            'role_field' => $roleField,
            'assigned_roles' => $assignedRoles,
            'issue' => $hasMultipleRoles ? 'Multiple Roles' : 'Mismatch'
        ];
    }
}

if (count($problemUsers) > 0) {
    echo "Found " . count($problemUsers) . " users with issues:\n\n";
    
    foreach ($problemUsers as $user) {
        echo "ID: {$user['id']} - {$user['name']}\n";
        echo "  Role field: {$user['role_field']}\n";
        echo "  Assigned Roles: " . implode(', ', $user['assigned_roles']) . "\n";
        echo "  Issue: {$user['issue']}\n\n";
    }
} else {
    echo "✓ No users with multiple roles or mismatches found!\n";
}

// عرض ملخص الأدوار
echo "\n=== Role Summary ===\n\n";

$roles = \Spatie\Permission\Models\Role::withCount('users')->get();

foreach ($roles as $role) {
    echo "{$role->name}: {$role->users_count} users\n";
    
    // عرض المستخدمين الذين role field = اسم الدور
    $usersWithRoleField = \App\Models\User::where('role', $role->name)->count();
    echo "  - Users with role field = '{$role->name}': {$usersWithRoleField}\n";
    
    // عرض المستخدمين المعينين من خلال Spatie
    $assignedUsers = $role->users()->count();
    echo "  - Users assigned via Spatie: {$assignedUsers}\n\n";
}

// التحقق من الأدوار غير المستخدمة
echo "\n=== Checking Unused Roles ===\n\n";

$allRoleNames = $roles->pluck('name')->toArray();
$usedRoleFieldValues = \App\Models\User::whereNotNull('role')->distinct()->pluck('role')->toArray();

$unusedRoles = array_diff($allRoleNames, $usedRoleFieldValues);

if (count($unusedRoles) > 0) {
    echo "Roles not used in 'role' field:\n";
    foreach ($unusedRoles as $role) {
        echo "  - {$role}\n";
    }
} else {
    echo "All roles are being used.\n";
}

echo "\n=== Done ===\n";
