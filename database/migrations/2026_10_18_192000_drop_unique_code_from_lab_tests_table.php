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
        if (Schema::hasTable('lab_tests')) {
            $driver = DB::getDriverName();
            
            if ($driver === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS lab_tests_code_unique');
                DB::statement('CREATE INDEX IF NOT EXISTS lab_tests_code_index ON lab_tests (code)');
            } else {
                // MySQL / MariaDB
                $indexNames = collect(DB::select("SHOW INDEX FROM lab_tests"))->pluck('Key_name')->unique()->toArray();
                
                if (in_array('lab_tests_code_unique', $indexNames)) {
                    Schema::table('lab_tests', function (Blueprint $table) {
                        $table->dropUnique('lab_tests_code_unique');
                    });
                }
                
                if (!in_array('lab_tests_code_index', $indexNames)) {
                    Schema::table('lab_tests', function (Blueprint $table) {
                        $table->index('code', 'lab_tests_code_index');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('lab_tests')) {
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS lab_tests_code_index');
            } else {
                Schema::table('lab_tests', function (Blueprint $table) {
                    $table->dropIndex('lab_tests_code_index');
                    $table->unique('code', 'lab_tests_code_unique');
                });
            }
        }
    }
};
