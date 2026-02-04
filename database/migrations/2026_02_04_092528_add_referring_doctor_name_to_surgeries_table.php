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
        Schema::table('surgeries', function (Blueprint $table) {
            if (!Schema::hasColumn('surgeries', 'referring_doctor_type')) {
                $table->string('referring_doctor_type')->default('internal')->after('surgery_type');
            }
            if (!Schema::hasColumn('surgeries', 'referring_doctor_name')) {
                $table->string('referring_doctor_name')->nullable()->after('referring_doctor_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            if (Schema::hasColumn('surgeries', 'referring_doctor_name')) {
                $table->dropColumn('referring_doctor_name');
            }
            if (Schema::hasColumn('surgeries', 'referring_doctor_type')) {
                $table->dropColumn('referring_doctor_type');
            }
        });
    }
};
