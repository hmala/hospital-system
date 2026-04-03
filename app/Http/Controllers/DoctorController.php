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
    public function index(Request $request)
    {
        $query = Doctor::with(['user', 'department'])
            ->withCount(['appointments as today_appointments_count' => function($query) {
                $query->whereDate('appointment_date', today());
            }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhere('specialization', 'like', "%{$search}%");
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('workdays')) {
            $day = $request->workdays;
            $query->whereJsonContains('working_days', $day);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $doctors = $query->latest()->paginate(10)->appends($request->query());

        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('doctors.index', compact('doctors', 'departments'));
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
            'consultation_fee' => 'required|numeric|min:0',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'working_days' => 'required|array|min:1',
            'working_days.*' => 'string',
        ]);

        // إنشاء مستخدم للطبيب
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('password'), // كلمة مرور افتراضية
            'role' => 'doctor',
        ]);
        
        // إضافة صلاحية الطبيب باستخدام Spatie Permission
        $user->assignRole('doctor');

        // إنشاء سجل الطبيب
        Doctor::create([
            'user_id' => $user->id,
            'department_id' => $request->department_id,
            'type' => $request->type,
            'phone' => $request->phone,
            'specialization' => $request->specialization,
            'consultation_fee' => $request->consultation_fee,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'working_days' => $request->working_days,
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
            'consultation_fee' => 'required|numeric|min:0',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'working_days' => 'required|array|min:1',
            'working_days.*' => 'string',
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
        $doctor->update([
            'department_id' => $request->department_id,
            'type' => $request->type,
            'phone' => $request->phone,
            'specialization' => $request->specialization,
            'consultation_fee' => $request->consultation_fee,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'working_days' => $request->working_days,
            'is_active' => $request->has('is_active'),
        ]);

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
            // إنشاء بريد إلكتروني فريد وصالح من الاسم
            $slugName = strtolower(trim($request->name));
            $slugName = preg_replace('/[^a-z0-9]+/i', '_', $slugName); // يلتقط أحرف إنجليزية وأرقام فقط
            $slugName = trim($slugName, '_');
            if (empty($slugName)) {
                $slugName = 'doctor';
            }

            $email = 'external_' . $slugName . '_' . uniqid() . '@external.local';

            // ضمان عدم تكرار البريد
            while (User::where('email', $email)->exists()) {
                $email = 'external_' . $slugName . '_' . uniqid() . '@external.local';
            }

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