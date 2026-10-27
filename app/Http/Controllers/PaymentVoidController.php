<?php

namespace App\Http\Controllers;

use App\Http\Requests\VoidPaymentRequest;
use App\Models\Appointment;
use App\Models\Emergency;
use App\Models\EmergencyLabRequest;
use App\Models\Payment;
use App\Models\PaymentVoidLog;
use App\Models\Request as MedicalRequest;
use App\Services\UserActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class PaymentVoidController extends Controller
{
    /**
     * عرض صفحة إبطال الوصل المالي وإعادة فتحه للتصحيح.
     */
    public function showVoidForm(Payment $payment)
    {
        // 1. التحقق من الصلاحية
        if (! auth()->user()->can('void payment receipt')) {
            abort(403, 'غير مصرح لك بإبطال الوصولات المالية.');
        }

        // 2. التحقق من حالة الوصل
        if (is_null($payment->paid_at) && empty($payment->receipt_number)) {
            return redirect()->route('cashier.receipt', $payment->id)
                ->with('error', 'هذا القيد غير مسدد بالفعل ولا يمكن إبطاله.');
        }

        if ($payment->void_type === 'refund') {
            return redirect()->route('cashier.receipt', $payment->id)
                ->with('error', 'تم إبطال هذا الوصل مسبقاً وسداد استرجاعه.');
        }

        // 3. تجهيز بيانات المريض للعرض
        $p = $payment->patient;
        if (! $p && $payment->emergency) {
            $ep = $payment->emergency->emergencyPatient;
            $pname = $ep ? $ep->name : 'غير محدد';
            $pphone = $ep ? ($ep->phone ?? '-') : '-';
            $pid = '(طوارئ)';
        } else {
            $pname = $p ? ($p->user?->name ?? 'غير محدد') : 'غير محدد';
            $pphone = $p ? ($p->user?->phone ?? '-') : '-';
            $pid = $p ? ($p->national_id ?? '#'.$p->id) : '-';
        }

        $totalPatientSum = (float) $payment->amount;

        return view('cashier.payments.void', compact('payment', 'pname', 'pphone', 'pid', 'totalPatientSum'));
    }

    /**
     * إبطال وإلغاء وصل دفع مالي وإعادة فتحه للتصحيح أو الاسترداد.
     */
    public function void(VoidPaymentRequest $request, Payment $payment): JsonResponse|RedirectResponse
    {
        // 1. التحقق من حالة الوصل
        if (is_null($payment->paid_at) && empty($payment->receipt_number)) {
            $msg = 'هذا القيد غير مسدد بالفعل ولا يمكن إبطاله.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        if ($payment->void_type === 'refund') {
            $msg = 'تم إبطال هذا الوصل مسبقاً وسداد استرجاعه.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $oldReceipt = $payment->receipt_number ?: ('#'.$payment->id);
        $voidType = $request->void_type;
        $reason = $request->void_reason;
        $userId = auth()->id();

        DB::transaction(function () use ($payment, $oldReceipt, $voidType, $reason, $userId) {
            // 2. تسجيل حركة التدقيق الرقابي في payment_void_logs
            PaymentVoidLog::create([
                'payment_id' => $payment->id,
                'receipt_number' => $oldReceipt,
                'patient_id' => $payment->patient_id,
                'cashier_id' => $payment->cashier_id,
                'voided_by' => $userId,
                'amount' => $payment->amount,
                'patient_share' => $payment->patient_share ?? 0,
                'insurance_share' => $payment->insurance_share ?? 0,
                'void_type' => $voidType,
                'void_reason' => $reason,
            ]);

            // 3. معالجة الإلغاء حسب النوع
            if ($voidType === 'rebill') {
                // حالة إعادة الفتح للتسديد والتصحيح:
                $payment->update([
                    'paid_at' => null,
                    'receipt_number' => null,
                    'payment_method' => 'pending',
                    'voided_at' => now(),
                    'voided_by' => $userId,
                    'void_type' => 'rebill',
                    'void_reason' => $reason,
                    'original_receipt_number' => $oldReceipt,
                    'claim_status' => $payment->insurance_type && $payment->insurance_type !== 'none' ? 'pending' : $payment->claim_status,
                ]);

                // إعادة السجلات المرتبطة لحالة الانتظار غير المسدد:
                // أ) الطوارئ
                if ($payment->emergency_id) {
                    Emergency::where('id', $payment->emergency_id)->update(['payment_status' => 'pending']);
                    EmergencyLabRequest::where('payment_id', $payment->id)
                        ->orWhere('emergency_id', $payment->emergency_id)
                        ->update(['payment_status' => 'pending', 'status' => 'pending']);
                }

                // ب) المواعيد والاستشارية
                if ($payment->appointment_id) {
                    Appointment::where('id', $payment->appointment_id)->update(['payment_status' => 'pending']);
                }

                // ج) الطلبات الطبية (مختبر / أشعة)
                if ($payment->request_id) {
                    MedicalRequest::where('id', $payment->request_id)->update([
                        'status' => 'pending',
                    ]);
                }
                if (! empty($payment->lab_request_ids)) {
                    $labIds = is_array($payment->lab_request_ids) ? $payment->lab_request_ids : json_decode($payment->lab_request_ids, true);
                    if (! empty($labIds)) {
                        MedicalRequest::whereIn('id', $labIds)->update(['status' => 'pending']);
                    }
                }
                if (! empty($payment->radiology_request_ids)) {
                    $radIds = is_array($payment->radiology_request_ids) ? $payment->radiology_request_ids : json_decode($payment->radiology_request_ids, true);
                    if (! empty($radIds)) {
                        MedicalRequest::whereIn('id', $radIds)->update(['status' => 'pending']);
                    }
                }
            } else {
                // حالة الاسترداد والإلغاء النهائي:
                $payment->update([
                    'voided_at' => now(),
                    'voided_by' => $userId,
                    'void_type' => 'refund',
                    'void_reason' => $reason,
                    'original_receipt_number' => $oldReceipt,
                    'claim_status' => 'cancelled',
                ]);

                // إلغاء السجلات المرتبطة
                if ($payment->appointment_id) {
                    Appointment::where('id', $payment->appointment_id)->update([
                        'status' => 'cancelled',
                        'payment_status' => 'cancelled',
                    ]);
                }
                if ($payment->emergency_id) {
                    EmergencyLabRequest::where('payment_id', $payment->id)
                        ->orWhere('emergency_id', $payment->emergency_id)
                        ->update(['status' => 'cancelled', 'payment_status' => 'cancelled']);
                }
                if ($payment->request_id) {
                    MedicalRequest::where('id', $payment->request_id)->update(['status' => 'cancelled']);
                }
            }

            // 4. تسجيل النشاط الإداري والرقابي الحساس
            UserActivityLogger::log(
                'void',
                'cashier',
                "قام بإبطال الوصل رقم ({$oldReceipt}) - نوع العملية: ".($voidType === 'rebill' ? 'إعادة فتح للتصحيح' : 'استرداد نقدي')." بالسبب: {$reason}",
                $payment,
                true,
                ['old_receipt' => $oldReceipt, 'void_type' => $voidType, 'reason' => $reason]
            );
        });

        $successMsg = $voidType === 'rebill'
            ? "تم إبطال الوصل ({$oldReceipt}) بنجاح، وأُعيد القيد إلى طابور الكاشير للتسديد بالمبلغ والنسبة الصحيحين."
            : "تم إبطال الوصل ({$oldReceipt}) بنجاح وتسجيل عملية الاسترداد للمريض.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'redirect_url' => route('cashier.index'),
            ]);
        }

        return redirect()->route('cashier.index')->with('success', $successMsg);
    }
}
