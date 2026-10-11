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

        $viewPerm = Permission::firstOrCreate(['name' => 'view activity logs', 'guard_name' => 'web']);
        $deletePerm = Permission::firstOrCreate(['name' => 'delete activity logs', 'guard_name' => 'web']);

        $adminRoles = ['admin', 'super_admin', 'hospital_admin'];
        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                if (!$role->hasPermissionTo($viewPerm)) {
                    $role->givePermissionTo($viewPerm);
                }
                if (!$role->hasPermissionTo($deletePerm)) {
                    $role->givePermissionTo($deletePerm);
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

        Permission::whereIn('name', ['view activity logs', 'delete activity logs'])->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
