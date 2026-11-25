<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrAbsence;
use App\Models\HrEmployee;
use App\Models\HrEmployeeAction;
use App\Models\HrLeaveRequest;
use App\Models\HrLoan;
use App\Models\HrOvertime;
use App\Models\HrPayroll;
use App\Models\HrPayrollCycle;
use App\Models\HrSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HrPayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view hr payroll')->only(['index', 'show']);
        $this->middleware('permission:create hr payroll')->only(['create', 'store', 'updateSlip']);
        $this->middleware('permission:approve hr payroll')->only(['approve']);
        $this->middleware('permission:delete hr payroll')->only(['destroy']);
        $this->middleware('permission:print hr payslips')->only(['printSlip']);
    }

    // قائمة دورات الرواتب
    public function index()
    {
        $cycles = HrPayrollCycle::latest()->paginate(12);

        return view('hr.payrolls.index', compact('cycles'));
    }

    // نموذج توليد دورة جديدة
    public function create()
    {
        return view('hr.payrolls.create');
    }

    // توليد مسير الرواتب
    public function store(Request $request)
    {
        $request->validate([
            'cycle_month' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // تأكد أنه لا يوجد مسير لنفس الشهر
        if (HrPayrollCycle::where('cycle_month', $request->cycle_month)->exists()) {
            return back()->with('error', 'يوجد مسير رواتب لهذا الشهر بالفعل. لا يمكن إنشاء مسير مكرر.');
        }

        $cycle = HrPayrollCycle::create([
            'cycle_month' => $request->cycle_month,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        $cycleStart = Carbon::parse($request->start_date);
        $cycleEnd = Carbon::parse($request->end_date);

        $employees = HrEmployee::where('status', 'active')
            ->with(['activeAllowances', 'activeLoans', 'actions', 'absences', 'overtimes'])
            ->get();

        foreach ($employees as $emp) {
            $isPerCall = $emp->isPerCall();
            $callsCount = 0;
            $callsAmount = 0.0;
            $basicSalary = $isPerCall ? 0.0 : (float) $emp->basic_salary;

            if ($isPerCall) {
                $approvedSchedules = HrSchedule::with('shift')
                    ->where('hr_employee_id', $emp->id)
                    ->whereDate('shift_date', '>=', $cycleStart->format('Y-m-d'))
                    ->whereDate('shift_date', '<=', $cycleEnd->format('Y-m-d'))
                    ->where('is_off_day', false)
                    ->where('is_leave', false)
                    ->where('attendance_status', '!=', 'absent')
                    ->whereNotNull('hr_shift_id')
                    ->where(function ($q) use ($emp) {
                        $q->whereHas('rosterApproval', fn ($rq) => $rq->where('status', 'approved'))
                            ->orWhere(function ($sq) use ($emp) {
                                if ($emp->department_id) {
                                    $sq->whereExists(function ($sub) use ($emp) {
                                        $sub->selectRaw('1')
                                            ->from('hr_roster_approvals')
                                            ->where('department_id', $emp->department_id)
                                            ->where('status', 'approved')
                                            ->whereColumn('hr_roster_approvals.start_date', '<=', 'hr_schedules.shift_date')
                                            ->whereColumn('hr_roster_approvals.end_date', '>=', 'hr_schedules.shift_date');
                                    });
                                }
                            });
                    })
                    ->get();

                $callsCount = $approvedSchedules->count();
                foreach ($approvedSchedules as $callSch) {
                    $callsAmount += $emp->resolveCallRateForShift($callSch->shift);
                }
                $callsAmount = round($callsAmount, 2);
            }

            // ① المخصصات الثابتة
            $totalAllowances = $emp->activeAllowances->sum('amount');

            // ② العقوبات والمكافآت (pending)
            $pendingActions = HrEmployeeAction::where('hr_employee_id', $emp->id)
                ->where('status', 'pending')->get();
            $bonuses = $pendingActions->where('financial_amount', '>', 0)->sum('financial_amount');
            $penalties = abs($pendingActions->where('financial_amount', '<', 0)->sum('financial_amount'));

            // ③ الغيابات (pending)
            $pendingAbsences = HrAbsence::where('hr_employee_id', $emp->id)
                ->where('status', 'pending')
                ->whereBetween('absence_date', [$request->start_date, $request->end_date])
                ->get();
            $absenceDeduction = $pendingAbsences->sum('deduction_amount');

            // ③-ب الإجازات المعتمدة بدون راتب للموظف ذي الراتب الثابت
            $unpaidLeaves = collect();
            $unpaidLeaveDeduction = 0.0;
            if (! $isPerCall && $basicSalary > 0) {
                $unpaidLeaves = HrLeaveRequest::where('hr_employee_id', $emp->id)
                    ->where('status', 'approved')
                    ->where('is_paid', false)
                    ->whereNull('hr_payroll_cycle_id')
                    ->whereDate('start_date', '<=', $cycleEnd->format('Y-m-d'))
                    ->whereDate('end_date', '>=', $cycleStart->format('Y-m-d'))
                    ->get();

                $unpaidDays = 0;
                foreach ($unpaidLeaves as $ul) {
                    $overlapStart = $ul->start_date->gt($cycleStart) ? $ul->start_date : $cycleStart;
                    $overlapEnd = $ul->end_date->lt($cycleEnd) ? $ul->end_date : $cycleEnd;
                    if ($overlapEnd->gte($overlapStart)) {
                        $unpaidDays += $overlapStart->diffInDays($overlapEnd) + 1;
                    }
                }
                if ($unpaidDays > 0) {
                    $unpaidLeaveDeduction = round(($basicSalary / 30) * $unpaidDays, 2);
                }
            }

            // ④ العمل الإضافي (pending)
            $pendingOvertimes = HrOvertime::where('hr_employee_id', $emp->id)
                ->where('status', 'pending')
                ->whereBetween('overtime_date', [$request->start_date, $request->end_date])
                ->get();
            $overtimeAmount = $pendingOvertimes->sum('total_amount');

            // ⑤ السلف النشطة
            $activeLoan = $emp->activeLoans()->where('start_date', '<=', $request->end_date)->first();
            $loanDeduction = 0;
            if ($activeLoan && $activeLoan->remaining_amount > 0) {
                $loanDeduction = min($activeLoan->monthly_installment, $activeLoan->remaining_amount);
            }

            // ⑥ الضمان الاجتماعي
            $socialSecurity = 0;
            if (! $isPerCall && $emp->subject_to_social_security) {
                $socialSecurity = round($basicSalary * ($emp->social_security_percentage / 100), 2);
            }

            // ⑦ الضريبة
            $tax = 0;
            if (! $isPerCall && $emp->subject_to_tax) {
                $tax = round($basicSalary * ($emp->tax_percentage / 100), 2);
            }

            // ⑧ الراتب الصافي النهائي
            $netSalary = $basicSalary
                + $callsAmount
                + $totalAllowances
                + $bonuses
                + $overtimeAmount
                - $penalties
                - $absenceDeduction
                - $unpaidLeaveDeduction
                - $loanDeduction
                - $socialSecurity
                - $tax;

            // إنشاء سجل الراتب
            $slip = HrPayroll::create([
                'hr_payroll_cycle_id' => $cycle->id,
                'hr_employee_id' => $emp->id,
                'salary_calculation_mode' => $emp->salary_calculation_mode ?? 'fixed_salary',
                'basic_salary' => $basicSalary,
                'calls_count' => $callsCount,
                'calls_amount' => $callsAmount,
                'allowances' => $totalAllowances,
                'bonuses_amount' => $bonuses,
                'penalties_amount' => $penalties,
                'overtime_amount' => $overtimeAmount,
                'absence_deduction' => $absenceDeduction,
                'unpaid_leave_deduction' => $unpaidLeaveDeduction,
                'loan_deduction' => $loanDeduction,
                'social_security_amount' => $socialSecurity,
                'tax_amount' => $tax,
                'net_salary' => max(0, $netSalary),
                'payment_method' => $emp->payment_method ?? 'cash',
                'status' => 'pending',
            ]);

            // ترحيل العقوبات/المكافآت
            $pendingActions->each(fn ($a) => $a->update([
                'status' => 'processed',
                'hr_payroll_cycle_id' => $cycle->id,
            ]));

            // ترحيل الغيابات
            $pendingAbsences->each(fn ($a) => $a->update([
                'status' => 'processed',
                'hr_payroll_cycle_id' => $cycle->id,
            ]));

            // ترحيل الإجازات بدون راتب
            $unpaidLeaves->each(fn ($ul) => $ul->update([
                'hr_payroll_cycle_id' => $cycle->id,
            ]));

            // ترحيل الإضافي
            $pendingOvertimes->each(fn ($o) => $o->update([
                'status' => 'processed',
                'hr_payroll_cycle_id' => $cycle->id,
            ]));

            // خصم قسط السلفة
            if ($activeLoan && $loanDeduction > 0) {
                $activeLoan->payInstallment($loanDeduction);
            }
        }

        return redirect()->route('hr.payrolls.show', $cycle->id)
            ->with('success', 'تم توليد مسير رواتب شهر '.$request->cycle_month.' بنجاح لـ '.$employees->count().' موظف.');
    }

    // عرض تفاصيل مسير الرواتب
    public function show(HrPayrollCycle $payroll)
    {
        $payroll->load(['payrolls.employee.department', 'payrolls.employee']);

        $summary = [
            'total_basic' => $payroll->payrolls->sum('basic_salary'),
            'total_calls_count' => $payroll->payrolls->sum('calls_count'),
            'total_calls_amount' => $payroll->payrolls->sum('calls_amount'),
            'total_allowances' => $payroll->payrolls->sum('allowances'),
            'total_bonuses' => $payroll->payrolls->sum('bonuses_amount'),
            'total_overtime' => $payroll->payrolls->sum('overtime_amount'),
            'total_penalties' => $payroll->payrolls->sum('penalties_amount'),
            'total_absence' => $payroll->payrolls->sum('absence_deduction'),
            'total_unpaid_leaves' => $payroll->payrolls->sum('unpaid_leave_deduction'),
            'total_loans' => $payroll->payrolls->sum('loan_deduction'),
            'total_social' => $payroll->payrolls->sum('social_security_amount'),
            'total_tax' => $payroll->payrolls->sum('tax_amount'),
            'total_net' => $payroll->payrolls->sum('net_salary'),
            'cash_count' => $payroll->payrolls->where('payment_method', 'cash')->count(),
            'bank_count' => $payroll->payrolls->where('payment_method', 'bank')->count(),
            'total_cash_amount' => $payroll->payrolls->where('payment_method', 'cash')->sum('net_salary'),
            'total_bank_amount' => $payroll->payrolls->where('payment_method', 'bank')->sum('net_salary'),
        ];

        return view('hr.payrolls.show', compact('payroll', 'summary'));
    }

    // اعتماد المسير
    public function approve(HrPayrollCycle $payroll)
    {
        if ($payroll->status !== 'draft') {
            return back()->with('error', 'هذا المسير ليس في حالة مسودة.');
        }
        $payroll->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

        // إطلاق الحدث الخاص بالحسابات
        event(new \App\Events\HR\PayrollApprovedEvent($payroll));

        return back()->with('success', 'تم اعتماد مسير رواتب شهر '.$payroll->cycle_month.' بنجاح، وتم إرسال الإشعار لقسم الحسابات.');
    }

    // تعديل مخصصات الموظف في المسير (Ajax)
    public function updateSlip(Request $request, HrPayroll $slip)
    {
        if ($slip->payrollCycle->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'المسير معتمد ولا يمكن التعديل.'], 403);
        }

        $slip->allowances = $request->allowances ?? $slip->allowances;

        // إعادة حساب الصافي
        $slip->net_salary = max(0,
            $slip->basic_salary
            + $slip->calls_amount
            + $slip->allowances
            + $slip->bonuses_amount
            + $slip->overtime_amount
            - $slip->penalties_amount
            - $slip->absence_deduction
            - $slip->unpaid_leave_deduction
            - $slip->loan_deduction
            - $slip->social_security_amount
            - $slip->tax_amount
        );
        $slip->save();

        return response()->json(['success' => true, 'net_salary' => $slip->net_salary]);
    }

    // حذف مسير الرواتب (إذا كان مسودة فقط) وإرجاع كل شيء لحالته السابقة
    public function destroy(HrPayrollCycle $payroll)
    {
        if ($payroll->status !== 'draft') {
            return back()->with('error', 'لا يمكن حذف مسير رواتب تم اعتماده مسبقاً.');
        }

        // إرجاع حالة المكافآت والعقوبات إلى pending
        HrEmployeeAction::where('hr_payroll_cycle_id', $payroll->id)->update([
            'status' => 'pending',
            'hr_payroll_cycle_id' => null,
        ]);

        // إرجاع حالة الغيابات إلى pending
        HrAbsence::where('hr_payroll_cycle_id', $payroll->id)->update([
            'status' => 'pending',
            'hr_payroll_cycle_id' => null,
        ]);

        // فك ارتباط الإجازات بدون راتب
        HrLeaveRequest::where('hr_payroll_cycle_id', $payroll->id)->update([
            'hr_payroll_cycle_id' => null,
        ]);

        // إرجاع حالة الإضافي إلى pending
        HrOvertime::where('hr_payroll_cycle_id', $payroll->id)->update([
            'status' => 'pending',
            'hr_payroll_cycle_id' => null,
        ]);

        // إرجاع أقساط السلف
        foreach ($payroll->payrolls as $slip) {
            if ($slip->loan_deduction > 0) {
                // البحث عن السلفة النشطة أو المكتملة حديثاً لنفس الموظف
                $loan = HrLoan::where('hr_employee_id', $slip->hr_employee_id)
                    ->orderBy('id', 'desc')->first();
                if ($loan) {
                    $loan->revertInstallment($slip->loan_deduction);
                }
            }
        }

        // سجلات الرواتب الفرعية (HrPayroll) ستحذف تلقائياً إذا كان هناك cascadeOnDelete
        // أو نقوم بحذفها يدوياً لتأكيد ذلك
        $payroll->payrolls()->delete();

        // حذف الدورة نفسها
        $payroll->delete();

        return redirect()->route('hr.payrolls.index')->with('success', 'تم حذف مسودة مسير الرواتب بنجاح وإعادة جميع الاستحقاقات والخصومات لحالة الانتظار.');
    }

    /**
     * طباعة قسيمة الراتب الفردية للموظف (Payslip)
     */
    public function printSlip(HrPayroll $slip)
    {
        $slip->load(['employee.department', 'cycle', 'payrollCycle']);
        $hospital = \App\Models\Hospital::first();

        return view('hr.payrolls.payslip', compact('slip', 'hospital'));
    }
}
