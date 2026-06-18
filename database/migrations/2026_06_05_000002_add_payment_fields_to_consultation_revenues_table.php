<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_revenues', function (Blueprint $table) {
            $table->string('receipt_number')->nullable()->after('payment_id');
            $table->string('payment_method')->nullable()->after('receipt_number');
            $table->string('payment_type')->nullable()->after('payment_method');
            $table->foreignId('cashier_id')->nullable()->constrained('users')->onDelete('set null')->after('payment_type');
            $table->string('movement_type')->nullable()->after('hospital_share');
            $table->string('transaction_reference')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_revenues', function (Blueprint $table) {
            $table->dropForeign(['cashier_id']);
            $table->dropColumn([
                'receipt_number',
                'payment_method',
                'payment_type',
                'cashier_id',
                'movement_type',
                'transaction_reference',
            ]);
        });
    }
};
