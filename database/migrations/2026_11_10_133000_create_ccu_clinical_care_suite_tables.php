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
        // 1. جدول توجيهات وملاحظات أطباء الاختصاص المثبتة (CCU Pinned Doctor Directives & Notes)
        Schema::create('ccu_doctor_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_admission_id')->constrained('icu_admissions')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->text('note_text');
            $table->string('priority')->default('urgent'); // normal, urgent, stat
            $table->boolean('is_pinned')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('pinned_at')->nullable();
            $table->timestamps();
        });

        // 2. جدول جداول إعطاء وجرعات الأدوية ومضخات التسريب الوريدي (CCU Medication Administration Record - MAR)
        Schema::create('ccu_medication_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_admission_id')->constrained('icu_admissions')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('type')->default('medication'); // medication, infusion, feeding, other
            $table->string('title'); // اسم الدواء / المحلول
            $table->string('dose_or_amount')->nullable(); // الجرعة أو الكمية
            $table->string('route')->default('حقن وريدي (IV)'); // IV, Infusion, IM, Oral/NG, SC, Inhalation
            $table->string('interval_hours')->default('8'); // 4, 6, 8, 12, 24, continuous, prn
            $table->dateTime('start_time')->nullable();
            $table->dateTime('next_due_at')->nullable()->index();
            $table->dateTime('last_administered_at')->nullable();
            $table->foreignId('last_administered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('infusion_rate')->nullable(); // معدل المضخة e.g. 5 mcg/min or 10 ml/hr
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 3. جدول استشارات الأطباء التخصصية (CCU Clinical Consultations Suite)
        Schema::create('ccu_consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_admission_id')->constrained('icu_admissions')->cascadeOnDelete();
            $table->string('consultation_type')->default('internal'); // internal, external
            $table->string('specialty'); // باطنية، جراحة، قلبية، كلى، أعصاب، تخدير...
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete(); // في حال كانت استشارة داخلية
            $table->string('external_doctor_name')->nullable(); // في حال كانت استشارة خارجية
            $table->text('reason'); // دواعي الاستشارة والحالة السريرية
            $table->text('response')->nullable(); // توصيات ورأي الطبيب الاستشاري
            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->string('priority')->default('urgent'); // normal, urgent, stat
            $table->decimal('fee', 10, 2)->default(0); // أجور الاستشارة المالية
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        // 4. جدول الأرشيف والمرفقات الطبية وتخطيط القلب (CCU Medical Attachments & ECG Records)
        Schema::create('ccu_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('icu_admission_id')->constrained('icu_admissions')->cascadeOnDelete();
            $table->string('title');
            $table->string('attachment_type')->default('report'); // ecg, report, imaging, lab_external, referral, other
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ccu_attachments');
        Schema::dropIfExists('ccu_consultations');
        Schema::dropIfExists('ccu_medication_schedules');
        Schema::dropIfExists('ccu_doctor_notes');
    }
};
