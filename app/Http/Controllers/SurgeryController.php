<?php

namespace App\Http\Controllers;

use App\Events\SurgeryUpdated;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Location;
use App\Models\OperationTheater;
use App\Models\Patient;
use App\Models\PatientDocument;
use App\Models\RadiologyType;
use App\Models\Room;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Surgery;
use App\Models\SurgeryTabPermission;
use App\Models\SurgeryTreatment;
use App\Models\SurgicalAssistant;
use App\Models\SurgicalOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SurgeryController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $canAccessSurgeries = $user && ($user->can('view surgeries') || $user->can('view anesthesia station'));
        if (! $isAdmin && ! $canAccessSurgeries) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // العمليات النشطة (المجدولة والمنتظرة والجارية فقط - استبعاد ما اكتملت صالته)
        $activeSurgeriesQuery = Surgery::with(['patient.user', 'doctor.user', 'department', 'room', 'operationTheaterStation'])
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->whereDoesntHave('operationTheaterStation', function ($q) {
                $q->where('status', 'completed');
            });

        // فلترة حسب الطبيب إذا كان المستخدم طبيباً جراحاً أو تخدير (وليس استشاري)
        if ($user->doctor && ! $isAdmin && ! $user->hasRole(['receptionist', 'surgery_staff'])) {
            // إخفاء العمليات عن الأطباء الاستشاريين
            if ($user->doctor->type === 'consultant') {
                // لا نعرض أي عمليات للطبيب الاستشاري
                $activeSurgeriesQuery->whereRaw('1 = 0');
            } else {
                // عرض العمليات الخاصة بالطبيب الجراح أو المخدر
                $activeSurgeriesQuery->where(function ($q) use ($user) {
                    $q->where('doctor_id', $user->doctor->id)
                        ->orWhere('anesthesiologist_id', $user->doctor->id)
                        ->orWhere('anesthesiologist_2_id', $user->doctor->id);
                });
            }
        }

        // تطبيق البحث والفلترة
        if (request('search')) {
            $search = request('search');
            $activeSurgeriesQuery->where(function ($query) use ($search) {
                $query->whereHas('patient.user', function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%');
                })
                    ->orWhereHas('doctor.user', function ($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhere('surgery_type', 'like', '%'.$search.'%');
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

        // العمليات المكتملة والملغاة (بما فيها التي اكتملت صالة عملياتها وخرجت)
        $completedSurgeriesQuery = Surgery::with(['patient.user', 'doctor.user', 'department', 'room', 'operationTheaterStation'])
            ->where(function ($q) {
                $q->whereIn('status', ['completed', 'cancelled'])
                    ->orWhereHas('operationTheaterStation', function ($oq) {
                        $oq->where('status', 'completed');
                    });
            });

        // فلترة حسب الطبيب إذا كان المستخدم طبيباً جراحاً أو تخدير (وليس استشاري)
        if ($user->doctor && ! $isAdmin && ! $user->hasRole(['receptionist', 'surgery_staff'])) {
            // إخفاء العمليات عن الأطباء الاستشاريين
            if ($user->doctor->type === 'consultant') {
                // لا نعرض أي عمليات للطبيب الاستشاري
                $completedSurgeriesQuery->whereRaw('1 = 0');
            } else {
                // عرض العمليات الخاصة بالطبيب الجراح أو المخدر
                $completedSurgeriesQuery->where(function ($q) use ($user) {
                    $q->where('doctor_id', $user->doctor->id)
                        ->orWhere('anesthesiologist_id', $user->doctor->id)
                        ->orWhere('anesthesiologist_2_id', $user->doctor->id);
                });
            }
        }

        if (request('search')) {
            $search = request('search');
            $completedSurgeriesQuery->where(function ($query) use ($search) {
                $query->whereHas('patient.user', function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%');
                })
                    ->orWhereHas('doctor.user', function ($q) use ($search) {
                        $q->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhere('surgery_type', 'like', '%'.$search.'%');
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

        // احتساب إحصاءات ومؤشرات العمليات الشاملة للوحة المؤشرات
        $statsQuery = Surgery::query();
        if ($user->hasRole('doctor') && $user->doctor) {
            if ($user->doctor->type === 'consultant') {
                $statsQuery->whereRaw('1 = 0');
            } else {
                $statsQuery->where('doctor_id', $user->doctor->id);
            }
        }
        $stats = (clone $statsQuery)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('scheduled', 'waiting', 'in_progress') AND NOT EXISTS (SELECT 1 FROM operation_theater_stations WHERE operation_theater_stations.surgery_id = surgeries.id AND operation_theater_stations.status = 'completed') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'scheduled' AND NOT EXISTS (SELECT 1 FROM operation_theater_stations WHERE operation_theater_stations.surgery_id = surgeries.id AND operation_theater_stations.status = 'completed') THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'waiting' AND NOT EXISTS (SELECT 1 FROM operation_theater_stations WHERE operation_theater_stations.surgery_id = surgeries.id AND operation_theater_stations.status = 'completed') THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN status = 'in_progress' AND NOT EXISTS (SELECT 1 FROM operation_theater_stations WHERE operation_theater_stations.surgery_id = surgeries.id AND operation_theater_stations.status = 'completed') THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN (status = 'completed' OR EXISTS (SELECT 1 FROM operation_theater_stations WHERE operation_theater_stations.surgery_id = surgeries.id AND operation_theater_stations.status = 'completed')) AND status != 'cancelled' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();

        // بيانات مخزن وعُهدة العمليات: يظهر التبويب فقط للحساب المعين له مخزن عمليات في إدارة المستخدمين (location_id)
        $userAssignedLocation = ($user && $user->location_id)
            ? Location::where('type', 'sub')->find($user->location_id)
            : null;
        $canViewOrStoreTab = (bool) ($userAssignedLocation && Surgery::isOperationLocationName($userAssignedLocation->name));
        $orCurrentLocation = $canViewOrStoreTab ? $userAssignedLocation : null;
        $orLocations = $orCurrentLocation ? collect([$orCurrentLocation]) : collect();
        $isOperationCustodian = $canViewOrStoreTab;

        $orStockBatches = collect();
        $orRecentMovements = collect();
        if ($canViewOrStoreTab && $orCurrentLocation) {
            $orStockBatches = StockBatch::with('product')
                ->where('location_id', $orCurrentLocation->id)
                ->where('current_qty', '>', 0)
                ->orderBy('expiry_date')
                ->get();

            $orRecentMovements = StockMovement::with(['batch.product', 'user', 'surgery.patient.user'])
                ->where('from_location_id', $orCurrentLocation->id)
                ->orderBy('created_at', 'desc')
                ->take(30)
                ->get();
        }

        $allowedIndexTabs = $canViewOrStoreTab ? ['active', 'completed', 'or_store'] : ['active', 'completed'];
        $activeIndexTab = in_array(request()->query('tab'), $allowedIndexTabs, true)
            ? request()->query('tab')
            : 'active';

        return view('surgeries.index', compact(
            'activeSurgeries',
            'completedSurgeries',
            'stats',
            'canViewOrStoreTab',
            'orLocations',
            'orCurrentLocation',
            'isOperationCustodian',
            'orStockBatches',
            'orRecentMovements',
            'activeIndexTab'
        ));
    }

    public function create()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('create surgeries'))) {
            abort(403, 'غير مصرح لك بإنشاء عمليات جراحية');
        }

        // احضر البيانات مع الترتيب الأبجدي للأسماء
        $patients = Patient::with('user')->get()->sortBy(function ($p) {
            return optional($p->user)->name ?? '';
        });
        $doctors = Doctor::with('user')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('type', 'surgeon')
                    ->orWhere('specialization', 'جراحة')
                    ->orWhereHas('user', function ($uq) {
                        $uq->whereHas('roles', function ($rq) {
                            $rq->whereIn('name', ['الجراح', 'surgeon', 'جراح']);
                        });
                    });
            })
            ->get()
            ->sortBy(function ($d) {
                return optional($d->user)->name ?? '';
            });
        $labTests = LabTest::active()->orderBy('name')->get();
        $radiologyTypes = RadiologyType::active()->orderBy('name')->get();
        $surgicalOperations = \App\Models\SurgicalOperation::where('is_active', true)->orderBy('category')->orderBy('name')->get();
        $rooms = Room::where('is_active', true)->where('room_purpose', 'beds')->orderBy('room_type')->orderBy('room_number')->get();
        $locations = \App\Models\Location::orderBy('name')->get();

        $selectedPatient = null;
        if (request('patient_id')) {
            $selectedPatient = Patient::with('user')->find(request('patient_id'));
        }

        return view('surgeries.create', compact('patients', 'doctors', 'labTests', 'radiologyTypes', 'surgicalOperations', 'rooms', 'locations', 'selectedPatient'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('create surgeries'))) {
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
            'room_id' => 'required|exists:rooms,id',
            'location_id' => 'nullable|exists:locations,id',
            'expected_stay_days' => 'required|integer|min:1|max:365',
            'surgery_category' => 'required|string|max:255',
            'surgical_operation_id' => 'required|exists:surgical_operations,id',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable',
            'referring_doctor_name' => 'required|string|max:255',
            'referral_letter' => 'nullable|file|max:25600',
            'scanned_referral_letter' => 'nullable|string',
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
            'additional_operations' => 'nullable|array',
            'additional_operations.*.surgical_operation_id' => 'nullable|exists:surgical_operations,id',
            'additional_operations.*.fee' => 'nullable',
            'additional_operations.*.notes' => 'nullable|string|max:500',
        ]);

        $doctor = Doctor::find($request->doctor_id);
        if (! $doctor || ! $doctor->department_id) {
            return back()->withInput()->withErrors(['doctor_id' => 'الطبيب المختار يجب أن يكون مرتبطاً بقسم.']);
        }
        $request->merge(['department_id' => $doctor->department_id]);

        $surgeryData = $request->except(['referral_letter', 'scanned_referral_letter', 'additional_operations']);

        $patient = Patient::find($request->patient_id);
        $applyInsurance = $request->input('apply_insurance', '1') == '1';
        $bookingInsuranceType = ($applyInsurance && $patient && $patient->insurance_type !== 'none') ? $patient->insurance_type : 'none';
        if ($request->filled('insurance_type')) {
            $bookingInsuranceType = $request->insurance_type;
        }
        $surgeryData['insurance_type'] = $bookingInsuranceType;

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

        $scheduledTimeStr = $request->filled('scheduled_time') ? $request->scheduled_time : '00:00';
        $surgeryData['scheduled_time'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $request->scheduled_date.' '.$scheduledTimeStr);

        // حساب أجرة الغرفة المبدئية عند الحجز (الليلة الأولى: عادية=0, VIP=100,000, VVIP=200,000)
        if ($request->room_id) {
            $room = Room::find($request->room_id);
            if ($room) {
                $surgeryData['room_fee'] = Room::getInitialFeeForType($room->room_type);
                // تحديث حالة الغرفة لمحجوزة
                $room->update(['status' => 'occupied']);
            }
        }

        // handle referral letter upload or direct scanner capture
        $path = null;
        if ($request->hasFile('referral_letter')) {
            $file = $request->file('referral_letter');
            $path = $file->store('referrals', 'public');
            $surgeryData['referral_letter_path'] = $path;
        } elseif ($request->filled('scanned_referral_letter')) {
            $base64Data = $request->input('scanned_referral_letter');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                $ext = strtolower($type[1]);
            } else {
                $ext = 'jpg';
            }
            $base64Data = base64_decode($base64Data);
            if ($base64Data !== false) {
                $storedName = 'referral_scan_'.time().'_'.uniqid().'.'.$ext;
                $relativePath = 'referrals/'.$storedName;
                Storage::disk('public')->put($relativePath, $base64Data);
                $path = $relativePath;
                $surgeryData['referral_letter_path'] = $path;
            }
        }

        $surgery = Surgery::create($surgeryData);

        // حفظ العمليات الجراحية المرافقة / الإضافية إن وجدت
        if ($request->filled('additional_operations') && is_array($request->additional_operations)) {
            foreach ($request->additional_operations as $addOp) {
                if (! empty($addOp['surgical_operation_id'])) {
                    $rawFee = $addOp['fee'] ?? 0;
                    $normalizedAddFee = is_numeric($rawFee) ? (float) $rawFee : (float) str_replace([',', ' '], ['', ''], $rawFee);

                    $surgery->additionalOperations()->create([
                        'surgical_operation_id' => $addOp['surgical_operation_id'],
                        'fee' => $normalizedAddFee,
                        'notes' => $addOp['notes'] ?? null,
                        'added_by' => Auth::id(),
                    ]);
                }
            }
        }

        // أرشفة الوثيقة تلقائياً في سجل وأرشيف المريض
        if ($path && $surgery->patient_id) {
            PatientDocument::create([
                'patient_id' => $surgery->patient_id,
                'title' => 'ورقة تحويل / مستند عملية: '.($surgery->surgery_type ?? 'عملية جراحية'),
                'category' => 'surgery_consent',
                'file_path' => $path,
                'file_name' => basename($path),
                'file_type' => 'image/jpeg',
                'file_size' => Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : null,
                'notes' => 'مرفق تلقائي من حجز العملية #'.$surgery->id,
                'uploaded_by' => Auth::id(),
            ]);
        }

        // إنشاء زيارة للعملية إذا لم تكن موجودة
        if (! $surgery->visit_id) {
            $visit = \App\Models\Visit::create([
                'patient_id' => $surgery->patient_id,
                'department_id' => $surgery->department_id,
                'doctor_id' => $surgery->doctor_id,
                'visit_date' => $surgery->scheduled_date,
                'visit_time' => $surgery->scheduled_time,
                'visit_type' => 'surgery',
                'chief_complaint' => 'عملية جراحية: '.$surgery->surgery_type,
                'status' => 'pending_payment',
                'notes' => 'زيارة خاصة بالعملية الجراحية #'.$surgery->id,
            ]);
            $surgery->update(['visit_id' => $visit->id]);
        } else {
            $visit = $surgery->visit;
        }

        // إنشاء طلبات المختبر - فقط إذا تم تحديد تحاليل معينة
        $labTestIds = $request->input('lab_tests', []);
        if (is_array($labTestIds) && count($labTestIds)) {
            foreach ($labTestIds as $labTestId) {
                if ($labTestId && ! $surgery->labTests()->where('lab_test_id', $labTestId)->exists()) {
                    $surgery->labTests()->create([
                        'lab_test_id' => $labTestId,
                        'status' => 'pending',
                        'payment_status' => 'pending',
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
                if ($radiologyTypeId && ! $surgery->radiologyTests()->where('radiology_type_id', $radiologyTypeId)->exists()) {
                    $surgery->radiologyTests()->create([
                        'radiology_type_id' => $radiologyTypeId,
                        'status' => 'pending',
                        'payment_status' => 'pending',
                    ]);
                }
            }
        }
        // ملاحظة: لا نقوم بإنشاء سجلات عامة (بدون radiology_type_id)
        // سيقوم موظف الأشعة بإضافة الأشعة المطلوبة لاحقاً

        // إرفاق الخدمات الإضافية التلقائية للعملية (تخطيط قلب، فتح طبلة، خدمات فندقية)
        $surgery->attachDefaultExtraServices();

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->route('surgeries.index')->with('success', 'تم حجز العملية بنجاح');
    }

    public function show(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $isAnesthesiaUser = SurgeryTabPermission::isAnesthesiaUser($user);
        $hasStationAccess = $user && $user->hasAnyPermission([
            'view surgeries', 'view resident station', 'view surgeon station',
            'view anesthesia station', 'view nursing station', 'view operation theater station',
        ]);
        $isAssignedDoctor = $user && $user->doctor && (
            $user->doctor->id == $surgery->doctor_id
            || $user->doctor->id == $surgery->anesthesiologist_id
            || $user->doctor->id == $surgery->anesthesiologist_2_id
        );
        $hasConfiguredDetailAccess = SurgeryTabPermission::hasConfiguredVisibleDetailTab($user);

        if (! $isAdmin && ! $hasStationAccess && ! $isAssignedDoctor && ! $isAnesthesiaUser && ! $hasConfiguredDetailAccess) {
            abort(403, 'غير مصرح لك باستعراض تفاصيل العمليات الجراحية');
        }

        // منع الأطباء الاستشاريين من استعراض وتعديل العمليات
        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم باستعراض العمليات الجراحية');
        }

        $surgery->load([
            'patient.user',
            'doctor.user',
            'department',
            'visit',
            'labTests.labTest',
            'radiologyTests.radiologyType',
            'anesthesiologist.user',
            'anesthesiologist2.user',
            'surgeryTreatments.stockMovement.fromLocation',
            'surgeryTreatments.batch.product',
            'surgeryTreatments.administeredBy',
            'anesthesiaStation',
            'surgeonStation',
            'residentStationFollowUps',
            'medicalDevices',
            'additionalOperations.surgicalOperation',
            'room',
            'location',
        ]);
        $patients = Patient::with('user')->get()->sortBy(function ($p) {
            return optional($p->user)->name ?? '';
        });
        $anesthesiaDoctors = Doctor::with('user')
            ->anesthesia()
            ->get()
            ->sortBy(function ($d) {
                return optional($d->user)->name ?? '';
            });
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $labTests = LabTest::active()->orderBy('name')->get();
        $radiologyTypes = RadiologyType::active()->orderBy('name')->get();
        $surgicalOperations = \App\Models\SurgicalOperation::where('is_active', true)->orderBy('category')->orderBy('name')->get();
        $devices = \App\Models\MedicalDevice::where('status', 'active')
            ->orderBy('name')
            ->get();
        $locations = \App\Models\Location::orderBy('name')->get();

        // Compute dynamic tab permissions for /surgeries/{id}
        $detailPermissions = SurgeryTabPermission::getDetailTabPermissions($user);
        $visibleTabs = [];
        foreach (SurgeryTabPermission::DETAIL_TABS as $key => $meta) {
            $access = $detailPermissions[$key] ?? SurgeryTabPermission::ACCESS_HIDDEN;
            if ($access !== SurgeryTabPermission::ACCESS_HIDDEN) {
                $visibleTabs[$key] = array_merge($meta, [
                    'access' => $access,
                    'can_write' => ($access === SurgeryTabPermission::ACCESS_WRITE),
                    'is_readonly' => ($access === SurgeryTabPermission::ACCESS_READ),
                ]);
            }
        }

        $activeTab = $request->query('tab');
        if (! $activeTab || ! isset($visibleTabs[$activeTab])) {
            if ($isAnesthesiaUser && isset($visibleTabs['detail_anesthesia'])) {
                $activeTab = 'detail_anesthesia';
            } elseif ($user->hasRole('surgery_staff') && ! $isAdmin && isset($visibleTabs['detail_team_timing'])) {
                $activeTab = 'detail_team_timing';
            } elseif (! $isAdmin && isset($visibleTabs['detail_diagnosis']) && $visibleTabs['detail_diagnosis']['can_write']) {
                $activeTab = 'detail_diagnosis';
            } else {
                $activeTab = array_key_first($visibleTabs) ?? 'detail_tests';
            }
        }

        // بيانات مخزن وعُهدة العمليات وصرف المواد داخل تبويب detail_supplies
        $operationStoreLocation = $surgery->getOperationSubStoreLocation($user);
        $operationSupplyBatches = $operationStoreLocation
            ? StockBatch::with('product')
                ->where('location_id', $operationStoreLocation->id)
                ->where('current_qty', '>', 0)
                ->orderBy('expiry_date')
                ->get()
            : collect();

        $orDispensedItems = ($surgery->surgeryTreatments ?? collect())
            ->filter(function (SurgeryTreatment $t) {
                return ($t->stockMovement && Surgery::isOperationLocationName($t->stockMovement->fromLocation?->name))
                    || $t->timing === 'صرف من مخزن العمليات';
            })
            ->sortByDesc('id')
            ->values();

        $surgicalAssistants = SurgicalAssistant::active()->orderBy('name')->get();

        return view('surgeries.show', compact(
            'surgery',
            'patients',
            'anesthesiaDoctors',
            'departments',
            'labTests',
            'radiologyTypes',
            'surgicalOperations',
            'devices',
            'locations',
            'detailPermissions',
            'visibleTabs',
            'activeTab',
            'isAnesthesiaUser',
            'operationStoreLocation',
            'operationSupplyBatches',
            'orDispensedItems',
            'surgicalAssistants'
        ));
    }

    public function edit(Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('edit surgeries'))) {
            abort(403, 'غير مصرح لك بتعديل حجز العمليات الجراحية');
        }

        $surgery->load(['patient.user', 'doctor.user', 'department', 'visit', 'labTests.labTest', 'radiologyTests.radiologyType', 'anesthesiologist.user', 'anesthesiologist2.user', 'anesthesiaStation', 'room', 'additionalOperations.surgicalOperation']);
        $patients = Patient::with('user')->get()->sortBy(function ($p) {
            return optional($p->user)->name ?? '';
        });
        $doctors = Doctor::with('user')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('type', 'surgeon')
                    ->orWhere('specialization', 'جراحة')
                    ->orWhere('type', 'anesthesia')
                    ->orWhereHas('user', function ($uq) {
                        $uq->whereHas('roles', function ($rq) {
                            $rq->whereIn('name', ['الجراح', 'surgeon', 'جراح']);
                        });
                    });
            })
            ->get()
            ->sortBy(function ($d) {
                return optional($d->user)->name ?? '';
            });
        $surgicalOperations = \App\Models\SurgicalOperation::where('is_active', true)->orderBy('category')->orderBy('name')->get();
        // إحضار الغرف مع الغرفة المحجوزة حالياً
        $rooms = Room::where('room_purpose', 'beds')
            ->where(function ($query) use ($surgery) {
                $query->where('is_active', true);
                if ($surgery->room_id) {
                    $query->orWhere('id', $surgery->room_id);
                }
            })
            ->orderBy('room_type')
            ->orderBy('room_number')
            ->get();

        return view('surgeries.edit', compact('surgery', 'patients', 'doctors', 'surgicalOperations', 'rooms'));
    }

    public function print(Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $canView = $user && ($user->can('view surgeries') || $user->can('view surgeon station'));
        $isAssignedDoctor = $user && $user->doctor && ($user->doctor->id == $surgery->doctor_id);

        if (! $isAdmin && ! $canView && ! $isAssignedDoctor) {
            abort(403, 'غير مصرح لك بطباعة تفاصيل العملية');
        }

        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بطباعة تفاصيل العملية');
        }

        $surgery->load(['patient.user', 'doctor.user', 'department', 'visit', 'labTests.labTest', 'radiologyTests.radiologyType', 'anesthesiologist.user', 'anesthesiologist2.user', 'anesthesiaStation', 'room']);

        return view('surgeries.print', compact('surgery'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('edit surgeries'))) {
            abort(403, 'غير مصرح لك بتعديل حجز العمليات الجراحية');
        }

        if ($request->filled('custom_surgery_fee')) {
            $normalizedFee = str_replace([',', ' '], ['', ''], $request->custom_surgery_fee);
            $request->merge(['custom_surgery_fee' => $normalizedFee]);
        }

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'room_id' => 'required|exists:rooms,id',
            'expected_stay_days' => 'required|integer|min:1|max:365',
            'surgery_category' => 'required|string|max:255',
            'surgical_operation_id' => 'required|exists:surgical_operations,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'nullable',
            'referring_doctor_name' => 'required|string|max:255',
            'referral_letter' => 'nullable|file|max:25600',
            'scanned_referral_letter' => 'nullable|string',
            'custom_surgery_fee' => 'required',
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'anesthesiologist_2_id' => 'nullable|exists:doctors,id',
            'additional_operations' => 'nullable|array',
            'additional_operations.*.surgical_operation_id' => 'nullable|exists:surgical_operations,id',
            'additional_operations.*.fee' => 'nullable',
            'additional_operations.*.notes' => 'nullable|string',
        ], [
            'room_id.required' => 'يرجى اختيار غرفة للمريض قبل تأكيد الحجز.',
            'expected_stay_days.required' => 'يرجى تحديد عدد أيام الإقامة المتوقعة.',
        ]);

        $doctor = Doctor::find($request->doctor_id);
        if ($doctor && $doctor->department_id) {
            $request->merge(['department_id' => $doctor->department_id]);
        }

        $surgeryData = $request->except(['referral_letter', 'scanned_referral_letter', 'additional_operations']);
        $scheduledTimeStr = $request->filled('scheduled_time') ? $request->scheduled_time : '00:00';
        $surgeryData['scheduled_time'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $request->scheduled_date.' '.$scheduledTimeStr);

        $operation = SurgicalOperation::find($request->surgical_operation_id);
        if ($operation) {
            $surgeryData['surgery_type'] = $operation->name;
            if ($request->filled('custom_surgery_fee')) {
                $surgeryData['surgery_fee'] = $request->custom_surgery_fee;
            } else {
                $surgeryData['surgery_fee'] = $operation->fee;
            }
        }

        // تحديث نوع تغطية العملية (ضمان أم كاش)
        $patient = Patient::find($request->patient_id);
        if ($request->has('apply_insurance')) {
            $applyInsurance = $request->input('apply_insurance') == '1';
            $bookingInsuranceType = ($applyInsurance && $patient && $patient->insurance_type !== 'none') ? $patient->insurance_type : 'none';
            $surgeryData['insurance_type'] = $bookingInsuranceType;
        } elseif ($request->filled('insurance_type')) {
            $surgeryData['insurance_type'] = $request->insurance_type;
        }

        // معالجة تغيير الغرفة وإعادة احتساب الأجرة الفندقية
        if ($surgery->room_id != $request->room_id) {
            if ($surgery->room_id) {
                Room::where('id', $surgery->room_id)->update(['status' => 'available']);
            }
            if ($request->room_id) {
                $newRoom = Room::find($request->room_id);
                if ($newRoom) {
                    $newRoom->update(['status' => 'occupied']);
                    $surgeryData['room_fee'] = Room::getInitialFeeForType($newRoom->room_type);
                }
            } else {
                $surgeryData['room_fee'] = 0;
            }
        } elseif ($request->room_id && $surgery->room) {
            // إعادة احتساب تفاصيل الإقامة الفندقية الحالية
            $stayDetails = $surgery->calculateStayDetails();
            $surgeryData['room_fee'] = $stayDetails['total_fee'];
        }

        $path = null;
        if ($request->hasFile('referral_letter')) {
            $file = $request->file('referral_letter');
            $path = $file->store('referrals', 'public');
            $surgeryData['referral_letter_path'] = $path;
        } elseif ($request->filled('scanned_referral_letter')) {
            $base64Data = $request->input('scanned_referral_letter');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
                $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
                $ext = strtolower($type[1]);
            } else {
                $ext = 'jpg';
            }
            $base64Data = base64_decode($base64Data);
            if ($base64Data !== false) {
                $storedName = 'referral_scan_'.time().'_'.uniqid().'.'.$ext;
                $relativePath = 'referrals/'.$storedName;
                Storage::disk('public')->put($relativePath, $base64Data);
                $path = $relativePath;
                $surgeryData['referral_letter_path'] = $path;
            }
        }

        // إذا تغيرت أجور الغرفة وزادت عن المبلغ المدفوع مسبقاً، نحول الحالة لـ pending لتحصيل الفرق في الكاشير
        $newRoomFee = $surgeryData['room_fee'] ?? $surgery->room_fee ?? 0;
        $paidRoomFee = $surgery->room_fee_paid_amount ?? 0;
        if ($newRoomFee > $paidRoomFee && $surgery->payment_status === 'paid') {
            $surgeryData['payment_status'] = 'pending';
        }

        $surgery->update($surgeryData);

        // تحديث العمليات الإضافية / المرافقة
        $surgery->additionalOperations()->delete();
        if ($request->has('additional_operations') && is_array($request->additional_operations)) {
            foreach ($request->additional_operations as $addOp) {
                if (! empty($addOp['surgical_operation_id'])) {
                    $rawFee = $addOp['fee'] ?? 0;
                    $normalizedAddFee = is_numeric($rawFee) ? (float) $rawFee : (float) str_replace([',', ' '], ['', ''], $rawFee);

                    $surgery->additionalOperations()->create([
                        'surgical_operation_id' => $addOp['surgical_operation_id'],
                        'fee' => $normalizedAddFee,
                        'notes' => $addOp['notes'] ?? null,
                        'added_by' => Auth::id(),
                    ]);
                }
            }
        }

        // أرشفة الوثيقة في سجل المريض إن وجدت
        if ($path && $surgery->patient_id) {
            PatientDocument::create([
                'patient_id' => $surgery->patient_id,
                'title' => 'ورقة تحويل / مستند عملية: '.($surgery->surgery_type ?? 'عملية جراحية'),
                'category' => 'surgery_consent',
                'file_path' => $path,
                'file_name' => basename($path),
                'file_type' => 'image/jpeg',
                'file_size' => Storage::disk('public')->exists($path) ? Storage::disk('public')->size($path) : null,
                'notes' => 'مرفق محدث للعملية الجراحية #'.$surgery->id,
                'uploaded_by' => Auth::id(),
            ]);
        }

        // إنشاء طلبات الأشعة - فقط إذا تم تحديد أشعة معينة
        $radiologyTypeIds = $request->input('radiology_tests', []);
        if (is_array($radiologyTypeIds) && count($radiologyTypeIds)) {
            foreach ($radiologyTypeIds as $radiologyTypeId) {
                if ($radiologyTypeId && ! $surgery->radiologyTests()->where('radiology_type_id', $radiologyTypeId)->exists()) {
                    $surgery->radiologyTests()->create([
                        'radiology_type_id' => $radiologyTypeId,
                        'status' => 'pending',
                        'payment_status' => 'pending',
                    ]);
                }
            }
        }
        // ملاحظة: لا نقوم بإنشاء سجلات عامة (بدون radiology_type_id)
        // سيقوم موظف الأشعة بإضافة الأشعة المطلوبة لاحقاً

        // إرفاق الخدمات الإضافية التلقائية للعملية (تخطيط قلب، فتح طبلة، خدمات فندقية)
        $surgery->attachDefaultExtraServices();

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->route('surgeries.index')
            ->with('success', 'تم تعديل حجز العملية الجراحية بنجاح');
    }

    public function waiting(?Request $request = null)
    {
        // شاشة قائمة الانتظار عامة للعرض الحي على الشاشات المنفصلة (Public OR Flight Board)
        $request = $request ?? request();
        $selectedDate = $request->query('date', today()->format('Y-m-d'));
        try {
            $parsedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $selectedDate);
        } catch (\Exception $e) {
            $parsedDate = today();
            $selectedDate = $parsedDate->format('Y-m-d');
        }

        // 1. تحديد الشهر والسنة لليوم المختار وبناء شبكة التقويم الشهري
        $month = $parsedDate->month;
        $year = $parsedDate->year;
        $startOfMonth = $parsedDate->copy()->startOfMonth();
        $endOfMonth = $parsedDate->copy()->endOfMonth();

        // بداية ونهاية شبكة التقويم (من السبت إلى الجمعة لتغطية كل أسابيع الشهر)
        $calendarStart = $startOfMonth->copy()->startOfWeek(\Carbon\Carbon::SATURDAY);
        $calendarEnd = $endOfMonth->copy()->endOfWeek(\Carbon\Carbon::FRIDAY);

        // جلب أعداد العمليات لكافة أيام شبكة التقويم باستعلام تجميعي واحد وسريع
        $monthlyCounts = Surgery::whereBetween('scheduled_date', [
            $calendarStart->format('Y-m-d'),
            $calendarEnd->format('Y-m-d'),
        ])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('DATE(scheduled_date) as day_date, count(*) as count')
            ->groupBy('day_date')
            ->pluck('count', 'day_date');

        $arabicDayNames = [
            'Saturday' => 'السبت',
            'Sunday' => 'الأحد',
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
        ];

        $arabicMonths = [
            1 => 'كانون الثاني (يناير)',
            2 => 'شباط (فبراير)',
            3 => 'آذار (مارس)',
            4 => 'نيسان (أبريل)',
            5 => 'أيار (مايو)',
            6 => 'حزيران (يونيو)',
            7 => 'تموز (يوليو)',
            8 => 'آب (أغسطس)',
            9 => 'أيلول (سبتمبر)',
            10 => 'تشرين الأول (أكتوبر)',
            11 => 'تشرين الثاني (نوفمبر)',
            12 => 'كانون الأول (ديسمبر)',
        ];

        $calendarDays = [];
        $curr = $calendarStart->copy();
        while ($curr->lte($calendarEnd)) {
            $dayKey = $curr->format('Y-m-d');
            $count = (int) ($monthlyCounts[$dayKey] ?? 0);
            $englishDay = $curr->format('l');

            $calendarDays[] = [
                'date' => $dayKey,
                'day_number' => $curr->format('j'),
                'day_name' => $arabicDayNames[$englishDay] ?? $englishDay,
                'formatted_date' => $curr->format('d/m'),
                'is_current_month' => $curr->month === $month,
                'is_today' => $curr->isToday(),
                'is_selected' => $dayKey === $selectedDate,
                'count' => $count,
            ];
            $curr->addDay();
        }

        $weekDays = $calendarDays; // للتوافق العكسي
        $prevMonthDate = $startOfMonth->copy()->subMonth()->format('Y-m-d');
        $nextMonthDate = $startOfMonth->copy()->addMonth()->format('Y-m-d');
        $monthTitle = ($arabicMonths[$month] ?? $parsedDate->format('F')).' '.$year;
        $totalMonthSurgeries = Surgery::whereBetween('scheduled_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->where('status', '!=', 'cancelled')
            ->count();

        // جلب كافة عمليات اليوم المحدد
        $daySurgeries = Surgery::with([
            'patient.user',
            'doctor.user',
            'department',
            'room',
            'operationTheater',
            'operationTheaterStation',
            'anesthesiologist.user',
            'additionalOperations.surgicalOperation',
            'payment',
            'preOpResidentStation',
        ])
            ->whereDate('scheduled_date', $selectedDate)
            ->where('status', '!=', 'cancelled')
            ->orderBy('scheduled_time', 'asc')
            ->get();

        if ($parsedDate->isToday()) {
            $extraActive = Surgery::with([
                'patient.user',
                'doctor.user',
                'department',
                'room',
                'operationTheater',
                'operationTheaterStation',
                'anesthesiologist.user',
                'additionalOperations.surgicalOperation',
                'payment',
                'preOpResidentStation',
            ])
                ->whereIn('status', ['waiting', 'checked_in', 'in_progress'])
                ->whereDate('scheduled_date', '!=', $selectedDate)
                ->get();

            $daySurgeries = $daySurgeries->merge($extraActive)->unique('id');
        }

        $scheduledSurgeries = $daySurgeries->where('status', 'scheduled')->values();
        $activeSurgeries = $daySurgeries->whereIn('status', ['waiting', 'checked_in', 'in_progress'])->values();
        $recoverySurgeries = $daySurgeries->where('status', 'completed')->values();

        // صالات العمليات المتاحة
        $operationTheaters = OperationTheater::where('is_active', true)->orderBy('name')->get();

        return view('surgeries.waiting', compact(
            'scheduledSurgeries',
            'activeSurgeries',
            'recoverySurgeries',
            'daySurgeries',
            'calendarDays',
            'prevMonthDate',
            'nextMonthDate',
            'monthTitle',
            'totalMonthSurgeries',
            'weekDays',
            'selectedDate',
            'parsedDate',
            'operationTheaters'
        ));
    }

    public function quickStoreTheater(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('manage surgery waiting list'))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بإضافة صالة'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:operation_theaters,name',
            'code' => 'nullable|string|max:50',
        ]);

        $theater = OperationTheater::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'theater' => $theater,
            'message' => 'تم إضافة صالة العمليات بنجاح',
        ]);
    }

    public function quickUpdateSchedule(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('manage surgery waiting list') && ! $user->can('control surgeries') && ! $user->can('edit surgeries')))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل الموعد والصالة'], 403);
        }

        $validated = $request->validate([
            'operation_theater_id' => 'nullable|exists:operation_theaters,id',
            'scheduled_time' => 'nullable|string',
            'scheduled_date' => 'nullable|date',
        ]);

        if ($request->has('operation_theater_id')) {
            $surgery->operation_theater_id = $validated['operation_theater_id'] ?: null;
        }

        if ($request->filled('scheduled_time')) {
            $dateStr = $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : today()->format('Y-m-d');
            $surgery->scheduled_time = \Carbon\Carbon::parse($dateStr.' '.$validated['scheduled_time']);
        } elseif ($request->has('scheduled_time') && empty($validated['scheduled_time'])) {
            $surgery->scheduled_time = null;
        }

        if ($request->filled('scheduled_date')) {
            $surgery->scheduled_date = $validated['scheduled_date'];
        }

        $surgery->save();
        $surgery->load('operationTheater');

        broadcast(new SurgeryUpdated($surgery));

        return response()->json([
            'success' => true,
            'surgery' => $surgery,
            'theater_name' => $surgery->operationTheater->name ?? 'غير محددة',
            'formatted_time' => $surgery->scheduled_time ? $surgery->scheduled_time->format('h:i A') : 'غير محدد',
            'message' => 'تم تحديث موعد وصالة العملية بنجاح',
        ]);
    }

    public function waitingList()
    {
        return $this->waiting();
    }

    public function checkIn(Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('manage surgery waiting list') && ! $user->can('control surgeries') && ! $user->can('edit surgeries')))) {
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
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('control surgeries'))) {
            abort(403, 'غير مصرح لك ببدء العملية');
        }

        $surgery->status = 'in_progress';
        $surgery->started_at = now();
        $surgery->save();

        // بدء محطة صالة العمليات
        $otStation = $surgery->operationTheaterStation;
        if (! $otStation) {
            $otStation = $surgery->operationTheaterStation()->create([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        } else {
            $otStation->markAsStarted();
        }

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم بدء العملية وبدء محطة صالة العمليات في '.now()->format('H:i'));
    }

    public function complete(Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('control surgeries'))) {
            abort(403, 'غير مصرح لك بإكمال العملية');
        }

        // إتمام محطة صالة العمليات
        $otStation = $surgery->operationTheaterStation;
        if (! $otStation) {
            $otStation = $surgery->operationTheaterStation()->create([
                'status' => 'pending',
            ]);
        }
        $otStation->markAsCompleted();

        // إنشاء وتوجيه العملية لمحطة الجراح التالية
        if (! $surgery->surgeonStation) {
            $surgery->surgeonStation()->create([
                'surgeon_id' => $surgery->doctor_id,
                'status' => 'pending',
            ]);
        }

        // إنهاء العملية واكتمال مرحلة صالة العمليات وخروج المريض للمتابعة
        $surgery->status = 'completed';
        $surgery->end_time = now();
        $surgery->save();

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم إنهاء العملية بنجاح واكتمال مرحلة صالة العمليات وخروج المريض');
    }

    public function discharge(Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('control surgeries'))) {
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
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('delete surgeries') && ! $user->can('control surgeries')))) {
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

            // تحرير الغرفة المرتبطة إن وجدت
            if ($surgery->room_id) {
                Room::where('id', $surgery->room_id)->update(['status' => 'available']);
            }

            // إلغاء الفحوصات وإيقافها مع الحفاظ على سجل المدفوع منها
            $surgery->labTests()->where('payment_status', '!=', 'paid')->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);
            $surgery->labTests()->where('payment_status', 'paid')->update([
                'status' => 'cancelled',
            ]);

            $surgery->radiologyTests()->where('payment_status', '!=', 'paid')->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
            ]);
            $surgery->radiologyTests()->where('payment_status', 'paid')->update([
                'status' => 'cancelled',
            ]);
        });

        $surgery->refresh();
        broadcast(new SurgeryUpdated($surgery));

        $totalPaid = ($surgery->surgery_fee_paid_amount ?? 0) + ($surgery->room_fee_paid_amount ?? 0);
        $hasPaidRecord = $totalPaid > 0 || $surgery->payments()->where('amount', '>', 0)->exists();

        $successMsg = 'تم إلغاء العملية وتحرير الغرفة وإيقاف الفحوصات المرتبطة بها بنجاح.';
        if ($hasPaidRecord) {
            $successMsg .= ' المبالغ المسددة مسبقاً ('.number_format($totalPaid, 0).' د.ع) أصبحت جاهزة الآن في شاشة الكاشير لعمل استرجاع مالي (Refund) للمريض.';
        }

        return redirect()->back()->with('success', $successMsg);
    }

    public function returnToWaiting(Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('manage surgery waiting list') && ! $user->can('control surgeries')))) {
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
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $isAnesthesiaUser = SurgeryTabPermission::isAnesthesiaUser($user);
        $detailPermissions = SurgeryTabPermission::getDetailTabPermissions($user);

        // منع الأطباء الاستشاريين من تحديث تفاصيل العمليات
        if ($user && $user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بتحديث تفاصيل العمليات الجراحية');
        }

        $writableTabs = array_keys(array_filter(
            $detailPermissions,
            fn (string $level) => $level === SurgeryTabPermission::ACCESS_WRITE
        ));

        $canBaseEdit = $isAdmin
            || $user?->can('edit surgeries')
            || $user?->can('view surgeon station')
            || $user?->hasRole('surgery_staff')
            || $isAnesthesiaUser
            || ($user && $user->doctor && (
                $user->doctor->id == $surgery->doctor_id
                || $user->doctor->id == $surgery->anesthesiologist_id
                || $user->doctor->id == $surgery->anesthesiologist_2_id
            ))
            || ! empty($writableTabs);

        if (! $canBaseEdit || empty($writableTabs)) {
            abort(403, 'غير مصرح لك بتحديث تفاصيل العملية');
        }

        $requestedTab = $request->input('tab');
        $scopedTabs = ['detail_type', 'detail_diagnosis', 'detail_anesthesia', 'detail_team_timing', 'detail_supplies'];
        if ($requestedTab !== null && $requestedTab !== '') {
            if (! in_array($requestedTab, $scopedTabs, true)) {
                abort(403, 'تبويب غير صالح للتحديث.');
            }
            if (($detailPermissions[$requestedTab] ?? SurgeryTabPermission::ACCESS_HIDDEN) !== SurgeryTabPermission::ACCESS_WRITE) {
                abort(403, 'غير مصرح لك بتعديل هذا القسم.');
            }
        }

        $validated = $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'diagnosis' => 'nullable|string|max:1000',
            'anesthesia_type' => 'nullable|string|max:255',
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'anesthesiologist_2_id' => 'nullable|exists:doctors,id',
            'anesthesia_start_time' => 'nullable|date_format:H:i',
            'anesthesia_end_time' => 'nullable|date_format:H:i',
            'anesthesia_notes' => 'nullable|string|max:2000',
            'surgical_assistant_name' => 'nullable|string|max:255',
            'supplies' => 'nullable|string|max:10000',
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

        // Determine writable fields per tab permission
        $canWriteType = ($detailPermissions['detail_type'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteDiagnosis = ($detailPermissions['detail_diagnosis'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteAnesthesia = ($detailPermissions['detail_anesthesia'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteTeamTiming = ($detailPermissions['detail_team_timing'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteSupplies = ($detailPermissions['detail_supplies'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;

        if ($requestedTab === 'detail_type') {
            $allowedKeys = ['surgical_notes'];
        } elseif ($requestedTab === 'detail_diagnosis') {
            $allowedKeys = ['diagnosis', 'post_op_notes', 'notes', 'treatment_plan', 'follow_up_date', 'prescribed_medications'];
        } elseif ($requestedTab === 'detail_anesthesia') {
            $allowedKeys = ['anesthesia_type', 'anesthesia_position', 'asa_classification', 'anesthesia_start_time', 'anesthesia_end_time', 'anesthesia_notes'];
        } elseif ($requestedTab === 'detail_team_timing') {
            $allowedKeys = ['location_id', 'anesthesiologist_id', 'anesthesiologist_2_id', 'surgical_assistant_name', 'start_time', 'end_time', 'estimated_duration', 'estimated_duration_minutes'];
        } elseif ($requestedTab === 'detail_supplies') {
            $allowedKeys = ['supplies'];
        } else {
            // Unscoped PATCH: dynamically intersect with user's writable detail tabs
            $allowedKeys = [];
            if ($canWriteType) {
                $allowedKeys[] = 'surgical_notes';
            }
            if ($canWriteDiagnosis) {
                $allowedKeys = array_merge($allowedKeys, [
                    'diagnosis', 'post_op_notes', 'notes', 'treatment_plan', 'follow_up_date',
                    'surgery_category', 'surgery_type_detail', 'surgical_complexity', 'surgical_notes', 'prescribed_medications', 'required_fluids',
                ]);
            }
            if ($canWriteAnesthesia) {
                $allowedKeys = array_merge($allowedKeys, [
                    'anesthesia_type', 'anesthesia_position', 'asa_classification',
                    'anesthesia_start_time', 'anesthesia_end_time', 'anesthesia_notes',
                ]);
            }
            if ($canWriteTeamTiming) {
                $allowedKeys = array_merge($allowedKeys, [
                    'location_id', 'anesthesiologist_id', 'anesthesiologist_2_id', 'surgical_assistant_name',
                    'start_time', 'end_time', 'estimated_duration', 'estimated_duration_minutes',
                ]);
            }
            if ($canWriteSupplies) {
                $allowedKeys[] = 'supplies';
            }
            $allowedKeys = array_values(array_unique($allowedKeys));

            // If non-admin submitted payload containing no writable fields they own (e.g. anesthesiologist posting diagnosis), deny
            $submittedFieldKeys = array_keys($request->except(['_token', '_method', 'tab']));
            if (! $isAdmin && ! empty($submittedFieldKeys) && empty(array_intersect($submittedFieldKeys, $allowedKeys))) {
                abort(403, 'غير مصرح لك بتحديث هذه الحقول.');
            }
        }

        // Validate and convert surgical timing only when team/timing fields are submitted & permitted
        if (in_array('start_time', $allowedKeys, true) && $request->has('start_time')) {
            if (! empty($validated['start_time']) && ! preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $validated['start_time'])) {
                return redirect()->back()->withErrors(['start_time' => 'وقت البدء غير صحيح']);
            }
            $validated['start_time'] = ! empty($validated['start_time'])
                ? $surgery->scheduled_date->format('Y-m-d').' '.$validated['start_time'].':00'
                : null;
        }
        if (in_array('end_time', $allowedKeys, true) && $request->has('end_time')) {
            if (! empty($validated['end_time']) && ! preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $validated['end_time'])) {
                return redirect()->back()->withErrors(['end_time' => 'وقت الانتهاء غير صحيح']);
            }
            $validated['end_time'] = ! empty($validated['end_time'])
                ? $surgery->scheduled_date->format('Y-m-d').' '.$validated['end_time'].':00'
                : null;
        }

        if (in_array('follow_up_date', $allowedKeys, true) && $request->has('follow_up_date')) {
            $validated['follow_up_date'] = ! empty($validated['follow_up_date'])
                ? \Carbon\Carbon::parse($validated['follow_up_date'])
                : null;
        }

        $validated = Arr::only($validated, $allowedKeys);

        // Handle estimated duration when team_timing is writable
        if (in_array('estimated_duration', $allowedKeys, true) || in_array('estimated_duration_minutes', $allowedKeys, true)) {
            if (isset($validated['estimated_duration_minutes'])) {
                $validated['estimated_duration'] = $validated['estimated_duration_minutes'];
                unset($validated['estimated_duration_minutes']);
            } elseif (isset($validated['estimated_duration']) && ! empty($validated['estimated_duration'])) {
                if (is_numeric($validated['estimated_duration'])) {
                    $validated['estimated_duration'] = (int) $validated['estimated_duration'];
                } elseif (strpos($validated['estimated_duration'], ':') !== false) {
                    [$hours, $minutes] = explode(':', $validated['estimated_duration']);
                    $validated['estimated_duration'] = ($hours * 60) + $minutes;
                }
            } else {
                unset($validated['estimated_duration']);
            }
        }

        // Save surgery treatments only when diagnosis/treatments tab is writable & submitted
        $prescribedMedications = $request->input('prescribed_medications');
        if (in_array('prescribed_medications', $allowedKeys, true) && $prescribedMedications && isset($prescribedMedications['surgery_treatments'][$surgery->id])) {
            $surgery->surgeryTreatments()
                ->where('item_type', SurgeryTreatment::TYPE_MEDICATION)
                ->whereNull('batch_id')
                ->delete();

            $treatments = $prescribedMedications['surgery_treatments'][$surgery->id];
            foreach ($treatments as $index => $treatment) {
                if (! empty($treatment['description'])) {
                    SurgeryTreatment::create([
                        'surgery_id' => $surgery->id,
                        'item_type' => SurgeryTreatment::TYPE_MEDICATION,
                        'description' => $treatment['description'],
                        'dosage' => $treatment['dosage'] ?? null,
                        'timing' => $treatment['timing'] ?? null,
                        'duration_value' => $treatment['duration_value'] ?? null,
                        'duration_unit' => $treatment['duration_unit'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            }
        }
        unset($validated['prescribed_medications']);

        if (in_array('required_fluids', $allowedKeys, true) && $request->has('required_fluids')) {
            $station = $surgery->surgeonStation;
            if (! $station) {
                $station = $surgery->surgeonStation()->create(['status' => 'pending', 'surgeon_id' => $surgery->doctor_id]);
            }
            $station->update(['required_fluids' => $request->input('required_fluids', [])]);
        }
        unset($validated['required_fluids']);

        // Extract AnesthesiaStation-specific fields before updating Surgery
        $anesthesiaStartTime = $validated['anesthesia_start_time'] ?? null;
        $anesthesiaEndTime = $validated['anesthesia_end_time'] ?? null;
        $anesthesiaNotes = $validated['anesthesia_notes'] ?? null;
        $hasAnesthesiaStart = array_key_exists('anesthesia_start_time', $validated);
        $hasAnesthesiaEnd = array_key_exists('anesthesia_end_time', $validated);
        $hasAnesthesiaNotes = array_key_exists('anesthesia_notes', $validated);
        unset($validated['anesthesia_start_time'], $validated['anesthesia_end_time'], $validated['anesthesia_notes']);

        try {
            if (! empty($validated)) {
                $surgery->update($validated);
            }

            // Sync / create AnesthesiaStation when saving anesthesia details or updating team/anesthesiologist
            $stationSync = [];
            foreach (['anesthesiologist_id', 'anesthesiologist_2_id', 'surgical_assistant_name', 'anesthesia_type'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $stationSync[$field] = $validated[$field];
                }
            }
            if ($hasAnesthesiaStart) {
                $stationSync['start_time'] = $anesthesiaStartTime;
            }
            if ($hasAnesthesiaEnd) {
                $stationSync['end_time'] = $anesthesiaEndTime;
            }
            if ($hasAnesthesiaNotes) {
                $stationSync['notes'] = $anesthesiaNotes;
            }

            if (! empty($stationSync)) {
                if ($surgery->anesthesiaStation) {
                    $surgery->anesthesiaStation->update($stationSync);
                } else {
                    $initialStatus = ($requestedTab === 'detail_anesthesia' || $hasAnesthesiaNotes || $hasAnesthesiaStart || $hasAnesthesiaEnd)
                        ? 'in_progress'
                        : 'pending';

                    $surgery->anesthesiaStation()->create(array_merge([
                        'anesthesiologist_id' => $surgery->anesthesiologist_id,
                        'anesthesiologist_2_id' => $surgery->anesthesiologist_2_id,
                        'anesthesia_type' => $surgery->anesthesia_type,
                        'status' => $initialStatus,
                        'started_at' => now(),
                    ], $stationSync));
                }
            }

            // Auto-complete surgeon station only when surgeon/admin updates diagnosis tab (or legacy unscoped)
            if (($requestedTab === 'detail_diagnosis' || ! $requestedTab) && ($isAdmin || $user->can('view surgeon station')) && ! $isAnesthesiaUser) {
                if ($surgery->surgeonStation && $surgery->surgeonStation->status !== 'completed') {
                    $surgery->surgeonStation->markAsCompleted();

                    if (! $surgery->anesthesiaStation) {
                        $surgery->anesthesiaStation()->create([
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error updating surgery: '.$e->getMessage());

            return redirect()->back()->with('error', 'حدث خطأ في حفظ البيانات: '.$e->getMessage());
        }

        $redirectParams = ['surgery' => $surgery->id];
        if ($requestedTab) {
            $redirectParams['tab'] = $requestedTab;
        }

        $successMessage = match ($requestedTab) {
            'detail_type' => 'تم حفظ ملاحظات نوع العملية بنجاح',
            'detail_anesthesia' => 'تم حفظ تفاصيل التخدير بنجاح',
            'detail_diagnosis' => 'تم حفظ التشخيص وخطة العلاج بنجاح',
            'detail_team_timing' => 'تم حفظ بيانات الفريق الطبي والتوقيت بنجاح',
            'detail_supplies' => 'تم حفظ المستلزمات الطبية بنجاح',
            default => 'تم حفظ تفاصيل العملية بنجاح وإرسالها لمحطة التخدير',
        };

        return redirect()->route('surgeries.show', $redirectParams)->with('success', $successMessage);
    }

    public function updateSurgeryType(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $canWriteType = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_type') === SurgeryTabPermission::ACCESS_WRITE;

        if (! $isAdmin && ! $canWriteType) {
            return $request->expectsJson()
                ? response()->json(['message' => 'غير مصرح لك بتعديل نوع العملية'], 403)
                : abort(403, 'غير مصرح لك بتغيير نوع العملية');
        }

        // تحويل القيم الفارغة إلى null لتمرير validate بشكل صحيح
        if ($request->has('surgical_operation_id') && $request->surgical_operation_id === '') {
            $request->merge(['surgical_operation_id' => null]);
        }

        $validated = $request->validate([
            'surgery_type' => 'required|string|max:255',
            'surgical_operation_id' => 'nullable|integer|exists:surgical_operations,id',
        ]);

        $oldType = $surgery->surgery_type;

        if ($validated['surgery_type'] !== $oldType) {
            $opId = $validated['surgical_operation_id'];
            if (! $opId && $validated['surgery_type'] !== 'other') {
                $op = \App\Models\SurgicalOperation::where('name', $validated['surgery_type'])->first();
                $opId = $op?->id;
            }
            $surgery->previous_surgery_type = $oldType;
            $surgery->surgery_type = $validated['surgery_type'];
            $surgery->surgical_operation_id = $opId ?: null;
            $surgery->billing_status = 'pending_review';
            $surgery->save();

            $surgery->surgeryTypeChanges()->create([
                'old_type' => $oldType,
                'new_type' => $validated['surgery_type'],
                'changed_by' => $user->id,
            ]);

            $msg = 'تم تغيير نوع العملية من "'.$oldType.'" إلى "'.$validated['surgery_type'].'"';

            return $request->expectsJson()
                ? response()->json(['success' => $msg, 'type' => $validated['surgery_type']])
                : redirect()->back()->with('success', $msg);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'لم يتم تغيير نوع العملية'])
            : redirect()->back()->with('info', 'لم يتم تغيير نوع العملية');
    }

    public function addOperation(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $canWriteType = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_type') === SurgeryTabPermission::ACCESS_WRITE;
        if (! $isAdmin && ! $canWriteType) {
            return $request->expectsJson()
                ? response()->json(['message' => 'غير مصرح لك بإضافة عمليات إضافية'], 403)
                : abort(403, 'غير مصرح لك بإضافة عمليات إضافية');
        }

        $validated = $request->validate([
            'surgical_operation_ids' => 'required|array',
            'surgical_operation_ids.*' => 'integer|exists:surgical_operations,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $ops = \App\Models\SurgicalOperation::whereIn('id', $validated['surgical_operation_ids'])->get();

        foreach ($ops as $op) {
            $surgery->additionalOperations()->create([
                'surgical_operation_id' => $op->id,
                'notes' => $validated['notes'] ?? null,
                'fee' => $op->fee,
                'added_by' => $user?->id,
            ]);
        }

        $surgery->billing_status = 'pending_review';
        $surgery->save();

        $names = $ops->pluck('name')->join('، ');
        $msg = 'تم إضافة '.$ops->count().' عملية: '.$names;

        return $request->expectsJson()
            ? response()->json(['success' => $msg])
            : redirect()->back()->with('success', $msg);
    }

    public function removeOperation(Surgery $surgery, \App\Models\SurgeryAdditionalOperation $additionalOp)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $canWriteType = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_type') === SurgeryTabPermission::ACCESS_WRITE;
        if (! $isAdmin && ! $canWriteType) {
            abort(403, 'غير مصرح لك بحذف العملية الإضافية');
        }
        $additionalOp->delete();

        $surgery->billing_status = 'pending_review';
        $surgery->save();

        return redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_type'])
            ->with('success', 'تم حذف العملية الإضافية');
    }

    public function addDevice(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $canWriteSupplies = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_supplies') === SurgeryTabPermission::ACCESS_WRITE;
        if (! $isAdmin && ! $canWriteSupplies) {
            return $request->expectsJson()
                ? response()->json(['message' => 'غير مصرح لك بإضافة أجهزة طبية'], 403)
                : abort(403, 'غير مصرح لك بإضافة أجهزة طبية');
        }

        $validated = $request->validate([
            'device_ids' => 'required|array',
            'device_ids.*' => 'integer|exists:medical_devices,id',
        ]);

        $existingDeviceIds = $surgery->medicalDevices()->pluck('medical_devices.id')->toArray();
        $newDeviceIds = array_diff($validated['device_ids'], $existingDeviceIds);

        if (! empty($newDeviceIds)) {
            $syncData = [];
            foreach ($newDeviceIds as $deviceId) {
                $syncData[$deviceId] = ['assigned_by' => $user->id];
            }
            $surgery->medicalDevices()->attach($syncData);

            $surgery->billing_status = 'pending_review';
            $surgery->save();
        }

        $devices = \App\Models\MedicalDevice::whereIn('id', $validated['device_ids'])->get();
        $names = $devices->pluck('name')->join('، ');
        $msg = 'تم إضافة أجهزة طبية: '.$names;

        return $request->expectsJson()
            ? response()->json(['success' => $msg])
            : redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_supplies'])->with('success', $msg);
    }

    public function removeDevice(Surgery $surgery, \App\Models\MedicalDevice $device)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        $canWriteSupplies = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_supplies') === SurgeryTabPermission::ACCESS_WRITE;
        if (! $isAdmin && ! $canWriteSupplies) {
            abort(403, 'غير مصرح لك بإزالة أجهزة طبية');
        }

        $surgery->medicalDevices()->detach($device->id);

        $surgery->billing_status = 'pending_review';
        $surgery->save();

        return redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_supplies'])
            ->with('success', 'تم إزالة الجهاز الطبي من العملية بنجاح');
    }

    /**
     * صرف مادة أو مستلزم مباشرة للمريض من عهدة مخزن العمليات (تبويب detail_supplies)
     */
    public function dispenseOperationItem(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $canWriteSupplies = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_supplies') === SurgeryTabPermission::ACCESS_WRITE;

        if (! $isAdmin && ! $canWriteSupplies) {
            return $request->expectsJson()
                ? response()->json(['message' => 'غير مصرح لك بصرف مواد مخزن العمليات'], 403)
                : abort(403, 'غير مصرح لك بصرف مواد مخزن العمليات');
        }

        $validated = $request->validate([
            'batch_id' => 'required|exists:stock_batches,id',
            'quantity' => 'required|integer|min:1',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $opLocation = $surgery->getOperationSubStoreLocation($user);
        if (! $opLocation) {
            return redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_supplies'])
                ->with('error', 'لم يتم العثور على مخزن العمليات الفرعي (مخزن عمليات).');
        }

        try {
            DB::transaction(function () use ($validated, $surgery, $opLocation, $user) {
                $batch = StockBatch::with('product')
                    ->where('id', $validated['batch_id'])
                    ->where('location_id', $opLocation->id)
                    ->lockForUpdate()
                    ->first();

                if (! $batch) {
                    throw new \Exception("الوجبة المحددة لا تنتمي إلى عهدة ({$opLocation->name}).");
                }

                if ($batch->current_qty < $validated['quantity']) {
                    throw new \Exception("الكمية المطلوبة ({$validated['quantity']}) تتجاوز الرصيد المتوفر في مخزن العمليات ({$batch->current_qty}).");
                }

                $batch->decrement('current_qty', $validated['quantity']);

                $patientName = $surgery->patient?->user?->name ?? $surgery->patient?->name ?? 'مريض';
                $movementNotes = "صرف من ({$opLocation->name}) لعملية #{$surgery->id} - المريض: {$patientName}";
                if (! empty($validated['admin_notes'])) {
                    $movementNotes .= ' - '.$validated['admin_notes'];
                }

                $movement = StockMovement::create([
                    'batch_id' => $batch->id,
                    'from_location_id' => $opLocation->id,
                    'to_location_id' => null,
                    'quantity' => $validated['quantity'],
                    'type' => 'issue_to_patient',
                    'user_id' => $user->id,
                    'surgery_id' => $surgery->id,
                    'notes' => $movementNotes,
                ]);

                $product = $batch->product;
                $unitName = $product?->unit ?? 'قطعة';
                $dosageText = "{$validated['quantity']} {$unitName}";

                SurgeryTreatment::create([
                    'surgery_id' => $surgery->id,
                    'item_type' => SurgeryTreatment::TYPE_SUPPLY,
                    'description' => $product?->name ?? 'مادة مخزنية',
                    'dosage' => $dosageText,
                    'timing' => 'صرف من مخزن العمليات',
                    'status' => 'administered',
                    'administered_by' => $user->id,
                    'administered_at' => now(),
                    'admin_notes' => $validated['admin_notes'] ?? null,
                    'batch_id' => $batch->id,
                    'stock_movement_id' => $movement->id,
                    'quantity' => $validated['quantity'],
                    'administrations' => [
                        [
                            'administered_at' => now()->format('Y-m-d H:i'),
                            'administered_by' => $user->id,
                            'administered_by_name' => $user->full_name ?? $user->name ?? 'موظف العمليات',
                            'notes' => $validated['admin_notes'] ?? null,
                        ],
                    ],
                ]);
            });

            return redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_supplies'])
                ->with('success', "تم صرف المادة بنجاح من ({$opLocation->name}) وخصمها من الرصيد وتوثيقها في ملف العملية.");
        } catch (\Exception $e) {
            return redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_supplies'])
                ->with('error', $e->getMessage());
        }
    }

    /**
     * إلغاء / إرجاع مادة مصروفة من مخزن العمليات وإعادة رصيدها للوجبة
     */
    public function reverseOperationItem(Request $request, Surgery $surgery, SurgeryTreatment $treatment)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $canWriteSupplies = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_supplies') === SurgeryTabPermission::ACCESS_WRITE;

        if (! $isAdmin && ! $canWriteSupplies) {
            return $request->expectsJson()
                ? response()->json(['message' => 'غير مصرح لك بإرجاع مواد مخزن العمليات'], 403)
                : abort(403, 'غير مصرح لك بإرجاع مواد مخزن العمليات');
        }

        if ((int) $treatment->surgery_id !== (int) $surgery->id) {
            abort(404);
        }

        $treatment->load('stockMovement');

        DB::transaction(function () use ($treatment, $surgery, $user) {
            if ($treatment->batch_id && $treatment->quantity) {
                $batch = StockBatch::where('id', $treatment->batch_id)->lockForUpdate()->first();
                $restoreLocationId = $batch?->location_id
                    ?? $treatment->stockMovement?->from_location_id
                    ?? $surgery->getOperationSubStoreLocation($user)?->id;

                if ($batch) {
                    $batch->increment('current_qty', $treatment->quantity);

                    if ($restoreLocationId) {
                        StockMovement::create([
                            'batch_id' => $batch->id,
                            'from_location_id' => $restoreLocationId,
                            'to_location_id' => $restoreLocationId,
                            'quantity' => $treatment->quantity,
                            'type' => 'return',
                            'user_id' => $user->id,
                            'surgery_id' => $surgery->id,
                            'notes' => "إرجاع لمخزن العمليات وإلغاء صرف عملية #{$surgery->id}: {$treatment->description}",
                        ]);
                    }
                }
            }

            $treatment->delete();
        });

        return redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_supplies'])
            ->with('success', 'تم إلغاء الصرف وإعادة الكمية إلى رصيد مخزن العمليات بنجاح.');
    }

    /**
     * حذف حجز العملية الجراحية نهائياً وتحرير الغرفة (مع الحماية المالية للكاشير)
     */
    public function destroy(Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || ! $user->can('delete surgeries'))) {
            abort(403, 'غير مصرح لك بحذف العملية');
        }

        // الحماية المالية: إذا كان للعملية مبالغ مدفوعة، يتم إلغاؤها وتحرير الغرفة ونقل المبالغ للكاشير كاسترجاع
        $totalPaid = ($surgery->surgery_fee_paid_amount ?? 0) + ($surgery->room_fee_paid_amount ?? 0);
        $hasPaidRecord = $totalPaid > 0 || $surgery->payment_status === 'paid' || $surgery->payments()->where('amount', '>', 0)->exists();

        if ($hasPaidRecord) {
            DB::transaction(function () use ($surgery) {
                $surgery->status = 'cancelled';
                $surgery->cancellation_reason = 'تم إلغاء الحجز بناءً على طلب المستخدم وتحويل المبالغ للاسترجاع المالي';
                $surgery->payment_status = 'cancelled';
                $surgery->surgery_fee_paid = 'cancelled';
                $surgery->save();

                // تحرير الغرفة المرتبطة إن وجدت
                if ($surgery->room_id) {
                    Room::where('id', $surgery->room_id)->update(['status' => 'available']);
                }

                // إلغاء الفحوصات وإيقافها مع الحفاظ على سجل المدفوع منها
                $surgery->labTests()->where('payment_status', '!=', 'paid')->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled',
                ]);
                $surgery->labTests()->where('payment_status', 'paid')->update([
                    'status' => 'cancelled',
                ]);

                $surgery->radiologyTests()->where('payment_status', '!=', 'paid')->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled',
                ]);
                $surgery->radiologyTests()->where('payment_status', 'paid')->update([
                    'status' => 'cancelled',
                ]);
            });

            $surgery->refresh();
            broadcast(new SurgeryUpdated($surgery));

            return redirect()->route('surgeries.index')
                ->with('warning', 'تم إلغاء حجز العملية ('.$surgery->surgery_type.') وتحرير الغرفة بنجاح. نظراً لوجود مبالغ مدفوعة مسبقاً ('.number_format($totalPaid, 0).' د.ع)، تم توجيه العملية إلى الكاشير كـ (مستحق استرجاع Refund) لتسليم المبلغ للمريض.');
        }

        try {
            DB::beginTransaction();

            // تحرير الغرفة المرتبطة إن وجدت
            if ($surgery->room_id) {
                Room::where('id', $surgery->room_id)->update(['status' => 'available']);
            }

            // تنظيف المحطات المرتبطة
            if ($surgery->operationTheaterStation) {
                $surgery->operationTheaterStation()->delete();
            }
            if ($surgery->surgeonStation) {
                $surgery->surgeonStation()->delete();
            }
            if ($surgery->anesthesiaStation) {
                $surgery->anesthesiaStation()->delete();
            }
            if ($surgery->nursingStation) {
                $surgery->nursingStation()->delete();
            }

            // فك ارتباط وحذف الجداول التابعة
            $surgery->medicalDevices()->detach();
            $surgery->additionalOperations()->delete();
            $surgery->surgeryTypeChanges()->delete();
            $surgery->surgeryTreatments()->delete();
            $surgery->labTests()->delete();
            $surgery->radiologyTests()->delete();

            // حذف الزيارة التابعة للعملية إن وجدت
            $visit = $surgery->visit;
            if ($visit) {
                $visit->requests()->delete();
                $visit->delete();
            }

            $surgeryName = $surgery->surgery_type;
            $surgery->delete();

            DB::commit();

            return redirect()->route('surgeries.index')
                ->with('success', 'تم حذف حجز العملية ('.$surgeryName.') بنجاح وتحرير الغرفة');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف العملية: '.$e->getMessage());
        }
    }
}
