<?php
// database/migrations/2024_01_10_add_visit_time_to_visits_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->time('visit_time')->after('visit_date');
            $table->foreignId('appointment_id')->nullable()->after('department_id')->constrained()->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn(['visit_time', 'appointment_id']);
        });
    }
};