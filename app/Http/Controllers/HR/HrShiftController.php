<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrShift;
use Illuminate\Http\Request;

class HrShiftController extends Controller
{
    public function index()
    {
        $shifts = HrShift::orderBy('start_time')->get();
        return view('hr.shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'color_code' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);
        $validated['is_active'] = $request->has('is_active');

        HrShift::create($validated);
        return redirect()->route('hr.shifts.index')->with('success', 'تم إضافة الشفت بنجاح.');
    }

    public function update(Request $request, HrShift $shift)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'color_code' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);
        $validated['is_active'] = $request->has('is_active');

        $shift->update($validated);
        return redirect()->route('hr.shifts.index')->with('success', 'تم تحديث بيانات الشفت بنجاح.');
    }

    public function destroy(HrShift $shift)
    {
        if (\App\Models\HrSchedule::where('hr_shift_id', $shift->id)->exists()) {
            return redirect()->route('hr.shifts.index')->with('error', 'لا يمكن حذف هذا الشفت لأنه مرتبط بجداول دوام سابقة. يمكنك تعطيله بدلاً من ذلك.');
        }
        $shift->delete();
        return redirect()->route('hr.shifts.index')->with('success', 'تم حذف الشفت بنجاح.');
    }
}
