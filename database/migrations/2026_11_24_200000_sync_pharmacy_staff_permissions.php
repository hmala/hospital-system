<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        try {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $permissions = [
                'view pharmacy',
                'manage medicines',
                'create medicines',
                'edit medicines',
                'delete medicines',
                'manage pharmacy services',
                'import medicines',
                'dispense medications',
                'process pharmacy requests',
            ];

            foreach ($permissions as $permName) {
                Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            }

            $rolesToSync = ['pharmacy_staff', 'pharmacist', 'admin', 'admin-hsop', 'hospital_admin', 'super_admin'];

            foreach ($rolesToSync as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role) {
                    $role->givePermissionTo($permissions);
                }
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // تجاهل أي خطأ عابر
        }
    }

    public function down(): void
    {
        // عدم الحذف لسلامة الصلاحيات
    }
};
