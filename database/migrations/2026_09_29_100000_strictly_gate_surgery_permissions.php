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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. التأكد من وجود الصلاحيات الـ 16 المعتمدة لبطاقة العمليات الجراحية والرقود
        $surgeryPermissions = [
            'view surgeries',
            'create surgeries',
            'edit surgeries',
            'delete surgeries',
            'control surgeries',
            'manage surgery waiting list',
            'view surgical operations',
            'manage surgical operations',
            'view resident station',
            'view operation theater station',
            'view surgeon station',
            'view anesthesia station',
            'view nursing station',
            'manage rooms',
            'view medical devices',
            'manage medical devices',
        ];

        foreach ($surgeryPermissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. منح الصلاحيات الأساسية لكادر العمليات (surgery_staff)
        $surgeryStaff = Role::where('name', 'surgery_staff')->first();
        if ($surgeryStaff) {
            $surgeryStaff->givePermissionTo([
                'view surgeries',
                'edit surgeries',
                'manage surgery waiting list',
                'control surgeries',
                'view operation theater station',
            ]);
        }

        // 3. منح الصلاحيات لمحطات الأطباء والتمريض إن وجدت أدوارهم
        $doctorRole = Role::where('name', 'doctor')->first();
        if ($doctorRole) {
            $doctorRole->givePermissionTo(['view surgeries', 'view surgeon station']);
        }

        $nurseRole = Role::where('name', 'nurse')->first();
        if ($nurseRole) {
            $nurseRole->givePermissionTo(['view nursing station']);
        }

        // 4. دور المدير يملك كافة صلاحيات العمليات الـ 16
        $adminRoles = Role::whereIn('name', ['admin', 'admin-hsop', 'hospital_admin'])->get();
        foreach ($adminRoles as $adminRole) {
            $adminRole->givePermissionTo($surgeryPermissions);
        }

        // 5. سحب الصلاحيات المتقادمة (cancel surgeries, manage surgeries) من غير المدراء
        $obsoletePermissions = [
            'cancel surgeries',
            'manage surgeries',
        ];

        foreach ($obsoletePermissions as $obsPerm) {
            $perm = Permission::where('name', $obsPerm)->first();
            if ($perm) {
                $nonAdminRoles = Role::whereNotIn('name', ['admin', 'admin-hsop', 'hospital_admin'])->get();
                foreach ($nonAdminRoles as $role) {
                    if ($role->hasPermissionTo($perm)) {
                        $role->revokePermissionTo($perm);
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
