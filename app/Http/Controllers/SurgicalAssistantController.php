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

        if (! $isAdmin && ! $user->can('view surgical assistants') && ! $user->can('manage surgical assistants')) {
            abort(403, 'غير مصرح لك بالوصول إلى إدارة مساعدي الجراحين');
        }

        // Auto-ensure theater_attendant_name column exists in database
        if (! \Illuminate\Support\Facades\Schema::hasColumn('surgeries', 'theater_attendant_name')) {
            try {
                \Illuminate\Support\Facades\Schema::table('surgeries', function ($table) {
                    $table->string('theater_attendant_name')->nullable()->after('anesthesia_assistant_name');
                });
            } catch (\Throwable $e) {
            }
        }
        if (! \Illuminate\Support\Facades\Schema::hasColumn('anesthesia_stations', 'theater_attendant_name')) {
            try {
                \Illuminate\Support\Facades\Schema::table('anesthesia_stations', function ($table) {
                    $table->string('theater_attendant_name')->nullable()->after('anesthesia_assistant_name');
                });
            } catch (\Throwable $e) {
            }
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

        // Type filter (surgical / anesthesia / theater_attendant)
        if ($request->filled('type') && in_array($request->type, ['surgical', 'anesthesia', 'theater_attendant'], true)) {
            $query->where('type', $request->type);
        }

        $assistants = $query->orderBy('name', 'asc')->paginate(25)->withQueryString();

        // Get surgery counts for each assistant in batch
        $assistantNames = $assistants->pluck('name')->toArray();
        $surgeriesCounts = [];
        if (! empty($assistantNames)) {
            $hasAttendantCol = \Illuminate\Support\Facades\Schema::hasColumn('surgeries', 'theater_attendant_name');
            $surgCounts = Surgery::whereIn('surgical_assistant_name', $assistantNames)
                ->selectRaw('surgical_assistant_name as name, count(*) as count')
                ->groupBy('surgical_assistant_name')
                ->pluck('count', 'name')
                ->toArray();
            $anesthCounts = Surgery::whereIn('anesthesia_assistant_name', $assistantNames)
                ->selectRaw('anesthesia_assistant_name as name, count(*) as count')
                ->groupBy('anesthesia_assistant_name')
                ->pluck('count', 'name')
                ->toArray();
            $attendantCounts = $hasAttendantCol
                ? Surgery::whereIn('theater_attendant_name', $assistantNames)
                    ->selectRaw('theater_attendant_name as name, count(*) as count')
                    ->groupBy('theater_attendant_name')
                    ->pluck('count', 'name')
                    ->toArray()
                : [];

            foreach ($assistantNames as $name) {
                $surgeriesCounts[$name] = ($surgCounts[$name] ?? 0) + ($anesthCounts[$name] ?? 0) + ($attendantCounts[$name] ?? 0);
            }
        }

        $totalCount = SurgicalAssistant::count();
        $activeCount = SurgicalAssistant::where('is_active', true)->count();
        $inactiveCount = $totalCount - $activeCount;

        $canManage = $isAdmin || $user->can('manage surgical assistants');

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
            'type' => 'nullable|string|in:surgical,anesthesia,theater_attendant',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'اسم المساعد مطلوب',
            'name.unique' => 'اسم المساعد مسجل مسبقاً',
        ]);

        $validated['type'] = $request->input('type', SurgicalAssistant::TYPE_SURGICAL) ?: SurgicalAssistant::TYPE_SURGICAL;
        $validated['is_active'] = $request->boolean('is_active', true);

        SurgicalAssistant::create($validated);

        $label = match ($validated['type']) {
            SurgicalAssistant::TYPE_ANESTHESIA => 'مساعد التخدير',
            SurgicalAssistant::TYPE_THEATER_ATTENDANT => 'معين الصالة',
            default => 'مساعد الجراح',
        };

        return redirect()->route('surgical-assistants.index')
            ->with('success', "تمت إضافة {$label} بنجاح");
    }

    /**
     * Update the specified surgical assistant.
     */
    public function update(Request $request, SurgicalAssistant $surgicalAssistant): RedirectResponse
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:surgical_assistants,name,'.$surgicalAssistant->id,
            'type' => 'nullable|string|in:surgical,anesthesia,theater_attendant',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required' => 'اسم المساعد مطلوب',
            'name.unique' => 'اسم المساعد مسجل مسبقاً',
        ]);

        if ($request->filled('type')) {
            $validated['type'] = $request->input('type');
        }
        $validated['is_active'] = $request->boolean('is_active', true);

        $surgicalAssistant->update($validated);

        return redirect()->route('surgical-assistants.index')
            ->with('success', 'تم تحديث بيانات المساعد بنجاح');
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

        if (! $isAdmin && ! $user->can('manage surgical assistants')) {
            abort(403, 'غير مصرح لك بإدارة مساعدي الجراحين');
        }
    }
}
