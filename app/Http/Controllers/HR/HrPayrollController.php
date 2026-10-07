<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrEmployee;
use App\Models\HrPayroll;
use App\Models\HrPayrollCycle;
use App\Models\HrEmployeeAction;
use App\Models\HrAbsence;
use App\Models\HrOvertime;
use App\Models\HrLoan;
use Illuminate\Http\Request;

class HrPayrollController extends Controller
{
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
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
        ]);

        // تأكد أنه لا يوجد مسير لنفس الشهر
        if (HrPayrollCycle::where('cycle_month', $request->cycle_month)->exists()) {
            return back()->with('error', 'يوجد مسير رواتب لهذا الشهر بالفعل. لا يمكن إنشاء مسير مكرر.');
        }

        $cycle = HrPayrollCycle::create([
            'cycle_month' => $request->cycle_month,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'status'      => 'draft',
            'created_by'  => auth()->id(),
        ]);

        $employees = HrEmployee::where('status', 'active')
            ->with(['activeAllowances', 'activeLoans', 'actions', 'absences', 'overtimes'])
            ->get();

        foreach ($employees as $emp) {

            // ① المخصصات الثابتة
            $totalAllowances = $emp->activeAllowances->sum('amount');

            // ② العقوبات والمكافآت (pending)
            $pendingActions = HrEmployeeAction::where('hr_employee_id', $emp->id)
                ->where('status', 'pending')->get();
            $bonuses   = $pendingActions->where('financial_amount', '>', 0)->sum('financial_amount');
            $penalties = abs($pendingActions->where('financial_amount', '<', 0)->sum('financial_amount'));

            // ③ الغيابات (pending)
            $pendingAbsences = HrAbsence::where('hr_employee_id', $emp->id)
                ->where('status', 'pending')
                ->whereBetween('absence_date', [$request->start_date, $request->end_date])
                ->get();
            $absenceDeduction = $pendingAbsences->sum('deduction_amount');

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
            if ($emp->subject_to_social_security) {
                $socialSecurity = round($emp->basic_salary * ($emp->social_security_percentage / 100), 2);
            }

            // ⑦ الضريبة
            $tax = 0;
            if ($emp->subject_to_tax) {
                $tax = round($emp->basic_salary * ($emp->tax_percentage / 100), 2);
            }

            // ⑧ الراتب الصافي النهائي
            $netSalary = $emp->basic_salary
                + $totalAllowances
                + $bonuses
                + $overtimeAmount
                - $penalties
                - $absenceDeduction
                - $loanDeduction
                - $socialSecurity
                - $tax;

            // إنشاء سجل الراتب
            $slip = HrPayroll::create([
                'hr_payroll_cycle_id'    => $cycle->id,
                'hr_employee_id'         => $emp->id,
                'basic_salary'           => $emp->basic_salary,
                'allowances'             => $totalAllowances,
                'bonuses_amount'         => $bonuses,
                'penalties_amount'       => $penalties,
                'overtime_amount'        => $overtimeAmount,
                'absence_deduction'      => $absenceDeduction,
                'loan_deduction'         => $loanDeduction,
                'social_security_amount' => $socialSecurity,
                'tax_amount'             => $tax,
                'net_salary'             => max(0, $netSalary),
                'payment_method'         => $emp->payment_method ?? 'cash',
                'status'                 => 'draft',
            ]);

            // ترحيل العقوبات/المكافآت
            $pendingActions->each(fn($a) => $a->update([
                'status'               => 'processed',
                'hr_payroll_cycle_id'  => $cycle->id,
            ]));

            // ترحيل الغيابات
            $pendingAbsences->each(fn($a) => $a->update([
                'status'              => 'processed',
                'hr_payroll_cycle_id' => $cycle->id,
            ]));

            // ترحيل الإضافي
            $pendingOvertimes->each(fn($o) => $o->update([
                'status'              => 'processed',
                'hr_payroll_cycle_id' => $cycle->id,
            ]));

            // خصم قسط السلفة
            if ($activeLoan && $loanDeduction > 0) {
                $activeLoan->payInstallment($loanDeduction);
            }
        }

        return redirect()->route('hr.payrolls.show', $cycle->id)
            ->with('success', 'تم توليد مسير رواتب شهر ' . $request->cycle_month . ' بنجاح لـ ' . $employees->count() . ' موظف.');
    }

    // عرض تفاصيل مسير الرواتب
    public function show(HrPayrollCycle $payroll)
    {
        $payroll->load(['payrolls.employee.department', 'payrolls.employee']);

        $summary = [
            'total_basic'          => $payroll->payrolls->sum('basic_salary'),
            'total_allowances'     => $payroll->payrolls->sum('allowances'),
            'total_bonuses'        => $payroll->payrolls->sum('bonuses_amount'),
            'total_overtime'       => $payroll->payrolls->sum('overtime_amount'),
            'total_penalties'      => $payroll->payrolls->sum('penalties_amount'),
            'total_absence'        => $payroll->payrolls->sum('absence_deduction'),
            'total_loans'          => $payroll->payrolls->sum('loan_deduction'),
            'total_social'         => $payroll->payrolls->sum('social_security_amount'),
            'total_tax'            => $payroll->payrolls->sum('tax_amount'),
            'total_net'            => $payroll->payrolls->sum('net_salary'),
            'cash_count'           => $payroll->payrolls->where('payment_method', 'cash')->count(),
            'bank_count'           => $payroll->payrolls->where('payment_method', 'bank')->count(),
            'total_cash_amount'    => $payroll->payrolls->where('payment_method', 'cash')->sum('net_salary'),
            'total_bank_amount'    => $payroll->payrolls->where('payment_method', 'bank')->sum('net_salary'),
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

        return back()->with('success', 'تم اعتماد مسير رواتب شهر ' . $payroll->cycle_month . ' بنجاح، وتم إرسال الإشعار لقسم الحسابات.');
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
            + $slip->allowances
            + $slip->bonuses_amount
            + $slip->overtime_amount
            - $slip->penalties_amount
            - $slip->absence_deduction
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

}
