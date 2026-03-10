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
        Schema::create('emergency_radiology_requests', function (Blueprint $table) {
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

        // جدول العلاقة بين الطلب وأنواع الأشعة
        Schema::create('emergency_radiology_request_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emergency_radiology_request_id', 'emerg_rad_req_id')->constrained('emergency_radiology_requests', 'id', 'emerg_rad_req_fk')->onDelete('cascade');
            $table->foreignId('radiology_type_id')->constrained()->onDelete('cascade');
            $table->text('result')->nullable();
            $table->text('image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_radiology_request_types');
        Schema::dropIfExists('emergency_radiology_requests');
    }
};
