<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('hr_action_setting_id')->nullable()->constrained('hr_action_settings')->nullOnDelete();
            
            $table->date('action_date')->comment('تاريخ وقوع الحدث أو استحقاق المكافأة/العقوبة');
            $table->text('reason')->comment('السبب الفعلي والتفاصيل المكتوبة');
            
            // Snapshots of the effect at the time of creation (in case settings change later)
            $table->enum('applied_effect_type', ['none', 'amount', 'days', 'percentage'])->default('none');
            $table->decimal('applied_effect_value', 12, 2)->default(0);
            
            // The calculated final financial amount (negative for penalty, positive for bonus)
            // Stored for easy payroll aggregation without recalculating every time
            $table->decimal('financial_amount', 12, 2)->default(0)->comment('المبلغ المالي النهائي المحسوب (سالب للخصم، موجب للمكافأة)');
            
            $table->enum('status', ['pending', 'processed', 'cancelled'])->default('pending')->comment('pending: قيد الانتظار للراتب القادم، processed: تم ترحيله في الرواتب');
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_actions');
    }
};
