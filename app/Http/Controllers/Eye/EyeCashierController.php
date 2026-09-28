<?php

namespace App\Http\Controllers\Eye;

use App\Http\Controllers\Controller;
use App\Models\Eye\EyeAppointment;
use App\Models\Eye\EyeInvoice;
use App\Models\Eye\EyeInvoiceItem;
use App\Models\Eye\EyeStoreItem;
use App\Models\FinancialTransaction;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EyeCashierController extends Controller
{
    /**
     * شاشة كاشير مركز العيون وفواتير اليوم
     */
    public function index(Request $request)
    {
        $today = Carbon::today();

        $query = EyeInvoice::with(['patient', 'appointment.doctor.user', 'items', 'cashier'])
            ->whereDate('created_at', $today);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // إحصائيات الصندوق اليومي لمركز العيون
        $stats = [
            'total_invoices' => EyeInvoice::whereDate('created_at', $today)->count(),
            'pending_count'  => EyeInvoice::whereDate('created_at', $today)->where('status', 'pending')->count(),
            'paid_count'     => EyeInvoice::whereDate('created_at', $today)->where('status', 'paid')->count(),
            'total_collected'=> (float) EyeInvoice::whereDate('created_at', $today)->where('status', 'paid')->sum('paid_amount'),
            'insurance_due'  => (float) EyeInvoice::whereDate('created_at', $today)->where('status', 'paid')->sum('insurance_share'),
            'reconciled'     => EyeInvoice::whereDate('created_at', $today)->where('status', 'paid')->where('reconciled_with_hospital', true)->count(),
        ];

        return view('eye.cashier.eye_cashier_index', compact('invoices', 'stats'));
    }

    /**
     * استعراض ودفع فاتورة كاشير العيون
     */
    public function show(EyeInvoice $invoice)
    {
        $invoice->load(['patient', 'appointment.doctor.user', 'items', 'cashier']);
        $eyeStoreItems = EyeStoreItem::where('is_active', true)->where('current_stock', '>', 0)->get();

        return view('eye.cashier.eye_cashier_show', compact('invoice', 'eyeStoreItems'));
    }

    /**
     * إضافة بند خدمة أو فحص أو مستلزم إضافي إلى الفاتورة
     */
    public function addItem(Request $request, EyeInvoice $invoice)
    {
        $request->validate([
            'service_type' => 'required|string',
            'description'  => 'required|string',
            'quantity'     => 'required|integer|min:1',
            'unit_price'   => 'required|numeric|min:0',
        ]);

        $subtotal = $request->quantity * $request->unit_price;

        $invoice->items()->create([
            'service_type' => $request->service_type,
            'description'  => $request->description,
            'quantity'     => $request->quantity,
            'unit_price'   => $request->unit_price,
            'subtotal'     => $subtotal,
        ]);

        // إعادة احتساب إجمالي الفاتورة
        $total = $invoice->items()->sum('subtotal');
        $patientShare = $total;
        $insuranceShare = 0;

        if ($invoice->insurance_type !== 'cash') {
            $patientShare = $total * 0.10;
            $insuranceShare = $total * 0.90;
        }

        $net = $patientShare - $invoice->discount;

        $invoice->update([
            'total_amount'    => $total,
            'patient_share'   => $patientShare,
            'insurance_share' => $insuranceShare,
            'net_amount'      => $net,
        ]);

        return back()->with('success', 'تمت إضافة البند إلى الفاتورة بنجاح وتحديث الإجمالي.');
    }

    /**
     * تأكيد الدفع وتحصيل المبلغ
     */
    public function processPayment(Request $request, EyeInvoice $invoice)
    {
        $request->validate([
            'paid_amount'    => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,insurance',
            'discount'       => 'nullable|numeric|min:0',
        ]);

        $discount = (float) ($request->discount ?? 0);
        $paid = (float) $request->paid_amount;
        $net = $invoice->patient_share - $discount;

        $status = ($paid >= $net) ? 'paid' : 'partially_paid';

        $invoice->update([
            'discount'       => $discount,
            'net_amount'     => $net,
            'paid_amount'    => $paid,
            'payment_method' => $request->payment_method,
            'status'         => $status,
            'cashier_id'     => Auth::id(),
        ]);

        return redirect()->route('eye.cashier.printReceipt', $invoice)
            ->with('success', "تم تحصيل الفاتورة بنجاح بمبلغ (" . number_format($paid) . " د.ع)");
    }

    /**
     * طباعة وصل كاشير العيون الرسمي
     */
    public function printReceipt(EyeInvoice $invoice)
    {
        $invoice->load(['patient', 'appointment.doctor.user', 'items', 'cashier']);
        return view('eye.cashier.eye_cashier_receipt_print', compact('invoice'));
    }

    /**
     * الترحيل اليومي والإغلاق المالي لمركز العيون مع خزينة المستشفى الرئيسية
     */
    public function dailyReconciliation(Request $request)
    {
        $today = Carbon::today();

        $unreconciledInvoices = EyeInvoice::whereDate('created_at', $today)
            ->where('status', 'paid')
            ->where('reconciled_with_hospital', false)
            ->get();

        if ($unreconciledInvoices->isEmpty()) {
            return back()->with('info', 'لا توجد فواتير معلقة بانتظار الترحيل لليوم.');
        }

        $totalCash = $unreconciledInvoices->where('payment_method', 'cash')->sum('paid_amount');
        $totalCard = $unreconciledInvoices->where('payment_method', 'card')->sum('paid_amount');
        $grandTotal = $totalCash + $totalCard;

        DB::beginTransaction();
        try {
            // تحديث الفواتير بأنها رُحلت
            EyeInvoice::whereIn('id', $unreconciledInvoices->pluck('id'))->update([
                'reconciled_with_hospital' => true,
                'reconciled_at'            => now(),
            ]);

            // تسجيل قيد مالي مركزي في جدول المعاملات المالية للمستشفى إن وُجد
            if (class_exists(FinancialTransaction::class)) {
                FinancialTransaction::create([
                    'transaction_type' => 'hospital_revenue',
                    'amount'           => $grandTotal,
                    'related_type'     => EyeInvoice::class,
                    'description'      => "إغلاق إيراد كاشير مركز العيون ليوم {$today->toDateString()} (عدد {$unreconciledInvoices->count()} وصل)",
                    'performed_by_id'  => Auth::id(),
                    'performed_at'     => now(),
                ]);
            }

            DB::commit();

            return back()->with('success', "تم إغلاق وترحيل صندوق مركز العيون بنجاح إلى حسابات المستشفى الرئيسية بمبلغ إجمالي (" . number_format($grandTotal) . " د.ع)");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء الترحيل: ' . $e->getMessage());
        }
    }
}
