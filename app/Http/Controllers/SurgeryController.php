<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\SurgeryTreatment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\LabTest;
use App\Models\RadiologyType;
use App\Models\SurgicalOperation;
use App\Models\Room;
use App\Events\SurgeryUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SurgeryController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'receptionist', 'doctor', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // العمليات النشطة (المجدولة والمنتظرة والجارية)
        $activeSurgeriesQuery = Surgery::with(['patient.user', 'doctor.user', 'department', 'room', 'surgeryTreatments', 'radiologyTests', 'labTests'])
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress']);

        // فلترة حسب الطبيب إذا كان المستخدم طبيباً جراحاً أو تخدير (وليس استشاري)
        if ($user->hasRole('doctor') && $user->doctor) {
            // إخفاء العمليات عن الأطباء الاستشاريين
            if ($user->doctor->type === 'consultant') {
                // لا نعرض أي عمليات للطبيب الاستشاري
                $activeSurgeriesQuery->whereRaw('1 = 0');
            } else {
                // عرض العمليات الخاصة بالطبيب الجراح أو المخدر
                $activeSurgeriesQuery->where('doctor_id', $user->doctor->id);
            }
        }

        // تطبيق البحث والفلترة
        if (request('search')) {
            $search = request('search');
            $activeSurgeriesQuery->where(function($query) use ($search) {
                $query->whereHas('patient.user', function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('doctor.user', function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhere('surgery_type', 'like', '%' . $search . '%');
            });
        }

        if (request('status') && request('status') !== '') {
            $activeSurgeriesQuery->where('status', request('status'));
        }

        if (request('date')) {
            $activeSurgeriesQuery->whereDate('scheduled_date', request('date'));
        }

        $activeSurgeries = $activeSurgeriesQuery->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->paginate(20)
            ->appends(request()->query());

        // العمليات المكتملة والملغاة
        $completedSurgeriesQuery = Surgery::with(['patient.user', 'doctor.user', 'department', 'room', 'surgeryTreatments', 'radiologyTests', 'labTests'])
            ->whereIn('status', ['completed', 'cancelled']);

        // فلترة حسب الطبيب إذا كان المستخدم طبيباً جراحاً أو تخدير (وليس استشاري)
        if ($user->hasRole('doctor') && $user->doctor) {
            // إخفاء العمليات عن الأطباء الاستشاريين
            if ($user->doctor->type === 'consultant') {
                // لا نعرض أي عمليات للطبيب الاستشاري
                $completedSurgeriesQuery->whereRaw('1 = 0');
            } else {
                // عرض العمليات الخاصة بالطبيب الجراح أو المخدر
                $completedSurgeriesQuery->where('doctor_id', $user->doctor->id);
            }
        }

        if (request('search')) {
            $search = request('search');
            $completedSurgeriesQuery->where(function($query) use ($search) {
                $query->whereHas('patient.user', function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('doctor.user', function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhere('surgery_type', 'like', '%' . $search . '%');
            });
        }

        if (request('status') && in_array(request('status'), ['completed', 'cancelled'])) {
            $completedSurgeriesQuery->where('status', request('status'));
        }

        if (request('date')) {
            $completedSurgeriesQuery->whereDate('scheduled_date', request('date'));
        }

        $completedSurgeries = $completedSurgeriesQuery->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->paginate(20)
            ->appends(request()->query());

        return view('surgeries.index', compact('activeSurgeries', 'completedSurgeries'));
    }

    public function create()
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بإنشاء عمليات جراحية');
        }

        // احضر البيانات مع الترتيب الأبجدي للأسماء
        $patients = Patient::with('user')->get()->sortBy(function($p) {
            return optional($p->user)->name ?? '';
        });
        $doctors = Doctor::with('user')
                        ->where('is_active', true)
                        ->where(function($query) {
                            $query->where('type', 'surgeon')
                                  ->orWhere('specialization', 'جراحة');
                        })
                        ->get()
                        ->sortBy(function($d) {
                            return optional($d->user)->name ?? '';
                        });
        $labTests = LabTest::active()->orderBy('name')->get();
        $radiologyTypes = RadiologyType::active()->orderBy('name')->get();
        $surgicalOperations = \App\Models\SurgicalOperation::where('is_active', true)->orderBy('category')->orderBy('name')->get();
        $rooms = Room::where('is_active', true)->where('room_purpose', 'beds')->orderBy('room_type')->orderBy('room_number')->get();
        return view('surgeries.create', compact('patients', 'doctors', 'labTests', 'radiologyTypes', 'surgicalOperations', 'rooms'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بإنشاء عمليات جراحية');
        }

        // تحويل أي نص يحتوي على فواصل أو مسافات إلى عدد صحيح/عشري صالح
        if ($request->filled('custom_surgery_fee')) {
            $normalizedFee = str_replace([',', ' '], ['', ''], $request->custom_surgery_fee);
            $request->merge(['custom_surgery_fee' => $normalizedFee]);
        }

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'nullable|exists:departments,id',
            'room_id' => 'nullable|exists:rooms,id',
            'expected_stay_days' => 'nullable|integer|min:1|max:365',
            'surgery_category' => 'required|string|max:255',
            'surgical_operation_id' => 'required|exists:surgical_operations,id',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'referring_doctor_name' => 'required|string|max:255',
            'referral_letter' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'custom_surgery_fee' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'anesthesiologist_2_id' => 'nullable|exists:doctors,id',
            'surgical_assistant_name' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'referring_physician' => 'nullable|string|max:255',
            'anesthesia_type' => 'nullable|string|max:255',
            'surgery_classification' => 'nullable|string|max:255',
            'supplies' => 'nullable|string',
            'lab_tests' => 'nullable|array',
            'lab_tests.*' => 'exists:lab_tests,id',
            'radiology_tests' => 'nullable|array',
            'radiology_tests.*' => 'exists:radiology_types,id',
        ]);

        $doctor = Doctor::find($request->doctor_id);
        if (!$doctor || !$doctor->department_id) {
            return back()->withInput()->withErrors(['doctor_id' => 'الطبيب المختار يجب أن يكون مرتبطاً بقسم.']);
        }
        $request->merge(['department_id' => $doctor->department_id]);

        $surgeryData = $request->except([]);
        
        // استخراج اسم العملية من الجدول
        $operation = SurgicalOperation::find($request->surgical_operation_id);
        if ($operation) {
            $surgeryData['surgery_type'] = $operation->name;
            
            // استخدام السعر المخصص دائماً (يدوياً في كلتا الحالتين)
            if ($request->filled('custom_surgery_fee')) {
                $surgeryData['surgery_fee'] = $request->custom_surgery_fee;
            } else {
                // احتياطي: نسخ السعر من الجدول في حالة عدم وجود سعر مخصص
                $surgeryData['surgery_fee'] = $operation->fee;
            }
        }
        
        $surgeryData['scheduled_time'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $request->scheduled_date . ' ' . $request->scheduled_time);

        // حساب أجرة الغرفة
        if ($request->room_id && $request->expected_stay_days) {
            $room = Room::find($request->room_id);
            if ($room) {
                $surgeryData['room_fee'] = $room->daily_fee * $request->expected_stay_days;
                // تحديث حالة الغرفة لمحجوزة
                $room->update(['status' => 'occupied']);
            }
        }

        // handle referral letter upload if present
        if ($request->hasFile('referral_letter')) {
            $file = $request->file('referral_letter');
            $path = $file->store('referrals', 'public');
            $surgeryData['referral_letter_path'] = $path;
        }

        $surgery = Surgery::create($surgeryData);

        // إنشاء زيارة للعملية إذا لم تكن موجودة
        if (!$surgery->visit_id) {
            $visit = \App\Models\Visit::create([
                'patient_id' => $surgery->patient_id,
                'department_id' => $surgery->department_id,
                'doctor_id' => $surgery->doctor_id,
                'visit_date' => $surgery->scheduled_date,
                'visit_time' => $surgery->scheduled_time,
                'visit_type' => 'surgery',
                'chief_complaint' => 'عملية جراحية: ' . $surgery->surgery_type,
                'status' => 'pending_payment',
                'notes' => 'زيارة خاصة بالعملية الجراحية #' . $surgery->id
            ]);
            $surgery->update(['visit_id' => $visit->id]);
        } else {
            $visit = $surgery->visit;
        }

        // إنشاء طلبات المختبر - فقط إذا تم تحديد تحاليل معينة
        $labTestIds = $request->input('lab_tests', []);
        if (is_array($labTestIds) && count($labTestIds)) {
            foreach ($labTestIds as $labTestId) {
                if ($labTestId && !$surgery->labTests()->where('lab_test_id', $labTestId)->exists()) {
                    $surgery->labTests()->create([
                        'lab_test_id' => $labTestId,
                        'status' => 'pending',
                        'payment_status' => 'pending'
                    ]);
                }
            }
        }
        // ملاحظة: لا نقوم بإنشاء سجلات عامة (بدون lab_test_id)
        // سيقوم موظف المختبر بإضافة التحاليل المطلوبة لاحقاً

        // إنشاء طلبات الأشعة - فقط إذا تم تحديد أشعة معينة
        $radiologyTypeIds = $request->input('radiology_tests', []);
        if (is_array($radiologyTypeIds) && count($radiologyTypeIds)) {
            foreach ($radiologyTypeIds as $radiologyTypeId) {
                if ($radiologyTypeId && !$surgery->radiologyTests()->where('radiology_type_id', $radiologyTypeId)->exists()) {
                    $surgery->radiologyTests()->create([
                        'radiology_type_id' => $radiologyTypeId,
                        'status' => 'pending',
                        'payment_status' => 'pending'
                    ]);
                }
            }
        }
        // ملاحظة: لا نقوم بإنشاء سجلات عامة (بدون radiology_type_id)
        // سيقوم موظف الأشعة بإضافة الأشعة المطلوبة لاحقاً

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->route('surgeries.index')->with('success', 'تم حجز العملية بنجاح');
    }

    public function show(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist', 'doctor', 'الجراح', 'التخدير']) && 
            !$user->hasAnyPermission(['view surgeries', 'view resident station', 'view surgeon station', 'view anesthesia station', 'view nursing station', 'view operation theater station'])) {
            abort(403, 'غير مصرح لك باستعراض تفاصيل العمليات الجراحية');
        }

        // منع الأطباء الاستشاريين من استعراض وتعديل العمليات
        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم باستعراض العمليات الجراحية');
        }

        $surgery->load(['patient.user', 'doctor.user', 'department', 'visit', 'labTests.labTest', 'radiologyTests.radiologyType', 'anesthesiologist.user', 'anesthesiologist2.user', 'surgeryTreatments', 'anesthesiaStation', 'residentStationFollowUps']);
        $patients = Patient::with('user')->get()->sortBy(function($p) {
            return optional($p->user)->name ?? '';
        });
        $anesthesiaDoctors = Doctor::with('user')
            ->anesthesia()
            ->get()
            ->sortBy(function($d) {
                return optional($d->user)->name ?? '';
            });
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $labTests = LabTest::active()->orderBy('name')->get();
        $radiologyTypes = RadiologyType::active()->orderBy('name')->get();
        $surgeryTypes = \App\Models\SurgeryType::active()->orderBy('name')->get();
        return view('surgeries.show', compact('surgery', 'patients', 'anesthesiaDoctors', 'departments', 'labTests', 'radiologyTypes', 'surgeryTypes'));
    }

    public function edit(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist', 'doctor', 'الجراح', 'التخدير']) && 
            !$user->hasAnyPermission(['edit surgeries', 'view surgeon station'])) {
            abort(403, 'غير مصرح لك بتعديل العمليات الجراحية');
        }

        // منع الأطباء الاستشاريين من تعديل العمليات
        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بتعديل العمليات الجراحية');
        }

        $surgery->load(['patient.user', 'doctor.user', 'department', 'visit', 'labTests.labTest', 'radiologyTests.radiologyType', 'anesthesiologist.user', 'anesthesiologist2.user', 'anesthesiaStation']);
        $patients = Patient::with('user')->get()->sortBy(function($p) {
            return optional($p->user)->name ?? '';
        });
        $doctors = Doctor::with('user')->where('is_active', true)->get()->sortBy(function($d) {
            return optional($d->user)->name ?? '';
        });
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $labTests = LabTest::active()->orderBy('name')->get();
        $radiologyTypes = RadiologyType::active()->orderBy('name')->get();
        return view('surgeries.edit', compact('surgery', 'patients', 'doctors', 'departments', 'labTests', 'radiologyTypes'));
    }

    public function print(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist', 'doctor', 'الجراح']) && 
            !$user->hasAnyPermission(['view surgeries', 'view surgeon station'])) {
            abort(403, 'غير مصرح لك بطباعة تفاصيل العملية');
        }

        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بطباعة تفاصيل العملية');
        }

        $surgery->load(['patient.user', 'doctor.user', 'department', 'visit', 'labTests.labTest', 'radiologyTests.radiologyType', 'anesthesiologist.user', 'anesthesiologist2.user', 'anesthesiaStation']);
        return view('surgeries.print', compact('surgery'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist', 'doctor', 'الجراح']) && 
            !$user->hasAnyPermission(['edit surgeries', 'view surgeon station'])) {
            abort(403, 'غير مصرح لك بتعديل العمليات الجراحية');
        }

        // منع الأطباء الاستشاريين من تعديل العمليات
        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بتعديل العمليات الجراحية');
        }

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'surgery_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'status' => 'required|in:scheduled,waiting,in_progress,completed,cancelled',
            'referral_source' => 'required|in:internal,external',
            'external_doctor_name' => 'nullable|string|max:255',
            'external_hospital_name' => 'nullable|string|max:255',
            'referral_notes' => 'nullable|string',
            'referral_letter' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'custom_surgery_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'post_op_notes' => 'nullable|string',
            'diagnosis' => 'nullable|string|max:1000',
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'anesthesiologist_2_id' => 'nullable|exists:doctors,id',
            'surgical_assistant_name' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'referring_physician' => 'nullable|string|max:255',
            'anesthesia_type' => 'nullable|string|max:255',
            'surgery_classification' => 'nullable|string|max:255',
            'supplies' => 'nullable|string',
            'surgery_category' => 'nullable|string|in:elective,emergency,urgent,semi_urgent',
            'surgery_type_detail' => 'nullable|string|in:diagnostic,therapeutic,preventive,cosmetic,reconstructive,palliative',
            'anesthesia_position' => 'nullable|string|in:supine,prone,lateral,lithotomy,fowler,trendelenburg,sitting,other',
            'asa_classification' => 'nullable|string|in:asa1,asa2,asa3,asa4,asa5,asa6',
            'surgical_complexity' => 'nullable|string|in:minor,intermediate,major,complex',
            'surgical_notes' => 'nullable|string|max:1000',
            'treatment_plan' => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date|after:today',
            'lab_tests' => 'nullable|array',
            'lab_tests.*' => 'exists:lab_tests,id',
            'radiology_tests' => 'nullable|array',
            'radiology_tests.*' => 'exists:radiology_types,id',
        ]);

        $surgeryData = $request->except(['referral_letter']);
        $surgeryData['scheduled_time'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $request->scheduled_date . ' ' . $request->scheduled_time);

        // if operation changed, refresh fee and type
        if ($request->filled('surgical_operation_id')) {
            $operation = SurgicalOperation::find($request->surgical_operation_id);
            if ($operation) {
                $surgeryData['surgery_type'] = $operation->name;
                
                // استخدام السعر المخصص دائماً (يدوياً في كلتا الحالتين)
                if ($request->filled('custom_surgery_fee')) {
                    $surgeryData['surgery_fee'] = $request->custom_surgery_fee;
                } else {
                    // احتياطي: استخدام السعر من الجدول في حالة عدم وجود سعر مخصص
                    $surgeryData['surgery_fee'] = $operation->fee;
                }
            }
        }

        // process referral letter if new file uploaded
        if ($request->hasFile('referral_letter')) {
            $file = $request->file('referral_letter');
            $path = $file->store('referrals', 'public');
            $surgeryData['referral_letter_path'] = $path;
        }
        $surgery->update($surgeryData);

        // التأكد من وجود زيارة
        if (!$surgery->visit_id) {
            $visit = \App\Models\Visit::create([
                'patient_id' => $surgery->patient_id,
                'department_id' => $surgery->department_id,
                'doctor_id' => $surgery->doctor_id,
                'visit_date' => $surgery->scheduled_date,
                'visit_time' => $surgery->scheduled_time,
                'visit_type' => 'surgery',
                'chief_complaint' => 'عملية جراحية: ' . $surgery->surgery_type,
                'status' => 'pending_payment',
                'notes' => 'زيارة خاصة بالعملية الجراحية #' . $surgery->id
            ]);
            $surgery->update(['visit_id' => $visit->id]);
        } else {
            $visit = $surgery->visit;
        }

        // إنشاء طلبات المختبر - فقط إذا تم تحديد تحاليل معينة
        $labTestIds = $request->input('lab_tests', []);
        if (is_array($labTestIds) && count($labTestIds)) {
            foreach ($labTestIds as $labTestId) {
                if ($labTestId && !$surgery->labTests()->where('lab_test_id', $labTestId)->exists()) {
                    $surgery->labTests()->create([
                        'lab_test_id' => $labTestId,
                        'status' => 'pending',
                        'payment_status' => 'pending'
                    ]);
                }
            }
        }
        // ملاحظة: لا نقوم بإنشاء سجلات عامة (بدون lab_test_id)
        // سيقوم موظف المختبر بإضافة التحاليل المطلوبة لاحقاً

        // إنشاء طلبات الأشعة - فقط إذا تم تحديد أشعة معينة
        $radiologyTypeIds = $request->input('radiology_tests', []);
        if (is_array($radiologyTypeIds) && count($radiologyTypeIds)) {
            foreach ($radiologyTypeIds as $radiologyTypeId) {
                if ($radiologyTypeId && !$surgery->radiologyTests()->where('radiology_type_id', $radiologyTypeId)->exists()) {
                    $surgery->radiologyTests()->create([
                        'radiology_type_id' => $radiologyTypeId,
                        'status' => 'pending',
                        'payment_status' => 'pending'
                    ]);
                }
            }
        }
        // ملاحظة: لا نقوم بإنشاء سجلات عامة (بدون radiology_type_id)
        // سيقوم موظف الأشعة بإضافة الأشعة المطلوبة لاحقاً

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->route('surgeries.show', $surgery)
            ->with('success', 'تم تحديث العملية الجراحية بنجاح');
    }

    public function waiting()
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى قائمة الانتظار');
        }

        // العمليات المجدولة
        $scheduledSurgeries = Surgery::with(['patient.user', 'doctor.user', 'department', 'room'])
            ->where('status', 'scheduled')
            ->orderBy('scheduled_date', 'asc')
            ->orderBy('scheduled_time', 'asc')
            ->get();

        // العمليات في الانتظار والجارية (التي لم تنتهِ بعد من صالة العمليات)
        $activeSurgeries = Surgery::with(['patient.user', 'doctor.user', 'department', 'room', 'operationTheaterStation'])
            ->whereIn('status', ['waiting', 'checked_in', 'in_progress'])
            ->where(function($query) {
                $query->whereDoesntHave('operationTheaterStation')
                    ->orWhereHas('operationTheaterStation', function($q) {
                        $q->where('status', '!=', 'completed');
                    });
            })
            ->orderBy('scheduled_date', 'asc')
            ->orderBy('scheduled_time', 'asc')
            ->get();
            
        return view('surgeries.waiting', compact('scheduledSurgeries', 'activeSurgeries'));
    }

    public function waitingList()
    {
        return $this->waiting();
    }

    public function checkIn(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بتسجيل دخول المريض');
        }

        $surgery->status = 'waiting';
        $surgery->save();

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم تسجيل دخول المريض لقائمة الانتظار');
    }

    public function start(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك ببدء العملية');
        }

        $surgery->status = 'in_progress';
        $surgery->started_at = now();
        $surgery->save();

        // بدء محطة صالة العمليات
        $otStation = $surgery->operationTheaterStation;
        if (!$otStation) {
            $otStation = $surgery->operationTheaterStation()->create([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        } else {
            $otStation->markAsStarted();
        }

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم بدء العملية وبدء محطة صالة العمليات في ' . now()->format('H:i'));
    }

    public function complete(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بإكمال العملية');
        }

        // إتمام محطة صالة العمليات
        $otStation = $surgery->operationTheaterStation;
        if (!$otStation) {
            $otStation = $surgery->operationTheaterStation()->create([
                'status' => 'pending',
            ]);
        }
        $otStation->markAsCompleted();

        // إنشاء وتوجيه العملية لمحطة الجراح التالية
        if (!$surgery->surgeonStation) {
            $surgery->surgeonStation()->create([
                'surgeon_id' => $surgery->doctor_id,
                'status' => 'pending',
            ]);
        }

        // لا نجعل حالة العملية "مكتملة" بالكامل هنا لأنها يجب أن تمر بالجراح والتخدير والتمريض
        // نجعلها "in_progress" لتستمر في دورة المحطات
        $surgery->status = 'in_progress';
        $surgery->save();

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم إنهاء مرحلة صالة العمليات ونقل المريض لمحطة الجراح');
    }

    public function discharge(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بإخراج المريض');
        }

        if ($surgery->status !== 'completed') {
            return redirect()->back()->with('error', 'لا يمكن إخراج المريض إلا بعد إكمال العملية');
        }

        if ($surgery->discharged_at) {
            return redirect()->back()->with('warning', 'المريض خارج بالفعل');
        }

        $surgery->discharged_at = now();
        $surgery->discharge_notes = request('discharge_notes');
        $surgery->save();

        // تحرير الغرفة إذا كانت محجوزة
        if ($surgery->room_id) {
            $room = $surgery->room;
            if ($room && $room->status === 'occupied') {
                $room->status = 'available';
                $room->save();
            }
        }

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم إخراج المريض بنجاح');
    }

    public function cancel(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بإلغاء العملية');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($surgery, $validated) {
            $surgery->status = 'cancelled';
            $surgery->cancellation_reason = $validated['cancellation_reason'] ?? null;
            $surgery->payment_status = 'cancelled';
            $surgery->surgery_fee_paid = 'cancelled';
            $surgery->save();

            $surgery->labTests()->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);

            $surgery->radiologyTests()->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);
        });

        $surgery->refresh();
        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم إلغاء العملية وإيقاف التحاليل والأشعة المرتبطة بها');
    }

    public function returnToWaiting(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بإعادة العملية إلى الانتظار');
        }

        $surgery->status = 'waiting';
        $surgery->save();

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم إعادة العملية إلى قائمة الانتظار');
    }

    public function updateDetails(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'doctor', 'surgery_staff', 'الجراح']) && 
            !$user->hasAnyPermission(['edit surgeries', 'view surgeon station']) && 
            !($user->doctor && $user->doctor->id == $surgery->doctor_id)) {
            abort(403, 'غير مصرح لك بتحديث تفاصيل العملية');
        }

        // منع الأطباء الاستشاريين من تحديث تفاصيل العمليات
        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بتحديث تفاصيل العمليات الجراحية');
        }

        \Log::info('=== Update Surgery Details Started ===');
        \Log::info('Surgery ID: ' . $surgery->id);
        \Log::info('Request data:', $request->all());
        \Log::info('Request method: ' . $request->method());
        \Log::info('User: ' . $user->name . ' (' . $user->id . ')');

        // Log specific fields
        \Log::info('Diagnosis from request: ' . $request->input('diagnosis'));
        \Log::info('Anesthesia type from request: ' . $request->input('anesthesia_type'));
        \Log::info('Start time from request: ' . $request->input('start_time'));
        \Log::info('End time from request: ' . $request->input('end_time'));

        $validated = $request->validate([
            'diagnosis' => 'nullable|string|max:1000',
            'anesthesia_type' => 'nullable|string|max:255',
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'anesthesiologist_2_id' => 'nullable|exists:doctors,id',
            'surgical_assistant_name' => 'nullable|string|max:255',
            'supplies' => 'nullable|string|max:1000',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'estimated_duration' => 'nullable',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'post_op_notes' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
            'treatment_plan' => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date',
            'prescribed_medications' => 'nullable|array',
            'prescribed_medications.surgery_treatments' => 'nullable|array',
            'prescribed_medications.surgery_treatments.*' => 'nullable|array',
            'prescribed_medications.surgery_treatments.*.*' => 'nullable|array',
            'prescribed_medications.surgery_treatments.*.*.description' => 'nullable|string',
            'prescribed_medications.surgery_treatments.*.*.dosage' => 'nullable|string',
            'prescribed_medications.surgery_treatments.*.*.timing' => 'nullable|string',
            'prescribed_medications.surgery_treatments.*.*.duration_value' => 'nullable|integer|min:1',
            'prescribed_medications.surgery_treatments.*.*.duration_unit' => 'nullable|string|in:days,weeks,months,hours,doses',
            'surgery_category' => 'nullable|string|in:elective,emergency,urgent,semi_urgent',
            'surgery_type_detail' => 'nullable|string|in:diagnostic,therapeutic,preventive,cosmetic,reconstructive,palliative',
            'anesthesia_position' => 'nullable|string|in:supine,prone,lateral,lithotomy,fowler,trendelenburg,sitting,other',
            'asa_classification' => 'nullable|string|in:asa1,asa2,asa3,asa4,asa5,asa6',
            'surgical_complexity' => 'nullable|string|in:minor,intermediate,major,complex',
            'surgical_notes' => 'nullable|string|max:1000',
            'required_fluids' => 'nullable|array',
            'required_fluids.*' => 'string|in:intake_iv_fluids,intake_oral,intake_blood,output_urine,output_drain,output_gtube_ng,output_vomiting,output_stool',
        ]);

        \Log::info('Validation passed, validated data: ', $validated);

        // Validate time formats manually
        if (!empty($validated['start_time']) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $validated['start_time'])) {
            return redirect()->back()->withErrors(['start_time' => 'وقت البدء غير صحيح']);
        }
        if (!empty($validated['end_time']) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $validated['end_time'])) {
            return redirect()->back()->withErrors(['end_time' => 'وقت الانتهاء غير صحيح']);
        }

        // Convert time fields to full datetime using surgery's scheduled date
        if (isset($validated['start_time']) && !empty($validated['start_time'])) {
            $validated['start_time'] = $surgery->scheduled_date->format('Y-m-d') . ' ' . $validated['start_time'] . ':00';
        } else {
            $validated['start_time'] = null;
        }
        if (isset($validated['end_time']) && !empty($validated['end_time'])) {
            $validated['end_time'] = $surgery->scheduled_date->format('Y-m-d') . ' ' . $validated['end_time'] . ':00';
        } else {
            $validated['end_time'] = null;
        }

        // Convert follow_up_date to Carbon instance
        if (isset($validated['follow_up_date']) && !empty($validated['follow_up_date'])) {
            $validated['follow_up_date'] = \Carbon\Carbon::parse($validated['follow_up_date']);
        } else {
            $validated['follow_up_date'] = null;
        }

        // If the current user is surgery staff, allow only team, timing, and supplies fields to be updated.
        if ($user->hasRole('surgery_staff')) {
            $validated = Arr::only($validated, [
                'anesthesiologist_id',
                'anesthesiologist_2_id',
                'surgical_assistant_name',
                'start_time',
                'end_time',
                'supplies',
            ]);
        }

        // Handle estimated duration - use minutes if available, otherwise convert from HH:MM format
        if (isset($validated['estimated_duration_minutes'])) {
            $validated['estimated_duration'] = $validated['estimated_duration_minutes'];
            unset($validated['estimated_duration_minutes']);
        } elseif (isset($validated['estimated_duration']) && !empty($validated['estimated_duration'])) {
            // Check if it's already in minutes (number) or in HH:MM format
            if (is_numeric($validated['estimated_duration'])) {
                // Already in minutes, keep it as is
                $validated['estimated_duration'] = (int)$validated['estimated_duration'];
            } elseif (strpos($validated['estimated_duration'], ':') !== false) {
                // Convert HH:MM to minutes
                list($hours, $minutes) = explode(':', $validated['estimated_duration']);
                $validated['estimated_duration'] = ($hours * 60) + $minutes;
            }
        } else {
            unset($validated['estimated_duration']);
        }

        // Handle prescribed_medications - save surgery treatments to separate table
        \Log::info('=== Surgery Update Details Debug ===');
        \Log::info('Raw request data:', $request->all());
        \Log::info('Request method: ' . $request->method());
        \Log::info('Prescribed medications from request: ' . json_encode($request->input('prescribed_medications')));
        \Log::info('===================================');

        // Check if we have surgery treatments to save
        $prescribedMedications = $request->input('prescribed_medications');
        
        if ($prescribedMedications && isset($prescribedMedications['surgery_treatments'][$surgery->id])) {
            \Log::info('Surgery treatments for surgery ' . $surgery->id . ': ' . json_encode($prescribedMedications['surgery_treatments'][$surgery->id]));

            // Delete existing treatments
            $surgery->surgeryTreatments()->delete();

            // Save new treatments
            $treatments = $prescribedMedications['surgery_treatments'][$surgery->id];
            \Log::info('Treatments array: ' . json_encode($treatments));
            foreach ($treatments as $index => $treatment) {
                \Log::info('Processing treatment ' . $index . ': ' . json_encode($treatment));
                if (!empty($treatment['description'])) {
                    \Log::info('Creating treatment for index ' . $index);
                    SurgeryTreatment::create([
                        'surgery_id' => $surgery->id,
                        'description' => $treatment['description'],
                        'dosage' => $treatment['dosage'] ?? null,
                        'timing' => $treatment['timing'] ?? null,
                        'duration_value' => $treatment['duration_value'] ?? null,
                        'duration_unit' => $treatment['duration_unit'] ?? null,
                        'sort_order' => $index,
                    ]);
                    \Log::info('Treatment created successfully');
                } else {
                    \Log::info('Skipping treatment ' . $index . ' - empty description');
                }
            }
        } else {
            \Log::info('No surgery treatments found in request');
        }

        // Remove prescribed_medications from validated array as we handled it separately
        unset($validated['prescribed_medications']);

        // Save required_fluids to surgeon station
        if ($request->has('required_fluids')) {
            $station = $surgery->surgeonStation;
            if (!$station) {
                $station = $surgery->surgeonStation()->create(['status' => 'pending', 'surgeon_id' => $surgery->doctor_id]);
            }
            $station->update(['required_fluids' => $request->input('required_fluids', [])]);
        }
        unset($validated['required_fluids']);

        \Log::info('Final validated data to update: ', $validated);

        try {
            $surgery->update($validated);
            \Log::info('Surgery updated successfully, new data: ', $surgery->toArray());

            // إذا كانت هناك محطة تخدير قائمة، مزامنة تغييرات أطباء التخدير وبيانات التخدير
            if ($surgery->anesthesiaStation) {
                $stationSync = [];
                foreach (['anesthesiologist_id', 'anesthesiologist_2_id', 'surgical_assistant_name', 'anesthesia_type'] as $field) {
                    if (array_key_exists($field, $validated)) {
                        $stationSync[$field] = $validated[$field];
                    }
                }
                if (!empty($stationSync)) {
                    $surgery->anesthesiaStation->update($stationSync);
                }
            }

            // إتمام محطة الجراح تلقائياً عند حفظ التفاصيل الطبية
            if ($surgery->surgeonStation && $surgery->surgeonStation->status !== 'completed') {
                $surgery->surgeonStation->markAsCompleted();
                
                // إنشاء محطة التخدير التالية
                if (!$surgery->anesthesiaStation) {
                    $surgery->anesthesiaStation()->create([
                        'status' => 'pending',
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error updating surgery: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ في حفظ البيانات: ' . $e->getMessage());
        }

        return redirect()->route('surgeries.show', $surgery)->with('success', 'تم حفظ تفاصيل العملية بنجاح وإرسالها لمحطة التخدير');
    }

    public function updateSurgeryType(Request $request, Surgery $surgery)
    {
        $user = auth()->user();

        if (!$user->hasRole(['admin', 'surgery_staff', 'inquiry_staff'])) {
            abort(403, 'غير مصرح لك بتغيير نوع العملية');
        }

        $validated = $request->validate([
            'surgery_type' => 'required|string|max:255',
        ]);

        $oldType = $surgery->surgery_type;

        if ($validated['surgery_type'] !== $oldType) {
            $surgery->previous_surgery_type = $oldType;
            $surgery->surgery_type = $validated['surgery_type'];
            $surgery->save();

            $surgery->surgeryTypeChanges()->create([
                'old_type' => $oldType,
                'new_type' => $validated['surgery_type'],
                'changed_by' => $user->id,
            ]);

            return redirect()->back()->with('success', 'تم تغيير نوع العملية من "' . $oldType . '" إلى "' . $validated['surgery_type'] . '"');
        }

        return redirect()->back()->with('info', 'لم يتم تغيير نوع العملية');
    }
}
