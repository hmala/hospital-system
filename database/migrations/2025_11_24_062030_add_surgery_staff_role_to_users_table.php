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
        // إضافة دور surgery_staff إلى enum
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') { DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'doctor', 'receptionist', 'patient', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'surgery_staff') DEFAULT 'patient'"); }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') { DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'doctor', 'receptionist', 'patient', 'lab_staff', 'radiology_staff', 'pharmacy_staff') DEFAULT 'patient'"); }
    }
};
