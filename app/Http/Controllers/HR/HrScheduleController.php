<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrSchedule;
use Illuminate\Http\Request;

class HrScheduleController extends Controller
{
    public function index(Request $request)
    {
        $departments = \App\Models\Department::all();
        $departmentId = $request->get('department_id');
        
        $startDate = $request->get('start_date') ? \Carbon\Carbon::parse($request->get('start_date')) : now()->startOfWeek(\Carbon\Carbon::SUNDAY);
        $endDate = clone $startDate;
        $endDate->addDays(6); // 7 days view
        
        $dates = [];
        for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
            $dates[] = clone $date;
        }

        $employees = \App\Models\HrEmployee::where('status', 'active');
        if ($departmentId) {
            $employees->where('department_id', $departmentId);
        }
        $employees = $employees->with(['department'])->get();

        $shifts = \App\Models\HrShift::where('is_active', true)->get();

        // Get existing schedules
        $existingSchedules = HrSchedule::whereIn('hr_employee_id', $employees->pluck('id'))
            ->whereBetween('shift_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('hr_employee_id');

        return view('hr.schedules.index', compact('departments', 'departmentId', 'startDate', 'endDate', 'dates', 'employees', 'shifts', 'existingSchedules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'schedules' => 'nullable|array',
            // schedules format: schedules[employee_id][date] = shift_id (or 'off' or empty)
        ]);

        $schedules = $request->input('schedules', []);
        
        // Loop through all submitted schedules and update/create
        foreach ($schedules as $employeeId => $dates) {
            foreach ($dates as $date => $shiftValue) {
                if (empty($shiftValue)) {
                    // Remove if exists
                    HrSchedule::where('hr_employee_id', $employeeId)
                        ->where('shift_date', $date)
                        ->delete();
                } else {
                    $isOffDay = ($shiftValue === 'off');
                    $shiftId = $isOffDay ? null : $shiftValue;

                    HrSchedule::updateOrCreate(
                        ['hr_employee_id' => $employeeId, 'shift_date' => $date],
                        [
                            'hr_shift_id' => $shiftId,
                            'is_off_day' => $isOffDay,
                            'created_by' => auth()->id()
                        ]
                    );
                }
            }
        }

        return back()->with('success', 'تم حفظ وتحديث جدول الدوام بنجاح.');
    }

    public function copyLastWeek(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'department_id' => 'nullable|exists:departments,id'
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
                    'created_by' => auth()->id()
                ]
            );
            $count++;
        }

        return back()->with('success', "تم نسخ $count شفت من الأسبوع الماضي بنجاح.");
    }
}