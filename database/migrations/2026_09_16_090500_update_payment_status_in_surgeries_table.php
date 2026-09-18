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
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `surgeries` MODIFY COLUMN `payment_status` ENUM('pending', 'paid', 'partial', 'partially_paid', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending'");
        } else {
            Schema::table('surgeries', function (Blueprint $table) {
                $table->string('payment_status', 50)->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `surgeries` MODIFY COLUMN `payment_status` ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
