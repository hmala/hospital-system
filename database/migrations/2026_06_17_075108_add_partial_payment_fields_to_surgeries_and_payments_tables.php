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
        // 1. Update surgeries table
        Schema::table('surgeries', function (Blueprint $table) {
            $table->decimal('surgery_fee_paid_amount', 12, 2)->default(0.00)->after('surgery_fee');
            $table->decimal('room_fee_paid_amount', 12, 2)->default(0.00)->after('room_fee');
        });

        // 2. Update payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('surgery_id')->nullable()->after('emergency_id')->constrained('surgeries')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Update payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['surgery_id']);
            $table->dropColumn('surgery_id');
        });

        // 2. Update surgeries table
        Schema::table('surgeries', function (Blueprint $table) {
            $table->dropColumn(['surgery_fee_paid_amount', 'room_fee_paid_amount']);
        });
    }
};
