<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE emergencies MODIFY COLUMN payment_status ENUM('pending', 'paid', 'waived', 'cancelled', 'refunded') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE emergencies MODIFY COLUMN payment_status ENUM('pending', 'paid', 'waived', 'cancelled') DEFAULT 'pending'");
        }
    }
};
