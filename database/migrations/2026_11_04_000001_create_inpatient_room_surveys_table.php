<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inpatient_room_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->foreignId('surgery_id')->nullable()->constrained('surgeries')->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('room_type')->nullable(); // regular, vip, vvip
            $table->date('survey_date')->nullable();
            $table->string('status', 20)->default('pending'); // pending, completed

            // القسم الثاني: تقييم غرفة الرقود (1 إلى 5: ممتاز=5, جيد جداً=4, جيد=3, مقبول=2, ضعيف=1)
            $table->unsignedTinyInteger('cleanliness_room')->nullable();
            $table->unsignedTinyInteger('cleanliness_bathroom')->nullable();
            $table->unsignedTinyInteger('temperature_ac')->nullable();
            $table->unsignedTinyInteger('lighting')->nullable();
            $table->unsignedTinyInteger('quietness_privacy')->nullable();
            $table->unsignedTinyInteger('bed_comfort')->nullable();
            $table->unsignedTinyInteger('control_panel')->nullable();
            $table->unsignedTinyInteger('storage_space')->nullable();
            $table->unsignedTinyInteger('furniture_safety')->nullable();
            $table->unsignedTinyInteger('entertainment')->nullable();
            $table->text('room_notes')->nullable();

            // القسم الثالث: أسئلة مفتوحة ومؤشر التوصية
            $table->json('top_three_improvements')->nullable();
            $table->text('patient_suggestions')->nullable();
            $table->unsignedTinyInteger('recommend_score_nps')->nullable();

            // تقييم الفريق الطبي
            $table->unsignedTinyInteger('doctor_communication')->nullable();
            $table->text('doctor_notes')->nullable();

            // التدقيق والمؤشرات
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->string('submitted_via', 20)->nullable(); // patient_qr, staff_manual
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inpatient_room_surveys');
    }
};
