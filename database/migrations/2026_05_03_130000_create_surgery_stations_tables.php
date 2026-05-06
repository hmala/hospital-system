<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // محطة الطبيب الجراح
        Schema::create('surgeon_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->onDelete('cascade');
            $table->foreignId('surgeon_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->foreignId('resident_assigned_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('surgery_id');
            $table->index('status');
        });

        // محطة التخدير
        Schema::create('anesthesia_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->onDelete('cascade');
            $table->foreignId('anesthesiologist_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->foreignId('anesthesiologist_2_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->string('anesthesia_type')->nullable();
            $table->string('surgical_assistant_name')->nullable();
            $table->text('notes')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('surgery_id');
            $table->index('status');
        });

        // محطة المقيم
        Schema::create('resident_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->onDelete('cascade');
            $table->foreignId('resident_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->text('post_op_notes')->nullable();
            $table->text('treatment_plan')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('surgery_id');
            $table->index('status');
        });

        // محطة التمريض
        Schema::create('nursing_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->onDelete('cascade');
            $table->foreignId('nurse_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('nursing_notes')->nullable();
            $table->text('discharge_notes')->nullable();
            $table->text('vital_signs')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('surgery_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursing_stations');
        Schema::dropIfExists('resident_stations');
        Schema::dropIfExists('anesthesia_stations');
        Schema::dropIfExists('surgeon_stations');
    }
};
