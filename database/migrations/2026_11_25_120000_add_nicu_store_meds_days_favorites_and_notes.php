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
        // 1. ربط حركات المخزن الفرعي بملف الطفل الخديج (بدون أثر مالي)
        if (Schema::hasTable('stock_movements') && ! Schema::hasColumn('stock_movements', 'incubator_reservation_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $afterCol = Schema::hasColumn('stock_movements', 'surgery_id') ? 'surgery_id' : 'user_id';
                $table->foreignId('incubator_reservation_id')
                    ->nullable()
                    ->after($afterCol)
                    ->constrained('incubator_reservations')
                    ->nullOnDelete();
            });
        }

        // 2. دليل الأدوية والتركيبات المضافة في قسم الخدج (معزول عن الصيدلية المتعاقدة)
        if (! Schema::hasTable('nicu_custom_medicines')) {
            Schema::create('nicu_custom_medicines', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('name')->unique();
                $table->string('generic_name')->nullable();
                $table->string('dosage_form', 100)->nullable();
                $table->string('strength', 100)->nullable();
                $table->string('default_dose', 100)->nullable();
                $table->string('default_route', 100)->nullable();
                $table->string('default_frequency', 100)->nullable();
                $table->unsignedSmallInteger('default_duration_days')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('usage_count')->default(1);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['is_active', 'usage_count']);
            });
        }

        // 3. عداد أيام العلاج الدوائي في أوامر وجداول الخدج (Day X of Y)
        if (Schema::hasTable('nicu_medical_orders')) {
            Schema::table('nicu_medical_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('nicu_medical_orders', 'duration_days')) {
                    $table->unsignedSmallInteger('duration_days')->nullable()->after('instructions');
                }
                if (! Schema::hasColumn('nicu_medical_orders', 'started_at')) {
                    $table->timestamp('started_at')->nullable()->after('ordered_at');
                }
                if (! Schema::hasColumn('nicu_medical_orders', 'stopped_at')) {
                    $table->timestamp('stopped_at')->nullable()->after('completed_at');
                }
                if (! Schema::hasColumn('nicu_medical_orders', 'stopped_by')) {
                    $table->foreignId('stopped_by')->nullable()->after('stopped_at')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('nicu_care_schedules')) {
            Schema::table('nicu_care_schedules', function (Blueprint $table) {
                if (! Schema::hasColumn('nicu_care_schedules', 'duration_days')) {
                    $table->unsignedSmallInteger('duration_days')->nullable()->after('interval_hours');
                }
                if (! Schema::hasColumn('nicu_care_schedules', 'stopped_at')) {
                    $table->timestamp('stopped_at')->nullable()->after('last_administered_at');
                }
            });
        }

        // 4. خانة الملاحظات السريرية الكبيرة في القراءة السريرية والتمريضية للخدج
        if (Schema::hasTable('nicu_vitals') && ! Schema::hasColumn('nicu_vitals', 'clinical_notes')) {
            Schema::table('nicu_vitals', function (Blueprint $table) {
                $table->text('clinical_notes')->nullable()->after('feeding_notes');
            });
        }

        // 5. عزل باقات الأدوية السريعة لقسم الخدج ودعم الأدوية المضافة داخل الباقات
        if (Schema::hasTable('user_medicine_groups') && ! Schema::hasColumn('user_medicine_groups', 'department')) {
            Schema::table('user_medicine_groups', function (Blueprint $table) {
                $table->string('department', 50)->default('general')->after('user_id');
                $table->index('department');
            });
        }

        if (Schema::hasTable('user_medicine_group_medicine')) {
            Schema::table('user_medicine_group_medicine', function (Blueprint $table) {
                if (! Schema::hasColumn('user_medicine_group_medicine', 'route')) {
                    $table->string('route', 100)->nullable()->after('dosage');
                }
                if (! Schema::hasColumn('user_medicine_group_medicine', 'duration_days')) {
                    $table->unsignedSmallInteger('duration_days')->nullable()->after('duration');
                }
            });
        }

        if (! Schema::hasTable('user_medicine_group_custom_items')) {
            Schema::create('user_medicine_group_custom_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('group_id')->constrained('user_medicine_groups')->cascadeOnDelete();
                $table->foreignId('nicu_custom_medicine_id')->nullable()->constrained('nicu_custom_medicines')->nullOnDelete();
                $table->string('medicine_name');
                $table->string('dosage_form', 100)->nullable();
                $table->string('dosage', 100)->nullable();
                $table->string('route', 100)->nullable();
                $table->string('frequency', 100)->nullable();
                $table->string('duration', 100)->nullable();
                $table->unsignedSmallInteger('duration_days')->nullable();
                $table->string('instructions', 500)->nullable();
                $table->timestamps();
            });
        }

        // 6. صلاحيات مخزن الخدج وباقات الأدوية لكادر وأطباء الخدج
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $manageNicuStore = Permission::firstOrCreate(['name' => 'manage nicu store', 'guard_name' => 'web']);
        $medGroupPerms = [
            Permission::firstOrCreate(['name' => 'view medicine groups', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'create medicine groups', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'edit medicine groups', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'delete medicine groups', 'guard_name' => 'web']),
        ];

        $storeRoles = Role::whereIn('name', [
            'nicu_nurse', 'nicu_doctor', 'nicu_staff', 'head_nurse',
            'admin', 'admin-hsop', 'hospital_admin', 'super_admin',
        ])->get();

        foreach ($storeRoles as $role) {
            $role->givePermissionTo($manageNicuStore);
        }

        $nicuClinicalRoles = Role::whereIn('name', [
            'nicu_doctor', 'nicu_staff', 'doctor', 'resident',
            'admin', 'admin-hsop', 'hospital_admin', 'super_admin',
        ])->get();

        foreach ($nicuClinicalRoles as $role) {
            $role->givePermissionTo($medGroupPerms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_medicine_group_custom_items');

        if (Schema::hasTable('user_medicine_group_medicine')) {
            Schema::table('user_medicine_group_medicine', function (Blueprint $table) {
                $cols = array_values(array_filter(['route', 'duration_days'], fn ($c) => Schema::hasColumn('user_medicine_group_medicine', $c)));
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('user_medicine_groups') && Schema::hasColumn('user_medicine_groups', 'department')) {
            Schema::table('user_medicine_groups', function (Blueprint $table) {
                $table->dropIndex(['department']);
                $table->dropColumn('department');
            });
        }

        if (Schema::hasTable('nicu_vitals') && Schema::hasColumn('nicu_vitals', 'clinical_notes')) {
            Schema::table('nicu_vitals', function (Blueprint $table) {
                $table->dropColumn('clinical_notes');
            });
        }

        if (Schema::hasTable('nicu_care_schedules')) {
            Schema::table('nicu_care_schedules', function (Blueprint $table) {
                $cols = array_values(array_filter(['duration_days', 'stopped_at'], fn ($c) => Schema::hasColumn('nicu_care_schedules', $c)));
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('nicu_medical_orders')) {
            Schema::table('nicu_medical_orders', function (Blueprint $table) {
                if (Schema::hasColumn('nicu_medical_orders', 'stopped_by')) {
                    $table->dropForeign(['stopped_by']);
                }
                $cols = array_values(array_filter(['duration_days', 'started_at', 'stopped_at', 'stopped_by'], fn ($c) => Schema::hasColumn('nicu_medical_orders', $c)));
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        Schema::dropIfExists('nicu_custom_medicines');

        if (Schema::hasTable('stock_movements') && Schema::hasColumn('stock_movements', 'incubator_reservation_id')) {
            Schema::table('stock_movements', function (Blueprint $table) {
                $table->dropForeign(['incubator_reservation_id']);
                $table->dropColumn('incubator_reservation_id');
            });
        }
    }
};
