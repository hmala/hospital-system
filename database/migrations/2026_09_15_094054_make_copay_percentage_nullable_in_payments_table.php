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
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('copay_percentage', 5, 2)->nullable()->default(0.00)->change();
            $table->decimal('total_amount', 12, 2)->nullable()->change();
            $table->decimal('patient_share', 12, 2)->nullable()->change();
            $table->decimal('insurance_share', 12, 2)->nullable()->change();
            $table->string('claim_status')->nullable()->default('none')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('copay_percentage', 5, 2)->default(0.00)->change();
        });
    }
};
