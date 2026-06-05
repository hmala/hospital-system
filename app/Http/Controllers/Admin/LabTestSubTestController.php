<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabTest;
use App\Models\LabTestSubTest;
use Illuminate\Http\Request;

class LabTestSubTestController extends Controller
{
    /**
     * عرض الفحوصات الفرعية لتحليل معين
     */
    public function index($labTestId)
    {
        $labTest = LabTest::with('subTests')->findOrFail($labTestId);
        return view('admin.lab-test-sub-tests.index', compact('labTest'));
    }

    /**
     * عرض صفحة إضافة فحص فرعي
     */
    public function create($labTestId)
    {
        $labTest = LabTest::findOrFail($labTestId);
        return view('admin.lab-test-sub-tests.create', compact('labTest'));
    }

    /**
     * حفظ فحص فرعي جديد
     */
    public function store(Request $request, $labTestId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:100',
            'reference_range' => 'nullable|string|max:255',
            'result_type' => 'required|in:numeric,text',
            'sort_order' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        LabTestSubTest::create([
            'lab_test_id' => $labTestId,
            'name' => $request->name,
            'unit' => $request->unit,
            'reference_range' => $request->reference_range,
            'result_type' => $request->result_type,
            'sort_order' => $request->sort_order ?? 0,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.lab-test-sub-tests.index', $labTestId)
            ->with('success', 'تم إضافة الفحص الفرعي بنجاح');
    }

    /**
     * عرض صفحة تعديل فحص فرعي
     */
    public function edit($labTestId, $id)
    {
        $labTest = LabTest::findOrFail($labTestId);
        $subTest = LabTestSubTest::findOrFail($id);
        return view('admin.lab-test-sub-tests.edit', compact('labTest', 'subTest'));
    }

    /**
     * تحديث فحص فرعي
     */
    public function update(Request $request, $labTestId, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'nullable|string|max:100',
            'reference_range' => 'nullable|string|max:255',
            'result_type' => 'required|in:numeric,text',
            'sort_order' => 'nullable|integer',
            'notes' => 'nullable|string',
        ]);

        $subTest = LabTestSubTest::findOrFail($id);
        $subTest->update([
            'name' => $request->name,
            'unit' => $request->unit,
            'reference_range' => $request->reference_range,
            'result_type' => $request->result_type,
            'sort_order' => $request->sort_order ?? 0,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.lab-test-sub-tests.index', $labTestId)
            ->with('success', 'تم تحديث الفحص الفرعي بنجاح');
    }

    /**
     * حذف فحص فرعي
     */
    public function destroy($labTestId, $id)
    {
        $subTest = LabTestSubTest::findOrFail($id);
        $subTest->delete();

        return redirect()->route('admin.lab-test-sub-tests.index', $labTestId)
            ->with('success', 'تم حذف الفحص الفرعي بنجاح');
    }
}
