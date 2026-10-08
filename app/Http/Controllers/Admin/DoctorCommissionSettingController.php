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
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
            if (!$isAdmin && (!$user || !$user->can('manage doctor commissions'))) {
                abort(403, 'غير مصرح لك بإدارة إعدادات عمولات الأطباء.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $baseQuery = Doctor::where(function ($q) {
            $q->where('type', 'consultant')->orWhereNull('type');
        });

        $query = (clone $baseQuery)->with(['user', 'department', 'currentCommissionSetting']);

        // إحصاءات عامة لأطباء الاستشارية
        $totalDoctors = (clone $baseQuery)->count();
        $assignedDoctors = (clone $baseQuery)->whereHas('currentCommissionSetting', function ($q) {
            $q->whereNotNull('fixed_amount')->where('fixed_amount', '>', 0);
        })->count();
        $unassignedDoctors = $totalDoctors - $assignedDoctors;
        $activeDoctors = (clone $baseQuery)->where('is_active', true)->count();

        // فلترة بالقسم
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // فلترة بحالة العمولة
        if ($request->filled('status_filter')) {
            if ($request->status_filter === 'assigned') {
                $query->whereHas('currentCommissionSetting', function ($q) {
                    $q->whereNotNull('fixed_amount')->where('fixed_amount', '>', 0);
                });
            } elseif ($request->status_filter === 'unassigned') {
                $query->whereDoesntHave('currentCommissionSetting', function ($q) {
                    $q->whereNotNull('fixed_amount')->where('fixed_amount', '>', 0);
                });
            }
        }

        // فلترة بالنشاط
        if ($request->filled('active_filter')) {
            if ($request->active_filter === 'active') {
                $query->where('is_active', true);
            } elseif ($request->active_filter === 'inactive') {
                $query->where('is_active', false);
            }
        }

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

        $doctors = $query->orderBy('id')->paginate(30)->withQueryString();
        $departments = Department::where('is_active', true)
            ->whereHas('doctors', function ($q) {
                $q->where(function ($sub) {
                    $sub->where('type', 'consultant')->orWhereNull('type');
                });
            })
            ->withCount(['doctors as consultant_doctors_count' => function ($q) {
                $q->where(function ($sub) {
                    $sub->where('type', 'consultant')->orWhereNull('type');
                });
            }])
            ->orderBy('name')
            ->get();
        $q = $request->q;

        return view('admin.doctor_commission_settings.index', compact(
            'doctors',
            'departments',
            'totalDoctors',
            'assignedDoctors',
            'unassignedDoctors',
            'activeDoctors',
            'q'
        ));
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
            'consultation_fee' => 'nullable|array',
            'consultation_fee.*' => 'nullable|numeric|min:0',
            'hi_price' => 'nullable|array',
            'hi_price.*' => 'nullable|numeric|min:0',
            'is_hi_active' => 'nullable|array',
            'moi_price' => 'nullable|array',
            'moi_price.*' => 'nullable|numeric|min:0',
            'is_moi_active' => 'nullable|array',
            'save_mode' => 'required|in:row,all,first_n',
            'doctor_row' => 'required_if:save_mode,row|exists:doctors,id',
            'rows_to_save' => 'nullable|integer|min:1',
        ]);

        $doctorIds = $validated['doctor_id'];
        $fixedAmounts = $request->input('fixed_amount', []);
        $consultationFees = $request->input('consultation_fee', []);
        $hiPrices = $request->input('hi_price', []);
        $isHiActives = $request->input('is_hi_active', []);
        $moiPrices = $request->input('moi_price', []);
        $isMoiActives = $request->input('is_moi_active', []);
        $saveMode = $validated['save_mode'];

        $rowsToSave = null;
        if ($saveMode === 'first_n') {
            $rowsToSave = min($validated['rows_to_save'] ?? count($doctorIds), count($doctorIds));
        }

        $processRows = function (int $index) use ($doctorIds, $fixedAmounts, $consultationFees, $hiPrices, $isHiActives, $moiPrices, $isMoiActives) {
            $doctorId = $doctorIds[$index];
            $fixedAmount = $fixedAmounts[$index] ?? null;

            $doctor = Doctor::findOrFail($doctorId);
            $departmentId = $doctor->department_id;
            $isActive = $doctor->is_active;

            // تحديث أسعار وتفعيل كشفيات الضمان والكاش للطبيب
            $doctorUpdateData = [];
            if (array_key_exists($index, $consultationFees) && $consultationFees[$index] !== null && $consultationFees[$index] !== '') {
                $doctorUpdateData['consultation_fee'] = (float) $consultationFees[$index];
            }
            if (array_key_exists($index, $hiPrices)) {
                $doctorUpdateData['hi_price'] = $hiPrices[$index] !== null && $hiPrices[$index] !== '' ? (float) $hiPrices[$index] : null;
            }
            if (array_key_exists($index, $isHiActives) || isset($hiPrices[$index])) {
                $doctorUpdateData['is_hi_active'] = !empty($isHiActives[$index]);
            }
            if (array_key_exists($index, $moiPrices)) {
                $doctorUpdateData['moi_price'] = $moiPrices[$index] !== null && $moiPrices[$index] !== '' ? (float) $moiPrices[$index] : null;
            }
            if (array_key_exists($index, $isMoiActives) || isset($moiPrices[$index])) {
                $doctorUpdateData['is_moi_active'] = !empty($isMoiActives[$index]);
            }

            if (!empty($doctorUpdateData)) {
                $doctor->update($doctorUpdateData);
            }

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

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ إعداد العمولة بنجاح',
            ]);
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
