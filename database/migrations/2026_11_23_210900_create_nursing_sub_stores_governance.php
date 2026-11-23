<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('locations', 'custodian_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->foreignId('custodian_id')->nullable()->after('type')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('location_custodian_histories')) {
            Schema::create('location_custodian_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->nullable();
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        // إنشاء الصلاحية وربطها بالأدوار الإشرافية
        try {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $perm = Permission::firstOrCreate(['name' => 'manage nursing sub stores', 'guard_name' => 'web']);

            $rolesToAssign = ['head_nurse', 'admin', 'admin-hsop', 'hospital_admin', 'super_admin'];
            foreach ($rolesToAssign as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role && ! $role->hasPermissionTo($perm)) {
                    $role->givePermissionTo($perm);
                }
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // تجاهل أي خطأ عابر في حال كانت البيئة لا تدعم الجداول بعد
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('location_custodian_histories');

        if (Schema::hasColumn('locations', 'custodian_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropForeign(['custodian_id']);
                $table->dropColumn('custodian_id');
            });
        }

        try {
            Permission::where('name', 'manage nursing sub stores')->delete();
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }
};
