<?php

namespace App\Http\Controllers;

use App\Models\LabTest;
use App\Models\LabTestSubTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabTestSubTestController extends Controller
{
    public function index(LabTest $labTest)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'lab_staff', 'doctor', 'receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $subTests = $labTest->subTests()->get();

        return view('lab-tests.sub-tests.index', compact('labTest', 'subTests'));
    }

    public function store(Request $request, LabTest $labTest)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'lab_staff'])) {
            abort(403, 'غير مصرح لك بإضافة فحوصات فرعية');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'reference_range' => 'nullable|string|max:100',
            'result_type' => 'nullable|string|in:numeric,text,positive_negative',
            'sort_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!isset($validated['sort_order']) || $validated['sort_order'] === null) {
            $maxOrder = $labTest->subTests()->max('sort_order') ?? 0;
            $validated['sort_order'] = $maxOrder + 1;
        }

        if (empty($validated['result_type'])) {
            $validated['result_type'] = 'numeric';
        }

        $validated['lab_test_id'] = $labTest->id;

        LabTestSubTest::create($validated);

        return redirect()->route('lab-tests.sub-tests.index', $labTest)
            ->with('success', 'تمت إضافة الفحص الفرعي بنجاح');
    }

    public function update(Request $request, LabTest $labTest, LabTestSubTest $subTest)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'lab_staff'])) {
            abort(403, 'غير مصرح لك بتعديل الفحوصات الفرعية');
        }

        if ($subTest->lab_test_id !== $labTest->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:50',
            'reference_range' => 'nullable|string|max:100',
            'result_type' => 'nullable|string|in:numeric,text,positive_negative',
            'sort_order' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $subTest->update($validated);

        return redirect()->route('lab-tests.sub-tests.index', $labTest)
            ->with('success', 'تم تحديث بيانات الفحص الفرعي بنجاح');
    }

    public function destroy(LabTest $labTest, LabTestSubTest $subTest)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'lab_staff'])) {
            abort(403, 'غير مصرح لك بحذف الفحوصات الفرعية');
        }

        if ($subTest->lab_test_id !== $labTest->id) {
            abort(404);
        }

        $subTest->delete();

        return redirect()->route('lab-tests.sub-tests.index', $labTest)
            ->with('success', 'تم حذف الفحص الفرعي بنجاح');
    }
}
