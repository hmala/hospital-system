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
    public function index()
    {
        $patients = Patient::with(['user', 'appointments'])
            ->withCount(['appointments as total_appointments'])
            ->latest()
            ->paginate(15);

        return view('patients.index', compact('patients'));
    }

    public function create()
    {
        $countries = \App\Models\Country::all();
        $governorates = \App\Models\Governorate::all();
        $iraq = \App\Models\Country::where('name', 'العراق')->first();
        $iraq_id = $iraq ? $iraq->id : null;
        return view('patients.create', compact('countries', 'governorates', 'iraq_id'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'emergency_contact' => 'required|string',
            'blood_type' => 'nullable|string|max:10',
            'national_id' => 'nullable|string|unique:patients,national_id',
            'mother_name' => 'nullable|string|max:255',
            'country' => 'nullable|exists:countries,id',
            'governorate' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:أعزب,متزوج,مطلق,أرمل',
            'covered_by_insurance' => 'nullable|in:0,1',
            'insurance_booklet_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'الاسم مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'date_of_birth.required' => 'تاريخ الميلاد مطلوب',
            'gender.required' => 'النوع مطلوب',
            'emergency_contact.required' => 'رقم الطوارئ مطلوب',
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
            'national_id' => $request->national_id,
            'first_visit_date' => now(),
            'notes' => $request->notes,
            'mother_name' => $request->mother_name,
            'country_id' => $request->country,
            'governorate' => $request->governorate,
            'district' => $request->district,
            'neighborhood' => $request->neighborhood,
            'marital_status' => $request->marital_status,
            'covered_by_insurance' => $request->covered_by_insurance,
            'insurance_booklet_number' => $request->insurance_booklet_number,
        ]);

        return redirect()->route('patients.index')
            ->with('success', 'تم إضافة المريض بنجاح');
    }

    public function show(Patient $patient)
    {
        $patient->load(['user', 'appointments.doctor.user', 'appointments.department']);
        
        // إحصائيات المريض
        $stats = [
            'total_appointments' => $patient->appointments->count(),
            'completed_appointments' => $patient->appointments->where('status', 'completed')->count(),
            'upcoming_appointments' => $patient->appointments->where('status', 'scheduled')->count(),
        ];

        return view('patients.show', compact('patient', 'stats'));
    }

    public function edit(Patient $patient)
    {
        $patient->load('user');
        $countries = \App\Models\Country::all();
        $governorates = \App\Models\Governorate::all();
        $iraq = \App\Models\Country::where('name', 'العراق')->first();
        $iraq_id = $iraq ? $iraq->id : null;
        return view('patients.edit', compact('patient', 'countries', 'governorates', 'iraq_id'));
    }

    public function update(Request $request, Patient $patient)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $patient->user_id,
            'phone' => 'required|string|max:15',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'emergency_contact' => 'required|string',
            'blood_type' => 'nullable|string|max:10',
            'national_id' => 'nullable|string|unique:patients,national_id,' . $patient->id,
            'mother_name' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:أعزب,متزوج,مطلق,أرمل',
            'covered_by_insurance' => 'nullable|in:0,1',
            'insurance_booklet_number' => 'nullable|string|max:255',
            'country' => 'nullable|exists:countries,id',
            'governorate' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
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
        $patient->user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
        ]);

        // تحديث بيانات المريض
        $patient->update($request->only([
            'emergency_contact', 'blood_type', 'national_id', 'mother_name',
            'marital_status', 'covered_by_insurance', 'insurance_booklet_number',
            'country', 'governorate', 'district', 'neighborhood'
        ]));

        return redirect()->route('patients.show', $patient)
            ->with('success', 'تم تحديث بيانات المريض بنجاح');
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();
        // يمكنك اختيار حذف المستخدم أيضاً أو تركه
        // $patient->user->delete();

        return redirect()->route('patients.index')
            ->with('success', 'تم حذف المريض بنجاح');
    }

    // بحث المرضى
    public function search(Request $request)
    {
        $search = $request->get('search');
        
        $patients = Patient::with('user')
            ->whereHas('user', function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
            })
            ->orWhere('national_id', 'like', "%{$search}%")
            ->paginate(15);

        return view('patients.index', compact('patients'));
    }
}