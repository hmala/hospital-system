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
        if (!Schema::hasColumn('emergency_vital_signs', 'patient_id')) {
            Schema::table('emergency_vital_signs', function (Blueprint $table) {
                $table->foreignId('patient_id')->nullable()->after('emergency_id')->constrained()->nullOnDelete();
                $table->index(['patient_id', 'emergency_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_vital_signs', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['patient_id', 'emergency_id']);
            $table->dropColumn('patient_id');
        });
    }
};
