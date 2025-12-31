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
    public function index(Request $request)
    {
        $query = RadiologyType::query();

        // البحث بالاسم أو الكود
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // الفلترة حسب الحالة
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // الفلترة حسب الحاجة لمادة تباين
        if ($request->filled('requires_contrast')) {
            $query->where('requires_contrast', $request->requires_contrast);
        }

        // الفلترة حسب الحاجة للتحضير
        if ($request->filled('requires_preparation')) {
            $query->where('requires_preparation', $request->requires_preparation);
        }

        // الفلترة حسب التصنيف الرئيسي
        if ($request->filled('main_category')) {
            $query->where('main_category', $request->main_category);
        }

        // الفلترة حسب التصنيف الفرعي
        if ($request->filled('subcategory')) {
            $query->where('subcategory', $request->subcategory);
        }

        // الفلترة حسب نطاق السعر
        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        // الترتيب
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $types = $query->paginate(15)->withQueryString();
        
        // الحصول على التصنيفات المتاحة
        $mainCategories = RadiologyType::distinct()->pluck('main_category');
        $subcategories = RadiologyType::distinct()->pluck('subcategory');

        return view('radiology.types.index', compact('types', 'mainCategories', 'subcategories'));
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
