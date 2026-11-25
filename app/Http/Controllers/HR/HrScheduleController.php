<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HrEmployee;
use App\Models\HrRosterApproval;
use App\Models\HrSchedule;
use App\Models\HrShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HrScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage hr schedules|manage department schedules|final approve hr schedules')
            ->except(['finalApprove', 'reopenApproval']);
        $this->middleware('permission:final approve hr schedules|manage hr schedules')
            ->only(['finalApprove', 'reopenApproval']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $canManageAll = $this->canManageAllDepartments($user);
        $canFinalApprove = $user->isAdminDirector() || $user->can('final approve hr schedules') || $user->can('manage hr schedules');
        $managedDeptIds = $user->managedDepartmentIds();

        if ($canManageAll) {
            $departments = Department::orderBy('name')->get();
            $departmentId = $request->get('department_id');
        } else {
            $departments = Department::whereIn('id', $managedDeptIds)->orderBy('name')->get();
            $departmentId = $request->get('department_id') ?: ($departments->first()?->id);
            if ($departmentId && ! in_array((int) $departmentId, $managedDeptIds, true)) {
                abort(403, 'غير مصرح لك بعرض جدول دوام قسم آخر.');
            }
        }

        $viewMode = $request->get('view_mode', 'week'); // 'week' (7 days) or 'month' (full month)

        if ($request->get('start_date')) {
            $startDate = Carbon::parse($request->get('start_date'));
        } else {
            $startDate = ($viewMode === 'month')
                ? now()->startOfMonth()
                : now()->startOfWeek(Carbon::SUNDAY);
        }

        if ($viewMode === 'month') {
            $startDate = $startDate->copy()->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            $prevDate = $startDate->copy()->subMonth()->startOfMonth()->format('Y-m-d');
            $nextDate = $startDate->copy()->addMonth()->startOfMonth()->format('Y-m-d');
            $currentPeriodDate = now()->startOfMonth()->format('Y-m-d');
        } else {
            $endDate = $startDate->copy()->addDays(6);
            $prevDate = $startDate->copy()->subDays(7)->format('Y-m-d');
            $nextDate = $startDate->copy()->addDays(7)->format('Y-m-d');
            $currentPeriodDate = now()->startOfWeek(Carbon::SUNDAY)->format('Y-m-d');
        }

        $dates = [];
        for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
            $dates[] = clone $date;
        }

        $employeesQuery = HrEmployee::where('status', 'active');
        if ($canManageAll) {
            if ($departmentId) {
                $employeesQuery->where('department_id', $departmentId);
            }
        } else {
            if ($departmentId) {
                $employeesQuery->where('department_id', $departmentId);
            } else {
                $employeesQuery->whereRaw('1 = 0');
            }
        }
        $employees = $employeesQuery->with(['department', 'defaultShift', 'secondShift'])->get();

        $shifts = HrShift::where('is_active', true)->get();

        // Get existing schedules
        $existingSchedules = HrSchedule::whereIn('hr_employee_id', $employees->pluck('id'))
            ->whereDate('shift_date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('shift_date', '<=', $endDate->format('Y-m-d'))
            ->with(['shift', 'leaveRequest'])
            ->get()
            ->groupBy('hr_employee_id');

        // حالة اعتماد الروستر الحالي للقسم والفترة
        $rosterApproval = $departmentId
            ? HrRosterApproval::with(['submittedBy', 'approvedBy', 'department'])
                ->where('department_id', $departmentId)
                ->whereDate('start_date', $startDate->format('Y-m-d'))
                ->whereDate('end_date', $endDate->format('Y-m-d'))
                ->first()
            : null;
        $isRosterLocked = $rosterApproval && $rosterApproval->status === 'approved';

        $pendingApprovals = $canFinalApprove
            ? HrRosterApproval::with(['department', 'submittedBy'])
                ->where('status', 'submitted_by_dept')
                ->latest('submitted_at')
                ->get()
            : collect();

        // Calculate daily shift coverage totals, overall KPI stats, and per-employee Call estimate
        $dailyCoverage = [];
        $employeeCallSummaries = [];
        foreach ($employees as $emp) {
            $employeeCallSummaries[$emp->id] = ['calls' => 0, 'amount' => 0.0];
        }

        $kpiStats = [
            'total_shifts' => 0,
            'total_off' => 0,
            'split_count' => $employees->where('is_split_shift', true)->count(),
            'total_staff' => $employees->count(),
        ];

        foreach ($dates as $date) {
            $dStr = $date->format('Y-m-d');
            $dailyCoverage[$dStr] = [
                'shifts' => [],
                'off' => 0,
                'empty' => 0,
                'total' => 0,
            ];
            foreach ($shifts as $shift) {
                $dailyCoverage[$dStr]['shifts'][$shift->id] = 0;
            }

            foreach ($employees as $emp) {
                $empSchedules = $existingSchedules->get($emp->id, collect());
                $daySchedules = $empSchedules->filter(function ($item) use ($dStr) {
                    return $item->shift_date->format('Y-m-d') === $dStr;
                });

                if ($daySchedules->isNotEmpty()) {
                    $isOff = $daySchedules->contains('is_off_day', true);
                    $isLeave = $daySchedules->contains('is_leave', true);
                    if ($isOff || $isLeave) {
                        $dailyCoverage[$dStr]['off']++;
                        $kpiStats['total_off']++;
                    } else {
                        foreach ($daySchedules as $sch) {
                            if ($sch->hr_shift_id && isset($dailyCoverage[$dStr]['shifts'][$sch->hr_shift_id])) {
                                $dailyCoverage[$dStr]['shifts'][$sch->hr_shift_id]++;
                                $dailyCoverage[$dStr]['total']++;
                                $kpiStats['total_shifts']++;
                            }
                            if ($emp->isPerCall() && $sch->shift && $sch->attendance_status !== 'absent') {
                                $employeeCallSummaries[$emp->id]['calls']++;
                                $employeeCallSummaries[$emp->id]['amount'] += $emp->resolveCallRateForShift($sch->shift);
                            }
                        }
                    }
                } elseif ($emp->schedule_type === 'fixed') {
                    if ($emp->isOffOn($date)) {
                        $dailyCoverage[$dStr]['off']++;
                        $kpiStats['total_off']++;
                    } else {
                        if ($emp->default_shift_id && isset($dailyCoverage[$dStr]['shifts'][$emp->default_shift_id])) {
                            $dailyCoverage[$dStr]['shifts'][$emp->default_shift_id]++;
                            $dailyCoverage[$dStr]['total']++;
                            $kpiStats['total_shifts']++;
                            if ($emp->isPerCall() && $emp->defaultShift) {
                                $employeeCallSummaries[$emp->id]['calls']++;
                                $employeeCallSummaries[$emp->id]['amount'] += $emp->resolveCallRateForShift($emp->defaultShift);
                            }
                        }
                        if ($emp->is_split_shift && $emp->second_shift_id && isset($dailyCoverage[$dStr]['shifts'][$emp->second_shift_id])) {
                            $dailyCoverage[$dStr]['shifts'][$emp->second_shift_id]++;
                            $dailyCoverage[$dStr]['total']++;
                            $kpiStats['total_shifts']++;
                            if ($emp->isPerCall() && $emp->secondShift) {
                                $employeeCallSummaries[$emp->id]['calls']++;
                                $employeeCallSummaries[$emp->id]['amount'] += $emp->resolveCallRateForShift($emp->secondShift);
                            }
                        }
                        if (! $emp->default_shift_id && (! $emp->is_split_shift || ! $emp->second_shift_id)) {
                            $dailyCoverage[$dStr]['empty']++;
                        }
                    }
                } else {
                    $dailyCoverage[$dStr]['empty']++;
                }
            }
        }

        return view('hr.schedules.index', compact(
            'departments', 'departmentId', 'startDate', 'endDate',
            'dates', 'employees', 'shifts', 'existingSchedules',
            'viewMode', 'prevDate', 'nextDate', 'currentPeriodDate', 'dailyCoverage', 'kpiStats',
            'rosterApproval', 'isRosterLocked', 'pendingApprovals', 'employeeCallSummaries',
            'canManageAll', 'canFinalApprove'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'schedules' => 'nullable|array',
        ]);

        $schedules = $request->input('schedules', []);

        // Loop through all submitted schedules and update/create
        foreach ($schedules as $employeeId => $dates) {
            $employee = HrEmployee::find($employeeId);
            if (! $employee) {
                continue;
            }
            $this->authorizeEmployeeDepartment(auth()->user(), $employee);

            foreach ($dates as $date => $shiftValues) {
                $formattedDate = Carbon::parse($date)->format('Y-m-d');

                if ($this->isDateLockedForEmployee($employee, $formattedDate)) {
                    continue;
                }

                // عدم المساس بأيام الإجازات المعتمدة
                $hasApprovedLeave = HrSchedule::where('hr_employee_id', $employeeId)
                    ->whereDate('shift_date', $formattedDate)
                    ->where('is_leave', true)
                    ->exists();
                if ($hasApprovedLeave) {
                    continue;
                }

                $values = is_array($shiftValues) ? $shiftValues : [$shiftValues];

                // Remove existing records for this employee on this date
                HrSchedule::where('hr_employee_id', $employeeId)
                    ->whereDate('shift_date', $formattedDate)
                    ->delete();

                // If 'off' is selected in any slot, mark day as off
                if (in_array('off', $values, true)) {
                    HrSchedule::create([
                        'hr_employee_id' => $employeeId,
                        'shift_date' => $formattedDate,
                        'hr_shift_id' => null,
                        'is_off_day' => true,
                        'created_by' => auth()->id(),
                    ]);

                    continue;
                }

                // Filter valid numeric shift IDs
                $validShiftIds = array_unique(array_filter($values, fn ($v) => ! empty($v) && is_numeric($v)));
                foreach ($validShiftIds as $sId) {
                    HrSchedule::create([
                        'hr_employee_id' => $employeeId,
                        'shift_date' => $formattedDate,
                        'hr_shift_id' => (int) $sId,
                        'is_off_day' => false,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
        }

        return back()->with('success', 'تم حفظ وتحديث جدول الدوام بنجاح.');
    }

    public function copyLastWeek(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $this->authorizeDepartmentFilter(auth()->user(), $request->department_id);

        $currentStartDate = Carbon::parse($request->start_date);
        $currentEndDate = clone $currentStartDate;
        $currentEndDate->addDays(6);

        $lastWeekStartDate = clone $currentStartDate;
        $lastWeekStartDate->subDays(7);
        $lastWeekEndDate = clone $currentEndDate;
        $lastWeekEndDate->subDays(7);

        $employees = $this->scopedEmployeesQuery(auth()->user(), $request->department_id);
        $employeeIds = $employees->pluck('id')->toArray();

        // Get last week's schedules for these employees
        $lastWeekSchedules = HrSchedule::whereIn('hr_employee_id', $employeeIds)
            ->whereBetween('shift_date', [$lastWeekStartDate->format('Y-m-d'), $lastWeekEndDate->format('Y-m-d')])
            ->where('is_leave', false)
            ->get();

        if ($lastWeekSchedules->isEmpty()) {
            return back()->with('error', 'لا يوجد جدول محفوظ في الأسبوع الماضي لنسخه.');
        }

        $count = 0;
        foreach ($lastWeekSchedules as $schedule) {
            $newDate = Carbon::parse($schedule->shift_date)->addDays(7)->format('Y-m-d');
            if ($this->isDateLockedForEmployee($schedule->employee, $newDate)) {
                continue;
            }
            $hasLeave = HrSchedule::where('hr_employee_id', $schedule->hr_employee_id)
                ->whereDate('shift_date', $newDate)
                ->where('is_leave', true)
                ->exists();
            if ($hasLeave) {
                continue;
            }

            HrSchedule::updateOrCreate(
                ['hr_employee_id' => $schedule->hr_employee_id, 'shift_date' => $newDate],
                [
                    'hr_shift_id' => $schedule->hr_shift_id,
                    'is_off_day' => $schedule->is_off_day,
                    'created_by' => auth()->id(),
                ]
            );
            $count++;
        }

        return back()->with('success', "تم نسخ $count شفت من الأسبوع الماضي بنجاح.");
    }

    public function swap(Request $request)
    {
        $request->validate([
            'swap_date' => 'required|date',
            'employee1_id' => 'required|exists:hr_employees,id',
            'employee2_id' => 'required|exists:hr_employees,id|different:employee1_id',
        ]);

        $emp1 = HrEmployee::findOrFail($request->employee1_id);
        $emp2 = HrEmployee::findOrFail($request->employee2_id);
        $this->authorizeEmployeeDepartment(auth()->user(), $emp1);
        $this->authorizeEmployeeDepartment(auth()->user(), $emp2);

        $date = Carbon::parse($request->swap_date)->format('Y-m-d');
        if ($this->isDateLockedForEmployee($emp1, $date) || $this->isDateLockedForEmployee($emp2, $date)) {
            return back()->with('error', 'لا يمكن تبادل خفارات في روستر معتمد ومقفل.');
        }

        $schedule1 = HrSchedule::whereDate('shift_date', $date)->where('hr_employee_id', $request->employee1_id)->first();
        $schedule2 = HrSchedule::whereDate('shift_date', $date)->where('hr_employee_id', $request->employee2_id)->first();

        if (($schedule1 && $schedule1->is_leave) || ($schedule2 && $schedule2->is_leave)) {
            return back()->with('error', 'لا يمكن تبادل يوم مسجل كإجازة معتمدة.');
        }

        $shift1 = $schedule1 ? $schedule1->hr_shift_id : null;
        $off1 = $schedule1 ? $schedule1->is_off_day : false;

        $shift2 = $schedule2 ? $schedule2->hr_shift_id : null;
        $off2 = $schedule2 ? $schedule2->is_off_day : false;

        // Update 1 with 2's data
        if ($schedule1) {
            $schedule1->update(['hr_shift_id' => $shift2, 'is_off_day' => $off2]);
        } else {
            HrSchedule::create(['hr_employee_id' => $request->employee1_id, 'shift_date' => $date, 'hr_shift_id' => $shift2, 'is_off_day' => $off2, 'created_by' => auth()->id()]);
        }

        // Update 2 with 1's data
        if ($schedule2) {
            $schedule2->update(['hr_shift_id' => $shift1, 'is_off_day' => $off1]);
        } else {
            HrSchedule::create(['hr_employee_id' => $request->employee2_id, 'shift_date' => $date, 'hr_shift_id' => $shift1, 'is_off_day' => $off1, 'created_by' => auth()->id()]);
        }

        return back()->with('success', 'تم تبادل الخفارات بنجاح.');
    }

    public function emergencyCoverSearch(Request $request)
    {
        $date = $request->get('date') ? Carbon::parse($request->get('date'))->format('Y-m-d') : null;
        $departmentId = $request->get('department_id');

        if (! $date) {
            return response()->json([]);
        }

        $employees = $this->scopedEmployeesQuery(auth()->user(), $departmentId);

        $allEmployees = $employees->get();
        $schedules = HrSchedule::whereDate('shift_date', $date)
            ->whereIn('hr_employee_id', $allEmployees->pluck('id'))
            ->get()->keyBy('hr_employee_id');

        $available = [];
        foreach ($allEmployees as $emp) {
            $sch = $schedules->get($emp->id);
            // Available if they have NO schedule OR they are explicitly marked as OFF (not on approved leave)
            if (! $sch || ($sch->is_off_day && ! $sch->is_leave)) {
                $available[] = [
                    'id' => $emp->id,
                    'name' => $emp->full_name,
                    'job' => $emp->job_title,
                ];
            }
        }

        return response()->json($available);
    }

    public function autoFillDefaults(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|exists:departments,id',
            'overwrite' => 'nullable|boolean',
        ]);

        $this->authorizeDepartmentFilter(auth()->user(), $request->department_id);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);
        $overwrite = $request->boolean('overwrite', false);

        $employees = $this->scopedEmployeesQuery(auth()->user(), $request->department_id)->get();

        $count = 0;
        for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            foreach ($employees as $emp) {
                if ($this->isDateLockedForEmployee($emp, $dateStr)) {
                    continue;
                }

                $existingRows = HrSchedule::where('hr_employee_id', $emp->id)
                    ->whereDate('shift_date', $dateStr)
                    ->get();
                if ($existingRows->contains('is_leave', true)) {
                    continue;
                }
                $hasExisting = $existingRows->isNotEmpty();

                if (! $overwrite && $hasExisting) {
                    continue;
                }

                if ($emp->schedule_type === 'fixed') {
                    if ($overwrite && $hasExisting) {
                        HrSchedule::where('hr_employee_id', $emp->id)
                            ->whereDate('shift_date', $dateStr)
                            ->delete();
                    }

                    $isOff = $emp->isOffOn($date);
                    if ($isOff) {
                        HrSchedule::create([
                            'hr_employee_id' => $emp->id,
                            'shift_date' => $dateStr,
                            'hr_shift_id' => null,
                            'is_off_day' => true,
                            'created_by' => auth()->id(),
                        ]);
                        $count++;
                    } else {
                        // Slot 1: Default shift
                        if ($emp->default_shift_id) {
                            HrSchedule::create([
                                'hr_employee_id' => $emp->id,
                                'shift_date' => $dateStr,
                                'hr_shift_id' => $emp->default_shift_id,
                                'is_off_day' => false,
                                'created_by' => auth()->id(),
                            ]);
                            $count++;
                        }

                        // Slot 2: Split shift if applicable
                        if ($emp->is_split_shift && $emp->second_shift_id && $emp->second_shift_id != $emp->default_shift_id) {
                            HrSchedule::create([
                                'hr_employee_id' => $emp->id,
                                'shift_date' => $dateStr,
                                'hr_shift_id' => $emp->second_shift_id,
                                'is_off_day' => false,
                                'created_by' => auth()->id(),
                            ]);
                            $count++;
                        }
                    }
                }
            }
        }

        return back()->with('success', "تم تطبيق الدوام الافتراضي تلقائياً لـ ($count) نوبة/يوم عمل بنجاح.");
    }

    public function clearRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $this->authorizeDepartmentFilter(auth()->user(), $request->department_id);

        $startDate = Carbon::parse($request->start_date)->format('Y-m-d');
        $endDate = Carbon::parse($request->end_date)->format('Y-m-d');

        if ($request->department_id && HrRosterApproval::where('department_id', $request->department_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->exists()) {
            return back()->with('error', 'لا يمكن تفريغ روستر معتمد ومقفل للصرف.');
        }

        $employeesQuery = $this->scopedEmployeesQuery(auth()->user(), $request->department_id);
        $employeeIds = $employeesQuery->pluck('id');

        $deleted = HrSchedule::whereIn('hr_employee_id', $employeeIds)
            ->whereDate('shift_date', '>=', $startDate)
            ->whereDate('shift_date', '<=', $endDate)
            ->where('is_leave', false)
            ->delete();

        return back()->with('success', "تم تفريغ وإعادة تعيين جدول الفترة المحددة بنجاح ($deleted سجل).");
    }

    public function updateCell(Request $request)
    {
        $validated = $request->validate([
            'hr_employee_id' => 'required|exists:hr_employees,id',
            'shift_date' => 'required|date',
            'slot' => 'nullable|string',
            'shift_value' => 'nullable|string',
            'other_value' => 'nullable|string',
        ]);

        $employeeId = $validated['hr_employee_id'];
        $employee = HrEmployee::findOrFail($employeeId);
        $this->authorizeEmployeeDepartment(auth()->user(), $employee);

        $date = Carbon::parse($validated['shift_date'])->format('Y-m-d');

        if ($this->isDateLockedForEmployee($employee, $date)) {
            return response()->json(['success' => false, 'message' => 'الروستر معتمد ومقفل للصرف ولا يمكن تعديله.'], 403);
        }

        if (HrSchedule::where('hr_employee_id', $employeeId)->whereDate('shift_date', $date)->where('is_leave', true)->exists()) {
            return response()->json(['success' => false, 'message' => 'هذا اليوم مسجل كإجازة معتمدة للموظف.'], 422);
        }

        $shiftValue = $validated['shift_value'];
        $slot = $request->input('slot', 'slot1');
        $otherValue = $request->input('other_value');

        // Collect distinct shift values for this day
        $values = [];
        if ($slot === 'slot2') {
            if (! empty($otherValue)) {
                $values[] = $otherValue;
            }
            if (! empty($shiftValue)) {
                $values[] = $shiftValue;
            }
        } else {
            if (! empty($shiftValue)) {
                $values[] = $shiftValue;
            }
            if (! empty($otherValue)) {
                $values[] = $otherValue;
            }
        }

        // Delete old records for this day
        HrSchedule::where('hr_employee_id', $employeeId)
            ->whereDate('shift_date', $date)
            ->delete();

        if (in_array('off', $values, true)) {
            HrSchedule::create([
                'hr_employee_id' => $employeeId,
                'shift_date' => $date,
                'hr_shift_id' => null,
                'is_off_day' => true,
                'created_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'action' => 'saved',
                'is_off' => true,
                'shift_id' => null,
                'shift_ids' => [],
            ]);
        }

        $validShiftIds = array_unique(array_filter($values, fn ($v) => ! empty($v) && is_numeric($v)));
        if (empty($validShiftIds)) {
            return response()->json(['success' => true, 'action' => 'deleted']);
        }

        foreach ($validShiftIds as $sId) {
            HrSchedule::create([
                'hr_employee_id' => $employeeId,
                'shift_date' => $date,
                'hr_shift_id' => (int) $sId,
                'is_off_day' => false,
                'created_by' => auth()->id(),
            ]);
        }

        return response()->json([
            'success' => true,
            'action' => 'saved',
            'is_off' => false,
            'shift_id' => (int) ($validShiftIds[0] ?? 0),
            'shift_ids' => $validShiftIds,
        ]);
    }

    public function toggleAttendance(Request $request)
    {
        $validated = $request->validate([
            'hr_employee_id' => 'required|exists:hr_employees,id',
            'shift_date' => 'required|date',
        ]);

        $employee = HrEmployee::findOrFail($validated['hr_employee_id']);
        $this->authorizeEmployeeDepartment(auth()->user(), $employee);

        $date = Carbon::parse($validated['shift_date'])->format('Y-m-d');
        if ($this->isDateLockedForEmployee($employee, $date)) {
            return response()->json(['success' => false, 'message' => 'الروستر معتمد ومقفل للصرف.'], 403);
        }

        $schedules = HrSchedule::where('hr_employee_id', $employee->id)
            ->whereDate('shift_date', $date)
            ->get();

        // إذا كان دوامه ثابتاً ولم يُنشأ سجل صريح بعد، نُنشئ السجل الصريح أولاً
        if ($schedules->isEmpty() && $employee->schedule_type === 'fixed' && ! $employee->isOffOn($date) && $employee->default_shift_id) {
            $created = HrSchedule::create([
                'hr_employee_id' => $employee->id,
                'shift_date' => $date,
                'hr_shift_id' => $employee->default_shift_id,
                'is_off_day' => false,
                'attendance_status' => 'present',
                'created_by' => auth()->id(),
            ]);
            $schedules = collect([$created]);
        }

        if ($schedules->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'لا يوجد شفت مسجل لهذا اليوم.'], 422);
        }

        $currentStatus = $schedules->first()->attendance_status;
        $newStatus = ($currentStatus === 'absent') ? 'present' : 'absent';

        HrSchedule::where('hr_employee_id', $employee->id)
            ->whereDate('shift_date', $date)
            ->update(['attendance_status' => $newStatus]);

        return response()->json([
            'success' => true,
            'attendance_status' => $newStatus,
        ]);
    }

    public function submitApproval(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'period_type' => 'required|in:week,month',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->authorizeDepartmentFilter(auth()->user(), $validated['department_id']);

        $startDate = Carbon::parse($validated['start_date'])->format('Y-m-d');
        $endDate = Carbon::parse($validated['end_date'])->format('Y-m-d');

        $approval = DB::transaction(function () use ($validated, $startDate, $endDate) {
            $approval = HrRosterApproval::updateOrCreate(
                [
                    'department_id' => $validated['department_id'],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                [
                    'period_type' => $validated['period_type'],
                    'status' => 'submitted_by_dept',
                    'submitted_by' => auth()->id(),
                    'submitted_at' => now(),
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            $this->materializeAndAttachDepartmentSchedules((int) $validated['department_id'], $startDate, $endDate, $approval->id);

            return $approval;
        });

        return back()->with('success', 'تم اعتماد وإرسال روستر القسم للمراجعة النهائية للصرف.');
    }

    public function finalApprove(Request $request, HrRosterApproval $approval)
    {
        $request->validate(['notes' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($approval, $request) {
            $this->materializeAndAttachDepartmentSchedules(
                (int) $approval->department_id,
                $approval->start_date->format('Y-m-d'),
                $approval->end_date->format('Y-m-d'),
                $approval->id
            );

            $approval->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'notes' => $request->notes ?: $approval->notes,
            ]);
        });

        return back()->with('success', 'تم الاعتماد النهائي لروستر القسم وإقفاله للصرف في مسير الرواتب الشهري.');
    }

    public function reopenApproval(HrRosterApproval $approval)
    {
        $approval->update([
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return back()->with('success', 'تمت إعادة فتح الروستر للتعديل.');
    }

    private function canManageAllDepartments($user): bool
    {
        return $user->isAdminDirector()
            || $user->can('manage hr schedules')
            || $user->can('final approve hr schedules');
    }

    private function authorizeDepartmentFilter($user, $departmentId): void
    {
        if ($this->canManageAllDepartments($user)) {
            return;
        }

        $managedIds = $user->managedDepartmentIds();
        if (! $departmentId || ! in_array((int) $departmentId, $managedIds, true)) {
            abort(403, 'غير مصرح لك بإدارة جدول دوام قسم آخر.');
        }
    }

    private function authorizeEmployeeDepartment($user, HrEmployee $employee): void
    {
        if ($this->canManageAllDepartments($user)) {
            return;
        }

        if (! in_array((int) $employee->department_id, $user->managedDepartmentIds(), true)) {
            abort(403, 'غير مصرح لك بتعديل دوام موظف خارج قسمك.');
        }
    }

    private function scopedEmployeesQuery($user, $departmentId = null)
    {
        $query = HrEmployee::where('status', 'active');
        if ($this->canManageAllDepartments($user)) {
            if ($departmentId) {
                $query->where('department_id', $departmentId);
            }
        } else {
            $managedIds = $user->managedDepartmentIds();
            if ($departmentId) {
                $query->where('department_id', $departmentId);
            } else {
                $query->whereIn('department_id', $managedIds ?: [-1]);
            }
        }

        return $query;
    }

    private function isDateLockedForEmployee(?HrEmployee $employee, string $date): bool
    {
        if (! $employee || ! $employee->department_id) {
            return false;
        }

        return HrRosterApproval::where('department_id', $employee->department_id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    private function materializeAndAttachDepartmentSchedules(int $departmentId, string $startDate, string $endDate, int $approvalId): void
    {
        $employees = HrEmployee::where('status', 'active')
            ->where('department_id', $departmentId)
            ->get();

        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        foreach ($employees as $emp) {
            if ($emp->schedule_type === 'fixed' && $emp->default_shift_id) {
                for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                    $dStr = $d->format('Y-m-d');
                    $exists = HrSchedule::where('hr_employee_id', $emp->id)
                        ->whereDate('shift_date', $dStr)
                        ->exists();
                    if (! $exists && ! $emp->isOffOn($d)) {
                        HrSchedule::create([
                            'hr_employee_id' => $emp->id,
                            'hr_shift_id' => $emp->default_shift_id,
                            'shift_date' => $dStr,
                            'is_off_day' => false,
                            'attendance_status' => 'present',
                            'hr_roster_approval_id' => $approvalId,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            HrSchedule::where('hr_employee_id', $emp->id)
                ->whereDate('shift_date', '>=', $startDate)
                ->whereDate('shift_date', '<=', $endDate)
                ->update(['hr_roster_approval_id' => $approvalId]);
        }
    }
}
