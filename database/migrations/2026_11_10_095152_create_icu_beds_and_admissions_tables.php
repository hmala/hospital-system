<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('icu_beds')) {
            Schema::create('icu_beds', function (Blueprint $table) {
                $table->id();
                $table->string('bed_number')->unique();
                $table->string('bed_type')->default('general'); // general, isolation, ccu, neuro
                $table->string('room_number')->nullable();
                $table->string('floor')->default('الطابق الثالث - العناية المركزة');
                $table->string('status')->default('available'); // available, occupied, sterilizing, maintenance
                $table->decimal('daily_price', 12, 2)->default(150000.00);
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('icu_admissions')) {
            Schema::create('icu_admissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
                $table->foreignId('icu_bed_id')->constrained('icu_beds')->cascadeOnDelete();
                $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
                $table->string('admission_source')->default('emergency'); // emergency, operation_theater, ward, external_referral
                $table->dateTime('admission_date');
                $table->dateTime('discharge_date')->nullable();
                $table->string('status')->default('active'); // active, discharged, transferred, deceased
                $table->string('primary_diagnosis')->nullable();
                $table->string('icd10_code')->nullable();
                $table->integer('initial_gcs')->nullable(); // Glasgow Coma Scale (3-15)
                $table->boolean('is_ventilated')->default(false);
                $table->boolean('is_isolated')->default(false);
                $table->boolean('is_insured')->default(false);
                $table->string('insurance_category')->nullable();
                $table->text('discharge_summary')->nullable();
                $table->string('discharge_destination')->nullable();
                $table->foreignId('discharged_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('icu_vital_signs')) {
            Schema::create('icu_vital_signs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('icu_admission_id')->constrained('icu_admissions')->cascadeOnDelete();
                $table->dateTime('recorded_at');
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('gcs')->nullable();
                $table->integer('heart_rate')->nullable();
                $table->integer('systolic_bp')->nullable();
                $table->integer('diastolic_bp')->nullable();
                $table->integer('mean_arterial_pressure')->nullable();
                $table->integer('respiratory_rate')->nullable();
                $table->decimal('temperature', 4, 1)->nullable();
                $table->integer('spo2')->nullable();
                $table->string('oxygen_device')->nullable();
                $table->integer('fio2')->nullable();
                $table->decimal('peep', 4, 1)->nullable();
                $table->string('ventilator_mode')->nullable();
                $table->integer('blood_sugar')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('icu_fluid_balances')) {
            Schema::create('icu_fluid_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('icu_admission_id')->constrained('icu_admissions')->cascadeOnDelete();
                $table->dateTime('recorded_at');
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('intake_iv_fluids')->default(0);
                $table->integer('intake_blood_products')->default(0);
                $table->integer('intake_enteral_feeding')->default(0);
                $table->integer('intake_medications')->default(0);
                $table->integer('intake_total')->default(0);
                $table->integer('output_urine')->default(0);
                $table->integer('output_drains')->default(0);
                $table->integer('output_nasogastric')->default(0);
                $table->integer('output_other')->default(0);
                $table->integer('output_total')->default(0);
                $table->integer('net_balance')->default(0);
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('icu_medical_orders')) {
            Schema::create('icu_medical_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('icu_admission_id')->constrained('icu_admissions')->cascadeOnDelete();
                $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
                $table->string('order_type')->default('medication'); // medication, investigation, nursing_care, ventilator_setting, diet, discharge
                $table->text('order_text');
                $table->string('priority')->default('routine'); // routine, urgent, stat
                $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
                $table->dateTime('completed_at')->nullable();
                $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('icu_care_schedules')) {
            Schema::create('icu_care_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('icu_admission_id')->constrained('icu_admissions')->cascadeOnDelete();
                $table->string('care_type'); // e.g. تقليب كل ساعتين, سحب مفرزات ETT, عناية بالقسطرة المركزية CVC
                $table->string('scheduled_time'); // e.g. 08:00, 10:00, 12:00
                $table->boolean('is_done')->default(false);
                $table->dateTime('done_at')->nullable();
                $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('icu_care_schedules');
        Schema::dropIfExists('icu_medical_orders');
        Schema::dropIfExists('icu_fluid_balances');
        Schema::dropIfExists('icu_vital_signs');
        Schema::dropIfExists('icu_admissions');
        Schema::dropIfExists('icu_beds');
    }
};
