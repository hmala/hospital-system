<?php

use App\Models\User;
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

        // 1. التأكد من وجود دور head_nurse وصلاحياته الكاملة
        $headNurseRole = Role::firstOrCreate(['name' => 'head_nurse', 'guard_name' => 'web']);
        $managePerm = Permission::firstOrCreate(['name' => 'manage nurse availability', 'guard_name' => 'web']);
        $viewNursingPerm = Permission::firstOrCreate(['name' => 'view nursing station', 'guard_name' => 'web']);
        $headNurseRole->givePermissionTo([$managePerm, $viewNursingPerm]);

        // 2. ترقية وتثبيت حساب رئيس/مشرف التمريض (عبدالله الحسين أو أي حساب مسمى مشرف تمريض)
        $supervisors = User::where('email', 'abdullahalhussen247@gmail.com')
            ->orWhere('role', 'head_nurse')
            ->orWhere('name', 'like', '%مشرف التمريض%')
            ->orWhere('name', 'like', '%رئيس التمريض%')
            ->orWhere('name', 'like', '%عبدالله الحسين%')
            ->get();

        foreach ($supervisors as $user) {
            $user->syncRoles(['head_nurse']);
            $user->update(['role' => 'head_nurse']);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed to preserve security roles
    }
};
