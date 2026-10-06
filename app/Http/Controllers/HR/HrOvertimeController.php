<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrEmployee;
use App\Models\HrOvertime;
use Illuminate\Http\Request;

class HrOvertimeController extends Controller
{
    public function store(Request $request, HrEmployee $employee)
    {
        $request->validate([
            'overtime_date' => 'required|date',
            'input_type'    => 'required|in:hours,manual_amount',
            'hours_count'   => 'nullable|numeric|min:0.5',
            'manual_amount' => 'nullable|numeric|min:0',
            'description'   => 'nullable|string|max:255',
        ]);

        $rateUsed = null;
        $total    = 0;

        if ($request->input_type === 'hours') {
            // استخدام سعر الساعة المسجل في ملف الموظف
            $rateUsed = $employee->overtime_hourly_rate;
            if (!$rateUsed || $rateUsed <= 0) {
                return back()->with('error', 'لم يتم تحديد سعر ساعة العمل الإضافي لهذا الموظف. يرجى تحديثه في إعدادات الراتب أولاً.');
            }
            $total = round($request->hours_count * $rateUsed, 2);
        } else {
            $total = round($request->manual_amount ?? 0, 2);
        }

        $employee->overtimes()->create([
            'overtime_date'    => $request->overtime_date,
            'input_type'       => $request->input_type,
            'hours_count'      => $request->input_type === 'hours' ? $request->hours_count : null,
            'hourly_rate_used' => $rateUsed,
            'manual_amount'    => $request->input_type === 'manual_amount' ? $request->manual_amount : null,
            'total_amount'     => $total,
            'status'           => 'pending',
            'description'      => $request->description,
            'created_by'       => auth()->id(),
        ]);

        return back()->with('success', 'تم تسجيل العمل الإضافي بمبلغ ' . number_format($total, 0) . ' د.ع وسيُضاف لراتب الموظف.');
    }

    public function destroy(HrOvertime $overtime)
    {
        if ($overtime->status === 'processed') {
            return back()->with('error', 'لا يمكن حذف إضافي تم ترحيله في مسير الرواتب.');
        }
        $overtime->delete();
        return back()->with('success', 'تم حذف سجل الإضافي.');
    }
}
