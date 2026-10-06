<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_employee_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->string('title')->comment('اسم المخصص: نقل، خطورة، زوجية، أطفال...');
            $table->decimal('amount', 12, 2)->default(0)->comment('قيمة المخصص الشهرية');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_employee_allowances');
    }
};
