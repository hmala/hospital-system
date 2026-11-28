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

        // ضمان عدم وجود أي ازدواجية في حجز الغرف للحالات النشطة وفك أي تعارض قديم تلقائياً
        $conflictingRoomIds = Surgery::whereNotNull('room_id')
            ->whereNull('discharged_at')
            ->whereNotIn('status', ['cancelled'])
            ->select('room_id')
            ->groupBy('room_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('room_id');

        if ($conflictingRoomIds->isNotEmpty()) {
            foreach ($conflictingRoomIds as $cRoomId) {
                $surgeriesInConflict = Surgery::where('room_id', $cRoomId)
                    ->whereNull('discharged_at')
                    ->whereNotIn('status', ['cancelled'])
                    ->orderBy('id', 'asc')
                    ->get();

                if ($surgeriesInConflict->count() > 1) {
                    $primarySurgery = $surgeriesInConflict->first();
                    $conflictingSurgeries = $surgeriesInConflict->slice(1);
                    foreach ($conflictingSurgeries as $conflictingSurgery) {
                        $conflictingSurgery->update([
                            'room_id' => null,
                            'room_fee' => 0,
                            'room_checkin_at' => null,
                        ]);

                        \App\Services\UserActivityLogger::log(
                            action: 'surgery_room_conflict_resolved',
                            category: 'surgery',
                            description: "فك ازدواجية حجز الغرفة: تحويل العملية #{$conflictingSurgery->id} للمريض {$conflictingSurgery->patient?->name} إلى وضع انتظار الغرفة لوجود مريض آخر مسبقاً بالغرفة (#{$primarySurgery->id})",
                            subject: $conflictingSurgery,
                            isSensitive: false
                        );
                    }
                }
            }
        }

        // العمليات النشطة (المجدولة والمنتظرة والجارية فقط - استبعاد ما اكتملت صالته أو تم تخريجه رسمياً من المستشفى)
        $activeSurgeriesQuery = Surgery::with(['patient.user', 'doctor.user', 'department', 'room', 'operationTheaterStation'])
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->whereNull('discharged_at')
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

        // العمليات المكتملة والملغاة (بما فيها التي اكتملت صالة عملياتها أو تم تخريج مرضاها)
        $completedSurgeriesQuery = Surgery::with(['patient.user', 'doctor.user', 'department', 'room', 'operationTheaterStation'])
            ->where(function ($q) {
                $q->whereIn('status', ['completed', 'cancelled'])
                    ->orWhereNotNull('discharged_at')
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
                SUM(CASE WHEN status IN ('scheduled', 'waiting', 'in_progress') AND discharged_at IS NULL AND NOT EXISTS (SELECT 1 FROM operation_theater_stations WHERE operation_theater_stations.surgery_id = surgeries.id AND operation_theater_stations.status = 'completed') THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'scheduled' AND discharged_at IS NULL AND NOT EXISTS (SELECT 1 FROM operation_theater_stations WHERE operation_theater_stations.surgery_id = surgeries.id AND operation_theater_stations.status = 'completed') THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN status = 'waiting' AND discharged_at IS NULL AND NOT EXISTS (SELECT 1 FROM operation_theater_stations WHERE operation_theater_stations.surgery_id = surgeries.id AND operation_theater_stations.status = 'completed') THEN 1 ELSE 0 END) as waiting,
                SUM(CASE WHEN status = 'in_progress' AND discharged_at IS NULL AND NOT EXISTS (SELECT 1 FROM operation_theater_stations WHERE operation_theater_stations.surgery_id = surgeries.id AND operation_theater_stations.status = 'completed') THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN (status = 'completed' OR discharged_at IS NOT NULL OR EXISTS (SELECT 1 FROM operation_theater_stations WHERE operation_theater_stations.surgery_id = surgeries.id AND operation_theater_stations.status = 'completed')) AND status != 'cancelled' THEN 1 ELSE 0 END) as completed,
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

        // تحسين أداء حرج: عدم تحميل كامل قاعدة بيانات المرضى (10,000+ مريض) في الذاكرة
        // إذا كان المريض محدداً مسبقاً (عبر الاستعلامات أو النموذج) نجلبه حصراً، وإلا نمرر أحدث 25 مريضاً فقط مع دعم البحث اللحظي
        $selectedPatient = null;
        $selectedPatientId = old('patient_id', request('patient_id'));
        if ($selectedPatientId) {
            $selectedPatient = Patient::with(['user', 'healthInsuranceCategory'])->find($selectedPatientId);
        }

        if ($selectedPatient) {
            $patients = collect([$selectedPatient]);
        } else {
            $patients = Patient::with(['user', 'healthInsuranceCategory'])->latest('id')->take(25)->get();
        }

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
        $rooms = Room::where('is_active', true)->where('room_purpose', 'beds')->where('status', 'available')->orderBy('room_type')->orderBy('room_number')->get();
        $locations = \App\Models\Location::orderBy('name')->get();

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
            'room_id' => 'nullable|exists:rooms,id',
            'room_waiting_mode' => 'nullable|boolean',
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

        if ($request->filled('insurance_card_no')) {
            $surgeryData['insurance_card_no'] = $request->insurance_card_no;
        } elseif ($patient && ($patient->insurance_card_no || $patient->insurance_booklet_number)) {
            $surgeryData['insurance_card_no'] = $patient->insurance_card_no ?: $patient->insurance_booklet_number;
        }

        // استخراج اسم العملية من الجدول
        $operation = SurgicalOperation::find($request->surgical_operation_id);
        if ($operation) {
            $surgeryData['surgery_type'] = $operation->name;

            // استخدام السعر المخصص دائماً (يدوياً في كلتا الحالتين)
            $enteredFee = $request->filled('custom_surgery_fee')
                ? (float) $request->custom_surgery_fee
                : (float) ($operation->fee ?? 0);

            $surgeryData['surgery_fee'] = $enteredFee;
            $surgeryData['original_surgery_fee'] = $enteredFee;
        }

        if ($bookingInsuranceType === 'moi') {
            $surgeryData['copay_percentage'] = $request->has('copay_percentage') && $request->copay_percentage !== null && $request->copay_percentage !== ''
                ? (float) $request->copay_percentage
                : (float) ($patient->copay_percentage ?? 15.0);
        } else {
            $surgeryData['copay_percentage'] = $request->filled('copay_percentage') ? (float) $request->copay_percentage : null;
        }

        $scheduledTimeStr = $request->filled('scheduled_time') ? $request->scheduled_time : '00:00';
        $surgeryData['scheduled_time'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $request->scheduled_date.' '.$scheduledTimeStr);

        // حساب أجرة الغرفة المبدئية عند الحجز أو إبقائها بوضع الانتظار إذا لم تُخصص غرفة الآن
        if ($request->filled('room_id')) {
            $room = Room::find($request->room_id);
            if ($room) {
                // التحقق الصارم: منع حجز غرفة مشغولة بمريض آخر
                $isOccupiedByActiveSurgery = Surgery::where('room_id', $room->id)
                    ->whereNull('discharged_at')
                    ->whereNotIn('status', ['cancelled'])
                    ->exists();

                if ($room->status !== 'available' || $isOccupiedByActiveSurgery) {
                    return back()->withInput()->with('error', "الغرفة ({$room->room_number}) مشغولة حالياً بمريض آخر ولا يمكن حجزها إلا بعد تخريجه وإخلاء السرير.");
                }

                $surgeryData['room_fee'] = Room::getInitialFeeForType($room->room_type);
                // تحديث حالة الغرفة لمحجوزة وتسجيل الدخول تلقائياً
                $room->update(['status' => 'occupied']);
                $surgeryData['room_checkin_at'] = now();
                $surgeryData['room_checkin_by'] = Auth::id();
            }
        } else {
            $surgeryData['room_id'] = null;
            $surgeryData['room_fee'] = 0;
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
        $surgery->syncPaymentStatus();

        broadcast(new SurgeryUpdated($surgery));

        if ($request->boolean('room_waiting_mode')) {
            return redirect()->route('inquiry.create')->with('success', 'تم حجز العملية بنجاح');
        }

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

        // منع الأطباء الاستشاريين من استعراض وتعديل العمليات إلا إذا كان لديهم استشارة مطلوبة لهذه العملية
        $isConsultantForSurgery = $user && $user->doctor && $surgery->consultations()->where('doctor_id', $user->doctor->id)->exists();
        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant' && ! $isConsultantForSurgery) {
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
            'consultations.doctor.user',
            'consultations.requestedBy',
            'consultations.respondedBy',
        ]);
        $patients = $surgery->patient ? collect([$surgery->patient]) : collect();
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
        // قصر قائمة الصالات على صالات العمليات الجراحية فقط واستبعاد كافة الأقسام والمخازن الأخرى
        $locations = \App\Models\Location::where(function ($q) {
            $q->where('name', 'like', '%عمليات%')
                ->orWhere('name', 'like', '%صالة%')
                ->orWhere('name', 'like', '%operation%')
                ->orWhere('name', 'like', '%theater%');
        })
            ->where(function ($q) {
                $q->where('name', 'not like', '%مخزن%')
                    ->where('name', 'not like', '%store%');
            })
            ->when($surgery->location_id, fn ($q) => $q->orWhere('id', $surgery->location_id))
            ->orderBy('name')
            ->get();

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

        // تحديد مخزن وعُهدة العمليات التابع للعملية تلقائياً للسحب المباشر
        $operationStoreLocation = $surgery->getOperationSubStoreLocation($user)
            ?: Location::where('name', 'like', '%عمليات%')->where('type', 'sub')->first()
            ?: Location::where('type', 'main')->first();

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

        $surgeons = Doctor::with('user')
            ->where('is_active', true)
            ->get()
            ->sortBy(function ($d) {
                return optional($d->user)->name ?? '';
            });

        $surgicalAssistants = SurgicalAssistant::active()->surgical()->orderBy('name')->get();
        $anesthesiaAssistants = SurgicalAssistant::active()->anesthesia()->orderBy('name')->get();

        return view('surgeries.show', compact(
            'surgery',
            'patients',
            'anesthesiaDoctors',
            'surgeons',
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
            'surgicalAssistants',
            'anesthesiaAssistants'
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
        $patients = $surgery->patient ? collect([$surgery->patient]) : collect();
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
        // إحضار الغرف المتاحة فقط مع الغرفة المحجوزة حالياً لنفس العملية
        $rooms = Room::where('room_purpose', 'beds')
            ->where('is_active', true)
            ->where(function ($query) use ($surgery) {
                $query->where('status', 'available');
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

        $surgery->load([
            'patient.user',
            'doctor.user',
            'department.hospital',
            'visit',
            'labTests.labTest',
            'radiologyTests.radiologyType',
            'anesthesiologist.user',
            'anesthesiologist2.user',
            'anesthesiaStation.anesthesiologist.user',
            'anesthesiaStation.anesthesiologist2.user',
            'surgeonStation.surgeon.user',
            'surgeonStation.residentAssigned.user',
            'residentStations.resident.user',
            'operationTheaterStation.orNurse',
            'additionalOperations.surgicalOperation',
            'surgeryTreatments.medicine',
            'medicalDevices',
            'consultations.doctor.user',
            'room',
            'location',
        ]);

        $hospital = $surgery->department?->hospital ?? \App\Models\Hospital::first();

        return view('surgeries.print', compact('surgery', 'hospital'));
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
            'room_id' => 'nullable|exists:rooms,id',
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
            $enteredFee = $request->filled('custom_surgery_fee')
                ? (float) $request->custom_surgery_fee
                : (float) ($operation->fee ?? 0);
            $surgeryData['surgery_fee'] = $enteredFee;
            $surgeryData['original_surgery_fee'] = $enteredFee;
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

        if ($request->filled('insurance_card_no')) {
            $surgeryData['insurance_card_no'] = $request->insurance_card_no;
        }
        $resolvedInsType = $surgeryData['insurance_type'] ?? $surgery->insurance_type;
        if ($resolvedInsType === 'moi') {
            if ($request->has('copay_percentage') && $request->copay_percentage !== null && $request->copay_percentage !== '') {
                $surgeryData['copay_percentage'] = (float) $request->copay_percentage;
            } elseif ($patient) {
                $surgeryData['copay_percentage'] = (float) ($patient->copay_percentage ?? 15.0);
            }
        } elseif ($request->filled('copay_percentage')) {
            $surgeryData['copay_percentage'] = (float) $request->copay_percentage;
        }

        // معالجة تغيير الغرفة وإعادة احتساب الأجرة الفندقية
        if ($surgery->room_id != $request->room_id) {
            if ($request->filled('room_id')) {
                $newRoom = Room::find($request->room_id);
                if ($newRoom) {
                    // التحقق الصارم: منع إسناد غرفة مشغولة بمريض آخر
                    $isOccupiedByAnother = Surgery::where('room_id', $newRoom->id)
                        ->where('id', '!=', $surgery->id)
                        ->whereNull('discharged_at')
                        ->whereNotIn('status', ['cancelled'])
                        ->exists();

                    if ($newRoom->status !== 'available' || $isOccupiedByAnother) {
                        return back()->withInput()->with('error', "الغرفة ({$newRoom->room_number}) مشغولة حالياً بمريض آخر ولا يمكن إسنادها.");
                    }

                    if ($surgery->room_id) {
                        Room::where('id', $surgery->room_id)->update(['status' => 'available']);
                    }

                    $newRoom->update(['status' => 'occupied']);
                    $surgeryData['room_fee'] = Room::getInitialFeeForType($newRoom->room_type);
                    if (! $surgery->room_checkin_at) {
                        $surgeryData['room_checkin_at'] = now();
                        $surgeryData['room_checkin_by'] = Auth::id();
                    }
                }
            } else {
                if ($surgery->room_id) {
                    Room::where('id', $surgery->room_id)->update(['status' => 'available']);
                }
                $surgeryData['room_id'] = null;
                $surgeryData['room_fee'] = 0;
            }
        } elseif ($request->filled('room_id') && $surgery->room) {
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
        $surgery->syncPaymentStatus();

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

        if (! $request->expectsJson() && ! $request->ajax()) {
            return redirect()->back()->with('success', 'تم تأجيل وتحديث موعد وصالة العملية الجراحية بنجاح');
        }

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
        $surgery->end_time = null;
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

        if ($surgery->status === 'cancelled') {
            return redirect()->back()->with('error', 'لا يمكن بدء عملية ملغاة.');
        }

        if ($surgery->discharged_at) {
            return redirect()->back()->with('error', 'لا يمكن بدء عملية لمريض تم تخريجه مسبقاً.');
        }

        if ($surgery->status === 'completed' || ($surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'completed')) {
            return redirect()->back()->with('error', 'تم إنهاء هذه العملية وخروج المريض من صالة العمليات مسبقاً ولا يمكن بدؤها أو استئنافها من جديد.');
        }

        if ($surgery->status === 'in_progress') {
            return redirect()->back()->with('info', 'العملية قيد التنفيذ حالياً داخل صالة العمليات.');
        }

        $surgery->status = 'in_progress';
        $surgery->started_at = $surgery->started_at ?? now();
        $surgery->end_time = null;
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

        if ($surgery->status === 'cancelled') {
            return redirect()->back()->with('error', 'لا يمكن إنهاء عملية ملغاة.');
        }

        if ($surgery->status === 'completed' || ($surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'completed')) {
            return redirect()->back()->with('info', 'هذه العملية مكتملة ومنتهية مسبقاً.');
        }

        if ($surgery->status !== 'in_progress') {
            return redirect()->back()->with('error', 'لا يمكن إنهاء العملية قبل تسجيل بدئها ودخول المريض لصالة العمليات.');
        }

        // إتمام محطة صالة العمليات
        $otStation = $surgery->operationTheaterStation;
        if (! $otStation) {
            $otStation = $surgery->operationTheaterStation()->create([
                'status' => 'pending',
            ]);
        }
        $otStation->markAsCompleted();

        // إغلاق محطة المقيم pre_op تلقائياً إذا بقيت معلقة حتى لا تفقد الحالة من الأرشيف لاحقاً
        $preOpStation = $surgery->residentStations()->where('phase', 'pre_op')->first();
        if (! $preOpStation) {
            $surgery->residentStations()->create([
                'phase' => 'pre_op',
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } elseif ($preOpStation->status !== 'completed') {
            $preOpStation->markAsCompleted();
        }

        // ضمان إنشاء محطة المقيم post_op للمتابعة بعد العملية
        $surgery->residentStations()->firstOrCreate(
            ['phase' => 'post_op'],
            ['status' => 'pending']
        );

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

        $clearance = $surgery->getFinancialClearanceSummary(now());
        if (! $clearance['cleared']) {
            return redirect()->back()->with(
                'error',
                'لا يمكن إخراج المريض قبل تسوية كافة المستحقات المالية في الكاشير: '.implode(' | ', $clearance['reasons'])
            );
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

    /**
     * تخصيص غرفة لمريض عملية (من وضع الانتظار أو تبديل الغرفة لاحقاً) حسب الصلاحية المخصصة
     */
    public function assignRoom(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        if (! $isAdmin && (! $user || ! $user->can('assign surgery room'))) {
            abort(403, 'غير مصرح لك بتخصيص الغرف لمرضى العمليات.');
        }

        if ($surgery->status === 'cancelled') {
            return redirect()->back()->with('error', 'لا يمكن تخصيص غرفة لعملية ملغاة.');
        }

        if ($surgery->discharged_at) {
            return redirect()->back()->with('error', 'تم تخريج المريض مسبقاً ولا يمكن تخصيص غرفة جديدة.');
        }

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'checkin_immediately' => 'nullable|boolean',
        ]);

        try {
            $assignedRoom = DB::transaction(function () use ($validated, $surgery, $user) {
                $newRoom = Room::where('id', $validated['room_id'])->lockForUpdate()->firstOrFail();

                if ($newRoom->status !== 'available' && (int) $surgery->room_id !== (int) $newRoom->id) {
                    throw new \RuntimeException("الغرفة ({$newRoom->room_number}) مشغولة أو غير متاحة حالياً.");
                }

                if ($surgery->room_id && (int) $surgery->room_id !== (int) $newRoom->id) {
                    Room::where('id', $surgery->room_id)->update(['status' => 'available']);
                }

                $newRoom->update(['status' => 'occupied']);

                $updates = [
                    'room_id' => $newRoom->id,
                    'room_fee' => Room::getInitialFeeForType($newRoom->room_type),
                    'room_checkin_at' => $surgery->room_checkin_at ?: now(),
                    'room_checkin_by' => $surgery->room_checkin_by ?: $user->id,
                ];

                $surgery->update($updates);
                $surgery->refresh();
                $surgery->syncPaymentStatus();

                return $newRoom;
            });

            broadcast(new SurgeryUpdated($surgery));

            \App\Services\UserActivityLogger::log(
                action: 'surgery_assign_room',
                category: 'surgery',
                description: "تخصيص الغرفة رقم {$assignedRoom->room_number} ({$assignedRoom->room_type_name}) للعملية #{$surgery->id}",
                subject: $surgery,
                isSensitive: false
            );

            return redirect()->back()->with('success', "تم تخصيص الغرفة ({$assignedRoom->room_number}) للمريض بنجاح".($assignedRoom->daily_fee > 0 ? ' وتم تحديث الكشف المالي بالكاشير.' : '.'));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('delete surgeries') && ! $user->can('cancel surgeries') && ! $user->can('control surgeries')))) {
            abort(403, 'غير مصرح لك بإلغاء العملية');
        }

        if ($surgery->status === 'completed' || ($surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'completed')) {
            return redirect()->back()->with('error', 'لا يمكن إلغاء عملية بعد إتمامها وإخراج المريض من صالة العمليات.');
        }

        if ($surgery->status === 'in_progress') {
            return redirect()->back()->with('error', 'لا يمكن إلغاء عملية جارية داخل صالة العمليات؛ يجب إخراج المريض لقائمة الانتظار أولاً.');
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

    public function reactivate(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('control surgeries') && ! $user->can('edit surgeries')))) {
            abort(403, 'غير مصرح لك بالتراجع عن إلغاء العملية');
        }

        if ($surgery->status !== 'cancelled') {
            return redirect()->back()->with('error', 'العملية ليست في حالة ملغاة لتتم استعادتها.');
        }

        $roomReassigned = false;
        $roomConflicted = false;
        $conflictingPatientName = null;
        $oldRoomNumber = null;

        DB::transaction(function () use ($surgery, $user, &$roomReassigned, &$roomConflicted, &$conflictingPatientName, &$oldRoomNumber) {
            // فحص حالة الغرفة السابقة
            if ($surgery->room_id) {
                $oldRoom = Room::find($surgery->room_id);
                $oldRoomNumber = $oldRoom?->room_number ?? $surgery->room_id;

                // هل الغرفة مشغولة بمريض رقود آخر حالياً؟
                $conflictSurgery = Surgery::where('room_id', $surgery->room_id)
                    ->where('id', '!=', $surgery->id)
                    ->whereNull('discharged_at')
                    ->whereNotIn('status', ['cancelled'])
                    ->with('patient.user')
                    ->first();

                if ($conflictSurgery || ($oldRoom && $oldRoom->status === 'occupied')) {
                    // الغرفة مشغولة بمريض آخر: تصفير الغرفة لتجنب التضارب
                    $roomConflicted = true;
                    $conflictingPatientName = $conflictSurgery?->patient?->user?->name ?? 'مريض آخر';
                    $surgery->room_id = null;
                    $surgery->room_fee = 0;
                    $surgery->room_fee_paid_amount = 0;
                    $surgery->room_checkin_at = null;
                    $surgery->room_checkin_by = null;
                } else {
                    // الغرفة لا تزال شاغرة: إعادة حجزها للمريض
                    $roomReassigned = true;
                    if ($oldRoom) {
                        $oldRoom->update(['status' => 'occupied']);
                    }
                    $surgery->room_checkin_at = $surgery->room_checkin_at ?: now();
                    $surgery->room_checkin_by = $surgery->room_checkin_by ?: $user->id;
                }
            }

            // إعادة الحالة إلى الانتظار وتفريغ سبب الإلغاء
            $surgery->status = 'waiting';
            $surgery->cancellation_reason = null;
            $surgery->save();

            // إعادة تفعيل الفحوصات الطبية المرتبطة بالعملية
            $surgery->labTests()->where('status', 'cancelled')->update([
                'status' => 'pending',
                'payment_status' => DB::raw("CASE WHEN payment_status = 'cancelled' THEN 'pending' ELSE payment_status END"),
            ]);

            $surgery->radiologyTests()->where('status', 'cancelled')->update([
                'status' => 'pending',
                'payment_status' => DB::raw("CASE WHEN payment_status = 'cancelled' THEN 'pending' ELSE payment_status END"),
            ]);

            // مزامنة حالة الدفع والكاشير
            $surgery->syncPaymentStatus();
            $surgery->save();
        });

        $surgery->refresh();
        broadcast(new SurgeryUpdated($surgery));

        \App\Services\UserActivityLogger::log(
            action: 'surgery_reactivate',
            category: 'surgery',
            description: "التراجع عن إلغاء العملية #{$surgery->id} وإعادة تفعيلها".($roomConflicted ? " (مع تفريغ الغرفة السابقة {$oldRoomNumber} لانشغالها بمريض آخر)" : ($roomReassigned ? " (مع إعادة تثبيت الغرفة {$oldRoomNumber})" : '')),
            subject: $surgery,
            isSensitive: false
        );

        if ($roomConflicted) {
            return redirect()->back()->with(
                'warning',
                "تم التراجع عن إلغاء العملية وتفعيلها بنجاح. تنبيه: الغرفة السابقة ({$oldRoomNumber}) مشغولة حالياً بمريض رقود آخر ({$conflictingPatientName})، لذلك تم وضع العملية بانتظار تخصيص غرفة شاغرة جديدة."
            );
        }

        $msg = 'تم التراجع عن إلغاء العملية وإعادتها لقائمة العمليات النشطة بنجاح.';
        if ($roomReassigned) {
            $msg .= " وتمت إعادة حجز الغرفة السابقة ({$oldRoomNumber}) للمريض لكونها شاغرة.";
        }

        return redirect()->back()->with('success', $msg);
    }

    public function returnToWaiting(Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('manage surgery waiting list') && ! $user->can('control surgeries')))) {
            abort(403, 'غير مصرح لك بإعادة العملية إلى الانتظار');
        }

        if ($surgery->status === 'completed' || ($surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'completed')) {
            return redirect()->back()->with('error', 'لا يمكن إرجاع المريض لقائمة الانتظار بعد إنهاء العملية وخروجه من صالة العمليات.');
        }

        if ($surgery->status !== 'in_progress') {
            return redirect()->back()->with('error', 'يمكن إرجاع المريض لقائمة الانتظار فقط إذا كان حالياً داخل صالة العمليات.');
        }

        $surgery->status = 'waiting';
        $surgery->end_time = null;
        $surgery->save();

        if ($surgery->operationTheaterStation) {
            $surgery->operationTheaterStation->status = 'pending';
            $surgery->operationTheaterStation->started_at = null;
            $surgery->operationTheaterStation->save();
        }

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم إعادة إخراج المريض من الصالة وإرجاعه إلى قائمة الانتظار بنجاح');
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
        $scopedTabs = [
            'detail_type', 'detail_diagnosis', 'detail_medications',
            'detail_anesthesia', 'detail_team_timing', 'detail_team',
            'detail_timing_location', 'detail_supplies', 'detail_devices',
        ];
        if ($requestedTab !== null && $requestedTab !== '') {
            if (! in_array($requestedTab, $scopedTabs, true)) {
                abort(403, 'تبويب غير صالح للتحديث.');
            }
            $targetLevel = $detailPermissions[$requestedTab]
                ?? ($requestedTab === 'detail_team_timing' ? ($detailPermissions['detail_team'] ?? $detailPermissions['detail_timing_location'] ?? null) : null)
                ?? ($requestedTab === 'detail_team' || $requestedTab === 'detail_timing_location' ? ($detailPermissions['detail_team_timing'] ?? null) : null)
                ?? ($requestedTab === 'detail_devices' ? ($detailPermissions['detail_supplies'] ?? null) : null)
                ?? SurgeryTabPermission::ACCESS_HIDDEN;

            if ($targetLevel !== SurgeryTabPermission::ACCESS_WRITE) {
                abort(403, 'غير مصرح لك بتعديل هذا القسم.');
            }
        }

        $validated = $request->validate([
            'location_id' => 'nullable|exists:locations,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'diagnosis' => 'nullable|string|max:1000',
            'anesthesia_type' => 'nullable|string|max:255',
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'anesthesiologist_2_id' => 'nullable|exists:doctors,id',
            'anesthesia_start_time' => 'nullable|date_format:H:i',
            'anesthesia_end_time' => 'nullable|date_format:H:i',
            'anesthesia_notes' => 'nullable|string|max:2000',
            'surgical_assistant_name' => 'nullable|string|max:255',
            'anesthesia_assistant_name' => 'nullable|string|max:255',
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
        $canWriteMedications = ($detailPermissions['detail_medications'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteAnesthesia = ($detailPermissions['detail_anesthesia'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteTeamTiming = ($detailPermissions['detail_team_timing'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteTeam = ($detailPermissions['detail_team'] ?? $detailPermissions['detail_team_timing'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteTimingLocation = ($detailPermissions['detail_timing_location'] ?? $detailPermissions['detail_team_timing'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteSupplies = ($detailPermissions['detail_supplies'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteDevices = ($detailPermissions['detail_devices'] ?? $detailPermissions['detail_supplies'] ?? '') === SurgeryTabPermission::ACCESS_WRITE;

        if ($requestedTab === 'detail_type') {
            $allowedKeys = ['surgical_notes'];
        } elseif ($requestedTab === 'detail_diagnosis') {
            $allowedKeys = ['diagnosis', 'post_op_notes', 'notes', 'treatment_plan', 'follow_up_date', 'prescribed_medications'];
        } elseif ($requestedTab === 'detail_medications') {
            $allowedKeys = ['prescribed_medications'];
        } elseif ($requestedTab === 'detail_anesthesia') {
            $allowedKeys = ['anesthesia_type', 'anesthesia_position', 'asa_classification', 'anesthesia_start_time', 'anesthesia_end_time', 'anesthesia_notes'];
        } elseif ($requestedTab === 'detail_team') {
            $allowedKeys = ['doctor_id', 'anesthesiologist_id', 'anesthesiologist_2_id', 'surgical_assistant_name', 'anesthesia_assistant_name'];
        } elseif ($requestedTab === 'detail_timing_location') {
            $allowedKeys = ['location_id', 'start_time', 'end_time', 'estimated_duration', 'estimated_duration_minutes'];
        } elseif ($requestedTab === 'detail_team_timing') {
            $allowedKeys = ['location_id', 'doctor_id', 'anesthesiologist_id', 'anesthesiologist_2_id', 'surgical_assistant_name', 'anesthesia_assistant_name', 'start_time', 'end_time', 'estimated_duration', 'estimated_duration_minutes'];
        } elseif ($requestedTab === 'detail_supplies' || $requestedTab === 'detail_devices') {
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
                    'surgery_category', 'surgery_type_detail', 'surgical_complexity', 'surgical_notes', 'required_fluids',
                ]);
            }
            if ($canWriteMedications || $canWriteDiagnosis) {
                $allowedKeys[] = 'prescribed_medications';
            }
            if ($canWriteAnesthesia) {
                $allowedKeys = array_merge($allowedKeys, [
                    'anesthesia_type', 'anesthesia_position', 'asa_classification',
                    'anesthesia_start_time', 'anesthesia_end_time', 'anesthesia_notes',
                ]);
            }
            if ($canWriteTeamTiming || $canWriteTeam) {
                $allowedKeys = array_merge($allowedKeys, [
                    'doctor_id', 'anesthesiologist_id', 'anesthesiologist_2_id', 'surgical_assistant_name', 'anesthesia_assistant_name',
                ]);
            }
            if ($canWriteTeamTiming || $canWriteTimingLocation) {
                $allowedKeys = array_merge($allowedKeys, [
                    'location_id', 'start_time', 'end_time', 'estimated_duration', 'estimated_duration_minutes',
                ]);
            }
            if ($canWriteSupplies || $canWriteDevices) {
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

                if (array_key_exists('post_op_notes', $validated)) {
                    $postOpStation = $surgery->residentStations()->where('phase', 'post_op')->first();
                    if ($postOpStation) {
                        $postOpStation->update(['post_op_notes' => $validated['post_op_notes']]);
                    } elseif (! empty($validated['post_op_notes'])) {
                        $surgery->residentStations()->create([
                            'phase' => 'post_op',
                            'status' => 'pending',
                            'post_op_notes' => $validated['post_op_notes'],
                        ]);
                    }
                }
            }

            // Sync / create AnesthesiaStation when saving anesthesia details or updating team/anesthesiologist
            $stationSync = [];
            foreach (['anesthesiologist_id', 'anesthesiologist_2_id', 'surgical_assistant_name', 'anesthesia_assistant_name', 'anesthesia_type'] as $field) {
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
            $surgery->syncPaymentStatus();

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
        $surgery->syncPaymentStatus();

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
        $surgery->syncPaymentStatus();

        return redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_type'])
            ->with('success', 'تم حذف العملية الإضافية');
    }

    public function addDevice(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $canWriteSupplies = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_supplies') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteAnesthesia = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_anesthesia') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteTeamTiming = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_team_timing') === SurgeryTabPermission::ACCESS_WRITE;
        if (! $isAdmin && ! $canWriteSupplies && ! $canWriteAnesthesia && ! $canWriteTeamTiming) {
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
        $devices = \App\Models\MedicalDevice::whereIn('id', $validated['device_ids'])->get()->keyBy('id');

        if (! empty($newDeviceIds)) {
            $syncData = [];
            foreach ($newDeviceIds as $deviceId) {
                $devModel = $devices->get($deviceId);
                $syncData[$deviceId] = [
                    'assigned_by' => $user->id,
                    'price' => (float) ($devModel?->usage_price ?? 0),
                ];
            }
            $surgery->medicalDevices()->attach($syncData);

            // الأجهزة الطبية تُحاسب مع الطبيب الجراح في الحسابات ولا توقف أو تُخفي فاتورة المريض في الكاشير
            $surgery->devices_billing_status = 'pending_review';
            $surgery->save();
            $surgery->syncSurgeonDoctorDue();
        }

        $names = $devices->pluck('name')->join('، ');
        $msg = 'تم إضافة أجهزة طبية: '.$names;

        $targetTab = $request->input('tab', 'detail_anesthesia');

        return $request->expectsJson()
            ? response()->json(['success' => $msg])
            : redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => $targetTab])->with('success', $msg);
    }

    public function removeDevice(Surgery $surgery, \App\Models\MedicalDevice $device)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $canWriteSupplies = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_supplies') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteAnesthesia = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_anesthesia') === SurgeryTabPermission::ACCESS_WRITE;
        $canWriteTeamTiming = SurgeryTabPermission::getDetailAccessForUser($user, 'detail_team_timing') === SurgeryTabPermission::ACCESS_WRITE;
        if (! $isAdmin && ! $canWriteSupplies && ! $canWriteAnesthesia && ! $canWriteTeamTiming) {
            abort(403, 'غير مصرح لك بإزالة أجهزة طبية');
        }

        $surgery->medicalDevices()->detach($device->id);

        $surgery->devices_billing_status = $surgery->medicalDevices()->exists() ? 'pending_review' : 'none';
        $surgery->save();
        $surgery->syncSurgeonDoctorDue();

        $targetTab = request()->input('tab', 'detail_anesthesia');

        return redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => $targetTab])
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

        // 1. Bulk multi-items dispense
        if ($request->has('items') && is_array($request->input('items'))) {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.batch_id' => 'required|exists:stock_batches,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.admin_notes' => 'nullable|string|max:1000',
                'location_id' => 'nullable|exists:locations,id',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            $locationId = $request->input('location_id');
            $opLocation = $locationId ? Location::find($locationId) : $surgery->getOperationSubStoreLocation($user);

            try {
                $dispensedCount = 0;
                $dispensedNames = [];

                DB::transaction(function () use ($validated, $surgery, $opLocation, $user, &$dispensedCount, &$dispensedNames) {
                    $patientName = $surgery->patient?->user?->name ?? $surgery->patient?->name ?? 'مريض';

                    foreach ($validated['items'] as $item) {
                        $batch = StockBatch::with('product')
                            ->where('id', $item['batch_id'])
                            ->lockForUpdate()
                            ->first();

                        if (! $batch) {
                            throw new \Exception("الوجبة رقم ({$item['batch_id']}) غير موجودة في المخزن.");
                        }

                        $itemLoc = $batch->location ?: $opLocation;
                        $locName = $itemLoc?->name ?? 'المخزن';

                        if ($batch->current_qty < $item['quantity']) {
                            $prodName = $batch->product?->name ?? 'مادة';
                            throw new \Exception("الكمية المطلوبة ({$item['quantity']}) من [{$prodName}] تتجاوز الرصيد المتوفر في {$locName} ({$batch->current_qty}).");
                        }

                        $batch->decrement('current_qty', $item['quantity']);

                        $itemNotes = $item['admin_notes'] ?? $validated['admin_notes'] ?? '';
                        $movementNotes = "صرف من ({$locName}) لعملية #{$surgery->id} - المريض: {$patientName}";
                        if (! empty($itemNotes)) {
                            $movementNotes .= ' - '.$itemNotes;
                        }

                        $movement = StockMovement::create([
                            'batch_id' => $batch->id,
                            'from_location_id' => $itemLoc?->id,
                            'to_location_id' => null,
                            'quantity' => $item['quantity'],
                            'type' => 'issue_to_patient',
                            'user_id' => $user->id,
                            'surgery_id' => $surgery->id,
                            'notes' => $movementNotes,
                        ]);

                        $product = $batch->product;
                        $unitName = $product?->unit ?? 'قطعة';
                        $dosageText = "{$item['quantity']} {$unitName}";

                        SurgeryTreatment::create([
                            'surgery_id' => $surgery->id,
                            'item_type' => SurgeryTreatment::TYPE_SUPPLY,
                            'description' => $product?->name ?? 'مادة مخزنية',
                            'dosage' => $dosageText,
                            'timing' => 'صرف من مخزن العمليات',
                            'status' => 'administered',
                            'administered_by' => $user->id,
                            'administered_at' => now(),
                            'admin_notes' => $itemNotes ?: null,
                            'batch_id' => $batch->id,
                            'stock_movement_id' => $movement->id,
                            'quantity' => $item['quantity'],
                            'administrations' => [
                                [
                                    'administered_at' => now()->format('Y-m-d H:i'),
                                    'administered_by' => $user->id,
                                    'administered_by_name' => $user->full_name ?? $user->name ?? 'موظف العمليات',
                                    'notes' => $itemNotes ?: null,
                                ],
                            ],
                        ]);

                        $dispensedCount++;
                        $dispensedNames[] = ($product?->name ?? 'مادة')." ({$item['quantity']} {$unitName})";
                    }
                });

                $msg = "تم صرف {$dispensedCount} مواد ومستلزمات بنجاح للعملية: ".implode('، ', array_slice($dispensedNames, 0, 4)).(count($dispensedNames) > 4 ? ' وغيرها...' : '');

                return $request->expectsJson()
                    ? response()->json(['success' => $msg])
                    : redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_supplies'])->with('success', $msg);
            } catch (\Exception $e) {
                return $request->expectsJson()
                    ? response()->json(['message' => $e->getMessage()], 422)
                    : redirect()->route('surgeries.show', ['surgery' => $surgery->id, 'tab' => 'detail_supplies'])->with('error', $e->getMessage());
            }
        }

        // 2. Single item dispense (backward compatibility with existing forms and tests)
        $validated = $request->validate([
            'batch_id' => 'required|exists:stock_batches,id',
            'quantity' => 'required|integer|min:1',
            'admin_notes' => 'nullable|string|max:1000',
            'location_id' => 'nullable|exists:locations,id',
        ]);

        $locationId = $request->input('location_id');
        $opLocation = $locationId ? Location::find($locationId) : $surgery->getOperationSubStoreLocation($user);
        if (! $opLocation) {
            $batchObj = StockBatch::find($validated['batch_id']);
            $opLocation = $batchObj?->location;
        }

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

        if ($surgery->status === 'completed' || ($surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'completed')) {
            return redirect()->back()->with('error', 'لا يمكن حذف أو إلغاء عملية مكتملة ومُنفّذة فعلياً.');
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
