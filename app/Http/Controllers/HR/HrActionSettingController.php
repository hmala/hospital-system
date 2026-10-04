<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrActionSetting;
use Illuminate\Http\Request;

class HrActionSettingController extends Controller
{
    public function index()
    {
        $settings = HrActionSetting::orderBy('category')->orderBy('id', 'desc')->get();
        return view('hr.action_settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|in:penalty,bonus,warning',
            'title' => 'required|string|max:255',
            'effect_type' => 'required|in:none,amount,days,percentage',
            'effect_value' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        HrActionSetting::create($validated);

        return redirect()->route('hr.action_settings.index')->with('success', 'تم إضافة الإجراء بنجاح');
    }

    public function update(Request $request, HrActionSetting $actionSetting)
    {
        $validated = $request->validate([
            'category' => 'required|in:penalty,bonus,warning',
            'title' => 'required|string|max:255',
            'effect_type' => 'required|in:none,amount,days,percentage',
            'effect_value' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $actionSetting->update($validated);

        return redirect()->route('hr.action_settings.index')->with('success', 'تم تحديث الإجراء بنجاح');
    }

    public function destroy(HrActionSetting $actionSetting)
    {
        if ($actionSetting->employeeActions()->exists()) {
            return redirect()->route('hr.action_settings.index')->with('error', 'لا يمكن حذف هذا الإجراء لارتباطه بموظفين. يمكنك تعطيله بدلاً من ذلك.');
        }

        $actionSetting->delete();
        return redirect()->route('hr.action_settings.index')->with('success', 'تم حذف الإجراء بنجاح');
    }
}
