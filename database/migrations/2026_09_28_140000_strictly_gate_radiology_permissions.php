<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. إعادة ضبط كاش الصلاحيات
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. ضمان وجود الصلاحيات الستة الأساسية لقسم الأشعة
        $radiologyPermissions = [
            'view radiology',
            'create radiology',
            'edit radiology',
            'delete radiology',
            'process radiology requests',
            'manage radiology types',
        ];

        foreach ($radiologyPermissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 3. منح الصلاحيات للأدوار المتخصصة
        $radiologyRoles = [
            'radiology_staff',
            'radiology_general',
            'radiology_ultrasound',
            'radiology_mri',
            'radiology_echo',
        ];

        foreach ($radiologyRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo(['view radiology', 'process radiology requests']);
            }
        }

        // 4. دور المدير يملك كافة صلاحيات الأشعة
        $adminRoles = Role::whereIn('name', ['admin', 'admin-hsop', 'hospital_admin'])->get();
        foreach ($adminRoles as $adminRole) {
            $adminRole->givePermissionTo($radiologyPermissions);
        }

        // 5. سحب الصلاحيات المتقادمة إن وجدت من غير المدراء
        $obsoletePermissions = [
            'create radiology types',
            'edit radiology types',
            'delete radiology types',
            'view radiology types',
        ];

        foreach ($obsoletePermissions as $obsPerm) {
            $perm = Permission::where('name', $obsPerm)->first();
            if ($perm) {
                $nonAdminRoles = Role::whereNotIn('name', ['admin', 'admin-hsop', 'hospital_admin'])->get();
                foreach ($nonAdminRoles as $role) {
                    if ($role->hasPermissionTo($obsPerm)) {
                        $role->revokePermissionTo($obsPerm);
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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
