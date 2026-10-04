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
        $healthInsurancePerm = Permission::where('name', 'manage health insurance')->where('guard_name', 'web')->first();
        if ($healthInsurancePerm) {
            $adminRoles = Role::whereIn('name', ['admin', 'admin-hsop', 'accountant'])->get();
            foreach ($adminRoles as $role) {
                if (!$role->hasPermissionTo($healthInsurancePerm)) {
                    $role->givePermissionTo($healthInsurancePerm);
                }
            }
        }

        // Grant process refunds to cashier and admin
        $refundPerm = Permission::where('name', 'process refunds')->where('guard_name', 'web')->first();
        if ($refundPerm) {
            $cashierRoles = Role::whereIn('name', ['admin', 'admin-hsop', 'cashier'])->get();
            foreach ($cashierRoles as $role) {
                if (!$role->hasPermissionTo($refundPerm)) {
                    $role->givePermissionTo($refundPerm);
                }
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
        foreach ($obsoletePermissions as $permName) {
            $perm = Permission::where('name', $permName)->first();
            if ($perm) {
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
        // No action needed
    }
};
