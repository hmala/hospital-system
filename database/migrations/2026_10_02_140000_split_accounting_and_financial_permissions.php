<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. إنشاء كافة الصلاحيات المالية المستقلة الجديدة
        $newPermissions = [
            'manage doctor commissions',
            'view consultant financial movements',
            'view account statements',
            'view doctor accounts',
            'view emergency analytics',
            'view emergency financial movements',
            'view emergency statements',
            'view emergency doctor accounts',
            'view diagnostic analytics',
        ];

        foreach ($newPermissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. منح الصلاحيات الجديدة لأدوار الإدارة والمحاسب
        $adminAndAccountantRoles = Role::whereIn('name', ['admin', 'admin-hsop', 'hospital_admin', 'accountant'])->get();
        foreach ($adminAndAccountantRoles as $role) {
            foreach ($newPermissions as $permName) {
                $permission = Permission::where('name', $permName)->where('guard_name', 'web')->first();
                if ($permission && !$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }

        // 3. لأي دور آخر كان يمتلك سابقاً 'view cashier reports'، نقوم بمنحه الصلاحيات الجديدة لضمان عدم كسر الوصول حتى يتم تخصيصها له
        $rolesWithOldReportPerm = Role::whereNotIn('name', ['admin', 'admin-hsop', 'hospital_admin', 'accountant'])->get();
        $oldReportPerm = Permission::where('name', 'view cashier reports')->first();

        if ($oldReportPerm) {
            foreach ($rolesWithOldReportPerm as $role) {
                if ($role->hasPermissionTo($oldReportPerm)) {
                    foreach ($newPermissions as $permName) {
                        $permission = Permission::where('name', $permName)->where('guard_name', 'web')->first();
                        if ($permission && !$role->hasPermissionTo($permission)) {
                            $role->givePermissionTo($permission);
                        }
                    }
                }
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No action needed
    }
};
