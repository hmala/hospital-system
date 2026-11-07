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
        Schema::table('resident_stations', function (Blueprint $table) {
            $table->timestamp('vitals_recorded_at')->nullable()->after('completed_at');
            $table->foreignId('vitals_recorded_by')->nullable()->after('vitals_recorded_at')->constrained('users')->onDelete('set null');
        });

        Schema::table('resident_station_readings', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('resident_id')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resident_stations', function (Blueprint $table) {
            $table->dropForeign(['vitals_recorded_by']);
            $table->dropColumn(['vitals_recorded_at', 'vitals_recorded_by']);
        });

        Schema::table('resident_station_readings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id']);
        });
    }
};
