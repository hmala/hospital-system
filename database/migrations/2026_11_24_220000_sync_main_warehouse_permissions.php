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

            $warehousePermissions = [
                'view suppliers',
                'create suppliers',
                'edit suppliers',
                'delete suppliers',
                'view purchases',
                'create purchases',
                'edit purchases',
                'delete purchases',
                'view inventory',
                'manage inventory',
                'view products',
                'create products',
                'edit products',
                'delete products',
                'view stock_batches',
                'view stock_movements',
                'view stock transfers',
                'view stock transfer requests',
                'manage locations',
            ];

            foreach ($warehousePermissions as $permName) {
                Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            }

            $rolesToSync = ['inventory_manager', 'admin', 'admin-hsop', 'hospital_admin', 'super_admin'];

            foreach ($rolesToSync as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role) {
                    $role->givePermissionTo($warehousePermissions);
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
