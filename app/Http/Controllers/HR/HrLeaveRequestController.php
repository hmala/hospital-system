<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HrEmployee;
use App\Models\HrLeaveRequest;
use App\Models\HrSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrLeaveRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * بوابة الموظف الشخصية: طلبات إجازاتي
     */
    public function myLeaves()
    {
        $user = auth()->user();
        $employee = HrEmployee::with(['department.manager'])->where('user_id', $user->id)->first();

        $requests = $employee
            ? HrLeaveRequest::with(['department', 'deptManager', 'adminApprover'])
                ->where('hr_employee_id', $employee->id)
                ->latest()
                ->paginate(15)
            : collect();

        return view('hr.leaves.my_leaves', compact('employee', 'requests'));
    }

    /**
     * بوابة الموظف الشخصية: جدولي الشهري
     */
    public function myRoster(Request $request)
    {
        $user = auth()->user();
        $employee = HrEmployee::with(['department', 'defaultShift', 'secondShift'])
            ->where('user_id', $user->id)
            ->first();

        $month = $request->get('month', now()->format('Y-m'));
        $startOfMonth = Carbon::parse($month.'-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        $schedules = $employee
            ? HrSchedule::with(['shift', 'leaveRequest', 'rosterApproval'])
                ->where('hr_employee_id', $employee->id)
                ->whereBetween('shift_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                ->orderBy('shift_date')
                ->get()
            : collect();

        $estimatedCallsCount = 0;
        $estimatedCallsAmount = 0;
        if ($employee && $employee->isPerCall()) {
            foreach ($schedules as $sch) {
                if (! $sch->is_off_day && ! $sch->is_leave && $sch->attendance_status !== 'absent' && $sch->shift) {
                    $estimatedCallsCount++;
                    $estimatedCallsAmount += $employee->resolveCallRateForShift($sch->shift);
                }
            }
        }

        return view('hr.leaves.my_roster', compact(
            'employee',
            'schedules',
            'startOfMonth',
            'endOfMonth',
            'estimatedCallsCount',
            'estimatedCallsAmount'
        ));
    }

    /**
     * تقديم طلب إجازة إلكتروني من حساب الموظف
     */
    public function submitMyLeave(Request $request)
    {
        $user = auth()->user();
        $employee = HrEmployee::with('department')->where('user_id', $user->id)->first();

        if (! $employee) {
            return back()->with('error', 'حسابك غير مرتبط بملف موظف في الموارد البشرية. يرجى مراجعة HR لربط الحساب.');
        }

        $validated = $request->validate([
            'leave_type' => 'required|in:annual,sick,emergency,unpaid,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
        ], [
            'end_date.after_or_equal' => 'تاريخ نهاية الإجازة يجب أن يكون مساوياً أو بعد تاريخ البداية.',
            'reason.required' => 'يرجى كتابة سبب طلب الإجازة.',
        ]);

        $leaveRequest = $this->createLeaveRecord($employee, $validated, $user->id);

        $msg = $leaveRequest->status === 'pending_admin'
            ? 'تم تقديم طلب الإجازة بنجاح وإرساله مباشرة لمدير الإدارة / HR للاعتماد.'
            : 'تم تقديم طلب الإجازة بنجاح وإرساله إلى مدير القسم للمراجعة.';

        return redirect()->route('my.leaves.index')->with('success', $msg);
    }

    public function cancelMyLeave(HrLeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        $employee = HrEmployee::where('user_id', $user->id)->first();

        if (! $employee || $leaveRequest->hr_employee_id !== $employee->id) {
            abort(403, 'غير مصرح لك بإلغاء هذا الطلب.');
        }

        if (! in_array($leaveRequest->status, ['pending_department', 'pending_admin'], true)) {
            return back()->with('error', 'لا يمكن إلغاء طلب إجازة تم البت فيه نهائياً.');
        }

        $leaveRequest->update(['status' => 'cancelled']);

        return back()->with('success', 'تم إلغاء طلب الإجازة بنجاح.');
    }

    /**
     * لوحة إدارة واعتماد الإجازات (مدير القسم / HR / مدير الإدارة)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $canViewAll = $user->isAdminDirector()
            || $user->hasRole('hr_manager')
            || $user->can('approve admin leaves')
            || $user->can('manage hr actions');
        $managedDeptIds = $user->managedDepartmentIds();
        $isDeptManager = ! empty($managedDeptIds) || $user->can('approve department leaves');

        if (! $canViewAll && ! $isDeptManager && ! $user->can('view hr')) {
            abort(403, 'غير مصرح لك بالوصول لإدارة الإجازات.');
        }

        $query = HrLeaveRequest::with(['employee.department', 'department.manager', 'deptManager', 'adminApprover', 'creator']);

        if (! $canViewAll) {
            if (! empty($managedDeptIds)) {
                $query->whereIn('department_id', $managedDeptIds);
            } else {
                // إذا لديه صلاحية approve department leaves بدون تعيينه على قسم محدد
                $empDeptId = HrEmployee::where('user_id', $user->id)->value('department_id');
                $empDeptId ? $query->where('department_id', $empDeptId) : $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('department_id') && $canViewAll) {
            $query->where('department_id', $request->department_id);
        }

        $leaveRequests = $query->latest()->paginate(20)->withQueryString();

        $departments = $canViewAll
            ? Department::orderBy('name')->get()
            : Department::whereIn('id', $managedDeptIds)->orderBy('name')->get();

        $employeesForProxy = HrEmployee::active()
            ->when(! $canViewAll && ! empty($managedDeptIds), fn ($q) => $q->whereIn('department_id', $managedDeptIds))
            ->with('department')
            ->orderBy('full_name')
            ->get();

        return view('hr.leaves.index', compact(
            'leaveRequests',
            'departments',
            'employeesForProxy',
            'canViewAll',
            'managedDeptIds'
        ));
    }

    /**
     * رفع طلب إجازة بالنيابة عن موظف (من مدير القسم أو HR)
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $canViewAll = $user->isAdminDirector() || $user->hasRole('hr_manager') || $user->can('manage hr actions');
        $managedDeptIds = $user->managedDepartmentIds();

        if (! $canViewAll && empty($managedDeptIds) && ! $user->can('approve department leaves')) {
            abort(403, 'غير مصرح لك برفع طلب إجازة.');
        }

        $validated = $request->validate([
            'hr_employee_id' => 'required|exists:hr_employees,id',
            'leave_type' => 'required|in:annual,sick,emergency,unpaid,other',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
        ]);

        $employee = HrEmployee::with('department')->findOrFail($validated['hr_employee_id']);

        if (! $canViewAll && ! in_array($employee->department_id, $managedDeptIds, true)) {
            abort(403, 'يمكنك رفع طلبات إجازة لموظفي قسمك فقط.');
        }

        $leaveRequest = $this->createLeaveRecord($employee, $validated, $user->id);

        return redirect()->route('hr.leaves.index')
            ->with('success', "تم تسجيل طلب الإجازة للموظف '{$employee->full_name}' بحالة: {$leaveRequest->status_label}");
    }

    /**
     * موافقة مدير القسم (وتحديد تصنيف الإجازة: اعتيادية/مرضية/براتب/بدون راتب)
     */
    public function deptApprove(Request $request, HrLeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        $this->authorizeDepartmentDecision($user, $leaveRequest);

        if ($leaveRequest->status !== 'pending_department') {
            return back()->with('error', 'هذا الطلب ليس في مرحلة موافقة مدير القسم.');
        }

        $validated = $request->validate([
            'leave_type' => 'required|in:annual,sick,emergency,unpaid,other',
            'is_paid' => 'required|boolean',
            'dept_notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['leave_type'] === 'unpaid') {
            $validated['is_paid'] = false;
        }

        $leaveRequest->update([
            'leave_type' => $validated['leave_type'],
            'is_paid' => (bool) $validated['is_paid'],
            'status' => 'pending_admin',
            'dept_manager_id' => $user->id,
            'dept_decision_at' => now(),
            'dept_notes' => $validated['dept_notes'] ?? null,
        ]);

        return back()->with('success', 'تمت موافقة مدير القسم بنجاح، وتم تحويل الطلب إلى مدير الإدارة للاعتماد النهائي.');
    }

    public function deptReject(Request $request, HrLeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        $this->authorizeDepartmentDecision($user, $leaveRequest);

        if ($leaveRequest->status !== 'pending_department') {
            return back()->with('error', 'هذا الطلب ليس في مرحلة موافقة مدير القسم.');
        }

        $request->validate(['dept_notes' => 'nullable|string|max:1000']);

        $leaveRequest->update([
            'status' => 'rejected',
            'dept_manager_id' => $user->id,
            'dept_decision_at' => now(),
            'dept_notes' => $request->dept_notes ?: 'تم الرفض من قبل مدير القسم',
        ]);

        return back()->with('success', 'تم رفض طلب الإجازة من قبل مدير القسم.');
    }

    /**
     * الاعتماد النهائي لمدير الإدارة (أو مدير HR للحالات المباشرة لمديري الأقسام / الأقسام بدون مدير)
     */
    public function adminApprove(Request $request, HrLeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        $this->authorizeFinalDecision($user, $leaveRequest);

        if (! in_array($leaveRequest->status, ['pending_admin', 'pending_department'], true)) {
            return back()->with('error', 'لا يمكن اعتماد هذا الطلب في حالته الحالية.');
        }

        $validated = $request->validate([
            'leave_type' => 'nullable|in:annual,sick,emergency,unpaid,other',
            'is_paid' => 'nullable|boolean',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $leaveType = $validated['leave_type'] ?? $leaveRequest->leave_type;
        $isPaid = isset($validated['is_paid']) ? (bool) $validated['is_paid'] : $leaveRequest->is_paid;
        if ($leaveType === 'unpaid') {
            $isPaid = false;
        }

        DB::transaction(function () use ($leaveRequest, $user, $leaveType, $isPaid, $validated) {
            $leaveRequest->update([
                'leave_type' => $leaveType,
                'is_paid' => $isPaid,
                'status' => 'approved',
                'admin_approver_id' => $user->id,
                'admin_decision_at' => now(),
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);

            // مزامنة أيام الإجازة المعتمدة في جدول الدوام (hr_schedules)
            $start = Carbon::parse($leaveRequest->start_date);
            $end = Carbon::parse($leaveRequest->end_date);
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $dStr = $date->format('Y-m-d');
                HrSchedule::where('hr_employee_id', $leaveRequest->hr_employee_id)
                    ->whereDate('shift_date', $dStr)
                    ->delete();

                HrSchedule::create([
                    'hr_employee_id' => $leaveRequest->hr_employee_id,
                    'hr_shift_id' => null,
                    'shift_date' => $dStr,
                    'is_off_day' => false,
                    'is_leave' => true,
                    'hr_leave_request_id' => $leaveRequest->id,
                    'attendance_status' => 'pending',
                    'notes' => $leaveRequest->leave_type_label,
                    'created_by' => $user->id,
                ]);
            }
        });

        return back()->with('success', 'تم الاعتماد النهائي للإجازة وتثبيت أيامها في جدول الدوام (الروستر) بنجاح.');
    }

    public function adminReject(Request $request, HrLeaveRequest $leaveRequest)
    {
        $user = auth()->user();
        $this->authorizeFinalDecision($user, $leaveRequest);

        if (! in_array($leaveRequest->status, ['pending_admin', 'pending_department'], true)) {
            return back()->with('error', 'لا يمكن رفض هذا الطلب في حالته الحالية.');
        }

        $request->validate(['admin_notes' => 'nullable|string|max:1000']);

        $leaveRequest->update([
            'status' => 'rejected',
            'admin_approver_id' => $user->id,
            'admin_decision_at' => now(),
            'admin_notes' => $request->admin_notes ?: 'تم الرفض من قبل الإدارة',
        ]);

        return back()->with('success', 'تم رفض طلب الإجازة.');
    }

    private function createLeaveRecord(HrEmployee $employee, array $validated, int $actorUserId): HrLeaveRequest
    {
        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $daysCount = $start->diffInDays($end) + 1;

        $dept = $employee->department;
        $deptManagerId = $dept?->manager_id;

        // إذا كان مقدم الطلب هو مدير القسم نفسه، أو القسم بلا مدير معين => ينتقل مباشرة إلى pending_admin (لمدير HR أو مدير الإدارة)
        $isEmployeeDeptManager = $deptManagerId && $employee->user_id && (int) $deptManagerId === (int) $employee->user_id;
        $initialStatus = (! $deptManagerId || $isEmployeeDeptManager) ? 'pending_admin' : 'pending_department';

        return HrLeaveRequest::create([
            'hr_employee_id' => $employee->id,
            'user_id' => $employee->user_id ?: $actorUserId,
            'department_id' => $employee->department_id,
            'leave_type' => $validated['leave_type'],
            'is_paid' => $validated['leave_type'] !== 'unpaid',
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'days_count' => $daysCount,
            'reason' => $validated['reason'],
            'status' => $initialStatus,
            'created_by' => $actorUserId,
        ]);
    }

    private function authorizeDepartmentDecision($user, HrLeaveRequest $leaveRequest): void
    {
        if ($user->isAdminDirector()) {
            return;
        }

        $isAssignedDeptManager = $leaveRequest->department && (int) $leaveRequest->department->manager_id === (int) $user->id;
        $hasDeptPerm = $user->can('approve department leaves') && in_array($leaveRequest->department_id, $user->managedDepartmentIds(), true);

        if (! $isAssignedDeptManager && ! $hasDeptPerm) {
            abort(403, 'غير مصرح لك باتخاذ قرار مدير القسم لهذا الطلب.');
        }
    }

    private function authorizeFinalDecision($user, HrLeaveRequest $leaveRequest): void
    {
        if ($user->isAdminDirector() || $user->can('approve admin leaves')) {
            return;
        }

        // استثناء مدير القسم أو القسم بلا مدير: يحق لمدير HR الموافقة عليه
        $deptManagerId = $leaveRequest->department?->manager_id;
        $isDeptManagerLeave = $deptManagerId && $leaveRequest->employee?->user_id && (int) $deptManagerId === (int) $leaveRequest->employee->user_id;
        $isHrManager = $user->hasRole('hr_manager') || $user->can('manage hr actions');

        if ($isHrManager && (! $deptManagerId || $isDeptManagerLeave)) {
            return;
        }

        abort(403, 'الاعتماد النهائي للإجازات محصور بمدير الإدارة (أو مدير HR لإجازات مديري الأقسام).');
    }
}
