<?php

namespace App\Http\Controllers;

use App\Models\NicuCustomMedicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NicuCustomMedicineController extends Controller
{
    protected function authorizeManage(): void
    {
        $user = Auth::user();
        if (! $user) {
            abort(401);
        }

        if ($user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])) {
            return;
        }

        if ($user->hasAnyPermission(['manage nicu prescriptions', 'manage nicu clinical', 'manage nicu services'])
            || $user->hasRole(['nicu_doctor', 'nicu_staff', 'doctor', 'resident'])) {
            return;
        }

        abort(403, 'غير مصرح لك بإدارة دليل الأدوية المضافة لقسم الخدج.');
    }

    public function index(Request $request)
    {
        $this->authorizeManage();

        $search = trim((string) $request->query('search', ''));
        $query = NicuCustomMedicine::with('creator')
            ->orderByDesc('is_active')
            ->orderByDesc('usage_count')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('strength', 'like', "%{$search}%");
            });
        }

        $customMedicines = $query->paginate(25)->withQueryString();

        $stats = [
            'total' => NicuCustomMedicine::count(),
            'active' => NicuCustomMedicine::where('is_active', true)->count(),
            'total_usages' => (int) NicuCustomMedicine::sum('usage_count'),
        ];

        return view('nicu.custom-medicines.index', compact('customMedicines', 'search', 'stats'));
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:nicu_custom_medicines,name',
            'generic_name' => 'nullable|string|max:255',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'default_dose' => 'nullable|string|max:100',
            'default_route' => 'nullable|string|max:100',
            'default_frequency' => 'nullable|string|max:100',
            'default_duration_days' => 'nullable|integer|min:1|max:365',
            'notes' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'يرجى إدخال اسم الدواء أو التركيبة.',
            'name.unique' => 'هذا الدواء مسجل مسبقاً في دليل أدوية الخدج.',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['is_active'] = true;

        NicuCustomMedicine::create($validated);

        return redirect()->route('nicu.custom-medicines.index')
            ->with('success', 'تمت إضافة الدواء إلى دليل أدوية الخدج بنجاح وسيظهر تلقائياً بالبحث.');
    }

    public function update(Request $request, NicuCustomMedicine $customMedicine)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:nicu_custom_medicines,name,'.$customMedicine->id,
            'generic_name' => 'nullable|string|max:255',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'default_dose' => 'nullable|string|max:100',
            'default_route' => 'nullable|string|max:100',
            'default_frequency' => 'nullable|string|max:100',
            'default_duration_days' => 'nullable|integer|min:1|max:365',
            'notes' => 'nullable|string|max:1000',
        ]);

        $customMedicine->update($validated);

        return redirect()->route('nicu.custom-medicines.index')
            ->with('success', 'تم تحديث بيانات الدواء في دليل الخدج بنجاح.');
    }

    public function toggle(NicuCustomMedicine $customMedicine)
    {
        $this->authorizeManage();

        $customMedicine->update(['is_active' => ! $customMedicine->is_active]);

        $status = $customMedicine->is_active ? 'تفعيل' : 'تعطيل';

        return redirect()->back()->with('success', "تم {$status} الدواء ({$customMedicine->name}) بنجاح.");
    }

    public function destroy(NicuCustomMedicine $customMedicine)
    {
        $this->authorizeManage();

        $customMedicine->delete();

        return redirect()->back()->with('success', 'تم حذف الدواء من دليل أدوية الخدج بنجاح.');
    }
}
