<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            'view medicine groups',
            'create medicine groups',
            'edit medicine groups',
            'delete medicine groups',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web',
            ]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Assign to doctor and admin roles
        foreach (['admin', 'admin-hsop', 'hospital_admin', 'doctor', 'pharmacy_staff'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'view medicine groups',
            'create medicine groups',
            'edit medicine groups',
            'delete medicine groups',
        ];

        Permission::whereIn('name', $permissions)->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
