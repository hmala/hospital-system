<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('emergency_radiology_requests', 'payment_status')) {
            Schema::table('emergency_radiology_requests', function (Blueprint $table) {
                $table->string('payment_status', 20)->default('pending')->after('payment_id');
            });
        }

        if (! Schema::hasColumn('emergency_lab_requests', 'payment_status')) {
            Schema::table('emergency_lab_requests', function (Blueprint $table) {
                $table->string('payment_status', 20)->default('pending')->after('payment_id');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE emergency_radiology_requests MODIFY COLUMN payment_status ENUM('pending', 'paid', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE emergency_lab_requests MODIFY COLUMN payment_status ENUM('pending', 'paid', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE emergency_radiology_requests MODIFY COLUMN payment_status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE emergency_lab_requests MODIFY COLUMN payment_status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
