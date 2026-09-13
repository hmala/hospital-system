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
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'total_amount')) {
                    $table->decimal('total_amount', 10, 2)->default(0.00)->after('amount'); // إجمالي القيمة المعتمدة للبند/الفاتورة
                }
                if (!Schema::hasColumn('payments', 'patient_share')) {
                    $table->decimal('patient_share', 10, 2)->default(0.00)->after('total_amount'); // ما دفعه المريض نقداً
                }
                if (!Schema::hasColumn('payments', 'insurance_share')) {
                    $table->decimal('insurance_share', 10, 2)->default(0.00)->after('patient_share'); // ما تحمله الضمان
                }
                if (!Schema::hasColumn('payments', 'insurance_type')) {
                    $table->string('insurance_type')->default('none')->after('insurance_share'); // none, moi, hi
                }
                if (!Schema::hasColumn('payments', 'copay_percentage')) {
                    $table->decimal('copay_percentage', 5, 2)->default(0.00)->after('insurance_type'); // نسبة التحمل المجمدة بالفاتورة
                }
                if (!Schema::hasColumn('payments', 'insurance_card_no')) {
                    $table->string('insurance_card_no')->nullable()->after('copay_percentage'); // رقم باج/بطاقة الضمان
                }
                if (!Schema::hasColumn('payments', 'claim_status')) {
                    $table->string('claim_status')->default('none')->after('insurance_card_no'); // none, pending_claim, claimed, settled, rejected
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn([
                    'total_amount',
                    'patient_share',
                    'insurance_share',
                    'insurance_type',
                    'copay_percentage',
                    'insurance_card_no',
                    'claim_status'
                ]);
            });
        }
    }
};
