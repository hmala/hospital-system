<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $basePermissions = [
            'view hr',
            'view employees',
        ];

        $permissions = [
            'manage hr bulk actions',
            'approve hr action cancellation',
            'approve department leaves',
            'approve admin leaves',
            'manage department schedules',
            'final approve hr schedules',
        ];

        foreach (array_merge($basePermissions, $permissions) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // دور مدير القسم
        $deptManagerRole = Role::firstOrCreate(['name' => 'department_manager', 'guard_name' => 'web']);
        $deptManagerRole->givePermissionTo([
            'view hr',
            'view employees',
            'manage department schedules',
            'approve department leaves',
        ]);

        // صلاحيات الإدارة العليا (مدير الإدارة)
        $adminRoles = Role::whereIn('name', ['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissions);
        }

        // صلاحيات مدير الموارد البشرية
        $hrManagerRole = Role::where('name', 'hr_manager')->first();
        if ($hrManagerRole) {
            $hrManagerRole->givePermissionTo([
                'manage hr bulk actions',
                'final approve hr schedules',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
