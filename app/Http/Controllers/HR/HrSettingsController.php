<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HrFieldRequirement;
use App\Models\HrLookupOption;
use App\Models\Department;
use Illuminate\Http\Request;

class HrSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view hr');
    }

    /**
     * عرض الشاشة الموحدة لإدارة الحقول والخيارات والقوائم المنسدلة
     */
    public function index()
    {
        $fields = HrFieldRequirement::with(['options'])->orderBy('sort_order')->orderBy('id')->get();
        $departmentsCount = Department::count();

        // تجميع الخيارات لكل فئة لسهولة الاستعراض
        $allLookupOptions = HrLookupOption::orderBy('sort_order')->orderBy('name')->get()->groupBy('category');

        return view('hr.settings.index', compact('fields', 'departmentsCount', 'allLookupOptions'));
    }

    /**
     * حفظ وتحديث إعدادات الحقول الإجبارية دفعة واحدة من الجدول
     */
    public function updateFieldRequirements(Request $request)
    {
        $requiredFields = $request->input('required_fields', []);

        $allFields = HrFieldRequirement::where('is_locked', false)->get();

        foreach ($allFields as $field) {
            $field->update([
                'is_required' => in_array($field->field_key, $requiredFields)
            ]);
        }

        return redirect()->route('hr.settings.index')
            ->with('success', 'تم حفظ وتحديث متطلبات الحقول الإجبارية والاختيارية بنجاح.');
    }

    /**
     * تعديل حقل محدد (تغيير اسمه، نوع الحقل: نص/قائمة/تاريخ، أو فئة القائمة)
     */
    public function updateField(Request $request, HrFieldRequirement $field)
    {
        $validated = $request->validate([
            'field_name_ar' => 'required|string|max:255',
            'field_type' => 'required|in:text,select,date,number,file,textarea',
            'is_required' => 'nullable|boolean',
        ]);

        if ($field->is_locked) {
            $validated['is_required'] = true; // الحقول المقفلة تظل إجبارية
        } else {
            $validated['is_required'] = $request->has('is_required');
        }

        // إذا تم تغيير النوع إلى قائمة منسدلة ولم يكن له فئة مسندة، ننشئ له فئة برمز الحقل
        if ($validated['field_type'] === 'select' && empty($field->lookup_category)) {
            $validated['lookup_category'] = $field->field_key;
        }

        $field->update($validated);

        return redirect()->route('hr.settings.index')
            ->with('success', "تم تعديل إعدادات الحقل '{$field->field_name_ar}' بنجاح.");
    }

    /**
     * إضافة خيار جديد إلى أي قائمة منسدلة
     */
    public function storeLookupOption(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ], [
            'name.required' => 'يرجى إدخال اسم الخيار.',
        ]);

        $validated['is_active'] = true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        HrLookupOption::create($validated);

        return redirect()->route('hr.settings.index')
            ->with('success', "تمت إضافة الخيار '{$validated['name']}' بنجاح إلى القائمة المنسدلة.");
    }

    /**
     * تعديل خيار في أي قائمة منسدلة
     */
    public function updateLookupOption(Request $request, HrLookupOption $option)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $option->update($validated);

        return redirect()->route('hr.settings.index')
            ->with('success', "تم تعديل الخيار '{$option->name}' بنجاح.");
    }

    /**
     * تفعيل / تعطيل خيار في القائمة المنسدلة
     */
    public function toggleLookupOption(HrLookupOption $option)
    {
        $option->update([
            'is_active' => !$option->is_active
        ]);

        $statusText = $option->is_active ? 'تفعيل' : 'تعطيل';
        return redirect()->route('hr.settings.index')
            ->with('success', "تم {$statusText} الخيار '{$option->name}' بنجاح.");
    }

    /**
     * حذف خيار من القائمة
     */
    public function destroyLookupOption(HrLookupOption $option)
    {
        $name = $option->name;
        $option->delete();

        return redirect()->route('hr.settings.index')
            ->with('success', "تم حذف الخيار '{$name}' بنجاح.");
    }
}
