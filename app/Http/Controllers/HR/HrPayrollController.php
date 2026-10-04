<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrPayrollCycle;
use App\Models\HrPayroll;
use App\Models\HrEmployee;
use App\Models\HrEmployeeAction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HrPayrollController extends Controller
{
    public function index()
    {
        $cycles = HrPayrollCycle::orderBy('cycle_month', 'desc')->get();
        return view('hr.payrolls.index', compact('cycles'));
    }

    public function create()
    {
        return view('hr.payrolls.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'cycle_month' => 'required|date_format:Y-m|unique:hr_payroll_cycles,cycle_month',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            DB::beginTransaction();

            $cycle = HrPayrollCycle::create([
                'cycle_month' => $request->cycle_month,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            // جلب الموظفين الفعالين
            $activeEmployees = HrEmployee::where('status', 'active')->get();

            foreach ($activeEmployees as $employee) {
                // حساب إجمالي المكافآت والعقوبات غير المرحلة
                $pendingActions = HrEmployeeAction::where('hr_employee_id', $employee->id)
                    ->where('status', 'pending')
                    ->where('action_date', '<=', $request->end_date)
                    ->get();

                $bonuses = 0;
                $penalties = 0;

                foreach ($pendingActions as $action) {
                    if ($action->financial_amount > 0) {
                        $bonuses += $action->financial_amount;
                    } else {
                        $penalties += abs($action->financial_amount);
                    }
                    
                    // تحديث الإجراء لربطه بهذه الدورة
                    $action->update([
                        'hr_payroll_cycle_id' => $cycle->id,
                        'status' => 'processed'
                    ]);
                }

                $netSalary = $employee->basic_salary + $bonuses - $penalties; // المخصصات تحسب لاحقاً من الواجهة أو يمكن إضافتها هنا إن كانت ثابتة

                HrPayroll::create([
                    'hr_payroll_cycle_id' => $cycle->id,
                    'hr_employee_id' => $employee->id,
                    'basic_salary' => $employee->basic_salary,
                    'allowances' => 0,
                    'bonuses_amount' => $bonuses,
                    'penalties_amount' => $penalties,
                    'net_salary' => $netSalary,
                    'status' => 'pending'
                ]);
            }

            DB::commit();

            return redirect()->route('hr.payrolls.show', $cycle->id)->with('success', 'تم توليد مسير الرواتب بنجاح، يمكنك الآن تعديل المخصصات لكل موظف.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء توليد مسير الرواتب: ' . $e->getMessage());
        }
    }

    public function show(HrPayrollCycle $payroll)
    {
        $cycle = $payroll->load(['payrolls.employee.department']);
        return view('hr.payrolls.show', compact('cycle'));
    }

    public function updateSlip(Request $request, HrPayroll $slip)
    {
        $request->validate([
            'allowances' => 'required|numeric|min:0',
        ]);

        $allowances = $request->allowances;
        
        $netSalary = $slip->basic_salary + $allowances + $slip->bonuses_amount - $slip->penalties_amount;

        $slip->update([
            'allowances' => $allowances,
            'net_salary' => $netSalary
        ]);

        return response()->json([
            'success' => true,
            'net_salary' => $netSalary,
            'message' => 'تم تحديث الراتب بنجاح'
        ]);
    }

    public function approve(HrPayrollCycle $payroll)
    {
        $payroll->update(['status' => 'approved']);
        return redirect()->route('hr.payrolls.show', $payroll->id)->with('success', 'تم اعتماد مسير الرواتب.');
    }
}
