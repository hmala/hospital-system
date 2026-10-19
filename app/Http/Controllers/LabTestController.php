<?php

namespace App\Http\Controllers;

use App\Models\LabTest;
use App\Exports\LabTestsExport;
use App\Imports\LabTestsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabTestController extends Controller
{
    public function export(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->can('view lab tests') && !$user->hasRole(['lab_staff', 'doctor', 'receptionist', 'radiology_staff', 'pharmacy_staff', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $fileName = 'قائمة_تحاليل_المختبر_' . date('Y_m_d') . '.xlsx';
        return Excel::download(new LabTestsExport($request), $fileName);
    }

    public function import(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->can('edit lab tests') && !$user->hasRole('lab_staff')) {
            abort(403, 'غير مصرح لك بتحديث أسعار الفحوصات المختبرية');
        }

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'excel_file.required' => 'يرجى اختيار ملف الإكسل المراد استيراده',
            'excel_file.mimes' => 'يجب أن يكون الملف بصيغة Excel (.xlsx, .xls) أو CSV',
            'excel_file.max' => 'حجم الملف يجب ألا يتجاوز 10 ميغابايت',
        ]);

        try {
            $replace = $request->boolean('replace_existing', false);
            $import = new LabTestsImport($replace);
            Excel::import($import, $request->file('excel_file'));

            return redirect()->back()->with(
                'success',
                "تم استيراد الفحوصات بنجاح: تم تحديث {$import->updatedCount} فحصاً، وإضافة {$import->createdCount} فحصاً جديداً من ملف الإكسل (تم تخطي {$import->skippedCount})."
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة ملف الإكسل: ' . $e->getMessage());
        }
    }
    public function index()
    {
        $user = Auth::user();

        // السماح بالمشاهدة للأدمن أو حاملي صلاحية view lab tests أو الكادر المخول
        if (!$user->isAdmin() && !$user->can('view lab tests') && !$user->hasRole(['lab_staff', 'doctor', 'receptionist', 'radiology_staff', 'pharmacy_staff', 'surgery_staff'])) {
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


        // فلترة حسب الحالة العامة
        if (request('status') !== null && request('status') !== '') {
            $query->where('is_active', request('status') === 'active');
        }

        // فلترة حسب حالة الضمان الصحي
        if (request('insurance_status') === 'insured') {
            $query->where('is_hi_active', true)->where('hi_price', '>', 0);
        } elseif (request('insurance_status') === 'non_insured') {
            $query->where(function($q) {
                $q->where('is_hi_active', false)
                  ->orWhereNull('hi_price')
                  ->orWhere('hi_price', '<=', 0);
            });
        }

        // البحث بالاسم أو الكود
        if (request('search')) {
            $term = '%' . request('search') . '%';
            $query->where(function($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term);
            });
        }

        $perPage = (int) request('per_page', 25);
        if (!in_array($perPage, [20, 25, 50, 100, 200, 500])) {
            $perPage = 25;
        }

        $labTests = $query->orderBy('main_category')->orderBy('subcategory')->orderBy('name')->paginate($perPage)->withQueryString();

        // إحصائيات سريعة للتحكم الشامل
        $stats = [
            'total' => LabTest::count(),
            'active' => LabTest::where('is_active', true)->count(),
            'inactive' => LabTest::where('is_active', false)->count(),
            'hi_active' => LabTest::where('is_hi_active', true)->where('hi_price', '>', 0)->count(),
            'hi_inactive' => LabTest::where(function($q) {
                $q->where('is_hi_active', false)
                  ->orWhereNull('hi_price')
                  ->orWhere('hi_price', '<=', 0);
            })->count(),
            'hi_unpriced' => LabTest::where(function($q) {
                $q->whereNull('hi_price')->orWhere('hi_price', '<=', 0);
            })->count(),
        ];

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

        return view('lab-tests.index', compact('labTests', 'categories', 'mainCategories', 'subcategories', 'stats'));
    }

    public function create()
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->can('create lab tests') && !$user->hasRole('lab_staff')) {
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

        $subcategories = [
            'Biochemistry',
            'Haematology',
            'Microbiology',
            'Blood Bank',
            'Immunology',
            'Serology',
            'Tumor marker',
            'Virology'
        ];

        return view('lab-tests.create', compact('categories', 'subcategories'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->can('create lab tests') && !$user->hasRole('lab_staff')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:lab_tests,code',
            'name' => 'required|string|max:255|unique:lab_tests,name',
            'description' => 'nullable|string|max:1000',
            'main_category' => 'nullable|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'result_entry_type' => 'nullable|string|in:single_value,sub_tests,attachment_only,flexible',
            'price' => 'nullable|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'is_moi_active' => 'nullable|boolean',
            'hi_price' => 'nullable|numeric|min:0',
            'is_hi_active' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        $data['is_moi_active'] = $request->has('is_moi_active');
        $data['is_hi_active'] = $request->has('is_hi_active');

        LabTest::create($data);

        return redirect()->route('lab-tests.index')->with('success', 'تم إضافة الفحص المختبري بنجاح');
    }

    public function show(LabTest $labTest)
    {
        $user = Auth::user();

        // السماح بمشاهدة تفاصيل الفحص
        if (!$user->isAdmin() && !$user->can('view lab tests') && !$user->hasRole(['lab_staff', 'doctor', 'receptionist', 'radiology_staff', 'pharmacy_staff', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        return view('lab-tests.show', compact('labTest'));
    }

    public function edit(LabTest $labTest)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->can('edit lab tests') && !$user->hasRole('lab_staff')) {
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

        $subcategories = [
            'Biochemistry',
            'Haematology',
            'Microbiology',
            'Blood Bank',
            'Immunology',
            'Serology',
            'Tumor marker',
            'Virology'
        ];

        return view('lab-tests.edit', compact('labTest', 'categories', 'subcategories'));
    }

    public function update(Request $request, LabTest $labTest)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->can('edit lab tests') && !$user->hasRole('lab_staff')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:lab_tests,code,' . $labTest->id,
            'name' => 'required|string|max:255|unique:lab_tests,name,' . $labTest->id,
            'description' => 'nullable|string|max:1000',
            'main_category' => 'nullable|string|max:255',
            'subcategory' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:50',
            'result_entry_type' => 'nullable|string|in:single_value,sub_tests,attachment_only,flexible',
            'price' => 'nullable|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'is_moi_active' => 'nullable|boolean',
            'hi_price' => 'nullable|numeric|min:0',
            'is_hi_active' => 'nullable|boolean',
            'is_active' => 'nullable|boolean'
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        $data['is_moi_active'] = $request->has('is_moi_active');
        $data['is_hi_active'] = $request->has('is_hi_active');

        $labTest->update($data);

        return redirect()->route('lab-tests.index')->with('success', 'تم تحديث الفحص المختبري بنجاح');
    }

    public function destroy(LabTest $labTest)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->can('delete lab tests')) {
            abort(403, 'غير مصرح لك بحذف الفحوصات المختبرية');
        }

        $labTest->delete();

        return redirect()->route('lab-tests.index')->with('success', 'تم حذف الفحص المختبري بنجاح');
    }

    public function toggleStatus(LabTest $labTest)
    {
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->can('edit lab tests') && !$user->hasRole('lab_staff')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $labTest->update(['is_active' => !$labTest->is_active]);

        $status = $labTest->is_active ? 'تفعيل' : 'إلغاء تفعيل';

        return redirect()->back()->with('success', "تم {$status} الفحص المختبري بنجاح");
    }

    /**
     * تبديل فوري وحفظ لحظي عبر AJAX لحالة الفحص (العامة أو الضمان)
     */
    public function quickToggle(Request $request, LabTest $labTest)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->can('edit lab tests') && !$user->hasRole(['admin', 'lab_staff'])) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل حالة الفحوصات'], 403);
        }

        $request->validate([
            'field' => 'required|in:is_active,is_hi_active,is_moi_active',
            'value' => 'nullable|boolean',
        ]);

        $field = $request->input('field');
        $newValue = $request->has('value') ? $request->boolean('value') : !$labTest->{$field};

        $labTest->update([$field => $newValue]);

        $labels = [
            'is_active' => 'الحالة العامة',
            'is_hi_active' => 'حالة الضمان الصحي',
            'is_moi_active' => 'حالة تأمين الداخلية',
        ];

        $statusText = $newValue ? 'تفعيل' : 'تعطيل';

        return response()->json([
            'success' => true,
            'message' => "تم {$statusText} {$labels[$field]} للفحص ({$labTest->name}) بنجاح",
            'is_active' => (bool) $labTest->is_active,
            'is_hi_active' => (bool) $labTest->is_hi_active,
            'is_moi_active' => (bool) $labTest->is_moi_active,
            'field' => $field,
            'new_value' => $newValue,
        ]);
    }

    /**
     * تبديل جماعي لحالة مجموعة من الفحوصات المختارة
     */
    public function bulkToggleStatus(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->can('edit lab tests') && !$user->hasRole(['admin', 'lab_staff'])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
            }
            abort(403, 'غير مصرح لك بتعديل حالة الفحوصات');
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:lab_tests,id',
            'field' => 'required|in:is_active,is_hi_active,is_moi_active',
            'status' => 'required|in:active,inactive,0,1,true,false',
        ]);

        $field = $request->input('field', 'is_active');
        $statusInput = $request->input('status');
        $isActive = in_array($statusInput, ['active', '1', 1, true, 'true'], true);

        $ids = $request->input('ids', []);
        $count = LabTest::whereIn('id', $ids)->update([$field => $isActive]);

        $labels = [
            'is_active' => 'العام',
            'is_hi_active' => 'بالضمان الصحي',
            'is_moi_active' => 'بتأمين الداخلية',
        ];
        $statusText = $isActive ? 'تفعيل' : 'تعطيل';
        $message = "تم {$statusText} {$count} فحص {$labels[$field]} بنجاح";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $count,
                'field' => $field,
                'is_active' => $isActive,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * تحكم ذكي كلي بالفحوصات خارج الضمان (غير المسعرة بالضمان أو المسعرة بصفر)
     */
    public function bulkDisableNonInsurance(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->can('edit lab tests') && !$user->hasRole(['admin', 'lab_staff'])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
            }
            abort(403, 'غير مصرح لك بتعديل حالة الفحوصات');
        }

        $action = $request->input('action', 'general_disable'); 
        // 1. 'general_disable': إطفاء عام للفحوصات التي ليس لها تسعير ضمان
        // 2. 'hi_disable': إطفاء عن الضمان فقط للفحوصات التي ليس لها تسعير ضمان
        // 3. 'enable_all_insured': تفعيل عام وبالضمان لكل فحص مسعر بالضمان (> 0)

        if ($action === 'general_disable') {
            $count = LabTest::where(function($q) {
                $q->whereNull('hi_price')->orWhere('hi_price', '<=', 0);
            })->update(['is_active' => false]);
            $message = "تم إطفاء وتنزيل {$count} فحص خارج الضمان من محطات النظام بالكامل.";
        } elseif ($action === 'hi_disable') {
            $count = LabTest::where(function($q) {
                $q->whereNull('hi_price')->orWhere('hi_price', '<=', 0);
            })->update(['is_hi_active' => false]);
            $message = "تم إيقاف {$count} فحص غير مشمول بالضمان عن مظلة التأمين الصحي.";
        } elseif ($action === 'enable_all_insured') {
            $count = LabTest::where('hi_price', '>', 0)->update([
                'is_active' => true,
                'is_hi_active' => true,
            ]);
            $message = "تم تفعيل {$count} فحص معتمد ومسعر بالضمان الصحي في كافة المحطات بنجاح.";
        } else {
            return redirect()->back()->with('error', 'إجراء غير معروف');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'count' => $count,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function toggleFavorite(LabTest $labTest)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $isFavorite = \App\Models\UserLabTestStat::toggleFavorite($user->id, $labTest->id);

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
            'message' => $isFavorite ? 'تمت الإضافة للمفضلة' : 'تمت الإزالة من المفضلة'
        ]);
    }

    /**
     * شاشة إعدادات وتخصيص نمط إدخال نتائج الفحوصات المختبرية (للأدمن)
     */
    public function entrySettings(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->hasRole('admin') && !$user->can('manage lab tests') && !$user->can('edit lab tests')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة، هذه الصلاحية خاصة بمدير النظام');
        }

        $query = LabTest::withCount('subTests');

        // البحث بالاسم أو الكود
        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // فلترة بالتصنيف الفرعي
        if ($request->filled('category')) {
            $query->where('subcategory', $request->category);
        }

        // فلترة بنمط الإدخال
        if ($request->filled('entry_type')) {
            $query->where('result_entry_type', $request->entry_type);
        }

        // فلترة بالفحوصات التي تحتوي قيم فرعية
        if ($request->filled('sub_tests_only')) {
            if ($request->sub_tests_only === 'yes') {
                $query->has('subTests');
            } elseif ($request->sub_tests_only === 'no') {
                $query->doesntHave('subTests');
            }
        }

        $perPage = (int) $request->input('per_page', 50);
        if (!in_array($perPage, [25, 50, 100, 200, 500])) {
            $perPage = 50;
        }

        $labTests = $query->orderBy('subcategory')->orderBy('name')->paginate($perPage)->withQueryString();

        // إحصائيات عامة عن الأنماط
        $stats = [
            'total' => LabTest::count(),
            'single_value' => LabTest::where(function($q) {
                $q->whereNull('result_entry_type')->orWhere('result_entry_type', LabTest::ENTRY_TYPE_SINGLE);
            })->count(),
            'sub_tests' => LabTest::where('result_entry_type', LabTest::ENTRY_TYPE_SUBTESTS)->count(),
            'attachment_only' => LabTest::where('result_entry_type', LabTest::ENTRY_TYPE_ATTACHMENT)->count(),
            'flexible' => LabTest::where('result_entry_type', LabTest::ENTRY_TYPE_FLEXIBLE)->count(),
            'with_subtests_count' => LabTest::has('subTests')->count(),
        ];

        // التصنيفات الفرعية المتاحة
        $categories = LabTest::select('subcategory')
            ->whereNotNull('subcategory')
            ->where('subcategory', '!=', '')
            ->distinct()
            ->orderBy('subcategory')
            ->pluck('subcategory');

        return view('lab-tests.entry_settings', compact('labTests', 'stats', 'categories'));
    }

    /**
     * تحديث فوري لنمط إدخال نتيجة فحص مخبري محدد (AJAX)
     */
    public function updateEntryType(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->hasRole('admin') && !$user->can('manage lab tests') && !$user->can('edit lab tests')) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $validated = $request->validate([
            'lab_test_id' => 'required|exists:lab_tests,id',
            'result_entry_type' => 'required|in:single_value,sub_tests,attachment_only,flexible',
        ]);

        $labTest = LabTest::findOrFail($validated['lab_test_id']);
        $labTest->result_entry_type = $validated['result_entry_type'];
        $labTest->save();

        return response()->json([
            'success' => true,
            'message' => "تم تحديث نمط إدخال فحص ({$labTest->name}) إلى: {$labTest->result_entry_type_label}",
            'test_id' => $labTest->id,
            'entry_type' => $labTest->result_entry_type,
            'label' => $labTest->result_entry_type_label,
        ]);
    }

    /**
     * تحديث جماعي لنمط الإدخال لمجموعة من الفحوصات المختبرية
     */
    public function bulkUpdateEntryType(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->hasRole('admin') && !$user->can('manage lab tests') && !$user->can('edit lab tests')) {
            abort(403, 'غير مصرح لك بتنفيذ هذا الإجراء');
        }

        $validated = $request->validate([
            'test_ids' => 'required|array|min:1',
            'test_ids.*' => 'exists:lab_tests,id',
            'result_entry_type' => 'required|in:single_value,sub_tests,attachment_only,flexible',
        ]);

        $count = LabTest::whereIn('id', $validated['test_ids'])
            ->update(['result_entry_type' => $validated['result_entry_type']]);

        $typeLabel = match($validated['result_entry_type']) {
            LabTest::ENTRY_TYPE_SUBTESTS => 'قيم فرعية مفصلة',
            LabTest::ENTRY_TYPE_ATTACHMENT => 'مرفق تقرير الجهاز',
            LabTest::ENTRY_TYPE_FLEXIBLE => 'كلاهما (مرن)',
            default => 'قيمة مباشرة مفردة',
        };

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "تم تحديث نمط إدخال ({$count}) فحصاً إلى ({$typeLabel}) بنجاح",
                'updated_count' => $count,
                'entry_type' => $validated['result_entry_type'],
            ]);
        }

        return redirect()->back()->with('success', "تم تحديث نمط إدخال ({$count}) فحصاً إلى ({$typeLabel}) بنجاح.");
    }
}