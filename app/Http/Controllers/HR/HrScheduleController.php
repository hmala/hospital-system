<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrSchedule;
use Illuminate\Http\Request;

class HrScheduleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage hr schedules');
    }

    public function index(Request $request)
    {
        $departments = \App\Models\Department::all();
        $departmentId = $request->get('department_id');
        $viewMode = $request->get('view_mode', 'week'); // 'week' (7 days) or 'month' (full month)

        if ($request->get('start_date')) {
            $startDate = \Carbon\Carbon::parse($request->get('start_date'));
        } else {
            $startDate = ($viewMode === 'month')
                ? now()->startOfMonth()
                : now()->startOfWeek(\Carbon\Carbon::SUNDAY);
        }

        if ($viewMode === 'month') {
            $startDate = $startDate->copy()->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();
            $prevDate = $startDate->copy()->subMonth()->startOfMonth()->format('Y-m-d');
            $nextDate = $startDate->copy()->addMonth()->startOfMonth()->format('Y-m-d');
            $currentPeriodDate = now()->startOfMonth()->format('Y-m-d');
        } else {
            // Week mode (7 days)
            $endDate = $startDate->copy()->addDays(6);
            $prevDate = $startDate->copy()->subDays(7)->format('Y-m-d');
            $nextDate = $startDate->copy()->addDays(7)->format('Y-m-d');
            $currentPeriodDate = now()->startOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d');
        }

        $dates = [];
        for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
            $dates[] = clone $date;
        }

        $employeesQuery = \App\Models\HrEmployee::where('status', 'active');
        if ($departmentId) {
            $employeesQuery->where('department_id', $departmentId);
        }
        $employees = $employeesQuery->with(['department', 'defaultShift', 'secondShift'])->get();

        $shifts = \App\Models\HrShift::where('is_active', true)->get();

        // Get existing schedules
        $existingSchedules = HrSchedule::whereIn('hr_employee_id', $employees->pluck('id'))
            ->whereDate('shift_date', '>=', $startDate->format('Y-m-d'))
            ->whereDate('shift_date', '<=', $endDate->format('Y-m-d'))
            ->with('shift')
            ->get()
            ->groupBy('hr_employee_id');

        // Calculate daily shift coverage totals and overall KPI stats
        $dailyCoverage = [];
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
                    if ($isOff) {
                        $dailyCoverage[$dStr]['off']++;
                        $kpiStats['total_off']++;
                    } else {
                        foreach ($daySchedules as $sch) {
                            if ($sch->hr_shift_id && isset($dailyCoverage[$dStr]['shifts'][$sch->hr_shift_id])) {
                                $dailyCoverage[$dStr]['shifts'][$sch->hr_shift_id]++;
                                $dailyCoverage[$dStr]['total']++;
                                $kpiStats['total_shifts']++;
                            }
                        }
                    }
                } elseif ($emp->schedule_type === 'fixed') {
                    if ($emp->isOffOn($date)) {
                        $dailyCoverage[$dStr]['off']++;
                        $kpiStats['total_off']++;
                    } else {
                        // Slot 1
                        if ($emp->default_shift_id && isset($dailyCoverage[$dStr]['shifts'][$emp->default_shift_id])) {
                            $dailyCoverage[$dStr]['shifts'][$emp->default_shift_id]++;
                            $dailyCoverage[$dStr]['total']++;
                            $kpiStats['total_shifts']++;
                        }
                        // Slot 2 if split shift
                        if ($emp->is_split_shift && $emp->second_shift_id && isset($dailyCoverage[$dStr]['shifts'][$emp->second_shift_id])) {
                            $dailyCoverage[$dStr]['shifts'][$emp->second_shift_id]++;
                            $dailyCoverage[$dStr]['total']++;
                            $kpiStats['total_shifts']++;
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
            'viewMode', 'prevDate', 'nextDate', 'currentPeriodDate', 'dailyCoverage', 'kpiStats'
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
            foreach ($dates as $date => $shiftValues) {
                $formattedDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
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

        $currentStartDate = \Carbon\Carbon::parse($request->start_date);
        $currentEndDate = clone $currentStartDate;
        $currentEndDate->addDays(6);

        $lastWeekStartDate = clone $currentStartDate;
        $lastWeekStartDate->subDays(7);
        $lastWeekEndDate = clone $currentEndDate;
        $lastWeekEndDate->subDays(7);

        $employees = \App\Models\HrEmployee::where('status', 'active');
        if ($request->department_id) {
            $employees->where('department_id', $request->department_id);
        }
        $employeeIds = $employees->pluck('id')->toArray();

        // Get last week's schedules for these employees
        $lastWeekSchedules = \App\Models\HrSchedule::whereIn('hr_employee_id', $employeeIds)
            ->whereBetween('shift_date', [$lastWeekStartDate->format('Y-m-d'), $lastWeekEndDate->format('Y-m-d')])
            ->get();

        if ($lastWeekSchedules->isEmpty()) {
            return back()->with('error', 'لا يوجد جدول محفوظ في الأسبوع الماضي لنسخه.');
        }

        $count = 0;
        foreach ($lastWeekSchedules as $schedule) {
            $newDate = \Carbon\Carbon::parse($schedule->shift_date)->addDays(7)->format('Y-m-d');

            \App\Models\HrSchedule::updateOrCreate(
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

        $date = \Carbon\Carbon::parse($request->swap_date)->format('Y-m-d');

        $schedule1 = \App\Models\HrSchedule::whereDate('shift_date', $date)->where('hr_employee_id', $request->employee1_id)->first();
        $schedule2 = \App\Models\HrSchedule::whereDate('shift_date', $date)->where('hr_employee_id', $request->employee2_id)->first();

        $shift1 = $schedule1 ? $schedule1->hr_shift_id : null;
        $off1 = $schedule1 ? $schedule1->is_off_day : false;

        $shift2 = $schedule2 ? $schedule2->hr_shift_id : null;
        $off2 = $schedule2 ? $schedule2->is_off_day : false;

        // Update 1 with 2's data
        if ($schedule1) {
            $schedule1->update(['hr_shift_id' => $shift2, 'is_off_day' => $off2]);
        } else {
            \App\Models\HrSchedule::create(['hr_employee_id' => $request->employee1_id, 'shift_date' => $date, 'hr_shift_id' => $shift2, 'is_off_day' => $off2, 'created_by' => auth()->id()]);
        }

        // Update 2 with 1's data
        if ($schedule2) {
            $schedule2->update(['hr_shift_id' => $shift1, 'is_off_day' => $off1]);
        } else {
            \App\Models\HrSchedule::create(['hr_employee_id' => $request->employee2_id, 'shift_date' => $date, 'hr_shift_id' => $shift1, 'is_off_day' => $off1, 'created_by' => auth()->id()]);
        }

        return back()->with('success', 'تم تبادل الخفارات بنجاح.');
    }

    public function emergencyCoverSearch(Request $request)
    {
        $date = $request->get('date') ? \Carbon\Carbon::parse($request->get('date'))->format('Y-m-d') : null;
        $departmentId = $request->get('department_id');

        if (! $date) {
            return response()->json([]);
        }

        $employees = \App\Models\HrEmployee::where('status', 'active');
        if ($departmentId) {
            $employees->where('department_id', $departmentId);
        }

        $allEmployees = $employees->get();
        $schedules = \App\Models\HrSchedule::whereDate('shift_date', $date)
            ->whereIn('hr_employee_id', $allEmployees->pluck('id'))
            ->get()->keyBy('hr_employee_id');

        $available = [];
        foreach ($allEmployees as $emp) {
            $sch = $schedules->get($emp->id);
            // Available if they have NO schedule OR they are explicitly marked as OFF
            if (! $sch || $sch->is_off_day) {
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

        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $overwrite = $request->boolean('overwrite', false);

        $employeesQuery = \App\Models\HrEmployee::where('status', 'active');
        if ($request->department_id) {
            $employeesQuery->where('department_id', $request->department_id);
        }
        $employees = $employeesQuery->get();

        $count = 0;
        for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            foreach ($employees as $emp) {
                $hasExisting = HrSchedule::where('hr_employee_id', $emp->id)
                    ->whereDate('shift_date', $dateStr)
                    ->exists();

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

        $startDate = \Carbon\Carbon::parse($request->start_date)->format('Y-m-d');
        $endDate = \Carbon\Carbon::parse($request->end_date)->format('Y-m-d');

        $employeesQuery = \App\Models\HrEmployee::where('status', 'active');
        if ($request->department_id) {
            $employeesQuery->where('department_id', $request->department_id);
        }
        $employeeIds = $employeesQuery->pluck('id');

        $deleted = HrSchedule::whereIn('hr_employee_id', $employeeIds)
            ->whereDate('shift_date', '>=', $startDate)
            ->whereDate('shift_date', '<=', $endDate)
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
        $date = \Carbon\Carbon::parse($validated['shift_date'])->format('Y-m-d');
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
}
