<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('absence_date')->comment('تاريخ الغياب');
            $table->decimal('days_count', 5, 2)->default(1)->comment('عدد الأيام (يمكن أن يكون كسراً مثل 0.5)');
            $table->enum('type', ['absence', 'late', 'early_leave'])->default('absence')->comment('غياب / تأخير / انصراف مبكر');
            $table->boolean('is_excused')->default(false)->comment('هل الغياب بعذر؟');
            $table->decimal('deduction_amount', 12, 2)->default(0)->comment('قيمة الخصم المحتسبة آلياً');
            $table->enum('status', ['pending', 'processed'])->default('pending');
            $table->foreignId('hr_payroll_cycle_id')->nullable()->constrained('hr_payroll_cycles')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_absences');
    }
};
