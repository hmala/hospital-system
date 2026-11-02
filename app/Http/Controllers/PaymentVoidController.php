<?php

namespace App\Http\Controllers;

use App\Http\Requests\VoidPaymentRequest;
use App\Models\Appointment;
use App\Models\Emergency;
use App\Models\EmergencyLabRequest;
use App\Models\EmergencyRadiologyRequest;
use App\Models\Payment;
use App\Models\PaymentVoidLog;
use App\Models\Request as MedicalRequest;
use App\Models\Visit;
use App\Services\UserActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentVoidController extends Controller
{
    /**
     * عرض سجل تدقيق الوصولات الملغاة والمبطلة للرقابة والتدقيق المالي (Audit Trail).
     */
    public function logs(Request $request)
    {
        $user = auth()->user();
        if (! $user->can('void payment receipt') && ! $user->hasRole(['admin', 'accountant', 'hospital_admin'])) {
            abort(403, 'غير مصرح لك بعرض سجل تدقيق الوصولات الملغاة.');
        }

        $query = PaymentVoidLog::with(['patient.user', 'cashier', 'voidedBy', 'payment']);

        // فلترة بالبحث (رقم الوصل، اسم المريض، السبب)
        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('void_reason', 'like', "%{$search}%")
                    ->orWhereHas('patient.user', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('voidedBy', function ($qv) use ($search) {
                        $qv->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // فلترة بنوع الإجراء (rebill / refund)
        if ($voidType = $request->get('void_type')) {
            $query->where('void_type', $voidType);
        }

        // فلترة بالتاريخ
        if ($fromDate = $request->get('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate = $request->get('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        // إحصائيات التدقيق
        $statsQuery = clone $query;
        $totalLogsCount = (clone $statsQuery)->count();
        $rebillCount = (clone $statsQuery)->where('void_type', 'rebill')->count();
        $refundCount = (clone $statsQuery)->where('void_type', 'refund')->count();
        $totalVoidedAmount = (clone $statsQuery)->sum('amount');

        $logs = $query->latest()->paginate(20)->withQueryString();

        return view('cashier.payments.void-logs', compact(
            'logs',
            'totalLogsCount',
            'rebillCount',
            'refundCount',
            'totalVoidedAmount',
            'search',
            'voidType',
            'fromDate',
            'toDate'
        ));
    }

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

            // جمع كافة معرفات الطلبات الطبية المرتبطة بهذا الوصل المالي بأي شكل (request_id, lab_request_ids, radiology_request_ids, أو payment_id)
            $associatedRequestIds = [];
            if ($payment->request_id) {
                $associatedRequestIds[] = (int) $payment->request_id;
            }
            if (! empty($payment->lab_request_ids)) {
                $labIds = is_array($payment->lab_request_ids) ? $payment->lab_request_ids : json_decode($payment->lab_request_ids, true);
                if (is_array($labIds)) {
                    $associatedRequestIds = array_merge($associatedRequestIds, array_map('intval', $labIds));
                }
            }
            if (! empty($payment->radiology_request_ids)) {
                $radIds = is_array($payment->radiology_request_ids) ? $payment->radiology_request_ids : json_decode($payment->radiology_request_ids, true);
                if (is_array($radIds)) {
                    $associatedRequestIds = array_merge($associatedRequestIds, array_map('intval', $radIds));
                }
            }
            $directPaymentReqIds = MedicalRequest::where('payment_id', $payment->id)->pluck('id')->toArray();
            $associatedRequestIds = array_values(array_unique(array_filter(array_merge($associatedRequestIds, $directPaymentReqIds))));

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
                        ->update(['payment_status' => 'pending', 'status' => 'pending', 'payment_id' => null]);
                    EmergencyRadiologyRequest::where('payment_id', $payment->id)
                        ->orWhere('emergency_id', $payment->emergency_id)
                        ->update(['payment_status' => 'pending', 'status' => 'pending', 'payment_id' => null]);
                    DB::table('payments')->where('id', $payment->id)->update(['created_at' => now()]);
                }

                // ب) المواعيد والاستشارية
                if ($payment->appointment_id) {
                    Appointment::where('id', $payment->appointment_id)->update([
                        'payment_status' => 'pending',
                        'appointment_date' => now()->toDateString(),
                    ]);
                }

                // ج) الطلبات الطبية (مختبر / أشعة)
                if (! empty($associatedRequestIds)) {
                    MedicalRequest::whereIn('id', $associatedRequestIds)->update([
                        'status' => 'pending',
                        'payment_status' => 'pending',
                        'payment_id' => null,
                        'created_at' => now(),
                    ]);

                    // إعادة حالة الزيارات المرتبطة إلى pending_payment لتظهر في طابور الكاشير للتسديد
                    $linkedVisitIds = MedicalRequest::whereIn('id', $associatedRequestIds)->whereNotNull('visit_id')->pluck('visit_id')->unique()->toArray();
                    if (! empty($linkedVisitIds)) {
                        Visit::whereIn('id', $linkedVisitIds)
                            ->where('status', 'scheduled')
                            ->update(['status' => 'pending_payment']);
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
                    EmergencyRadiologyRequest::where('payment_id', $payment->id)
                        ->orWhere('emergency_id', $payment->emergency_id)
                        ->update(['status' => 'cancelled', 'payment_status' => 'cancelled']);
                }
                if (! empty($associatedRequestIds)) {
                    $reqsToCancel = MedicalRequest::whereIn('id', $associatedRequestIds)->get();
                    foreach ($reqsToCancel as $rItem) {
                        $rDetails = is_string($rItem->details) ? json_decode($rItem->details, true) : ($rItem->details ?? []);
                        if (! is_array($rDetails)) {
                            $rDetails = [];
                        }
                        $rDetails['cancellation_reason'] = 'استرداد نقدي لدى الكاشير: '.$reason;
                        $rDetails['cancelled_by_cashier_id'] = $userId;
                        $rDetails['cancelled_at'] = now()->toDateTimeString();

                        $rItem->update([
                            'status' => 'cancelled',
                            'payment_status' => 'cancelled',
                            'details' => $rDetails,
                        ]);
                    }
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
