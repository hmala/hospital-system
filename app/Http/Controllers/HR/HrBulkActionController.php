<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HrActionBatch;
use App\Models\HrActionSetting;
use App\Models\HrEmployee;
use App\Models\HrEmployeeAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrBulkActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage hr bulk actions|manage hr actions|approve hr action cancellation')->only(['index', 'show']);
        $this->middleware('permission:manage hr bulk actions|manage hr actions')->only(['store', 'cancelBatch', 'requestCancelBatch']);
        $this->middleware('permission:approve hr action cancellation')->only(['approveCancelBatch', 'rejectCancelBatch']);
    }

    public function index()
    {
        $departments = Department::orderBy('name')->get();
        $actionSettings = HrActionSetting::where('is_active', true)->orderBy('category')->get();
        $activeEmployees = HrEmployee::active()->with('department')->orderBy('full_name')->get();

        $batches = HrActionBatch::with(['department', 'actionSetting', 'creator', 'cancelledBy', 'cancelRequester'])
            ->withCount([
                'actions',
                'actions as pending_actions_count' => fn ($q) => $q->where('status', 'pending'),
                'actions as cancelled_actions_count' => fn ($q) => $q->where('status', 'cancelled'),
            ])
            ->latest()
            ->paginate(15);

        $pendingIndividualRequests = HrEmployeeAction::with(['employee.department', 'actionSetting', 'cancelRequester', 'batch'])
            ->where('status', 'pending')
            ->whereNotNull('cancel_requested_at')
            ->latest('cancel_requested_at')
            ->get();

        $pendingBatchRequests = HrActionBatch::with(['department', 'actionSetting', 'cancelRequester'])
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('cancel_requested_at')
            ->latest('cancel_requested_at')
            ->get();

        return view('hr.bulk_actions.index', compact(
            'departments',
            'actionSettings',
            'activeEmployees',
            'batches',
            'pendingIndividualRequests',
            'pendingBatchRequests'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scope' => 'required|in:hospital,department,selected',
            'department_id' => 'required_if:scope,department|nullable|exists:departments,id',
            'employee_ids' => 'required_if:scope,selected|nullable|array|min:1',
            'employee_ids.*' => 'exists:hr_employees,id',
            'hr_action_setting_id' => 'required|exists:hr_action_settings,id',
            'action_date' => 'required|date',
            'reason' => 'required|string|max:1000',
        ], [
            'department_id.required_if' => 'يرجى اختيار القسم المستهدف.',
            'employee_ids.required_if' => 'يرجى تحديد موظف واحد على الأقل.',
            'hr_action_setting_id.required' => 'يرجى اختيار نوع العقوبة أو المكافأة من اللائحة.',
            'reason.required' => 'يرجى كتابة سبب تطبيق القرار.',
        ]);

        $setting = HrActionSetting::findOrFail($validated['hr_action_setting_id']);

        $employeesQuery = HrEmployee::where('status', 'active');
        if ($validated['scope'] === 'department') {
            $employeesQuery->where('department_id', $validated['department_id']);
        } elseif ($validated['scope'] === 'selected') {
            $employeesQuery->whereIn('id', $validated['employee_ids'] ?? []);
        }

        $employees = $employeesQuery->get();
        if ($employees->isEmpty()) {
            return back()->with('error', 'لا يوجد موظفون نشطون ضمن النطاق المحدد لتطبيق الإجراء عليهم.');
        }

        $deadline = now()->addHours(48);

        $batch = DB::transaction(function () use ($validated, $setting, $employees, $deadline) {
            $batch = HrActionBatch::create([
                'scope' => $validated['scope'],
                'department_id' => $validated['scope'] === 'department' ? $validated['department_id'] : null,
                'hr_action_setting_id' => $setting->id,
                'action_date' => $validated['action_date'],
                'reason' => $validated['reason'],
                'target_count' => $employees->count(),
                'status' => 'active',
                'created_by' => auth()->id(),
                'cancellable_until' => $deadline,
            ]);

            foreach ($employees as $employee) {
                $financialAmount = $this->calculateFinancialAmount($setting, $employee);

                HrEmployeeAction::create([
                    'hr_employee_id' => $employee->id,
                    'hr_action_setting_id' => $setting->id,
                    'hr_action_batch_id' => $batch->id,
                    'action_date' => $validated['action_date'],
                    'reason' => $validated['reason'],
                    'applied_effect_type' => $setting->effect_type,
                    'applied_effect_value' => $setting->effect_value,
                    'financial_amount' => $financialAmount,
                    'status' => 'pending',
                    'cancellable_until' => $deadline,
                    'created_by' => auth()->id(),
                ]);
            }

            return $batch;
        });

        return redirect()->route('hr.bulk-actions.show', $batch->id)
            ->with('success', "تم تطبيق الإجراء '{$setting->title}' جماعياً على ({$employees->count()}) موظف بنجاح. الإجراء ساري المفعول ومتاح للإلغاء المباشر خلال 48 ساعة.");
    }

    public function show(HrActionBatch $batch)
    {
        $batch->load([
            'department',
            'actionSetting',
            'creator',
            'cancelRequester',
            'cancelledBy',
            'actions.employee.department',
            'actions.cancelledBy',
            'actions.cancelRequester',
            'actions.cancelApprover',
            'actions.payrollCycle',
        ]);

        return view('hr.bulk_actions.show', compact('batch'));
    }

    public function cancelBatch(Request $request, HrActionBatch $batch)
    {
        $isAdminApprover = auth()->user()->can('approve hr action cancellation');
        if (! $batch->isWithin48Hours() && ! $isAdminApprover) {
            return back()->with('error', 'انتهت مهلة الـ 48 ساعة للإلغاء المباشر. يرجى رفع طلب إلغاء لاعتماده من مدير الإدارة.');
        }

        $pendingActions = $batch->actions()->where('status', 'pending')->get();
        if ($pendingActions->isEmpty()) {
            return back()->with('error', 'لا توجد سجلات معلقة قابلة للإلغاء في هذا القرار (قد تكون مُلغاة أو مُرحّلة للرواتب).');
        }

        $reason = $request->input('cancellation_reason') ?: 'إلغاء القرار الجماعي بالكامل';

        DB::transaction(function () use ($batch, $pendingActions, $reason, $isAdminApprover) {
            $extra = (! $batch->isWithin48Hours() && $isAdminApprover)
                ? ['cancel_approved_by' => auth()->id(), 'cancel_approved_at' => now()]
                : [];

            foreach ($pendingActions as $action) {
                $action->update(array_merge([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                ], $extra));
            }

            $batch->update([
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $batch->refreshStatusFromActions();
        });

        return back()->with('success', "تم إلغاء القرار الجماعي ({$pendingActions->count()} سجل) بنجاح مع حفظه في الأرشيف.");
    }

    public function requestCancelBatch(Request $request, HrActionBatch $batch)
    {
        $request->validate([
            'cancel_request_reason' => 'required|string|max:1000',
        ], [
            'cancel_request_reason.required' => 'يرجى كتابة سبب طلب إلغاء القرار الجماعي.',
        ]);

        if (! $batch->actions()->where('status', 'pending')->exists()) {
            return back()->with('error', 'لا توجد سجلات معلقة في هذا القرار.');
        }

        $batch->update([
            'cancel_requested_by' => auth()->id(),
            'cancel_requested_at' => now(),
            'cancel_request_reason' => $request->cancel_request_reason,
        ]);

        return back()->with('success', 'تم إرسال طلب إلغاء القرار الجماعي إلى مدير الإدارة للموافقة.');
    }

    public function approveCancelBatch(Request $request, HrActionBatch $batch)
    {
        $pendingActions = $batch->actions()->where('status', 'pending')->get();
        if ($pendingActions->isEmpty()) {
            return back()->with('error', 'لا توجد سجلات معلقة في هذا القرار.');
        }

        $reason = $batch->cancel_request_reason ?: ($request->input('cancellation_reason') ?: 'موافقة مدير الإدارة على إلغاء القرار الجماعي');

        DB::transaction(function () use ($batch, $pendingActions, $reason) {
            foreach ($pendingActions as $action) {
                $action->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                    'cancel_approved_by' => auth()->id(),
                    'cancel_approved_at' => now(),
                ]);
            }

            $batch->update([
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $batch->refreshStatusFromActions();
        });

        return back()->with('success', 'تمت موافقة مدير الإدارة وإلغاء القرار الجماعي بنجاح.');
    }

    public function rejectCancelBatch(HrActionBatch $batch)
    {
        $batch->update([
            'cancel_requested_by' => null,
            'cancel_requested_at' => null,
            'cancel_request_reason' => null,
        ]);

        return back()->with('success', 'تم رفض طلب إلغاء القرار الجماعي وبقي سارياً.');
    }

    private function calculateFinancialAmount(HrActionSetting $setting, HrEmployee $employee): float
    {
        $amount = 0.0;
        if ($setting->effect_type === 'amount') {
            $amount = (float) $setting->effect_value;
        } elseif ($setting->effect_type === 'days') {
            $dayValue = ((float) $employee->basic_salary) / 30;
            $amount = $dayValue * (float) $setting->effect_value;
        } elseif ($setting->effect_type === 'percentage') {
            $amount = ((float) $employee->basic_salary) * ((float) $setting->effect_value / 100);
        }

        if ($setting->category === 'penalty' && $amount > 0) {
            $amount = -$amount;
        }

        return round($amount, 2);
    }
}
