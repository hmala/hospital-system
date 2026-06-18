<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('resident_station_follow_ups', function (Blueprint $table) {
            if (!Schema::hasColumn('resident_station_follow_ups', 'resident_name')) {
                $table->string('resident_name')->nullable()->after('resident_id');
            }
        });
    }

    public function down()
    {
        Schema::table('resident_station_follow_ups', function (Blueprint $table) {
            $table->dropColumn('resident_name');
        });
    }
};
