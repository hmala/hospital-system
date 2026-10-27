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
        // 1. إضافة حقول الإلغاء لجدول المدفوعات payments
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'voided_at')) {
                    $table->timestamp('voided_at')->nullable()->after('paid_at');
                }
                if (! Schema::hasColumn('payments', 'voided_by')) {
                    $table->foreignId('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('payments', 'void_type')) {
                    $table->string('void_type', 30)->nullable()->after('voided_by'); // 'rebill', 'refund'
                }
                if (! Schema::hasColumn('payments', 'void_reason')) {
                    $table->text('void_reason')->nullable()->after('void_type');
                }
                if (! Schema::hasColumn('payments', 'original_receipt_number')) {
                    $table->string('original_receipt_number', 100)->nullable()->after('void_reason');
                }
            });
        }

        // 2. إنشاء جدول سجل تدقيق الإلغاءات payment_void_logs
        if (! Schema::hasTable('payment_void_logs')) {
            Schema::create('payment_void_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
                $table->string('receipt_number', 100)->nullable();
                $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();
                $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('voided_by')->constrained('users')->cascadeOnDelete();
                $table->decimal('amount', 12, 2);
                $table->decimal('patient_share', 12, 2)->default(0);
                $table->decimal('insurance_share', 12, 2)->default(0);
                $table->string('void_type', 30); // 'rebill', 'refund'
                $table->text('void_reason');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_void_logs');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropColumn([
                'voided_at',
                'voided_by',
                'void_type',
                'void_reason',
                'original_receipt_number',
            ]);
        });
    }
};
