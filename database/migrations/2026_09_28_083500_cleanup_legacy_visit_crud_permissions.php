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
        foreach ($roles as $role) {
            foreach ($legacyVisitPermissions as $permName) {
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
