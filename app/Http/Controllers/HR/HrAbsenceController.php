<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrEmployee;
use App\Models\HrAbsence;
use Illuminate\Http\Request;

class HrAbsenceController extends Controller
{
    public function store(Request $request, HrEmployee $employee)
    {
        $request->validate([
            'absence_date' => 'required|date',
            'days_count'   => 'required|numeric|min:0.25|max:30',
            'type'         => 'required|in:absence,late,early_leave',
            'is_excused'   => 'nullable|boolean',
        ]);

        $isExcused = $request->boolean('is_excused', false);

        // احتساب قيمة الخصم آلياً
        $dailyRate = $employee->basic_salary / 30;
        $deduction = $isExcused ? 0 : round($dailyRate * $request->days_count, 2);

        $employee->absences()->create([
            'absence_date'     => $request->absence_date,
            'days_count'       => $request->days_count,
            'type'             => $request->type,
            'is_excused'       => $isExcused,
            'deduction_amount' => $deduction,
            'status'           => 'pending',
            'reason'           => $request->reason,
            'notes'            => $request->notes,
            'created_by'       => auth()->id(),
        ]);

        $msg = $isExcused
            ? 'تم تسجيل الغياب بعذر (بدون خصم مالي).'
            : 'تم تسجيل الغياب وسيُخصم ' . number_format($deduction, 0) . ' د.ع من راتب الموظف.';

        return back()->with('success', $msg);
    }

    public function destroy(HrAbsence $absence)
    {
        if ($absence->status === 'processed') {
            return back()->with('error', 'لا يمكن حذف غياب تم ترحيله في مسير الرواتب.');
        }
        $absence->delete();
        return back()->with('success', 'تم حذف سجل الغياب.');
    }
}
