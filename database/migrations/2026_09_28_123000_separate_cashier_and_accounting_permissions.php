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

        // 1. Create dedicated permissions
        $manageInsurance = Permission::firstOrCreate(['name' => 'manage health insurance', 'guard_name' => 'web']);
        $processRefunds = Permission::firstOrCreate(['name' => 'process refunds', 'guard_name' => 'web']);

        // 2. Assign to admin & accountant
        $adminRoles = Role::whereIn('name', ['admin', 'admin-hsop', 'hospital_admin'])->get();
        foreach ($adminRoles as $adminRole) {
            $adminRole->givePermissionTo($manageInsurance);
            $adminRole->givePermissionTo($processRefunds);
        }

        $accountant = Role::where('name', 'accountant')->where('guard_name', 'web')->first();
        if ($accountant) {
            $accountant->givePermissionTo($manageInsurance);
        }

        $cashier = Role::where('name', 'cashier')->where('guard_name', 'web')->first();
        if ($cashier) {
            $cashier->givePermissionTo($processRefunds);
        }

        // 3. Revoke redundant/ghost permissions from non-admin roles
        $ghostPermissions = [
            'view cashier appointments',
            'view cashier medical requests',
            'view cashier emergency',
            'process payments',
            'view payments',
            'create payments',
            'edit payments',
        ];

        $nonAdminRoles = Role::whereNotIn('name', ['admin', 'admin-hsop', 'hospital_admin'])->get();
        foreach ($nonAdminRoles as $role) {
            foreach ($ghostPermissions as $permName) {
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
