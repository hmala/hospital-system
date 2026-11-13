<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\SurgicalAssistant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurgicalAssistantController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of surgical assistants.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && ! $user->can('view surgeries') && ! $user->can('edit surgeries') && ! $user->hasRole('surgery_staff')) {
            abort(403, 'غير مصرح لك بالوصول إلى إدارة مساعدي الجراحين');
        }

        $query = SurgicalAssistant::query();

        // Search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $assistants = $query->orderBy('name', 'asc')->paginate(25)->withQueryString();

        // Get surgery counts for each assistant in batch
        $assistantNames = $assistants->pluck('name')->toArray();
        $surgeriesCounts = ! empty($assistantNames)
            ? Surgery::whereIn('surgical_assistant_name', $assistantNames)
                ->selectRaw('surgical_assistant_name, count(*) as count')
                ->groupBy('surgical_assistant_name')
                ->pluck('count', 'surgical_assistant_name')
                ->toArray()
            : [];

        $totalCount = SurgicalAssistant::count();
        $activeCount = SurgicalAssistant::where('is_active', true)->count();
        $inactiveCount = $totalCount - $activeCount;

        $canManage = $isAdmin || $user->can('edit surgeries') || $user->hasRole('surgery_staff');

        return view('surgical-assistants.index', compact(
            'assistants',
            'surgeriesCounts',
            'totalCount',
            'activeCount',
            'inactiveCount',
            'canManage'
        ));
    }

    /**
     * Store a newly created surgical assistant.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:surgical_assistants,name',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'اسم مساعد الجراح مطلوب',
            'name.unique' => 'اسم مساعد الجراح مسجل مسبقاً',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        SurgicalAssistant::create($validated);

        return redirect()->route('surgical-assistants.index')
            ->with('success', 'تمت إضافة مساعد الجراح بنجاح');
    }

    /**
     * Update the specified surgical assistant.
     */
    public function update(Request $request, SurgicalAssistant $surgicalAssistant): RedirectResponse
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:surgical_assistants,name,'.$surgicalAssistant->id,
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'اسم مساعد الجراح مطلوب',
            'name.unique' => 'اسم مساعد الجراح مسجل مسبقاً',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $surgicalAssistant->update($validated);

        return redirect()->route('surgical-assistants.index')
            ->with('success', 'تم تحديث بيانات مساعد الجراح بنجاح');
    }

    /**
     * Toggle the active status of the specified assistant.
     */
    public function toggleStatus(SurgicalAssistant $surgicalAssistant): RedirectResponse
    {
        $this->authorizeManagement();

        $surgicalAssistant->update([
            'is_active' => ! $surgicalAssistant->is_active,
        ]);

        $statusText = $surgicalAssistant->is_active ? 'تفعيل' : 'تعطيل';

        return redirect()->back()
            ->with('success', "تم {$statusText} مساعد الجراح ({$surgicalAssistant->name}) بنجاح");
    }

    /**
     * Remove the specified surgical assistant.
     */
    public function destroy(SurgicalAssistant $surgicalAssistant): RedirectResponse
    {
        $this->authorizeManagement();

        $name = $surgicalAssistant->name;
        $surgicalAssistant->delete();

        return redirect()->route('surgical-assistants.index')
            ->with('success', "تم حذف مساعد الجراح ({$name}) بنجاح");
    }

    /**
     * Check if the authenticated user has management authority.
     */
    private function authorizeManagement(): void
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && ! $user->can('edit surgeries') && ! $user->hasRole('surgery_staff')) {
            abort(403, 'غير مصرح لك بإدارة مساعدي الجراحين');
        }
    }
}
