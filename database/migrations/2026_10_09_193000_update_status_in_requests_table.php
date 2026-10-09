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
        if (Schema::hasTable('requests')) {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE `requests` MODIFY COLUMN `status` VARCHAR(30) NOT NULL DEFAULT 'pending'");
            } else {
                Schema::table('requests', function (Blueprint $table) {
                    $table->string('status', 30)->default('pending')->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('requests')) {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE `requests` MODIFY COLUMN `status` ENUM('pending', 'in_progress', 'completed', 'cancelled', 'pending_service_selection') NOT NULL DEFAULT 'pending'");
            }
        }
    }
};
