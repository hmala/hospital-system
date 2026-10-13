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
            if (!Schema::hasColumn('emergencies', 'is_insured')) {
                $table->boolean('is_insured')->default(false)->after('patient_id');
            }
            if (!Schema::hasColumn('emergencies', 'insurance_type')) {
                $table->string('insurance_type', 20)->default('none')->after('is_insured');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            if (Schema::hasColumn('emergencies', 'insurance_type')) {
                $table->dropColumn('insurance_type');
            }
            if (Schema::hasColumn('emergencies', 'is_insured')) {
                $table->dropColumn('is_insured');
            }
        });
    }
};
