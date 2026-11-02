<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. توسيع enum طريقة الدفع في MySQL ليشمل 'split'
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('pending', 'cash', 'card', 'insurance', 'split') DEFAULT 'cash'");
        }

        // 2. إضافة حقول مبالغ الدفع المركب (كاش + بطاقة)
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'cash_amount')) {
                $table->decimal('cash_amount', 12, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('payments', 'card_amount')) {
                $table->decimal('card_amount', 12, 2)->nullable()->after('cash_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'card_amount')) {
                $table->dropColumn('card_amount');
            }
            if (Schema::hasColumn('payments', 'cash_amount')) {
                $table->dropColumn('cash_amount');
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('pending', 'cash', 'card', 'insurance') DEFAULT 'cash'");
        }
    }
};
