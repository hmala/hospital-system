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

        // 1. Ensure new permissions exist
        $newPerms = [
            'manage health insurance',
            'process refunds',
        ];
        foreach ($newPerms as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // 2. Grant manage health insurance to accountant and admin
        $adminRoles = Role::whereIn('name', ['admin', 'admin-hsop', 'accountant'])->get();
        foreach ($adminRoles as $role) {
            if (!$role->hasPermissionTo('manage health insurance')) {
                $role->givePermissionTo('manage health insurance');
            }
        }

        // Grant process refunds to cashier and admin
        $cashierRoles = Role::whereIn('name', ['admin', 'admin-hsop', 'cashier'])->get();
        foreach ($cashierRoles as $role) {
            if (!$role->hasPermissionTo('process refunds')) {
                $role->givePermissionTo('process refunds');
            }
        }

        // 3. Revoke obsolete cashier permissions from non-admin roles
        $obsoletePermissions = [
            'view cashier appointments',
            'view cashier medical requests',
            'view cashier emergency',
            'process payments',
            'create payments',
            'edit payments',
            'view payments',
            'view doctor profits',
        ];

        $nonAdminRoles = Role::whereNotIn('name', ['admin', 'admin-hsop'])->get();
        foreach ($nonAdminRoles as $role) {
            foreach ($obsoletePermissions as $permName) {
                if ($role->hasPermissionTo($permName)) {
                    $role->revokePermissionTo($permName);
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
