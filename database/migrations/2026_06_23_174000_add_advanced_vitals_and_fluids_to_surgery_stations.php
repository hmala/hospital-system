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
        // Add columns to resident_stations
        Schema::table('resident_stations', function (Blueprint $table) {
            $table->string('pain_score')->nullable()->after('spo2');
            $table->string('rbs')->nullable()->after('pain_score');
            $table->string('gcs')->nullable()->after('rbs');
            $table->string('crt')->nullable()->after('gcs');
            
            // Intake & Output
            $table->decimal('intake_iv_fluids', 8, 2)->nullable()->after('crt');
            $table->decimal('intake_oral', 8, 2)->nullable()->after('intake_iv_fluids');
            $table->decimal('intake_blood', 8, 2)->nullable()->after('intake_oral');
            $table->decimal('output_urine', 8, 2)->nullable()->after('intake_blood');
            $table->decimal('output_drain', 8, 2)->nullable()->after('output_urine');
            $table->decimal('output_gtube_ng', 8, 2)->nullable()->after('output_drain');
            $table->decimal('output_vomiting', 8, 2)->nullable()->after('output_gtube_ng');
            $table->decimal('output_stool', 8, 2)->nullable()->after('output_vomiting');
            $table->decimal('fluid_balance', 8, 2)->nullable()->after('output_stool');
        });

        // Add columns to resident_station_readings
        Schema::table('resident_station_readings', function (Blueprint $table) {
            $table->string('pain_score')->nullable()->after('spo2');
            $table->string('rbs')->nullable()->after('pain_score');
            $table->string('gcs')->nullable()->after('rbs');
            $table->string('crt')->nullable()->after('gcs');
            
            // Intake & Output
            $table->decimal('intake_iv_fluids', 8, 2)->nullable()->after('crt');
            $table->decimal('intake_oral', 8, 2)->nullable()->after('intake_iv_fluids');
            $table->decimal('intake_blood', 8, 2)->nullable()->after('intake_oral');
            $table->decimal('output_urine', 8, 2)->nullable()->after('intake_blood');
            $table->decimal('output_drain', 8, 2)->nullable()->after('output_urine');
            $table->decimal('output_gtube_ng', 8, 2)->nullable()->after('output_drain');
            $table->decimal('output_vomiting', 8, 2)->nullable()->after('output_gtube_ng');
            $table->decimal('output_stool', 8, 2)->nullable()->after('output_vomiting');
            $table->decimal('fluid_balance', 8, 2)->nullable()->after('output_stool');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resident_stations', function (Blueprint $table) {
            $table->dropColumn([
                'pain_score', 'rbs', 'gcs', 'crt',
                'intake_iv_fluids', 'intake_oral', 'intake_blood',
                'output_urine', 'output_drain', 'output_gtube_ng', 'output_vomiting', 'output_stool',
                'fluid_balance'
            ]);
        });

        Schema::table('resident_station_readings', function (Blueprint $table) {
            $table->dropColumn([
                'pain_score', 'rbs', 'gcs', 'crt',
                'intake_iv_fluids', 'intake_oral', 'intake_blood',
                'output_urine', 'output_drain', 'output_gtube_ng', 'output_vomiting', 'output_stool',
                'fluid_balance'
            ]);
        });
    }
};
