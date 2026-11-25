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
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                if (Schema::hasColumn('departments', 'working_hours_start')) {
                    $table->time('working_hours_start')->nullable()->change();
                }
                if (Schema::hasColumn('departments', 'working_hours_end')) {
                    $table->time('working_hours_end')->nullable()->change();
                }
                if (Schema::hasColumn('departments', 'max_patients_per_day')) {
                    $table->integer('max_patients_per_day')->nullable()->default(null)->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                if (Schema::hasColumn('departments', 'working_hours_start')) {
                    $table->time('working_hours_start')->nullable(false)->default('08:00:00')->change();
                }
                if (Schema::hasColumn('departments', 'working_hours_end')) {
                    $table->time('working_hours_end')->nullable(false)->default('16:00:00')->change();
                }
                if (Schema::hasColumn('departments', 'max_patients_per_day')) {
                    $table->integer('max_patients_per_day')->nullable(false)->default(30)->change();
                }
            });
        }
    }
};
