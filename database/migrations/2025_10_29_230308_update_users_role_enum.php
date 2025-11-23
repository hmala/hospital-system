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
        // في MySQL لا يمكن تحديث enum مباشرة، لذا سنستخدم raw SQL
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'doctor', 'receptionist', 'patient', 'lab_staff', 'radiology_staff', 'pharmacy_staff') DEFAULT 'patient'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'doctor', 'receptionist', 'patient') DEFAULT 'patient'");
    }
};
