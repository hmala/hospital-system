@extends('layouts.app')

@php
use App\Models\LabTestResult;
use App\Models\LabTest;

// الحصول على نتائج التحاليل المرتبطة بهذا الطلب
$labTest = LabTestResult::where('visit_id', $request->visit_id)
    ->where('test_name', $request->description)
    ->first();

// الحصول على جميع التحاليل النشطة للاستخدام في JavaScript
$labTests = LabTest::active()->get()->keyBy('name');

function getTestIcon($testName) {
    $name = strtolower($testName);

    if (strpos($name, 'سكر') !== false || strpos($name, 'glucose') !== false) {
        return 'fas fa-tint text-danger';
    } elseif (strpos($name, 'ضغط') !== false || strpos($name, 'pressure') !== false) {
        return 'fas fa-heartbeat text-danger';
    } elseif (strpos($name, 'كوليسترول') !== false || strpos($name, 'cholesterol') !== false) {
        return 'fas fa-oil-can text-warning';
    } elseif (strpos($name, 'دم') !== false || strpos($name, 'blood') !== false || strpos($name, 'cbc') !== false) {
        return 'fas fa-tint text-danger';
    } elseif (strpos($name, 'بول') !== false || strpos($name, 'urine') !== false) {
        return 'fas fa-flask text-warning';
    } elseif (strpos($name, 'كبد') !== false || strpos($name, 'liver') !== false || strpos($name, 'sgot') !== false || strpos($name, 'sgpt') !== false) {
        return 'fas fa-lungs text-success';
    } elseif (strpos($name, 'كلى') !== false || strpos($name, 'kidney') !== false || strpos($name, 'urea') !== false || strpos($name, 'creatinine') !== false) {
        return 'fas fa-kidney text-info';
    } elseif (strpos($name, 'هرمون') !== false || strpos($name, 'hormone') !== false || strpos($name, 'tsh') !== false) {
        return 'fas fa-atom text-purple';
    } elseif (strpos($name, 'فيروس') !== false || strpos($name, 'virus') !== false) {
        return 'fas fa-virus text-danger';
    } elseif (strpos($name, 'بكتيريا') !== false || strpos($name, 'bacteria') !== false) {
        return 'fas fa-bacterium text-success';
    } else {
        return 'fas fa-vial text-primary';
    }
}

function getTestUnit($testName, $labTests) {
    if (isset($labTests[$testName]) && !empty($labTests[$testName]->unit)) {
        return $labTests[$testName]->unit;
    }
    $name = strtolower($testName);
    if (strpos($name, 'سكر') !== false || strpos($name, 'glucose') !== false) return 'mg/dL';
    if (strpos($name, 'كوليسترول') !== false || strpos($name, 'cholesterol') !== false) return 'mg/dL';
    if (strpos($name, 'بيليروبين') !== false || strpos($name, 'bilirubin') !== false) return 'mg/dL';
    if (strpos($name, 'كرياتينين') !== false || strpos($name, 'creatinine') !== false) return 'mg/dL';
    if (strpos($name, 'يوريا') !== false || strpos($name, 'urea') !== false) return 'mg/dL';
    if (strpos($name, 'sgot') !== false || strpos($name, 'ast') !== false || strpos($name, 'sgpt') !== false || strpos($name, 'alt') !== false) return 'U/L';
    if (strpos($name, 'هيموغلوبين') !== false || strpos($name, 'hemoglobin') !== false || strpos($name, 'hb') !== false) return 'g/dL';
    if (strpos($name, 'wbc') !== false || strpos($name, 'platelets') !== false) return '/µL';
    return '';
}

function normalizeTestIds($testIds) {
    if (empty($testIds)) return [];
    if (is_string($testIds)) {
        $decoded = json_decode($testIds, true);
        if (is_array($decoded)) return $decoded;
        return array_filter(array_map('trim', explode(',', $testIds)), fn($item) => $item !== '');
    }
    if (is_array($testIds)) return $testIds;
    return [];
}

function normalizeTestsField($tests) {
    if (empty($tests)) return [];
    if (is_string($tests)) {
        $decoded = json_decode($tests, true);
        if (is_array($decoded)) return $decoded;
        return array_filter(array_map('trim', explode(',', $tests)), fn($item) => $item !== '');
    }
    if (is_array($tests)) return $tests;
    return [];
}

function buildSelectedLabTests($requestDetails) {
    $testsList = [];
    if (!is_array($requestDetails)) return $testsList;

    if (!empty($requestDetails['package_id'])) {
        $pkg = \App\Models\Package::find($requestDetails['package_id']);
        if ($pkg) {
            $testsList = array_merge($testsList, $pkg->labTests->pluck('name')->toArray());
        }
    }

    if (!empty($requestDetails['lab_test_ids'])) {
        $ids = normalizeTestIds($requestDetails['lab_test_ids']);
        foreach ($ids as $testId) {
            if ($testId === '') continue;
            $labTest = \App\Models\LabTest::find($testId);
            if ($labTest) {
                $testsList[] = $labTest->name;
            }
        }
    }

    if (isset($requestDetails['tests'])) {
        $tests = normalizeTestsField($requestDetails['tests']);
        $testsList = array_merge($testsList, $tests);
    }

    $testsList = array_filter($testsList, fn($item) => !is_null($item) && trim((string) $item) !== '');
    return array_values(array_unique($testsList));
}

$requestDetails = is_string($request->details) ? json_decode($request->details, true) : ($request->details ?? []);
if (!is_array($requestDetails)) $requestDetails = [];
$isBloodBankRequest = $request->type === 'blood_bank' || ($requestDetails['blood_bank'] ?? false);
$hasAttachment = !empty($requestDetails['attachment']);
$attachmentUrl = $hasAttachment ? asset('storage/' . $requestDetails['attachment']) : '';
$isImageAttachment = $hasAttachment && str_starts_with($requestDetails['attachment_mime'] ?? '', 'image/');
$testAttachments = is_array($requestDetails['test_attachments'] ?? null) ? $requestDetails['test_attachments'] : [];
$hasAnyAttachment = $hasAttachment || count($testAttachments) > 0;

$testsList = buildSelectedLabTests($requestDetails);
$patient = $request->visit?->patient;
$patientUser = $patient?->user;
$gender = $patientUser?->gender ?? ($patient?->gender ?? 'male');
$age = $patient?->age ?? null;
@endphp

@section('content')
<div class="container-fluid py-2">

    <!-- 1. شريط العنوان والإجراءات العلوي المدمج -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 bg-transparent pb-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-microscope fs-5"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="mb-0 fw-bold text-dark">
                                طلب المختبر #{{ $request->id }}
                            </h5>
                            <span class="badge bg-{{ $request->status_color }}">{{ $request->status_text }}</span>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i> {{ $request->created_at->format('Y-m-d H:i') }}
                        </small>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    @if(!$isBloodBankRequest)
                        <button type="submit" form="labResultForm" class="btn btn-primary fw-bold shadow-sm px-3">
                            <i class="fas fa-check-circle me-1"></i> حفظ واعتماد النتائج
                        </button>
                    @endif

                    @if(!$isBloodBankRequest && in_array($request->status, ['pending_service_selection', 'pending', 'in_progress', 'completed']))
                        <a href="{{ route('lab.show', ['request' => $request, 'append' => 1]) }}#appendTestsSection" class="btn btn-outline-secondary" title="إضافة تحاليل إضافية">
                            <i class="fas fa-plus-circle me-1"></i> إضافة تحاليل
                        </a>
                    @endif

                    <a href="{{ route('lab.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> القائمة
                    </a>
                </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger shadow-sm">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 2. بطاقة معلومات المريض والطلب الموحدة -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-transparent">
                <div class="card-header bg-transparent text-dark border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-id-card text-primary"></i>
                        <h6 class="mb-0 fw-bold">بيانات المريض والطلب</h6>
                    </div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary font-monospace fw-bold">
                            ملف: #{{ $patient?->national_id ?: ($patient?->id ?? $request->visit?->patient_id) }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <!-- اسم المريض -->
                        <div class="col-xl-3 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-subtle text-primary rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">اسم المريض</small>
                                    <strong class="text-dark fs-6">{{ $patientUser?->name ?? 'غير محدد' }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- العمر والجنس -->
                        <div class="col-xl-2 col-md-3 col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-info-subtle text-info rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas {{ $gender === 'female' ? 'fa-venus text-danger' : 'fa-mars text-primary' }}"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">العمر / الجنس</small>
                                    <strong class="text-dark">
                                        {{ $age ? ($age . ' سنة') : 'غير محدد' }} / 
                                        {{ $gender === 'male' ? 'ذكر' : ($gender === 'female' ? 'أنثى' : 'غير محدد') }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <!-- فصيلة الدم -->
                        <div class="col-xl-2 col-md-3 col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-danger-subtle text-danger rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-tint"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">فصيلة الدم</small>
                                    <strong class="text-danger fw-bold">{{ $patient?->blood_type ?: 'غير مسجلة' }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- الطبيب المرسل -->
                        <div class="col-xl-3 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-success-subtle text-success rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">الطبيب والعيادة</small>
                                    <strong class="text-dark">
                                        {{ $request->visit?->doctor?->user?->name ? 'د. ' . $request->visit->doctor->user->name : 'غير محدد' }}
                                    </strong>
                                    <small class="text-muted d-block">{{ $request->visit?->doctor?->specialization ?? '' }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- حالة السداد المالي -->
                        <div class="col-xl-2 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-warning-subtle text-warning rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-cash-register"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">حالة الدفع</small>
                                    @if(($request->payment_status ?? 'pending') == 'paid')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> مسدد بالكامل
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">
                                            <i class="fas fa-clock me-1"></i> غير مسدد ⚠️
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- إمكانية حذف تحليل قبل الدفع إن وجد -->
                    @if($request->status !== 'pending_service_selection' && $request->payment_status != 'paid' && $request->type == 'lab')
                        @php
                            $selectedTests = collect([]);
                            if (!empty($requestDetails['lab_test_ids'])) {
                                $selectedTests = \App\Models\LabTest::whereIn('id', $requestDetails['lab_test_ids'])->get();
                            }
                        @endphp
                        @if($selectedTests->count() > 0)
                            <div class="mt-3 pt-2 border-top d-flex flex-wrap gap-2 align-items-center">
                                <span class="badge bg-secondary py-1 px-2 small">التحاليل المحددة (قبل الدفع):</span>
                                @foreach($selectedTests as $test)
                                    <form action="{{ route('staff.lab-requests.remove-test', [$request, $test]) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-2 d-inline-flex align-items-center gap-1" title="حذف هذا التحليل" onclick="return confirm('هل أنت متأكد من حذف هذا التحليل؟');">
                                            <span style="font-size: 0.75rem;">{{ $test->name }}</span>
                                            <i class="fas fa-times" style="font-size: 0.7rem;"></i>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 3. قسم إضافة تحاليل إضافية (Append Tests Collapsible) -->
    @php
        $showAppendSection = request()->query('append') == '1';
        $currentTestIds = array_map('intval', $requestDetails['lab_test_ids'] ?? []);
        $favorites = \App\Models\UserLabTestStat::getFavoritesForUser(auth()->id());
    @endphp

    @if(!$isBloodBankRequest && in_array($request->status, ['pending_service_selection', 'pending', 'in_progress', 'completed']) && ($showAppendSection || count($testsList) == 0))
    <div class="row mb-3" id="appendTestsSection">
        <div class="col-12">
            <div class="card shadow-sm border border-primary-subtle bg-white">
                <div class="card-header bg-primary-subtle text-primary border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>{{ count($testsList) == 0 ? 'تحديد واختيار التحاليل المطلوبة لهذا الطلب' : 'إضافة تحاليل إضافية إلى هذا الطلب' }}</h6>
                    @if(count($testsList) > 0)
                        <a href="{{ route('lab.show', $request) }}" class="btn btn-outline-secondary btn-sm py-0 px-2">
                            <i class="fas fa-times me-1"></i> إغلاق
                        </a>
                    @endif
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('staff.lab-requests.append-tests', $request) }}" method="POST">
                        @csrf
                        <div class="alert alert-info py-2 px-3 mb-3 small">
                            <i class="fas fa-info-circle me-1"></i>
                            {{ count($testsList) == 0 ? 'هذا الطلب بانتظار تحديد الفحوصات. يرجى اختيار التحاليل المطلوبة للمريض من القائمة أدناه أو البحث عنها ثم الضغط على زر الحفظ.' : 'سيتم إدراج التحاليل المختارة إلى الطلب الحالي مباشرة دون حذف أي من التحاليل المسجلة مسبقاً.' }}
                        </div>

                        @php
                            $favoriteIds = $favorites->pluck('lab_test_id')->toArray();
                        @endphp

                        @if($favorites->isNotEmpty())
                        <div class="mb-3">
                            <span class="fw-bold small text-dark d-block mb-1"><i class="fas fa-star text-warning me-1"></i> مفضلاتي:</span>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($favorites as $stat)
                                    @php
                                        $test = $stat->labTest;
                                        if (!$test || !$test->is_active) continue;
                                    @endphp
                                    <label class="btn btn-sm {{ in_array($test->id, $currentTestIds) ? 'btn-secondary disabled' : 'btn-outline-primary' }} mb-0 py-1 px-2" style="font-size: 0.8rem;">
                                        <input type="checkbox" name="extra_lab_test_ids[]" value="{{ $test->id }}" class="d-none" {{ in_array($test->id, $currentTestIds) ? 'disabled' : '' }}>
                                        {{ $test->name }}
                                        @if($test->code) <small class="text-muted font-monospace">({{ $test->code }})</small> @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="appendTestSearch" placeholder="ابحث عن تحليل لإضافته...">
                            <button class="btn btn-outline-secondary" type="button" id="clearAppendSearch"><i class="fas fa-times"></i></button>
                        </div>

                        <div class="border rounded p-2 mb-3 bg-light" style="max-height: 250px; overflow-y: auto;" id="appendTestsContainer">
                            @php
                                $allLabTests = \App\Models\LabTest::where('is_active', true)
                                    ->orderBy('main_category')->orderBy('name')
                                    ->get()->groupBy('main_category');
                            @endphp
                            @foreach($allLabTests as $cat => $tests)
                                <div class="mb-2 append-test-group">
                                    <h6 class="text-primary border-bottom pb-1 small fw-bold">
                                        <i class="fas fa-folder-open me-1"></i>{{ $cat }}
                                    </h6>
                                    <div class="row g-1">
                                        @foreach($tests as $t)
                                            <div class="col-md-4 col-sm-6 append-test-item">
                                                <div class="form-check small">
                                                    <input class="form-check-input append-test-cb" type="checkbox"
                                                           name="extra_lab_test_ids[]" value="{{ $t->id }}"
                                                           id="append_{{ $t->id }}"
                                                           {{ in_array($t->id, $currentTestIds) ? 'disabled' : '' }}>
                                                    <label class="form-check-label {{ in_array($t->id, $currentTestIds) ? 'text-muted' : '' }}"
                                                           for="append_{{ $t->id }}">
                                                        {{ $t->name }}
                                                        @if(in_array($t->id, $currentTestIds))
                                                            <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">موجود</span>
                                                        @endif
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">
                            <i class="fas fa-save me-1"></i> إضافة التحاليل المختارة
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 4. النموذج الرئيسي لإدخال ومعالجة النتائج ورفع التقارير -->
    @if($isBloodBankRequest)
        {{-- نموذج مصرف الدم --}}
        <div class="card border-0 bg-transparent shadow-sm mb-4">
            <div class="card-header bg-transparent text-dark border-bottom py-2 px-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-tint text-danger me-2"></i>تفاصيل طلب مصرف الدم</h6>
            </div>
            <div class="card-body p-3">
                <form method="POST" action="{{ route('lab.update', $request) }}">
                    @csrf
                    @method('PUT')
                    @php
                        $bb = $bloodBankRequest ?? (object) [
                            'room_no' => $requestDetails['room_no'] ?? null,
                            'donor_group' => $requestDetails['donor_group'] ?? null,
                            'patient_group' => $requestDetails['patient_group'] ?? null,
                            'at_room_temp' => $requestDetails['at_room_temp'] ?? null,
                            'bovine_albumin' => $requestDetails['bovine_albumin'] ?? null,
                            'anti_human_globulin' => $requestDetails['anti_human_globulin'] ?? null,
                            'compatibility' => $requestDetails['compatibility'] ?? null,
                            'bottle_no' => $requestDetails['bottle_no'] ?? null,
                            'operative_date' => $requestDetails['operative_date'] ?? null,
                            'exp_date' => $requestDetails['exp_date'] ?? null,
                            'doctor_in_charge' => $requestDetails['doctor_in_charge'] ?? null,
                            'total_amount' => $requestDetails['total_amount'] ?? 0,
                            'notes' => $requestDetails['summary'] ?? null,
                        ];
                    @endphp
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">رقم الغرفة / السرير</label>
                            <input type="text" name="room_no" class="form-control form-control-sm" value="{{ old('room_no', $bb->room_no) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">فصيلة المتبرع</label>
                            <input type="text" name="donor_group" class="form-control form-control-sm" value="{{ old('donor_group', $bb->donor_group) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">فصيلة المريض</label>
                            <input type="text" name="patient_group" class="form-control form-control-sm" value="{{ old('patient_group', $bb->patient_group) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">التوافق</label>
                            <input type="text" name="compatibility" class="form-control form-control-sm" value="{{ old('compatibility', $bb->compatibility) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">رقم العبوة</label>
                            <input type="text" name="bottle_no" class="form-control form-control-sm" value="{{ old('bottle_no', $bb->bottle_no) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">تاريخ العملية</label>
                            <input type="date" name="operative_date" class="form-control form-control-sm" value="{{ old('operative_date', optional($bb->operative_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">تاريخ الانتهاء</label>
                            <input type="date" name="exp_date" class="form-control form-control-sm" value="{{ old('exp_date', optional($bb->exp_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">طبيب المسؤول</label>
                            <input type="text" name="doctor_in_charge" class="form-control form-control-sm" value="{{ old('doctor_in_charge', $bb->doctor_in_charge) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">ملاحظات إضافية</label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2">{{ old('notes', $bb->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold">حفظ بيانات مصرف الدم</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        {{-- نموذج إدخال وإرفاق نتائج المختبر الذكي --}}
        @php
            $patientGender = $gender ?? 'both';
            $patientAge    = (int) ($age ?? 0);
            $testsList = buildSelectedLabTests($requestDetails);
            $labTestMap = \App\Models\LabTest::with(['subTests' => function($q) {
                $q->orderBy('sort_order')->orderBy('id');
            }, 'references'])->whereIn('name', $testsList)->get()->keyBy('name');
            $dbResults = \App\Models\LabResult::where('request_id', $request->id)->get()->keyBy('test_name');
            
            $resultData = is_string($request->result) ? json_decode($request->result, true) : ($request->result ?? []);
            $savedTestResults = is_array($resultData) ? ($resultData['test_results'] ?? []) : [];
            
            // استخراج الملاحظات النصية الصافية وتجنب ظهور كود JSON الخام
            $savedNotes = '';
            if (is_array($resultData) && isset($resultData['notes']) && is_string($resultData['notes'])) {
                $trimmed = trim($resultData['notes']);
                if (!str_starts_with($trimmed, '{') && !str_starts_with($trimmed, '[')) {
                    $savedNotes = $trimmed;
                }
            } elseif (is_string($request->result)) {
                $trimmed = trim($request->result);
                if (!str_starts_with($trimmed, '{') && !str_starts_with($trimmed, '[')) {
                    $savedNotes = $trimmed;
                }
            }
        @endphp

        <form action="{{ route('lab.update', $request) }}" method="POST" enctype="multipart/form-data" id="labResultForm">
            @csrf
            @method('PUT')
            <input type="hidden" name="status" value="completed">
            <input type="hidden" name="action" value="complete">

            <div class="row g-3 mb-3">
                <!-- العمود الأيمن (8 أعمدة): جدول النتائج الرقمية والمديات المرجعية -->
                <div class="col-12">
                    <div class="card shadow-sm border-0 bg-transparent h-100 rounded-3 overflow-hidden d-flex flex-column">
                        <div class="card-header bg-transparent text-dark border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-table fs-5 text-primary"></i>
                                <h6 class="mb-0 fw-bold">جدول النتائج والمديات المرجعية ({{ count($testsList) }} فحص)</h6>
                            </div>
                            <div>
                                <span class="badge bg-primary-subtle text-primary fw-bold font-monospace">{{ count($testsList) }} فحوصات</span>
                            </div>
                        </div>

                        <div class="card-body p-0 flex-grow-1">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle mb-0" id="resultsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width: 40px;">#</th>
                                            <th>اسم الفحص الطبي</th>
                                            <th style="width: 200px;">النتيجة (Value)</th>
                                            <th style="width: 80px;">الوحدة</th>
                                            <th style="width: 150px;">المدى المرجعي</th>
                                            <th style="width: 90px;" class="text-center">الحالة (Flag)</th>
                                            <th style="width: 250px;" class="text-center">
                                                <i class="fas fa-paperclip text-primary me-1"></i> المرفق (اختياري)
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($testsList as $index => $test)
                                            @php
                                                $testIcon   = getTestIcon($test);
                                                $labTestObj = $labTestMap[$test] ?? null;
                                                $hasSubTests = $labTestObj && $labTestObj->subTests->count() > 0;
                                                $hasSpecificAttachment = !empty($testAttachments[$test]);
                                            @endphp

                                            @if($hasSubTests)
                                                {{-- فحص مركب: يعتمد على تقرير الجهاز المرفق --}}
                                                <tr class="table-info bg-opacity-10">
                                                    <td class="text-center text-muted small fw-bold">{{ $index + 1 }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="{{ $testIcon }}"></i>
                                                            <strong class="text-dark">{{ $test }}</strong>
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" style="font-size: 0.72rem;">
                                                                <i class="fas fa-layer-group me-1"></i> فحص مركب ({{ $labTestObj->subTests->count() }} فرعي)
                                                            </span>
                                                        </div>
                                                        <small class="text-muted d-block mt-1">
                                                            <i class="fas fa-info-circle me-1"></i> تتم قراءة كافة المعاملات والرسومات البيانية من الملف المرفق
                                                        </small>
                                                    </td>
                                                    <td colspan="4">
                                                        <div class="d-flex align-items-center gap-2 text-muted small">
                                                            <i class="fas fa-arrow-left"></i> يرجى إرفاق تقرير الجهاز الخاص بهذا الفحص في الخانة المجاورة
                                                        </div>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($hasSpecificAttachment)
                                                            <div class="d-inline-flex align-items-center gap-1 bg-white border border-primary-subtle rounded-pill p-1 shadow-sm">
                                                                <a href="{{ asset('storage/' . $testAttachments[$test]['path']) }}" target="_blank" class="btn btn-xs btn-primary-subtle text-primary rounded-pill py-1 px-2.5 d-inline-flex align-items-center gap-1 text-decoration-none fw-bold" style="font-size: 0.72rem;" title="معاينة التقرير المرفق">
                                                                    <i class="fas fa-paperclip"></i>
                                                                    <span>معاينة التقرير</span>
                                                                </a>
                                                                <label class="btn btn-xs btn-outline-danger rounded-circle p-0 mb-0 d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px; cursor: pointer;" title="حذف هذا المرفق عند الحفظ">
                                                                    <input type="checkbox" name="remove_test_attachment[{{ $test }}]" value="1" class="d-none" onchange="this.checked ? (this.closest('label').classList.add('active', 'btn-danger', 'text-white'), this.closest('.d-inline-flex').classList.add('border-danger', 'bg-danger-subtle')) : (this.closest('label').classList.remove('active', 'btn-danger', 'text-white'), this.closest('.d-inline-flex').classList.remove('border-danger', 'bg-danger-subtle'))">
                                                                    <i class="fas fa-times" style="font-size: 0.65rem;"></i>
                                                                </label>
                                                            </div>
                                                        @elseif($hasAttachment)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                                                <i class="fas fa-check-circle me-1"></i> ضمن التقرير العام
                                                            </span>
                                                        @else
                                                            <div class="pill-upload-wrapper d-inline-block">
                                                                <label class="btn btn-sm btn-outline-primary rounded-pill py-1 px-2.5 mb-0 d-inline-flex align-items-center gap-1.5 shadow-sm pill-upload-btn" style="cursor: pointer; font-size: 0.75rem; border-width: 1.5px;" title="اضغط لاختيار تقرير هذا الفحص">
                                                                    <i class="fas fa-paperclip"></i>
                                                                    <span class="pill-text fw-semibold">إرفاق تقرير</span>
                                                                    <input type="file" name="test_attachments[{{ $test }}]" class="d-none pill-file-input" accept=".pdf,image/*" onchange="handlePillUpload(this)">
                                                                </label>
                                                                <div class="pill-chosen-badge d-none align-items-center gap-1 bg-success-subtle text-success border border-success-subtle rounded-pill py-1 px-2 shadow-sm" style="font-size: 0.74rem;">
                                                                    <i class="fas fa-check-circle"></i>
                                                                    <span class="pill-filename text-truncate fw-bold" style="max-width: 110px;"></span>
                                                                    <button type="button" class="btn-close p-0 ms-1" style="font-size: 0.55rem;" title="إلغاء الملف" onclick="cancelPillUpload(this)"></button>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @else
                                                {{-- فحص رقمي فردي --}}
                                                @php
                                                    $refObj = $labTestObj
                                                        ? \App\Models\LabTestReference::forPatient($labTestObj->id, $patientGender, $patientAge)
                                                        : null;
                                                    $refDisplay  = $refObj ? $refObj->range_display : '—';
                                                    $refMin      = $refObj?->ref_min;
                                                    $refMax      = $refObj?->ref_max;
                                                    $unitDisplay = $refObj?->unit ?? getTestUnit($test, $labTests);
                                                    
                                                    // القيمة المحفوظة مسبقاً
                                                    $savedVal = $dbResults[$test]->value ?? '';
                                                    if ($savedVal === '' && isset($savedTestResults[$test])) {
                                                        $savedVal = is_array($savedTestResults[$test]) ? ($savedTestResults[$test]['value'] ?? '') : $savedTestResults[$test];
                                                    }
                                                @endphp
                                                <tr class="test-row" data-test="{{ $test }}"
                                                    data-ref-min="{{ $refMin }}"
                                                    data-ref-max="{{ $refMax }}">
                                                    <td class="text-center text-muted small fw-bold">{{ $index + 1 }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="{{ $testIcon }}"></i>
                                                            <strong class="text-dark">{{ $test }}</strong>
                                                            @if($labTestObj?->code)
                                                                <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.7rem;">{{ $labTestObj->code }}</span>
                                                            @endif
                                                            @if($hasSpecificAttachment)
                                                                <a href="{{ asset('storage/' . $testAttachments[$test]['path']) }}" target="_blank" class="badge bg-info-subtle text-info border border-info ms-1 text-decoration-none" title="يوجد تقرير منفصل لهذا الفحص">
                                                                    <i class="fas fa-paperclip"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                               class="form-control form-control-sm test-value font-monospace fw-bold"
                                                               name="test_results[{{ $test }}][value]"
                                                               value="{{ old('test_results.' . $test . '.value', $savedVal) }}"
                                                               placeholder="أدخل القيمة..."
                                                               tabindex="{{ $index + 1 }}"
                                                               data-test="{{ $test }}">
                                                        <input type="hidden" name="test_results[{{ $test }}][test_name]" value="{{ $test }}">
                                                        <input type="hidden" name="test_results[{{ $test }}][lab_test_id]" value="{{ $labTestObj?->id }}">
                                                        <input type="hidden" name="test_results[{{ $test }}][unit]" value="{{ $unitDisplay }}">
                                                        <input type="hidden" name="test_results[{{ $test }}][reference_range]" value="{{ $refDisplay }}">
                                                    </td>
                                                    <td class="text-muted small fw-semibold">{{ $unitDisplay ?: '-' }}</td>
                                                    <td>
                                                        @if($refObj && $refDisplay !== '—')
                                                            <span class="badge bg-light text-primary border font-monospace">{{ $refDisplay }}</span>
                                                        @else
                                                            <span class="text-muted small">{{ $refDisplay }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="result-flag" id="flag-{{ $index }}">
                                                            <i class="fas fa-circle text-muted small"></i>
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($hasSpecificAttachment)
                                                            <div class="d-inline-flex align-items-center gap-1 bg-white border border-primary-subtle rounded-pill p-1 shadow-sm">
                                                                <a href="{{ asset('storage/' . $testAttachments[$test]['path']) }}" target="_blank" class="btn btn-xs btn-primary-subtle text-primary rounded-pill py-1 px-2.5 d-inline-flex align-items-center gap-1 text-decoration-none fw-bold" style="font-size: 0.72rem;" title="معاينة التقرير المرفق">
                                                                    <i class="fas fa-paperclip"></i>
                                                                    <span>معاينة التقرير</span>
                                                                </a>
                                                                <label class="btn btn-xs btn-outline-danger rounded-circle p-0 mb-0 d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px; cursor: pointer;" title="حذف هذا المرفق عند الحفظ">
                                                                    <input type="checkbox" name="remove_test_attachment[{{ $test }}]" value="1" class="d-none" onchange="this.checked ? (this.closest('label').classList.add('active', 'btn-danger', 'text-white'), this.closest('.d-inline-flex').classList.add('border-danger', 'bg-danger-subtle')) : (this.closest('label').classList.remove('active', 'btn-danger', 'text-white'), this.closest('.d-inline-flex').classList.remove('border-danger', 'bg-danger-subtle'))">
                                                                    <i class="fas fa-times" style="font-size: 0.65rem;"></i>
                                                                </label>
                                                            </div>
                                                        @elseif($hasAttachment)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">
                                                                <i class="fas fa-check-circle me-1"></i> ضمن التقرير العام
                                                            </span>
                                                        @else
                                                            <div class="pill-upload-wrapper d-inline-block">
                                                                <label class="btn btn-sm btn-outline-primary rounded-pill py-1 px-2.5 mb-0 d-inline-flex align-items-center gap-1.5 shadow-sm pill-upload-btn" style="cursor: pointer; font-size: 0.75rem; border-width: 1.5px;" title="اضغط لاختيار تقرير هذا الفحص">
                                                                    <i class="fas fa-paperclip"></i>
                                                                    <span class="pill-text fw-semibold">إرفاق تقرير</span>
                                                                    <input type="file" name="test_attachments[{{ $test }}]" class="d-none pill-file-input" accept=".pdf,image/*" onchange="handlePillUpload(this)">
                                                                </label>
                                                                <div class="pill-chosen-badge d-none align-items-center gap-1 bg-success-subtle text-success border border-success-subtle rounded-pill py-1 px-2 shadow-sm" style="font-size: 0.74rem;">
                                                                    <i class="fas fa-check-circle"></i>
                                                                    <span class="pill-filename text-truncate fw-bold" style="max-width: 110px;"></span>
                                                                    <button type="button" class="btn-close p-0 ms-1" style="font-size: 0.55rem;" title="إلغاء الملف" onclick="cancelPillUpload(this)"></button>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-5 text-muted">
                                                    <i class="fas fa-flask fa-3x mb-3 text-primary opacity-50"></i>
                                                    <h6 class="fw-bold text-dark mb-1">لا توجد تحاليل محددة في هذا الطلب بعد</h6>
                                                    <p class="text-muted small mb-3">يمكنك اختيار وتحديد التحاليل المطلوبة للمريض الآن للبدء بإدخال النتائج.</p>
                                                    <a href="{{ route('lab.show', ['request' => $request, 'append' => 1]) }}#appendTestsSection" class="btn btn-primary btn-sm px-3 shadow-sm fw-bold">
                                                        <i class="fas fa-plus-circle me-1"></i> اختيار وتحديد التحاليل الآن
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- شريط الإحصاء الحي للنتائج -->
                        <div class="p-3 bg-light border-top d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-chart-pie text-primary fs-5"></i>
                                <strong class="text-dark small">ملخص تقييم النتائج:</strong>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3">
                                    <i class="fas fa-check-circle me-1"></i> طبيعي: <strong id="normal-count" class="ms-1 fs-6">0</strong>
                                </span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-2 px-3">
                                    <i class="fas fa-arrow-up me-1"></i> مرتفع: <strong id="high-count" class="ms-1 fs-6">0</strong>
                                </span>
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-2 px-3">
                                    <i class="fas fa-arrow-down me-1 text-warning"></i> منخفض: <strong id="low-count" class="ms-1 fs-6">0</strong>
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary border py-2 px-3">
                                    <i class="fas fa-clock me-1"></i> غير مدخل: <strong id="pending-count" class="ms-1 fs-6">{{ count($testsList) }}</strong>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- البطاقة ج: الملاحظات والتوصيات المخبرية (منسدلة اختيارية لتوفير المساحة) -->
            @php
                $hasNotes = !empty(trim($savedNotes ?? ''));
            @endphp
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 fw-semibold" 
                            type="button" 
                            data-bs-toggle="collapse" 
                            data-bs-target="#labNotesCollapse" 
                            aria-expanded="{{ $hasNotes ? 'true' : 'false' }}">
                        <i class="fas fa-comment-medical text-primary"></i>
                        <span>{{ $hasNotes ? 'تعديل الملاحظات والتوصيات المخبرية' : '+ إضافة ملاحظة أو توصية مخبرية (اختياري)' }}</span>
                        @if($hasNotes)
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2">يوجد ملاحظة</span>
                        @endif
                    </button>
                </div>
                <div class="collapse {{ $hasNotes ? 'show' : '' }}" id="labNotesCollapse">
                    <div class="card shadow-sm border-0 bg-white rounded-3 p-3">
                        <label for="result" class="form-label small fw-bold text-muted mb-1">
                            <i class="fas fa-edit me-1"></i> نص الملاحظات والتوصيات (يظهر أسفل تقرير النتائج المطبوع):
                        </label>
                        <textarea class="form-control form-control-sm" id="result" name="result" rows="2" placeholder="اكتب أي ملاحظات فنية (مثل: عينة متحللة، توصية بإعادة الفحص...)">{{ old('result', $savedNotes) }}</textarea>
                    </div>
                </div>
            </div>

        </form>
    @endif

</div>

<script>
// ──────────────── دوال رفع المرفقات بنمط الكبسولة الأنيقة ────────────────
function handlePillUpload(input) {
    const wrapper = input.closest('.pill-upload-wrapper');
    if (!wrapper) return;
    const btn = wrapper.querySelector('.pill-upload-btn');
    const badge = wrapper.querySelector('.pill-chosen-badge');
    const nameSpan = wrapper.querySelector('.pill-filename');
    
    if (input.files && input.files[0]) {
        nameSpan.textContent = input.files[0].name;
        nameSpan.title = input.files[0].name;
        btn.classList.add('d-none');
        badge.classList.remove('d-none');
        badge.classList.add('d-inline-flex');
    }
}

function cancelPillUpload(button) {
    const wrapper = button.closest('.pill-upload-wrapper');
    if (!wrapper) return;
    const btn = wrapper.querySelector('.pill-upload-btn');
    const badge = wrapper.querySelector('.pill-chosen-badge');
    const input = wrapper.querySelector('.pill-file-input');
    
    if (input) input.value = '';
    badge.classList.add('d-none');
    badge.classList.remove('d-inline-flex');
    btn.classList.remove('d-none');
}

// ──────────────── تلوين وتحديد نتائج التحاليل تلقائياً ────────────────
function parseRangeValues(row) {
    let min = null;
    let max = null;

    if (row.dataset.refMin && row.dataset.refMin.trim() !== '') {
        min = parseFloat(row.dataset.refMin);
        if (isNaN(min)) min = null;
    }
    if (row.dataset.refMax && row.dataset.refMax.trim() !== '') {
        max = parseFloat(row.dataset.refMax);
        if (isNaN(max)) max = null;
    }

    if (min === null && max === null) {
        const refCell = row.querySelector('td:nth-child(5)');
        const refText = refCell ? refCell.textContent.replace(/,/g, '').trim() : '';
        if (refText) {
            let m = refText.match(/([\d\.]+)\s*-\s*([\d\.]+)/);
            if (m) {
                min = parseFloat(m[1]);
                max = parseFloat(m[2]);
            } else {
                m = refText.match(/<\s*=?\s*([\d\.]+)/);
                if (m) max = parseFloat(m[1]);
                m = refText.match(/>\s*=?\s*([\d\.]+)/);
                if (m) min = parseFloat(m[1]);
            }
        }
    }
    return { min, max };
}

function evaluateRow(row) {
    const input = row.querySelector('.test-value');
    if (!input) return null;

    const flag = row.querySelector('.result-flag');
    const val  = input.value.trim();

    row.style.backgroundColor = '';
    row.classList.remove('table-success', 'table-danger', 'table-warning', 'result-normal', 'result-high', 'result-low');
    row.querySelectorAll('td').forEach(td => td.style.backgroundColor = '');
    if (flag) flag.innerHTML = '<i class="fas fa-circle text-muted small"></i>';

    if (val === '') {
        return null;
    }

    const lower = val.toLowerCase();
    if (lower === 'positive' || lower === 'موجب' || lower === 'pos' || lower === '+') {
        row.classList.add('table-danger', 'result-high');
        row.querySelectorAll('td').forEach(td => td.style.backgroundColor = '#f8d7da');
        if (flag) flag.innerHTML = '<span class="badge bg-danger">↑ موجب (غير طبيعي)</span>';
        return 'high';
    }
    if (lower === 'negative' || lower === 'سالب' || lower === 'neg' || lower === '-' || lower === 'normal' || lower === 'طبيعي') {
        row.classList.add('table-success', 'result-normal');
        row.querySelectorAll('td').forEach(td => td.style.backgroundColor = '#d1e7dd');
        if (flag) flag.innerHTML = '<span class="badge bg-success">✓ سالب (طبيعي)</span>';
        return 'normal';
    }

    const numeric = parseFloat(val.replace(/,/g, ''));
    const { min: refMin, max: refMax } = parseRangeValues(row);

    if (isNaN(numeric) || (refMin === null && refMax === null)) {
        return 'unknown';
    }

    if (refMin !== null && numeric < refMin) {
        row.classList.add('table-warning', 'result-low');
        row.querySelectorAll('td').forEach(td => td.style.backgroundColor = '#fff3cd');
        if (flag) flag.innerHTML = '<span class="badge bg-warning text-dark">↓ منخفض</span>';
        return 'low';
    }
    if (refMax !== null && numeric > refMax) {
        row.classList.add('table-danger', 'result-high');
        row.querySelectorAll('td').forEach(td => td.style.backgroundColor = '#f8d7da');
        if (flag) flag.innerHTML = '<span class="badge bg-danger">↑ مرتفع</span>';
        return 'high';
    }
    row.classList.add('table-success', 'result-normal');
    row.querySelectorAll('td').forEach(td => td.style.backgroundColor = '#d1e7dd');
    if (flag) flag.innerHTML = '<span class="badge bg-success">✓ طبيعي</span>';
    return 'normal';
}

function updateSummary() {
    let normal = 0, high = 0, low = 0, pending = 0;
    document.querySelectorAll('.test-row').forEach(row => {
        const r = evaluateRow(row);
        if (r === 'normal') normal++;
        else if (r === 'high') high++;
        else if (r === 'low') low++;
        else pending++;
    });
    const n = document.getElementById('normal-count');
    const h = document.getElementById('high-count');
    const l = document.getElementById('low-count');
    const p = document.getElementById('pending-count');
    if (n) n.textContent = normal;
    if (h) h.textContent = high;
    if (l) l.textContent = low;
    if (p) p.textContent = pending;
}

function initializeLabResults() {
    document.querySelectorAll('.test-row .test-value').forEach((input) => {
        input.addEventListener('input', function () {
            evaluateRow(this.closest('.test-row'));
            updateSummary();
        });
        input.addEventListener('change', function () {
            evaluateRow(this.closest('.test-row'));
            updateSummary();
        });
        input.addEventListener('keyup', function () {
            evaluateRow(this.closest('.test-row'));
            updateSummary();
        });
        if (input.value.trim() !== '') {
            evaluateRow(input.closest('.test-row'));
        }
    });
    updateSummary();
}

document.addEventListener('DOMContentLoaded', function() {
    initializeLabResults();

    // بحث سريع في إضافة التحاليل
    const appendSearch = document.getElementById('appendTestSearch');
    if (appendSearch) {
        appendSearch.addEventListener('input', function() {
            const term = this.value.toLowerCase().trim();
            document.querySelectorAll('.append-test-item').forEach(item => {
                const label = item.textContent.toLowerCase();
                item.style.display = label.includes(term) ? '' : 'none';
            });
        });
    }

    const clearAppend = document.getElementById('clearAppendSearch');
    if (clearAppend && appendSearch) {
        clearAppend.addEventListener('click', function() {
            appendSearch.value = '';
            appendSearch.dispatchEvent(new Event('input'));
        });
    }
});
</script>
@endsection
