<?php

namespace App\Http\Controllers;

use App\Models\NicuMilkType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NicuMilkTypeController extends Controller
{
    /**
     * التحقق من صلاحية إدارة وتعديل أنواع الحليب
     */
    protected function authorizeManage(): void
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || (! $user->can('manage nicu services') && ! $user->can('manage nicu care plans')))) {
            abort(403, 'غير مصرح لك بإدارة قائمة أنواع الحليب وتغذية الخدج');
        }
    }

    /**
     * جلب قائمة أنواع الحليب النشطة (JSON للواجهات السريرية)
     */
    public function getActiveList()
    {
        $milkTypes = NicuMilkType::active()->get();

        return response()->json([
            'success' => true,
            'data' => $milkTypes,
        ]);
    }

    /**
     * حفظ نوع حليب جديد
     */
    public function store(Request $request)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? (NicuMilkType::max('sort_order') + 1);

        $milkType = NicuMilkType::create($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة نوع الحليب والتغذية بنجاح',
                'data' => $milkType,
            ]);
        }

        return redirect()->back()->with('success', 'تمت إضافة نوع الحليب والتغذية بنجاح');
    }

    /**
     * تحديث نوع الحليب
     */
    public function update(Request $request, NicuMilkType $milkType)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer',
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $milkType->update($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث نوع الحليب بنجاح',
                'data' => $milkType,
            ]);
        }

        return redirect()->back()->with('success', 'تم تحديث نوع الحليب والتغذية بنجاح');
    }

    /**
     * تبديل حالة التفعيل
     */
    public function toggle(NicuMilkType $milkType)
    {
        $this->authorizeManage();

        $milkType->update(['is_active' => ! $milkType->is_active]);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => (bool) $milkType->is_active,
                'message' => 'تم تغيير حالة نوع الحليب بنجاح',
            ]);
        }

        return redirect()->back()->with('success', 'تم تغيير حالة نوع الحليب بنجاح');
    }

    /**
     * حذف نوع الحليب
     */
    public function destroy(Request $request, NicuMilkType $milkType)
    {
        $this->authorizeManage();

        $milkType->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف نوع الحليب من القائمة بنجاح',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف نوع الحليب من القائمة بنجاح');
    }
}
