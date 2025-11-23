<?php

namespace App\Http\Controllers;

use App\Models\RadiologyType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RadiologyTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of radiology types.
     */
    public function index()
    {
        $types = RadiologyType::orderBy('name')->paginate(15);

        return view('radiology.types.index', compact('types'));
    }

    /**
     * Show the form for creating a new radiology type.
     */
    public function create()
    {
        return view('radiology.types.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:radiology_types,code',
            'description' => 'nullable|string|max:1000',
            'base_price' => 'required|numeric|min:0',
            'estimated_duration' => 'required|integer|min:1|max:480', // max 8 hours
            'requires_contrast' => 'boolean',
            'requires_preparation' => 'boolean',
            'preparation_instructions' => 'nullable|string|max:2000',
            'is_active' => 'boolean'
        ]);

        RadiologyType::create($request->all());

        return redirect()->route('radiology.types.index')->with('success', 'تم إضافة نوع الإشعة بنجاح');
    }

    /**
     * Display the specified radiology type.
     */
    public function show(RadiologyType $type)
    {
        $type->load('requests');

        return view('radiology.types.show', compact('type'));
    }

    /**
     * Show the form for editing the radiology type.
     */
    public function edit(RadiologyType $type)
    {
        return view('radiology.types.edit', compact('type'));
    }

    /**
     * Update the specified radiology type.
     */
    public function update(Request $request, RadiologyType $type)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:radiology_types,code,' . $type->id,
            'description' => 'nullable|string|max:1000',
            'base_price' => 'required|numeric|min:0',
            'estimated_duration' => 'required|integer|min:1|max:480',
            'requires_contrast' => 'boolean',
            'requires_preparation' => 'boolean',
            'preparation_instructions' => 'nullable|string|max:2000',
            'is_active' => 'boolean'
        ]);

        $type->update($request->all());

        return redirect()->route('radiology.types.show', $type)->with('success', 'تم تحديث نوع الإشعة بنجاح');
    }

    /**
     * Remove the specified radiology type.
     */
    public function destroy(RadiologyType $type)
    {
        // التحقق من عدم وجود طلبات مرتبطة
        if ($type->requests()->count() > 0) {
            return redirect()->route('radiology.types.show', $type)->with('error', 'لا يمكن حذف نوع الإشعة لأنه مرتبط بطلبات موجودة');
        }

        $type->delete();

        return redirect()->route('radiology.types.index')->with('success', 'تم حذف نوع الإشعة بنجاح');
    }

    /**
     * Toggle active status of radiology type.
     */
    public function toggleStatus(RadiologyType $type)
    {
        $type->update(['is_active' => !$type->is_active]);

        $status = $type->is_active ? 'تفعيل' : 'إلغاء تفعيل';

        return redirect()->route('radiology.types.show', $type)->with('success', "تم {$status} نوع الإشعة بنجاح");
    }
}
