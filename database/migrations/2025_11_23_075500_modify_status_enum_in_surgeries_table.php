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
        // We use raw SQL to modify the ENUM column to include 'waiting'
        DB::statement("ALTER TABLE surgeries MODIFY COLUMN status ENUM('scheduled', 'waiting', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the original ENUM values
        // WARNING: This will fail if there are records with 'waiting' status
        DB::statement("ALTER TABLE surgeries MODIFY COLUMN status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled'");
    }
};
