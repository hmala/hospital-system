<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تنفيذ عملية الهجرة لإنشاء جدول نتائج المختبر
     */
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id(); // المعرف الفريد للنتيجة
            $table->foreignId('visit_id')->constrained('visits')->onDelete('cascade'); // معرف الزيارة مع حذف النتائج عند حذف الزيارة
            $table->foreignId('request_id')->constrained('requests')->onDelete('cascade'); // معرف طلب التحليل مع حذف النتائج عند حذف الطلب
            $table->string('test_name'); // اسم الفحص المخبري
            $table->decimal('value', 10, 2)->nullable(); // قيمة نتيجة الفحص (رقمية مع خانتين عشريتين)
            $table->string('unit')->nullable(); // وحدة القياس
            $table->string('status')->nullable(); // حالة النتيجة (طبيعي، مرتفع، منخفض)
            $table->string('reference_range')->nullable(); // المدى المرجعي للنتيجة الطبيعية
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->timestamps();

            // تركيب فهرس مركب للبحث السريع
            $table->index(['visit_id', 'request_id', 'test_name']);
        });
    }

    /**
     * التراجع عن عملية الهجرة وحذف جدول نتائج المختبر
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};