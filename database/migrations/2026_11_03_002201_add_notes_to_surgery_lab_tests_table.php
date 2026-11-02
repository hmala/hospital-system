<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('surgery_lab_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('surgery_lab_tests', 'notes')) {
                $table->text('notes')->nullable()->after('completed_at');
            }
        });

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE surgery_lab_tests MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgery_lab_tests', function (Blueprint $table) {
            if (Schema::hasColumn('surgery_lab_tests', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
