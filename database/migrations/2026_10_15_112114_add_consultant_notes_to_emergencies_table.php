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
        Schema::table('emergencies', function (Blueprint $table) {
            $table->text('consultant_notes')->nullable()->after('notes');
            $table->string('consultant_recommendation', 100)->nullable()->after('consultant_notes');
            $table->timestamp('consultation_responded_at')->nullable()->after('consultant_recommendation');
            $table->foreignId('consultant_doctor_id')->nullable()->after('consultation_responded_at')->constrained('doctors')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            $table->dropForeign(['consultant_doctor_id']);
            $table->dropColumn(['consultant_notes', 'consultant_recommendation', 'consultation_responded_at', 'consultant_doctor_id']);
        });
    }
};
