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

        $permissions = [
            'view surgical assistants',
            'manage surgical assistants',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // Grant to admin roles
        $adminRoles = Role::whereIn('name', ['admin', 'admin-hsop', 'hospital_admin'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($permissions);
        }

        // Grant to surgery staff
        $surgeryStaffRole = Role::where('name', 'surgery_staff')->first();
        if ($surgeryStaffRole) {
            $surgeryStaffRole->givePermissionTo($permissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'view surgical assistants',
            'manage surgical assistants',
        ];

        foreach ($permissions as $permName) {
            $perm = Permission::where('name', $permName)->first();
            if ($perm) {
                $perm->delete();
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
