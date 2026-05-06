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

    public function index()
    {
        $user = Auth::user();

        $groupsQuery = UserLabTestGroup::withCount('labTests')
            ->with('user')
            ->orderBy('updated_at', 'desc');

        if (!$user->hasRole('admin')) {
            $groupsQuery->where('user_id', $user->id);
        }

        $groups = $groupsQuery->get();

        return view('lab-tests.groups.index', compact('groups'));
    }

    public function store(Request $request)
    {
        abort_unless(Auth::user()->can('create lab test groups'), 403, 'غير مصرح لك بإنشاء مجموعات');
        
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        UserLabTestGroup::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'تم إنشاء المجموعة بنجاح');
    }

    public function edit(UserLabTestGroup $group)
    {
        abort_unless(Auth::user()->can('edit lab test groups'), 403, 'غير مصرح لك بتعديل مجموعات');
        
        $user = Auth::user();

        if ($group->user_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بتحرير هذه المجموعة');
        }

        $labTests = \App\Models\LabTest::where('is_active', true)
            ->orderBy('main_category')
            ->orderBy('name')
            ->get()
            ->groupBy('main_category');

        $selectedTestIds = $group->labTests->pluck('id')->toArray();

        return view('lab-tests.groups.edit', compact('group', 'labTests', 'selectedTestIds'));
    }

    public function update(Request $request, UserLabTestGroup $group)
    {
        $user = Auth::user();

        if ($group->user_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بتحرير هذه المجموعة');
        }

        $validated = $request->validate([
            'lab_test_ids' => 'nullable|array',
            'lab_test_ids.*' => 'integer|exists:lab_tests,id',
        ]);

        $group->labTests()->sync($validated['lab_test_ids'] ?? []);

        return redirect()->route('lab-tests.groups.edit', $group)->with('success', 'تم حفظ التحاليل للمجموعة بنجاح');
    }

    public function destroy(UserLabTestGroup $group)
    {
        $user = Auth::user();

        if ($group->user_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'غير مصرح لك بحذف هذه المجموعة');
        }

        $group->delete();

        return redirect()->back()->with('success', 'تم حذف المجموعة بنجاح');
    }
}
