<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ConsultationRevenue;
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
use Maatwebsite\Excel\Facades\Excel;
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
            ->whereNull('emergency_id')
            ->whereHas('patient') // التأكد من وجود مريض مرتبط
            ->orderBy('appointment_date')
            ->paginate(15);

        // جلب الطلبات المعلقة (تحاليل، أشعة، صيدلية) - استبعاد الطلبات المرتبطة بالعمليات
        $pendingRequests = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled')
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

                $radiologyTypeIds = [];
                if (isset($details['radiology_type_ids']) && !empty($details['radiology_type_ids'])) {
                    $radiologyTypeIds = $details['radiology_type_ids'];
                } elseif (isset($details['radiology_types']) && !empty($details['radiology_types'])) {
                    $radiologyTypeIds = $details['radiology_types'];
                }

                if (!empty($radiologyTypeIds)) {
                    if (!$request->visit) {
                        \Log::warning('Skipping radiology request creation, visit missing for request #' . $request->id);
                    } else {
                        \Log::info('Found radiology type ids: ' . json_encode($radiologyTypeIds));

                        foreach ($radiologyTypeIds as $radiologyTypeId) {
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

            // إذا كان الطلب تمريض (nursing)، يتم إرساله لموظفي الطوارئ
            if ($request->type === 'nursing') {
                \Log::info('Processing nursing request payment, request_id: ' . $request->id);
                // يتم عرضه في StaffRequestController للموظفين في قسم الطوارئ
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
     * عرض واجهة كشوفات الحسابات
     */
    public function statements(Request $request)
    {
        $user = Auth::user();

        if (!$user->can('view cashier reports') && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $data = $this->buildStatementData($request);

        return view('cashier.statements', $data);
    }

    public function exportStatements(Request $request)
    {
        $user = Auth::user();

        if (!$user->can('view cashier reports') && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $data = $this->buildStatementData($request);

        return Excel::download(
            new \App\Exports\CashierStatementsExport(
                $data['revenues'],
                $data['groupedRevenues'],
                $data['monthlyDoctorSummary'],
                $data['monthNames']
            ),
            'cashier_statements_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    protected function buildStatementData(Request $request): array
    {
        $query = ConsultationRevenue::with(['doctor.user', 'appointment.patient.user', 'serviceType'])
            ->where('movement_type', 'payment')
            ->whereHas('doctor', function ($q) {
                $q->where('type', 'consultant');
            })
            ->whereHas('appointment', function ($q) use ($request) {
                $q->where('payment_status', 'paid')
                    ->when($request->filled('from_date'), function ($q) use ($request) {
                        $q->whereDate('appointment_date', '>=', $request->from_date);
                    })
                    ->when($request->filled('to_date'), function ($q) use ($request) {
                        $q->whereDate('appointment_date', '<=', $request->to_date);
                    });
            });

        $revenues = $query->orderBy('paid_at', 'desc')->get();

        $groupedRevenues = ConsultationRevenue::selectRaw(
                'consultation_revenues.doctor_id as doctor_id, MIN(appointments.appointment_date) as first_appointment_date, COUNT(*) as examination_count, SUM(total_amount) as total_amount, SUM(doctor_share) as doctor_share, SUM(hospital_share) as hospital_share'
            )
            ->join('appointments', 'consultation_revenues.appointment_id', '=', 'appointments.id')
            ->where('consultation_revenues.movement_type', 'payment')
            ->where('appointments.payment_status', 'paid')
            ->whereHas('doctor', function ($q) {
                $q->where('type', 'consultant');
            })
            ->when($request->filled('from_date'), function ($q) use ($request) {
                $q->whereDate('appointments.appointment_date', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                $q->whereDate('appointments.appointment_date', '<=', $request->to_date);
            })
            ->groupBy('consultation_revenues.doctor_id')
            ->with('doctor.user')
            ->orderByDesc('examination_count')
            ->get();

        $monthNames = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر',
        ];

        $monthlyDoctorRevenues = ConsultationRevenue::selectRaw(
                'consultation_revenues.doctor_id, MONTH(appointments.appointment_date) as month_number, COUNT(*) as examination_count'
            )
            ->join('appointments', 'consultation_revenues.appointment_id', '=', 'appointments.id')
            ->where('consultation_revenues.movement_type', 'payment')
            ->where('appointments.payment_status', 'paid')
            ->whereHas('doctor', function ($q) {
                $q->where('type', 'consultant');
            })
            ->when($request->filled('from_date'), function ($q) use ($request) {
                $q->whereDate('appointments.appointment_date', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                $q->whereDate('appointments.appointment_date', '<=', $request->to_date);
            })
            ->groupBy('consultation_revenues.doctor_id', 'month_number')
            ->with('doctor.user')
            ->orderBy('consultation_revenues.doctor_id')
            ->orderBy('month_number')
            ->get();

        $monthlyDoctorSummary = $monthlyDoctorRevenues->groupBy('doctor_id')->map(function ($items) use ($monthNames) {
            $months = array_fill(1, 12, 0);
            $doctor = optional($items->first())->doctor;

            foreach ($items as $item) {
                $months[(int) $item->month_number] = (int) $item->examination_count;
            }

            $monthTrends = array_fill(1, 12, 'flat');
            $previousCount = null;
            foreach ($months as $month => $count) {
                if ($previousCount === null) {
                    $monthTrends[$month] = 'flat';
                } elseif ($count > $previousCount) {
                    $monthTrends[$month] = 'up';
                } elseif ($count < $previousCount) {
                    $monthTrends[$month] = 'down';
                } else {
                    $monthTrends[$month] = 'flat';
                }
                $previousCount = $count;
            }

            $firstMonthCount = 0;
            foreach ($months as $count) {
                if ($count > 0) {
                    $firstMonthCount = $count;
                    break;
                }
            }

            $lastMonthCount = 0;
            for ($month = 12; $month >= 1; $month--) {
                if ($months[$month] > 0) {
                    $lastMonthCount = $months[$month];
                    break;
                }
            }

            $percent = 0;
            if ($firstMonthCount > 0) {
                $percent = round((($lastMonthCount - $firstMonthCount) / $firstMonthCount) * 100, 2);
            } elseif ($lastMonthCount > 0) {
                $percent = 100;
            }

            return (object) [
                'doctor' => $doctor,
                'months' => $months,
                'month_trends' => $monthTrends,
                'total' => array_sum($months),
                'first_month_count' => $firstMonthCount,
                'last_month_count' => $lastMonthCount,
                'percent_change' => $percent,
            ];
        })->values();

        $monthlyTotals = array_fill(1, 12, 0);
        foreach ($monthlyDoctorSummary as $row) {
            foreach ($row->months as $month => $count) {
                $monthlyTotals[$month] += $count;
            }
        }

        $overallMonthlyExaminations = $monthlyDoctorSummary->sum('total');

        $totals = [
            'count' => $revenues->count(),
            'total_amount' => $revenues->sum('total_amount'),
            'total_doctor_share' => $revenues->sum('doctor_share'),
            'total_hospital_share' => $revenues->sum('hospital_share'),
            'grouped_count' => $groupedRevenues->count(),
            'grouped_total_amount' => $groupedRevenues->sum('total_amount'),
            'grouped_total_doctor_share' => $groupedRevenues->sum('doctor_share'),
            'grouped_total_hospital_share' => $groupedRevenues->sum('hospital_share'),
            'monthly_grouped_count' => $monthlyDoctorSummary->count(),
            'monthly_examination_count' => $overallMonthlyExaminations,
        ];

        return compact('revenues', 'groupedRevenues', 'monthlyDoctorSummary', 'monthlyTotals', 'monthNames', 'totals');
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

        // جلب العمليات المعلقة (بانتظار الدفع أو دفع جزئي، أو التي تحتوي مبالغ زائدة للاسترجاع) مجمعة حسب المريض
        $pendingSurgeries = Surgery::with([
            'patient.user', 
            'doctor.user', 
            'department',
            'surgicalOperation',
            'room',
            'labTests.labTest',
            'radiologyTests.radiologyType',
            'visit',
            'additionalOperations'
        ])
        ->whereIn('status', ['scheduled', 'waiting', 'in_progress', 'completed'])
        ->where('billing_status', '!=', 'pending_review')
        ->where(function($query) {
            $query->whereIn('payment_status', ['pending', 'partial'])
                  ->orWhereRaw('surgery_fee_paid_amount > (surgery_fee + (select COALESCE(sum(fee), 0) from surgery_additional_operations where surgery_additional_operations.surgery_id = surgeries.id))');
        })
        ->orderBy('scheduled_date')
        ->get();

        // تجميع العمليات حسب المريض
        $surgeriesByPatient = $pendingSurgeries->groupBy('patient_id');

        // إحصائيات العمليات
        $today = Carbon::today();
        $surgeryStats = [
            'pending_count' => Surgery::where('billing_status', '!=', 'pending_review')
                ->where(function($query) {
                    $query->whereIn('payment_status', ['pending', 'partial'])
                          ->orWhereRaw('surgery_fee_paid_amount > (surgery_fee + (select COALESCE(sum(fee), 0) from surgery_additional_operations where surgery_additional_operations.surgery_id = surgeries.id))');
                })->count(),
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

        if ($surgery->status === 'cancelled') {
            return redirect()->route('cashier.surgeries.index')
                ->with('warning', 'لا يمكن دفع عملية ملغاة');
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
            'visit',
            'additionalOperations.surgicalOperation',
            'medicalDevices'
        ]);

        // حساب التكلفة الإجمالية
        // use the stored fee on the surgery itself (preserved at creation/edit time)
        $surgeryFee = $surgery->surgery_fee ?? 0;
        $additionalOpsFee = $surgery->additionalOperations->sum('fee');
        $devicesFee = $surgery->medicalDevices->sum('pivot.price');
        $totalSurgeryFee = $surgeryFee + $additionalOpsFee + $devicesFee;

        $labTestsFee = $surgery->labTests->sum(function($test) {
            return $test->labTest->price ?? 0;
        });
        $radiologyTestsFee = $surgery->radiologyTests->sum(function($test) {
            return $test->radiologyType->base_price ?? 0;
        });
        
        $totalAmount = $totalSurgeryFee + $labTestsFee + $radiologyTestsFee;

        return view('cashier.surgeries.payment-form', compact('surgery', 'surgeryFee', 'additionalOpsFee', 'devicesFee', 'totalSurgeryFee', 'labTestsFee', 'radiologyTestsFee', 'totalAmount'));
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

        if ($surgery->status === 'cancelled') {
            return redirect()->route('cashier.surgeries.index')
                ->with('warning', 'لا يمكن تسجيل دفع على عملية ملغاة');
        }

        $request->validate([
            'payment_method' => 'required|in:cash,card,insurance',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'inclusive' => 'nullable|boolean',
            'pay_surgery' => 'nullable',
            'surgery_payment_type' => 'nullable|in:full,partial',
            'surgery_custom_amount' => 'nullable|numeric|min:1',
            'pay_room' => 'nullable',
            'room_payment_type' => 'nullable|in:full,partial',
            'room_custom_amount' => 'nullable|numeric|min:1',
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

        if ($isInclusive) {
            // inclusive covers other costs, so we mark them as paid, but they add 0 to the actual cash amount paid this time
            $payRoom = true;
            $payLabTests = $surgery->labTests->pluck('id')->toArray();
            $payRadiologyTests = $surgery->radiologyTests->pluck('id')->toArray();
            $paySurgery = true;
        }

        if (!$paySurgery && !$payRoom && empty($payLabTests) && empty($payRadiologyTests)) {
            return redirect()->back()
                ->with('error', 'يرجى تحديد عنصر واحد على الأقل للدفع')
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $actualAmount = 0;
            $paidItems = [];

            // 1. Surgery Fee Payment
            $surgeryFeePaidThisTime = 0;
            if ($paySurgery) {
                $surgery->load(['additionalOperations', 'medicalDevices']);
                $totalSurgeryFee = ($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee') + $surgery->medicalDevices->sum('pivot.price');
                $remainingSurgeryFee = max(0, $totalSurgeryFee - ($surgery->surgery_fee_paid_amount ?? 0));
                if ($remainingSurgeryFee > 0) {
                    if ($request->input('surgery_payment_type') === 'partial') {
                        $customAmount = floatval($request->input('surgery_custom_amount'));
                        if ($customAmount <= 0) {
                            throw new \Exception('المبلغ المحدد لدفع رسوم العملية غير صالح');
                        }
                        $surgeryFeePaidThisTime = min($customAmount, $remainingSurgeryFee);
                    } else {
                        $surgeryFeePaidThisTime = $remainingSurgeryFee;
                    }
                    $paidItems[] = 'رسوم العملية الجراحية (الأساسية والإضافية)' . ($isInclusive ? ' (شاملة)' : '') . ' - مدفوع: ' . number_format($surgeryFeePaidThisTime, 0) . ' د.ع';
                    $actualAmount += $surgeryFeePaidThisTime;
                }
            }

            // 2. Room Fee Payment
            $roomFeePaidThisTime = 0;
            if ($payRoom) {
                $remainingRoomFee = max(0, ($surgery->room_fee ?? 0) - ($surgery->room_fee_paid_amount ?? 0));
                if ($remainingRoomFee > 0) {
                    if ($isInclusive) {
                        // inclusive covers room fee
                        $roomFeePaidThisTime = $remainingRoomFee;
                        $paidItems[] = 'أجور الغرفة (مشمولة في عرض العملية)';
                    } else {
                        if ($request->input('room_payment_type') === 'partial') {
                            $customAmount = floatval($request->input('room_custom_amount'));
                            if ($customAmount <= 0) {
                                throw new \Exception('المبلغ المحدد لدفع أجور الغرفة غير صالح');
                            }
                            $roomFeePaidThisTime = min($customAmount, $remainingRoomFee);
                        } else {
                            $roomFeePaidThisTime = $remainingRoomFee;
                        }
                        $actualAmount += $roomFeePaidThisTime;
                        $paidItems[] = 'أجور الغرفة - مدفوع: ' . number_format($roomFeePaidThisTime, 0) . ' د.ع';
                    }
                }
            }

            // 3. Lab Tests
            if (!empty($payLabTests)) {
                foreach ($surgery->labTests as $labTest) {
                    if (in_array($labTest->id, $payLabTests) && $labTest->payment_status !== 'paid') {
                        if ($isInclusive) {
                            $paidItems[] = 'تحليل: ' . ($labTest->labTest->name ?? 'غير محدد') . ' (مشمول)';
                        } else {
                            $labTestPrice = $labTest->labTest->price ?? 0;
                            $actualAmount += $labTestPrice;
                            $paidItems[] = 'تحليل: ' . ($labTest->labTest->name ?? 'غير محدد') . ' (' . number_format($labTestPrice, 0) . ' د.ع)';
                        }
                    }
                }
            }

            // 4. Radiology Tests
            if (!empty($payRadiologyTests)) {
                foreach ($surgery->radiologyTests as $radiologyTest) {
                    if (in_array($radiologyTest->id, $payRadiologyTests) && $radiologyTest->payment_status !== 'paid') {
                        if ($isInclusive) {
                            $paidItems[] = 'أشعة: ' . ($radiologyTest->radiologyType->name ?? 'غير محدد') . ' (مشمولة)';
                        } else {
                            $radiologyPrice = $radiologyTest->radiologyType->base_price ?? 0;
                            $actualAmount += $radiologyPrice;
                            $paidItems[] = 'أشعة: ' . ($radiologyTest->radiologyType->name ?? 'غير محدد') . ' (' . number_format($radiologyPrice, 0) . ' د.ع)';
                        }
                    }
                }
            }

            // التحقق من وجود مبلغ للدفع
            if ($actualAmount <= 0 && !$isInclusive) {
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
                'surgery_id' => $surgery->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => $actualAmount,
                'payment_method' => $request->payment_method,
                'payment_type' => 'surgery',
                'description' => $description,
                'notes' => $request->notes,
                'is_inclusive' => $isInclusive,
                'paid_at' => Carbon::now()
            ]);

            // تحديث مبالغ وحالة رسوم العملية
            if ($paySurgery && $surgeryFeePaidThisTime > 0) {
                $totalSurgeryFee = ($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee') + $surgery->medicalDevices->sum('pivot.price');
                $newSurgeryFeePaidAmount = ($surgery->surgery_fee_paid_amount ?? 0) + $surgeryFeePaidThisTime;
                $surgeryFeePaidStatus = $newSurgeryFeePaidAmount >= $totalSurgeryFee ? 'paid' : 'partial';
                
                $surgery->update([
                    'surgery_fee_paid_amount' => $newSurgeryFeePaidAmount,
                    'surgery_fee_paid' => $surgeryFeePaidStatus
                ]);
            }

            // تحديث مبالغ وحالة رسوم الغرفة
            if ($payRoom && $roomFeePaidThisTime > 0) {
                $newRoomFeePaidAmount = ($surgery->room_fee_paid_amount ?? 0) + $roomFeePaidThisTime;
                $surgery->update([
                    'room_fee_paid_amount' => $newRoomFeePaidAmount
                ]);
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
            $totalSurgeryFee = ($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee') + $surgery->medicalDevices->sum('pivot.price');
            $allSurgeryFeePaid = $surgery->surgery_fee_paid === 'paid' || $totalSurgeryFee <= ($surgery->surgery_fee_paid_amount ?? 0);
            $allRoomFeePaid = ($surgery->room_fee_paid_amount ?? 0) >= ($surgery->room_fee ?? 0);
            $allLabTestsPaid = $surgery->labTests->where('payment_status', '!=', 'paid')->count() === 0;
            $allRadiologyTestsPaid = $surgery->radiologyTests->where('payment_status', '!=', 'paid')->count() === 0;
            $allPaid = $allSurgeryFeePaid && $allRoomFeePaid && $allLabTestsPaid && $allRadiologyTestsPaid;

            // تحديث حالة دفع العملية
            $surgery->update([
                'payment_status' => $allPaid ? 'paid' : 'partial',
                'payment_id' => $payment->id
            ]);

            // تحديث حالة الطلبات المرتبطة إذا تم الدفع الكامل
            if ($allPaid && $surgery->visit_id) {
                MedicalRequest::where('visit_id', $surgery->visit_id)
                    ->where('payment_status', 'pending')
                    ->where('status', '!=', 'cancelled')
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
     * معالجة إرجاع المبالغ الزائدة للعملية الجراحية (Refund)
     */
    public function processSurgeryRefund(Request $request, Surgery $surgery)
    {
        $user = Auth::user();

        if (!$user->can('process surgery payments') && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        if ($surgery->status === 'cancelled') {
            return redirect()->route('cashier.surgeries.index')
                ->with('warning', 'لا يمكن إجراء عملية استرجاع لعملية ملغاة');
        }

        $totalSurgeryFee = ($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee');
        $surgeryFeePaidAmount = $surgery->surgery_fee_paid_amount ?? 0;
        $excessAmount = $surgeryFeePaidAmount - $totalSurgeryFee;

        if ($excessAmount <= 0) {
            return redirect()->back()->with('error', 'لا يوجد مبلغ زائد للاسترجاع.');
        }

        $request->validate([
            'payment_method' => 'required|in:cash,card',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // إنشاء وصف الاسترجاع
            $description = 'إرجاع مبلغ زائد مدفوع للعملية الجراحية: ' . $surgery->surgery_type . ' (ID: #' . $surgery->id . ")\n" .
                           'المبلغ المعاد: ' . number_format($excessAmount, 0) . ' د.ع بعد تعديل سعر العملية من قبل المحاسب.';

            // إنشاء سجل الدفع بقيمة سالبة
            $payment = Payment::create([
                'patient_id' => $surgery->patient_id,
                'cashier_id' => $user->id,
                'surgery_id' => $surgery->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => -$excessAmount,
                'payment_method' => $request->payment_method,
                'payment_type' => 'surgery',
                'description' => $description,
                'notes' => $request->notes,
                'is_inclusive' => false,
                'paid_at' => Carbon::now()
            ]);

            // تحديث مبالغ الدفع وحالتها في الجراحة
            $newPaidAmount = $totalSurgeryFee; // بما أننا أرجعنا الفارق، فالمدفوع الفعلي يساوي المطلوب
            
            $surgery->update([
                'surgery_fee_paid_amount' => $newPaidAmount,
                'surgery_fee_paid' => 'paid'
            ]);

            // التحقق من الدفع الكامل لكامل البنود (الغرفة، التحاليل، الأشعة)
            $allRoomFeePaid = ($surgery->room_fee_paid_amount ?? 0) >= ($surgery->room_fee ?? 0);
            $allLabTestsPaid = $surgery->labTests->where('payment_status', '!=', 'paid')->count() === 0;
            $allRadiologyTestsPaid = $surgery->radiologyTests->where('payment_status', '!=', 'paid')->count() === 0;
            $allPaid = $allRoomFeePaid && $allLabTestsPaid && $allRadiologyTestsPaid;

            $surgery->update([
                'payment_status' => $allPaid ? 'paid' : 'partial'
            ]);

            DB::commit();

            return redirect()->route('cashier.surgeries.index')
                ->with('success', 'تم إرجاع المبلغ الزائد بنجاح بقيمة ' . number_format($excessAmount, 0) . ' د.ع. رقم الإيصال: ' . $payment->receipt_number);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة المرتجع: ' . $e->getMessage());
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

        $payment->load([
            'emergency.patient.user',
            'emergency.services',
            'emergency.labRequests.labTests',
            'emergency.radiologyRequests.radiologyTypes',
            'appointment.doctor.user'
        ]);

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

            // تعيين payment_id في جميع الخدمات غير المدفوعة
            // 1. الخدمات (emergency_emergency_service)
            \DB::table('emergency_emergency_service')
                ->where('emergency_id', $payment->emergency_id)
                ->whereNull('payment_id')
                ->update(['payment_id' => $payment->id]);

            // 2. طلبات التحاليل
            \DB::table('emergency_lab_requests')
                ->where('emergency_id', $payment->emergency_id)
                ->whereNull('payment_id')
                ->update(['payment_id' => $payment->id]);

            // 3. طلبات الأشعة
            \DB::table('emergency_radiology_requests')
                ->where('emergency_id', $payment->emergency_id)
                ->whereNull('payment_id')
                ->update(['payment_id' => $payment->id]);

            // 4. رسوم المتابعة
            if ($payment->emergency->doctor_follow_up_fee > 0 && !$payment->emergency->follow_up_payment_id) {
                $payment->emergency->update([
                    'follow_up_payment_id' => $payment->id,
                ]);
            }

            // تحديث حالة الدفع في حالة الطوارئ
            // تحقق إذا كانت جميع الخدمات مدفوعة
            $hasUnpaidServices = \DB::table('emergency_emergency_service')
                ->where('emergency_id', $payment->emergency_id)
                ->whereNull('payment_id')
                ->exists();
            
            $hasUnpaidLabs = \DB::table('emergency_lab_requests')
                ->where('emergency_id', $payment->emergency_id)
                ->whereNull('payment_id')
                ->exists();
                
            $hasUnpaidRadiology = \DB::table('emergency_radiology_requests')
                ->where('emergency_id', $payment->emergency_id)
                ->whereNull('payment_id')
                ->exists();

            $paymentStatus = ($hasUnpaidServices || $hasUnpaidLabs || $hasUnpaidRadiology) 
                ? 'pending' 
                : 'paid';

            $payment->emergency->update([
                'payment_status' => $paymentStatus,
            ]);

            // تحديث جميع المواعيد المرتبطة بحالة الطوارئ إلى مدفوعة
            $payment->emergency->appointments()
                ->where('payment_status', 'pending')
                ->update([
                    'payment_status' => 'paid',
                    'payment_id' => $payment->id,
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

    /**
     * عرض الحركات المالية لقسم الطوارئ
     */
    public function emergencyFinancialMovements(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'cashier'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $paymentMethod = $request->query('payment_method');

        $query = Payment::with(['emergency.patient.user', 'cashier'])
            ->where('payment_type', 'emergency')
            ->whereNotNull('paid_at');

        if ($fromDate) {
            $query->whereDate('paid_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('paid_at', '<=', $toDate);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        $totalsQuery = clone $query;
        $totalReceived = (clone $totalsQuery)->where('amount', '>=', 0)->sum('amount');
        $totalRefunded = abs((clone $totalsQuery)->where('amount', '<', 0)->sum('amount'));
        $netTotal = (clone $totalsQuery)->sum('amount');

        $payments = $query->orderBy('paid_at', 'desc')->paginate(25);

        return view('cashier.emergency-movements', compact(
            'payments',
            'totalReceived',
            'totalRefunded',
            'netTotal',
            'fromDate',
            'toDate',
            'paymentMethod'
        ));
    }

    /**
     * كشوفات حسابات الطوارئ بالتفصيل
     */
    public function emergencyStatements(Request $request)
    {
        $user = Auth::user();
        if (!$user->can('view cashier reports') && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $query = \App\Models\Payment::with(['emergency.patient.user', 'cashier'])
            ->where('payment_type', 'emergency')
            ->whereNotNull('paid_at');

        if ($fromDate) {
            $query->whereDate('paid_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('paid_at', '<=', $toDate);
        }

        $payments = $query->orderBy('paid_at', 'desc')->get();

        $totalServices = 0;
        $totalLabs = 0;
        $totalRadiology = 0;
        $totalFollowUps = 0;
        $totalCollected = 0;

        $statements = [];

        foreach ($payments as $payment) {
            $servicesSum = 0;
            $labsSum = 0;
            $radiologySum = 0;
            $followUpSum = 0;

            if ($payment->emergency) {
                // Services
                $servicesSum = \DB::table('emergency_emergency_service')
                    ->join('emergency_services', 'emergency_emergency_service.emergency_service_id', '=', 'emergency_services.id')
                    ->where('emergency_emergency_service.payment_id', $payment->id)
                    ->sum('emergency_services.price');

                // Labs
                $labRequestIds = \DB::table('emergency_lab_requests')
                    ->where('payment_id', $payment->id)
                    ->pluck('id');
                if ($labRequestIds->isNotEmpty()) {
                    $labsSum = \DB::table('emergency_lab_request_test')
                        ->join('lab_tests', 'emergency_lab_request_test.lab_test_id', '=', 'lab_tests.id')
                        ->whereIn('emergency_lab_request_test.emergency_lab_request_id', $labRequestIds)
                        ->sum('lab_tests.price');
                }

                // Radiology
                $radiologyRequestIds = \DB::table('emergency_radiology_requests')
                    ->where('payment_id', $payment->id)
                    ->pluck('id');
                if ($radiologyRequestIds->isNotEmpty()) {
                    $radiologySum = \DB::table('emergency_radiology_request_type')
                        ->join('radiology_types', 'emergency_radiology_request_type.radiology_type_id', '=', 'radiology_types.id')
                        ->whereIn('emergency_radiology_request_type.emergency_radiology_request_id', $radiologyRequestIds)
                        ->sum('radiology_types.base_price');
                }

                // Follow-up
                if ($payment->emergency->follow_up_payment_id == $payment->id) {
                    $followUpSum = $payment->emergency->doctor_follow_up_fee;
                }
            }

            $totalServices += $servicesSum;
            $totalLabs += $labsSum;
            $totalRadiology += $radiologySum;
            $totalFollowUps += $followUpSum;
            $totalCollected += $payment->amount;

            $statements[] = [
                'payment' => $payment,
                'services_sum' => $servicesSum,
                'labs_sum' => $labsSum,
                'radiology_sum' => $radiologySum,
                'follow_up_sum' => $followUpSum,
                'total' => $payment->amount,
            ];
        }

        $perPage = 25;
        $page = $request->query('page', 1);
        $offset = ($page * $perPage) - $perPage;
        $paginatedStatements = new \Illuminate\Pagination\LengthAwarePaginator(
            array_slice($statements, $offset, $perPage, true),
            count($statements),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('cashier.emergency-statements', compact(
            'paginatedStatements',
            'totalServices',
            'totalLabs',
            'totalRadiology',
            'totalFollowUps',
            'totalCollected',
            'fromDate',
            'toDate'
        ));
    }

    /**
     * حسابات أطباء الطوارئ
     */
    public function emergencyDoctorAccounts(Request $request)
    {
        $user = Auth::user();
        if (!$user->can('view cashier reports') && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $doctors = \App\Models\Doctor::with(['user', 'department'])
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->orderBy('users.name')
            ->select('doctors.*')
            ->get();

        $doctorAccounts = [];

        foreach ($doctors as $doctor) {
            $earnedCases = \App\Models\Emergency::where('doctor_id', $doctor->id)
                ->whereNotNull('follow_up_payment_id')
                ->whereHas('payment', function($q) {
                    $q->whereNotNull('paid_at');
                })
                ->where('doctor_follow_up_fee', '>', 0)
                ->get();

            $totalEarned = $earnedCases->sum('doctor_follow_up_fee');

            $totalPaid = \App\Models\DoctorDue::where('doctor_id', $doctor->id)
                ->where('notes', 'like', '%طوارئ%')
                ->sum('amount');

            $balance = $totalEarned - $totalPaid;

            if ($totalEarned > 0 || $totalPaid > 0) {
                $doctorAccounts[] = [
                    'doctor' => $doctor,
                    'cases_count' => $earnedCases->count(),
                    'total_earned' => $totalEarned,
                    'total_paid' => $totalPaid,
                    'balance' => $balance,
                ];
            }
        }

        return view('cashier.emergency-doctor-accounts', compact('doctorAccounts'));
    }

    /**
     * تفاصيل مستحقات طبيب طوارئ واحد
     */
    public function emergencyDoctorAccount(Request $request, \App\Models\Doctor $doctor)
    {
        $doctor->load(['user', 'department']);

        $earnedCases = \App\Models\Emergency::with(['patient.user', 'payment'])
            ->where('doctor_id', $doctor->id)
            ->whereNotNull('follow_up_payment_id')
            ->whereHas('payment', function($q) {
                $q->whereNotNull('paid_at');
            })
            ->where('doctor_follow_up_fee', '>', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'cases_page');

        $totalEarned = \App\Models\Emergency::where('doctor_id', $doctor->id)
            ->whereNotNull('follow_up_payment_id')
            ->whereHas('payment', function($q) {
                $q->whereNotNull('paid_at');
            })
            ->where('doctor_follow_up_fee', '>', 0)
            ->sum('doctor_follow_up_fee');

        $payouts = \App\Models\DoctorDue::with('paidBy')
            ->where('doctor_id', $doctor->id)
            ->where('notes', 'like', '%طوارئ%')
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'payouts_page');

        $totalPaid = \App\Models\DoctorDue::where('doctor_id', $doctor->id)
            ->where('notes', 'like', '%طوارئ%')
            ->sum('amount');

        $balance = $totalEarned - $totalPaid;

        return view('cashier.emergency-doctor-account', compact(
            'doctor',
            'earnedCases',
            'totalEarned',
            'payouts',
            'totalPaid',
            'balance'
        ));
    }

    /**
     * صرف مستحقات طبيب طوارئ
     */
    public function emergencyDoctorPayout(Request $request, \App\Models\Doctor $doctor)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'cashier'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500'
        ]);

        $amount = $request->amount;

        $totalEarned = \App\Models\Emergency::where('doctor_id', $doctor->id)
            ->whereNotNull('follow_up_payment_id')
            ->whereHas('payment', function($q) {
                $q->whereNotNull('paid_at');
            })
            ->where('doctor_follow_up_fee', '>', 0)
            ->sum('doctor_follow_up_fee');

        $totalPaid = \App\Models\DoctorDue::where('doctor_id', $doctor->id)
            ->where('notes', 'like', '%طوارئ%')
            ->sum('amount');

        $balance = $totalEarned - $totalPaid;

        if ($amount > $balance) {
            return redirect()->back()->with('error', 'المبلغ المدخل أكبر من الرصيد المتاح للطبيب.');
        }

        DB::beginTransaction();
        try {
            \App\Models\DoctorDue::create([
                'doctor_id' => $doctor->id,
                'amount' => $amount,
                'status' => 'paid',
                'notes' => $request->notes ?: 'صرف مستحقات أطباء الطوارئ',
                'paid_by_id' => $user->id,
                'paid_at' => now(),
            ]);

            \App\Models\FinancialTransaction::create([
                'transaction_type' => 'expense',
                'related_type' => \App\Models\Doctor::class,
                'related_id' => $doctor->id,
                'amount' => $amount,
                'currency' => 'IQD',
                'description' => 'صرف مستحقات طوارئ للطبيب ' . optional($doctor->user)->name,
                'performed_by_id' => $user->id,
                'performed_at' => now(),
            ]);

            DB::commit();
            return redirect()->route('cashier.emergency.doctor-account', $doctor)->with('success', 'تم تسجيل دفعة الصرف للطبيب بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء الصرف: ' . $e->getMessage());
        }
    }
}
