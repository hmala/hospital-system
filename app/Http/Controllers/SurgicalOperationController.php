<?php

namespace App\Http\Controllers;

use App\Models\SurgicalOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurgicalOperationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin')->except(['index']);
        $this->middleware('role:admin|surgery_staff')->only(['index']);
    }

    /**
     * عرض قائمة العمليات الجراحية
     */
    public function index()
    {
        $operations = SurgicalOperation::orderBy('category')
            ->orderBy('name')
            ->get();

        $canEdit = auth()->user()->hasRole('admin');

        return view('surgical-operations.index', compact('operations', 'canEdit'));
    }

    /**
     * تحديث أجر عملية واحدة
     */
    public function updateFee(Request $request, SurgicalOperation $surgicalOperation)
    {
        $request->validate([
            'fee' => 'required|numeric|min:0'
        ]);

        $surgicalOperation->update([
            'fee' => $request->fee
        ]);

        return redirect()->back()->with('success', 'تم تحديث أجر العملية بنجاح: ' . $surgicalOperation->name . ' - ' . number_format($request->fee, 0) . ' د.ع');
    }

    /**
     * تحديث جماعي لأجور العمليات
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'update_type' => 'required|in:set,increase,decrease',
            'value' => 'required|numeric|min:0',
            'category' => 'nullable|string'
        ]);

        $query = SurgicalOperation::query();

        // تصفية حسب الصنف إذا تم تحديده
        if ($request->category) {
            $query->where('category', $request->category);
        }

        $operations = $query->get();
        $updatedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($operations as $operation) {
                $newFee = 0;

                switch ($request->update_type) {
                    case 'set':
                        // تعيين قيمة ثابتة
                        $newFee = $request->value;
                        break;

                    case 'increase':
                        // زيادة بنسبة مئوية
                        $currentFee = $operation->fee ?? 0;
                        $newFee = $currentFee + ($currentFee * ($request->value / 100));
                        break;

                    case 'decrease':
                        // تخفيض بنسبة مئوية
                        $currentFee = $operation->fee ?? 0;
                        $newFee = $currentFee - ($currentFee * ($request->value / 100));
                        $newFee = max(0, $newFee); // لا يمكن أن يكون سالباً
                        break;
                }

                $operation->update(['fee' => $newFee]);
                $updatedCount++;
            }

            DB::commit();

            $message = 'تم تحديث ' . $updatedCount . ' عملية بنجاح';
            if ($request->category) {
                $message .= ' في صنف: ' . $request->category;
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء التحديث: ' . $e->getMessage());
        }
    }
}
