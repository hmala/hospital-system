<?php

use Illuminate\Database\Migrations\Migration;
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

        $roles = [
            'nurse',
            'nicu_nurse',
            'icu_nurse',
            'head_nurse',
            'anesthesia',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
        }

        // ضمان منح صلاحيات إدارة جدول التمريض ومحطة التمريض لدور رئيس / مشرف التمريض
        $headNurseRole = Role::firstOrCreate(['name' => 'head_nurse', 'guard_name' => 'web']);
        $managePerm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'manage nurse availability', 'guard_name' => 'web']);
        $viewNursingPerm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view nursing station', 'guard_name' => 'web']);
        $headNurseRole->givePermissionTo([$managePerm, $viewNursingPerm]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep roles intact to prevent data loss
    }
};
