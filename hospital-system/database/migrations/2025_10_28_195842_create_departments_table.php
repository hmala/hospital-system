// database/migrations/2024_01_02_create_departments_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', [
                'internal',      // باطنية
                'surgery',       // جراحة
                'pediatrics',    // أطفال
                'obstetrics',    // نسائية
                'orthopedics',   // عظام
                'cardiology',    // قلب
                'dentistry',     // أسنان
                'dermatology',   // جلدية
                'emergency',     // طوارئ
                'laboratory',    // مختبر
                'other'          // أخرى
            ]);
            $table->string('room_number');
            $table->decimal('consultation_fee', 10, 2);
            $table->time('working_hours_start');
            $table->time('working_hours_end');
            $table->integer('max_patients_per_day')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('departments');
    }
};