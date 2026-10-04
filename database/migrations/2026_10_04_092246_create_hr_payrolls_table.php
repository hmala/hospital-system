<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_payroll_cycle_id')->constrained('hr_payroll_cycles')->cascadeOnDelete();
            $table->foreignId('hr_employee_id')->constrained('hr_employees')->cascadeOnDelete();
            
            // Financial details snapshot
            $table->decimal('basic_salary', 12, 2)->default(0)->comment('الراتب الأساسي (من ملف الموظف)');
            $table->decimal('allowances', 12, 2)->default(0)->comment('إجمالي المخصصات والعلاوات المضافة يدوياً');
            
            // Computed from hr_employee_actions
            $table->decimal('bonuses_amount', 12, 2)->default(0)->comment('المكافآت (تلقائي من الإجراءات)');
            $table->decimal('penalties_amount', 12, 2)->default(0)->comment('الاستقطاعات/العقوبات (تلقائي من الإجراءات)');
            
            $table->decimal('net_salary', 12, 2)->default(0)->comment('الصافي = الأساسي + المخصصات + المكافآت - الاستقطاعات');
            
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->date('payment_date')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Each employee can only have one payroll slip per cycle
            $table->unique(['hr_payroll_cycle_id', 'hr_employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payrolls');
    }
};
