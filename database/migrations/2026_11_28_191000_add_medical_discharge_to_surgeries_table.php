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
        Schema::table('surgeries', function (Blueprint $table) {
            if (! Schema::hasColumn('surgeries', 'medical_discharged_at')) {
                $table->timestamp('medical_discharged_at')->nullable()->after('treatment_plan');
            }
            if (! Schema::hasColumn('surgeries', 'medical_discharge_by')) {
                $table->foreignId('medical_discharge_by')->nullable()->after('medical_discharged_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('surgeries', 'medical_discharge_notes')) {
                $table->text('medical_discharge_notes')->nullable()->after('medical_discharge_by');
            }
            if (! Schema::hasColumn('surgeries', 'discharged_by')) {
                $table->foreignId('discharged_by')->nullable()->after('discharged_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            if (Schema::hasColumn('surgeries', 'medical_discharge_by')) {
                $table->dropForeign(['medical_discharge_by']);
                $table->dropColumn('medical_discharge_by');
            }
            if (Schema::hasColumn('surgeries', 'medical_discharged_at')) {
                $table->dropColumn('medical_discharged_at');
            }
            if (Schema::hasColumn('surgeries', 'medical_discharge_notes')) {
                $table->dropColumn('medical_discharge_notes');
            }
            if (Schema::hasColumn('surgeries', 'discharged_by')) {
                $table->dropForeign(['discharged_by']);
                $table->dropColumn('discharged_by');
            }
        });
    }
};
