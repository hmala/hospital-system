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
            if (!Schema::hasColumn('emergencies', 'arrival_mode')) {
                $table->string('arrival_mode', 100)->nullable()->after('emergency_type');
            }
            if (!Schema::hasColumn('emergencies', 'referred_from')) {
                $table->string('referred_from', 255)->nullable()->after('arrival_mode');
            }
            if (!Schema::hasColumn('emergencies', 'hpi')) {
                $table->text('hpi')->nullable()->after('symptoms');
            }
            if (!Schema::hasColumn('emergencies', 'medico_legal_notes')) {
                $table->text('medico_legal_notes')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('emergencies', 'gcs')) {
                $table->string('gcs', 20)->nullable()->default('15/15')->after('vital_signs');
            }
            if (!Schema::hasColumn('emergencies', 'discharge_destination')) {
                $table->string('discharge_destination', 100)->nullable()->after('discharge_type');
            }
            if (!Schema::hasColumn('emergencies', 'discharge_recommendations')) {
                $table->text('discharge_recommendations')->nullable()->after('discharge_notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            $columns = [
                'arrival_mode',
                'referred_from',
                'hpi',
                'medico_legal_notes',
                'gcs',
                'discharge_destination',
                'discharge_recommendations',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('emergencies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
