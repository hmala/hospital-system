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

        $legacyReferralPermissions = [
            'view referrals',
            'create referrals',
            'manage referrals',
        ];

        // Revoke legacy referral permissions from all non-admin roles
        $roles = Role::whereNotIn('name', ['admin', 'admin-hsop'])->get();
        foreach ($legacyReferralPermissions as $permName) {
            $perm = Permission::where('name', $permName)->first();
            if ($perm) {
                foreach ($roles as $role) {
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
