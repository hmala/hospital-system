<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CashierController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin|cashier|receptionist']);
    }

    /**
     * عرض قائمة المواعيد المعلقة التي تحتاج للدفع
     */
    public function index()
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (!$user->hasRole(['admin', 'cashier', 'receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // جلب المواعيد المعلقة (غير المدفوعة)
        $pendingAppointments = Appointment::with(['patient.user', 'doctor.user', 'department'])
            ->where('payment_status', 'pending')
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('appointment_date')
            ->paginate(15);

        // إحصائيات اليوم المحسنة
        $today = Carbon::today();
        $todayPayments = Payment::whereDate('paid_at', $today)->with('appointment.doctor')->get();

        $todayStats = [
            'total_collected' => $todayPayments->sum('amount'),
            'total_payments' => $todayPayments->count(),
            'pending_count' => $pendingAppointments->total(),
            'doctor_fees' => 0,
            'hospital_profit' => 0,
        ];

        // حساب أجور الأطباء وربح المستشفى
        foreach ($todayPayments as $payment) {
            $appointment = $payment->appointment;
            if ($appointment && $appointment->doctor) {
                $doctorFee = $appointment->doctor->fee_by_specialization ?? 0;
                $todayStats['doctor_fees'] += $doctorFee;
                $todayStats['hospital_profit'] += $payment->amount - $doctorFee;
            }
        }

        // إحصائيات شهرية للمخططات
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $monthlyPayments = Payment::whereBetween('paid_at', [$monthStart, $monthEnd])->get();

        $monthlyStats = [
            'total_revenue' => $monthlyPayments->sum('amount'),
            'total_payments' => $monthlyPayments->count(),
            'avg_daily' => $monthlyPayments->count() > 0 ? $monthlyPayments->sum('amount') / Carbon::now()->daysInMonth : 0,
        ];

        return view('cashier.index', compact('pendingAppointments', 'todayStats', 'todayPayments', 'monthlyStats'));
    }

    /**
     * عرض صفحة الدفع لموعد معين
     */
    public function showPaymentForm(Appointment $appointment)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'cashier', 'receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // التحقق من أن الموعد لم يتم دفعه بعد
        if ($appointment->payment_status === 'paid') {
            return redirect()->route('cashier.index')
                ->with('warning', 'هذا الموعد تم دفعه مسبقاً');
        }

        $appointment->load(['patient.user', 'doctor.user', 'department']);

        return view('cashier.payment-form', compact('appointment'));
    }

    /**
     * معالجة الدفع
     */
    public function processPayment(Request $request, Appointment $appointment)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'cashier', 'receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $request->validate([
            'payment_method' => 'required|in:cash,card,insurance',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        // التحقق من أن الموعد لم يتم دفعه بعد
        if ($appointment->payment_status === 'paid') {
            return redirect()->route('cashier.index')
                ->with('error', 'هذا الموعد تم دفعه مسبقاً');
        }

        DB::beginTransaction();
        try {
            // إنشاء سجل الدفع
            $payment = Payment::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'cashier_id' => $user->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'payment_type' => 'appointment',
                'description' => 'دفع رسوم موعد #' . $appointment->id,
                'notes' => $request->notes,
                'paid_at' => Carbon::now()
            ]);

            // تحديث حالة الدفع للموعد
            $appointment->update([
                'payment_status' => 'paid',
                'payment_id' => $payment->id
            ]);

            DB::commit();

            return redirect()->route('cashier.receipt', $payment->id)
                ->with('success', 'تم تسجيل الدفع بنجاح! رقم الإيصال: ' . $payment->receipt_number);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء معالجة الدفع: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض إيصال الدفع
     */
    public function showReceipt(Payment $payment)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'cashier', 'receptionist', 'patient'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $payment->load(['patient.user', 'appointment.doctor.user', 'appointment.department', 'cashier']);

        return view('cashier.receipt', compact('payment'));
    }

    /**
     * طباعة إيصال الدفع (PDF)
     */
    public function printReceipt(Payment $payment)
    {
        $payment->load(['patient.user', 'appointment.doctor.user', 'appointment.department', 'cashier']);

        // التحقق من وجود حزمة dompdf
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = Pdf::loadView('cashier.receipt-pdf', compact('payment'));
            return $pdf->download('receipt-' . $payment->receipt_number . '.pdf');
        }

        // بديل: عرض صفحة للطباعة عبر المتصفح
        return view('cashier.receipt-print', compact('payment'));
    }

    /**
     * عرض تقرير المدفوعات
     */
    public function paymentsReport(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $query = Payment::with(['patient.user', 'cashier', 'appointment']);

        // فلترة حسب التاريخ
        if ($request->filled('from_date')) {
            $query->whereDate('paid_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('paid_at', '<=', $request->to_date);
        }

        // فلترة حسب طريقة الدفع
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // فلترة حسب الكاشير
        if ($request->filled('cashier_id')) {
            $query->where('cashier_id', $request->cashier_id);
        }

        $payments = $query->orderBy('paid_at', 'desc')->paginate(20);

        $totalAmount = $query->sum('amount');

        return view('cashier.report', compact('payments', 'totalAmount'));
    }
}
