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
        Schema::create('emergency_lab_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['urgent', 'critical'])->default('urgent');
            $table->text('notes')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // جدول العلاقة بين الطلب والتحاليل
        Schema::create('emergency_lab_request_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_lab_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('lab_test_id')->constrained()->onDelete('cascade');
            $table->text('result')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_lab_request_tests');
        Schema::dropIfExists('emergency_lab_requests');
    }
};
