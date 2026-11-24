<?php

use App\Models\Location;
use App\Models\LocationCustodianHistory;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // 1. إنشاء صلاحية إدارة مخزن وصالات العمليات وربطها بالأدوار المعنية
        try {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $perm = Permission::firstOrCreate(['name' => 'manage surgery store', 'guard_name' => 'web']);

            $rolesToAssign = ['surgery_staff', 'admin', 'admin-hsop', 'hospital_admin', 'super_admin'];
            foreach ($rolesToAssign as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role && ! $role->hasPermissionTo($perm)) {
                    $role->givePermissionTo($perm);
                }
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // تجاهل في حال كانت البيئة لا تدعم الجداول بعد
        }

        // 2. تعيين أمين العهدة الافتراضي لمخزن العمليات الرئيسي وصالات العمليات
        try {
            $surgeryUser = User::whereHas('roles', fn ($q) => $q->where('name', 'surgery_staff'))
                ->orWhere('role', 'surgery_staff')
                ->where('is_active', true)
                ->first();

            if ($surgeryUser) {
                $targetLocations = Location::where('type', 'sub')
                    ->where(function ($q) {
                        $q->where('name', 'like', '%عمليات%')
                            ->orWhere('name', 'like', '%صالة العمليات%')
                            ->orWhere('name', 'like', '%صاله العمليات%')
                            ->orWhere('name', 'like', '%مخزن عمليات%');
                    })
                    ->where('name', 'not like', '%ولادة%')
                    ->get();

                foreach ($targetLocations as $loc) {
                    if (! $loc->custodian_id) {
                        $loc->update(['custodian_id' => $surgeryUser->id]);

                        LocationCustodianHistory::create([
                            'location_id' => $loc->id,
                            'user_id' => $surgeryUser->id,
                            'assigned_by' => $surgeryUser->id,
                            'assigned_at' => now(),
                            'notes' => "تكليف نظامي أولي: موظف العمليات ({$surgeryUser->name}) مسؤولاً عن عهدة ({$loc->name})",
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // تجاهل أي خطأ عابر في حال التشغيل الجزئي
        }
    }

    public function down(): void
    {
        try {
            $perm = Permission::where('name', 'manage surgery store')->where('guard_name', 'web')->first();
            if ($perm) {
                $perm->delete();
            }
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
            //
        }
    }
};
