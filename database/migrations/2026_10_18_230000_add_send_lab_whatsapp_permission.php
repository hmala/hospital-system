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

        $perm = Permission::firstOrCreate(['name' => 'send lab whatsapp', 'guard_name' => 'web']);

        $targetRoles = ['admin', 'admin-hsop', 'hospital_admin', 'lab_staff'];
        foreach ($targetRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                if (!$role->hasPermissionTo($perm)) {
                    $role->givePermissionTo($perm);
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

        Permission::where('name', 'send lab whatsapp')->delete();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
