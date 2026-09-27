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
        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `medicines` DROP INDEX `medicines_name_index`');
        } catch (\Throwable $e) {}

        try {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `medicines` DROP INDEX `medicines_generic_name_index`');
        } catch (\Throwable $e) {}

        Schema::table('medicines', function (Blueprint $table) {
            $table->text('generic_name')->nullable()->change();
            $table->text('name')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->string('generic_name', 255)->nullable()->change();
            $table->string('name', 255)->change();
            $table->index('name');
            $table->index('generic_name');
        });
    }
};
