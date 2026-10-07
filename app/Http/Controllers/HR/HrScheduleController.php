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
}
