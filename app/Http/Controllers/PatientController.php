<?php
// app/Http/Controllers/PatientController.php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->can('view patients') && !$user->hasRole(['receptionist', 'doctor', 'inquiry_staff', 'staff', 'nurse'])) {
            abort(403, 'غير مصرح لك بعرض المرضى');
        }

        $search = trim($request->get('search', ''));

        $query = Patient::with('user')
            ->withCount(['appointments as total_appointments']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                })->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        $patients = $query->latest()->paginate(15)->appends($request->query());

        // AJAX → أرجع JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'patients' => $patients->getCollection()->map(fn($p) => [
                    'id'                 => $p->id,
                    'user'               => $p->user ? [
                        'name'   => $p->user->name,
                        'phone'  => $p->user->phone,
                        'email'  => $p->user->email,
                        'gender' => $p->user->gender,
                    ] : null,
                    'age'                => $p->age,
                    'blood_type'         => $p->blood_type,
                    'emergency_contact'  => $p->emergency_contact,
                    'national_id'        => $p->national_id,
                    'total_appointments' => $p->total_appointments,
                    'last_visit_date'    => $p->getLastVisitDate()
                        ? $p->getLastVisitDate()->format('Y-m-d') : null,
                ])->values(),
                'pagination'   => $patients->links('vendor.pagination.bootstrap-5')->toHtml(),
                'current_page' => $patients->currentPage(),
                'last_page'    => $patients->lastPage(),
                'total'        => $patients->total(),
            ]);
        }

        return view('patients.index', compact('patients'));
    }

    public function create()
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->can('create patients') && !$user->hasRole(['receptionist', 'inquiry_staff'])) {
            abort(403, 'غير مصرح لك بإضافة مريض');
        }

        $countries = \App\Models\Country::all();
        $governorates = \App\Models\Governorate::all();
        $iraq = \App\Models\Country::where('name', 'العراق')->first();
        $iraq_id = $iraq ? $iraq->id : null;
        $healthInsuranceCategories = \App\Models\HealthInsuranceCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('patients.create', compact('countries', 'governorates', 'iraq_id', 'healthInsuranceCategories'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->can('create patients') && !$user->hasRole(['receptionist', 'inquiry_staff'])) {
            abort(403, 'غير مصرح لك بإضافة مريض');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'emergency_contact' => 'nullable|string',
            'blood_type' => 'nullable|string|max:10',
            'national_id' => 'required|string|unique:patients,national_id',
            'mother_name' => 'required|string|max:255',
            'country' => 'nullable|exists:countries,id',
            'governorate' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'marital_status' => 'required|in:أعزب,متزوج,مطلق,أرمل',
            'covered_by_insurance' => 'nullable|in:0,1',
            'insurance_type' => 'nullable|in:none,moi,hi',
            'health_insurance_category_id' => 'nullable|exists:health_insurance_categories,id',
            'insurance_card_no' => 'nullable|string|max:255',
            'copay_percentage' => 'nullable|numeric|min:0|max:100',
            'insurance_booklet_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'الاسم مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'date_of_birth.required' => 'تاريخ الميلاد مطلوب',
            'gender.required' => 'النوع مطلوب',
            'mother_name.required' => 'اسم الأم مطلوب',
            'marital_status.required' => 'الحالة الاجتماعية مطلوبة',
            'national_id.required' => 'الرقم الوطني مطلوب',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'national_id.unique' => 'الرقم الوطني مستخدم من قبل',
        ]);

        // التحقق من عدم تكرار الاسم واسم الأم
        $existingPatient = Patient::whereHas('user', function($query) use ($request) {
            $query->where('name', $request->name);
        })->where('mother_name', $request->mother_name)->first();

        if ($existingPatient) {
            return back()->withErrors([
                'duplicate_patient' => 'يوجد مريض آخر بنفس الاسم واسم الأم. يرجى التأكد من صحة البيانات.'
            ])->withInput();
        }
        $email = $request->email;
        if (!$email) {
            $email = 'patient.' . $request->phone . '.' . time() . '@hospital.local';
        }
        // إنشاء مستخدم للمريض
        $user = User::create([
            'name' => $request->name,
            'email' => $email,
            'password' => Hash::make('password'), // كلمة مرور افتراضية
            'role' => 'patient',
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
        ]);

        $insuranceType = $request->input('insurance_type', 'none');
        $isCovered = ($insuranceType !== 'none') ? 1 : ($request->input('covered_by_insurance', 0) ? 1 : 0);
        $hiCategoryId = ($insuranceType === 'hi') ? $request->input('health_insurance_category_id') : null;

        // إنشاء سجل المريض
        Patient::create([
            'user_id' => $user->id,
            'emergency_contact' => $request->emergency_contact,
            'blood_type' => $request->blood_type,
            'medical_history' => $request->medical_history,
            'allergies' => $request->allergies,
            'current_medications' => $request->current_medications,
            'insurance_company' => $request->insurance_company,
            'insurance_number' => $request->insurance_number,
            'insurance_type' => $insuranceType,
            'health_insurance_category_id' => $hiCategoryId,
            'insurance_card_no' => $request->insurance_card_no ?: $request->insurance_booklet_number,
            'copay_percentage' => $request->filled('copay_percentage') ? (float)$request->copay_percentage : ($insuranceType !== 'none' ? 15.00 : 0.00),
            'national_id' => $request->national_id,
            'first_visit_date' => now(),
            'notes' => $request->notes,
            'mother_name' => $request->mother_name,
            'country_id' => $request->country,
            'governorate' => $request->governorate,
            'district' => $request->district,
            'neighborhood' => $request->neighborhood,
            'marital_status' => $request->marital_status,
            'covered_by_insurance' => $isCovered,
            'insurance_booklet_number' => $request->insurance_booklet_number ?: $request->insurance_card_no,
        ]);

        return redirect()->route('patients.index')
            ->with('success', 'تم إضافة المريض بنجاح');
    }

    public function show(Patient $patient)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->can('view patients') && !$user->hasRole(['receptionist', 'doctor', 'inquiry_staff', 'staff', 'nurse'])) {
            abort(403, 'غير مصرح لك بعرض بيانات المريض');
        }

        $patient->load(['user', 'appointments.doctor.user', 'appointments.department']);
        
        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->can('edit patients') && !$user->hasRole(['receptionist', 'inquiry_staff'])) {
            abort(403, 'غير مصرح لك بتعديل بيانات المريض');
        }

        $countries = \App\Models\Country::all();
        $governorates = \App\Models\Governorate::all();
        $iraq = \App\Models\Country::where('name', 'العراق')->first();
        $iraq_id = $iraq ? $iraq->id : null;
        $healthInsuranceCategories = \App\Models\HealthInsuranceCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('patients.edit', compact('patient', 'countries', 'governorates', 'iraq_id', 'healthInsuranceCategories'));
    }

    public function update(Request $request, Patient $patient)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->can('edit patients') && !$user->hasRole(['receptionist', 'inquiry_staff'])) {
            abort(403, 'غير مصرح لك بتعديل بيانات المريض');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $patient->user_id,
            'phone' => 'required|string|max:15',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'emergency_contact' => 'nullable|string',
            'blood_type' => 'nullable|string|max:10',
            'national_id' => 'required|string|unique:patients,national_id,' . $patient->id,
            'mother_name' => 'required|string|max:255',
            'marital_status' => 'required|in:أعزب,متزوج,مطلق,أرمل',
            'covered_by_insurance' => 'nullable|in:0,1',
            'insurance_type' => 'nullable|in:none,moi,hi',
            'insurance_card_no' => 'nullable|string|max:255',
            'copay_percentage' => 'nullable|numeric|min:0|max:100',
            'insurance_booklet_number' => 'nullable|string|max:255',
            'country' => 'nullable|exists:countries,id',
            'governorate' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
        ], [
            'name.required' => 'الاسم مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'date_of_birth.required' => 'تاريخ الميلاد مطلوب',
            'gender.required' => 'النوع مطلوب',
            'mother_name.required' => 'اسم الأم مطلوب',
            'marital_status.required' => 'الحالة الاجتماعية مطلوبة',
            'national_id.required' => 'الرقم الوطني مطلوب',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'national_id.unique' => 'الرقم الوطني مستخدم من قبل',
        ]);

        // التحقق من عدم تكرار الاسم واسم الأم (استثناء المريض الحالي)
        $existingPatient = Patient::whereHas('user', function($query) use ($request) {
            $query->where('name', $request->name);
        })->where('mother_name', $request->mother_name)
          ->where('id', '!=', $patient->id)
          ->first();

        if ($existingPatient) {
            return back()->withErrors([
                'duplicate_patient' => 'يوجد مريض آخر بنفس الاسم واسم الأم. يرجى التأكد من صحة البيانات.'
            ])->withInput();
        }

        // تحديث بيانات المستخدم
        $email = $request->email;
        if (!$email) {
            $email = 'patient.' . $request->phone . '.' . time() . '@hospital.local';
        }
        $patient->user->update([
            'name' => $request->name,
            'email' => $email,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
        ]);

        $insuranceType = $request->input('insurance_type', 'none');
        $isCovered = ($insuranceType !== 'none') ? 1 : ($request->input('covered_by_insurance', 0) ? 1 : 0);
        $hiCategoryId = ($insuranceType === 'hi') ? $request->input('health_insurance_category_id') : null;

        // تحديث بيانات المريض
        $patient->update([
            'emergency_contact' => $request->emergency_contact,
            'blood_type' => $request->blood_type,
            'national_id' => $request->national_id,
            'mother_name' => $request->mother_name,
            'marital_status' => $request->marital_status,
            'covered_by_insurance' => $isCovered,
            'insurance_type' => $insuranceType,
            'health_insurance_category_id' => $hiCategoryId,
            'insurance_card_no' => $request->insurance_card_no ?: $request->insurance_booklet_number,
            'copay_percentage' => $request->filled('copay_percentage') ? (float)$request->copay_percentage : ($patient->copay_percentage ?? ($insuranceType !== 'none' ? 15.00 : 0.00)),
            'insurance_booklet_number' => $request->insurance_booklet_number ?: $request->insurance_card_no,
            'country_id' => $request->country,
            'governorate' => $request->governorate,
            'district' => $request->district,
            'neighborhood' => $request->neighborhood
        ]);

        return redirect()->route('patients.show', $patient)
            ->with('success', 'تم تحديث بيانات المريض بنجاح');
    }

    public function destroy(Patient $patient)
    {
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->can('delete patients')) {
            abort(403, 'غير مصرح لك بحذف المريض');
        }

        $patient->delete();
        // يمكنك اختيار حذف المستخدم أيضاً أو تركه
        // $patient->user->delete();

        return redirect()->route('patients.index')
            ->with('success', 'تم حذف المريض بنجاح');
    }

    // بحث المرضى (يُرجع JSON دائماً - مخصص لطلبات AJAX)
    public function search(Request $request)
    {
        $search = trim($request->get('search', ''));

        $query = Patient::with('user')->withCount(['appointments as total_appointments']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        $patients = $query->latest()->paginate(15)->appends($request->query());

        $patientsData = $patients->getCollection()->map(function ($patient) {
            return [
                'id'                  => $patient->id,
                'user'                => $patient->user ? [
                    'name'   => $patient->user->name,
                    'phone'  => $patient->user->phone,
                    'email'  => $patient->user->email,
                    'gender' => $patient->user->gender,
                ] : null,
                'age'                 => $patient->age,
                'blood_type'          => $patient->blood_type,
                'emergency_contact'   => $patient->emergency_contact,
                'national_id'         => $patient->national_id,
                'total_appointments'  => $patient->total_appointments,
                'last_visit_date'     => $patient->getLastVisitDate()
                    ? $patient->getLastVisitDate()->format('Y-m-d')
                    : null,
            ];
        });

        return response()->json([
            'patients'     => $patientsData->values(),
            'pagination'   => $patients->links()->toHtml(),
            'current_page' => $patients->currentPage(),
            'last_page'    => $patients->lastPage(),
            'total'        => $patients->total(),
        ]);
    }
}