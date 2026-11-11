<?php

namespace App\Http\Controllers;

use App\Models\CcuConsultation;
use App\Models\CcuPayment;
use App\Models\CcuService;
use App\Models\FinancialTransaction;
use App\Models\IcuAdmission;
use App\Models\IcuMedicalOrder;
use App\Services\CcuBillingService;
use App\Services\UserActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CcuCashierController extends Controller
{
    public function __construct(protected CcuBillingService $billingService) {}

    protected function authorizeCashier(): void
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view cashier') && ! $user->can('process payments') && ! $user->can('process refunds') && ! $user->can('view ccu statements')))) {
            abort(403, 'غير مصرح لك بالوصول إلى قسم حسابات وكاشير العناية المركزة');
        }
    }

    /**
     * شاشة تسديد وتصفية حساب مريض العناية المركزة (CCU Cashier Payment Desk)
     */
    public function showPaymentForm(IcuAdmission $admission)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view cashier') && ! $user->can('process payments') && ! $user->can('process refunds')))) {
            abort(403, 'غير مصرح لك بالوصول لشاشة تسديد رسوم العناية المركزة');
        }

        $admission->load([
            'bed',
            'ccuService',
            'doctor.user',
            'patient.user',
            'patient.healthInsuranceCategory',
            'ccuServiceLogs.ccuService',
            'ccuConsultations.doctor.user',
            'medicalOrders.labTest',
            'medicalOrders.radiologyType',
            'ccuPayments.cashier',
        ]);

        $stayDetails = $this->billingService->calculateStayDetails($admission);
        $packageServices = CcuService::active()->packages()->orderBy('id')->get();
        $addonServices = CcuService::active()->addons()->orderBy('id')->get();

        return view('cashier.ccu.payment-form', compact('admission', 'stayDetails', 'packageServices', 'addonServices'));
    }

    /**
     * معالجة تسديد دفعة أمانات / تحت الحساب / تصفية نهائية / أو استرجاع فائض
     */
    public function processPayment(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view cashier') && ! $user->can('process payments') && ! $user->can('process refunds')))) {
            abort(403, 'غير مصرح لك باستلام الدفعات أو صرف الاسترجاعات');
        }

        if ($request->input('payment_mode') === 'partial' && $request->filled('custom_paid_amount')) {
            $request->merge(['amount' => $request->input('custom_paid_amount')]);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,card',
            'payment_type' => 'nullable|in:deposit,interim,final_settlement,refund',
            'notes' => 'nullable|string|max:500',
            'payment_purpose' => 'nullable|string|max:100',
        ]);

        $amount = (float) $request->amount;
        $paymentType = $request->payment_type ?: CcuPayment::TYPE_INTERIM;
        $stayDetails = $this->billingService->calculateStayDetails($admission);

        if ($paymentType === CcuPayment::TYPE_REFUND) {
            $excessDeposit = (float) $stayDetails['excess_deposit'];

            if ($excessDeposit <= 0) {
                return redirect()->back()->with('error', 'لا يوجد أي فائض رسوم متبقي للاسترجاع في حساب هذا المريض.');
            }

            if ($amount > $excessDeposit) {
                return redirect()->back()->with('error', 'المبلغ المطلوب استرجاعه ('.number_format($amount).' د.ع) يتجاوز الحد الأقصى لفائض الرسوم المتوفر ('.number_format($excessDeposit).' د.ع).');
            }
        } else {
            $netBalance = (float) $stayDetails['net_balance'];
            if ($admission->status === IcuAdmission::STATUS_DISCHARGED || ($netBalance > 0 && $amount >= $netBalance)) {
                $paymentType = CcuPayment::TYPE_FINAL;
            } elseif ($netBalance <= 0) {
                $paymentType = CcuPayment::TYPE_DEPOSIT;
            }
        }

        $receiptNumber = CcuPayment::generateReceiptNumber();
        $patientName = $admission->patient?->user?->name ?? $admission->patient?->name ?? ('مريض #'.$admission->patient_id);
        $typeLabel = CcuPayment::PAYMENT_TYPES[$paymentType] ?? 'تسديد رسوم العناية المركزة';

        $defaultDesc = ($paymentType === CcuPayment::TYPE_REFUND)
            ? "صرف استرجاع فائض رسوم العناية المركزة لإضبارة #{$admission->id} - {$patientName}"
            : "{$typeLabel} للعناية المركزة إضبارة #{$admission->id} - {$patientName}";
        $desc = $request->payment_purpose ?: ($request->notes ?: $defaultDesc);

        $payment = CcuPayment::create([
            'icu_admission_id' => $admission->id,
            'patient_id' => $admission->patient_id,
            'cashier_id' => Auth::id(),
            'receipt_number' => $receiptNumber,
            'payment_type' => $paymentType,
            'amount' => $amount,
            'payment_method' => $request->payment_method,
            'financial_status' => 'paid',
            'notes' => $desc,
            'paid_at' => now(),
        ]);

        $isRefund = ($paymentType === CcuPayment::TYPE_REFUND);
        FinancialTransaction::create([
            'transaction_type' => $isRefund ? 'expense' : 'hospital_revenue',
            'voucher_type' => $isRefund ? 'outflow' : 'inflow',
            'category' => $isRefund ? 'refund' : 'icu',
            'voucher_number' => $receiptNumber,
            'related_type' => CcuPayment::class,
            'related_id' => $payment->id,
            'amount' => $amount,
            'currency' => 'IQD',
            'payment_method' => $request->payment_method ?: 'cash',
            'description' => $desc,
            'notes' => $desc,
            'performed_by_id' => Auth::id(),
            'performed_at' => now(),
        ]);

        UserActivityLogger::log(
            action: $isRefund ? 'ccu_refund_processed' : 'ccu_payment_received',
            category: 'cashier',
            description: $desc.' بمبلغ '.number_format($amount)." د.ع برقم وصل {$receiptNumber}",
            subject: $payment,
            isSensitive: false
        );

        $successMsg = $isRefund
            ? 'تم صرف واسترجاع مبلغ '.number_format($amount)." د.ع بنجاح برقم سند: {$receiptNumber}"
            : 'تم تسديد مبلغ '.number_format($amount)." د.ع بنجاح لحساب العناية المركزة برقم إيصال: {$receiptNumber}";

        return redirect()->route('cashier.ccu.receipt', $payment->id)
            ->with('success', $successMsg);
    }

    /**
     * تبديل احتساب التحليل أو الأشعة بضغطة زر بين:
     * (مشمول بالباقة اليومية = 0 د.ع) <-> (احتساب خارج الباقة = كاش/ضمان)
     * مع الحفاظ الدائم على القيمة الأصلية (original_price) لأغراض المطالبات والحسابات
     */
    public function toggleOrderBilling(Request $request, IcuMedicalOrder $order)
    {
        $user = Auth::user();
        $isAuthorized = $user && (
            $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'doctor', 'icu_doctor'])
            || $user->can('process payments')
            || $user->can('view cashier')
            || $user->can('manage ccu services')
            || $user->can('switch ccu services')
            || $user->can('manage icu medical orders')
        );
        if (! $isAuthorized) {
            abort(403, 'غير مصرح لك بتعديل حالة احتساب البند المالي');
        }

        $admission = $order->admission;
        if (! $admission) {
            return redirect()->back()->with('error', 'الطلب غير مرتبط بإضبارة عناية صالحة.');
        }

        // حفظ السعر الأصلي قبل أي تبديل لضمان عدم ضياع القيمة المرجعية في حسابات الضمان
        if (! $order->original_price || (float) $order->original_price <= 0) {
            $fallbackOrig = (float) $order->price;
            if ($fallbackOrig <= 0) {
                $fallbackOrig = $order->order_type === 'lab'
                    ? (float) ($order->labTest?->hi_price > 0 && $admission->is_insured ? $order->labTest->hi_price : ($order->labTest?->price ?? 0))
                    : (float) ($order->radiologyType?->hi_price > 0 && $admission->is_insured ? $order->radiologyType->hi_price : ($order->radiologyType?->base_price ?? $order->radiologyType?->price ?? 0));
            }
            $order->original_price = $fallbackOrig;
        }

        // حساب الحالة الحالية الفعلية قبل التبديل
        $stayBefore = $this->billingService->calculateStayDetails($admission);
        $currentOrder = $stayBefore['medical_orders']->firstWhere('id', $order->id);
        $currentlyBundled = $currentOrder ? (bool) $currentOrder->is_bundled : ($order->billing_mode === 'bundled');

        $targetMode = $request->input('billing_mode');
        if (! in_array($targetMode, ['bundled', 'extra', 'auto'], true)) {
            $targetMode = $currentlyBundled ? 'extra' : 'bundled';
        }

        $order->billing_mode = $targetMode;
        $order->save();

        // إعادة الاحتساب الفوري للموقف المالي ومطالبات الضمان
        $stayAfter = $this->billingService->calculateStayDetails($admission->fresh());

        $msg = $targetMode === 'bundled'
            ? "تم تحويل ({$order->item_name}) إلى [مشمول بالباقة اليومية - 0 د.ع على المريض] مع حفظ قيمته الأصلية في حسابات الضمان."
            : "تم تحويل ({$order->item_name}) إلى [محتسب منفرداً خارج الباقة].";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'billing_mode' => $targetMode,
                'message' => $msg,
                'stay_details' => [
                    'patient_grand_total' => $stayAfter['patient_grand_total'],
                    'insurance_grand_total' => $stayAfter['insurance_grand_total'],
                    'bundled_reference_total' => $stayAfter['bundled_reference_total'],
                    'net_balance' => $stayAfter['net_balance'],
                ],
            ]);
        }

        $redirect = redirect()->back()->with('success', $msg);
        if ($request->filled('_active_tab')) {
            $redirect->with('active_tab', $request->input('_active_tab'));
        }

        return $redirect;
    }

    /**
     * تبديل احتساب الاستشارة التخصصية بضغطة زر
     */
    public function toggleConsultationBilling(Request $request, CcuConsultation $consultation)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('process payments') && ! $user->can('view cashier') && ! $user->can('manage ccu services')))) {
            abort(403, 'غير مصرح لك بتعديل حالة احتساب الاستشارة');
        }

        $admission = $consultation->admission;
        if (! $consultation->original_fee || (float) $consultation->original_fee <= 0) {
            $consultation->original_fee = (float) $consultation->fee;
        }

        $targetMode = $request->input('billing_mode');
        if (! in_array($targetMode, ['bundled', 'extra', 'auto'], true)) {
            $targetMode = ($consultation->billing_mode === 'bundled') ? 'extra' : 'bundled';
        }

        $consultation->billing_mode = $targetMode;
        $consultation->save();

        if ($admission) {
            $this->billingService->calculateStayDetails($admission->fresh());
        }

        $msg = $targetMode === 'bundled'
            ? 'تم إدراج الاستشارة ضمن الباقة اليومية (0 د.ع على المريض) مع حفظ القيمة الأصلية.'
            : 'تم احتساب أجور الاستشارة كبند منفرد في الفاتورة.';

        $redirect = redirect()->back()->with('success', $msg);
        if ($request->filled('_active_tab')) {
            $redirect->with('active_tab', $request->input('_active_tab'));
        }

        return $redirect;
    }

    /**
     * عرض سند قبض / صرف العناية المركزة
     */
    public function showReceipt(CcuPayment $ccuPayment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view cashier') && ! $user->can('view payments')))) {
            abort(403, 'غير مصرح لك بعرض سند القبض');
        }

        $ccuPayment->load([
            'admission.bed',
            'admission.doctor.user',
            'admission.patient.user',
            'admission.ccuService',
            'cashier',
        ]);

        $stayDetails = $this->billingService->calculateStayDetails($ccuPayment->admission);

        return view('cashier.ccu-receipt', compact('ccuPayment', 'stayDetails'));
    }

    /**
     * طباعة وصل قبض حراري 78mm للعناية المركزة
     */
    public function printReceipt(CcuPayment $ccuPayment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view cashier') && ! $user->can('view payments')))) {
            abort(403, 'غير مصرح لك بطباعة سند القبض');
        }

        $ccuPayment->load([
            'admission.bed',
            'admission.doctor.user',
            'admission.patient.user',
            'admission.ccuService',
            'cashier',
        ]);

        $stayDetails = $this->billingService->calculateStayDetails($ccuPayment->admission);

        return view('cashier.ccu-receipt-print', compact('ccuPayment', 'stayDetails'));
    }

    /**
     * طباعة كشف تفصيلي شامل للفاتورة والمطالبات الرسمية لهيئة الضمان الصحي والمريض
     */
    public function printItemizedReceipt(IcuAdmission $admission)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view cashier') && ! $user->can('view payments') && ! $user->can('view ccu statements')))) {
            abort(403, 'غير مصرح لك بطباعة الكشف التفصيلي للعناية المركزة');
        }

        $admission->load([
            'bed',
            'ccuService',
            'doctor.user',
            'patient.user',
            'patient.healthInsuranceCategory',
            'ccuPayments.cashier',
            'medicalOrders.labTest',
            'medicalOrders.radiologyType',
            'ccuConsultations.doctor.user',
        ]);

        $stayDetails = $this->billingService->calculateStayDetails($admission);

        return view('cashier.ccu-itemized-print', compact('admission', 'stayDetails'));
    }

    /**
     * سجل الحركات المالية لصندوق العناية المركزة
     */
    public function financialMovements(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view ccu financial movements') && ! $user->can('view cashier')))) {
            abort(403, 'غير مصرح لك بالوصول إلى الحركات المالية للعناية المركزة');
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $type = $request->input('payment_type');
        $method = $request->input('payment_method');

        $query = CcuPayment::with([
            'admission.bed',
            'patient.user',
            'cashier',
        ])->where('financial_status', 'paid');

        if ($fromDate) {
            $query->whereDate('paid_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('paid_at', '<=', $toDate);
        }
        if ($type) {
            $query->where('payment_type', $type);
        }
        if ($method) {
            $query->where('payment_method', $method);
        }

        $totalsQuery = clone $query;
        $totalInflows = (float) (clone $totalsQuery)->where('payment_type', '!=', CcuPayment::TYPE_REFUND)->sum('amount');
        $totalRefunds = (float) (clone $totalsQuery)->where('payment_type', CcuPayment::TYPE_REFUND)->sum('amount');
        $netAmount = $totalInflows - $totalRefunds;

        $movements = $query->orderByDesc('paid_at')->orderByDesc('id')->paginate(25)->appends($request->query());

        return view('cashier.ccu-movements', compact(
            'movements',
            'totalInflows',
            'totalRefunds',
            'netAmount',
            'fromDate',
            'toDate',
            'type',
            'method'
        ));
    }

    /**
     * كشوفات حسابات ومطالبات العناية المركزة كبار (CCU Statements)
     */
    public function statements(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view ccu statements') && ! $user->can('view cashier')))) {
            abort(403, 'غير مصرح لك بالوصول إلى كشوفات حسابات العناية المركزة');
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $insuranceFilter = $request->input('insurance_type');

        $query = IcuAdmission::with([
            'patient.user',
            'patient.healthInsuranceCategory',
            'bed',
            'ccuService',
            'doctor.user',
            'ccuServiceLogs.ccuService',
            'medicalOrders.labTest',
            'medicalOrders.radiologyType',
            'ccuConsultations.doctor.user',
            'ccuPayments',
        ]);

        if ($fromDate) {
            $query->whereDate('admission_date', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('admission_date', '<=', $toDate);
        }
        if ($insuranceFilter === 'insured') {
            $query->where('is_insured', true);
        } elseif ($insuranceFilter === 'cash') {
            $query->where('is_insured', false);
        }

        $summaryIds = (clone $query)->pluck('id');
        $admissionsList = IcuAdmission::with([
            'bed', 'ccuService', 'ccuServiceLogs.ccuService',
            'medicalOrders.labTest', 'medicalOrders.radiologyType',
            'ccuConsultations', 'ccuPayments',
        ])->whereIn('id', $summaryIds)->get();

        $totalServicesFee = 0.0;
        $totalExtraOrdersAndCons = 0.0;
        $totalBundledReferenceValue = 0.0;
        $totalInsuranceClaims = 0.0;
        $totalHiaCollected = 0.0;
        $totalHiaRemaining = 0.0;
        $totalPatientShares = 0.0;

        foreach ($admissionsList as $adm) {
            $details = $this->billingService->calculateStayDetails($adm);
            $totalServicesFee += (float) $details['total_service_cost'];
            $totalExtraOrdersAndCons += (float) $details['total_lab_cost'] + (float) $details['total_radiology_cost'] + (float) $details['total_consultations_cost'];
            $totalBundledReferenceValue += (float) $details['bundled_reference_total'];
            $totalInsuranceClaims += (float) $details['insurance_grand_total'];
            $totalHiaCollected += (float) $details['hia_collected_amount'];
            $totalHiaRemaining += (float) $details['hia_remaining_balance'];
            $totalPatientShares += (float) $details['patient_grand_total'];
        }

        $paymentsQuery = CcuPayment::whereIn('icu_admission_id', $summaryIds)->where('financial_status', 'paid');
        $totalReceived = (float) (clone $paymentsQuery)->where('payment_type', '!=', CcuPayment::TYPE_REFUND)->sum('amount');
        $totalRefunds = (float) (clone $paymentsQuery)->where('payment_type', CcuPayment::TYPE_REFUND)->sum('amount');
        $totalCollected = $totalReceived - $totalRefunds;

        $admissions = $query->orderByDesc('admission_date')->orderByDesc('id')->paginate(20)->appends($request->query());
        $billingService = $this->billingService;

        return view('cashier.ccu-statements', compact(
            'admissions',
            'billingService',
            'totalServicesFee',
            'totalExtraOrdersAndCons',
            'totalBundledReferenceValue',
            'totalInsuranceClaims',
            'totalHiaCollected',
            'totalHiaRemaining',
            'totalPatientShares',
            'totalCollected',
            'fromDate',
            'toDate',
            'insuranceFilter'
        ));
    }

    /**
     * تسجيل تحصيل فعلي من هيئة الضمان الصحي مقابل مطالبة إضبارة عناية مركزة
     */
    public function updateHiaCollection(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view insurance claims') && ! $user->can('manage health insurance') && ! $user->can('view ccu analytics')))) {
            abort(403, 'غير مصرح لك بتسوية تحصيل مطالبة الضمان الصحي');
        }

        $validated = $request->validate([
            'hia_collected_amount' => 'required|numeric|min:0',
        ]);

        $admission->update([
            'hia_collected_amount' => (float) $validated['hia_collected_amount'],
        ]);

        return redirect()->back()->with('success', 'تم تحديث المبلغ المستلم من هيئة الضمان الصحي واحتساب الباقي بدقة.');
    }
}
