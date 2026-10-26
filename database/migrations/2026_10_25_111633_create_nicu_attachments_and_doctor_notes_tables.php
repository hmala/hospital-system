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
        Schema::create('nicu_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incubator_reservation_id')->constrained('incubator_reservations')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['incubator_reservation_id', 'created_at']);
            $table->index('patient_id');
        });

        Schema::create('nicu_doctor_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incubator_reservation_id')->constrained('incubator_reservations')->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('doctor_name')->nullable();
            $table->text('content');
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index(['incubator_reservation_id', 'created_at']);
            $table->index(['incubator_reservation_id', 'is_pinned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nicu_doctor_notes');
        Schema::dropIfExists('nicu_attachments');
    }
};
