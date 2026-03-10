<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إضافة حقل حالة دفع رسوم العملية نفسها
     */
    public function up(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            $table->string('surgery_fee_paid')->default('pending')->after('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            $table->dropColumn('surgery_fee_paid');
        });
    }
};
