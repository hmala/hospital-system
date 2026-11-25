<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
        if (Schema::hasTable('incubator_reservations')) {
            Schema::table('incubator_reservations', function (Blueprint $table) {
                if (! Schema::hasColumn('incubator_reservations', 'admission_reason')) {
                    $table->text('admission_reason')->nullable()->after('admission_notes');
                }
                if (! Schema::hasColumn('incubator_reservations', 'medical_history')) {
                    $table->text('medical_history')->nullable()->after('admission_reason');
                }
                if (! Schema::hasColumn('incubator_reservations', 'maternal_history')) {
                    $table->text('maternal_history')->nullable()->after('medical_history');
                }
                if (! Schema::hasColumn('incubator_reservations', 'admission_info_updated_by')) {
                    $table->foreignId('admission_info_updated_by')->nullable()->after('maternal_history')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('incubator_reservations', 'admission_info_updated_at')) {
                    $table->timestamp('admission_info_updated_at')->nullable()->after('admission_info_updated_by');
                }
            });
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $perm = Permission::firstOrCreate(['name' => 'manage nicu admission history', 'guard_name' => 'web']);

        // تمنح تلقائياً للأطباء والإدارة فقط (ويمكن للمدير منحها للتمريض أو أي مستخدم من شاشة الأدوار)
        $roles = Role::whereIn('name', [
            'nicu_doctor', 'doctor', 'resident',
            'admin', 'admin-hsop', 'hospital_admin', 'super_admin',
        ])->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($perm);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('incubator_reservations')) {
            Schema::table('incubator_reservations', function (Blueprint $table) {
                if (Schema::hasColumn('incubator_reservations', 'admission_info_updated_by')) {
                    $table->dropForeign(['admission_info_updated_by']);
                }
                $cols = array_values(array_filter([
                    'admission_reason',
                    'medical_history',
                    'maternal_history',
                    'admission_info_updated_by',
                    'admission_info_updated_at',
                ], fn ($c) => Schema::hasColumn('incubator_reservations', $c)));

                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
