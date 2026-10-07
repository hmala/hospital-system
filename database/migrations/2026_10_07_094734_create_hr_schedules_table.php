<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignId('hr_shift_id')->nullable()->constrained('hr_shifts')->nullOnDelete();
            
            $table->date('shift_date')->comment('تاريخ الشفت');
            $table->boolean('is_off_day')->default(false)->comment('هل هو يوم راحة/عطلة؟');
            $table->enum('attendance_status', ['pending', 'present', 'absent', 'late'])->default('pending')->comment('حالة الحضور الفعلية');
            
            $table->time('actual_check_in')->nullable();
            $table->time('actual_check_out')->nullable();
            
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            
            // Employee can only have one main record per day in this design (or multiple if overlapping shifts are allowed, but usually one is enough for scheduling).
            // Let's not enforce unique so they can do double shifts (e.g. morning and night).
            $table->unique(['hr_employee_id', 'shift_date', 'hr_shift_id'], 'emp_date_shift_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_schedules');
    }
};
