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

        $newPermissions = [
            'view bed reservations',
            'create bed reservations',
            'view incubator reservations',
            'create incubator reservations',
            'manage incubator reservations',
        ];

        foreach ($newPermissions as $permName) {
            Permission::firstOrCreate([
                'name' => $permName,
                'guard_name' => 'web'
            ]);
        }

        // إعطاء كافة الصلاحيات للأدمن
        $adminRoles = Role::whereIn('name', ['admin', 'admin-hsop', 'hospital_admin'])->get();
        foreach ($adminRoles as $role) {
            $role->givePermissionTo($newPermissions);
        }

        // إعطاء صلاحيات الحجز للاستقبال والاستعلامات
        $receptionist = Role::where('name', 'receptionist')->first();
        if ($receptionist) {
            $receptionist->givePermissionTo([
                'view bed reservations',
                'create bed reservations',
                'view incubator reservations',
                'create incubator reservations',
                'manage incubator reservations',
            ]);
        }

        // إعطاء كادر الحاضنات
        $nicuStaff = Role::where('name', 'nicu_staff')->first();
        if ($nicuStaff) {
            $nicuStaff->givePermissionTo([
                'view incubator reservations',
                'create incubator reservations',
                'manage incubator reservations',
            ]);
        }

        // إعطاء كادر العمليات
        $surgeryStaff = Role::where('name', 'surgery_staff')->first();
        if ($surgeryStaff) {
            $surgeryStaff->givePermissionTo([
                'view bed reservations',
                'create bed reservations',
            ]);
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
            'view bed reservations',
            'create bed reservations',
            'view incubator reservations',
            'create incubator reservations',
            'manage incubator reservations',
        ];

        Permission::whereIn('name', $permissions)->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
