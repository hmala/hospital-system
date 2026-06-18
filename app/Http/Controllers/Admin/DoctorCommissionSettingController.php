<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorCommissionSetting;
use Illuminate\Http\Request;

class DoctorCommissionSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Doctor::with(['user', 'department', 'currentCommissionSetting']);

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($builder) use ($search) {
                if (is_numeric($search)) {
                    $builder->where('id', $search);
                }

                $builder->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%");
                });

                $builder->orWhereHas('department', function ($deptQuery) use ($search) {
                    $deptQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        $doctors = $query->orderBy('id')->paginate(20)->withQueryString();
        $q = $request->q;

        return view('admin.doctor_commission_settings.index', compact('doctors', 'q'));
    }

    public function create()
    {
        $doctors = Doctor::with('user')->orderBy('id')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $serviceTypes = ServiceType::where('is_active', true)->ordered()->get();

        return view('admin.doctor_commission_settings.create', compact('doctors', 'departments', 'serviceTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'nullable|exists:departments,id',
            'service_type_id' => 'nullable|exists:service_types,id',
            'commission_value' => 'nullable|numeric|min:0',
            'fixed_amount' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'notes' => 'nullable|string|max:1000',
        ]);

        DoctorCommissionSetting::create([
            'doctor_id' => $data['doctor_id'],
            'department_id' => $data['department_id'] ?? null,
            'service_type_id' => $data['service_type_id'] ?? null,
            'commission_type' => 'fixed',
            'commission_value' => 0,
            'fixed_amount' => $data['fixed_amount'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('admin.doctor-commission-settings.index')
            ->with('success', 'تم إنشاء إعداد عمولة الطبيب بنجاح');
    }

    public function edit(DoctorCommissionSetting $doctorCommissionSetting)
    {
        $doctors = Doctor::with('user')->orderBy('id')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $serviceTypes = ServiceType::where('is_active', true)->ordered()->get();

        return view('admin.doctor_commission_settings.edit', compact('doctorCommissionSetting', 'doctors', 'departments', 'serviceTypes'));
    }

    public function update(Request $request, DoctorCommissionSetting $doctorCommissionSetting)
    {
        $data = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'nullable|exists:departments,id',
            'service_type_id' => 'nullable|exists:service_types,id',
            'commission_value' => 'nullable|numeric|min:0',
            'fixed_amount' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'notes' => 'nullable|string|max:1000',
        ]);

        $doctorCommissionSetting->update([
            'doctor_id' => $data['doctor_id'],
            'department_id' => $data['department_id'] ?? null,
            'service_type_id' => $data['service_type_id'] ?? null,
            'commission_type' => 'fixed',
            'commission_value' => 0,
            'fixed_amount' => $data['fixed_amount'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('admin.doctor-commission-settings.index')
            ->with('success', 'تم تحديث إعداد العمولة بنجاح');
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|array|min:1',
            'doctor_id.*' => 'required|exists:doctors,id',
            'fixed_amount' => 'nullable|array',
            'fixed_amount.*' => 'nullable|numeric|min:0',
            'save_mode' => 'required|in:row,all,first_n',
            'doctor_row' => 'required_if:save_mode,row|exists:doctors,id',
            'rows_to_save' => 'nullable|integer|min:1',
        ]);

        $doctorIds = $validated['doctor_id'];
        $fixedAmounts = $request->input('fixed_amount', []);
        $saveMode = $validated['save_mode'];

        $rowsToSave = null;
        if ($saveMode === 'first_n') {
            $rowsToSave = min($validated['rows_to_save'] ?? count($doctorIds), count($doctorIds));
        }

        $processRows = function (int $index) use ($doctorIds, $fixedAmounts) {
            $doctorId = $doctorIds[$index];
            $fixedAmount = $fixedAmounts[$index] ?? null;

            $doctor = Doctor::findOrFail($doctorId);
            $departmentId = $doctor->department_id;
            $isActive = $doctor->is_active;

            $commissionSetting = DoctorCommissionSetting::where('doctor_id', $doctorId)
                ->latest('id')
                ->first();

            if ($commissionSetting) {
                $commissionSetting->update([
                    'commission_type' => 'fixed',
                    'commission_value' => 0,
                    'fixed_amount' => $fixedAmount ?? null,
                    'department_id' => $departmentId,
                    'service_type_id' => null,
                    'is_active' => $isActive,
                    'notes' => 'تم التعيين من صفحة الأطباء',
                ]);
            } else {
                DoctorCommissionSetting::create([
                    'doctor_id' => $doctorId,
                    'commission_type' => 'fixed',
                    'commission_value' => 0,
                    'fixed_amount' => $fixedAmount ?? null,
                    'department_id' => $departmentId,
                    'service_type_id' => null,
                    'is_active' => $isActive,
                    'notes' => 'تم التعيين من صفحة الأطباء',
                ]);
            }
        };

        if ($saveMode === 'all') {
            foreach (array_keys($doctorIds) as $index) {
                $processRows($index);
            }
        } elseif ($saveMode === 'first_n') {
            foreach (range(0, $rowsToSave - 1) as $index) {
                $processRows($index);
            }
        } else {
            $doctorRowId = $validated['doctor_row'];
            $rowIndex = array_search($doctorRowId, $doctorIds, true);

            if ($rowIndex === false) {
                return redirect()->back()->withInput()->withErrors(['doctor_row' => 'رقم الطبيب غير صالح']);
            }

            $processRows($rowIndex);
        }

        return redirect()->route('admin.doctor-commission-settings.index')
            ->with('success', 'تم حفظ إعداد العمولة بنجاح');
    }

    public function destroy(DoctorCommissionSetting $doctorCommissionSetting)
    {
        $doctorCommissionSetting->delete();

        return redirect()->route('admin.doctor-commission-settings.index')
            ->with('success', 'تم حذف إعداد العمولة بنجاح');
    }
}
