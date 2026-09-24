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
        // 1. جدول الوصفات الطبية الإلكترونية
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_number', 50)->unique()->comment('رقم الوصفة المميز e.g. RX-20260924-0001');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->foreignId('emergency_id')->nullable()->constrained('emergencies')->nullOnDelete();
            $table->string('status', 30)->default('pending')->comment('pending, in_progress, dispensed, partially_dispensed, cancelled');
            $table->string('diagnosis')->nullable()->comment('التشخيص المبدئي أو سبب الوصفة');
            $table->text('notes')->nullable()->comment('تعليمات عامة للمريض أو الصيدلي');
            $table->timestamp('dispensed_at')->nullable()->comment('تاريخ ووقت الصرف');
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('pharmacy_sales')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'created_at']);
        });

        // 2. جدول بنود الوصفة الدوائية
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1)->comment('الكمية الموصوفة');
            $table->string('unit_type', 30)->default('main_unit')->comment('main_unit (علبة), sub_unit (شريط/أمبولة)');
            $table->string('dosage_frequency', 100)->nullable()->comment('الجرعة وتكرار الاستخدام e.g. 1x3, 1x2, عند اللزوم');
            $table->unsignedInteger('duration_days')->nullable()->comment('مدة العلاج بالأيام');
            $table->string('instructions', 255)->nullable()->comment('طريقة الاستخدام: بعد الأكل، قبل النوم، مع كوب ماء...');
            $table->string('status', 30)->default('pending')->comment('pending, dispensed, substituted, cancelled, out_of_stock');
            $table->foreignId('dispensed_medicine_id')->nullable()->constrained('medicines')->nullOnDelete()->comment('الدواء البديل المصروف في حال استبداله');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['prescription_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
    }
};
