<?php

// app/Http/Controllers/DepartmentController.php

namespace App\Http\Controllers;

use App\Models\Accounting\Account;
use App\Models\Accounting\CostCenter;
use App\Models\Department;
use App\Models\Hospital;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function publicIndex()
    {
        $departments = Department::with(['hospital', 'doctors'])
            ->where('is_active', true)
            ->withCount(['appointments as today_appointments_count' => function ($query) {
                $query->whereDate('appointment_date', today());
            }])
            ->latest()
            ->paginate(10);

        return view('departments.public_index', compact('departments'));
    }

    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('view departments'))) {
            abort(403, 'غير مصرح لك بعرض قائمة العيادات والأقسام');
        }

        $departments = Department::with(['hospital', 'doctors', 'manager', 'costCenter', 'revenueAccount'])
            ->withCount(['appointments as today_appointments_count' => function ($query) {
                $query->whereDate('appointment_date', today());
            }])
            ->latest()
            ->paginate(10);

        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('create departments'))) {
            abort(403, 'غير مصرح لك بإضافة عيادة أو قسم جديد');
        }

        $hospitals = Hospital::all();
        $managers = $this->getStaffManagers();
        $costCenters = CostCenter::orderBy('code')->get();
        $revenueAccounts = Account::where('code', 'LIKE', '43121%')->orderBy('code')->get();

        return view('departments.create', compact('hospitals', 'managers', 'costCenters', 'revenueAccounts'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('create departments'))) {
            abort(403, 'غير مصرح لك بإضافة عيادة أو قسم جديد');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,surgery,pediatrics,obstetrics,orthopedics,cardiology,dentistry,dermatology,emergency,other',
            'room_number' => 'required|string|max:50',
            'consultation_fee' => 'required|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'is_moi_active' => 'nullable|boolean',
            'hi_price' => 'nullable|numeric|min:0',
            'is_hi_active' => 'nullable|boolean',
            'working_hours_start' => 'nullable|date_format:H:i',
            'working_hours_end' => 'nullable|date_format:H:i'.($request->filled('working_hours_start') ? '|after:working_hours_start' : ''),
            'max_patients_per_day' => 'nullable|integer|min:1',
            'manager_id' => 'nullable|exists:users,id',
            'cost_center_id' => 'nullable|exists:acc_cost_centers,id',
            'revenue_account_id' => 'nullable|exists:acc_accounts,id',
        ]);

        $hospital = Hospital::first();
        Department::create([
            'hospital_id' => $hospital ? $hospital->id : 1,
            'manager_id' => $request->manager_id ?: null,
            'cost_center_id' => $request->cost_center_id ?: null,
            'revenue_account_id' => $request->revenue_account_id ?: null,
            'name' => $request->name,
            'type' => $request->type,
            'room_number' => $request->room_number,
            'consultation_fee' => $request->consultation_fee,
            'moi_price' => $request->moi_price,
            'is_moi_active' => $request->has('is_moi_active'),
            'hi_price' => $request->hi_price,
            'is_hi_active' => $request->has('is_hi_active'),
            'working_hours_start' => $request->filled('working_hours_start') ? $request->working_hours_start : null,
            'working_hours_end' => $request->filled('working_hours_end') ? $request->working_hours_end : null,
            'max_patients_per_day' => $request->filled('max_patients_per_day') ? (int) $request->max_patients_per_day : null,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('departments.admin')
            ->with('success', 'تم إضافة العيادة بنجاح');
    }

    public function show(Department $department)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('view departments'))) {
            abort(403, 'غير مصرح لك بعرض بيانات العيادة');
        }

        $department->load(['doctors', 'appointments' => function ($query) {
            $query->whereDate('appointment_date', today());
        }]);

        return view('departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('edit departments'))) {
            abort(403, 'غير مصرح لك بتعديل بيانات العيادة');
        }

        $department->loadMissing(['manager', 'costCenter', 'revenueAccount']);
        $hospitals = Hospital::all();
        $managers = $this->getStaffManagers();
        $costCenters = CostCenter::orderBy('code')->get();
        $revenueAccounts = Account::where('code', 'LIKE', '43121%')->orderBy('code')->get();

        return view('departments.edit', compact('department', 'hospitals', 'managers', 'costCenters', 'revenueAccounts'));
    }

    public function update(Request $request, Department $department)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('edit departments'))) {
            abort(403, 'غير مصرح لك بتعديل بيانات العيادة');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,surgery,pediatrics,obstetrics,orthopedics,cardiology,dentistry,dermatology,emergency,other',
            'room_number' => 'required|string|max:50',
            'consultation_fee' => 'required|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'is_moi_active' => 'nullable|boolean',
            'hi_price' => 'nullable|numeric|min:0',
            'is_hi_active' => 'nullable|boolean',
            'working_hours_start' => 'nullable|date_format:H:i',
            'working_hours_end' => 'nullable|date_format:H:i'.($request->filled('working_hours_start') ? '|after:working_hours_start' : ''),
            'max_patients_per_day' => 'nullable|integer|min:1',
            'manager_id' => 'nullable|exists:users,id',
            'cost_center_id' => 'nullable|exists:acc_cost_centers,id',
            'revenue_account_id' => 'nullable|exists:acc_accounts,id',
        ]);

        $data = $request->all();
        $data['manager_id'] = $request->manager_id ?: null;
        $data['cost_center_id'] = $request->cost_center_id ?: null;
        $data['revenue_account_id'] = $request->revenue_account_id ?: null;
        $data['working_hours_start'] = $request->filled('working_hours_start') ? $request->working_hours_start : null;
        $data['working_hours_end'] = $request->filled('working_hours_end') ? $request->working_hours_end : null;
        $data['max_patients_per_day'] = $request->filled('max_patients_per_day') ? (int) $request->max_patients_per_day : null;
        $data['is_active'] = $request->has('is_active');
        $data['is_moi_active'] = $request->has('is_moi_active');
        $data['is_hi_active'] = $request->has('is_hi_active');

        $department->update($data);

        return redirect()->route('departments.admin')
            ->with('success', 'تم تحديث العيادة بنجاح');
    }

    public function searchManagers(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        if (! $isAdmin && (! $user || (! $user->can('edit departments') && ! $user->can('create departments') && ! $user->can('view departments')))) {
            abort(403, 'غير مصرح لك بالبحث في قائمة مدراء الأقسام');
        }

        $q = trim((string) $request->query('q', ''));

        $query = $this->staffManagersBaseQuery()->with('roles');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $users = $query->orderBy('name')->limit(15)->get(['id', 'name', 'email', 'phone', 'role']);

        return response()->json([
            'data' => $users->map(function ($staff) {
                $roleNames = $staff->roles->pluck('name')->filter(fn ($r) => $r !== 'patient')->values()->all();
                $primaryRole = $roleNames[0] ?? $staff->role;

                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'email' => $staff->email,
                    'phone' => $staff->phone,
                    'role' => $primaryRole,
                ];
            })->values(),
        ]);
    }

    private function staffManagersBaseQuery()
    {
        return \App\Models\User::query()
            ->where(function ($q) {
                $q->whereNull('role')->orWhere('role', '!=', 'patient');
            })
            ->whereDoesntHave('roles', function ($rq) {
                $rq->where('name', 'patient');
            })
            ->whereDoesntHave('patient');
    }

    private function getStaffManagers()
    {
        return $this->staffManagersBaseQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone', 'role']);
    }

    public function destroy(Department $department)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('delete departments'))) {
            abort(403, 'غير مصرح لك بحذف العيادة');
        }

        $department->delete();

        return redirect()->route('departments.admin')
            ->with('success', 'تم حذف العيادة بنجاح');
    }
}
