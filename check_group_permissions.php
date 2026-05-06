<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

echo "=== Checking Lab Test Group Permissions ===\n\n";

// 1. Check if permissions exist
echo "1. Permissions containing 'group':\n";
$groupPerms = Permission::where('name', 'like', '%group%')->get();
if ($groupPerms->isEmpty()) {
    echo "   ❌ No group-related permissions found!\n\n";
} else {
    foreach ($groupPerms as $perm) {
        echo "   ✓ {$perm->name} (ID: {$perm->id})\n";
    }
    echo "\n";
}

// 2. Check lab test specific permissions
echo "2. Lab test group permissions:\n";
$labGroupPerms = Permission::where('name', 'like', '%lab test group%')->get();
if ($labGroupPerms->isEmpty()) {
    echo "   ❌ No 'lab test group' permissions found!\n";
    echo "   These permissions need to be created:\n";
    echo "      - view lab test groups\n";
    echo "      - create lab test groups\n";
    echo "      - edit lab test groups\n";
    echo "      - delete lab test groups\n\n";
} else {
    foreach ($labGroupPerms as $perm) {
        echo "   ✓ {$perm->name}\n";
    }
    echo "\n";
}

// 3. Check doctor role
echo "3. Doctor role permissions:\n";
$doctorRole = Role::where('name', 'doctor')->first();
if (!$doctorRole) {
    echo "   ❌ Doctor role not found!\n\n";
} else {
    echo "   Total permissions: {$doctorRole->permissions->count()}\n";
    echo "   All doctor permissions:\n";
    foreach ($doctorRole->permissions as $perm) {
        echo "      - {$perm->name}\n";
    }
    echo "\n";
    
    $groupPermissions = $doctorRole->permissions->filter(function($p) {
        return str_contains($p->name, 'group');
    });
    
    if ($groupPermissions->isEmpty()) {
        echo "   ❌ No group permissions assigned to doctor role\n\n";
    } else {
        echo "   Group permissions assigned:\n";
        foreach ($groupPermissions as $perm) {
            echo "      ✓ {$perm->name}\n";
        }
        echo "\n";
    }
}

// 4. Check a specific doctor user
echo "4. Checking a doctor user:\n";
$doctorUser = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'doctor');
})->first();

if (!$doctorUser) {
    echo "   ❌ No doctor user found in database\n";
} else {
    echo "   Doctor: {$doctorUser->name} ({$doctorUser->email})\n";
    echo "   Roles: " . $doctorUser->roles->pluck('name')->implode(', ') . "\n";
    
    $hasViewPerm = $doctorUser->can('view lab test groups');
    $hasCreatePerm = $doctorUser->can('create lab test groups');
    $hasEditPerm = $doctorUser->can('edit lab test groups');
    $hasDeletePerm = $doctorUser->can('delete lab test groups');
    
    echo "   Can view lab test groups? " . ($hasViewPerm ? '✓ YES' : '❌ NO') . "\n";
    echo "   Can create lab test groups? " . ($hasCreatePerm ? '✓ YES' : '❌ NO') . "\n";
    echo "   Can edit lab test groups? " . ($hasEditPerm ? '✓ YES' : '❌ NO') . "\n";
    echo "   Can delete lab test groups? " . ($hasDeletePerm ? '✓ YES' : '❌ NO') . "\n";
}

echo "\n=== Summary ===\n";
echo "If permissions are missing, run: php artisan db:seed --class=RolesAndPermissionsSeeder\n";
echo "If permissions exist but not assigned to doctor, update via roles management UI or seeder.\n";
