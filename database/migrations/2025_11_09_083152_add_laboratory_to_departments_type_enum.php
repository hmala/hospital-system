<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') { DB::statement("ALTER TABLE departments MODIFY COLUMN type ENUM('internal', 'surgery', 'pediatrics', 'obstetrics', 'orthopedics', 'cardiology', 'dentistry', 'dermatology', 'emergency', 'laboratory', 'other')"); }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') { DB::statement("ALTER TABLE departments MODIFY COLUMN type ENUM('internal', 'surgery', 'pediatrics', 'obstetrics', 'orthopedics', 'cardiology', 'dentistry', 'dermatology', 'emergency', 'other')"); }
    }
};
