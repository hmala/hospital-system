<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('overtime_date')->comment('تاريخ العمل الإضافي');
            $table->enum('input_type', ['hours', 'manual_amount'])->default('hours')->comment('حساب بالساعات أو مبلغ مقطوع');
            $table->decimal('hours_count', 5, 2)->nullable()->comment('عدد الساعات الإضافية');
            $table->decimal('hourly_rate_used', 10, 2)->nullable()->comment('سعر الساعة المستخدم وقت التسجيل');
            $table->decimal('manual_amount', 12, 2)->nullable()->comment('مبلغ يدوي مقطوع');
            $table->decimal('total_amount', 12, 2)->default(0)->comment('المبلغ الإجمالي المحتسب');
            $table->enum('status', ['pending', 'processed'])->default('pending');
            $table->foreignId('hr_payroll_cycle_id')->nullable()->constrained('hr_payroll_cycles')->nullOnDelete();
            $table->string('description')->nullable()->comment('وصف طبيعة العمل الإضافي');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_overtimes');
    }
};
