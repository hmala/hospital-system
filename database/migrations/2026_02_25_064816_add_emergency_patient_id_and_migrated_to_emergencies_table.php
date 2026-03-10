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
            $table->foreignId('emergency_patient_id')->nullable()->constrained('emergency_patients')->onDelete('set null');
            $table->boolean('patient_migrated')->default(false)->after('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            $table->dropForeign(['emergency_patient_id']);
            $table->dropColumn(['emergency_patient_id', 'patient_migrated']);
        });
    }
};
