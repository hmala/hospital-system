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
            $table->foreignId('lab_test_id')->nullable()->change();
        });

        Schema::table('surgery_radiology_tests', function (Blueprint $table) {
            $table->foreignId('radiology_type_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgery_lab_tests', function (Blueprint $table) {
            $table->foreignId('lab_test_id')->nullable(false)->change();
        });

        Schema::table('surgery_radiology_tests', function (Blueprint $table) {
            $table->foreignId('radiology_type_id')->nullable(false)->change();
        });
    }
};
