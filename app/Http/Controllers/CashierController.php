<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Patient;
use App\Models\Request as MedicalRequest;
use App\Models\Visit;
use App\Models\LabTest;
use App\Models\RadiologyType;
use App\Models\Surgery;
use App\Models\Emergency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CashierController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:view cashier']);
    }

    /**
     * عرض قائمة المواعيد المعلقة التي تحتاج للدفع
     */
    public function index()
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (!$user->can('view cashier appointments') && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // جلب المواعيد المعلقة (غير المدفوعة)
        $pendingAppointments = Appointment::with(['patient.user', 'doctor.user', 'department'])
            ->where('payment_status', 'pending')
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereHas('patient') // التأكد من وجود مريض مرتبط
            ->orderBy('appointment_date')
            ->paginate(15);

        // جلب الطلبات المعلقة (تحاليل، أشعة، صيدلية) - استبعاد الطلبات المرتبطة بالعمليات
        $pendingRequests = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->where('payment_status', 'pending')
            ->whereHas('visit', function($q) {
                $q->where('status', '!=', 'cancelled')
                  ->where(function($query) {
                      $query->where('visit_type', '!=', 'surgery')
                            ->orWhereNull('visit_type');
                  });
            })
            ->whereHas('visit.patient') // التأكد من وجود مريض مرتبط بالزيارة
            ->whereDoesntHave('visit.surgery')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'requests_page');

        // جلب خدمات الطوارئ المعلقة الدفع
        $pendingEmergencyPayments = Payment::with(['emergency.patient.user', 'emergency.emergencyPatient', 'emergency.services', 'appointment.doctor.user'])
            ->where('payment_type', 'emergency')
            ->whereNull('appointment_id') // الاستشاري يُحسب كموعد مستقل
            ->whereNull('paid_at') // لم يتم الدفع بعد
            ->whereHas('emergency') // التأكد من وجود حالة طوارئ مرتبطة
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'emergency_page');

        // إحصائيات اليوم المحسنة
        $today = Carbon::today();
        $todayPayments = Payment::whereDate('paid_at', $today)
            ->with(['appointment.doctor', 'appointment.patient.user', 'request.visit.patient.user', 'request.visit.doctor.user', 'patient.user'])
            ->get();

        // حساب عدد المعلقات بشكل صحيح
        $pendingAppointmentsCount = is_object($pendingAppointments) ? $pendingAppointments->total() : 0;
        $pendingRequestsCount = is_object($pendingRequests) ? $pendingRequests->total() : 0;
        $pendingEmergencyCount = is_object($pendingEmergencyPayments) ? $pendingEmergencyPayments->total() : 0;

        $todayStats = [
            'total_collected' => $todayPayments->sum('amount'),
            'total_payments' => $todayPayments->count(),
            'pending_appointments_count' => $pendingAppointmentsCount,
            'pending_requests_count' => $pendingRequestsCount,
            'pending_emergency_count' => $pendingEmergencyCount,
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

        return view('cashier.index', compact(
            'pendingAppointments', 
            'pendingMedicalRequests', 
            'pendingEmergencyPayments', 
            'todayStats', 
            'todayPayments', 
            'monthlyStats'
        ));
    }

    /**
     * عرض صفحة الدفع لموعد معين
     */
    public function showPaymentForm(Appointment $appointment)
    {
        $user = Auth::user();

        if (!$user->can('process consultation payments') && !$user->hasRole('admin')) {
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

        if (!$user->can('process consultation payments') && !$user->hasRole('admin')) {
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

        if (!$user->can('view cashier') && !$user->hasRole(['admin', 'patient'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $payment->load(['patient.user', 'appointment.doctor.user', 'appointment.department', 'request.visit.patient.user', 'request.visit.doctor.user', 'cashier']);

        return view('cashier.receipt', compact('payment'));
    }

    /**
     * طباعة إيصال الدفع (PDF)
     */
    public function printReceipt(Request $request, Payment $payment)
    {
        $payment->load(['patient.user', 'appointment.doctor.user', 'appointment.department', 'request.visit.patient.user', 'request.visit.doctor.user', 'cashier']);

        // إذا تم طلب النسخة HTML (مثل "طباعة" أساسي)
        if ($request->query('html')) {
            return view('cashier.receipt-print', compact('payment'));
        }

        // التحقق من وجود حزمة dompdf
        if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = Pdf::loadView('cashier.receipt-pdf', compact('payment'));
            // افتح المستند في المتصفح بدل تنزيله
            return $pdf->stream('receipt-' . $payment->receipt_number . '.pdf');
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

        if (!$user->can('process medical requests payments') && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // التحقق من أن الطلب لم يتم دفعه بعد
        if ($request->payment_status === 'paid') {
            return redirect()->route('cashier.index')
                ->with('warning', 'هذا الطلب تم دفعه مسبقاً');
        }

        $request->load(['visit.patient.user', 'visit.doctor.user']);

        if (!$request->visit) {
            // shouldn't normally happen but guard anyway
            return redirect()->route('cashier.index')
                ->with('error', 'الطلب غير مرتبط بأي زيارة ولا يمكن عرضه.');
        }

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

        if (!$user->can('process medical requests payments') && !$user->hasRole('admin')) {
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
            // Ensure visit is loaded and exists; otherwise abort early to avoid null errors.
        $request->load('visit');
        if (!$request->visit) {
            \Log::error('MedicalRequest #' . $request->id . ' has no associated visit');
            DB::rollBack();
            return redirect()->route('cashier.index')
                        ->with('error', 'الطلب غير مرتبط بأي زيارة. لا يمكن متابعة الدفع.');
        }
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
            if ($request->visit) {
                $request->visit->update([
                    'status' => 'in_progress'
                ]);
                \Log::info('Visit status updated to in_progress');
            } else {
                \Log::warning('Cannot update visit status: visit is null for request #' . $request->id);
            }

            // إذا كان الطلب أشعة، إنشاء سجل في radiology_requests إن لم يكن موجوداً
            if ($request->type === 'radiology') {
                \Log::info('Processing radiology request payment, request_id: ' . $request->id);

                $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                \Log::info('Request details: ' . json_encode($details));

                if (isset($details['radiology_type_ids']) && !empty($details['radiology_type_ids'])) {
                    if (!$request->visit) {
                        \Log::warning('Skipping radiology request creation, visit missing for request #' . $request->id);
                    } else {
                        \Log::info('Found radiology_type_ids: ' . json_encode($details['radiology_type_ids']));

                        foreach ($details['radiology_type_ids'] as $radiologyTypeId) {
                            $exists = \App\Models\RadiologyRequest::where('visit_id', $request->visit_id)
                                ->where('radiology_type_id', $radiologyTypeId)
                                ->exists();

                            if (!$exists) {
                                try {
                                    $radiologyType = \App\Models\RadiologyType::find($radiologyTypeId);

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
                    }
                } else {
                    \Log::warning('No radiology_type_ids found in request details for request #' . $request->id);
                }
            }

            // إذا كان الطلب طوارئ، إنشاء سجل طوارئ
            if ($request->type === 'emergency') {
                \Log::info('Processing emergency request payment, request_id: ' . $request->id);
                
                $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                \Log::info('Emergency request details: ' . json_encode($details));
                
                if (isset($details['emergency_priority']) && isset($details['emergency_type'])) {
                    // double-check visit exists
                    if (!$request->visit) {
                        \Log::warning('Skipping emergency record creation, visit missing for request #' . $request->id);
                    } else {
                        try {
                        // إنشاء سجل طوارئ
                        $emergency = \App\Models\Emergency::create([
                            'patient_id' => $request->visit->patient_id,
                            'doctor_id' => $request->visit->doctor_id,
                            'priority' => $details['emergency_priority'],
                            'emergency_type' => $details['emergency_type'],
                            'symptoms' => $details['symptoms_description'] ?? $request->description,
                            'vital_signs' => $details['vital_signs'] ?? [],
                            'admission_time' => now(),
                            'status' => 'waiting',
                            'is_active' => true,
                        ]);
                        
                        \Log::info('Created emergency record #' . $emergency->id . ' for request #' . $request->id);
                    } catch (\Exception $e) {
                        \Log::error('Failed to create emergency record: ' . $e->getMessage());
                    }
                } // end visit-exists else branch
            } // end isset-emergency_priority check
            else {
                \Log::warning('Emergency request missing required details for request #' . $request->id);
            }
            }

            DB::commit();

            \Log::info('Transaction committed. Redirecting to cashier index.');

            // العودة إلى صفحة الكاشير الرئيسية مع رسالة نجاح
            return redirect()->route('cashier.index')
                ->with('success', 'تم تسجيل الدفع بنجاح! رقم الإيصال: ' . $payment->receipt_number)
                ->with('payment_id', $payment->id);

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

        // التحقق من صلاحية عرض التقارير
        if (!$user->can('view cashier reports') && !$user->hasRole('admin')) {
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

        // استعلام من أجل صفحة السجل العامة
        return view('cashier.payments', compact('payments', 'totalAmount'));
    }

    /**
     * عرض واجهة الكاشير للعمليات الجراحية
     */
    public function surgeriesIndex()
    {
        $user = Auth::user();

        if (!$user->can('view cashier surgeries') && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // جلب العمليات المعلقة (بانتظار الدفع أو دفع جزئي) مجمعة حسب المريض
        $pendingSurgeries = Surgery::with([
            'patient.user', 
            'doctor.user', 
            'department',
            'surgicalOperation',
            'room',
            'labTests.labTest',
            'radiologyTests.radiologyType',
            'visit'
        ])
        ->whereIn('status', ['scheduled', 'waiting'])
        ->whereIn('payment_status', ['pending', 'partial'])
        ->orderBy('scheduled_date')
        ->get();

        // تجميع العمليات حسب المريض
        $surgeriesByPatient = $pendingSurgeries->groupBy('patient_id');

        // إحصائيات العمليات
        $today = Carbon::today();
        $surgeryStats = [
            'pending_count' => Surgery::whereIn('payment_status', ['pending', 'partial'])->count(),
            'patients_count' => $surgeriesByPatient->count(),
            'today_paid' => Payment::whereDate('paid_at', $today)
                ->where('payment_type', 'surgery')
                ->count(),
            'today_revenue' => (float) Payment::whereDate('paid_at', $today)
                ->where('payment_type', 'surgery')
                ->sum('amount'),
        ];

        return view('cashier.surgeries.index', compact('pendingSurgeries', 'surgeriesByPatient', 'surgeryStats'));
    }

    /**
     * عرض العمليات المدفوعة (كلياً أو جزئياً)
     */
    public function surgeriesPaid(Request $request)
    {
        $user = Auth::user();

        if (!$user->can('view cashier surgeries') && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // بناء الاستعلام
        $query = Payment::with(['patient.user', 'cashier'])
            ->where('payment_type', 'surgery')
            ->orderBy('paid_at', 'desc');

        // فلترة بالتاريخ
        if ($request->filled('from_date')) {
            $query->whereDate('paid_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('paid_at', '<=', $request->to_date);
        }

        // فلترة باسم المريض
        if ($request->filled('patient_name')) {
            $query->whereHas('patient.user', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->patient_name . '%');
            });
        }

        // فلترة بطريقة الدفع
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $payments = $query->paginate(20);

        // إحصائيات
        $today = Carbon::today();
        $stats = [
            'today_count' => Payment::where('payment_type', 'surgery')
                ->whereDate('paid_at', $today)
                ->count(),
            'today_amount' => (float) Payment::where('payment_type', 'surgery')
                ->whereDate('paid_at', $today)
                ->sum('amount'),
            'month_count' => Payment::where('payment_type', 'surgery')
                ->whereMonth('paid_at', $today->month)
                ->whereYear('paid_at', $today->year)
                ->count(),
            'month_amount' => (float) Payment::where('payment_type', 'surgery')
                ->whereMonth('paid_at', $today->month)
                ->whereYear('paid_at', $today->year)
                ->sum('amount'),
        ];

        return view('cashier.surgeries.paid', compact('payments', 'stats'));
    }

    /**
     * عرض نموذج دفع العملية الجراحية
     */
    public function showSurgeryPaymentForm(Surgery $surgery)
    {
        $user = Auth::user();

        if (!$user->can('process surgery payments') && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // التحقق من أن العملية لم يتم دفعها
        if ($surgery->payment_status === 'paid') {
            return redirect()->route('cashier.surgeries.index')
                ->with('warning', 'هذه العملية تم دفعها مسبقاً');
        }

        $surgery->load([
            'patient.user', 
            'doctor.user', 
            'department',
            'surgicalOperation',
            'room',
            'labTests.labTest',
            'radiologyTests.radiologyType',
            'visit'
        ]);

        // حساب التكلفة الإجمالية
        // use the stored fee on the surgery itself (preserved at creation/edit time)
        $surgeryFee = $surgery->surgery_fee ?? 0;
        $labTestsFee = $surgery->labTests->sum(function($test) {
            return $test->labTest->price ?? 0;
        });
        $radiologyTestsFee = $surgery->radiologyTests->sum(function($test) {
            return $test->radiologyType->base_price ?? 0;
        });
        
        $totalAmount = $surgeryFee + $labTestsFee + $radiologyTestsFee;

        return view('cashier.surgeries.payment-form', compact('surgery', 'surgeryFee', 'labTestsFee', 'radiologyTestsFee', 'totalAmount'));
    }

    /**
     * معالجة دفع العملية الجراحية
     */
    public function processSurgeryPayment(Request $request, Surgery $surgery)
    {
        $user = Auth::user();

        if (!$user->can('process surgery payments') && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $request->validate([
            'payment_method' => 'required|in:cash,card,insurance',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'inclusive' => 'nullable|boolean',
            'pay_surgery' => 'nullable',
            'pay_room' => 'nullable',
            'pay_lab_tests' => 'nullable|array',
            'pay_radiology_tests' => 'nullable|array',
        ]);
        // التحقق من أن العملية لم يتم دفعها بالكامل
        if ($surgery->payment_status === 'paid') {
            return redirect()->route('cashier.surgeries.index')
                ->with('error', 'هذه العملية تم دفعها مسبقاً بالكامل');
        }

        // determine if user marked cost as inclusive
        $isInclusive = $request->has('inclusive');

        // التحقق من تحديد عنصر واحد على الأقل للدفع
        $paySurgery = $request->has('pay_surgery');
        $payRoom = $request->has('pay_room');
        $payLabTests = $request->input('pay_lab_tests', []);
        $payRadiologyTests = $request->input('pay_radiology_tests', []);

        if (!$isInclusive && !$paySurgery && !$payRoom && empty($payLabTests) && empty($payRadiologyTests)) {
            return redirect()->back()
                ->with('error', 'يرجى تحديد عنصر واحد على الأقل للدفع')
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // حساب المبلغ الفعلي للعناصر المحددة
            $actualAmount = 0;
            $paidItems = [];

            if ($isInclusive) {
                // if inclusive, treat everything as paid but amount = surgery fee only
                $actualAmount = $surgery->surgery_fee ?? 0;
                $paidItems[] = 'رسوم العملية الجراحية (شاملة)';
                // mark all other pieces as intended to pay
                $paySurgery = true;
                $payRoom = true;
                $payLabTests = $surgery->labTests->pluck('id')->toArray();
                $payRadiologyTests = $surgery->radiologyTests->pluck('id')->toArray();
            }

            // رسوم العملية (فقط إذا لم تُدفع سابقاً)
            if ($paySurgery && $surgery->surgery_fee_paid !== 'paid') {
                if (!$isInclusive) {
                    $actualAmount += $surgery->surgery_fee ?? 0;
                    $paidItems[] = 'رسوم العملية الجراحية';
                }
            }

            // رسوم الغرفة (فقط إذا لم تُدفع سابقاً)
            if ($payRoom && $surgery->payment_status !== 'paid' && $surgery->room_fee > 0) {
                if (!$isInclusive) {
                    $actualAmount += $surgery->room_fee ?? 0;
                    $paidItems[] = 'أجور الغرفة: ' . ($surgery->room->room_number ?? 'غير محدد');
                }
            }

            // التحاليل المختارة (فقط غير المدفوعة)
            if (!empty($payLabTests)) {
                foreach ($surgery->labTests as $labTest) {
                    if (in_array($labTest->id, $payLabTests) && $labTest->payment_status !== 'paid') {
                        if (!$isInclusive) {
                            $actualAmount += $labTest->labTest->price ?? 0;
                            $paidItems[] = 'تحليل: ' . ($labTest->labTest->name ?? 'غير محدد');
                        }
                    }
                }
            }

            // الأشعة المختارة (فقط غير المدفوعة)
            if (!empty($payRadiologyTests)) {
                foreach ($surgery->radiologyTests as $radiologyTest) {
                    if (in_array($radiologyTest->id, $payRadiologyTests) && $radiologyTest->payment_status !== 'paid') {
                        if (!$isInclusive) {
                            $actualAmount += $radiologyTest->radiologyType->base_price ?? 0;
                            $paidItems[] = 'أشعة: ' . ($radiologyTest->radiologyType->name ?? 'غير محدد');
                        }
                    }
                }
            }

            // التحقق من وجود مبلغ للدفع
            if ($actualAmount <= 0) {
                return redirect()->back()
                    ->with('error', 'لا توجد عناصر معلقة لدفعها')
                    ->withInput();
            }

            // إنشاء وصف الدفع
            $description = 'دفع رسوم العملية الجراحية: ' . $surgery->surgery_type . ' (ID: #' . $surgery->id . ')';
            if ($isInclusive) {
                $description .= "\nملاحظة: الشاملة (التكاليف المسبقة مٌضمَّنة)";
            }
            $description .= "\nالعناصر المدفوعة:\n- " . implode("\n- ", $paidItems);

            // إنشاء سجل الدفع
            $payment = Payment::create([
                'patient_id' => $surgery->patient_id,
                'cashier_id' => $user->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => $actualAmount,
                'payment_method' => $request->payment_method,
                'payment_type' => 'surgery',
                'description' => $description,
                'notes' => $request->notes,
                'is_inclusive' => $isInclusive,
                'paid_at' => Carbon::now()
            ]);

            // تحديث حالة دفع رسوم العملية
            if ($paySurgery && $surgery->surgery_fee_paid !== 'paid') {
                $surgery->update(['surgery_fee_paid' => 'paid']);
            }

            // تحديث حالة التحاليل المدفوعة
            if (!empty($payLabTests)) {
                foreach ($surgery->labTests as $labTest) {
                    if (in_array($labTest->id, $payLabTests) && $labTest->payment_status !== 'paid') {
                        $labTest->update([
                            'payment_status' => 'paid',
                            'payment_id' => $payment->id
                        ]);
                    }
                }
            }

            // تحديث حالة الأشعة المدفوعة
            if (!empty($payRadiologyTests)) {
                foreach ($surgery->radiologyTests as $radiologyTest) {
                    if (in_array($radiologyTest->id, $payRadiologyTests) && $radiologyTest->payment_status !== 'paid') {
                        $radiologyTest->update([
                            'payment_status' => 'paid',
                            'payment_id' => $payment->id
                        ]);
                    }
                }
            }

            // تحديد إذا تم دفع كل شيء أم لا
            $surgery->refresh();
            $allSurgeryFeePaid = $surgery->surgery_fee_paid === 'paid' || !$surgery->surgery_fee;
            $allLabTestsPaid = $surgery->labTests->where('payment_status', '!=', 'paid')->count() === 0;
            $allRadiologyTestsPaid = $surgery->radiologyTests->where('payment_status', '!=', 'paid')->count() === 0;
            $allPaid = $allSurgeryFeePaid && $allLabTestsPaid && $allRadiologyTestsPaid;

            // تحديث حالة دفع العملية
            $surgery->update([
                'payment_status' => $allPaid ? 'paid' : 'partial',
                'payment_id' => $payment->id
            ]);

            // تحديث حالة الطلبات المرتبطة إذا تم الدفع الكامل
            if ($allPaid && $surgery->visit_id) {
                MedicalRequest::where('visit_id', $surgery->visit_id)
                    ->where('payment_status', 'pending')
                    ->update(['payment_status' => 'paid']);
                
                // تحديث حالة الزيارة
                $surgery->visit->update(['status' => 'in_progress']);
            }

            DB::commit();

            $successMessage = $allPaid 
                ? 'تم تسجيل الدفع الكامل بنجاح!' 
                : 'تم تسجيل الدفع الجزئي بنجاح! المتبقي سيُدفع لاحقاً.';
            $successMessage .= ' رقم الإيصال: ' . $payment->receipt_number;

            return redirect()->route('cashier.surgeries.index')
                ->with('success', $successMessage)
                ->with('payment_id', $payment->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء معالجة الدفع: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض نموذج دفع خدمات الطوارئ
     */
    public function showEmergencyPaymentForm(Payment $payment)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'cashier', 'receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // التحقق من أن الدفعة تخص خدمات طوارئ ولم يتم دفعها بعد
        if ($payment->payment_type !== 'emergency' || $payment->paid_at !== null || $payment->appointment_id !== null) {
            return redirect()->route('cashier.index')
                ->with('warning', 'هذه الدفعة غير متاحة للدفع');
        }

        $payment->load(['emergency.patient.user', 'emergency.services', 'appointment.doctor.user']);

        return view('cashier.emergency-payment-form', compact('payment'));
    }

    /**
     * معالجة دفع خدمات الطوارئ
     */
    public function processEmergencyPayment(Request $request, Payment $payment)
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

        // التحقق من أن الدفعة تخص خدمات طوارئ ولم يتم دفعها بعد
        if ($payment->payment_type !== 'emergency' || $payment->paid_at !== null || $payment->appointment_id !== null) {
            return redirect()->route('cashier.index')
                ->with('warning', 'هذه الدفعة غير متاحة للدفع');
        }

        DB::beginTransaction();

        try {
            // تحديث الدفعة
            $payment->update([
                'payment_method' => $request->payment_method,
                'amount' => $request->amount,
                'paid_at' => now(),
                'cashier_id' => $user->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'notes' => $request->notes,
            ]);

            // تحديث حالة الدفع في حالة الطوارئ
            $payment->emergency->update([
                'payment_status' => 'paid',
            ]);

            DB::commit();

            return redirect()->route('cashier.index')
                ->with('success', 'تم تسديد خدمات الطوارئ بنجاح! رقم الإيصال: ' . $payment->receipt_number)
                ->with('payment_id', $payment->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء معالجة الدفع: ' . $e->getMessage())
                ->withInput();
        }
    }
}
