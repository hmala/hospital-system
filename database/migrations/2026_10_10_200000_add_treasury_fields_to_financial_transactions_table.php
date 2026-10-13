<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('transaction_type');
            $table->enum('voucher_type', ['inflow', 'outflow'])->nullable()->after('category');
            $table->string('voucher_number', 50)->nullable()->after('voucher_type');
            $table->string('payment_method', 30)->default('cash')->after('currency');
            $table->text('notes')->nullable()->after('description');

            $table->index(['voucher_type', 'category']);
            $table->index('voucher_number');
        });

        // تحديث الحركات القديمة للتوافق الفوري مع نظام الخزينة
        $driver = DB::getDriverName();
        $revConcat = $driver === 'sqlite' ? "('REV-' || id)" : "CONCAT('REV-', id)";
        $expConcat = $driver === 'sqlite' ? "('EXP-' || id)" : "CONCAT('EXP-', id)";

        DB::table('financial_transactions')
            ->where('transaction_type', 'hospital_revenue')
            ->update([
                'voucher_type' => 'inflow',
                'category' => 'consultation',
                'voucher_number' => DB::raw($revConcat),
            ]);

        DB::table('financial_transactions')
            ->whereIn('transaction_type', ['expense', 'doctor_payment'])
            ->update([
                'voucher_type' => 'outflow',
                'category' => 'doctor_payout',
                'voucher_number' => DB::raw($expConcat),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropIndex(['voucher_type', 'category']);
            $table->dropIndex(['voucher_number']);
            $table->dropColumn(['category', 'voucher_type', 'voucher_number', 'payment_method', 'notes']);
        });
    }
};
