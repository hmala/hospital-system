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
        Schema::table('doctor_commission_settings', function (Blueprint $table) {
            $table->decimal('hi_fixed_amount', 10, 2)->nullable()->after('fixed_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_commission_settings', function (Blueprint $table) {
            $table->dropColumn('hi_fixed_amount');
        });
    }
};
