<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Patient;
use App\Models\Request as MedicalRequest;
use App\Models\Visit;
use App\Models\LabTest;
use App\Models\RadiologyType;
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

        // جلب الطلبات المعلقة (تحاليل، أشعة، صيدلية)
        $pendingRequests = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->where('payment_status', 'pending')
            ->whereHas('visit', function($q) {
                $q->where('status', '!=', 'cancelled');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'requests_page');

        // إحصائيات اليوم المحسنة
        $today = Carbon::today();
        $todayPayments = Payment::whereDate('paid_at', $today)->with('appointment.doctor')->get();

        // حساب عدد المعلقات بشكل صحيح
        $pendingAppointmentsCount = is_object($pendingAppointments) ? $pendingAppointments->total() : 0;
        $pendingRequestsCount = is_object($pendingRequests) ? $pendingRequests->total() : 0;

        $todayStats = [
            'total_collected' => $todayPayments->sum('amount'),
            'total_payments' => $todayPayments->count(),
            'pending_appointments_count' => $pendingAppointmentsCount,
            'pending_requests_count' => $pendingRequestsCount,
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

        // Debug: تأكد من نوع المتغير قبل إرساله
        \Log::info('CashierController - pendingRequests type: ' . gettype($pendingRequests));
        \Log::info('CashierController - pendingRequests class: ' . (is_object($pendingRequests) ? get_class($pendingRequests) : 'not object'));
        \Log::info('CashierController - pendingRequests count: ' . (is_object($pendingRequests) ? $pendingRequests->count() : 'not object'));

        // استخدام اسم مختلف لتجنب التعارض
        $pendingMedicalRequests = $pendingRequests;

        return view('cashier.index', compact('pendingAppointments', 'pendingMedicalRequests', 'todayStats', 'todayPayments', 'monthlyStats'));
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

        $payment->load(['patient.user', 'appointment.doctor.user', 'appointment.department', 'request.visit.patient.user', 'request.visit.doctor.user', 'cashier']);

        return view('cashier.receipt', compact('payment'));
    }

    /**
     * طباعة إيصال الدفع (PDF)
     */
    public function printReceipt(Payment $payment)
    {
        $payment->load(['patient.user', 'appointment.doctor.user', 'appointment.department', 'request.visit.patient.user', 'request.visit.doctor.user', 'cashier']);

        // التحقق من وجود حزمة dompdf
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = Pdf::loadView('cashier.receipt-pdf', compact('payment'));
            return $pdf->download('receipt-' . $payment->receipt_number . '.pdf');
        }

        // بديل: عرض صفحة للطباعة عبر المتصفح
        return view('cashier.receipt-print', compact('payment'));
    }

    /**
     * عرض نموذج دفع لطلب طبي
     */
    public function showRequestPaymentForm(MedicalRequest $request)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'cashier', 'receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // التحقق من أن الطلب لم يتم دفعه بعد
        if ($request->payment_status === 'paid') {
            return redirect()->route('cashier.index')
                ->with('warning', 'هذا الطلب تم دفعه مسبقاً');
        }

        $request->load(['visit.patient.user', 'visit.doctor.user']);

        return view('cashier.request-payment-form', compact('request'));
    }

    /**
     * معالجة دفع الطلب الطبي
     */
    public function processRequestPayment(Request $httpRequest, MedicalRequest $request)
    {
        \Log::info('========== processRequestPayment CALLED ==========');
        \Log::info('Request ID: ' . $request->id);
        \Log::info('HTTP Request data: ' . json_encode($httpRequest->all()));
        
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'cashier', 'receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $httpRequest->validate([
            'payment_method' => 'required|in:cash,card,insurance',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        // التحقق من أن الطلب لم يتم دفعه بعد
        if ($request->payment_status === 'paid') {
            return redirect()->route('cashier.index')
                ->with('error', 'هذا الطلب تم دفعه مسبقاً');
        }

        DB::beginTransaction();
        try {
            \Log::info('Starting payment process for request #' . $request->id);
            \Log::info('Request data: patient_id=' . $request->visit->patient_id . ', cashier_id=' . $user->id);
            
            // إنشاء سجل الدفع
            \Log::info('Creating payment record...');
            $payment = Payment::create([
                'request_id' => $request->id,
                'patient_id' => $request->visit->patient_id,
                'cashier_id' => $user->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => $httpRequest->amount,
                'payment_method' => $httpRequest->payment_method,
                'payment_type' => 'lab', // استخدام 'lab' بدلاً من 'request' لأن الـ enum لا يحتوي على 'request'
                'description' => 'دفع رسوم طلب ' . $request->type . ' #' . $request->id,
                'notes' => $httpRequest->notes,
                'paid_at' => Carbon::now()
            ]);

            \Log::info('Payment created successfully: #' . $payment->id);

            // تحديث حالة الدفع للطلب
            $request->update([
                'payment_status' => 'paid',
                'payment_id' => $payment->id
            ]);

            \Log::info('Request updated to paid');

            // تحديث حالة الزيارة من pending_payment إلى in_progress لتظهر في المختبر
            $request->visit->update([
                'status' => 'in_progress'
            ]);

            \Log::info('Visit status updated to in_progress');

            // إذا كان الطلب أشعة، إنشاء سجل في radiology_requests إن لم يكن موجوداً
            if ($request->type === 'radiology') {
                \Log::info('Processing radiology request payment, request_id: ' . $request->id);
                
                $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                \Log::info('Request details: ' . json_encode($details));
                
                if (isset($details['radiology_type_ids']) && !empty($details['radiology_type_ids'])) {
                    \Log::info('Found radiology_type_ids: ' . json_encode($details['radiology_type_ids']));
                    
                    // إنشاء سجل لكل نوع أشعة
                    foreach ($details['radiology_type_ids'] as $radiologyTypeId) {
                        // التحقق من عدم وجود سجل لنفس النوع في نفس الزيارة
                        $exists = \App\Models\RadiologyRequest::where('visit_id', $request->visit_id)
                            ->where('radiology_type_id', $radiologyTypeId)
                            ->exists();
                        
                        if (!$exists) {
                            try {
                                // الحصول على معلومات نوع الأشعة لحساب التكلفة
                                $radiologyType = \App\Models\RadiologyType::find($radiologyTypeId);
                                
                                // تحديد الطبيب - إذا لم يكن موجوداً، نستخدم أي طبيب من القسم أو نتركه null
                                $doctorId = $request->visit->doctor_id;
                                if (!$doctorId && $request->visit->department_id) {
                                    $doctor = \App\Models\Doctor::where('department_id', $request->visit->department_id)->first();
                                    $doctorId = $doctor ? $doctor->id : null;
                                }
                                
                                $radiologyRequest = \App\Models\RadiologyRequest::create([
                                    'visit_id' => $request->visit_id,
                                    'patient_id' => $request->visit->patient_id,
                                    'doctor_id' => $doctorId,
                                    'radiology_type_id' => $radiologyTypeId,
                                    'requested_date' => $request->created_at ?? now(),
                                    'status' => 'pending',
                                    'priority' => $details['priority'] ?? 'normal',
                                    'clinical_indication' => $request->description ?? 'طلب من الاستعلامات',
                                    'total_cost' => $radiologyType ? $radiologyType->base_price : null,
                                ]);
                                
                                \Log::info('Created radiology_request #' . $radiologyRequest->id . ' for type_id: ' . $radiologyTypeId);
                            } catch (\Exception $e) {
                                \Log::error('Failed to create radiology_request: ' . $e->getMessage());
                            }
                        } else {
                            \Log::info('Radiology request already exists for visit_id: ' . $request->visit_id . ', type_id: ' . $radiologyTypeId);
                        }
                    }
                } else {
                    \Log::warning('No radiology_type_ids found in request details for request #' . $request->id);
                }
            }

            DB::commit();

            \Log::info('Transaction committed. Redirecting based on request type.');

            // إذا كان الطلب أشعة، توجيه إلى صفحة الأشعة
            if ($request->type === 'radiology') {
                return redirect()->route('radiology.index')
                    ->with('success', 'تم تسجيل الدفع بنجاح! رقم الإيصال: ' . $payment->receipt_number . '. يمكنك الآن معالجة طلب الأشعة.');
            }

            // إذا كان الطلب مختبر، توجيه إلى صفحة طلبات المختبر
            if ($request->type === 'lab') {
                return redirect()->route('staff.requests.index', ['type' => 'lab'])
                    ->with('success', 'تم تسجيل الدفع بنجاح! رقم الإيصال: ' . $payment->receipt_number . '. يمكنك الآن معالجة طلب المختبر.');
            }

            // للطلبات الأخرى، عرض الإيصال
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
