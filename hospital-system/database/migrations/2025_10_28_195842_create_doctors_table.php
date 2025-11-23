<?php
// database/migrations/2024_01_03_create_doctors_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->string('specialization');
            $table->string('qualification');
            $table->string('license_number')->unique();
            $table->integer('experience_years')->default(0);
            $table->text('bio')->nullable();
            $table->json('schedule')->nullable(); // الجدول الزمني
            $table->decimal('consultation_fee', 10, 2);
            $table->integer('max_patients_per_day')->default(20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('doctors');
    }
};