<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->decimal('total_amount', 12, 2)->comment('إجمالي مبلغ السلفة');
            $table->decimal('monthly_installment', 12, 2)->comment('قيمة القسط الشهري');
            $table->decimal('paid_amount', 12, 2)->default(0)->comment('المبلغ المسدد حتى الآن');
            $table->decimal('remaining_amount', 12, 2)->comment('المبلغ المتبقي');
            $table->date('start_date')->comment('تاريخ بدء الاستقطاع');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->string('reason')->nullable()->comment('سبب السلفة');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_loans');
    }
};
