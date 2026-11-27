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
        Schema::table('surgery_radiology_tests', function (Blueprint $table) {
            if (! Schema::hasColumn('surgery_radiology_tests', 'doctor_id')) {
                $table->foreignId('doctor_id')->nullable()->after('radiology_type_id')->constrained('doctors')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgery_radiology_tests', function (Blueprint $table) {
            if (Schema::hasColumn('surgery_radiology_tests', 'doctor_id')) {
                $table->dropForeign(['doctor_id']);
                $table->dropColumn('doctor_id');
            }
        });
    }
};
