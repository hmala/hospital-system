<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            // إزالة القيد الفريد أولاً
            $table->dropUnique(['doctor_id', 'appointment_date', 'appointment_time']);
            // إزالة الحقل
            $table->dropColumn('appointment_time');
        });
    }

    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->time('appointment_time');
            $table->unique(['doctor_id', 'appointment_date', 'appointment_time']);
        });
    }
};