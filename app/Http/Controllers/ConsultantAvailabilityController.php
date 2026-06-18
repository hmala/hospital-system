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
            
            // التحقق من أن المستخدم لديه صلاحية إدارة توفر الأطباء الاستشاريين
            if (!auth()->user() || !auth()->user()->can('manage consultant availability')) {
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
            ->where('doctors.type', 'consultant')
            ->where('doctors.is_active', true)
            ->whereJsonContains('doctors.working_days', [$selectedDay])
            ->orderBy('doctors.specialization')
            ->orderBy('users.name')
            ->select('doctors.*')
            ->get();

        // تجميع الأطباء حسب التخصص للعرض
        $groupedDoctors = $consultantDoctors->groupBy('specialization');

        // جلب المواعيد المحجوزة اليوم للأطباء الاستشاريين
        $todayAppointments = \App\Models\Appointment::with(['patient.user', 'doctor.user', 'emergency'])
            ->whereHas('doctor', function($q) {
                $q->where('type', 'consultant');
            })
            ->whereDate('appointment_date', today())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderBy('appointment_date')
            ->get();

        $groupedDoctors = $consultantDoctors->groupBy('specialization');

        return view('consultant-availability.index', compact('consultantDoctors', 'groupedDoctors', 'todayAppointments', 'weekDays', 'selectedDay'));
    }

    public function financialMovements(Request $request)
    {
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
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        return Excel::download(
            new DoctorPaymentsDuesExport($doctor->id, $fromDate, $toDate),
            'doctor_' . optional($doctor->user)->name . '_payments_dues_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function doctorAccounts(Request $request)
    {
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
