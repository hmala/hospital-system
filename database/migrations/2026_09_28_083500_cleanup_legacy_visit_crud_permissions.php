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

        $legacyVisitPermissions = [
            'create visits',
            'edit visits',
            'delete visits',
        ];

        // Revoke legacy manual visit CRUD from non-admin roles
        $roles = Role::whereNotIn('name', ['admin', 'admin-hsop'])->get();
        foreach ($legacyVisitPermissions as $permName) {
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
