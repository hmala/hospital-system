<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrEmployee;
use App\Models\HrEmployeeAllowance;
use Illuminate\Http\Request;

class HrAllowanceController extends Controller
{
    public function store(Request $request, HrEmployee $employee)
    {
        $request->validate([
            'title'  => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
        ]);

        $employee->allowances()->create([
            'title'      => $request->title,
            'amount'     => $request->amount,
            'is_active'  => true,
            'notes'      => $request->notes,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'تم إضافة المخصص "' . $request->title . '" بنجاح.');
    }

    public function update(Request $request, HrEmployeeAllowance $allowance)
    {
        $request->validate([
            'title'  => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
        ]);

        $allowance->update([
            'title'     => $request->title,
            'amount'    => $request->amount,
            'is_active' => $request->boolean('is_active', true),
            'notes'     => $request->notes,
        ]);

        return back()->with('success', 'تم تحديث المخصص بنجاح.');
    }

    public function destroy(HrEmployeeAllowance $allowance)
    {
        $allowance->delete();
        return back()->with('success', 'تم حذف المخصص بنجاح.');
    }
}
