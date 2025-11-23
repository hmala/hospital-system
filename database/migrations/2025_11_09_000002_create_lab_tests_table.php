<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تنفيذ عملية إنشاء جدول التحاليل المخبرية
     */
    public function up(): void
    {
        Schema::create('lab_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->onDelete('cascade'); // معرف الزيارة
            $table->string('test_name'); // اسم التحليل المطلوب
            $table->decimal('result_value', 10, 2)->nullable(); // قيمة نتيجة التحليل
            $table->string('unit')->nullable(); // وحدة القياس
            $table->string('reference_range')->nullable(); // المدى المرجعي للنتيجة الطبيعية
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending'); // حالة التحليل
            $table->enum('result_status', ['normal', 'high', 'low'])->nullable(); // حالة النتيجة
            $table->text('notes')->nullable(); // ملاحظات
            $table->timestamp('requested_at'); // وقت طلب التحليل
            $table->timestamp('completed_at')->nullable(); // وقت اكتمال التحليل
            $table->timestamps();
            
            // إنشاء فهرس للبحث السريع
            $table->index(['visit_id', 'test_name', 'status']);
        });
    }

    /**
     * التراجع عن عملية إنشاء الجدول
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_test_results');
    }
};