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
        Schema::create('consultation_revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('appointment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('patient_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('service_type_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('examination_count')->default(1);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('doctor_share', 14, 2)->default(0);
            $table->decimal('hospital_share', 14, 2)->default(0);
            $table->decimal('doctor_percentage', 5, 2)->nullable();
            $table->date('revenue_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'patient_id', 'payment_id']);
            $table->index(['revenue_date']);
            $table->index(['department_id', 'service_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_revenues');
    }
};
