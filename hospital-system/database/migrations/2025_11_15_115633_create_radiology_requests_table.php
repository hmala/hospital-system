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
        Schema::create('radiology_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade'); // الطبيب المطلب
            $table->foreignId('radiology_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('visit_id')->nullable()->constrained()->onDelete('set null'); // الزيارة المرتبطة
            $table->dateTime('requested_date'); // تاريخ طلب الإشعة
            $table->dateTime('scheduled_date')->nullable(); // تاريخ جدولة الإشعة
            $table->enum('priority', ['normal', 'urgent', 'emergency'])->default('normal'); // الأولوية
            $table->enum('status', ['pending', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('clinical_indication'); // الإشارة السريرية
            $table->text('specific_instructions')->nullable(); // تعليمات خاصة
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->decimal('total_cost', 10, 2)->nullable(); // التكلفة الإجمالية
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null'); // موظف الإشعة المنفذ
            $table->dateTime('performed_at')->nullable(); // تاريخ ووقت التنفيذ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radiology_requests');
    }
};
