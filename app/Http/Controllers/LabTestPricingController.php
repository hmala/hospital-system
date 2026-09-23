<?php

namespace App\Http\Controllers;

use App\Models\LabTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LabTestPricingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function authorizeLabPricing()
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->can('manage lab tests') && !$user->can('edit lab tests') && !$user->hasRole('lab_staff')) {
            abort(403, 'غير مصرح لك بالوصول إلى إعدادات أسعار وتصنيفات التحاليل');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeLabPricing();

        $query = LabTest::query();

        // فلترة بالبحث (الاسم أو الكود)
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

        // فلترة بالحالة أو نوع السعر
        if ($request->filled('filter_type')) {
            switch ($request->filter_type) {
                case 'missing_hi':
                    $query->whereNull('hi_price')->orWhere('hi_price', 0);
                    break;
                case 'missing_moi':
                    $query->whereNull('moi_price')->orWhere('moi_price', 0);
                    break;
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }

        $perPage = (int) $request->input('per_page', 50);
        if (!in_array($perPage, [25, 50, 100, 200])) {
            $perPage = 50;
        }

        $labTests = $query->orderBy('subcategory')->orderBy('name')->paginate($perPage)->withQueryString();

        // جميع التصنيفات الحالية الفرعية
        $categories = LabTest::select('subcategory')
            ->whereNotNull('subcategory')
            ->where('subcategory', '!=', '')
            ->distinct()
            ->orderBy('subcategory')
            ->pluck('subcategory');

        return view('lab-tests.pricing_settings', compact('labTests', 'categories'));
    }

    public function save(Request $request)
    {
        $this->authorizeLabPricing();

        $validated = $request->validate([
            'test_id' => 'required|array|min:1',
            'test_id.*' => 'required|exists:lab_tests,id',
            'save_mode' => 'required|in:row,all',
            'target_id' => 'nullable|exists:lab_tests,id',
            'price' => 'nullable|array',
            'hi_price' => 'nullable|array',
            'moi_price' => 'nullable|array',
            'subcategory' => 'nullable|array',
            'is_active' => 'nullable|array',
        ]);

        $testIds = $validated['test_id'];
        $saveMode = $validated['save_mode'];
        $targetId = $validated['target_id'] ?? null;

        $cleanNumber = function ($val) {
            if ($val === null || $val === '') return null;
            $clean = str_replace([',', ' '], ['', ''], (string) $val);
            return is_numeric($clean) ? (float) $clean : null;
        };

        $updateSingleTest = function ($index) use ($testIds, $request, $cleanNumber) {
            $id = $testIds[$index];
            $test = LabTest::findOrFail($id);

            $price = $cleanNumber($request->input("price.{$index}"));
            $hiPrice = $cleanNumber($request->input("hi_price.{$index}"));
            $moiPrice = $cleanNumber($request->input("moi_price.{$index}"));
            $subcategory = trim((string) $request->input("subcategory.{$index}"));
            $isActive = $request->has("is_active.{$index}") ? (bool) $request->input("is_active.{$index}") : false;

            $updateData = [
                'price' => $price,
                'hi_price' => $hiPrice,
                'moi_price' => $moiPrice,
                'is_active' => $isActive,
            ];

            if ($subcategory !== '') {
                $updateData['subcategory'] = $subcategory;
            }

            $test->update($updateData);
            return $test;
        };

        if ($saveMode === 'all') {
            DB::beginTransaction();
            try {
                foreach (array_keys($testIds) as $index) {
                    $updateSingleTest($index);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء الحفظ: ' . $e->getMessage()], 500);
                }
                return back()->with('error', 'حدث خطأ أثناء حفظ التحاليل');
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم حفظ جميع الأسعار والتصنيفات بنجاح']);
            }
            return back()->with('success', 'تم حفظ جميع الأسعار والتصنيفات بنجاح');
        }

        // حفظ سطر فردي
        $targetIndex = array_search($targetId, $testIds);
        if ($targetIndex === false) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'تعذر العثور على التحليل المستهدف'], 422);
            }
            return back()->with('error', 'تعذر العثور على التحليل المستهدف');
        }

        $test = $updateSingleTest($targetIndex);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث فحص: ' . $test->name,
                'test' => [
                    'id' => $test->id,
                    'price' => $test->price,
                    'hi_price' => $test->hi_price,
                    'moi_price' => $test->moi_price,
                    'subcategory' => $test->subcategory,
                    'is_active' => $test->is_active,
                ]
            ]);
        }

        return back()->with('success', 'تم تحديث فحص: ' . $test->name . ' بنجاح');
    }

    public function renameCategory(Request $request)
    {
        $this->authorizeLabPricing();

        $validated = $request->validate([
            'old_category' => 'required|string',
            'new_category' => 'required|string|max:100',
        ]);

        $oldCat = trim($validated['old_category']);
        $newCat = trim($validated['new_category']);

        if ($oldCat === $newCat) {
            return back()->with('warning', 'الاسم الجديد مطابق للاسم القديم');
        }

        $updatedCount = LabTest::where('subcategory', $oldCat)->update(['subcategory' => $newCat]);

        return back()->with('success', "تم تعديل اسم التصنيف من \"{$oldCat}\" إلى \"{$newCat}\" وتحديث {$updatedCount} تحليل بنجاح.");
    }
}
