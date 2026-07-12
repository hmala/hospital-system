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
        Schema::table('medical_devices', function (Blueprint $table) {
            $table->decimal('usage_price', 12, 2)->nullable()->default(0.00)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medical_devices', function (Blueprint $table) {
            $table->dropColumn('usage_price');
        });
    }
};
