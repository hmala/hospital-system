<?php

namespace App\Http\Controllers;

use App\Models\LabTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabTestController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // السماح بالمشاهدة للأطباء والموظفين، لكن التحرير محدود للمسؤولين وموظفي المختبر
        if (!$user->hasRole(['admin', 'lab_staff', 'doctor', 'receptionist', 'radiology_staff', 'pharmacy_staff', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $query = LabTest::query();

        // فلترة حسب التصنيف الرئيسي
        if (request('main_category')) {
            $query->where('main_category', request('main_category'));
        }

        // فلترة حسب التصنيف الفرعي
        if (request('subcategory')) {
            $query->where('subcategory', request('subcategory'));
        }

        // فلترة حسب الفئة القديمة إذا تم تحديدها
        if (request('category')) {
            $query->where('category', request('category'));
        }

        // فلترة حسب الحالة
        if (request('status') !== null) {
            $query->where('is_active', request('status') === 'active');
        }

        // البحث بالاسم
        if (request('search')) {
            $query->where('name', 'like', '%' . request('search') . '%');
        }

        $labTests = $query->orderBy('main_category')->orderBy('subcategory')->orderBy('name')->paginate(20);

        // الحصول على جميع التصنيفات الرئيسية والفرعية الموجودة
        $mainCategories = LabTest::select('main_category')->distinct()->whereNotNull('main_category')->pluck('main_category')->toArray();
        $subcategories = LabTest::select('subcategory')->distinct()->whereNotNull('subcategory')->pluck('subcategory')->toArray();

        $categories = [
            'biochemistry' => 'كيمياء سريرية',
            'hematology' => 'أمراض الدم',
            'blood_bank' => 'مصرف الدم',
            'parasitology' => 'الطفيليات',
            'microbiology' => 'الأحياء المجهرية',
            'immunology' => 'المناعة والهرمونات',
            'virology' => 'فــيروسات',
            'hormones' => 'هرمــونات',
            'clinical_immunology' => 'المناعة السريرية',
            'cytology' => 'الخــلايا',
            'miscellaneous' => 'متفـــرقة',
            'other' => 'أخرى'
        ];

        return view('lab-tests.index', compact('labTests', 'categories', 'mainCategories', 'subcategories'));
    }

    public function create()
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'lab_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $categories = [
            'biochemistry' => 'كيمياء سريرية',
            'hematology' => 'أمراض الدم',
            'blood_bank' => 'مصرف الدم',
            'parasitology' => 'الطفيليات',
            'microbiology' => 'الأحياء المجهرية',
            'immunology' => 'المناعة والهرمونات',
            'virology' => 'فــيروسات',
            'hormones' => 'هرمــونات',
            'clinical_immunology' => 'المناعة السريرية',
            'cytology' => 'الخــلايا',
            'miscellaneous' => 'متفـــرقة',
            'other' => 'أخرى'
        ];

        return view('lab-tests.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'lab_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:lab_tests,name',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:biochemistry,hematology,blood_bank,parasitology,microbiology,immunology,virology,hormones,clinical_immunology,cytology,miscellaneous,other',
            'is_active' => 'boolean'
        ]);

        LabTest::create($request->all());

        return redirect()->route('lab-tests.index')->with('success', 'تم إضافة الفحص المختبري بنجاح');
    }

    public function show(LabTest $labTest)
    {
        $user = Auth::user();

        // السماح بمشاهدة تفاصيل الفحص للأطباء والموظفين
        if (!$user->hasRole(['admin', 'lab_staff', 'doctor', 'receptionist', 'radiology_staff', 'pharmacy_staff', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        return view('lab-tests.show', compact('labTest'));
    }

    public function edit(LabTest $labTest)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'lab_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $categories = [
            'biochemistry' => 'كيمياء سريرية',
            'hematology' => 'أمراض الدم',
            'blood_bank' => 'مصرف الدم',
            'parasitology' => 'الطفيليات',
            'microbiology' => 'الأحياء المجهرية',
            'immunology' => 'المناعة والهرمونات',
            'virology' => 'فــيروسات',
            'hormones' => 'هرمــونات',
            'clinical_immunology' => 'المناعة السريرية',
            'cytology' => 'الخــلايا',
            'miscellaneous' => 'متفـــرقة',
            'other' => 'أخرى'
        ];

        return view('lab-tests.edit', compact('labTest', 'categories'));
    }

    public function update(Request $request, LabTest $labTest)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'lab_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:lab_tests,name,' . $labTest->id,
            'description' => 'nullable|string|max:1000',
            'category' => 'required|string|in:biochemistry,hematology,blood_bank,parasitology,microbiology,immunology,virology,hormones,clinical_immunology,cytology,miscellaneous,other',
            'is_active' => 'boolean'
        ]);

        $labTest->update($request->all());

        return redirect()->route('lab-tests.index')->with('success', 'تم تحديث الفحص المختبري بنجاح');
    }

    public function destroy(LabTest $labTest)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بحذف الفحوصات المختبرية');
        }

        $labTest->delete();

        return redirect()->route('lab-tests.index')->with('success', 'تم حذف الفحص المختبري بنجاح');
    }

    public function toggleStatus(LabTest $labTest)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'lab_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $labTest->update(['is_active' => !$labTest->is_active]);

        $status = $labTest->is_active ? 'تفعيل' : 'إلغاء تفعيل';

        return redirect()->back()->with('success', "تم {$status} الفحص المختبري بنجاح");
    }
}