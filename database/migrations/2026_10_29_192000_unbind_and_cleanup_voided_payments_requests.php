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
        // 1. جلب كافة الوصولات المالية المبطلة أو الملغاة
        $voidedPayments = DB::table('payments')
            ->whereNotNull('voided_at')
            ->get(['id', 'void_type', 'void_reason', 'receipt_number']);

        foreach ($voidedPayments as $payment) {
            // أ) إذا كان الإلغاء نهائي استرداد نقدي (refund)
            if ($payment->void_type === 'refund') {
                $reqs = DB::table('requests')->where('payment_id', $payment->id)->get();
                foreach ($reqs as $r) {
                    $details = json_decode($r->details, true) ?: [];
                    $details['cancellation_reason'] = $details['cancellation_reason'] ?? ('استرداد نقدي للوصل: '.($payment->receipt_number ?: ('#'.$payment->id)));
                    $details['cancelled_at'] = now()->toDateTimeString();

                    DB::table('requests')->where('id', $r->id)->update([
                        'status' => 'cancelled',
                        'payment_status' => 'cancelled',
                        'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                    ]);
                }
            } else {
                // ب) إذا كان الإلغاء إعادة فتح للتصحيح (rebill)
                $reqs = DB::table('requests')->where('payment_id', $payment->id)->get();
                foreach ($reqs as $r) {
                    // التحقق هل تم تسديد نفس الزيارة لاحقاً بوصل جديد واستبعاد هذا الفحص
                    $hasNewerPaymentForVisit = DB::table('payments')
                        ->where('patient_id', $r->patient_id ?? null)
                        ->whereNull('voided_at')
                        ->where('created_at', '>', $r->created_at)
                        ->exists();

                    // إذا كانت هناك دفعة جديدة نشطة للزيارة ولم تشمل هذا الطلب، فيعتبر ملغياً بطلب المريض
                    if ($hasNewerPaymentForVisit && $r->visit_id) {
                        $otherVisitReqsPaid = DB::table('requests')
                            ->where('visit_id', $r->visit_id)
                            ->where('id', '!=', $r->id)
                            ->where('payment_status', 'paid')
                            ->whereNotNull('payment_id')
                            ->where('payment_id', '!=', $payment->id)
                            ->exists();

                        if ($otherVisitReqsPaid) {
                            $details = json_decode($r->details, true) ?: [];
                            $details['cancellation_reason'] = $details['cancellation_reason'] ?? 'استبعاد وإلغاء بطلب المريض عند إعادة فتح الوصل لدى الكاشير';
                            $details['cancelled_at'] = now()->toDateTimeString();

                            DB::table('requests')->where('id', $r->id)->update([
                                'status' => 'cancelled',
                                'payment_status' => 'cancelled',
                                'payment_id' => null,
                                'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                            ]);

                            continue;
                        }
                    }

                    // خلاف ذلك، إعادة فك ارتباط الوصل وإعادته للانتظار غير المسدد
                    DB::table('requests')->where('id', $r->id)->update([
                        'status' => 'pending',
                        'payment_status' => 'pending',
                        'payment_id' => null,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation needed for operational data cleanup
    }
};
