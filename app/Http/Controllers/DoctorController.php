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
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        return view('doctors.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'department_id' => 'required|exists:departments,id',
            'type' => 'required|in:consultant,anesthesiologist,surgeon,emergency',
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
        
        // إضافة صلاحية الطبيب باستخدام Spatie Permission
        $user->assignRole('doctor');

        // إنشاء سجل الطبيب
        Doctor::create([
            'user_id' => $user->id,
            'department_id' => $request->department_id,
            'type' => $request->type,
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
        $departments = Department::where('is_active', true)->orderBy('name')->get();
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
            'type' => 'required|in:consultant,anesthesiologist,surgeon,emergency',
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
            'department_id', 'type', 'phone', 'specialization', 'qualification', 
            'license_number', 'experience_years', 'consultation_fee',
            'max_patients_per_day', 'bio', 'is_active'
        ]));

        return redirect()->route('doctors.index')
            ->with('success', 'تم تحديث بيانات الطبيب بنجاح');
    }

    public function updateAvailability(Request $request, Doctor $doctor)
    {
        $request->validate([
            'is_available_today' => 'required|boolean',
        ]);

        $doctor->update([
            'is_available_today' => $request->is_available_today,
            'available_date' => today(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->is_available_today ? 'تم تفعيل التوفر اليومي' : 'تم إلغاء التوفر اليومي',
            'is_available_today' => $request->is_available_today,
        ]);
    }

    public function destroy(Doctor $doctor)
    {
        // حذف المستخدم المرتبط (اختياري حسب متطلباتك)
        // $doctor->user->delete();
        
        $doctor->delete();

        return redirect()->route('doctors.index')
            ->with('success', 'تم حذف الطبيب بنجاح');
    }
    
    /**
     * حفظ طبيب مرسل خارجي جديد
     * يستخدم من صفحة إضافة العمليات الجراحية
     */
    public function storeReferringDoctor(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'specialization' => 'required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            // إنشاء بريد إلكتروني فريد من الاسم
            $email = 'external_' . str_replace(' ', '_', strtolower($request->name)) . '@external.local';
            
            // التحقق من وجود طبيب بنفس الاسم
            $existingDoctor = Doctor::whereHas('user', function($query) use ($request) {
                $query->where('name', $request->name);
            })->first();
            
            if ($existingDoctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'يوجد طبيب بهذا الاسم بالفعل في النظام'
                ], 422);
            }

            // إنشاء مستخدم للطبيب الخارجي
            $user = User::create([
                'name' => $request->name,
                'email' => $email,
                'password' => Hash::make(uniqid()), // كلمة مرور عشوائية
                'role' => 'doctor', // دور طبيب عادي (التمييز يتم عبر is_active في جدول doctors)
            ]);
            
            // إضافة صلاحية الطبيب باستخدام Spatie Permission
            $user->assignRole('doctor');

            // البحث عن قسم "أطباء خارجيين" أو إنشاءه إن لم يكن موجوداً
            $externalDept = \App\Models\Department::firstOrCreate(
                ['name' => 'أطباء خارجيين'],
                [
                    'hospital_id' => 1, // افتراضي - يمكن تعديله حسب النظام
                    'type' => 'other',
                    'room_number' => 'N/A',
                    'consultation_fee' => 0,
                    'working_hours_start' => '00:00:00',
                    'working_hours_end' => '00:00:00',
                    'max_patients_per_day' => 0,
                    'is_active' => false // غير نشط لأنه قسم وهمي
                ]
            );

            // إنشاء سجل الطبيب مع علامة "خارجي"
            $doctor = Doctor::create([
                'user_id' => $user->id,
                'department_id' => $externalDept->id, // قسم الأطباء الخارجيين
                'type' => 'consultant', // افتراضي
                'phone' => $request->phone ?? '',
                'specialization' => $request->specialization,
                'qualification' => 'طبيب خارجي - ' . $request->specialization, // جعلها أكثر تفصيلاً
                'license_number' => 'EXT-' . time() . '-' . rand(100, 999), // رقم ترخيص مؤقت فريد
                'experience_years' => 5, // قيمة افتراضية معقولة
                'consultation_fee' => 0, // لا يتقاضى رسوم استشارة
                'max_patients_per_day' => 0, // لا يستقبل مرضى مباشرة
                'bio' => 'طبيب خارجي - ' . ($request->notes ?? 'لا توجد ملاحظات إضافية'),
                'is_active' => true, // نشط ليتمكن من استخدام النظام كطبيب
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الطبيب بنجاح',
                'doctor' => [
                    'id' => $doctor->id,
                    'name' => $user->name,
                    'specialization' => $doctor->specialization,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الطبيب: ' . $e->getMessage()
            ], 500);
        }
    }
}