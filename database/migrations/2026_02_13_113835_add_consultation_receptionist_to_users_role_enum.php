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
        // إصلاح أي بيانات غير صالحة أولاً
        \DB::statement("UPDATE users SET role = 'patient' WHERE role NOT IN ('admin', 'doctor', 'receptionist', 'patient')");
        
        // تحديث enum لحقل role لإضافة consultation_receptionist
        \DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'doctor', 'receptionist', 'patient', 'consultation_receptionist') DEFAULT 'patient'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إزالة consultation_receptionist من enum
        // أولاً تحويل أي مستخدمين لديهم هذا الدور إلى patient
        \DB::statement("UPDATE users SET role = 'patient' WHERE role = 'consultation_receptionist'");
        \DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'doctor', 'receptionist', 'patient') DEFAULT 'patient'");
    }
};
