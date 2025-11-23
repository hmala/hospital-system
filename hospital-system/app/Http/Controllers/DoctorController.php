<?php
// app/Http/Controllers/DoctorController.php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DoctorController extends Controller
{
    public function index()
    {
        $doctors = Doctor::with(['user', 'department'])
            ->withCount(['appointments as today_appointments_count' => function($query) {
                $query->whereDate('appointment_date', today());
            }])
            ->latest()
            ->paginate(10);

        return view('doctors.index', compact('doctors'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        return view('doctors.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'department_id' => 'required|exists:departments,id',
            'specialization' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'license_number' => 'required|string|unique:doctors,license_number',
            'experience_years' => 'required|integer|min:0',
            'consultation_fee' => 'required|numeric|min:0',
        ]);

        // إنشاء مستخدم للطبيب
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('password'), // كلمة مرور افتراضية
            'role' => 'doctor',
            // 'phone' => $request->phone, // إزالة حفظ رقم الهاتف من جدول users
        ]);

        // إنشاء سجل الطبيب
        Doctor::create([
            'user_id' => $user->id,
            'department_id' => $request->department_id,
            'phone' => $request->phone, // حفظ رقم الهاتف في جدول doctors
            'specialization' => $request->specialization,
            'qualification' => $request->qualification,
            'license_number' => $request->license_number,
            'experience_years' => $request->experience_years,
            'consultation_fee' => $request->consultation_fee,
            'max_patients_per_day' => $request->max_patients_per_day ?? 20,
            'bio' => $request->bio,
        ]);

        return redirect()->route('doctors.index')
            ->with('success', 'تم إضافة الطبيب بنجاح');
    }

    public function show(Doctor $doctor)
    {
        $doctor->load(['user', 'department', 'appointments' => function($query) {
            $query->whereDate('appointment_date', '>=', today())
                  ->orderBy('appointment_date');
        }]);

        return view('doctors.show', compact('doctor'));
    }

    public function edit(Doctor $doctor)
    {
        $departments = Department::where('is_active', true)->get();
        $doctor->load('user');
        return view('doctors.edit', compact('doctor', 'departments'));
    }

    public function update(Request $request, Doctor $doctor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $doctor->user_id,
            'phone' => 'required|string|max:15',
            'department_id' => 'required|exists:departments,id',
            'specialization' => 'required|string|max:255',
            'qualification' => 'required|string|max:255',
            'license_number' => 'required|string|unique:doctors,license_number,' . $doctor->id,
            'experience_years' => 'required|integer|min:0',
            'consultation_fee' => 'required|numeric|min:0',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // تحديث بيانات المستخدم
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        // تحديث كلمة المرور إذا تم إدخالها
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $doctor->user->update($userData);

        // تحديث بيانات الطبيب
        $doctor->update($request->only([
            'department_id', 'phone', 'specialization', 'qualification', 
            'license_number', 'experience_years', 'consultation_fee',
            'max_patients_per_day', 'bio', 'is_active'
        ]));

        return redirect()->route('doctors.index')
            ->with('success', 'تم تحديث بيانات الطبيب بنجاح');
    }

    public function destroy(Doctor $doctor)
    {
        // حذف المستخدم المرتبط (اختياري حسب متطلباتك)
        // $doctor->user->delete();
        
        $doctor->delete();

        return redirect()->route('doctors.index')
            ->with('success', 'تم حذف الطبيب بنجاح');
    }
}