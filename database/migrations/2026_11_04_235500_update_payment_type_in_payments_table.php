<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_type VARCHAR(50) DEFAULT 'appointment'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_type ENUM('appointment', 'lab', 'radiology', 'pharmacy', 'surgery', 'emergency', 'other') DEFAULT 'appointment'");
        }
    }
};
