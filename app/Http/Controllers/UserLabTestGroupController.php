<?php

namespace App\Http\Controllers;

use App\Models\UserLabTestGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserLabTestGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('can:view lab test groups');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $department = $request->query('department');

        $groupsQuery = UserLabTestGroup::withCount('labTests')
            ->with('user')
            ->orderBy('updated_at', 'desc');

        if ($department === 'nicu') {
            $groupsQuery->where('department', 'nicu');
        } elseif ($department === 'consultant') {
            $groupsQuery->where(function ($q) {
                $q->whereNull('department')->orWhere('department', 'consultant');
            });
        }

        if (!$user->isAdmin()) {
            $groupsQuery->where(function ($q) use ($user, $department) {
                $q->where('user_id', $user->id);
                if ($department) {
                    $q->orWhere(function ($sub) use ($department) {
                        $sub->where('department', $department)->where('is_public', true);
                    });
                }
            });
        }

        $groups = $groupsQuery->get();

        return view('lab-tests.groups.index', compact('groups', 'department'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('create lab test groups'), 403, 'غير مصرح لك بإنشاء مجموعات');
        
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'department' => 'nullable|string|in:nicu,consultant',
            'is_public' => 'nullable|boolean',
        ]);

        $group = UserLabTestGroup::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'department' => $request->department ?: null,
            'is_public' => $user->isAdmin() ? $request->boolean('is_public') : false,
        ]);

        $targetUrl = route('lab-tests.groups.edit', $group);
        if ($request->filled('department')) {
            $targetUrl .= '?department=' . urlencode($request->department);
        }

        return redirect($targetUrl)->with('success', 'تم إنشاء المجموعة بنجاح. يمكنك الآن اختيار التحاليل الخاصة بها.');
    }

    public function edit(UserLabTestGroup $group)
    {
        abort_unless(Auth::user()->can('edit lab test groups'), 403, 'غير مصرح لك بتعديل مجموعات');
        
        $user = Auth::user();

        if ($group->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'غير مصرح لك بتحرير هذه المجموعة');
        }

        $categoryNames = [
            'biochemistry' => 'الكيمياء الحيوية (Biochemistry)',
            'hormone' => 'الهرمونات (Hormone)',
            'hematology' => 'أمراض الدم (Hematology)',
            'haematology' => 'أمراض الدم (Hematology)',
            'immunity' => 'المناعة (Immunology)',
            'immunology' => 'المناعة (Immunology)',
            'serology' => 'الأمصال (Serology)',
            'infectious disease' => 'الأمراض المعدية (Infectious Diseases)',
            'microbiology' => 'الأحياء المجهرية (Microbiology)',
            'virology' => 'الفيروسات (Virology)',
            'histopathology' => 'علم الأمراض النسيجي والخزعات (Histopathology)',
            'pcr' => 'الفحوصات الجزيئية (PCR)',
        ];

        $labTests = \App\Models\LabTest::where('is_active', true)
            ->orderBy('subcategory')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($test) use ($categoryNames) {
                $subKey = strtolower(trim($test->subcategory ?? ''));
                return $categoryNames[$subKey] ?? ($test->subcategory ?: 'تحاليل أخرى');
            });

        $selectedTestIds = $group->labTests->pluck('id')->toArray();

        return view('lab-tests.groups.edit', compact('group', 'labTests', 'selectedTestIds'));
    }

    public function update(Request $request, UserLabTestGroup $group)
    {
        $user = Auth::user();

        if ($group->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'غير مصرح لك بتحرير هذه المجموعة');
        }

        if ($request->has('lab_test_ids')) {
            $validated = $request->validate([
                'lab_test_ids' => 'nullable|array',
                'lab_test_ids.*' => 'integer|exists:lab_tests,id',
            ]);

            $group->labTests()->sync($validated['lab_test_ids'] ?? []);
        }

        if ($request->filled('name')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'department' => 'nullable|string|in:nicu,consultant',
                'is_public' => 'nullable|boolean',
            ]);

            $updateData = [
                'name' => $request->name,
                'description' => $request->description,
                'department' => $request->department ?: null,
            ];

            if ($user->isAdmin()) {
                $updateData['is_public'] = $request->boolean('is_public');
            }

            $group->update($updateData);
        }

        return redirect()->route('lab-tests.groups.edit', $group)->with('success', 'تم حفظ التعديلات للمجموعة بنجاح');
    }

    public function destroy(UserLabTestGroup $group)
    {
        $user = Auth::user();

        if ($group->user_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'غير مصرح لك بحذف هذه المجموعة');
        }

        $group->delete();

        return redirect()->back()->with('success', 'تم حذف المجموعة بنجاح');
    }
}
