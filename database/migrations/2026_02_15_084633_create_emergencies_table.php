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
        Schema::create('emergencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('nurse_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('priority', ['critical', 'urgent', 'semi_urgent', 'non_urgent'])->default('urgent');
            $table->enum('status', ['waiting', 'in_progress', 'completed', 'transferred', 'discharged'])->default('waiting');
            $table->enum('emergency_type', [
                'trauma', 'cardiac', 'respiratory', 'neurological', 'poisoning', 
                'burns', 'allergic', 'pediatric', 'obstetric', 'general'
            ]);
            $table->text('symptoms');
            $table->text('initial_assessment')->nullable();
            $table->text('treatment_given')->nullable();
            $table->text('notes')->nullable();
            $table->json('vital_signs')->nullable(); // blood_pressure, heart_rate, temperature, etc.
            $table->timestamp('admission_time');
            $table->timestamp('discharge_time')->nullable();
            $table->string('room_assigned')->nullable();
            $table->boolean('requires_surgery')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergencies');
    }
};
