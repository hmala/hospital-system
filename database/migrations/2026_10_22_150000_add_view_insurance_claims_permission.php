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

        $perm = Permission::firstOrCreate(['name' => 'view insurance claims', 'guard_name' => 'web']);

        $roles = Role::whereIn('name', ['admin', 'admin-hsop', 'accountant'])->get();
        foreach ($roles as $role) {
            if (! $role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $perm = Permission::where('name', 'view insurance claims')->where('guard_name', 'web')->first();
        if ($perm) {
            $perm->delete();
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
