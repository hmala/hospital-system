<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\NicuCustomMedicine;
use App\Models\UserMedicineGroup;
use App\Models\UserMedicineGroupCustomItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserMedicineGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function canAccessDepartmentGroups(string $permission, ?string $department = null): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']) || $user->can($permission)) {
            return true;
        }

        if ($department === 'nicu' && ($user->hasAnyRole(['nicu_doctor', 'nicu_staff', 'doctor', 'resident']) || $user->can('manage nicu prescriptions'))) {
            return true;
        }

        return false;
    }

    protected function canSetPublic(?string $department = null): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])) {
            return true;
        }

        if ($department === 'nicu' && ($user->hasAnyRole(['nicu_doctor', 'nicu_staff', 'doctor', 'resident']) || $user->can('manage nicu prescriptions'))) {
            return true;
        }

        return false;
    }

    public function index(Request $request)
    {
        $department = $request->query('department', 'general');
        abort_unless($this->canAccessDepartmentGroups('view medicine groups', $department), 403, 'غير مصرح لك بعرض الباقات الدوائية');

        $user = Auth::user();

        $groupsQuery = UserMedicineGroup::withCount('medicines')
            ->withCount('customItems')
            ->with(['user', 'medicines', 'customItems'])
            ->orderByDesc('is_starred')
            ->orderByDesc('usage_count')
            ->orderByDesc('is_public')
            ->orderBy('updated_at', 'desc');

        if ($department === 'nicu') {
            $groupsQuery->where('department', 'nicu');
        } else {
            $groupsQuery->where(function ($q) {
                $q->whereNull('department')
                    ->orWhere('department', '!=', 'nicu');
            });
        }

        if (! $user->isAdmin() && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin'])) {
            $groupsQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('is_public', true);
            });
        }

        $groups = $groupsQuery->get();

        return view('medicines.groups.index', compact('groups', 'department'));
    }

    public function store(Request $request)
    {
        $department = $request->input('department', 'general') ?: 'general';
        abort_unless($this->canAccessDepartmentGroups('create medicine groups', $department), 403, 'غير مصرح لك بإنشاء مجموعات');

        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'nullable|boolean',
            'department' => 'nullable|string|max:50',
        ]);

        $isPublic = $request->boolean('is_public') && $this->canSetPublic($department);

        $group = UserMedicineGroup::create([
            'user_id' => $user->id,
            'department' => $department,
            'name' => $request->name,
            'description' => $request->description,
            'is_public' => $isPublic,
            'sort_order' => 0,
        ]);

        return redirect()->route('medicine-groups.index', $department === 'nicu' ? ['department' => 'nicu'] : [])
            ->with('success', 'تم إنشاء المجموعة بنجاح');
    }

    public function saveFromVisit(Request $request)
    {
        $department = $request->input('department', 'general') ?: 'general';
        abort_unless($this->canAccessDepartmentGroups('create medicine groups', $department), 403, 'غير مصرح لك بإنشاء باقات');

        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'nullable|boolean',
            'department' => 'nullable|string|max:50',
            'medications' => 'required|array|min:1',
            'medications.*.name' => 'required|string|max:255',
        ]);

        $isPublic = $request->boolean('is_public') && $this->canSetPublic($department);

        DB::beginTransaction();
        try {
            $group = UserMedicineGroup::create([
                'user_id' => $user->id,
                'department' => $department,
                'name' => $request->name,
                'description' => $request->description,
                'is_public' => $isPublic,
                'sort_order' => 0,
            ]);

            $syncData = [];
            foreach ($request->medications as $medData) {
                $medName = trim((string) $medData['name']);
                $medId = ! empty($medData['medicine_id']) && is_numeric($medData['medicine_id']) ? (int) $medData['medicine_id'] : null;

                if (! $medId) {
                    $medModel = Medicine::whereRaw('LOWER(name) = ?', [mb_strtolower($medName)])->first();
                    if ($medModel) {
                        $medId = $medModel->id;
                    }
                }

                $durationDays = ! empty($medData['duration_days']) ? (int) $medData['duration_days'] : null;

                if ($medId) {
                    $syncData[$medId] = [
                        'dosage_form' => $medData['type'] ?? null,
                        'dosage' => $medData['dosage'] ?? null,
                        'route' => $medData['route'] ?? null,
                        'frequency' => $medData['frequency'] ?? null,
                        'duration' => $medData['duration'] ?? ($durationDays ? "{$durationDays} أيام" : null),
                        'duration_days' => $durationDays,
                        'instructions' => $medData['instructions'] ?? null,
                    ];
                } elseif ($department === 'nicu') {
                    // للخدج: لا نلوث جدول أدوية الصيدلية المتعاقدة، بل نحفظه في عناصر الباقة الخاصة بالخدج + دليل الخدج
                    $customMed = NicuCustomMedicine::whereRaw('LOWER(name) = ?', [mb_strtolower($medName)])->first();
                    if (! $customMed) {
                        $customMed = NicuCustomMedicine::create([
                            'name' => $medName,
                            'default_dose' => $medData['dosage'] ?? null,
                            'default_route' => $medData['route'] ?? null,
                            'default_frequency' => $medData['frequency'] ?? null,
                            'default_duration_days' => $durationDays,
                            'is_active' => true,
                            'created_by' => $user->id,
                        ]);
                    }

                    UserMedicineGroupCustomItem::create([
                        'group_id' => $group->id,
                        'nicu_custom_medicine_id' => $customMed->id,
                        'medicine_name' => $medName,
                        'dosage_form' => $medData['type'] ?? null,
                        'dosage' => $medData['dosage'] ?? null,
                        'route' => $medData['route'] ?? null,
                        'frequency' => $medData['frequency'] ?? null,
                        'duration' => $medData['duration'] ?? ($durationDays ? "{$durationDays} أيام" : null),
                        'duration_days' => $durationDays,
                        'instructions' => $medData['instructions'] ?? null,
                    ]);
                } else {
                    $medModel = Medicine::create([
                        'name' => $medName,
                        'dosage_form' => $medData['type'] ?? 'tablet',
                        'strength' => $medData['dosage'] ?? '',
                        'is_active' => true,
                    ]);
                    $syncData[$medModel->id] = [
                        'dosage_form' => $medData['type'] ?? null,
                        'dosage' => $medData['dosage'] ?? null,
                        'route' => $medData['route'] ?? null,
                        'frequency' => $medData['frequency'] ?? null,
                        'duration' => $medData['duration'] ?? null,
                        'duration_days' => $durationDays,
                        'instructions' => $medData['instructions'] ?? null,
                    ];
                }
            }

            if (! empty($syncData)) {
                $group->medicines()->sync($syncData);
            }

            DB::commit();

            $group->load(['medicines', 'customItems']);
            $formattedMeds = $group->formatted_medications;

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الروشتة كباقة سريعة بنجاح',
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'department' => $group->department,
                    'is_public' => (bool) $group->is_public,
                    'medicines_count' => $formattedMeds->count(),
                    'meds' => $formattedMeds,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الباقة: '.$e->getMessage(),
            ], 500);
        }
    }

    public function reorder(Request $request)
    {
        $user = Auth::user();
        abort_unless($this->canAccessDepartmentGroups('edit medicine groups', 'nicu') || $user->can('edit medicine groups'), 403, 'غير مصرح لك بتعديل ترتيب المجموعات');

        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer|exists:user_medicine_groups,id',
            'orders.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->orders as $item) {
            $group = UserMedicineGroup::find($item['id']);
            if ($group && ($group->user_id === $user->id || $user->isAdmin())) {
                $group->update(['sort_order' => (int) $item['sort_order']]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الترتيب بنجاح',
        ]);
    }

    public function edit(UserMedicineGroup $group)
    {
        abort_unless($this->canAccessDepartmentGroups('edit medicine groups', $group->department), 403, 'غير مصرح لك بتعديل مجموعات');

        $user = Auth::user();

        if ($group->user_id !== $user->id && ! $user->isAdmin() && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin'])) {
            abort(403, 'غير مصرح لك بتحرير هذه المجموعة');
        }

        $group->load(['medicines', 'customItems']);

        $selectedStandard = $group->medicines->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'generic' => $m->generic_name,
            'strength' => $m->strength,
            'dosage_form' => $m->pivot->dosage_form,
            'dosage' => $m->pivot->dosage,
            'route' => $m->pivot->route,
            'frequency' => $m->pivot->frequency,
            'duration' => $m->pivot->duration,
            'duration_days' => $m->pivot->duration_days,
            'instructions' => $m->pivot->instructions,
            'is_custom' => false,
        ]);

        $selectedCustom = $group->customItems->map(fn ($c) => [
            'id' => 'custom_'.$c->id,
            'custom_id' => $c->id,
            'nicu_custom_medicine_id' => $c->nicu_custom_medicine_id,
            'name' => $c->medicine_name,
            'generic' => 'دواء مضاف بقسم الخدج',
            'strength' => '',
            'dosage_form' => $c->dosage_form,
            'dosage' => $c->dosage,
            'route' => $c->route,
            'frequency' => $c->frequency,
            'duration' => $c->duration,
            'duration_days' => $c->duration_days,
            'instructions' => $c->instructions,
            'is_custom' => true,
        ]);

        $selectedMedicines = $selectedStandard->concat($selectedCustom)->values();
        $selectedMedicinesJson = $selectedMedicines->toJson();
        $selectedCount = $selectedMedicines->count();

        $dosageForms = Medicine::where('is_active', true)
            ->whereNotNull('dosage_form')
            ->where('dosage_form', '!=', '')
            ->select('dosage_form', DB::raw('count(*) as total_count'))
            ->groupBy('dosage_form')
            ->orderByDesc('total_count')
            ->get();

        $totalActiveMedicines = Medicine::where('is_active', true)->count();

        return view('medicines.groups.edit', compact(
            'group',
            'selectedMedicinesJson',
            'selectedCount',
            'dosageForms',
            'totalActiveMedicines'
        ));
    }

    public function searchMedicines(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        $dosageForm = trim((string) $request->get('dosage_form', ''));
        $department = trim((string) $request->get('department', 'general'));
        $page = max(1, (int) $request->get('page', 1));
        $limit = max(10, min(100, (int) $request->get('limit', 40)));

        if (mb_strlen($query) < 2 && empty($dosageForm)) {
            return response()->json([]);
        }

        $results = collect();

        // عند البحث من قسم الخدج، نجلب الأدوية المضافة بالخدج أيضاً في الصفحة الأولى
        if ($department === 'nicu' && $page === 1 && ($dosageForm === '' || $dosageForm === 'all' || $dosageForm === 'nicu_custom')) {
            $customQuery = NicuCustomMedicine::active();
            if (mb_strlen($query) >= 2) {
                $customQuery->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('generic_name', 'like', "%{$query}%")
                        ->orWhere('strength', 'like', "%{$query}%");
                });
            }
            $customResults = $customQuery->orderByDesc('usage_count')
                ->limit(15)
                ->get()
                ->map(fn ($c) => [
                    'id' => 'nicu_'.$c->id,
                    'nicu_custom_medicine_id' => $c->id,
                    'name' => $c->name,
                    'generic' => $c->generic_name ?: 'دواء مضاف بالخدج (NICU)',
                    'strength' => $c->strength,
                    'dosage_form' => $c->dosage_form,
                    'dosage' => $c->default_dose,
                    'route' => $c->default_route,
                    'frequency' => $c->default_frequency,
                    'duration_days' => $c->default_duration_days,
                    'is_custom' => true,
                ]);
            $results = $results->concat($customResults);
        }

        if ($dosageForm !== 'nicu_custom') {
            $medicinesQuery = Medicine::where('is_active', true);

            if (! empty($dosageForm) && $dosageForm !== 'all') {
                $medicinesQuery->where('dosage_form', $dosageForm);
            }

            if (mb_strlen($query) >= 2) {
                $medicinesQuery->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('generic_name', 'like', "%{$query}%")
                        ->orWhere('strength', 'like', "%{$query}%");
                });
            }

            $medicines = $medicinesQuery
                ->orderBy('name')
                ->skip(($page - 1) * $limit)
                ->take($limit)
                ->get(['id', 'name', 'generic_name', 'strength', 'dosage_form'])
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'generic' => $m->generic_name,
                    'strength' => $m->strength,
                    'dosage_form' => $m->dosage_form,
                    'is_custom' => false,
                ]);

            $results = $results->concat($medicines);
        }

        return response()->json($results->values());
    }

    public function update(Request $request, UserMedicineGroup $group)
    {
        abort_unless($this->canAccessDepartmentGroups('edit medicine groups', $group->department), 403, 'غير مصرح لك بتعديل مجموعات');

        $user = Auth::user();

        if ($group->user_id !== $user->id && ! $user->isAdmin() && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin'])) {
            abort(403, 'غير مصرح لك بتحرير هذه المجموعة');
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'nullable|boolean',
            'medicine_ids' => 'nullable|array',
            'medicine_ids.*' => 'integer|exists:medicines,id',
            'dosage_form' => 'nullable|array',
            'dosage' => 'nullable|array',
            'route' => 'nullable|array',
            'frequency' => 'nullable|array',
            'duration' => 'nullable|array',
            'duration_days' => 'nullable|array',
            'instructions' => 'nullable|array',
            'custom_items' => 'nullable|array',
        ]);

        $updateData = [];
        if (! empty($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }
        if ($this->canSetPublic($group->department)) {
            $updateData['is_public'] = $request->boolean('is_public');
        }

        if (! empty($updateData)) {
            $group->update($updateData);
        }

        $medicineIds = $validated['medicine_ids'] ?? [];
        $syncData = [];

        foreach ($medicineIds as $id) {
            $syncData[$id] = [
                'dosage_form' => $validated['dosage_form'][$id] ?? null,
                'dosage' => $validated['dosage'][$id] ?? null,
                'route' => $validated['route'][$id] ?? null,
                'frequency' => $validated['frequency'][$id] ?? null,
                'duration' => $validated['duration'][$id] ?? null,
                'duration_days' => ! empty($validated['duration_days'][$id]) ? (int) $validated['duration_days'][$id] : null,
                'instructions' => $validated['instructions'][$id] ?? null,
            ];
        }

        $group->medicines()->sync($syncData);

        // مزامنة الأدوية المضافة المخصصة داخل الباقة
        $group->customItems()->delete();
        foreach ($request->input('custom_items', []) as $customKey => $cItem) {
            $cName = trim((string) ($cItem['name'] ?? ''));
            if ($cName === '') {
                continue;
            }
            UserMedicineGroupCustomItem::create([
                'group_id' => $group->id,
                'nicu_custom_medicine_id' => ! empty($cItem['nicu_custom_medicine_id']) ? (int) $cItem['nicu_custom_medicine_id'] : null,
                'medicine_name' => $cName,
                'dosage_form' => $cItem['dosage_form'] ?? null,
                'dosage' => $cItem['dosage'] ?? null,
                'route' => $cItem['route'] ?? null,
                'frequency' => $cItem['frequency'] ?? null,
                'duration' => $cItem['duration'] ?? null,
                'duration_days' => ! empty($cItem['duration_days']) ? (int) $cItem['duration_days'] : null,
                'instructions' => $cItem['instructions'] ?? null,
            ]);
        }

        return redirect()->route('medicine-groups.edit', $group)->with('success', 'تم حفظ الأدوية للمجموعة بنجاح');
    }

    public function toggleStar(UserMedicineGroup $group)
    {
        abort_unless($this->canAccessDepartmentGroups('view medicine groups', $group->department), 403);

        $user = Auth::user();

        if ($group->user_id !== $user->id && ! $group->is_public && ! $user->isAdmin()) {
            abort(403, 'غير مصرح لك بتعديل هذه المجموعة');
        }

        $group->is_starred = ! $group->is_starred;
        $group->save();

        return response()->json([
            'success' => true,
            'is_starred' => (bool) $group->is_starred,
            'message' => $group->is_starred ? 'تم تثبيت الباقة في المفضلة ⭐' : 'تم إلغاء تثبيت الباقة',
        ]);
    }

    public function incrementUsage(UserMedicineGroup $group)
    {
        $group->increment('usage_count');

        return response()->json([
            'success' => true,
            'usage_count' => (int) $group->usage_count,
        ]);
    }

    public function decrementUsage(UserMedicineGroup $group)
    {
        if ($group->usage_count > 0) {
            $group->decrement('usage_count');
        }

        return response()->json([
            'success' => true,
            'usage_count' => (int) $group->usage_count,
        ]);
    }

    public function resetUsageCounts(Request $request)
    {
        $department = $request->input('department', 'general');
        abort_unless($this->canAccessDepartmentGroups('edit medicine groups', $department), 403);

        $user = Auth::user();
        $query = UserMedicineGroup::query();
        if ($department === 'nicu') {
            $query->where('department', 'nicu');
        } else {
            $query->where(fn ($q) => $q->whereNull('department')->orWhere('department', '!=', 'nicu'));
        }

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $query->update(['usage_count' => 0]);

        return redirect()->back()->with('success', 'تم تصفير جميع عدادات استخدام الباقات بنجاح (0) 🔄');
    }

    public function resetSingleUsage(UserMedicineGroup $group)
    {
        abort_unless($this->canAccessDepartmentGroups('edit medicine groups', $group->department), 403);

        $user = Auth::user();

        if ($group->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403, 'غير مصرح لك بتعديل هذه المجموعة');
        }

        $group->update(['usage_count' => 0]);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تصفير عداد باقة "'.$group->name.'" بنجاح',
                'usage_count' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'تم تصفير عداد استخدام باقة "'.$group->name.'" بنجاح');
    }

    public function destroy(UserMedicineGroup $group)
    {
        abort_unless($this->canAccessDepartmentGroups('delete medicine groups', $group->department), 403, 'غير مصرح لك بحذف مجموعات');

        $user = Auth::user();

        if ($group->user_id !== $user->id && ! $user->isAdmin() && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin'])) {
            abort(403, 'غير مصرح لك بحذف هذه المجموعة');
        }

        $group->delete();

        return redirect()->back()->with('success', 'تم حذف المجموعة بنجاح');
    }
}
