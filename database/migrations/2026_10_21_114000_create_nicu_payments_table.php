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
        // 1. تنظيف جدول payments العام وإزالة أي حقل دخيل للخدج إن وُجد
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'incubator_reservation_id')) {
            Schema::table('payments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['incubator_reservation_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('incubator_reservation_id');
            });
        }

        // 2. إنشاء جدول مستقل تماماً لمدفوعات وأمانات الخدج nicu_payments
        if (!Schema::hasTable('nicu_payments')) {
            Schema::create('nicu_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('incubator_reservation_id')
                    ->constrained('incubator_reservations')
                    ->cascadeOnDelete();
                $table->foreignId('patient_id')
                    ->nullable()
                    ->constrained('patients')
                    ->nullOnDelete();
                $table->string('receipt_number', 50)->unique();
                $table->string('payment_type', 30)->default('deposit'); // deposit (أمانات), interim (دفعة تحت الحساب), final_settlement (تسوية خروج), refund (استرجاع)
                $table->decimal('amount', 12, 2);
                $table->string('payment_method', 30)->default('cash'); // cash, card, etc.
                $table->string('financial_status', 30)->default('paid'); // paid, refunded, voided
                $table->foreignId('cashier_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamp('paid_at')->useCurrent();
                $table->timestamps();

                $table->index(['incubator_reservation_id', 'financial_status']);
                $table->index(['paid_at', 'cashier_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nicu_payments');
    }
};
