<?php

namespace App\Http\Controllers;

use App\Models\ICD10Code;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ICD10Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $category = $request->get('category');

        $query = ICD10Code::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('description_ar', 'like', "%{$search}%");
            });
        }

        if ($category) {
            $query->where('category', $category);
        }

        $icd10Codes = $query->orderBy('code')->paginate(20);
        $categories = ICD10Code::distinct()->pluck('category')->filter()->sort();

        return view('icd10.index', compact('icd10Codes', 'categories', 'search', 'category'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ICD10Code::distinct()->pluck('category')->filter()->sort();
        return view('icd10.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:icd10_codes,code',
            'description' => 'required|string|max:255',
            'description_ar' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
        ]);

        ICD10Code::create($request->all());

        return redirect()->route('icd10.index')
            ->with('success', 'تم إضافة رمز ICD10 بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(ICD10Code $icd10)
    {
        return view('icd10.show', compact('icd10'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ICD10Code $icd10)
    {
        $categories = ICD10Code::distinct()->pluck('category')->filter()->sort();
        return view('icd10.edit', compact('icd10', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ICD10Code $icd10)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:icd10_codes,code,' . $icd10->id,
            'description' => 'required|string|max:255',
            'description_ar' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
        ]);

        $icd10->update($request->all());

        return redirect()->route('icd10.index')
            ->with('success', 'تم تحديث رمز ICD10 بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ICD10Code $icd10)
    {
        $icd10->delete();

        return redirect()->route('icd10.index')
            ->with('success', 'تم حذف رمز ICD10 بنجاح');
    }
}
