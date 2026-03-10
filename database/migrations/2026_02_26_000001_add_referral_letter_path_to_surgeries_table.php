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
            if (!Schema::hasColumn('surgeries', 'referral_letter_path')) {
                $table->string('referral_letter_path')->nullable()->after('referring_doctor_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            if (Schema::hasColumn('surgeries', 'referral_letter_path')) {
                $table->dropColumn('referral_letter_path');
            }
        });
    }
};