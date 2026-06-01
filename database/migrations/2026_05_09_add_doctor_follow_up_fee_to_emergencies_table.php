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
        Schema::table('emergencies', function (Blueprint $table) {
            if (!Schema::hasColumn('emergencies', 'doctor_follow_up_fee')) {
                $table->unsignedBigInteger('doctor_follow_up_fee')->default(0)->after('requires_surgery');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            if (Schema::hasColumn('emergencies', 'doctor_follow_up_fee')) {
                $table->dropColumn('doctor_follow_up_fee');
            }
        });
    }
};
