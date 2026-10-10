<?php

namespace App\Http\Controllers;

use App\Exports\ConsultantFinancialMovementsExport;
use App\Exports\DoctorPaymentsDuesExport;
use App\Models\ConsultationRevenue;
use App\Models\Doctor;
use App\Models\DoctorDue;
use App\Models\DoctorFinancialAccount;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ConsultantAvailabilityController extends Controller
{
    public function __construct()
    {
        // تطبيق middleware للوظائف العادية فقط، وليس للـ API
        $this->middleware(function ($request, $next) {
            // تجاهل التحقق للـ API endpoints
            if ($request->is('api/*')) {
                return $next($request);
            }
            
            $user = auth()->user();
            $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
            $allowedPerms = [
                'manage consultant availability',
                'view cashier reports',
                'view consultant financial movements',
                'view account statements',
                'view doctor accounts',
                'view doctor profits',
                'view appointments'
            ];
            $hasPerm = false;
            if ($user) {
                foreach ($allowedPerms as $p) {
                    if ($user->can($p)) {
                        $hasPerm = true;
                        break;
                    }
                }
            }
            if (!$isAdmin && (!$user || !$hasPerm)) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
            }
            return $next($request);
        });
    }

    /**
     * عرض قائمة الأطباء الاستشاريين وتوفرهم اليومي
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || (!$user->can('manage consultant availability') && !$user->can('view appointments')))) {
            abort(403, 'غير مصرح لك بعرض جدول توفر الاستشاريين');
        }

        $weekDays = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];

        $daysMap = [
            'Saturday' => 'السبت',
            'Sunday' => 'الأحد',
            'Monday' => 'الإثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
        ];

        $defaultDay = $daysMap[date('l')] ?? 'السبت';

        $selectedDay = $request->query('day', $defaultDay);
        if (!in_array($selectedDay, $weekDays)) {
            $selectedDay = $defaultDay;
        }

        $consultantDoctors = Doctor::with(['user', 'department'])
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->where(function($q) {
                $q->where('doctors.type', 'consultant')
                  ->orWhere('doctors.type', 'sonar')
                  ->orWhere('doctors.specialization', 'LIKE', '%سونار%');
            })
            ->where('doctors.is_active', true)
            ->orderBy('doctors.specialization')
            ->orderBy('users.name')
            ->select('doctors.*')
            ->get();

        // 1. جلب جميع الزيارات النشطة اليوم للأطباء الاستشاريين (المرضى الموجودين داخل غرفة الفحص حالياً)
        $today = today();
        $activeVisits = \App\Models\Visit::with(['patient.user', 'appointment'])
            ->whereDate('visit_date', $today)
            ->where('status', 'in_progress')
            ->get()
            ->groupBy('doctor_id');

        // 2. جلب المواعيد قيد الاستدعاء أو الكشف
        $inConsultationAppointments = \App\Models\Appointment::with('patient.user')
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['calling', 'in_consultation'])
            ->get()
            ->groupBy('doctor_id');

        // 3. جلب عدد المنتظرين لكل طبيب (الذين دفعوا ولم يدخلوا بعد)
        $waitingCounts = \App\Models\Appointment::whereDate('appointment_date', $today)
            ->where(function($q) {
                $q->where('payment_status', 'paid')
                  ->orWhereNotNull('emergency_id');
            })
            ->where(function($q) {
                $q->whereDoesntHave('visit')
                  ->orWhereHas('visit', function($vq) {
                      $vq->whereNotIn('status', ['completed', 'cancelled']);
                  });
            })
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->selectRaw('doctor_id, count(*) as count')
            ->groupBy('doctor_id')
            ->pluck('count', 'doctor_id');

        $consultantDoctors = $consultantDoctors->map(function ($doc) use ($selectedDay, $activeVisits, $inConsultationAppointments, $waitingCounts) {
            $doc->is_working_selected_day = $doc->isWorkingOnDay($selectedDay);
            $doc->is_available_for_view = $doc->is_working_selected_day && (bool)$doc->is_available_today;

            $activeVisit = $activeVisits->get($doc->id)?->first();
            $inConsultApp = $inConsultationAppointments->get($doc->id)?->first();

            $doc->current_patient_name = null;
            $doc->current_patient_queue = null;
            $doc->current_status = 'free'; // 'free', 'in_consultation', 'calling'
            $doc->current_since = null;

            if ($activeVisit) {
                $doc->current_patient_name = optional(optional($activeVisit->patient)->user)->name ?? optional($activeVisit->patient)->name ?? 'مريض بالداخل';
                $doc->current_status = 'in_consultation';
                $doc->current_since = $activeVisit->created_at ? $activeVisit->created_at->diffForHumans(null, true) : null;
                $doc->current_patient_queue = optional($activeVisit->appointment)->queue_number;
            } elseif ($inConsultApp) {
                $doc->current_patient_name = optional(optional($inConsultApp->patient)->user)->name ?? optional($inConsultApp->patient)->name ?? 'مريض قيد الاستدعاء';
                $doc->current_status = $inConsultApp->status;
                $doc->current_since = $inConsultApp->called_at ? \Carbon\Carbon::parse($inConsultApp->called_at)->diffForHumans(null, true) : null;
                $doc->current_patient_queue = $inConsultApp->queue_number;
            }

            $doc->waiting_patients_count = $waitingCounts->get($doc->id, 0);

            return $doc;
        });

        // تجميع الأطباء حسب التخصص للعرض
        $groupedDoctors = $consultantDoctors->groupBy('specialization');

        // جلب المواعيد المحجوزة اليوم للأطباء الاستشاريين وحجوزات السونار (المجدولة والمؤكدة والتي يتم استدعاؤها)
        $todayAppointments = \App\Models\Appointment::with(['patient.user', 'doctor.user', 'emergency'])
            ->where(function($query) {
                $query->whereHas('doctor', function($q) {
                    $q->where('type', 'consultant')
                      ->orWhere('specialization', 'LIKE', '%سونار%');
                })
                ->orWhere('reason', 'LIKE', '%سونار%');
            })
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['scheduled', 'confirmed', 'calling'])
            ->orderByRaw("CASE WHEN status = 'calling' THEN 1 ELSE 2 END")
            ->orderBy('queue_number', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // جلب طلبات الفحوصات والسونار المعلقة الصادرة اليوم من العيادات الاستشارية بانتظار الدفع
        $pendingConsultantRequests = \App\Models\Request::with(['visit.patient.user', 'visit.doctor.user', 'visit.department'])
            ->where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled')
            ->whereDate('created_at', today())
            ->where(function($q) {
                $q->whereHas('visit.doctor', function($dq) {
                    $dq->where('type', 'consultant');
                })->orWhere('subtype', 'ultrasound')
                  ->orWhere('description', 'LIKE', '%سونار%');
            })
            ->latest()
            ->get();

        // جلب المراجعات والمواعيد التي تم جدولتها اليوم من محطة الأطباء
        $todayScheduledFollowUps = \App\Models\Appointment::with(['patient.user', 'doctor.user', 'department'])
            ->where(function($q) {
                $q->where('is_free_recheck', true)
                  ->orWhereNotNull('recheck_parent_visit_id');
            })
            ->whereDate('created_at', today())
            ->latest('created_at')
            ->get();

        $pendingPrintFollowUps = $todayScheduledFollowUps->filter(function($f) {
            return empty($f->printed_at) && ($f->print_count ?? 0) === 0;
        });

        $printedTodayFollowUps = $todayScheduledFollowUps->filter(function($f) {
            return !empty($f->printed_at) || ($f->print_count ?? 0) > 0;
        });

        return view('consultant-availability.index', compact(
            'consultantDoctors', 
            'groupedDoctors', 
            'todayAppointments', 
            'pendingConsultantRequests', 
            'todayScheduledFollowUps', 
            'pendingPrintFollowUps',
            'printedTodayFollowUps',
            'weekDays', 
            'selectedDay'
        ));
    }

    /**
     * جلب الحالة المباشرة للعيادات الجارية والمراجعات المجدولة اليوم (AJAX Polling)
     */
    public function liveStatus(Request $request)
    {
        $today = today();

        // 1. العيادات الجارية الآن
        $consultantDoctors = Doctor::with(['user', 'department'])
            ->where(function($q) {
                $q->where('type', 'consultant')
                  ->orWhere('type', 'sonar')
                  ->orWhere('specialization', 'LIKE', '%سونار%');
            })
            ->where('is_active', true)
            ->get();

        $activeVisits = \App\Models\Visit::with(['patient.user', 'appointment'])
            ->whereDate('visit_date', $today)
            ->where('status', 'in_progress')
            ->get()
            ->groupBy('doctor_id');

        $inConsultationAppointments = \App\Models\Appointment::with('patient.user')
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['calling', 'in_consultation'])
            ->get()
            ->groupBy('doctor_id');

        $runningClinics = [];
        foreach ($consultantDoctors as $doc) {
            $activeVisit = $activeVisits->get($doc->id)?->first();
            $inConsultApp = $inConsultationAppointments->get($doc->id)?->first();

            if ($activeVisit || $inConsultApp) {
                $status = $activeVisit ? 'in_consultation' : $inConsultApp->status;
                $patientName = $activeVisit 
                    ? (optional(optional($activeVisit->patient)->user)->name ?? optional($activeVisit->patient)->name ?? 'مريض بالداخل')
                    : (optional(optional($inConsultApp->patient)->user)->name ?? optional($inConsultApp->patient)->name ?? 'مريض قيد الاستدعاء');
                $queue = $activeVisit ? optional($activeVisit->appointment)->queue_number : $inConsultApp->queue_number;
                $since = $activeVisit 
                    ? ($activeVisit->created_at ? $activeVisit->created_at->diffForHumans(null, true) : 'الآن')
                    : ($inConsultApp->called_at ? \Carbon\Carbon::parse($inConsultApp->called_at)->diffForHumans(null, true) : 'الآن');

                $runningClinics[] = [
                    'doctor_id' => $doc->id,
                    'doctor_name' => $doc->user->name ?? 'طبيب',
                    'department_name' => $doc->department->name ?? 'العيادة',
                    'status' => $status,
                    'patient_name' => $patientName,
                    'patient_queue' => $queue,
                    'current_since' => $since,
                ];
            }
        }

        // 2. المراجعات المجدولة اليوم
        $allTodayFollowUps = \App\Models\Appointment::with(['patient.user', 'doctor.user', 'department'])
            ->where(function($q) {
                $q->where('is_free_recheck', true)
                  ->orWhereNotNull('recheck_parent_visit_id');
            })
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->get();

        $mapFollowUp = function($fApp) {
            $appDate = $fApp->appointment_date ? \Carbon\Carbon::parse($fApp->appointment_date) : null;
            $isPrinted = !empty($fApp->printed_at) || (($fApp->print_count ?? 0) > 0);
            return [
                'id' => $fApp->id,
                'patient_name' => optional($fApp->patient)->name ?? optional(optional($fApp->patient)->user)->name ?? 'مريض',
                'doctor_name' => optional(optional($fApp->doctor)->user)->name ?? 'غير محدد',
                'appointment_date' => $appDate ? $appDate->format('Y-m-d') : '—',
                'day_name' => $appDate ? $appDate->locale('ar')->dayName : '',
                'notes' => $fApp->notes ?? '',
                'is_printed' => $isPrinted,
                'print_count' => $fApp->print_count ?? 0,
                'printed_time' => $fApp->printed_at ? \Carbon\Carbon::parse($fApp->printed_at)->format('H:i') : null,
                'print_url' => route('appointments.print', $fApp->id),
            ];
        };

        $pendingFollowUps = $allTodayFollowUps->filter(function($f) {
            return empty($f->printed_at) && ($f->print_count ?? 0) === 0;
        })->map($mapFollowUp)->values();

        $printedTodayFollowUps = $allTodayFollowUps->filter(function($f) {
            return !empty($f->printed_at) || ($f->print_count ?? 0) > 0;
        })->map($mapFollowUp)->values();

        // 3. طابور الحجوزات والقبض لليوم
        $canProcessPayments = auth()->check() && auth()->user()->canAny(['process consultation payments', 'process payments']);
        $todayAppointmentsList = \App\Models\Appointment::with(['patient.user', 'doctor.user', 'emergency.emergencyPatient'])
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['scheduled', 'confirmed', 'calling'])
            ->orderByRaw("CASE WHEN status = 'calling' THEN 1 ELSE 2 END")
            ->orderBy('queue_number', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function($app) use ($canProcessPayments) {
                $patientName = 'مريض غير محدد';
                if ($app->patient && $app->patient->user) {
                    $patientName = $app->patient->user->name;
                } elseif ($app->emergency && $app->emergency->emergencyPatient) {
                    $patientName = $app->emergency->emergencyPatient->name;
                }

                return [
                    'id' => $app->id,
                    'queue_number' => $app->queue_number ?: $app->id,
                    'patient_name' => $patientName,
                    'is_emergency' => (bool)$app->emergency_id,
                    'doctor_name' => optional(optional($app->doctor)->user)->name ?? 'غير محدد',
                    'status' => $app->status,
                    'payment_status' => $app->payment_status,
                    'is_free_recheck' => (bool)$app->is_free_recheck,
                    'can_process_payments' => $canProcessPayments,
                    'payment_url' => route('cashier.payment.form', $app->id),
                    'print_url' => route('appointments.print', $app->id),
                    'can_cancel' => $app->canBeCancelled(),
                    'cancel_url' => route('appointments.cancel', $app->id),
                ];
            })->values();

        // 4. طلبات الفحوصات والسونار بانتظار السداد
        $pendingRequests = \App\Models\Request::with(['visit.patient.user', 'visit.doctor.user', 'visit.department'])
            ->where('payment_status', 'pending')
            ->where('status', '!=', 'cancelled')
            ->whereDate('created_at', $today)
            ->where(function($q) {
                $q->whereHas('visit.doctor', function($dq) {
                    $dq->where('type', 'consultant');
                })->orWhere('subtype', 'ultrasound')
                  ->orWhere('description', 'LIKE', '%سونار%');
            })
            ->latest()
            ->get()
            ->map(function($req) {
                $patientName = optional(optional($req->visit)->patient)->name ?? optional(optional(optional($req->visit)->patient)->user)->name ?? 'مريض';
                return [
                    'id' => $req->id,
                    'patient_name' => $patientName,
                    'type_label' => $req->subtype === 'ultrasound' ? 'سونار' : ($req->type === 'radiology' ? 'أشعة' : $req->type),
                    'total_amount' => number_format($req->total_amount ?? 0)
                ];
            })->values();

        return response()->json([
            'success' => true,
            'running_clinics' => $runningClinics,
            'running_clinics_count' => count($runningClinics),
            'pending_follow_ups' => $pendingFollowUps,
            'pending_follow_ups_count' => count($pendingFollowUps),
            'printed_today_follow_ups' => $printedTodayFollowUps,
            'printed_today_follow_ups_count' => count($printedTodayFollowUps),
            'all_today_count' => count($allTodayFollowUps),
            'today_appointments' => $todayAppointmentsList,
            'today_appointments_count' => count($todayAppointmentsList),
            'pending_requests' => $pendingRequests,
            'pending_requests_count' => count($pendingRequests),
            'timestamp' => now()->format('H:i:s'),
        ]);
    }

    /**
     * البحث الفوري في سجل كافة المراجعات والمواعيد لجميع الأيام
     */
    public function searchFollowUps(Request $request)
    {
        $searchQuery = $request->query('q');
        $date = $request->query('date');

        $query = \App\Models\Appointment::with(['patient.user', 'doctor.user', 'department'])
            ->where(function($q) {
                $q->where('is_free_recheck', true)
                  ->orWhereNotNull('recheck_parent_visit_id');
            });

        if ($date) {
            $query->whereDate('appointment_date', $date);
        }

        if ($searchQuery) {
            $query->where(function($q) use ($searchQuery) {
                $q->whereHas('patient.user', function($uq) use ($searchQuery) {
                    $uq->where('name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('phone', 'LIKE', "%{$searchQuery}%");
                })
                ->orWhereHas('patient', function($pq) use ($searchQuery) {
                    $pq->where('name', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('national_id', 'LIKE', "%{$searchQuery}%")
                      ->orWhere('medical_record_number', 'LIKE', "%{$searchQuery}%");
                })
                ->orWhere('notes', 'LIKE', "%{$searchQuery}%")
                ->orWhere('reason', 'LIKE', "%{$searchQuery}%");
            });
        }

        $results = $query->latest('appointment_date')
            ->limit(30)
            ->get()
            ->map(function($fApp) {
                $appDate = $fApp->appointment_date ? \Carbon\Carbon::parse($fApp->appointment_date) : null;
                $isPrinted = !empty($fApp->printed_at) || (($fApp->print_count ?? 0) > 0);
                return [
                    'id' => $fApp->id,
                    'patient_name' => optional($fApp->patient)->name ?? optional(optional($fApp->patient)->user)->name ?? 'مريض',
                    'patient_phone' => optional(optional($fApp->patient)->user)->phone ?? optional($fApp->patient)->phone ?? '—',
                    'patient_file' => optional($fApp->patient)->national_id ?: optional($fApp->patient)->id,
                    'doctor_name' => optional(optional($fApp->doctor)->user)->name ?? 'غير محدد',
                    'department_name' => optional($fApp->department)->name ?? 'الاستشارية',
                    'appointment_date' => $appDate ? $appDate->format('Y-m-d') : '—',
                    'day_name' => $appDate ? $appDate->locale('ar')->dayName : '',
                    'notes' => $fApp->notes ?? '',
                    'is_printed' => $isPrinted,
                    'print_count' => $fApp->print_count ?? 0,
                    'printed_time' => $fApp->printed_at ? \Carbon\Carbon::parse($fApp->printed_at)->format('Y-m-d H:i') : null,
                    'print_url' => route('appointments.print', $fApp->id),
                ];
            });

        return response()->json([
            'success' => true,
            'results' => $results,
            'count' => count($results),
        ]);
    }

    public function financialMovements(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('view consultant financial movements'))) {
            abort(403, 'غير مصرح لك بالوصول إلى الحركات المالية للاستشارية');
        }

        $query = ConsultationRevenue::with(['appointment.patient.user', 'appointment.doctor.user', 'department', 'cashier'])
            ->whereHas('appointment.doctor', function ($q) {
                $q->where('type', 'consultant');
            });

        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $filterType = $request->query('filter_type');

        if ($fromDate) {
            $query->whereDate('paid_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('paid_at', '<=', $toDate);
        }

        if ($filterType === 'payment') {
            $query->where('movement_type', 'payment');
        } elseif ($filterType === 'refund') {
            $query->where('movement_type', 'refund');
        } elseif ($filterType === 'appointment_paid') {
            $query->whereHas('appointment', function ($q) {
                $q->where('payment_status', 'paid');
            });
        } elseif ($filterType === 'appointment_refunded') {
            $query->whereHas('appointment', function ($q) {
                $q->where('payment_status', 'refunded');
            });
        }

        $totalsQuery = clone $query;

        $totalReceived = (clone $totalsQuery)->where('total_amount', '>=', 0)->sum('total_amount');
        $totalRefunded = abs((clone $totalsQuery)->where('total_amount', '<', 0)->sum('total_amount'));
        $netTotal = (clone $totalsQuery)->sum('total_amount');

        $payments = $query->orderBy('paid_at', 'desc')->paginate(25);

        return view('consultant-availability.financial-movements', compact(
            'payments',
            'totalReceived',
            'totalRefunded',
            'netTotal',
            'fromDate',
            'toDate',
            'filterType'
        ));
    }

    public function exportFinancialMovements(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('view consultant financial movements'))) {
            abort(403, 'غير مصرح لك بتصدير الحركات المالية للاستشارية');
        }

        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $filterType = $request->query('filter_type');

        return Excel::download(
            new ConsultantFinancialMovementsExport($fromDate, $toDate, $filterType),
            'financial_movements_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportDoctorAccount(Request $request, Doctor $doctor)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || (!$user->can('view doctor accounts') && !$user->can('view doctor profits')))) {
            abort(403, 'غير مصرح لك بتصدير حسابات الأطباء');
        }

        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        return Excel::download(
            new DoctorPaymentsDuesExport($doctor->id, $fromDate, $toDate),
            'doctor_' . optional($doctor->user)->name . '_payments_dues_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function doctorAccounts(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || (!$user->can('view doctor accounts') && !$user->can('view doctor profits')))) {
            abort(403, 'غير مصرح لك بالوصول إلى حسابات الأطباء');
        }

        $consultantDoctors = Doctor::with(['user', 'department', 'financialAccount'])
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->where('doctors.type', 'consultant')
            ->where('doctors.is_active', true)
            ->orderBy('doctors.specialization')
            ->orderBy('users.name')
            ->select('doctors.*')
            ->get();

        return view('consultant-availability.doctor-accounts', compact('consultantDoctors'));
    }

    public function doctorAccount(Request $request, Doctor $doctor)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || (!$user->can('view doctor accounts') && !$user->can('view doctor profits')))) {
            abort(403, 'غير مصرح لك بالوصول إلى كشف حساب الطبيب');
        }

        $doctor->load(['user', 'department', 'financialAccount']);

        $filterType = $request->query('filter_type');
        $payoutSearch = $request->query('payout_search');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $query = ConsultationRevenue::with(['appointment.patient.user', 'cashier'])
            ->where('doctor_id', $doctor->id);

        if ($filterType === 'payment') {
            $query->where('movement_type', 'payment');
        } elseif ($filterType === 'refund') {
            $query->where('movement_type', 'refund');
        }

        $revenues = $query->orderBy('paid_at', 'desc')->paginate(25);

        $totalsQuery = clone $query;
        $totalReceived = (clone $totalsQuery)->where('total_amount', '>=', 0)->sum('total_amount');
        $totalRefunded = abs((clone $totalsQuery)->where('total_amount', '<', 0)->sum('total_amount'));
        $netTotal = (clone $totalsQuery)->sum('total_amount');

        $baseDuesQuery = DoctorDue::with('paidBy')
            ->where('doctor_id', $doctor->id)
            ->when($payoutSearch, function ($query) use ($payoutSearch) {
                $query->where(function ($sub) use ($payoutSearch) {
                    $sub->where('notes', 'like', '%' . $payoutSearch . '%')
                        ->orWhere('amount', 'like', '%' . $payoutSearch . '%');
                });
            })
            ->when($fromDate, fn($query) => $query->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn($query) => $query->whereDate('created_at', '<=', $toDate));

        $paidDues = (clone $baseDuesQuery)
            ->where('status', 'paid')
            ->orderBy('paid_at', 'desc')
            ->paginate(10, ['*'], 'paid_page');

        $pendingDues = (clone $baseDuesQuery)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'pending_page');

        return view('consultant-availability.doctor-account-details', compact(
            'doctor',
            'revenues',
            'totalReceived',
            'totalRefunded',
            'netTotal',
            'filterType',
            'paidDues',
            'pendingDues',
            'payoutSearch',
            'fromDate',
            'toDate'
        ));
    }

    public function doctorPayout(Request $request, Doctor $doctor)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || (!$user->can('view doctor accounts') && !$user->can('view doctor profits')))) {
            abort(403, 'غير مصرح لك بصرف مستحقات الأطباء');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:1000',
        ]);

        $amount = round($request->input('amount'), 2);

        $account = DoctorFinancialAccount::firstOrCreate(
            ['doctor_id' => $doctor->id],
            [
                'balance' => 0,
                'total_earned' => 0,
                'total_paid' => 0,
            ]
        );

        if ($amount > $account->balance) {
            return redirect()->back()->with('error', 'المبلغ أكبر من الرصيد المتاح للصرف.');
        }

        $account->balance = round($account->balance - $amount, 2);
        $account->total_paid = round($account->total_paid + $amount, 2);
        $account->last_paid_at = now();
        $account->save();

        $remaining = $amount;
        $pendingDues = DoctorDue::where('doctor_id', $doctor->id)
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        foreach ($pendingDues as $due) {
            if ($remaining <= 0) {
                break;
            }

            if ($due->amount <= $remaining) {
                $remaining = round($remaining - $due->amount, 2);
                $due->update([
                    'status' => 'paid',
                    'paid_by_id' => auth()->id(),
                    'paid_at' => now(),
                ]);
            } else {
                $due->amount = round($due->amount - $remaining, 2);
                $due->save();

                DoctorDue::create([
                    'doctor_id' => $doctor->id,
                    'amount' => $remaining,
                    'status' => 'paid',
                    'notes' => 'صرف جزئي لمستحقات الطبيب',
                    'paid_by_id' => auth()->id(),
                    'paid_at' => now(),
                ]);

                $remaining = 0;
            }
        }

        if ($remaining > 0) {
            DoctorDue::create([
                'doctor_id' => $doctor->id,
                'amount' => $remaining,
                'status' => 'paid',
                'notes' => 'صرف للطبيب دون وجود مستحقات سابقة',
                'paid_by_id' => auth()->id(),
                'paid_at' => now(),
            ]);
        }

        FinancialTransaction::create([
            'transaction_type' => 'expense',
            'related_type' => Doctor::class,
            'related_id' => $doctor->id,
            'amount' => $amount,
            'currency' => 'IQD',
            'description' => 'صرف للطبيب ' . optional($doctor->user)->name,
            'performed_by_id' => auth()->id(),
            'performed_at' => now(),
        ]);

        return redirect()->route('consultant-availability.doctor-account', $doctor)
            ->with('success', 'تم تسجيل صرف للطبيب بنجاح.');
    }

    /**
     * صفحة اختبار بسيطة
     */
    public function test()
    {
        $consultantDoctors = Doctor::with(['user', 'department'])
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->where('doctors.type', 'consultant')
            ->where('doctors.is_active', true)
            ->orderBy('doctors.specialization')
            ->orderBy('users.name')
            ->select('doctors.*')
            ->get();

        return view('consultant-availability.test', compact('consultantDoctors'));
    }

    /**
     * صفحة مبسطة بدون JavaScript معقد
     */
    public function simple()
    {
        $consultantDoctors = Doctor::with(['user', 'department'])
            ->join('users', 'doctors.user_id', '=', 'users.id')
            ->where('doctors.type', 'consultant')
            ->where('doctors.is_active', true)
            ->orderBy('doctors.specialization')
            ->orderBy('users.name')
            ->select('doctors.*')
            ->get();

        // تجميع الأطباء حسب التخصص للعرض
        $groupedDoctors = $consultantDoctors->groupBy('specialization');

        return view('consultant-availability.simple', compact('consultantDoctors', 'groupedDoctors'));
    }

    /**
     * تحديث توفر طبيب استشاري
     */
    public function updateAvailability(Request $request, Doctor $doctor)
    {
        // التحقق من أن الطبيب استشاري
        if ($doctor->type !== 'consultant') {
            return redirect()->back()->with('error', 'يمكن تحديث توفر الأطباء الاستشاريين فقط');
        }

        $request->validate([
            'is_available_today' => 'required|in:0,1,true,false',
            'day' => 'nullable|in:السبت,الأحد,الإثنين,الثلاثاء,الأربعاء,الخميس,الجمعة',
        ]);

        $isAvailable = filter_var($request->is_available_today, FILTER_VALIDATE_BOOLEAN);

        $updates = [
            'is_available_today' => $isAvailable,
            'available_date' => today(),
        ];

        if ($request->filled('day')) {
            $workingDays = is_array($doctor->working_days) ? $doctor->working_days : [];
            $day = $request->input('day');

            if ($isAvailable) {
                if (!in_array($day, $workingDays)) {
                    $workingDays[] = $day;
                }
            } else {
                $workingDays = array_values(array_diff($workingDays, [$day]));
            }

            $updates['working_days'] = $workingDays;
        }

        $doctor->update($updates);

        $statusText = $isAvailable ? 'متوفر' : 'غير متوفر';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'doctor_id' => $doctor->id,
                'is_available_today' => $isAvailable,
                'status_text' => $statusText,
                'message' => "تم تحديث توفر د. {$doctor->user->name} إلى: {$statusText}",
            ]);
        }

        return redirect()->back()->with('success', "تم تحديث توفر الطبيب: {$statusText}");
    }

    /**
     * تحديث توفر جميع الأطباء الاستشاريين
     */
    public function bulkUpdate(Request $request)
    {
        try {
            // التحقق من الصلاحيات (للـ web routes)
            if (auth()->check() && !auth()->user()->can('manage consultant availability')) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
            }

            $request->validate([
                'is_available_today' => 'required|in:0,1,true,false',
                'doctor_ids' => 'nullable|array',
                'doctor_ids.*' => 'exists:doctors,id',
            ]);

            $isAvailable = filter_var($request->is_available_today, FILTER_VALIDATE_BOOLEAN);
            $doctorIds = $request->doctor_ids ?? [];

            if (empty($doctorIds)) {
                // تحديث جميع الأطباء الاستشاريين
                $affected = Doctor::where('type', 'consultant')
                    ->where('is_active', true)
                    ->update([
                        'is_available_today' => $isAvailable,
                        'available_date' => today(),
                    ]);

                $message = $isAvailable ?
                    'تم تفعيل التوفر لجميع الأطباء الاستشاريين' :
                    'تم إلغاء التوفر لجميع الأطباء الاستشاريين';
            } else {
                // تحديث الأطباء المحددين فقط
                $affected = Doctor::whereIn('id', $doctorIds)
                    ->where('type', 'consultant')
                    ->where('is_active', true)
                    ->update([
                        'is_available_today' => $isAvailable,
                        'available_date' => today(),
                    ]);

                $message = $isAvailable ?
                    'تم تفعيل التوفر للأطباء المحددين' :
                    'تم إلغاء التوفر للأطباء المحددين';
            }

            // إذا كان API call، أعد JSON response
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'affected_doctors' => $affected
                ]);
            }

            // إعادة توجيه للـ web interface
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            \Log::error('Bulk update error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'user' => auth()->check() ? auth()->user()->name : 'guest',
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}
