<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\SurgeryAdditionalOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountantController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user || (!$user->hasRole('admin') && !$user->hasRole('cashier'))) {
                abort(403, 'غير مصرح لك بالوصول إلى صفحات مراجعة الحسابات.');
            }
            return $next($request);
        });
    }

    public function pendingReviews()
    {
        $pendingSurgeries = Surgery::with([
            'patient.user',
            'doctor.user',
            'department',
            'surgicalOperation',
            'additionalOperations.surgicalOperation',
        ])
        ->where('billing_status', 'pending_review')
        ->orderBy('updated_at', 'desc')
        ->get();

        return view('accountant.surgeries.index', compact('pendingSurgeries'));
    }

    public function reviewForm(Surgery $surgery)
    {
        if ($surgery->billing_status !== 'pending_review') {
            return redirect()->route('accountant.surgeries.index')->with('info', 'هذه العملية ليست بحاجة لمراجعة الأسعار حالياً.');
        }

        $surgery->load([
            'patient.user',
            'doctor.user',
            'surgicalOperation',
            'additionalOperations.surgicalOperation',
        ]);

        return view('accountant.surgeries.review-form', compact('surgery'));
    }

    public function confirmPrices(Request $request, Surgery $surgery)
    {
        if ($surgery->billing_status !== 'pending_review') {
            return redirect()->route('accountant.surgeries.index')->with('error', 'عملية غير صالحة للمراجعة.');
        }

        $validated = $request->validate([
            'surgery_fee' => 'required|numeric|min:0',
            'additional_ops' => 'nullable|array',
            'additional_ops.*' => 'required|numeric|min:0',
        ]);

        // تحديث سعر العملية الرئيسية
        $surgery->surgery_fee = $validated['surgery_fee'];
        $surgery->billing_status = 'reviewed';
        $surgery->save();

        // تحديث أسعار العمليات الإضافية
        if (!empty($validated['additional_ops'])) {
            foreach ($validated['additional_ops'] as $addOpId => $fee) {
                $additionalOp = SurgeryAdditionalOperation::where('surgery_id', $surgery->id)
                    ->where('id', $addOpId)
                    ->first();
                
                if ($additionalOp) {
                    $additionalOp->fee = $fee;
                    $additionalOp->save();
                }
            }
        }

        return redirect()->route('accountant.surgeries.index')->with('success', 'تم تأكيد الأسعار وإرسال الفاتورة للكاشير بنجاح.');
    }
}
