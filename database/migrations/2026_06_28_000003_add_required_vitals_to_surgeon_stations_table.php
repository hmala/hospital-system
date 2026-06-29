<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surgeon_stations', function (Blueprint $table) {
            $table->json('required_vitals')->nullable()->after('monitoring_protocol');
        });
    }

    public function down(): void
    {
        Schema::table('surgeon_stations', function (Blueprint $table) {
            $table->dropColumn('required_vitals');
        });
    }
};
