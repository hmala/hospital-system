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
        Schema::table('appointments', function (Blueprint $table) {
            // $table->foreignId('patient_id')->constrained('users')->onDelete('cascade'); // موجود بالفعل
            // $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade'); // موجود بالفعل
            // $table->date('appointment_date'); // موجود بالفعل
            // $table->time('appointment_time'); // موجود بالفعل
            // $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending'); // موجود بالفعل
            // $table->text('notes')->nullable(); // موجود بالفعل
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropForeign(['doctor_id']);
            $table->dropColumn(['patient_id', 'doctor_id', 'appointment_date', 'appointment_time', 'status', 'notes']);
        });
    }
};
