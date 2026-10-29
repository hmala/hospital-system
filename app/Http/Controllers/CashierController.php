<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\ConsultationRevenue;
use App\Models\Emergency;
use App\Models\LabTest;
use App\Models\Payment;
use App\Models\RadiologyType;
use App\Models\Request as MedicalRequest;
use App\Models\Surgery;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CashierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * عرض قائمة المواعيد المعلقة التي تحتاج للدفع
     */
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $canFullMedical = $isAdmin || ($user && $user->can('process medical requests payments'));
        $canUltrasoundOnly = ! $canFullMedical && ($user && $user->can('process ultrasound payments'));

        if (! $isAdmin && (! $user || (! $user->can('view cashier') && ! $user->can('process consultation payments') && ! $canFullMedical && ! $canUltrasoundOnly && ! $user->can('process emergency payments')))) {
            abort(403, 'غير مصرح لك بالوصول إلى لوحة الكاشير');
        }

        $today = Carbon::today();
        $dateFilter = request('date_filter', 'today'); // 'today' (default), 'previous', 'all', 'custom'
        $customDate = request('custom_date');
        $search = trim(request('search', ''));

        // إجمالي المعلقات السابقة (قبل اليوم)
        $previousPendingAptsCount = Appointment::where('payment_status', 'pending')
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereDate('appointment_date', '<', $today)
            ->whereNull('emergency_id')
            ->whereHas('patient')
            ->count();

        $previousPendingReqsQuery = MedicalRequest::where('payment_status', 'pending')
            ->whereNotIn('status', ['cancelled', 'postponed'])
            ->whereDate('created_at', '<', $today)
            ->whereHas('visit.patient')
            ->whereDoesntHave('visit.surgery');

        if ($canUltrasoundOnly) {
            $previousPendingReqsQuery->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('subtype', 'ultrasound')
                        ->orWhere('description', 'LIKE', '%سونار%');
                })->where('details', 'LIKE', '%created_at_inquiry%');
            });
        }

        $previousPendingReqsCount = $previousPendingReqsQuery->count();

        $previousPendingCount = $previousPendingAptsCount + $previousPendingReqsCount;

        // جلب المواعيد المعلقة (غير المدفوعة)
        $pendingAppointmentsQuery = Appointment::with(['patient.user', 'doctor.user', 'department'])
            ->where('payment_status', 'pending')
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->whereNull('emergency_id')
            ->whereDoesntHave('visit.requests', function ($q) {
                $q->whereIn('type', ['radiology', 'lab', 'blood_bank']);
            })
            ->whereHas('patient');

        if ($dateFilter === 'today') {
            $pendingAppointmentsQuery->whereDate('appointment_date', $today);
        } elseif ($dateFilter === 'previous') {
            $pendingAppointmentsQuery->whereDate('appointment_date', '<', $today);
        } elseif ($dateFilter === 'custom' && $customDate) {
            $pendingAppointmentsQuery->whereDate('appointment_date', $customDate);
        }

        if ($search) {
            $pendingAppointmentsQuery->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('patient.user', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('patient', function ($qp) use ($search) {
                        $qp->where('national_id', 'like', "%{$search}%");
                    });
            });
        }

        $pendingAppointments = $pendingAppointmentsQuery
            ->orderBy('appointment_date', 'asc')
            ->paginate(15, ['*'], 'appointments_page')
            ->appends(request()->query());

        // جلب الطلبات المعلقة (تحاليل، أشعة، صيدلية) - استبعاد الطلبات المرتبطة بالعمليات والمؤجلة
        $pendingRequestsQuery = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->where('payment_status', 'pending')
            ->whereNotIn('status', ['cancelled', 'postponed'])
            ->whereHas('visit', function ($q) {
                $q->where('status', '!=', 'cancelled')
                    ->where(function ($query) {
                        $query->where('visit_type', '!=', 'surgery')
                            ->orWhereNull('visit_type');
                    });
            })
            ->whereHas('visit.patient')
            ->whereDoesntHave('visit.surgery');

        if ($canUltrasoundOnly) {
            // تصفية حصرية لطلبات السونار الخارجي المباشر فقط (المحجوزة من الاستعلامات)
            $pendingRequestsQuery->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('subtype', 'ultrasound')
                        ->orWhere('description', 'LIKE', '%سونار%');
                })->where('details', 'LIKE', '%created_at_inquiry%');
            });
        }

        if ($dateFilter === 'today') {
            $pendingRequestsQuery->whereDate('created_at', $today);
        } elseif ($dateFilter === 'previous') {
            $pendingRequestsQuery->whereDate('created_at', '<', $today);
        } elseif ($dateFilter === 'custom' && $customDate) {
            $pendingRequestsQuery->whereDate('created_at', $customDate);
        }

        if ($search) {
            $pendingRequestsQuery->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('visit.patient.user', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('visit.patient', function ($qp) use ($search) {
                        $qp->where('national_id', 'like', "%{$search}%");
                    });
            });
        }

        $pendingRequests = $pendingRequestsQuery
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'requests_page')
            ->appends(request()->query());

        // جلب الطلبات المؤجلة بطلب المريض (Postponed Requests)
        $postponedRequestsQuery = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->where('payment_status', 'pending')
            ->where('status', 'postponed')
            ->whereHas('visit', function ($q) {
                $q->where('status', '!=', 'cancelled')
                    ->where(function ($query) {
                        $query->where('visit_type', '!=', 'surgery')
                            ->orWhereNull('visit_type');
                    });
            })
            ->whereHas('visit.patient')
            ->whereDoesntHave('visit.surgery');

        if ($canUltrasoundOnly) {
            $postponedRequestsQuery->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('subtype', 'ultrasound')
                        ->orWhere('description', 'LIKE', '%سونار%');
                })->where('details', 'LIKE', '%created_at_inquiry%');
            });
        }

        if ($dateFilter === 'today') {
            $postponedRequestsQuery->whereDate('created_at', $today);
        } elseif ($dateFilter === 'previous') {
            $postponedRequestsQuery->whereDate('created_at', '<', $today);
        } elseif ($dateFilter === 'custom' && $customDate) {
            $postponedRequestsQuery->whereDate('created_at', $customDate);
        }

        if ($search) {
            $postponedRequestsQuery->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('visit.patient.user', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('visit.patient', function ($qp) use ($search) {
                        $qp->where('national_id', 'like', "%{$search}%");
                    });
            });
        }

        $postponedMedicalRequests = $postponedRequestsQuery
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'postponed_page')
            ->appends(request()->query());

        $postponedRequestsCount = $postponedMedicalRequests->total();

        // جلب خدمات الطوارئ المعلقة الدفع
        $pendingEmergencyQuery = Payment::with(['emergency.patient.user', 'emergency.emergencyPatient', 'emergency.services', 'appointment.doctor.user'])
            ->where('payment_type', 'emergency')
            ->whereNull('appointment_id')
            ->whereNull('paid_at')
            ->whereHas('emergency');

        if ($dateFilter === 'today') {
            $pendingEmergencyQuery->whereDate('created_at', $today);
        } elseif ($dateFilter === 'previous') {
            $pendingEmergencyQuery->whereDate('created_at', '<', $today);
        } elseif ($dateFilter === 'custom' && $customDate) {
            $pendingEmergencyQuery->whereDate('created_at', $customDate);
        }

        if ($search) {
            $pendingEmergencyQuery->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('emergency_id', $search)
                    ->orWhereHas('emergency.patient.user', function ($qu) use ($search) {
                        $qu->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('emergency.emergencyPatient', function ($qe) use ($search) {
                        $qe->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $pendingEmergencyPayments = $pendingEmergencyQuery
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'emergency_page')
            ->appends(request()->query());

        // إحصائيات اليوم المحسنة
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
            'postponed_requests_count' => $postponedRequestsCount,
            'previous_pending_count' => $previousPendingCount,
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

        // جلب طلبات الفحوصات الطبية الملغاة بعد السداد والمستحقة للاسترجاع (Refunds)
        $pendingRefundQuery = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user', 'payments'])
            ->where('status', 'cancelled')
            ->whereHas('payments', function ($q) {
                $q->where('amount', '>', 0);
            })
            ->whereDoesntHave('payments', function ($q) {
                $q->where('amount', '<', 0);
            });

        if ($dateFilter === 'previous') {
            $pendingRefundQuery->whereDate('updated_at', '<', $today);
        }

        $pendingRefundRequests = $pendingRefundQuery
            ->orderBy('updated_at', 'desc')
            ->get();

        // جلب حالات الطوارئ الملغاة بعد السداد والمستحقة للاسترجاع (Emergency Refunds)
        $pendingEmergencyRefundQuery = \App\Models\Emergency::with(['patient.user', 'emergencyPatient', 'doctor.user', 'payments'])
            ->where('status', 'cancelled')
            ->whereHas('payments', function ($q) {
                $q->where('amount', '>', 0);
            })
            ->whereDoesntHave('payments', function ($q) {
                $q->where('amount', '<', 0);
            });

        if ($dateFilter === 'previous') {
            $pendingEmergencyRefundQuery->whereDate('updated_at', '<', $today);
        }

        $pendingEmergencyRefunds = $pendingEmergencyRefundQuery
            ->orderBy('updated_at', 'desc')
            ->get();

        // جلب فحوصات الأشعة في الطوارئ الملغاة بعد السداد والمستحقة للاسترجاع
        $pendingEmergencyRadRefunds = \App\Models\EmergencyRadiologyRequest::with([
            'emergency.patient.user',
            'emergency.emergencyPatient',
            'emergency.doctor.user',
            'radiologyTypes',
            'payment',
        ])
            ->where('status', 'cancelled')
            ->whereNotNull('payment_id')
            ->where('payment_status', '!=', 'refunded')
            ->orderBy('updated_at', 'desc')
            ->get();

        // جلب تحاليل المختبر في الطوارئ الملغاة بعد السداد والمستحقة للاسترجاع
        $pendingEmergencyLabRefunds = \App\Models\EmergencyLabRequest::with([
            'emergency.patient.user',
            'emergency.emergencyPatient',
            'emergency.doctor.user',
            'labTests',
            'payment',
        ])
            ->where('status', 'cancelled')
            ->whereNotNull('payment_id')
            ->where('payment_status', '!=', 'refunded')
            ->orderBy('updated_at', 'desc')
            ->get();

        $pendingRefundCount = $pendingRefundRequests->count() + $pendingEmergencyRefunds->count() + $pendingEmergencyRadRefunds->count() + $pendingEmergencyLabRefunds->count();

        // استخدام اسم مختلف لتجنب التعارض
        $pendingMedicalRequests = $pendingRequests;

        return view('cashier.index', compact(
            'pendingAppointments',
            'pendingMedicalRequests',
            'postponedMedicalRequests',
            'postponedRequestsCount',
            'pendingEmergencyPayments',
            'pendingRefundRequests',
            'pendingEmergencyRefunds',
            'pendingEmergencyRadRefunds',
            'pendingEmergencyLabRefunds',
            'pendingRefundCount',
            'todayStats',
            'todayPayments',
            'monthlyStats',
            'dateFilter',
            'customDate',
            'search',
            'previousPendingCount'
        ));
    }

    /**
     * عرض صفحة الدفع لموعد معين
     */
    public function showPaymentForm(Appointment $appointment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('process consultation payments'))) {
            abort(403, 'غير مصرح لك بقبض وتسديد رسوم الكشفية');
        }

        // التحقق من أن الموعد لم يتم دفعه بعد
        if ($appointment->payment_status === 'paid') {
            return redirect()->route('cashier.index')
                ->with('warning', 'هذا الموعد تم دفعه مسبقاً');
        }

        $appointment->load(['patient.user', 'doctor.user', 'department', 'visit']);

        return view('cashier.payment-form', compact('appointment'));
    }

    /**
     * معالجة الدفع
     */
    public function processPayment(Request $request, Appointment $appointment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('process consultation payments'))) {
            abort(403, 'غير مصرح لك بقبض وتسديد رسوم الكشفية');
        }

        $request->validate([
            'payment_method' => 'required|in:cash,card,insurance',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        // التحقق من أن الموعد لم يتم دفعه بعد
        if ($appointment->payment_status === 'paid') {
            return redirect()->route('cashier.index')
                ->with('error', 'هذا الموعد تم دفعه مسبقاً');
        }

        $patient = $appointment->patient;
        $insuranceType = $request->input('insurance_type', $patient->insurance_type ?? 'none');

        // فحص ما إذا كان الموعد مرتبطاً بفحص سونار أو أشعة
        $scanType = null;
        $appointment->load('visit');
        if ($appointment->visit) {
            $medReq = \App\Models\Request::where('visit_id', $appointment->visit->id)->where('type', 'radiology')->first();
            if ($medReq) {
                $details = is_string($medReq->details) ? json_decode($medReq->details, true) : $medReq->details;
                $radTypeId = $details['ultrasound_type_id'] ?? ($details['radiology_type_ids'][0] ?? null);
                if ($radTypeId) {
                    $scanType = \App\Models\RadiologyType::find($radTypeId);
                }
            }
        }

        if (! $scanType && $appointment->doctor && $insuranceType === 'hi') {
            $docCommission = \App\Models\DoctorCommissionSetting::where('doctor_id', $appointment->doctor->id)->first();
            if (! $docCommission || (float) ($docCommission->hi_fixed_amount ?? 0) <= 0) {
                return back()->with('error', 'لا يمكن التسديد! لم يتم تحديد حصة الطبيب لكشفيات الضمان الصحي من قبل الإدارة في إعدادات العمولات.');
            }
        }

        DB::beginTransaction();
        try {
            $copayPercentage = ($insuranceType === 'hi' && $patient)
                ? (float) $patient->getCopayPercentageFor('consultation')
                : (float) $request->input('copay_percentage', $patient->copay_percentage ?? 0);
            $cardNo = $request->input('insurance_card_no', $patient->insurance_card_no ?? $patient->insurance_booklet_number ?? null);

            if ($scanType) {
                $servicePricing = $scanType->calculateInsurancePricing($insuranceType, $copayPercentage);
            } elseif ($appointment->doctor && $appointment->doctor->consultation_fee > 0) {
                $servicePricing = $appointment->doctor->calculateInsurancePricing($insuranceType, $copayPercentage);
            } else {
                $baseFee = (float) ($appointment->consultation_fee > 0 ? $appointment->consultation_fee : 0);
                $isCovered = ($insuranceType !== 'none');
                $patientShare = $isCovered ? round($baseFee * ($copayPercentage / 100.0), 2) : $baseFee;
                $servicePricing = [
                    'is_covered' => $isCovered,
                    'total_amount' => $baseFee,
                    'patient_share' => $patientShare,
                    'insurance_share' => max(0, $baseFee - $patientShare),
                ];
            }

            $isCovered = $servicePricing ? $servicePricing['is_covered'] : false;
            $totalApproved = $isCovered ? (float) $servicePricing['total_amount'] : ($appointment->consultation_fee ?? (float) $request->amount);
            if ($totalApproved <= 0) {
                $totalApproved = (float) $request->amount;
            }
            $patientPaidAmount = $isCovered ? (float) $servicePricing['patient_share'] : (float) $request->amount;
            $patientShare = $patientPaidAmount;
            $insuranceShare = $isCovered ? max(0, $totalApproved - $patientShare) : 0;
            $claimStatus = ($insuranceShare > 0) ? 'pending' : 'none';

            // إنشاء سجل الدفع مع تجميد الأرقام المالية الثلاثة
            $payment = Payment::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'cashier_id' => $user->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => $patientPaidAmount,
                'total_amount' => $totalApproved,
                'patient_share' => $patientShare,
                'insurance_share' => $insuranceShare,
                'insurance_type' => $insuranceType,
                'insurance_card_no' => $cardNo,
                'copay_percentage' => $isCovered ? $copayPercentage : 0,
                'claim_status' => $claimStatus,
                'payment_method' => $request->payment_method,
                'payment_type' => 'appointment',
                'description' => ($scanType ? 'دفع رسوم '.$scanType->name : 'دفع رسوم موعد #'.$appointment->id),
                'notes' => $request->notes,
                'paid_at' => Carbon::now(),
            ]);

            // تحديث حالة الدفع للموعد
            $appointment->update([
                'payment_status' => 'paid',
                'payment_id' => $payment->id,
            ]);

            // تحديث الزيارة والطلبات الطبية المرتبطة إن وجدت
            if ($appointment->visit) {
                $appointment->visit->update([
                    'status' => 'scheduled', // في انتظار دخول الطبيب
                ]);

                $medReqs = \App\Models\Request::where('visit_id', $appointment->visit->id)->get();
                foreach ($medReqs as $medReq) {
                    $medReq->update([
                        'payment_status' => 'paid',
                        'payment_id' => $payment->id,
                        'status' => 'pending',
                    ]);

                    // إذا كان طلباً للأشعة أو السونار، إنشاء سجل في radiology_requests إن لم يوجد
                    if ($medReq->type === 'radiology') {
                        $details = is_string($medReq->details) ? json_decode($medReq->details, true) : $medReq->details;
                        $radTypeIds = $details['radiology_type_ids'] ?? [];
                        if (empty($radTypeIds) && ! empty($details['ultrasound_type_id'])) {
                            $radTypeIds = [$details['ultrasound_type_id']];
                        }

                        foreach ($radTypeIds as $radTypeId) {
                            $exists = \App\Models\RadiologyRequest::where('visit_id', $appointment->visit->id)
                                ->where('radiology_type_id', $radTypeId)
                                ->exists();

                            if (! $exists) {
                                try {
                                    $radTypeObj = \App\Models\RadiologyType::find($radTypeId);
                                    \App\Models\RadiologyRequest::create([
                                        'visit_id' => $appointment->visit->id,
                                        'patient_id' => $appointment->patient_id,
                                        'doctor_id' => $appointment->doctor_id,
                                        'radiology_type_id' => $radTypeId,
                                        'requested_date' => now(),
                                        'status' => 'pending',
                                        'priority' => 'normal',
                                        'clinical_indication' => $appointment->reason ?? 'طلب سونار من الاستعلامات',
                                        'total_cost' => $radTypeObj ? $radTypeObj->base_price : null,
                                    ]);
                                } catch (\Exception $e) {
                                    \Log::error('Failed to create radiology request from appointment payment: '.$e->getMessage());
                                }
                            }
                        }
                    }
                }
            }

            DB::commit();

            return redirect()->route('cashier.receipt', $payment->id)
                ->with('success', 'تم تسجيل الدفع بنجاح! رقم الإيصال: '.$payment->receipt_number);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء معالجة الدفع: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض إيصال الدفع
     */
    public function showReceipt(Payment $payment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $canViewReceipt = $isAdmin || (
            $user && (
                $user->can('view cashier') ||
                $user->can('view cashier reports') ||
                $user->can('process consultation payments') ||
                $user->can('process medical requests payments') ||
                $user->can('process emergency payments') ||
                $user->can('process surgery payments') ||
                $user->can('process payments') ||
                $user->can('view payments') ||
                $user->hasRole('patient')
            )
        );

        if (! $canViewReceipt) {
            abort(403, 'غير مصرح لك بعرض سند القبض');
        }

        $payment->load([
            'patient.user',
            'appointment.doctor.user',
            'appointment.department',
            'request.visit.patient.user',
            'request.visit.doctor.user',
            'surgery.room',
            'surgery.patient.user',
            'surgery.doctor.user',
            'surgery.department',
            'surgery.labTests.labTest',
            'surgery.radiologyTests.radiologyType',
            'cashier',
        ]);

        return view('cashier.receipt', compact('payment'));
    }

    /**
     * طباعة إيصال الدفع (PDF)
     */
    public function printReceipt(Request $request, Payment $payment)
    {
        $payment->load([
            'patient.user',
            'appointment.doctor.user',
            'appointment.department',
            'request.visit.patient.user',
            'request.visit.doctor.user',
            'surgery.room',
            'surgery.patient.user',
            'surgery.doctor.user',
            'surgery.department',
            'surgery.labTests.labTest',
            'surgery.radiologyTests.radiologyType',
            'cashier',
        ]);

        return view('cashier.receipt-print', compact('payment'));
    }

    /**
     * عرض نموذج دفع لطلب طبي
     */
    public function showRequestPaymentForm(MedicalRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $isUltrasoundRequest = ($request->subtype === 'ultrasound' || str_contains($request->description ?? '', 'سونار') || str_contains(json_encode($request->details ?? []), 'ultrasound'));

        $canPay = $isAdmin
            || ($user && $user->can('process medical requests payments'))
            || ($user && $user->can('process medical request payments'))
            || ($user && $user->can('process ultrasound payments') && $isUltrasoundRequest)
            || ($user && $user->can('process consultation payments') && in_array($request->type, ['radiology', 'lab']));

        if (! $canPay) {
            abort(403, 'غير مصرح لك بقبض رسوم الفحوصات الطبية');
        }

        // التحقق من أن الطلب لم يتم دفعه بعد
        if ($request->payment_status === 'paid') {
            return redirect()->route('cashier.index')
                ->with('warning', 'هذا الطلب تم دفعه مسبقاً');
        }

        $request->load(['visit.patient.user', 'visit.doctor.user']);

        if (! $request->visit) {
            // shouldn't normally happen but guard anyway
            return redirect()->route('cashier.index')
                ->with('error', 'الطلب غير مرتبط بأي زيارة ولا يمكن عرضه.');
        }

        // جلب أي طلبات أخرى غير مدفوعة تابعة لنفس الزيارة لتوحيدها في فاتورة واحدة للمريض
        $otherPendingRequests = MedicalRequest::where('visit_id', $request->visit_id)
            ->where('id', '!=', $request->id)
            ->where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled')
            ->get();

        return view('cashier.request-payment-form', compact('request', 'otherPendingRequests'));
    }

    /**
     * معالجة دفع الطلب الطبي
     */
    public function processRequestPayment(Request $httpRequest, MedicalRequest $request)
    {
        \Log::info('========== processRequestPayment CALLED ==========');
        \Log::info('Request ID: '.$request->id);
        \Log::info('HTTP Request data: '.json_encode($httpRequest->all()));

        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $isUltrasoundRequest = ($request->subtype === 'ultrasound' || str_contains($request->description ?? '', 'سونار') || str_contains(json_encode($request->details ?? []), 'ultrasound'));

        $canPay = $isAdmin
            || ($user && $user->can('process medical requests payments'))
            || ($user && $user->can('process medical request payments'))
            || ($user && $user->can('process ultrasound payments') && $isUltrasoundRequest)
            || ($user && $user->can('process consultation payments') && in_array($request->type, ['radiology', 'lab']));

        if (! $canPay) {
            abort(403, 'غير مصرح لك بقبض رسوم الفحوصات الطبية');
        }

        // التحقق من أن الطلب لم يتم دفعه بعد
        if ($request->payment_status === 'paid') {
            return redirect()->route('cashier.index')
                ->with('error', 'هذا الطلب تم دفعه مسبقاً');
        }

        // Ensure visit is loaded and exists
        $request->load('visit');
        if (! $request->visit) {
            return redirect()->route('cashier.index')
                ->with('error', 'الطلب غير مرتبط بأي زيارة. لا يمكن متابعة الدفع.');
        }

        $patient = optional($request->visit)->patient;
        $appointment = optional($request->visit)->appointment;
        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;

        // شرط الحصانة: الطلبات الخارجية والزيارات المباشرة للمختبر والأشعة بدون إحالة استشاري تحاسب كاش نقدي كامل 100%
        $isDirectVisit = empty($appointment) || in_array($request->visit?->visit_type, ['lab', 'radiology', 'external'], true) || (! empty($details['direct_lab_visit']) || ! empty($details['direct_radiology_visit']) || ! empty($details['created_at_inquiry']));
        if ($isDirectVisit && empty($appointment?->doctor_id)) {
            $insuranceType = 'none';
        } else {
            $insuranceType = $httpRequest->input('insurance_type', $request->insurance_type ?? optional($appointment)->insurance_type ?? $patient->insurance_type ?? 'none');
        }

        $copayPercentage = ($insuranceType === 'hi' && $patient)
            ? (float) $patient->getCopayPercentageFor($request->type)
            : (float) $httpRequest->input('copay_percentage', ($patient->copay_percentage ?? 0));
        $cardNo = ($insuranceType !== 'none') ? $httpRequest->input('insurance_card_no', $patient->insurance_card_no ?? $patient->insurance_booklet_number ?? null) : null;

        // الطلبات المتضمنة في الدفع الموحد للزيارة
        $includedRequestIds = (array) $httpRequest->input('included_request_ids', [$request->id]);
        $includedRequestIds = array_unique(array_filter(array_map('intval', $includedRequestIds)));
        if (! in_array($request->id, $includedRequestIds)) {
            $includedRequestIds[] = $request->id;
        }

        $allRequestsToPay = MedicalRequest::whereIn('id', $includedRequestIds)
            ->where('visit_id', $request->visit_id)
            ->where('payment_status', 'pending')
            ->get();

        if ($allRequestsToPay->isEmpty()) {
            $allRequestsToPay = collect([$request]);
        }

        // استلام إجراءات كل فحص (تسديد / تأجيل / إلغاء بطلب المريض) وأسباب الاستبعاد
        $itemActions = (array) $httpRequest->input('item_actions', []);
        $itemReasons = (array) $httpRequest->input('item_reasons', []);
        $itemCustomReasons = (array) $httpRequest->input('item_custom_reasons', []);

        $getReasonText = function ($reqId, $itemType, $itemId) use ($itemReasons, $itemCustomReasons) {
            $reasonKey = $itemReasons[$reqId][$itemType][$itemId] ?? 'lack_of_funds';
            $custom = trim($itemCustomReasons[$reqId][$itemType][$itemId] ?? '');
            $labels = [
                'lack_of_funds' => 'عدم توفر المبلغ المالي / قلة السيولة لدى المريض',
                'patient_refused' => 'عدم رغبة المريض بالفحص / رفض الإجراء',
                'time_constraint' => 'ضيق الوقت ورغبة المريض بالعودة لاحقاً',
                'other' => 'أسباب أخرى',
            ];
            $base = $labels[$reasonKey] ?? 'بطلب من المريض لدى الكاشير';

            return $custom ? "{$base} ({$custom})" : $base;
        };

        // مسح أولي لمعرفة هل توجد أي فحوصات مسددة أم أن كافة الفحوصات ألغيت/أجلت بطلب المريض
        $hasAnyPay = false;
        $hasAnyCancel = false;
        $hasAnyPostpone = false;

        foreach ($allRequestsToPay as $reqItem) {
            $rDetails = is_string($reqItem->details) ? json_decode($reqItem->details, true) : ($reqItem->details ?? []);
            if ($reqItem->type === 'lab') {
                if (! empty($rDetails['package_id'])) {
                    $pkgId = $rDetails['package_id'];
                    $act = $itemActions[$reqItem->id]['package'][$pkgId] ?? ($itemActions[$reqItem->id]['package']['pkg_'.$pkgId] ?? 'pay');
                    if ($act === 'pay') {
                        $hasAnyPay = true;
                    } elseif ($act === 'cancel') {
                        $hasAnyCancel = true;
                    } elseif ($act === 'postpone') {
                        $hasAnyPostpone = true;
                    }
                }
                $tIds = $rDetails['lab_test_ids'] ?? [];
                if (! empty($tIds)) {
                    foreach ($tIds as $tId) {
                        $act = $itemActions[$reqItem->id]['lab'][$tId] ?? 'pay';
                        if ($act === 'pay') {
                            $hasAnyPay = true;
                        } elseif ($act === 'cancel') {
                            $hasAnyCancel = true;
                        } elseif ($act === 'postpone') {
                            $hasAnyPostpone = true;
                        }
                    }
                } elseif (! empty($rDetails['tests'])) {
                    foreach ($rDetails['tests'] as $tName) {
                        $tModel = \App\Models\LabTest::where('name', $tName)->orWhere('code', $tName)->first();
                        $lookupId = $tModel?->id ?? $tName;
                        $act = $itemActions[$reqItem->id]['lab'][$lookupId] ?? ($itemActions[$reqItem->id]['lab'][$tName] ?? 'pay');
                        if ($act === 'pay') {
                            $hasAnyPay = true;
                        } elseif ($act === 'cancel') {
                            $hasAnyCancel = true;
                        } elseif ($act === 'postpone') {
                            $hasAnyPostpone = true;
                        }
                    }
                }
            } elseif ($reqItem->type === 'radiology') {
                $radIds = $rDetails['radiology_type_ids'] ?? $rDetails['radiology_types'] ?? $rDetails['radiology_type_id'] ?? [];
                if (! is_array($radIds)) {
                    $radIds = [$radIds];
                }
                foreach ($radIds as $rId) {
                    $act = $itemActions[$reqItem->id]['radiology'][$rId] ?? 'pay';
                    if ($act === 'pay') {
                        $hasAnyPay = true;
                    } elseif ($act === 'cancel') {
                        $hasAnyCancel = true;
                    } elseif ($act === 'postpone') {
                        $hasAnyPostpone = true;
                    }
                }
            }
        }

        // =========================================================================
        // الحالة أ: لم يتم اختيار أي فحص للدفع (إلغاء أو تأجيل كامل الطلبات بطلب المريض)
        // =========================================================================
        if (! $hasAnyPay) {
            DB::beginTransaction();
            try {
                foreach ($allRequestsToPay as $reqItem) {
                    $rDetails = is_string($reqItem->details) ? json_decode($reqItem->details, true) : ($reqItem->details ?? []);
                    $itemActionsForReq = [];

                    if ($reqItem->type === 'lab') {
                        $tIds = $rDetails['lab_test_ids'] ?? [];
                        if (empty($tIds) && ! empty($rDetails['tests'])) {
                            foreach ($rDetails['tests'] as $tName) {
                                $tModel = \App\Models\LabTest::where('name', $tName)->orWhere('code', $tName)->first();
                                $tIds[] = $tModel?->id ?? $tName;
                            }
                        }
                        foreach ($tIds as $tId) {
                            $itemActionsForReq[$tId] = $itemActions[$reqItem->id]['lab'][$tId] ?? 'cancel';
                        }
                    } elseif ($reqItem->type === 'radiology') {
                        $radIds = (array) ($rDetails['radiology_type_ids'] ?? $rDetails['radiology_types'] ?? $rDetails['radiology_type_id'] ?? []);
                        foreach ($radIds as $rId) {
                            $itemActionsForReq[$rId] = $itemActions[$reqItem->id]['radiology'][$rId] ?? 'cancel';
                        }
                    }

                    $reqPostponed = in_array('postpone', $itemActionsForReq) && ! in_array('cancel', $itemActionsForReq);
                    $firstKey = ! empty($itemActionsForReq) ? array_key_first($itemActionsForReq) : 0;
                    $reasonText = $getReasonText($reqItem->id, $reqItem->type, $firstKey);

                    if ($reqPostponed) {
                        $rDetails['postpone_reason'] = $reasonText;
                        $rDetails['postponed_by_cashier_id'] = $user->id;
                        $rDetails['postponed_at'] = Carbon::now()->toDateTimeString();
                        $reqItem->status = 'postponed';
                    } else {
                        $rDetails['cancellation_reason'] = $reasonText;
                        $rDetails['cancelled_by_cashier_id'] = $user->id;
                        $rDetails['cancelled_at'] = Carbon::now()->toDateTimeString();
                        $reqItem->status = 'cancelled';
                        $reqItem->payment_status = 'cancelled';
                    }
                    $reqItem->details = $rDetails;
                    $reqItem->save();
                }

                DB::commit();

                $redirectMsg = ($hasAnyCancel && ! $hasAnyPostpone)
                    ? 'تم إلغاء كافة الفحوصات بطلب من المريض بنجاح دون تحصيل أي مبالغ.'
                    : ((! $hasAnyCancel && $hasAnyPostpone)
                        ? 'تم تأجيل كافة الفحوصات بطلب من المريض بنجاح وحفظها لمراجعته القادمة.'
                        : 'تم تحديث حالة الفحوصات (إلغاء / تأجيل) بطلب المريض دون تحصيل أي مبالغ.');

                return redirect()->route('cashier.index')->with('info', $redirectMsg);
            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة الطلبات: '.$e->getMessage());
            }
        }

        // =========================================================================
        // الحالة ب: توجد فحوصات مسددة (مع إمكانية استبعاد/إلغاء/تأجيل فحوصات أخرى جزئياً)
        // =========================================================================
        $httpRequest->validate([
            'payment_method' => 'required|in:cash,card,insurance',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            \Log::info('Starting payment process for request #'.$request->id);

            $computedTotal = 0;
            $computedPatientShare = 0;
            $computedInsuranceShare = 0;
            $hasCoveredItem = false;
            $descParts = [];
            $package = null;
            $seenLabTestIds = [];
            $seenLabTestNames = [];
            $seenRadKeys = [];
            $excludedNotes = [];
            $actuallyPaidRequests = collect();

            foreach ($allRequestsToPay as $reqItem) {
                $details = is_string($reqItem->details) ? json_decode($reqItem->details, true) : ($reqItem->details ?? []);

                if ($reqItem->type === 'lab') {
                    $packageId = $details['package_id'] ?? null;
                    $package = $packageId ? \App\Models\Package::find($packageId) : null;

                    if ($package) {
                        $pkgAct = $itemActions[$reqItem->id]['package'][$packageId] ?? 'pay';
                        if ($pkgAct === 'pay') {
                            $pkgPrice = (float) $package->price;
                            $computedTotal += $pkgPrice;
                            $computedPatientShare += $pkgPrice;
                            $descParts[] = 'باقة مختبر ('.$package->name.')';
                            $actuallyPaidRequests->push($reqItem);
                        } else {
                            $reasonText = $getReasonText($reqItem->id, 'package', $packageId);
                            if ($pkgAct === 'cancel') {
                                $reqItem->status = 'cancelled';
                                $reqItem->payment_status = 'cancelled';
                                $details['cancellation_reason'] = $reasonText;
                                $details['cancelled_by_cashier_id'] = $user->id;
                                $details['cancelled_at'] = Carbon::now()->toDateTimeString();
                                $excludedNotes[] = 'إلغاء باقة مختبر ('.$package->name.') - '.$reasonText;
                            } else {
                                $reqItem->status = 'postponed';
                                $details['postpone_reason'] = $reasonText;
                                $details['postponed_by_cashier_id'] = $user->id;
                                $details['postponed_at'] = Carbon::now()->toDateTimeString();
                                $excludedNotes[] = 'تأجيل باقة مختبر ('.$package->name.') - '.$reasonText;
                            }
                            $reqItem->details = $details;
                            $reqItem->save();

                            continue;
                        }
                    } else {
                        // تحاليل فردية
                        $allTestIds = (array) ($details['lab_test_ids'] ?? []);
                        if (empty($allTestIds) && ! empty($details['tests'])) {
                            foreach ($details['tests'] as $tName) {
                                $tModel = \App\Models\LabTest::where('name', $tName)->orWhere('code', $tName)->first();
                                if ($tModel) {
                                    $allTestIds[] = $tModel->id;
                                }
                            }
                        }

                        $paidLabIds = [];
                        $cancelledLabIds = [];
                        $postponedLabIds = [];

                        foreach ($allTestIds as $tId) {
                            $act = $itemActions[$reqItem->id]['lab'][$tId] ?? 'pay';
                            if ($act === 'pay') {
                                $paidLabIds[] = $tId;
                            } elseif ($act === 'cancel') {
                                $cancelledLabIds[] = $tId;
                            } elseif ($act === 'postpone') {
                                $postponedLabIds[] = $tId;
                            }
                        }

                        // فصل التحاليل الملغاة في طلب مستقل
                        if (! empty($cancelledLabIds)) {
                            $cancelledNames = \App\Models\LabTest::whereIn('id', $cancelledLabIds)->pluck('name')->toArray();
                            $reasonText = $getReasonText($reqItem->id, 'lab', $cancelledLabIds[0]);
                            $excludedNotes[] = 'إلغاء تحاليل: '.implode('، ', $cancelledNames).' ('.$reasonText.')';

                            if (empty($paidLabIds) && empty($postponedLabIds)) {
                                $reqItem->status = 'cancelled';
                                $reqItem->payment_status = 'cancelled';
                                $details['cancellation_reason'] = $reasonText;
                                $details['cancelled_by_cashier_id'] = $user->id;
                                $details['cancelled_at'] = Carbon::now()->toDateTimeString();
                                $reqItem->details = $details;
                                $reqItem->save();
                            } else {
                                MedicalRequest::create([
                                    'visit_id' => $reqItem->visit_id,
                                    'type' => 'lab',
                                    'subtype' => $reqItem->subtype,
                                    'description' => 'تحاليل ملغاة بطلب المريض لدى الكاشير: '.implode('، ', $cancelledNames),
                                    'status' => 'cancelled',
                                    'payment_status' => 'cancelled',
                                    'insurance_type' => $insuranceType,
                                    'details' => [
                                        'tests' => $cancelledNames,
                                        'lab_test_ids' => array_values($cancelledLabIds),
                                        'cancellation_reason' => $reasonText,
                                        'cancelled_by_cashier_id' => $user->id,
                                        'cancelled_at' => Carbon::now()->toDateTimeString(),
                                        'original_request_id' => $reqItem->id,
                                        'action_source' => 'cashier_item_selection',
                                    ],
                                    'requested_by' => $reqItem->requested_by,
                                ]);
                            }
                        }

                        // فصل التحاليل المؤجلة في طلب مستقل
                        if (! empty($postponedLabIds)) {
                            $postponedNames = \App\Models\LabTest::whereIn('id', $postponedLabIds)->pluck('name')->toArray();
                            $reasonText = $getReasonText($reqItem->id, 'lab', $postponedLabIds[0]);
                            $excludedNotes[] = 'تأجيل تحاليل: '.implode('، ', $postponedNames).' ('.$reasonText.')';

                            if (empty($paidLabIds)) {
                                $reqItem->status = 'postponed';
                                $details['postpone_reason'] = $reasonText;
                                $details['postponed_by_cashier_id'] = $user->id;
                                $details['postponed_at'] = Carbon::now()->toDateTimeString();
                                $reqItem->details = $details;
                                $reqItem->save();
                            } else {
                                MedicalRequest::create([
                                    'visit_id' => $reqItem->visit_id,
                                    'type' => 'lab',
                                    'subtype' => $reqItem->subtype,
                                    'description' => 'تحاليل مؤجلة بطلب المريض لدى الكاشير: '.implode('، ', $postponedNames),
                                    'status' => 'postponed',
                                    'payment_status' => 'pending',
                                    'insurance_type' => $insuranceType,
                                    'details' => [
                                        'tests' => $postponedNames,
                                        'lab_test_ids' => array_values($postponedLabIds),
                                        'postpone_reason' => $reasonText,
                                        'postponed_by_cashier_id' => $user->id,
                                        'postponed_at' => Carbon::now()->toDateTimeString(),
                                        'original_request_id' => $reqItem->id,
                                        'action_source' => 'cashier_item_selection',
                                    ],
                                    'requested_by' => $reqItem->requested_by,
                                ]);
                            }
                        }

                        // احتساب الفحوصات المسددة فقط
                        if (! empty($paidLabIds)) {
                            $paidNames = \App\Models\LabTest::whereIn('id', $paidLabIds)->pluck('name')->toArray();
                            $details['lab_test_ids'] = array_values($paidLabIds);
                            $details['tests'] = $paidNames;
                            $reqItem->details = $details;
                            $reqItem->save();

                            $actuallyPaidRequests->push($reqItem);

                            foreach ($paidLabIds as $testId) {
                                if (in_array($testId, $seenLabTestIds)) {
                                    continue;
                                }
                                $seenLabTestIds[] = $testId;
                                $test = LabTest::find($testId);
                                if ($test && $test->is_active) {
                                    $normTestName = mb_strtolower(trim($test->name));
                                    if (in_array($normTestName, $seenLabTestNames)) {
                                        continue;
                                    }
                                    $seenLabTestNames[] = $normTestName;

                                    $p = $test->calculateInsurancePricing($insuranceType, $copayPercentage);
                                    $computedTotal += $p['total_amount'];
                                    $computedPatientShare += $p['patient_share'];
                                    $computedInsuranceShare += $p['insurance_share'];
                                    if ($p['is_covered']) {
                                        $hasCoveredItem = true;
                                    }
                                }
                            }
                            $descParts[] = 'مختبر #'.$reqItem->id;
                        }
                    }
                } elseif ($reqItem->type === 'radiology') {
                    $allRadIds = (array) ($details['radiology_type_ids'] ?? $details['radiology_types'] ?? $details['radiology_type_id'] ?? []);
                    $radTargets = (array) ($details['radiology_targets'] ?? []);

                    $paidRadIds = [];
                    $cancelledRadIds = [];
                    $postponedRadIds = [];

                    foreach ($allRadIds as $rId) {
                        $act = $itemActions[$reqItem->id]['radiology'][$rId] ?? 'pay';
                        if ($act === 'pay') {
                            $paidRadIds[] = $rId;
                        } elseif ($act === 'cancel') {
                            $cancelledRadIds[] = $rId;
                        } elseif ($act === 'postpone') {
                            $postponedRadIds[] = $rId;
                        }
                    }

                    // فصل الأشعة الملغاة في طلب مستقل
                    if (! empty($cancelledRadIds)) {
                        $cancelledRadNames = \App\Models\RadiologyType::whereIn('id', $cancelledRadIds)->pluck('name')->toArray();
                        $reasonText = $getReasonText($reqItem->id, 'radiology', $cancelledRadIds[0]);
                        $excludedNotes[] = 'إلغاء أشعة: '.implode('، ', $cancelledRadNames).' ('.$reasonText.')';

                        if (empty($paidRadIds) && empty($postponedRadIds)) {
                            $reqItem->status = 'cancelled';
                            $reqItem->payment_status = 'cancelled';
                            $details['cancellation_reason'] = $reasonText;
                            $details['cancelled_by_cashier_id'] = $user->id;
                            $details['cancelled_at'] = Carbon::now()->toDateTimeString();
                            $reqItem->details = $details;
                            $reqItem->save();
                        } else {
                            MedicalRequest::create([
                                'visit_id' => $reqItem->visit_id,
                                'type' => 'radiology',
                                'subtype' => $reqItem->subtype,
                                'description' => 'أشعة ملغاة بطلب المريض لدى الكاشير: '.implode('، ', $cancelledRadNames),
                                'status' => 'cancelled',
                                'payment_status' => 'cancelled',
                                'insurance_type' => $insuranceType,
                                'details' => [
                                    'radiology_type_ids' => array_values($cancelledRadIds),
                                    'radiology_types' => array_values($cancelledRadIds),
                                    'radiology_targets' => array_intersect_key($radTargets, array_flip($cancelledRadIds)),
                                    'cancellation_reason' => $reasonText,
                                    'cancelled_by_cashier_id' => $user->id,
                                    'cancelled_at' => Carbon::now()->toDateTimeString(),
                                    'original_request_id' => $reqItem->id,
                                    'action_source' => 'cashier_item_selection',
                                ],
                                'requested_by' => $reqItem->requested_by,
                            ]);
                        }
                    }

                    // فصل الأشعة المؤجلة في طلب مستقل
                    if (! empty($postponedRadIds)) {
                        $postponedRadNames = \App\Models\RadiologyType::whereIn('id', $postponedRadIds)->pluck('name')->toArray();
                        $reasonText = $getReasonText($reqItem->id, 'radiology', $postponedRadIds[0]);
                        $excludedNotes[] = 'تأجيل أشعة: '.implode('، ', $postponedRadNames).' ('.$reasonText.')';

                        if (empty($paidRadIds)) {
                            $reqItem->status = 'postponed';
                            $details['postpone_reason'] = $reasonText;
                            $details['postponed_by_cashier_id'] = $user->id;
                            $details['postponed_at'] = Carbon::now()->toDateTimeString();
                            $reqItem->details = $details;
                            $reqItem->save();
                        } else {
                            MedicalRequest::create([
                                'visit_id' => $reqItem->visit_id,
                                'type' => 'radiology',
                                'subtype' => $reqItem->subtype,
                                'description' => 'أشعة مؤجلة بطلب المريض لدى الكاشير: '.implode('، ', $postponedRadNames),
                                'status' => 'postponed',
                                'payment_status' => 'pending',
                                'insurance_type' => $insuranceType,
                                'details' => [
                                    'radiology_type_ids' => array_values($postponedRadIds),
                                    'radiology_types' => array_values($postponedRadIds),
                                    'radiology_targets' => array_intersect_key($radTargets, array_flip($postponedRadIds)),
                                    'postpone_reason' => $reasonText,
                                    'postponed_by_cashier_id' => $user->id,
                                    'postponed_at' => Carbon::now()->toDateTimeString(),
                                    'original_request_id' => $reqItem->id,
                                    'action_source' => 'cashier_item_selection',
                                ],
                                'requested_by' => $reqItem->requested_by,
                            ]);
                        }
                    }

                    // احتساب فحوصات الأشعة المسددة فقط
                    if (! empty($paidRadIds)) {
                        $details['radiology_type_ids'] = array_values($paidRadIds);
                        $details['radiology_types'] = array_values($paidRadIds);
                        $details['radiology_targets'] = array_intersect_key($radTargets, array_flip($paidRadIds));
                        $reqItem->details = $details;
                        $reqItem->save();

                        $actuallyPaidRequests->push($reqItem);

                        foreach ($paidRadIds as $typeId) {
                            $targetPart = isset($radTargets[$typeId]) ? trim($radTargets[$typeId]) : '';
                            $radKey = $typeId.'_'.$targetPart;
                            if (in_array($radKey, $seenRadKeys)) {
                                continue;
                            }
                            $seenRadKeys[] = $radKey;
                            $type = RadiologyType::find($typeId);
                            if ($type) {
                                $p = $type->calculateInsurancePricing($insuranceType, $copayPercentage);
                                $computedTotal += $p['total_amount'];
                                $computedPatientShare += $p['patient_share'];
                                $computedInsuranceShare += $p['insurance_share'];
                                if ($p['is_covered']) {
                                    $hasCoveredItem = true;
                                }
                            }
                        }
                        $descParts[] = 'أشعة #'.$reqItem->id;
                    }
                }
            }

            if ($actuallyPaidRequests->isEmpty()) {
                throw new \Exception('لم يتم العثور على أي خدمات مسددة لمعالجة الدفع.');
            }

            $totalApproved = $computedTotal > 0 ? $computedTotal : (float) $httpRequest->amount;
            // ضمان تطابق المبلغ المقبوض وحصة المريض مع المجموع الفعلي لأسطر الفحوصات والوصل المالي
            $patientShare = ($computedTotal > 0) ? $computedPatientShare : (float) $httpRequest->amount;
            $patientPaidAmount = $patientShare;
            $insuranceShare = $hasCoveredItem ? max(0, $totalApproved - $patientShare) : 0;
            $claimStatus = ($insuranceShare > 0) ? 'pending' : 'none';

            $paymentType = in_array('radiology', $actuallyPaidRequests->pluck('type')->toArray()) && ! in_array('lab', $actuallyPaidRequests->pluck('type')->toArray()) ? 'radiology' : 'lab';
            $paymentDescription = count($actuallyPaidRequests) > 1
                ? ('دفع رسوم موحدة للزيارة ('.implode(' + ', $descParts).')')
                : (! empty($package) ? ('دفع رسوم باقة: '.$package->name.' - طلب #'.$actuallyPaidRequests->first()->id) : ('دفع رسوم طلب '.$actuallyPaidRequests->first()->type.' #'.$actuallyPaidRequests->first()->id));

            $combinedNotes = $httpRequest->notes ?: '';
            if (! empty($excludedNotes)) {
                $combinedNotes = trim($combinedNotes.(empty($combinedNotes) ? '' : ' | ').implode(' | ', $excludedNotes));
            }

            // إنشاء سجل الدفع مع تجميد الأرقام المالية الثلاثة
            \Log::info('Creating payment record with insurance snapshot...');
            $primaryRequestId = $actuallyPaidRequests->first()->id ?? $request->id;
            $payment = Payment::create([
                'request_id' => $primaryRequestId,
                'patient_id' => $request->visit->patient_id,
                'cashier_id' => $user->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => $patientPaidAmount,
                'total_amount' => $totalApproved,
                'patient_share' => $patientShare,
                'insurance_share' => $insuranceShare,
                'insurance_type' => $insuranceType,
                'insurance_card_no' => $cardNo,
                'copay_percentage' => $hasCoveredItem ? $copayPercentage : 0,
                'claim_status' => $claimStatus,
                'payment_method' => $httpRequest->payment_method,
                'payment_type' => $paymentType,
                'description' => $paymentDescription,
                'notes' => $combinedNotes,
                'paid_at' => Carbon::now(),
                'lab_request_ids' => json_encode($actuallyPaidRequests->where('type', 'lab')->pluck('id')->values()->toArray()),
                'radiology_request_ids' => json_encode($actuallyPaidRequests->where('type', 'radiology')->pluck('id')->values()->toArray()),
            ]);

            \Log::info('Payment created successfully: #'.$payment->id);

            // تحديث حالة الدفع لكافة الطلبات المشمولة بالدفع الفعلي
            foreach ($actuallyPaidRequests as $reqItem) {
                $reqItem->update([
                    'payment_status' => 'paid',
                    'payment_id' => $payment->id,
                ]);
            }

            // تحصين رقابي: أي طلب طبي كان مشمولاً بقائمة الدفع ولم يُسدد ولم يُؤجل يُعتبر ملغياً بطلب المريض
            $unpaidExcludedRequests = $allRequestsToPay->whereNotIn('id', $actuallyPaidRequests->pluck('id'));
            foreach ($unpaidExcludedRequests as $unpaidReq) {
                if (! in_array($unpaidReq->status, ['cancelled', 'postponed'])) {
                    $uDetails = is_string($unpaidReq->details) ? json_decode($unpaidReq->details, true) : ($unpaidReq->details ?? []);
                    if (! is_array($uDetails)) {
                        $uDetails = [];
                    }
                    $uDetails['cancellation_reason'] = $uDetails['cancellation_reason'] ?? 'استبعاد وإلغاء بطلب المريض عند إصدار الوصل لدى الكاشير';
                    $uDetails['cancelled_by_cashier_id'] = $user->id;
                    $uDetails['cancelled_at'] = Carbon::now()->toDateTimeString();
                    $unpaidReq->update([
                        'status' => 'cancelled',
                        'payment_status' => 'cancelled',
                        'payment_id' => null,
                        'details' => $uDetails,
                    ]);
                }
            }

            \Log::info('All included requests ('.$allRequestsToPay->pluck('id')->join(',').') updated. Paid: '.$actuallyPaidRequests->pluck('id')->join(','));

            // تحديث حالة الزيارة من pending_payment إلى scheduled لتظهر في قائمة انتظار المختبر والأشعة (فقط للزيارات قيد انتظار الدفع)
            if ($request->visit) {
                if ($request->visit->status === 'pending_payment') {
                    $request->visit->update([
                        'status' => 'scheduled',
                    ]);
                }

                // تحديث الموعد المرتبط إن وجد (حصراً لحجوزات السونار المباشرة الخالية من كشفية طبيب مستقلة ولم تدفع بعد)
                if ($request->visit->appointment_id) {
                    $linkedAppt = \App\Models\Appointment::with(['doctor.currentCommissionSetting', 'patient'])->find($request->visit->appointment_id);
                    if ($linkedAppt && empty($linkedAppt->payment_id) && (float) $linkedAppt->consultation_fee == 0) {
                        $linkedAppt->update([
                            'payment_status' => 'paid',
                            'status' => 'confirmed',
                            'payment_id' => $payment->id,
                        ]);

                        if (empty($payment->appointment_id)) {
                            $payment->update(['appointment_id' => $linkedAppt->id]);
                        }

                        // إنشاء أو تحديث قيد إيرادات الاستشارية لحجز السونار الخارجي المباشر
                        $doctor = $linkedAppt->doctor;
                        $setting = $doctor?->currentCommissionSetting;
                        $amount = (float) ($payment->total_amount > 0 ? $payment->total_amount : $payment->amount);
                        $docShare = 0;
                        if ($setting && $amount > 0) {
                            if ($setting->commission_type === 'fixed') {
                                $isInsurance = in_array($payment->insurance_type, ['hi', 'moi']) || ($payment->insurance_share > 0);
                                $fixed = $isInsurance && $setting->hi_fixed_amount > 0
                                    ? (float) $setting->hi_fixed_amount
                                    : (float) $setting->fixed_amount;
                                $docShare = min($amount, $fixed);
                            } elseif ($setting->commission_type === 'percentage') {
                                $docShare = round($amount * ($setting->commission_value / 100), 2);
                            }
                        }
                        $hospShare = max(0, round($amount - $docShare, 2));
                        $percentage = $amount > 0 ? round(($docShare / $amount) * 100, 2) : 0;

                        \App\Models\ConsultationRevenue::updateOrCreate(
                            ['payment_id' => $payment->id],
                            [
                                'receipt_number' => $payment->receipt_number,
                                'payment_method' => $payment->payment_method ?? 'cash',
                                'payment_type' => 'ultrasound',
                                'cashier_id' => $payment->cashier_id,
                                'appointment_id' => $linkedAppt->id,
                                'patient_id' => $linkedAppt->patient_id ?? $payment->patient_id,
                                'doctor_id' => $doctor?->id,
                                'department_id' => $linkedAppt->department_id,
                                'service_type_id' => null,
                                'examination_count' => 1,
                                'total_amount' => $amount,
                                'doctor_share' => $docShare,
                                'hospital_share' => $hospShare,
                                'doctor_percentage' => $percentage,
                                'movement_type' => 'payment',
                                'revenue_date' => $payment->paid_at ? $payment->paid_at->toDateString() : now()->toDateString(),
                                'paid_at' => $payment->paid_at ?? now(),
                                'notes' => 'حجز سونار خارجي مباشر من الاستعلامات (طلب #'.$request->id.')',
                            ]
                        );
                    }
                }
                \Log::info('Visit and linked appointment status handled after request payment');
            } else {
                \Log::warning('Cannot update visit status: visit is null for request #'.$request->id);
            }

            // إذا كان أي من الطلبات المدفوعة أشعة، إنشاء سجلات في radiology_requests إن لم تكن موجودة
            foreach ($allRequestsToPay->where('type', 'radiology') as $radReq) {
                \Log::info('Processing radiology request payment, request_id: '.$radReq->id);

                $radDetails = is_string($radReq->details) ? json_decode($radReq->details, true) : $radReq->details;
                \Log::info('Request details: '.json_encode($radDetails));

                $radiologyTypeIds = [];
                if (isset($radDetails['radiology_type_ids']) && ! empty($radDetails['radiology_type_ids'])) {
                    $radiologyTypeIds = $radDetails['radiology_type_ids'];
                } elseif (isset($radDetails['radiology_types']) && ! empty($radDetails['radiology_types'])) {
                    $radiologyTypeIds = $radDetails['radiology_types'];
                }

                if (! empty($radiologyTypeIds)) {
                    if (! $radReq->visit) {
                        \Log::warning('Skipping radiology request creation, visit missing for request #'.$radReq->id);
                    } else {
                        \Log::info('Found radiology type ids: '.json_encode($radiologyTypeIds));

                        foreach ($radiologyTypeIds as $radiologyTypeId) {
                            $exists = \App\Models\RadiologyRequest::where('visit_id', $radReq->visit_id)
                                ->where('radiology_type_id', $radiologyTypeId)
                                ->exists();

                            if (! $exists) {
                                try {
                                    $radiologyType = \App\Models\RadiologyType::find($radiologyTypeId);

                                    $doctorId = $radReq->visit->doctor_id;
                                    if (! $doctorId && $radReq->visit->department_id) {
                                        $doctor = \App\Models\Doctor::where('department_id', $radReq->visit->department_id)->first();
                                        $doctorId = $doctor ? $doctor->id : null;
                                    }

                                    $radiologyRequest = \App\Models\RadiologyRequest::create([
                                        'visit_id' => $radReq->visit_id,
                                        'patient_id' => $radReq->visit->patient_id,
                                        'doctor_id' => $doctorId,
                                        'radiology_type_id' => $radiologyTypeId,
                                        'requested_date' => $radReq->created_at ?? now(),
                                        'status' => 'pending',
                                        'priority' => $radDetails['priority'] ?? 'normal',
                                        'clinical_indication' => $radReq->description ?? 'طلب من الاستعلامات',
                                        'total_cost' => $radiologyType ? $radiologyType->base_price : null,
                                    ]);

                                    \Log::info('Created radiology_request #'.$radiologyRequest->id.' for type_id: '.$radiologyTypeId);
                                } catch (\Exception $e) {
                                    \Log::error('Failed to create radiology_request: '.$e->getMessage());
                                }
                            } else {
                                \Log::info('Radiology request already exists for visit_id: '.$radReq->visit_id.', type_id: '.$radiologyTypeId);
                            }
                        }
                    }
                } else {
                    \Log::warning('No radiology_type_ids found in request details for request #'.$radReq->id);
                }
            }

            // إذا كان الطلب تمريض (nursing)، يتم إرساله لموظفي الطوارئ
            if ($request->type === 'nursing') {
                \Log::info('Processing nursing request payment, request_id: '.$request->id);
                // يتم عرضه في StaffRequestController للموظفين في قسم الطوارئ
            }

            // إذا كان الطلب طوارئ، إنشاء سجل طوارئ
            if ($request->type === 'emergency') {
                \Log::info('Processing emergency request payment, request_id: '.$request->id);

                $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                \Log::info('Emergency request details: '.json_encode($details));

                if (isset($details['emergency_priority']) && isset($details['emergency_type'])) {
                    // double-check visit exists
                    if (! $request->visit) {
                        \Log::warning('Skipping emergency record creation, visit missing for request #'.$request->id);
                    } else {
                        try {
                            // إنشاء سجل طوارئ
                            $isEmergencyInsured = ($request->insurance_type && $request->insurance_type !== 'none');
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
                                'is_insured' => $isEmergencyInsured,
                                'insurance_type' => $isEmergencyInsured ? $request->insurance_type : 'none',
                            ]);

                            \Log::info('Created emergency record #'.$emergency->id.' for request #'.$request->id);
                        } catch (\Exception $e) {
                            \Log::error('Failed to create emergency record: '.$e->getMessage());
                        }
                    } // end visit-exists else branch
                } // end isset-emergency_priority check
                else {
                    \Log::warning('Emergency request missing required details for request #'.$request->id);
                }
            }

            DB::commit();

            \Log::info('Transaction committed. Redirecting to cashier receipt.');

            // العودة إلى صفحة إيصال الدفع
            return redirect()->route('cashier.receipt', $payment->id)
                ->with('success', 'تم تسجيل الدفع بنجاح! رقم الإيصال: '.$payment->receipt_number)
                ->with('payment_id', $payment->id);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء معالجة الدفع: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * معالجة استرجاع مبالغ الفحوصات الطبية الملغاة (مختبر / أشعة / سونار / رنين / مفراس / إيكو)
     */
    public function processRequestRefund(Request $httpRequest, MedicalRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('process medical requests payments') && ! $user->can('process refunds') && ! $user->can('process payments') && ! $user->hasRole('cashier')))) {
            abort(403, 'غير مصرح لك بمعالجة استرجاع المبالغ الطبية');
        }

        // 1. التحقق من أن الطلب ملغي
        if ($request->status !== 'cancelled') {
            return redirect()->back()->with('error', 'لا يمكن استرجاع المبلغ لأن الطلب الطبي غير ملغي.');
        }

        // 2. التحقق من المبالغ المدفوعة والمسترجعة مسبقاً
        $totalPaid = (float) $request->payments()->where('amount', '>', 0)->sum('amount');
        $totalAlreadyRefunded = abs((float) $request->payments()->where('amount', '<', 0)->sum('amount'));
        $netRefundable = max(0, $totalPaid - $totalAlreadyRefunded);

        if ($netRefundable <= 0) {
            return redirect()->back()->with('error', 'لا يوجد رصيد مالي مسدد مستحق للاسترجاع لهذا الطلب.');
        }

        $httpRequest->validate([
            'payment_method' => 'required|in:cash,card',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $originalPayment = $request->payments()->where('amount', '>', 0)->latest()->first();
            $patientId = $request->visit?->patient_id ?? $originalPayment?->patient_id;

            $typeName = match ($request->type) {
                'lab' => 'تحاليل المختبر',
                'radiology' => 'الأشعة والتصوير',
                default => 'الفحوصات الطبية',
            };

            $description = "استرجاع مالي للمريض: طلب {$typeName} ملغي #{$request->id}".
                ($originalPayment ? " (عن الوصل الأصلي #{$originalPayment->receipt_number})" : '');

            // إنشاء سند دفع سالب (Refund)
            $refundPayment = Payment::create([
                'patient_id' => $patientId,
                'cashier_id' => $user->id,
                'request_id' => $request->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => -$netRefundable,
                'total_amount' => -$netRefundable,
                'patient_share' => -$netRefundable,
                'insurance_share' => 0,
                'insurance_type' => 'none',
                'copay_percentage' => 0,
                'claim_status' => 'none',
                'payment_method' => $httpRequest->input('payment_method', 'cash'),
                'payment_type' => $request->type === 'lab' ? 'lab' : 'radiology',
                'description' => $description,
                'notes' => $httpRequest->input('notes', 'استرجاع نقدي لطلب فحص خارجي ملغي'),
                'paid_at' => now(),
            ]);

            // تحديث تفاصيل الطلب
            $details = is_string($request->details) ? (json_decode($request->details, true) ?: []) : ($request->details ?? []);
            $details['refund_status'] = 'refunded';
            $details['refunded_at'] = now()->toDateTimeString();
            $details['refunded_by'] = $user->id;
            $details['refunded_by_name'] = $user->name;
            $details['refund_payment_id'] = $refundPayment->id;
            $details['refund_receipt_number'] = $refundPayment->receipt_number;
            $details['refunded_amount'] = $netRefundable;

            $request->details = $details;
            $request->save();

            DB::commit();

            $successMsg = 'تم صرف واسترجاع مبلغ ('.number_format($netRefundable, 0)." د.ع) للمريض بنجاح، وتوليد سند الاسترجاع رقم #{$refundPayment->receipt_number}.";

            return redirect()->route('cashier.index')
                ->with('success', $successMsg)
                ->with('payment_id', $refundPayment->id);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing medical request refund: '.$e->getMessage());

            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة الاسترجاع المالي: '.$e->getMessage());
        }
    }

    /**
     * معالجة استرجاع مبالغ حالات الطوارئ الملغاة
     */
    public function processEmergencyRefund(Request $httpRequest, \App\Models\Emergency $emergency)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('process emergency payments') && ! $user->can('process refunds') && ! $user->can('process payments') && ! $user->hasRole('cashier')))) {
            abort(403, 'غير مصرح لك بمعالجة استرجاع مبالغ الطوارئ');
        }

        // 1. التحقق من أن حالة الطوارئ ملغاة
        if ($emergency->status !== 'cancelled') {
            return redirect()->back()->with('error', 'لا يمكن استرجاع المبلغ لأن حالة الطوارئ غير ملغاة.');
        }

        // 2. التحقق من المبالغ المدفوعة والمسترجعة مسبقاً
        $totalPaid = (float) $emergency->payments()->where('amount', '>', 0)->sum('amount');
        $totalAlreadyRefunded = abs((float) $emergency->payments()->where('amount', '<', 0)->sum('amount'));
        $netRefundable = max(0, $totalPaid - $totalAlreadyRefunded);

        if ($netRefundable <= 0) {
            return redirect()->back()->with('error', 'لا يوجد رصيد مالي مسدد مستحق للاسترجاع لهذه الحالة.');
        }

        $httpRequest->validate([
            'payment_method' => 'required|in:cash,card',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $originalPayment = $emergency->payments()->where('amount', '>', 0)->latest()->first();
            $patientId = $emergency->patient_id ?? $originalPayment?->patient_id;

            $description = "استرجاع مالي للمريض: حالة طوارئ ملغاة #{$emergency->id}".
                ($originalPayment ? " (عن الوصل الأصلي #{$originalPayment->receipt_number})" : '');

            // إنشاء سند دفع سالب (Refund)
            $refundPayment = Payment::create([
                'patient_id' => $patientId,
                'cashier_id' => $user->id,
                'emergency_id' => $emergency->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => -$netRefundable,
                'total_amount' => -$netRefundable,
                'patient_share' => -$netRefundable,
                'insurance_share' => 0,
                'insurance_type' => 'none',
                'copay_percentage' => 0,
                'claim_status' => 'none',
                'payment_method' => $httpRequest->input('payment_method', 'cash'),
                'payment_type' => 'emergency',
                'description' => $description,
                'notes' => $httpRequest->input('notes', 'استرجاع نقدي لحالة طوارئ ملغاة'),
                'paid_at' => now(),
            ]);

            // إلغاء أي مطالبات ضمان معلقة على هذه الحالة
            $emergency->payments()->where('claim_status', 'pending')->update(['claim_status' => 'cancelled']);

            // تحديث حالة دفع الطوارئ
            $emergency->update([
                'payment_status' => 'refunded',
            ]);

            DB::commit();

            $successMsg = 'تم صرف واسترجاع مبلغ ('.number_format($netRefundable, 0)." د.ع) للمريض بنجاح، وتوليد سند الاسترجاع رقم #{$refundPayment->receipt_number}.";

            return redirect()->route('cashier.receipt', $refundPayment->id)
                ->with('success', $successMsg)
                ->with('payment_id', $refundPayment->id);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing emergency refund: '.$e->getMessage());

            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة الاسترجاع المالي: '.$e->getMessage());
        }
    }

    /**
     * معالجة استرجاع مبالغ طلبات الطوارئ الملغاة (فحوصات الأشعة أو تحاليل المختبر)
     */
    public function processEmergencyRequestRefund(Request $httpRequest, string $type, int $id)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('process emergency payments') && ! $user->can('process refunds') && ! $user->can('process payments') && ! $user->hasRole('cashier')))) {
            abort(403, 'غير مصرح لك بمعالجة استرجاع مبالغ الطوارئ');
        }

        if ($type === 'radiology') {
            $req = \App\Models\EmergencyRadiologyRequest::with(['emergency.patient', 'radiologyTypes', 'payment'])->find($id);
            $typeName = 'الأشعة والتصوير';
            $itemsList = $req ? $req->radiologyTypes->pluck('name')->join('، ') : '';
            $refundAmount = $req ? (float) $req->radiologyTypes->sum(fn ($t) => $t->base_price ?? $t->price ?? 0) : 0;
        } elseif ($type === 'lab') {
            $req = \App\Models\EmergencyLabRequest::with(['emergency.patient', 'labTests', 'payment'])->find($id);
            $typeName = 'تحاليل المختبر';
            $itemsList = $req ? $req->labTests->pluck('name')->join('، ') : '';
            $refundAmount = $req ? (float) $req->labTests->sum('price') : 0;
        } else {
            return redirect()->back()->with('error', 'نوع الفحص غير صالح');
        }

        if (! $req) {
            return redirect()->back()->with('error', 'طلب الفحص غير موجود');
        }

        if ($req->status !== 'cancelled') {
            return redirect()->back()->with('error', 'لا يمكن استرجاع المبلغ لأن الطلب غير ملغي.');
        }

        if ($req->payment_status === 'refunded') {
            return redirect()->back()->with('error', 'تم استرجاع مبلغ هذا الطلب مسبقاً.');
        }

        if (! $req->payment_id) {
            return redirect()->back()->with('error', 'هذا الطلب لم يتم تسديده مسبقاً.');
        }

        $httpRequest->validate([
            'payment_method' => 'required|in:cash,card',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $emergency = $req->emergency;
            $originalPayment = $req->payment ?? ($emergency ? $emergency->payments()->where('amount', '>', 0)->latest()->first() : null);
            $patientId = $emergency?->patient_id ?? $originalPayment?->patient_id;

            $description = "استرجاع مالي للمريض: طلب {$typeName} طوارئ ملغي #{$req->id} ({$itemsList})".
                ($originalPayment ? " (عن الوصل الأصلي #{$originalPayment->receipt_number})" : '');

            // إنشاء سند دفع سالب (Refund)
            $refundPayment = Payment::create([
                'patient_id' => $patientId,
                'cashier_id' => $user->id,
                'emergency_id' => $emergency?->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => -$refundAmount,
                'total_amount' => -$refundAmount,
                'patient_share' => -$refundAmount,
                'insurance_share' => 0,
                'insurance_type' => 'none',
                'copay_percentage' => 0,
                'claim_status' => 'none',
                'payment_method' => $httpRequest->input('payment_method', 'cash'),
                'payment_type' => 'emergency',
                'description' => $description,
                'notes' => $httpRequest->input('notes', 'استرجاع نقدي لفحص طوارئ ملغي'),
                'paid_at' => now(),
            ]);

            $req->update([
                'payment_status' => 'refunded',
                'notes' => trim(($req->notes ?? '')." | تم استرجاع المبلغ بسند #{$refundPayment->receipt_number}"),
            ]);

            DB::commit();

            $successMsg = 'تم صرف واسترجاع مبلغ ('.number_format($refundAmount, 0)." د.ع) للمريض بنجاح، وتوليد سند الاسترجاع رقم #{$refundPayment->receipt_number}.";

            return redirect()->route('cashier.receipt', $refundPayment->id)
                ->with('success', $successMsg)
                ->with('payment_id', $refundPayment->id);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error processing emergency request refund: '.$e->getMessage());

            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة الاسترجاع المالي: '.$e->getMessage());
        }
    }

    /**
     * تقرير كشف مدفوعات الكاشير وحركات الصندوق
     */
    public function paymentsReport(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('view cashier reports'))) {
            abort(403, 'غير مصرح لك بعرض التقارير المالية وكشوفات الصندوق');
        }

        $range = $request->query('range');
        $paymentType = $request->query('payment_type');
        $paymentMethod = $request->query('payment_method');
        $insuranceType = $request->query('insurance_type');
        $cashierId = $request->query('cashier_id');
        $search = $request->query('search');

        // تحديد التواريخ بمرونة مع الحفاظ على الفلاتر أثناء التنقل بين الصفحات
        if ($range === 'all') {
            $fromDate = null;
            $toDate = null;
        } elseif ($range === 'today') {
            $fromDate = now()->startOfDay()->format('Y-m-d');
            $toDate = now()->endOfDay()->format('Y-m-d');
        } elseif ($range === 'yesterday') {
            $fromDate = now()->subDay()->startOfDay()->format('Y-m-d');
            $toDate = now()->subDay()->endOfDay()->format('Y-m-d');
        } elseif ($range === 'this_week') {
            $fromDate = now()->startOfWeek()->format('Y-m-d');
            $toDate = now()->format('Y-m-d');
        } elseif ($range === 'this_month') {
            $fromDate = now()->startOfMonth()->format('Y-m-d');
            $toDate = now()->format('Y-m-d');
        } else {
            // إذا كانت الزيارة الأولى تماماً (بدون أي معايير GET إطلاقاً ولا حتى page)
            if (! $request->has('from_date') && ! $request->has('to_date') && ! $request->has('search') && ! $request->has('payment_type') && ! $request->has('page') && ! $request->has('range')) {
                $fromDate = now()->startOfDay()->format('Y-m-d');
                $toDate = now()->endOfDay()->format('Y-m-d');
                $range = 'today';
            } else {
                $fromDate = $request->query('from_date');
                $toDate = $request->query('to_date');
            }
        }

        $query = \App\Models\Payment::with([
            'patient.user',
            'cashier',
            'appointment.doctor.user',
            'appointment.department',
            'emergency.patient.user',
            'emergency.emergencyPatient',
            'surgery.doctor.user',
            'request.visit.doctor.user',
        ]);

        if ($fromDate) {
            $query->whereDate('paid_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('paid_at', '<=', $toDate);
        }

        if ($paymentType) {
            $query->where('payment_type', $paymentType);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        if ($insuranceType) {
            if ($insuranceType === 'insured') {
                $query->whereIn('insurance_type', ['moi', 'hi']);
            } else {
                $query->where('insurance_type', $insuranceType);
            }
        }

        // كل مستخدم/كاشير يسحب سجله الخاص به تلقائياً (إلا إذا كان مديراً أو محاسباً)
        if (! $user->hasRole(['admin', 'accountant', 'super_admin'])) {
            $query->where('cashier_id', $user->id);
        } elseif ($cashierId) {
            $query->where('cashier_id', $cashierId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('insurance_card_no', 'like', "%{$search}%")
                    ->orWhereHas('patient.user', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('emergency.emergencyPatient', function ($eq) use ($search) {
                        $eq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('emergency.patient.user', function ($epq) use ($search) {
                        $epq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // حساب الإحصائيات قبل التقسيم للصفحات
        $statsQuery = clone $query;
        $allPayments = $statsQuery->get();

        $totalAmount = $allPayments->sum('amount');
        $totalInsuranceShare = $allPayments->sum('insurance_share');
        $totalPatientShare = $allPayments->sum('patient_share');
        $totalCount = $allPayments->count();

        // إحصائيات حسب الأقسام
        $typeBreakdown = $allPayments->groupBy('payment_type')->map(function ($items, $type) {
            return [
                'count' => $items->count(),
                'total' => $items->sum('amount'),
                'label' => \App\Models\Payment::PAYMENT_TYPES[$type] ?? $type,
            ];
        });

        $cashiers = $user->hasRole(['admin', 'accountant', 'super_admin'])
            ? \App\Models\User::whereIn('id', \App\Models\Payment::select('cashier_id')->distinct())->orderBy('name')->get()
            : collect();

        // إذا كان الطلب طباعة
        if ($request->query('print') == '1') {
            $payments = $query->orderBy('paid_at', 'desc')->get();

            return view('cashier.report-print', compact(
                'payments', 'totalAmount', 'totalInsuranceShare', 'totalPatientShare', 'totalCount', 'typeBreakdown',
                'fromDate', 'toDate', 'paymentType', 'paymentMethod', 'insuranceType', 'search', 'range', 'cashierId', 'cashiers'
            ));
        }

        $payments = $query->orderBy('paid_at', 'desc')->paginate(25)->withQueryString();

        return view('cashier.report', compact(
            'payments', 'totalAmount', 'totalInsuranceShare', 'totalPatientShare', 'totalCount', 'typeBreakdown',
            'fromDate', 'toDate', 'paymentType', 'paymentMethod', 'insuranceType', 'search', 'range', 'cashierId', 'cashiers'
        ));
    }

    /**
     * عرض واجهة كشوفات الحسابات
     */
    public function statements(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('view account statements'))) {
            abort(403, 'غير مصرح لك بالوصول إلى كشوفات الحسابات');
        }

        $data = $this->buildStatementData($request);

        return view('cashier.statements', $data);
    }

    public function exportStatements(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('view account statements'))) {
            abort(403, 'غير مصرح لك بتصدير كشوفات الحسابات');
        }

        $data = $this->buildStatementData($request);

        return Excel::download(
            new \App\Exports\CashierStatementsExport(
                $data['revenues'],
                $data['groupedRevenues'],
                $data['monthlyDoctorSummary'],
                $data['monthNames']
            ),
            'cashier_statements_'.now()->format('Ymd_His').'.xlsx'
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

        $monthExpression = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%m', appointments.appointment_date) AS INTEGER) as month_number"
            : 'MONTH(appointments.appointment_date) as month_number';

        $monthlyDoctorRevenues = ConsultationRevenue::selectRaw(
            "consultation_revenues.doctor_id, {$monthExpression}, COUNT(*) as examination_count"
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

        $monthlyDoctorSummary = $monthlyDoctorRevenues->groupBy('doctor_id')->map(function ($items) {
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
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('view cashier surgeries'))) {
            abort(403, 'غير مصرح لك بالوصول إلى كاشير العمليات');
        }

        // جلب العمليات المعلقة (بانتظار الدفع أو دفع جزئي، أو التي تحتوي مبالغ زائدة للاسترجاع) مجمعة حسب المريض
        // جلب العمليات المعلقة (بانتظار الدفع أو دفع جزئي، أو التي تحتوي مبالغ زائدة/ملغاة للاسترجاع) مجمعة حسب المريض
        $pendingSurgeries = Surgery::with([
            'patient.user',
            'doctor.user',
            'department',
            'surgicalOperation',
            'room',
            'labTests.labTest',
            'radiologyTests.radiologyType',
            'visit',
            'additionalOperations',
        ])
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('billing_status', '!=', 'pending_review')
                        ->whereIn('status', ['scheduled', 'waiting', 'in_progress', 'completed'])
                        ->where(function ($subQ) {
                            $subQ->whereIn('payment_status', ['pending', 'partial', 'partially_paid'])
                                ->orWhereRaw('surgery_fee_paid_amount > (surgery_fee + (select COALESCE(sum(fee), 0) from surgery_additional_operations where surgery_additional_operations.surgery_id = surgeries.id))')
                                ->orWhereRaw('room_fee_paid_amount > room_fee');
                        });
                })
                    ->orWhere(function ($q) {
                        $q->where('status', 'cancelled')
                            ->where(function ($subQ) {
                                $subQ->where('surgery_fee_paid_amount', '>', 0)
                                    ->orWhere('room_fee_paid_amount', '>', 0);
                            });
                    });
            })
            ->orderBy('scheduled_date')
            ->get();

        // تجميع العمليات حسب المريض
        $surgeriesByPatient = $pendingSurgeries->groupBy('patient_id');

        // إحصائيات العمليات
        $today = Carbon::today();
        $surgeryStats = [
            'pending_count' => Surgery::where(function ($query) {
                $query->where(function ($q) {
                    $q->where('billing_status', '!=', 'pending_review')
                        ->whereIn('status', ['scheduled', 'waiting', 'in_progress', 'completed'])
                        ->where(function ($subQ) {
                            $subQ->whereIn('payment_status', ['pending', 'partial', 'partially_paid'])
                                ->orWhereRaw('surgery_fee_paid_amount > (surgery_fee + (select COALESCE(sum(fee), 0) from surgery_additional_operations where surgery_additional_operations.surgery_id = surgeries.id))')
                                ->orWhereRaw('room_fee_paid_amount > room_fee');
                        });
                })
                    ->orWhere(function ($q) {
                        $q->where('status', 'cancelled')
                            ->where(function ($subQ) {
                                $subQ->where('surgery_fee_paid_amount', '>', 0)
                                    ->orWhere('room_fee_paid_amount', '>', 0);
                            });
                    });
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
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('view cashier surgeries'))) {
            abort(403, 'غير مصرح لك بالوصول إلى كاشير العمليات');
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
            $query->whereHas('patient.user', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->patient_name.'%');
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
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('process surgery payments'))) {
            abort(403, 'غير مصرح لك بقبض وتثبيت دفعات العمليات الجراحية');
        }

        $isCancelled = $surgery->status === 'cancelled';
        $hasPaidMoneyToRefund = (($surgery->surgery_fee_paid_amount ?? 0) > 0) || (($surgery->room_fee_paid_amount ?? 0) > 0);

        if ($isCancelled && ! $hasPaidMoneyToRefund) {
            return redirect()->route('cashier.surgeries.index')
                ->with('warning', 'لا يمكن دفع عملية ملغاة');
        }

        // التحقق من أن العملية لم يتم دفعها بالكامل (أو تم ترقية/تخفيض الغرفة ويوجد فرق مالي معلق أو مستحق استرجاع)
        $hasRemainingRoomFee = ! $isCancelled && ((($surgery->room_fee ?? 0) - ($surgery->room_fee_paid_amount ?? 0)) > 0);
        $hasExcessRoomFee = (($surgery->room_fee_paid_amount ?? 0) - ($isCancelled ? 0 : ($surgery->room_fee ?? 0))) > 0;
        $totalSurgeryFee = $isCancelled ? 0 : (($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee') + $surgery->medicalDevices->sum('pivot.price'));
        $hasExcessSurgeryFee = (($surgery->surgery_fee_paid_amount ?? 0) - $totalSurgeryFee) > 0;

        if (! $isCancelled && $surgery->payment_status === 'paid' && ! $hasRemainingRoomFee && ! $hasExcessRoomFee && ! $hasExcessSurgeryFee) {
            return redirect()->route('cashier.surgeries.index')
                ->with('warning', 'هذه العملية تم دفعها مسبقاً بالكامل');
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
            'medicalDevices',
        ]);

        // حساب التكلفة الإجمالية
        // use the stored fee on the surgery itself (preserved at creation/edit time)
        $surgeryFee = $surgery->surgery_fee ?? 0;
        $additionalOpsFee = $surgery->additionalOperations->sum('fee');
        $devicesFee = $surgery->medicalDevices->sum('pivot.price');
        $totalSurgeryFee = $surgeryFee + $additionalOpsFee + $devicesFee;

        $labTestsFee = $surgery->labTests->sum(function ($test) {
            return $test->labTest->price ?? 0;
        });
        $radiologyTestsFee = $surgery->radiologyTests->sum(function ($test) {
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
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('process surgery payments'))) {
            abort(403, 'غير مصرح لك بقبض وتثبيت دفعات العمليات الجراحية');
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

        // التحقق من أن العملية لم يتم دفعها بالكامل (أو تم ترقية الغرفة ويوجد فرق مالي)
        $hasRemainingRoomFee = (($surgery->room_fee ?? 0) - ($surgery->room_fee_paid_amount ?? 0)) > 0;
        if ($surgery->payment_status === 'paid' && ! $hasRemainingRoomFee) {
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

        if (! $paySurgery && ! $payRoom && empty($payLabTests) && empty($payRadiologyTests)) {
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
                    $paidItems[] = 'رسوم العملية الجراحية (الأساسية والإضافية)'.($isInclusive ? ' (شاملة)' : '').' - مدفوع: '.number_format($surgeryFeePaidThisTime, 0).' د.ع';
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
                        $paidItems[] = 'أجور الغرفة - مدفوع: '.number_format($roomFeePaidThisTime, 0).' د.ع';
                    }
                }
            }

            $insuranceType = $request->input('insurance_type', $surgery->insurance_type ?? $surgery->patient->insurance_type ?? 'none');
            $patient = $surgery->patient;
            $copayPercentage = ($insuranceType === 'hi' && $patient)
                ? (float) $patient->getCopayPercentageFor('surgery')
                : (float) ($request->input('copay_percentage', $patient->copay_percentage ?? 15.0));
            $isInsurance = ($insuranceType === 'moi' || $insuranceType === 'hi');

            // 3. Lab Tests
            if (! empty($payLabTests)) {
                foreach ($surgery->labTests as $labTest) {
                    if (in_array($labTest->id, $payLabTests) && $labTest->payment_status !== 'paid') {
                        if ($isInclusive) {
                            $paidItems[] = 'تحليل: '.($labTest->labTest->name ?? 'غير محدد').' (مشمول)';
                        } else {
                            $pricing = $labTest->labTest ? $labTest->labTest->calculateInsurancePricing($insuranceType, $copayPercentage) : null;
                            $labTestPrice = $pricing ? (float) $pricing['approved_price'] : (float) ($labTest->labTest->price ?? 0);
                            $actualAmount += $labTestPrice;
                            $paidItems[] = 'تحليل: '.($labTest->labTest->name ?? 'غير محدد').' ('.number_format($labTestPrice, 0).' د.ع)';
                        }
                    }
                }
            }

            // 4. Radiology Tests
            if (! empty($payRadiologyTests)) {
                foreach ($surgery->radiologyTests as $radiologyTest) {
                    if (in_array($radiologyTest->id, $payRadiologyTests) && $radiologyTest->payment_status !== 'paid') {
                        if ($isInclusive) {
                            $paidItems[] = 'أشعة: '.($radiologyTest->radiologyType->name ?? 'غير محدد').' (مشمولة)';
                        } else {
                            $pricing = $radiologyTest->radiologyType ? $radiologyTest->radiologyType->calculateInsurancePricing($insuranceType, $copayPercentage) : null;
                            $radiologyPrice = $pricing ? (float) $pricing['approved_price'] : (float) ($radiologyTest->radiologyType->base_price ?? 0);
                            $actualAmount += $radiologyPrice;
                            $paidItems[] = 'أشعة: '.($radiologyTest->radiologyType->name ?? 'غير محدد').' ('.number_format($radiologyPrice, 0).' د.ع)';
                        }
                    }
                }
            }

            $totalApprovedPayable = $actualAmount;
            $patientShare = $isInsurance ? round(($totalApprovedPayable * $copayPercentage) / 100, 2) : $totalApprovedPayable;
            $insuranceShare = $isInsurance ? max(0, $totalApprovedPayable - $patientShare) : 0.00;

            if ($isInsurance) {
                $paidItems[] = 'تغطية الضمان: '.($insuranceType === 'moi' ? 'قوى الأمن الداخلي' : 'الضمان الصحي').' (نسبة التحمل: '.$copayPercentage.'%)';
                $paidItems[] = 'حصة المريض: '.number_format($patientShare, 0).' د.ع | مطالبة الضمان: '.number_format($insuranceShare, 0).' د.ع';
            }

            // إنشاء وصف الدفع
            $description = 'دفع رسوم العملية الجراحية: '.$surgery->surgery_type.' (ID: #'.$surgery->id.')';
            if ($isInclusive) {
                $description .= "\nملاحظة: الشاملة (التكاليف المسبقة مٌضمَّنة)";
            }
            $description .= "\nالعناصر المدفوعة:\n- ".implode("\n- ", $paidItems);

            // إنشاء سجل الدفع
            $payment = Payment::create([
                'patient_id' => $surgery->patient_id,
                'cashier_id' => $user->id,
                'surgery_id' => $surgery->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => $patientShare,
                'total_amount' => $totalApprovedPayable,
                'patient_share' => $patientShare,
                'insurance_share' => $insuranceShare,
                'insurance_type' => $insuranceType,
                'insurance_card_no' => $request->insurance_card_no ?? $surgery->patient->insurance_card_no ?? null,
                'copay_percentage' => $isInsurance ? $copayPercentage : 0.00,
                'claim_status' => $isInsurance ? 'pending' : 'none',
                'payment_method' => $request->payment_method,
                'payment_type' => 'surgery',
                'description' => $description,
                'notes' => $request->notes,
                'is_inclusive' => $isInclusive,
                'paid_at' => Carbon::now(),
            ]);

            // تحديث مبالغ وحالة رسوم العملية
            if ($paySurgery && $surgeryFeePaidThisTime > 0) {
                $totalSurgeryFee = ($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee') + $surgery->medicalDevices->sum('pivot.price');
                $newSurgeryFeePaidAmount = ($surgery->surgery_fee_paid_amount ?? 0) + $surgeryFeePaidThisTime;
                $surgeryFeePaidStatus = $newSurgeryFeePaidAmount >= $totalSurgeryFee ? 'paid' : 'partial';

                $surgery->update([
                    'surgery_fee_paid_amount' => $newSurgeryFeePaidAmount,
                    'surgery_fee_paid' => $surgeryFeePaidStatus,
                ]);
            }

            // تحديث مبالغ وحالة رسوم الغرفة
            if ($payRoom && $roomFeePaidThisTime > 0) {
                $newRoomFeePaidAmount = ($surgery->room_fee_paid_amount ?? 0) + $roomFeePaidThisTime;
                $surgery->update([
                    'room_fee_paid_amount' => $newRoomFeePaidAmount,
                ]);
            }

            // تحديث حالة التحاليل المدفوعة
            if (! empty($payLabTests)) {
                foreach ($surgery->labTests as $labTest) {
                    if (in_array($labTest->id, $payLabTests) && $labTest->payment_status !== 'paid') {
                        $labTest->update([
                            'payment_status' => 'paid',
                            'payment_id' => $payment->id,
                        ]);
                    }
                }
            }

            // تحديث حالة الأشعة المدفوعة
            if (! empty($payRadiologyTests)) {
                foreach ($surgery->radiologyTests as $radiologyTest) {
                    if (in_array($radiologyTest->id, $payRadiologyTests) && $radiologyTest->payment_status !== 'paid') {
                        $radiologyTest->update([
                            'payment_status' => 'paid',
                            'payment_id' => $payment->id,
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
                'payment_id' => $payment->id,
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
            $successMessage .= ' رقم الإيصال: '.$payment->receipt_number;

            return redirect()->route('cashier.surgeries.index')
                ->with('success', $successMessage)
                ->with('payment_id', $payment->id);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء معالجة الدفع: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * معالجة إرجاع المبالغ الزائدة للعملية الجراحية (Refund)
     */
    public function processSurgeryRefund(Request $request, Surgery $surgery)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('process surgery payments') && ! $user->can('process refunds')))) {
            abort(403, 'غير مصرح لك بمعالجة استرجاع مبالغ العمليات');
        }

        $isCancelled = $surgery->status === 'cancelled';

        if ($isCancelled) {
            // للمريض: استرجاع المبلغ النقدي الفعلي الذي دفعه المريض فقط (حصة المريض وليس كامل سعر الضمان)
            $netPatientPaidCash = (float) $surgery->payments()->sum('amount');
            $totalExcessAmount = $netPatientPaidCash > 0
                ? $netPatientPaidCash
                : (($surgery->surgery_fee_paid_amount ?? 0) + ($surgery->room_fee_paid_amount ?? 0));
            $excessSurgeryFee = $totalExcessAmount;
            $excessRoomFee = 0;

            // إلغاء أي مطالبات ضمان معلقة على هذه العملية
            $totalInsuranceClaim = (float) $surgery->payments()->where('amount', '>', 0)->sum('insurance_share');
            $surgery->payments()->where('claim_status', 'pending')->update(['claim_status' => 'cancelled']);
        } else {
            $totalSurgeryFee = ($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee');
            $surgeryFeePaidAmount = $surgery->surgery_fee_paid_amount ?? 0;
            $excessSurgeryFee = max(0, $surgeryFeePaidAmount - $totalSurgeryFee);

            $roomFee = $surgery->room_fee ?? 0;
            $roomFeePaidAmount = $surgery->room_fee_paid_amount ?? 0;
            $excessRoomFee = max(0, $roomFeePaidAmount - $roomFee);

            $totalExcessAmount = $excessSurgeryFee + $excessRoomFee;
            $totalInsuranceClaim = 0;
        }

        if ($totalExcessAmount <= 0) {
            return redirect()->back()->with('error', 'لا يوجد مبلغ زائد للاسترجاع.');
        }

        $request->validate([
            'payment_method' => 'required|in:cash,card',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $isInsurance = ($surgery->insurance_type && $surgery->insurance_type !== 'none');
            // إنشاء وصف الاسترجاع
            $description = ($isCancelled ? 'إرجاع المبلغ النقدي الفعلي المسدد لعملية ملغاة: ' : 'إرجاع مبلغ زائد مدفوع للعملية الجراحية: ').
                           $surgery->surgery_type.' (ID: #'.$surgery->id.")\n".
                           'المبلغ المعاد للمريض: '.number_format($totalExcessAmount, 0).' د.ع'.
                           ($isInsurance ? ' (تغطية الضمان: '.($surgery->insurance_type === 'moi' ? 'قوى الأمن الداخلي' : 'الضمان الصحي').' - تم إلغاء المطالبة)' : '').
                           ($excessRoomFee > 0 ? ' (يشمل استرجاع رسوم الغرفة: '.number_format($excessRoomFee, 0).' د.ع)' : '');

            // إنشاء سجل الدفع بقيمة سالبة
            $payment = Payment::create([
                'patient_id' => $surgery->patient_id,
                'cashier_id' => $user->id,
                'surgery_id' => $surgery->id,
                'receipt_number' => Payment::generateReceiptNumber(),
                'amount' => -$totalExcessAmount,
                'total_amount' => -$totalExcessAmount,
                'patient_share' => -$totalExcessAmount,
                'insurance_share' => -$totalInsuranceClaim,
                'insurance_type' => $surgery->insurance_type ?? 'none',
                'copay_percentage' => 0,
                'claim_status' => 'cancelled',
                'payment_method' => $request->payment_method,
                'payment_type' => 'surgery',
                'description' => $description,
                'notes' => $request->notes,
                'is_inclusive' => false,
                'paid_at' => Carbon::now(),
            ]);

            if ($isCancelled) {
                // تصفير المبالغ المدفوعة وتعيين الحالة كمسترجعة بالكامل
                $surgery->update([
                    'surgery_fee_paid_amount' => 0,
                    'room_fee_paid_amount' => 0,
                    'surgery_fee_paid' => 'refunded',
                    'payment_status' => 'refunded',
                ]);
            } else {
                // تحديث مبالغ الدفع وحالتها في الجراحة
                $surgery->update([
                    'surgery_fee_paid_amount' => $totalSurgeryFee,
                    'surgery_fee_paid' => 'paid',
                    'room_fee_paid_amount' => $roomFee,
                ]);

                // التحقق من الدفع الكامل لكامل البنود (الغرفة، التحاليل، الأشعة)
                $allRoomFeePaid = ($surgery->room_fee_paid_amount ?? 0) >= ($surgery->room_fee ?? 0);
                $allLabTestsPaid = $surgery->labTests->where('payment_status', '!=', 'paid')->count() === 0;
                $allRadiologyTestsPaid = $surgery->radiologyTests->where('payment_status', '!=', 'paid')->count() === 0;
                $allPaid = $allRoomFeePaid && $allLabTestsPaid && $allRadiologyTestsPaid;

                $surgery->update([
                    'payment_status' => $allPaid ? 'paid' : 'partial',
                ]);
            }

            DB::commit();

            return redirect()->route('cashier.surgeries.index')
                ->with('success', 'تم إرجاع المبلغ بنجاح بقيمة '.number_format($totalExcessAmount, 0).' د.ع. رقم الإيصال: '.$payment->receipt_number);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة المرتجع: '.$e->getMessage());
        }
    }

    /**
     * عرض نموذج دفع خدمات الطوارئ
     */
    public function showEmergencyPaymentForm(Payment $payment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('process emergency payments'))) {
            abort(403, 'غير مصرح لك بقبض رسوم الطوارئ');
        }

        // التحقق من أن الدفعة تخص خدمات طوارئ
        if ($payment->payment_type !== 'emergency' || $payment->appointment_id !== null) {
            return redirect()->route('cashier.index')
                ->with('warning', 'هذه الدفعة غير متاحة للدفع');
        }

        // إذا كانت الدفعة مسددة بالفعل، يتم التوجيه مباشرة إلى الوصل الرسمي
        if ($payment->paid_at !== null) {
            return redirect()->route('cashier.receipt', $payment)
                ->with('info', 'هذه الدفعة مسددة مسبقاً، تم فتح الوصل الرسمي');
        }

        $payment->load([
            'emergency.patient.user',
            'emergency.patient.healthInsuranceCategory',
            'emergency.services',
            'emergency.labRequests.labTests',
            'emergency.radiologyRequests.radiologyTypes',
            'emergency.appointments.doctor.user',
            'emergency.appointments.department',
            'appointment.doctor.user',
        ]);

        return view('cashier.emergency-payment-form', compact('payment'));
    }

    /**
     * معالجة دفع خدمات الطوارئ
     */
    public function processEmergencyPayment(Request $request, Payment $payment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('process emergency payments'))) {
            abort(403, 'غير مصرح لك بقبض رسوم الطوارئ');
        }

        $request->validate([
            'payment_method' => 'required|in:cash,card,insurance',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        // التحقق من أن الدفعة تخص خدمات طوارئ ولم يتم دفعها بعد
        if ($payment->payment_type !== 'emergency' || $payment->paid_at !== null || $payment->appointment_id !== null) {
            return redirect()->route('cashier.index')
                ->with('warning', 'هذه الدفعة غير متاحة للدفع');
        }

        DB::beginTransaction();

        try {
            $patient = $payment->patient ?? optional($payment->emergency)->patient;
            $insuranceType = $request->input('insurance_type', $payment->insurance_type ?? $patient->insurance_type ?? 'none');

            // جلب نسبة التحمل الرسمية لفئة المريض تلقائياً وحمايتها
            $copayPercentage = ($insuranceType === 'hi' && $patient)
                ? (float) $patient->getCopayPercentageFor('emergency')
                : (float) $request->input('copay_percentage', $payment->copay_percentage ?? $patient->copay_percentage ?? 0);

            $cardNo = $request->input('insurance_card_no', $payment->insurance_card_no ?? $patient->insurance_card_no ?? $patient->insurance_booklet_number ?? null);

            $patientPaidAmount = (float) $request->amount;
            $totalApproved = ($payment->total_amount && $payment->total_amount > 0) ? (float) $payment->total_amount : $patientPaidAmount;

            if ($insuranceType !== 'none') {
                $patientShare = $patientPaidAmount;
                $insuranceShare = max(0, $totalApproved - $patientShare);
            } else {
                $patientShare = $patientPaidAmount;
                $insuranceShare = 0;
            }
            $claimStatus = ($insuranceShare > 0) ? 'pending' : 'none';

            // تحديث الدفعة
            $payment->update([
                'payment_method' => $request->payment_method,
                'amount' => $patientPaidAmount,
                'total_amount' => $totalApproved,
                'patient_share' => $patientShare,
                'insurance_share' => $insuranceShare,
                'insurance_type' => $insuranceType,
                'insurance_card_no' => $cardNo,
                'copay_percentage' => ($insuranceType !== 'none') ? $copayPercentage : 0,
                'claim_status' => $claimStatus,
                'paid_at' => now(),
                'cashier_id' => $user->id,
                'receipt_number' => $payment->receipt_number ?: Payment::generateReceiptNumber(),
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
                ->update([
                    'payment_id' => $payment->id,
                    'payment_status' => 'paid',
                ]);

            // 3. طلبات الأشعة
            \DB::table('emergency_radiology_requests')
                ->where('emergency_id', $payment->emergency_id)
                ->whereNull('payment_id')
                ->update([
                    'payment_id' => $payment->id,
                    'payment_status' => 'paid',
                ]);

            // 4. رسوم المتابعة
            if ($payment->emergency->doctor_follow_up_fee > 0 && ! $payment->emergency->follow_up_payment_id) {
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

            // تحديث جميع المواعيد المرتبطة بحالة الطوارئ إلى مدفوعة واحتساب حصص الأطباء الاستشاريين
            $emergencyAppointments = $payment->emergency->appointments()
                ->where('payment_status', 'pending')
                ->where('status', '!=', 'cancelled')
                ->get();

            foreach ($emergencyAppointments as $appointment) {
                $appointment->update([
                    'payment_status' => 'paid',
                    'payment_id' => $payment->id,
                ]);

                if ($appointment->doctor_id) {
                    $fee = (float) ($appointment->consultation_fee > 0 ? $appointment->consultation_fee : 30000);
                    // حصة استشارة الطوارئ: 24,000 د.ع للطبيب و 6,000 د.ع للمستشفى (نسبة 80% / 20%)
                    $doctorShare = ($fee == 30000.0) ? 24000.0 : round($fee * 0.80, 2);
                    $hospitalShare = round($fee - $doctorShare, 2);
                    $percentage = round(($doctorShare / $fee) * 100, 2);

                    // قيد إيراد استشارية في ConsultationRevenue
                    \App\Models\ConsultationRevenue::updateOrCreate(
                        [
                            'appointment_id' => $appointment->id,
                        ],
                        [
                            'payment_id' => $payment->id,
                            'receipt_number' => $payment->receipt_number,
                            'payment_method' => $payment->payment_method ?? 'cash',
                            'payment_type' => 'emergency_consultation',
                            'cashier_id' => $user->id,
                            'patient_id' => $appointment->patient_id,
                            'doctor_id' => $appointment->doctor_id,
                            'department_id' => $appointment->department_id,
                            'service_type_id' => null,
                            'examination_count' => 1,
                            'total_amount' => $fee,
                            'doctor_share' => $doctorShare,
                            'hospital_share' => $hospitalShare,
                            'doctor_percentage' => $percentage,
                            'movement_type' => 'payment',
                            'revenue_date' => ($payment->paid_at ?? now())->toDateString(),
                            'paid_at' => $payment->paid_at ?? now(),
                            'notes' => 'استشارة طوارئ لحالة #'.$payment->emergency_id.($appointment->reason ? ' ('.$appointment->reason.')' : ''),
                            'transaction_reference' => $payment->receipt_number,
                        ]
                    );

                    // قيد مستحقات الطبيب في DoctorDue
                    \App\Models\DoctorDue::updateOrCreate(
                        [
                            'doctor_id' => $appointment->doctor_id,
                            'payment_id' => $payment->id,
                        ],
                        [
                            'amount' => $doctorShare,
                            'status' => 'pending',
                            'notes' => 'مستحقات استشارة طوارئ لحالة #'.$payment->emergency_id.' (إيصال '.$payment->receipt_number.')',
                        ]
                    );

                    // مزامنة وتحديث رصيد حساب الطبيب
                    $doctorAccount = \App\Models\DoctorFinancialAccount::firstOrCreate(
                        ['doctor_id' => $appointment->doctor_id],
                        ['balance' => 0, 'total_earned' => 0, 'total_paid' => 0]
                    );
                    $totalEarned = (float) \App\Models\DoctorDue::where('doctor_id', $appointment->doctor_id)->sum('amount');
                    $totalPaid = (float) ($doctorAccount->total_paid ?? 0);
                    $doctorAccount->total_earned = $totalEarned;
                    $doctorAccount->balance = round($totalEarned - $totalPaid, 2);
                    $doctorAccount->last_paid_at = $payment->paid_at ?? now();
                    $doctorAccount->save();
                }
            }

            DB::commit();

            return redirect()->route('cashier.receipt', $payment)
                ->with('success', 'تم تسديد خدمات الطوارئ بنجاح! رقم الإيصال: '.$payment->receipt_number);

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء معالجة الدفع: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض الحركات المالية لقسم الطوارئ
     */
    public function emergencyFinancialMovements(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('view emergency financial movements'))) {
            abort(403, 'غير مصرح لك بالوصول إلى الحركات المالية للطوارئ');
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
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('view emergency statements'))) {
            abort(403, 'غير مصرح لك بالوصول إلى كشوفات حسابات الطوارئ');
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
                    ->sum(\DB::raw('emergency_services.price * COALESCE(emergency_emergency_service.quantity, 1)'));

                // Labs
                $labRequestIds = \DB::table('emergency_lab_requests')
                    ->where('payment_id', $payment->id)
                    ->pluck('id');
                if ($labRequestIds->isNotEmpty()) {
                    $labsSum = \DB::table('emergency_lab_request_tests')
                        ->join('lab_tests', 'emergency_lab_request_tests.lab_test_id', '=', 'lab_tests.id')
                        ->whereIn('emergency_lab_request_tests.emergency_lab_request_id', $labRequestIds)
                        ->sum('lab_tests.price');
                }

                // Radiology
                $radiologyRequestIds = \DB::table('emergency_radiology_requests')
                    ->where('payment_id', $payment->id)
                    ->pluck('id');
                if ($radiologyRequestIds->isNotEmpty()) {
                    $radiologySum = \DB::table('emergency_radiology_request_types')
                        ->join('radiology_types', 'emergency_radiology_request_types.radiology_type_id', '=', 'radiology_types.id')
                        ->whereIn('emergency_radiology_request_types.emergency_radiology_request_id', $radiologyRequestIds)
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

        // حساب الملخصات المجمعة حسب الطبيب والملخص الشهري
        $groupedRevenues = [];
        $monthlyDoctorRevenues = [];

        foreach ($statements as $stmt) {
            $payment = $stmt['payment'];
            if ($payment->emergency && $payment->emergency->doctor_id) {
                $doctorId = $payment->emergency->doctor_id;
                $doctor = \App\Models\Doctor::with('user')->find($doctorId);
                if (! $doctor) {
                    continue;
                }

                $paidAt = $payment->paid_at;
                $month = (int) $paidAt->format('m');

                // ملخص حسب الطبيب
                if (! isset($groupedRevenues[$doctorId])) {
                    $groupedRevenues[$doctorId] = [
                        'doctor' => $doctor,
                        'first_appointment_date' => $paidAt,
                        'examination_count' => 0,
                        'total_amount' => 0,
                        'doctor_share' => 0,
                        'hospital_share' => 0,
                    ];
                }

                $groupedRevenues[$doctorId]['examination_count']++;
                $groupedRevenues[$doctorId]['total_amount'] += $stmt['total'];
                $groupedRevenues[$doctorId]['doctor_share'] += $stmt['follow_up_sum'];
                $groupedRevenues[$doctorId]['hospital_share'] += ($stmt['total'] - $stmt['follow_up_sum']);

                if ($paidAt->lt($groupedRevenues[$doctorId]['first_appointment_date'])) {
                    $groupedRevenues[$doctorId]['first_appointment_date'] = $paidAt;
                }

                // الكشف الشهري المجمع
                if (! isset($monthlyDoctorRevenues[$doctorId])) {
                    $monthlyDoctorRevenues[$doctorId] = [];
                }
                if (! isset($monthlyDoctorRevenues[$doctorId][$month])) {
                    $monthlyDoctorRevenues[$doctorId][$month] = 0;
                }
                $monthlyDoctorRevenues[$doctorId][$month]++;
            }
        }

        // تحويل المصفوفة لـ Collection وكائنات متوافقة مع الواجهة
        $groupedRevenues = collect($groupedRevenues)->sortByDesc('examination_count')->map(function ($item) {
            return (object) $item;
        });

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

        $monthlyDoctorSummary = collect();
        foreach ($monthlyDoctorRevenues as $doctorId => $monthsData) {
            $doctor = \App\Models\Doctor::with('user')->find($doctorId);
            $months = array_fill(1, 12, 0);
            foreach ($monthsData as $m => $cnt) {
                $months[$m] = $cnt;
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

            $monthlyDoctorSummary->push((object) [
                'doctor' => $doctor,
                'months' => $months,
                'month_trends' => $monthTrends,
                'total' => array_sum($months),
                'first_month_count' => $firstMonthCount,
                'last_month_count' => $lastMonthCount,
                'percent_change' => $percent,
            ]);
        }

        $monthlyTotals = array_fill(1, 12, 0);
        foreach ($monthlyDoctorSummary as $row) {
            foreach ($row->months as $month => $count) {
                $monthlyTotals[$month] += $count;
            }
        }

        // جلب إحصائيات الحالات المحولة للعمليات حسب الطبيب
        $surgeryReferrals = \App\Models\Emergency::selectRaw('doctor_id, COUNT(*) as total_cases, SUM(CASE WHEN requires_surgery = 1 THEN 1 ELSE 0 END) as referred_count')
            ->whereNotNull('doctor_id')
            ->when($fromDate, function ($q) use ($fromDate) {
                $q->whereDate('created_at', '>=', $fromDate);
            })
            ->when($toDate, function ($q) use ($toDate) {
                $q->whereDate('created_at', '<=', $toDate);
            })
            ->groupBy('doctor_id')
            ->with('doctor.user')
            ->get();

        // إحصائيات تحويلات الفحوصات والتحاليل حسب الطبيب
        $diagnosticReferrals = \App\Models\Emergency::select('doctor_id')
            ->whereNotNull('doctor_id')
            ->when($fromDate, function ($q) use ($fromDate) {
                $q->whereDate('created_at', '>=', $fromDate);
            })
            ->when($toDate, function ($q) use ($toDate) {
                $q->whereDate('created_at', '<=', $toDate);
            })
            ->groupBy('doctor_id')
            ->get()
            ->map(function ($emergencyGroup) use ($fromDate, $toDate) {
                $doctorId = $emergencyGroup->doctor_id;
                $doctor = \App\Models\Doctor::with('user')->find($doctorId);

                $totalCases = \App\Models\Emergency::where('doctor_id', $doctorId)
                    ->when($fromDate, function ($q) use ($fromDate) {
                        $q->whereDate('created_at', '>=', $fromDate);
                    })
                    ->when($toDate, function ($q) use ($toDate) {
                        $q->whereDate('created_at', '<=', $toDate);
                    })
                    ->count();

                $labCount = \App\Models\Emergency::where('doctor_id', $doctorId)
                    ->whereHas('labRequests')
                    ->when($fromDate, function ($q) use ($fromDate) {
                        $q->whereDate('created_at', '>=', $fromDate);
                    })
                    ->when($toDate, function ($q) use ($toDate) {
                        $q->whereDate('created_at', '<=', $toDate);
                    })
                    ->count();

                $radiologyCount = \App\Models\Emergency::where('doctor_id', $doctorId)
                    ->whereHas('radiologyRequests.radiologyTypes', function ($q) {
                        $q->where('main_category', 'أشعة');
                    })
                    ->when($fromDate, function ($q) use ($fromDate) {
                        $q->whereDate('created_at', '>=', $fromDate);
                    })
                    ->when($toDate, function ($q) use ($toDate) {
                        $q->whereDate('created_at', '<=', $toDate);
                    })
                    ->count();

                $sonarCount = \App\Models\Emergency::where('doctor_id', $doctorId)
                    ->whereHas('radiologyRequests.radiologyTypes', function ($q) {
                        $q->where('main_category', 'السونار');
                    })
                    ->when($fromDate, function ($q) use ($fromDate) {
                        $q->whereDate('created_at', '>=', $fromDate);
                    })
                    ->when($toDate, function ($q) use ($toDate) {
                        $q->whereDate('created_at', '<=', $toDate);
                    })
                    ->count();

                $echoCount = \App\Models\Emergency::where('doctor_id', $doctorId)
                    ->whereHas('radiologyRequests.radiologyTypes', function ($q) {
                        $q->where('main_category', 'الإيكو');
                    })
                    ->when($fromDate, function ($q) use ($fromDate) {
                        $q->whereDate('created_at', '>=', $fromDate);
                    })
                    ->when($toDate, function ($q) use ($toDate) {
                        $q->whereDate('created_at', '<=', $toDate);
                    })
                    ->count();

                $mriCount = \App\Models\Emergency::where('doctor_id', $doctorId)
                    ->whereHas('radiologyRequests.radiologyTypes', function ($q) {
                        $q->where('main_category', 'الرنين');
                    })
                    ->when($fromDate, function ($q) use ($fromDate) {
                        $q->whereDate('created_at', '>=', $fromDate);
                    })
                    ->when($toDate, function ($q) use ($toDate) {
                        $q->whereDate('created_at', '<=', $toDate);
                    })
                    ->count();

                return (object) [
                    'doctor' => $doctor,
                    'doctor_id' => $doctorId,
                    'total_cases' => $totalCases,
                    'lab_count' => $labCount,
                    'radiology_count' => $radiologyCount,
                    'sonar_count' => $sonarCount,
                    'echo_count' => $echoCount,
                    'mri_count' => $mriCount,
                ];
            })->filter(function ($item) {
                return $item->doctor !== null;
            });

        $totals = [
            'grouped_count' => $groupedRevenues->count(),
            'grouped_total_amount' => $groupedRevenues->sum('total_amount'),
            'grouped_total_doctor_share' => $groupedRevenues->sum('doctor_share'),
            'grouped_total_hospital_share' => $groupedRevenues->sum('hospital_share'),
            'monthly_grouped_count' => $monthlyDoctorSummary->count(),
            'monthly_examination_count' => $groupedRevenues->sum('examination_count'),
            'total_referred_surgeries' => $surgeryReferrals->sum('referred_count'),
        ];

        return view('cashier.emergency-statements', compact(
            'paginatedStatements',
            'totalServices',
            'totalLabs',
            'totalRadiology',
            'totalFollowUps',
            'totalCollected',
            'fromDate',
            'toDate',
            'groupedRevenues',
            'monthlyDoctorSummary',
            'monthNames',
            'monthlyTotals',
            'totals',
            'surgeryReferrals',
            'diagnosticReferrals'
        ));
    }

    /**
     * عرض تقرير الحالات المحولة لطبيب طوارئ معين في فترة محددة بالفئات
     */
    public function emergencyReferrals(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('view emergency statements'))) {
            abort(403, 'غير مصرح لك بالوصول إلى تقرير إحالات الطوارئ');
        }

        $doctorId = $request->query('doctor_id');
        $type = $request->query('type'); // 'lab', 'radiology', 'sonar', 'echo', 'mri', 'surgery'
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $doctor = \App\Models\Doctor::with('user')->find($doctorId);
        if (! $doctor) {
            abort(404, 'الطبيب غير موجود');
        }

        $emergencies = \App\Models\Emergency::with(['patient.user'])
            ->where('doctor_id', $doctorId)
            ->when($fromDate, function ($q) use ($fromDate) {
                $q->whereDate('created_at', '>=', $fromDate);
            })
            ->when($toDate, function ($q) use ($toDate) {
                $q->whereDate('created_at', '<=', $toDate);
            });

        $details = [];
        $totalAmount = 0;

        if ($type === 'lab') {
            $emergencies->whereHas('labRequests');
            $records = $emergencies->get();
            foreach ($records as $em) {
                $items = [];
                $caseSum = 0;
                foreach ($em->labRequests as $req) {
                    $labTests = \DB::table('emergency_lab_request_tests')
                        ->join('lab_tests', 'emergency_lab_request_tests.lab_test_id', '=', 'lab_tests.id')
                        ->where('emergency_lab_request_tests.emergency_lab_request_id', $req->id)
                        ->select('lab_tests.name', 'lab_tests.price')
                        ->get();
                    foreach ($labTests as $t) {
                        $items[] = $t->name.' ('.number_format($t->price, 0).' د.ع)';
                        $caseSum += $t->price;
                    }
                }
                $details[] = (object) [
                    'patient_name' => optional($em->patient->user)->name ?? 'غير معروف',
                    'date' => $em->created_at->format('Y-m-d H:i'),
                    'items' => implode(', ', $items),
                    'amount' => $caseSum,
                ];
                $totalAmount += $caseSum;
            }
        } elseif (in_array($type, ['radiology', 'sonar', 'echo', 'mri'])) {
            $categoryMap = [
                'radiology' => 'أشعة',
                'sonar' => 'السونار',
                'echo' => 'الإيكو',
                'mri' => 'الرنين',
            ];
            $dbCategory = $categoryMap[$type];

            $emergencies->whereHas('radiologyRequests.radiologyTypes', function ($q) use ($dbCategory) {
                $q->where('main_category', $dbCategory);
            });

            $records = $emergencies->get();
            foreach ($records as $em) {
                $items = [];
                $caseSum = 0;
                foreach ($em->radiologyRequests as $req) {
                    $radiologyTypes = \DB::table('emergency_radiology_request_types')
                        ->join('radiology_types', 'emergency_radiology_request_types.radiology_type_id', '=', 'radiology_types.id')
                        ->where('emergency_radiology_request_types.emergency_radiology_request_id', $req->id)
                        ->where('radiology_types.main_category', $dbCategory)
                        ->select('radiology_types.name', 'radiology_types.base_price')
                        ->get();
                    foreach ($radiologyTypes as $r) {
                        $items[] = $r->name.' ('.number_format($r->base_price, 0).' د.ع)';
                        $caseSum += $r->base_price;
                    }
                }
                $details[] = (object) [
                    'patient_name' => optional($em->patient->user)->name ?? 'غير معروف',
                    'date' => $em->created_at->format('Y-m-d H:i'),
                    'items' => implode(', ', $items),
                    'amount' => $caseSum,
                ];
                $totalAmount += $caseSum;
            }
        } elseif ($type === 'surgery') {
            $emergencies->where('requires_surgery', true);
            $records = $emergencies->get();
            foreach ($records as $em) {
                $surgery = \App\Models\Surgery::where('patient_id', $em->patient_id)
                    ->whereDate('created_at', '>=', $em->created_at->toDateString())
                    ->first();

                $caseSum = $surgery ? (($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee')) : 0;
                $items = $surgery ? $surgery->surgery_type : 'تحويل للعمليات (قيد الانتظار)';

                $details[] = (object) [
                    'patient_name' => optional($em->patient->user)->name ?? 'غير معروف',
                    'date' => $em->created_at->format('Y-m-d H:i'),
                    'items' => $items,
                    'amount' => $caseSum,
                ];
                $totalAmount += $caseSum;
            }
        }

        $typeLabel = ($type === 'lab' ? 'تحاليل المختبر' : ($type === 'radiology' ? 'الأشعة' : ($type === 'sonar' ? 'السونار' : ($type === 'echo' ? 'الإيكو' : ($type === 'mri' ? 'المفراس والرنين' : 'العمليات الجراحية')))));

        return view('cashier.emergency-referrals', compact(
            'doctor',
            'type',
            'typeLabel',
            'fromDate',
            'toDate',
            'details',
            'totalAmount'
        ));
    }

    /**
     * حسابات أطباء الطوارئ
     */
    public function emergencyDoctorAccounts(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view emergency doctor accounts') && ! $user->can('view doctor profits')))) {
            abort(403, 'غير مصرح لك بالوصول إلى حسابات أطباء الطوارئ');
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
                ->whereHas('followUpPayment', function ($q) {
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
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view emergency doctor accounts') && ! $user->can('view doctor profits')))) {
            abort(403, 'غير مصرح لك بالوصول إلى تفاصيل حساب طبيب الطوارئ');
        }

        $doctor->load(['user', 'department']);

        $earnedCases = \App\Models\Emergency::with(['patient.user', 'followUpPayment'])
            ->where('doctor_id', $doctor->id)
            ->whereNotNull('follow_up_payment_id')
            ->whereHas('followUpPayment', function ($q) {
                $q->whereNotNull('paid_at');
            })
            ->where('doctor_follow_up_fee', '>', 0)
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'cases_page');

        $totalEarned = \App\Models\Emergency::where('doctor_id', $doctor->id)
            ->whereNotNull('follow_up_payment_id')
            ->whereHas('followUpPayment', function ($q) {
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
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view emergency doctor accounts') && ! $user->can('view doctor profits')))) {
            abort(403, 'غير مصرح لك بصرف مستحقات طبيب الطوارئ');
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = $request->amount;

        $totalEarned = \App\Models\Emergency::where('doctor_id', $doctor->id)
            ->whereNotNull('follow_up_payment_id')
            ->whereHas('followUpPayment', function ($q) {
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

            $voucherNumber = 'EXP-EMG-DOC-'.now()->format('ymd').'-'.rand(1000, 9999);
            \App\Models\FinancialTransaction::create([
                'transaction_type' => 'expense',
                'voucher_type' => 'outflow',
                'category' => 'doctor_payout',
                'voucher_number' => $voucherNumber,
                'related_type' => \App\Models\Doctor::class,
                'related_id' => $doctor->id,
                'amount' => $amount,
                'currency' => 'IQD',
                'payment_method' => 'cash',
                'description' => 'صرف مستحقات طوارئ للطبيب '.optional($doctor->user)->name,
                'notes' => 'سند صرف نقدي - طوارئ: '.($request->notes ?: 'صرف مستحقات أطباء الطوارئ'),
                'performed_by_id' => $user->id,
                'performed_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('cashier.emergency.doctor-account', $doctor)->with('success', 'تم تسجيل دفعة الصرف للطبيب بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'حدث خطأ أثناء الصرف: '.$e->getMessage());
        }
    }
}
