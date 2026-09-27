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

        // Ensure granular radiology inquiry permissions exist
        $granularPermissions = [
            'inquiry.create.radiology.general',
            'inquiry.create.radiology.ultrasound',
            'inquiry.create.radiology.mri',
            'inquiry.create.radiology.echo',
            'view patient history',
        ];

        foreach ($granularPermissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
        }

        // Revoke legacy umbrella permission from non-admin receptionists
        $legacyPerm = Permission::where('name', 'inquiry.create.radiology')->where('guard_name', 'web')->first();
        if ($legacyPerm) {
            $rolesToRevoke = ['receptionist', 'consultation_receptionist', 'inquiry_staff'];
            foreach ($rolesToRevoke as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role && $role->hasPermissionTo($legacyPerm)) {
                    $role->revokePermissionTo($legacyPerm);
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
        // No action needed on rollback
    }
};
