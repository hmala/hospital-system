<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\UserMedicineGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserMedicineGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view medicine groups');
    }

    public function index()
    {
        $user = Auth::user();

        $groupsQuery = UserMedicineGroup::withCount('medicines')
            ->with(['user', 'medicines'])
            ->orderByDesc('is_starred')
            ->orderByDesc('usage_count')
            ->orderByDesc('is_public')
            ->orderBy('updated_at', 'desc');

        if (!$user->isAdmin()) {
            $groupsQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('is_public', true);
            });
        }

        $groups = $groupsQuery->get();

        return view('medicines.groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('create medicine groups'), 403, 'غير مصرح لك بإنشاء مجموعات');

        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'nullable|boolean',
        ]);

        $isPublic = false;
        if ($request->boolean('is_public') && ($user->isAdmin() || $user->hasRole('admin'))) {
            $isPublic = true;
        }

        UserMedicineGroup::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'is_public' => $isPublic,
            'sort_order' => 0,
        ]);

        return redirect()->back()->with('success', 'تم إنشاء المجموعة بنجاح');
    }

    public function saveFromVisit(Request $request)
    {
        abort_unless(Auth::user()->can('create medicine groups'), 403, 'غير مصرح لك بإنشاء باقات');

        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'nullable|boolean',
            'medications' => 'required|array|min:1',
            'medications.*.name' => 'required|string|max:255',
        ]);

        $isPublic = false;
        if ($request->boolean('is_public') && ($user->isAdmin() || $user->hasRole('admin'))) {
            $isPublic = true;
        }

        DB::beginTransaction();
        try {
            $group = UserMedicineGroup::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'description' => $request->description,
                'is_public' => $isPublic,
                'sort_order' => 0,
            ]);

            $syncData = [];
            foreach ($request->medications as $medData) {
                $medId = !empty($medData['medicine_id']) ? (int) $medData['medicine_id'] : null;

                if (!$medId) {
                    // Try to find medicine by name
                    $medModel = Medicine::where('name', $medData['name'])->first();
                    if ($medModel) {
                        $medId = $medModel->id;
                    } else {
                        // Create a record in medicines if not existing to link it
                        $medModel = Medicine::create([
                            'name' => $medData['name'],
                            'dosage_form' => $medData['type'] ?? 'tablet',
                            'strength' => $medData['dosage'] ?? '',
                            'is_active' => true,
                        ]);
                        $medId = $medModel->id;
                    }
                }

                if ($medId) {
                    $syncData[$medId] = [
                        'dosage_form' => $medData['type'] ?? null,
                        'dosage' => $medData['dosage'] ?? null,
                        'frequency' => $medData['frequency'] ?? null,
                        'duration' => $medData['duration'] ?? null,
                        'instructions' => $medData['instructions'] ?? null,
                    ];
                }
            }

            if (!empty($syncData)) {
                $group->medicines()->sync($syncData);
            }

            DB::commit();

            $group->load('medicines');

            $formattedMeds = $group->medicines->map(function ($m) {
                return [
                    'medicine_id' => $m->id,
                    'name' => $m->name,
                    'type' => $m->pivot->dosage_form,
                    'dosage' => $m->pivot->dosage,
                    'frequency' => $m->pivot->frequency,
                    'duration' => $m->pivot->duration,
                    'instructions' => $m->pivot->instructions,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الروشتة كباقة سريعة بنجاح',
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'description' => $group->description,
                    'is_public' => (bool) $group->is_public,
                    'medicines_count' => $group->medicines->count(),
                    'meds' => $formattedMeds,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الباقة: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reorder(Request $request)
    {
        abort_unless(Auth::user()->can('edit medicine groups'), 403, 'غير مصرح لك بتعديل ترتيب المجموعات');

        $user = Auth::user();

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
        abort_unless(Auth::user()->can('edit medicine groups'), 403, 'غير مصرح لك بتعديل مجموعات');

        $user = Auth::user();

        if ($group->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'غير مصرح لك بتحرير هذه المجموعة');
        }

        $selectedMedicines = $group->medicines->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
            'generic' => $m->generic_name,
            'strength' => $m->strength,
            'dosage_form' => $m->pivot->dosage_form,
            'dosage' => $m->pivot->dosage,
            'frequency' => $m->pivot->frequency,
            'duration' => $m->pivot->duration,
            'instructions' => $m->pivot->instructions,
        ])->values();

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
        $page = max(1, (int) $request->get('page', 1));
        $limit = max(10, min(100, (int) $request->get('limit', 40)));

        if (mb_strlen($query) < 2 && empty($dosageForm)) {
            return response()->json([]);
        }

        $medicinesQuery = Medicine::where('is_active', true);

        if (!empty($dosageForm) && $dosageForm !== 'all') {
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
            ]);

        return response()->json($medicines);
    }

    public function update(Request $request, UserMedicineGroup $group)
    {
        abort_unless(Auth::user()->can('edit medicine groups'), 403, 'غير مصرح لك بتعديل مجموعات');

        $user = Auth::user();

        if ($group->user_id !== $user->id && !$user->isAdmin()) {
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
            'frequency' => 'nullable|array',
            'duration' => 'nullable|array',
            'instructions' => 'nullable|array',
        ]);

        $updateData = [];
        if (!empty($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (array_key_exists('description', $validated)) {
            $updateData['description'] = $validated['description'];
        }
        if ($user->isAdmin()) {
            $updateData['is_public'] = $request->boolean('is_public');
        }

        if (!empty($updateData)) {
            $group->update($updateData);
        }

        $medicineIds = $validated['medicine_ids'] ?? [];
        $syncData = [];

        foreach ($medicineIds as $id) {
            $syncData[$id] = [
                'dosage_form' => $validated['dosage_form'][$id] ?? null,
                'dosage' => $validated['dosage'][$id] ?? null,
                'frequency' => $validated['frequency'][$id] ?? null,
                'duration' => $validated['duration'][$id] ?? null,
                'instructions' => $validated['instructions'][$id] ?? null,
            ];
        }

        $group->medicines()->sync($syncData);

        return redirect()->route('medicine-groups.edit', $group)->with('success', 'تم حفظ الأدوية للمجموعة بنجاح');
    }

    public function toggleStar(UserMedicineGroup $group)
    {
        abort_unless(Auth::user()->can('edit medicine groups') || Auth::user()->can('view medicine groups'), 403);

        $user = Auth::user();

        if ($group->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'غير مصرح لك بتعديل هذه المجموعة');
        }

        $group->is_starred = !$group->is_starred;
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
        abort_unless(Auth::user()->can('edit medicine groups'), 403);

        $user = Auth::user();

        if ($user->isAdmin()) {
            UserMedicineGroup::query()->update(['usage_count' => 0]);
        } else {
            UserMedicineGroup::where('user_id', $user->id)->update(['usage_count' => 0]);
        }

        return redirect()->back()->with('success', 'تم تصفير جميع عدادات استخدام الباقات بنجاح (0) 🔄');
    }

    public function resetSingleUsage(UserMedicineGroup $group)
    {
        abort_unless(Auth::user()->can('edit medicine groups'), 403);

        $user = Auth::user();

        if ($group->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'غير مصرح لك بتعديل هذه المجموعة');
        }

        $group->update(['usage_count' => 0]);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تصفير عداد باقة "' . $group->name . '" بنجاح',
                'usage_count' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'تم تصفير عداد استخدام باقة "' . $group->name . '" بنجاح');
    }

    public function destroy(UserMedicineGroup $group)
    {
        abort_unless(Auth::user()->can('delete medicine groups'), 403, 'غير مصرح لك بحذف مجموعات');

        $user = Auth::user();

        if ($group->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'غير مصرح لك بحذف هذه المجموعة');
        }

        $group->delete();

        return redirect()->back()->with('success', 'تم حذف المجموعة بنجاح');
    }
}
