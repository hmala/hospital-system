<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تصفية قيود الموعد الملغى #80 لد. زياد طارق (معرف: 193)
        // 1. حذف قيود إيرادات الاستشارية المعلقة للموعد الملغى
        DB::table('consultation_revenues')
            ->where('appointment_id', 80)
            ->orWhereIn('payment_id', [82, 86])
            ->delete();

        // 2. حذف مستحقات الطبيب المعلقة لدفعة الموعد الملغى
        DB::table('doctor_dues')
            ->where('doctor_id', 193)
            ->where(function ($q) {
                $q->whereIn('payment_id', [82, 86])
                  ->orWhere('notes', 'like', '%REC-20261013-0002%');
            })
            ->delete();

        // 3. حذف حركات الصندوق المعلقة للموعد الملغى
        DB::table('financial_transactions')
            ->where('related_type', 'App\\Models\\Payment')
            ->whereIn('related_id', [82, 86])
            ->delete();

        // 4. إعادة ضبط رصيد الحساب المالي لد. زياد (193)
        $account = DB::table('doctor_financial_accounts')
            ->where('doctor_id', 193)
            ->first();

        $totalEarned = (float) DB::table('doctor_dues')
            ->where('doctor_id', 193)
            ->sum('amount');

        $totalPaid = (float) ($account->total_paid ?? 0);
        $newBalance = round($totalEarned - $totalPaid, 2);

        if ($account) {
            DB::table('doctor_financial_accounts')
                ->where('doctor_id', 193)
                ->update([
                    'total_earned' => $totalEarned,
                    'balance' => $newBalance,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا حاجة لـ down migration لتنظيف البيانات القديمة
    }
};
