<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_payroll_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('cycle_month', 7)->unique()->comment('YYYY-MM');
            $table->date('start_date')->comment('بداية فترة الاحتساب');
            $table->date('end_date')->comment('نهاية فترة الاحتساب');
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft')->comment('حالة مسير الرواتب');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payroll_cycles');
    }
};
