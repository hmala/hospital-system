<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('lab_tests', function (Blueprint $table) {
                $table->renameColumn('notes', 'price');
            });
        } else {
            DB::statement('ALTER TABLE lab_tests CHANGE notes price DECIMAL(10,2) DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('lab_tests', function (Blueprint $table) {
                $table->renameColumn('price', 'notes');
            });
        } else {
            DB::statement('ALTER TABLE lab_tests CHANGE price notes TEXT');
        }
    }
};
