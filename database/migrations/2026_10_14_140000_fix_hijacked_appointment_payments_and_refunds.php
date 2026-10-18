<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. تصحيح قيود الاسترجاع التاريخية التي سحبت مبالغ فحوصات بدلاً من كشفية الطبيب
        // الحالة المحددة: الوصل #79 للموعد #67
        DB::table('payments')
            ->where('id', 79)
            ->where('appointment_id', 67)
            ->where('amount', '<', -2500)
            ->update([
                'amount' => -2500.00,
                'description' => 'استرجاع دفعة كشفية موعد #67 (مصححة تلقائياً)',
                'notes' => 'تم تصحيح مبلغ الاسترجاع إلى كشفية الموعد الأصلية 2,500 د.ع'
            ]);

        // مسح عام لأي استرجاع موعد تجاوز قيمة دفعة الكشفية الأصلية
        $refunds = DB::table('payments')
            ->where('amount', '<', 0)
            ->where('payment_type', 'appointment')
            ->whereNotNull('appointment_id')
            ->get();

        foreach ($refunds as $rf) {
            $original = DB::table('payments')
                ->where('appointment_id', $rf->appointment_id)
                ->where('payment_type', 'appointment')
                ->where('amount', '>', 0)
                ->first();

            if ($original && abs((float)$rf->amount) > (float)$original->amount) {
                DB::table('payments')
                    ->where('id', $rf->id)
                    ->update(['amount' => -abs((float)$original->amount)]);
            }
        }

        // 2. فك ارتباط دفعات الفحوصات الطبية والمختبر من حقل payment_id في المواعيد وإعادة دفعة الكشفية الحقيقية
        $hijacked = DB::table('appointments')
            ->join('payments', 'appointments.payment_id', '=', 'payments.id')
            ->where('payments.payment_type', '!=', 'appointment')
            ->select('appointments.id', 'appointments.patient_id', 'appointments.payment_id')
            ->get();

        foreach ($hijacked as $item) {
            $truePayment = DB::table('payments')
                ->where('appointment_id', $item->id)
                ->where('payment_type', 'appointment')
                ->where('amount', '>', 0)
                ->latest('id')
                ->first();

            if ($truePayment) {
                DB::table('appointments')
                    ->where('id', $item->id)
                    ->update(['payment_id' => $truePayment->id]);
            } else {
                DB::table('appointments')
                    ->where('id', $item->id)
                    ->update(['payment_id' => null]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed for data sanitization
    }
};
