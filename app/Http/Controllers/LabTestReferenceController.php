<?php

namespace App\Http\Controllers;

use App\Models\LabTest;
use App\Models\LabTestReference;
use Illuminate\Http\Request;

class LabTestReferenceController extends Controller
{
    public function index(LabTest $labTest)
    {
        $this->authorizeAdmin();
        $references = $labTest->references()->orderBy('gender')->orderBy('age_min')->get();
        return view('lab-tests.references', compact('labTest', 'references'));
    }

    public function store(Request $request, LabTest $labTest)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'gender'   => 'required|in:male,female,both',
            'age_min'  => 'required|integer|min:0',
            'age_max'  => 'required|integer|min:0',
            'ref_min'  => 'nullable|numeric',
            'ref_max'  => 'nullable|numeric',
            'ref_text' => 'nullable|string|max:100',
            'unit'     => 'nullable|string|max:50',
            'notes'    => 'nullable|string',
        ]);

        $labTest->references()->create($data);

        return redirect()->route('lab-tests.references.index', $labTest)
            ->with('success', 'تم إضافة القيمة المرجعية بنجاح.');
    }

    public function update(Request $request, LabTest $labTest, LabTestReference $reference)
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'gender'   => 'required|in:male,female,both',
            'age_min'  => 'required|integer|min:0',
            'age_max'  => 'required|integer|min:0',
            'ref_min'  => 'nullable|numeric',
            'ref_max'  => 'nullable|numeric',
            'ref_text' => 'nullable|string|max:100',
            'unit'     => 'nullable|string|max:50',
            'notes'    => 'nullable|string',
        ]);

        $reference->update($data);

        return redirect()->route('lab-tests.references.index', $labTest)
            ->with('success', 'تم تحديث القيمة المرجعية بنجاح.');
    }

    public function destroy(LabTest $labTest, LabTestReference $reference)
    {
        $this->authorizeAdmin();
        $reference->delete();

        return redirect()->route('lab-tests.references.index', $labTest)
            ->with('success', 'تم حذف القيمة المرجعية.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->user()->hasAnyRole(['admin', 'lab_staff'])) {
            abort(403);
        }
    }
}
