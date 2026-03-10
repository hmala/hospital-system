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
        // إضافة emergency_staff إلى enum role
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'doctor', 'receptionist', 'patient', 'nurse', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'cashier', 'surgery_staff', 'consultation_receptionist', 'emergency_staff') DEFAULT 'patient'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إزالة emergency_staff من enum role
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'doctor', 'receptionist', 'patient', 'nurse', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'cashier', 'surgery_staff', 'consultation_receptionist') DEFAULT 'patient'");
    }
};
