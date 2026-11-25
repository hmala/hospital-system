<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nicu_settings')) {
            Schema::create('nicu_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('label')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        $defaults = [
            [
                'key' => 'observation_duration_minutes',
                'value' => '120',
                'label' => 'مدة المعاينة القياسية للوليد (بالدقائق)',
                'description' => 'المدة الزمنية الافتراضية لمراقبة الطفل بعد الولادة قبل التسليم أو التحويل للحاضنة (الافتراضي 120 دقيقة / ساعتان).',
            ],
            [
                'key' => 'observation_is_billable',
                'value' => '0',
                'label' => 'تفعيل احتساب أجور معاينة الوليد',
                'description' => 'عند التفعيل يتم احتساب أجور معاينة مستقلة، وعند الإيقاف تكون المعاينة مجانية ومشمولة مع الولادة.',
            ],
            [
                'key' => 'observation_fee',
                'value' => '0',
                'label' => 'مبلغ أجور المعاينة (د.ع)',
                'description' => 'الأجور المحتسبة عند تفعيل خاصية الفوترة لمعاينة الوليد (ساعتان).',
            ],
            [
                'key' => 'hospital_print_name_ar',
                'value' => 'مستشفى الكفاءات الأهلي',
                'label' => 'اسم المستشفى بالطباعة (عربي)',
                'description' => 'يظهر في ترويسة استمارة Obstetric Case Sheet.',
            ],
            [
                'key' => 'hospital_print_name_en',
                'value' => 'Al-Kafaat Hospital',
                'label' => 'اسم المستشفى بالطباعة (إنجليزي)',
                'description' => 'يظهر في ترويسة استمارة Obstetric Case Sheet.',
            ],
            [
                'key' => 'hospital_print_email',
                'value' => 'alkafaathospital@gmail.com',
                'label' => 'البريد الإلكتروني بالترويسة',
                'description' => 'البريد الإلكتروني الظاهر أسفل ورقة الاستمارة.',
            ],
            [
                'key' => 'hospital_print_phone',
                'value' => '07760507060',
                'label' => 'رقم الهاتف بالترويسة',
                'description' => 'رقم الهاتف الظاهر أسفل ورقة الاستمارة.',
            ],
            [
                'key' => 'hospital_print_address',
                'value' => 'بغداد - الحارثية شارع الكندي',
                'label' => 'العنوان بالترويسة',
                'description' => 'عنوان المستشفى الظاهر أسفل ورقة الاستمارة.',
            ],
        ];

        foreach ($defaults as $row) {
            DB::table('nicu_settings')->updateOrInsert(
                ['key' => $row['key']],
                [
                    'value' => $row['value'],
                    'label' => $row['label'],
                    'description' => $row['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (! Schema::hasTable('nicu_delivery_observations')) {
            Schema::create('nicu_delivery_observations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surgery_id')->nullable()->constrained('surgeries')->nullOnDelete();
                $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->string('mother_name');
                $table->string('mother_age_gender')->nullable();
                $table->date('mother_birth_date')->nullable();
                $table->date('reference_date')->nullable();
                $table->string('department_name')->nullable();
                $table->string('requesting_doctor_name')->nullable();
                $table->string('report_type')->default('Obstetrics Case Sheet');
                $table->string('stay_type')->default('In Patient');
                $table->date('admission_date')->nullable();
                $table->date('discharge_date')->nullable();
                $table->string('operative_description')->nullable();
                $table->date('delivery_date')->nullable();
                $table->string('delivery_time')->nullable();
                $table->string('case_of_delivery')->default('SINGLE');
                $table->unsignedTinyInteger('no_of_borns')->default(1);
                $table->text('pre_delivery_diagnosis')->nullable();
                $table->text('post_delivery_diagnosis')->nullable();
                $table->text('procedure_performed')->nullable();
                $table->string('surgeon_name')->nullable();
                $table->string('anesthesia_type')->nullable();
                $table->text('assessment')->nullable();
                $table->text('history_of_illness')->nullable();
                $table->text('delivery_note')->nullable();
                $table->json('treatments')->nullable();
                $table->timestamp('started_at');
                $table->unsignedSmallInteger('target_duration_minutes')->default(120);
                $table->timestamp('completed_at')->nullable();
                $table->string('status')->default('under_observation');
                $table->boolean('is_billable')->default(false);
                $table->decimal('observation_fee', 10, 2)->default(0);
                $table->string('payment_status')->default('free');
                $table->foreignId('nicu_doctor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nicu_doctor_name')->nullable();
                $table->text('handover_text')->nullable();
                $table->string('recipient_name')->nullable();
                $table->string('recipient_relation')->nullable();
                $table->string('recipient_id_number')->nullable();
                $table->string('recipient_phone')->nullable();
                $table->string('recipient_address')->nullable();
                $table->timestamp('handed_over_at')->nullable();
                $table->foreignId('handed_over_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('signed_form_path')->nullable();
                $table->timestamp('signed_form_uploaded_at')->nullable();
                $table->foreignId('signed_form_uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['status', 'started_at']);
                $table->index('delivery_date');
                $table->index('mother_name');
            });
        }

        if (! Schema::hasTable('nicu_delivery_observation_babies')) {
            Schema::create('nicu_delivery_observation_babies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('observation_id')->constrained('nicu_delivery_observations', 'id', 'nicu_obs_babies_obs_fk')->cascadeOnDelete();
                $table->string('born_name');
                $table->string('live_status')->default('Live Birth');
                $table->string('gender')->nullable();
                $table->decimal('weight_kg', 5, 3)->nullable();
                $table->string('disposition')->default('observing');
                $table->foreignId('incubator_reservation_id')->nullable()->constrained('incubator_reservations', 'id', 'nicu_obs_babies_res_fk')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['observation_id', 'disposition'], 'nicu_obs_babies_obs_disp_idx');
            });
        }

        if (Schema::hasTable('permissions')) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $perm = Permission::firstOrCreate([
                'name' => 'manage nicu delivery observations',
                'guard_name' => 'web',
            ]);

            $targetRoles = ['nicu_doctor', 'nicu_nurse', 'nicu_staff', 'admin', 'admin-hsop', 'hospital_admin', 'super_admin'];
            foreach ($targetRoles as $roleName) {
                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
                if ($role) {
                    $role->givePermissionTo($perm);
                }
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nicu_delivery_observation_babies');
        Schema::dropIfExists('nicu_delivery_observations');
        Schema::dropIfExists('nicu_settings');
    }
};
