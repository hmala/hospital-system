<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inpatient_room_surveys', function (Blueprint $table) {
            // تقييم طاقم التمريض (6 بنود + ملاحظات)
            $table->unsignedTinyInteger('nursing_greeting')->nullable()->after('room_notes');
            $table->unsignedTinyInteger('nursing_skill')->nullable()->after('nursing_greeting');
            $table->unsignedTinyInteger('nursing_care_attention')->nullable()->after('nursing_skill');
            $table->unsignedTinyInteger('nursing_instructions')->nullable()->after('nursing_care_attention');
            $table->unsignedTinyInteger('nursing_response_speed')->nullable()->after('nursing_instructions');
            $table->unsignedTinyInteger('nursing_privacy_respect')->nullable()->after('nursing_response_speed');
            $table->text('nursing_notes')->nullable()->after('nursing_privacy_respect');

            // تقييم قسم المختبر والتحاليل (5 بنود + ملاحظات)
            $table->unsignedTinyInteger('lab_prep_instructions')->nullable()->after('nursing_notes');
            $table->unsignedTinyInteger('lab_sampling_skill')->nullable()->after('lab_prep_instructions');
            $table->unsignedTinyInteger('lab_hygiene_sterilization')->nullable()->after('lab_sampling_skill');
            $table->unsignedTinyInteger('lab_results_speed')->nullable()->after('lab_hygiene_sterilization');
            $table->unsignedTinyInteger('lab_staff_behavior')->nullable()->after('lab_results_speed');
            $table->text('lab_notes')->nullable()->after('lab_staff_behavior');

            // تقييم الصيدلية والتعامل مع الأدوية (5 بنود + ملاحظات)
            $table->unsignedTinyInteger('pharmacy_dispense_speed')->nullable()->after('lab_notes');
            $table->unsignedTinyInteger('pharmacy_instruction_clarity')->nullable()->after('pharmacy_dispense_speed');
            $table->unsignedTinyInteger('pharmacy_comprehension_check')->nullable()->after('pharmacy_instruction_clarity');
            $table->unsignedTinyInteger('pharmacy_respect_politeness')->nullable()->after('pharmacy_comprehension_check');
            $table->unsignedTinyInteger('pharmacy_med_availability')->nullable()->after('pharmacy_respect_politeness');
            $table->text('pharmacy_notes')->nullable()->after('pharmacy_med_availability');

            // بيانات المريض والتوقيع في أسفل الورقة 2
            $table->string('respondent_name')->nullable()->after('doctor_notes');
            $table->string('respondent_signature')->nullable()->after('respondent_name');
            $table->string('respondent_phone')->nullable()->after('respondent_signature');
            $table->string('respondent_email')->nullable()->after('respondent_phone');

            // الدرجات الفرعية لكل قسم
            $table->decimal('room_score', 5, 2)->nullable()->after('overall_score');
            $table->decimal('nursing_score', 5, 2)->nullable()->after('room_score');
            $table->decimal('lab_score', 5, 2)->nullable()->after('nursing_score');
            $table->decimal('pharmacy_score', 5, 2)->nullable()->after('lab_score');
        });
    }

    public function down(): void
    {
        Schema::table('inpatient_room_surveys', function (Blueprint $table) {
            $table->dropColumn([
                'nursing_greeting',
                'nursing_skill',
                'nursing_care_attention',
                'nursing_instructions',
                'nursing_response_speed',
                'nursing_privacy_respect',
                'nursing_notes',
                'lab_prep_instructions',
                'lab_sampling_skill',
                'lab_hygiene_sterilization',
                'lab_results_speed',
                'lab_staff_behavior',
                'lab_notes',
                'pharmacy_dispense_speed',
                'pharmacy_instruction_clarity',
                'pharmacy_comprehension_check',
                'pharmacy_respect_politeness',
                'pharmacy_med_availability',
                'pharmacy_notes',
                'respondent_name',
                'respondent_signature',
                'respondent_phone',
                'respondent_email',
                'room_score',
                'nursing_score',
                'lab_score',
                'pharmacy_score',
            ]);
        });
    }
};

