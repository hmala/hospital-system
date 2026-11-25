<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $connection = \Illuminate\Support\Facades\Schema::getConnection()->getName();
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            $permissions = [
                'process pharmacy payments',
                'view cashier pharmacy',
            ];

            foreach ($permissions as $permName) {
                Permission::on($connection)->firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            }

            // إسناد الصلاحية تلقائياً لأدوار الإدارة والكاشير
            $rolesToAssign = Role::on($connection)->whereIn('name', ['admin', 'admin-hsop', 'hospital_admin', 'cashier'])->get();
            foreach ($rolesToAssign as $role) {
                foreach ($permissions as $permName) {
                    if (! $role->hasPermissionTo($permName)) {
                        $role->givePermissionTo($permName);
                    }
                }
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // Safe guard for remote database migrations
        }
    }

    public function down(): void
    {
        $permissions = [
            'process pharmacy payments',
            'view cashier pharmacy',
        ];

        foreach ($permissions as $permName) {
            Permission::where('name', $permName)->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
