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

        $role = Role::firstOrCreate(['name' => 'consultation_receptionist']);

        $perms = [
            'process consultation payments',
            'process medical requests payments',
            'view cashier',
        ];

        foreach ($perms as $p) {
            $perm = Permission::firstOrCreate(['name' => $p]);
            if (!$role->hasPermissionTo($perm)) {
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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::where('name', 'consultation_receptionist')->first();
        if ($role) {
            $role->revokePermissionTo('process consultation payments');
            $role->revokePermissionTo('process medical requests payments');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
