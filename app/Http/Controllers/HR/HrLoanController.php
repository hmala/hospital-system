<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrEmployee;
use App\Models\HrLoan;
use Illuminate\Http\Request;

class HrLoanController extends Controller
{
    public function store(Request $request, HrEmployee $employee)
    {
        $request->validate([
            'total_amount'        => 'required|numeric|min:1000',
            'monthly_installment' => 'required|numeric|min:0',
            'start_date'          => 'required|date',
            'reason'              => 'nullable|string|max:255',
        ]);

        // إذا كان القسط 0، يعني السلفة لمرة واحدة
        $installment = $request->monthly_installment > 0
            ? $request->monthly_installment
            : $request->total_amount;

        $employee->loans()->create([
            'total_amount'        => $request->total_amount,
            'monthly_installment' => $installment,
            'paid_amount'         => 0,
            'remaining_amount'    => $request->total_amount,
            'start_date'          => $request->start_date,
            'status'              => 'active',
            'reason'              => $request->reason,
            'notes'               => $request->notes,
            'created_by'          => auth()->id(),
        ]);

        return back()->with('success', 'تم تسجيل السلفة بنجاح وستبدأ الاستقطاعات من ' . $request->start_date . '.');
    }

    public function cancel(HrLoan $loan)
    {
        if ($loan->status !== 'active') {
            return back()->with('error', 'لا يمكن إلغاء سلفة غير نشطة.');
        }
        $loan->update(['status' => 'cancelled']);
        return back()->with('success', 'تم إلغاء السلفة.');
    }
}
