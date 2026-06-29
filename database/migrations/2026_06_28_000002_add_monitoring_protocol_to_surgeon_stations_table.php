<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surgeon_stations', function (Blueprint $table) {
            $table->enum('monitoring_protocol', ['standard', 'fluid_monitoring', 'intensive'])
                ->default('standard')
                ->after('treatment_plan');
        });
    }

    public function down(): void
    {
        Schema::table('surgeon_stations', function (Blueprint $table) {
            $table->dropColumn('monitoring_protocol');
        });
    }
};
