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
        Schema::table('resident_station_follow_ups', function (Blueprint $table) {
            if (! Schema::hasColumn('resident_station_follow_ups', 'clinical_signs')) {
                $table->json('clinical_signs')->nullable()->after('session');
            }
            if (! Schema::hasColumn('resident_station_follow_ups', 'other_notes')) {
                $table->text('other_notes')->nullable()->after('notes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resident_station_follow_ups', function (Blueprint $table) {
            if (Schema::hasColumn('resident_station_follow_ups', 'clinical_signs')) {
                $table->dropColumn('clinical_signs');
            }
            if (Schema::hasColumn('resident_station_follow_ups', 'other_notes')) {
                $table->dropColumn('other_notes');
            }
        });
    }
};
