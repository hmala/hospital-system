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
        Schema::create('incubator_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade')
                  ->comment('معرف المريض (الأم أو السجل الطبي)');
            $table->string('baby_name')->comment('اسم الطفل الخدج');
            $table->foreignId('incubator_id')->constrained()->onDelete('cascade')
                  ->comment('رقم الحاضنة المحجوزة');
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('set null')
                  ->comment('الطبيب المسؤول');
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null')
                  ->comment('القسم المسؤول');
            $table->date('admission_date')->comment('تاريخ الدخول');
            $table->time('admission_time')->comment('وقت الدخول');
            $table->date('discharge_date')->nullable()->comment('تاريخ الخروج الفعلي');
            $table->time('discharge_time')->nullable()->comment('وقت الخروج الفعلي');
            $table->integer('expected_duration')->default(1)->comment('المدة المتوقعة بالأيام');
            $table->enum('status', ['pending', 'admitted', 'discharged', 'transferred', 'cancelled'])
                  ->default('pending')
                  ->comment('الحالة: قيد الانتظار، داخل الحاضنة، خرج، منقول، ملغي');
            $table->decimal('total_cost', 12, 2)->default(0)->comment('التكلفة الإجمالية');
            $table->text('medical_notes')->nullable()->comment('ملاحظات طبية');
            $table->text('admission_notes')->nullable()->comment('ملاحظات الدخول');
            $table->text('discharge_notes')->nullable()->comment('ملاحظات الخروج');
            $table->string('birth_weight')->nullable()->comment('وزن الولادة');
            $table->string('gestational_age')->nullable()->comment('عمر الحمل');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('admission_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incubator_reservations');
    }
};
