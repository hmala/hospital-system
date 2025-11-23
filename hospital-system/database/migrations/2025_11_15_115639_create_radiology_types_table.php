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
        Schema::create('radiology_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم نوع الإشعة (أشعة عادية، CT، MRI، إلخ)
            $table->string('code')->unique(); // رمز نوع الإشعة
            $table->text('description')->nullable(); // وصف نوع الإشعة
            $table->decimal('base_price', 10, 2); // السعر الأساسي
            $table->integer('estimated_duration')->default(30); // المدة المقدرة بالدقائق
            $table->boolean('requires_contrast')->default(false); // هل يحتاج مادة تباين؟
            $table->boolean('requires_preparation')->default(false); // هل يحتاج تحضير مسبق؟
            $table->text('preparation_instructions')->nullable(); // تعليمات التحضير
            $table->boolean('is_active')->default(true); // هل النوع نشط؟
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radiology_types');
    }
};
