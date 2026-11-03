<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_action_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['penalty', 'bonus', 'warning'])->comment('نوع الإجراء: عقوبة، مكافأة، أو إنذار فقط');
            $table->string('title')->comment('اسم الإجراء (مثال: غياب بدون عذر، تأخير، مكافأة تميز)');
            $table->enum('effect_type', ['none', 'amount', 'days', 'percentage'])->default('none')->comment('نوع التأثير المالي: لا يوجد، مبلغ ثابت، خصم/إضافة أيام، نسبة من الراتب');
            $table->decimal('effect_value', 12, 2)->default(0)->comment('قيمة التأثير (عدد الأيام، أو المبلغ، أو النسبة المئوية)');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable()->comment('وصف تفصيلي للائحة');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_action_settings');
    }
};
