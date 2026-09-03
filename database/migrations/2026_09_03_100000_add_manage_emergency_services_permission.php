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
        // إنشاء الصلاحية
        $permission = Permission::firstOrCreate([
            'name' => 'manage emergency services',
            'guard_name' => 'web',
        ]);

        // منح الصلاحية للأدوار الافتراضية
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }

        $accountantRole = Role::where('name', 'accountant')->first();
        if ($accountantRole) {
            $accountantRole->givePermissionTo($permission);
        }

        // مسح كاش الصلاحيات
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('name', 'manage emergency services')->first();
        if ($permission) {
            $permission->delete();
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
