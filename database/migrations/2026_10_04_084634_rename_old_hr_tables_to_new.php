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
        // 1. Copy data from old employees to new hr_employees if they both exist
        if (Schema::hasTable('employees') && Schema::hasTable('hr_employees')) {
            $oldEmployees = DB::table('employees')->get();
            foreach ($oldEmployees as $emp) {
                if (!DB::table('hr_employees')->where('id', $emp->id)->exists()) {
                    DB::table('hr_employees')->insert((array)$emp);
                }
            }
        }

        // 2. Handle employee_documents table
        if (Schema::hasTable('employee_documents') && !Schema::hasTable('hr_employee_documents')) {
            // In MySQL, to change the foreign key, we have to drop the old one.
            // The old constraint name was employee_documents_employee_id_foreign
            Schema::table('employee_documents', function (Blueprint $table) {
                // Ignore errors if the key doesn't exist (e.g. SQLite tests)
                try {
                    $table->dropForeign('employee_documents_employee_id_foreign');
                } catch (\Exception $e) {
                    // Ignore
                }
            });

            // Rename table
            Schema::rename('employee_documents', 'hr_employee_documents');
            
            // Add new foreign key pointing to hr_employees
            Schema::table('hr_employee_documents', function (Blueprint $table) {
                $table->foreign('employee_id')->references('id')->on('hr_employees')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No safe down
    }
};
