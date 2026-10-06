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
$patient = $request->visit?->patient;
$patientUser = $patient?->user;
$gender = $patientUser?->gender ?? ($patient?->gender ?? 'male');
$age = $patient?->age ?? null;
@endphp

@section('content')
<div class="container-fluid py-2">

    <!-- 1. شريط العنوان والأزرار العلوية -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 bg-white p-3 rounded-3 shadow-sm border">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary text-white p-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-microscope fs-5"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-dark">
                            معالجة طلب المختبر #{{ $request->id }}
                        </h4>
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i> {{ $request->created_at->format('Y-m-d H:i') }}
                            <span class="mx-1">•</span>
                            <span class="badge bg-{{ $request->status_color }}">{{ $request->status_text }}</span>
                        </small>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($hasAttachment)
                        <a href="{{ $attachmentUrl }}" 
                           class="btn btn-info text-white fw-bold shadow-sm" 
                           target="_blank">
                            <i class="fas fa-file-alt me-1"></i>
                            معاينة / طباعة تقرير الجهاز
                        </a>
                    @endif
                    @if($request->type == 'lab' || $isBloodBankRequest)
                        <a href="{{ route('lab.print', $request) }}" 
                           class="btn btn-success fw-bold shadow-sm" 
                           target="_blank">
                            <i class="fas fa-print me-1"></i>
                            طباعة تقرير النظام (RX)
                        </a>
                    @endif
                    @if(!$isBloodBankRequest && in_array($request->status, ['pending', 'in_progress', 'completed']))
                        <a href="{{ route('lab.show', ['request' => $request, 'append' => 1]) }}#appendTestsSection" class="btn btn-outline-primary fw-semibold">
                            <i class="fas fa-plus-circle me-1"></i>
                            إضافة تحاليل أخرى
                        </a>
                    @endif
                    <a href="{{ route('lab.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
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
            <div class="card shadow-sm border-0 bg-white">
                <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-id-card"></i>
                        <h6 class="mb-0 fw-bold">بيانات المريض والطلب</h6>
                    </div>
                    <div>
                        <span class="badge bg-white text-primary font-monospace fw-bold">
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

    @if(!$isBloodBankRequest && in_array($request->status, ['pending', 'in_progress', 'completed']) && $showAppendSection)
    <div class="row mb-3" id="appendTestsSection">
        <div class="col-12">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>إضافة تحاليل إضافية إلى هذا الطلب</h6>
                    <a href="{{ route('lab.show', $request) }}" class="btn btn-light btn-sm py-0 px-2">
                        <i class="fas fa-times me-1"></i> إغلاق
                    </a>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('staff.lab-requests.append-tests', $request) }}" method="POST">
                        @csrf
                        <div class="alert alert-info py-2 px-3 mb-3 small">
                            <i class="fas fa-info-circle me-1"></i>
                            سيتم إدراج التحاليل المختارة إلى الطلب الحالي مباشرة دون حذف أي من التحاليل المسجلة مسبقاً.
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

    <!-- 4. النموذج الرئيسي لإدخال ومعالجة النتائج -->
    @if($isBloodBankRequest)
        {{-- نموذج مصرف الدم --}}
        <div class="card border-danger shadow-sm mb-4">
            <div class="card-header bg-danger text-white py-2 px-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-tint me-2"></i>تفاصيل طلب مصرف الدم</h6>
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
        {{-- نموذج إدخال وإرفاق نتائج المختبر --}}
        <form action="{{ route('lab.update', $request) }}" method="POST" enctype="multipart/form-data" id="labResultForm">
            @csrf
            @method('PUT')

            <!-- البطاقة أ: إرفاق تقرير جهاز التحاليل / سكانر / PDF -->
            <div class="card border-info mb-3 shadow-sm rounded-3 overflow-hidden" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);">
                <div class="card-header bg-info text-white d-flex align-items-center justify-content-between py-2 px-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-file-medical-alt me-2"></i>
                        إرفاق تقرير جهاز التحاليل (Scanned Machine Report / PDF / صور)
                    </h6>
                    @if($hasAttachment)
                        <span class="badge bg-success fs-6"><i class="fas fa-check-circle me-1"></i> يوجد تقرير مرفق</span>
                    @endif
                </div>
                <div class="card-body p-3">
                    @if($hasAttachment)
                        <div class="alert alert-white bg-white border-2 border-success p-3 rounded-3 mb-3 shadow-sm">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle fs-3">
                                        <i class="fas {{ $isImageAttachment ? 'fa-file-image' : 'fa-file-pdf' }}"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1">{{ $requestDetails['attachment_title'] ?? 'ملف تقرير التحاليل المرفق' }}</h6>
                                        <div class="text-muted small">
                                            <span><i class="fas fa-paperclip me-1"></i> {{ $requestDetails['attachment_name'] ?? 'ملف مرفق' }}</span>
                                            @if(!empty($requestDetails['attached_at']))
                                                <span class="ms-3"><i class="fas fa-clock me-1"></i> {{ $requestDetails['attached_at'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ $attachmentUrl }}" target="_blank" class="btn btn-sm btn-info text-white fw-bold shadow-sm">
                                        <i class="fas fa-print me-1"></i> طباعة الملف المرفق
                                    </a>
                                    <a href="{{ $attachmentUrl }}" target="_blank" class="btn btn-sm btn-primary fw-bold">
                                        <i class="fas fa-eye me-1"></i> معاينة وتكبير
                                    </a>
                                    <label class="btn btn-sm btn-outline-danger" for="removeAttachmentCb" style="cursor: pointer;">
                                        <input type="checkbox" name="remove_attachment" value="1" id="removeAttachmentCb" class="d-none" onchange="this.checked ? this.closest('label').classList.add('active', 'btn-danger') : this.closest('label').classList.remove('active', 'btn-danger')">
                                        <i class="fas fa-trash-alt me-1"></i> حذف الملف عند الحفظ
                                    </label>
                                </div>
                            </div>
                            @if($isImageAttachment)
                                <div class="mt-3 text-center border-top pt-2">
                                    <a href="{{ $attachmentUrl }}" target="_blank" title="اضغط للتكبير">
                                        <img src="{{ $attachmentUrl }}" alt="تقرير مرفق" style="max-height: 200px; max-width: 100%; object-fit: contain;" class="rounded border shadow-sm">
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="row align-items-center g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold small text-dark mb-1">
                                <i class="fas fa-upload me-1 text-primary"></i>
                                {{ $hasAttachment ? 'استبدال أو رفع ملف جديد:' : 'اختر ملف التقرير الممسوح من الجهاز (PDF، صورة، أو سكانر):' }}
                            </label>
                            <input type="file" name="attachment" id="attachmentInput" class="form-control form-control-sm" accept=".pdf,.png,.jpg,.jpeg">
                            <div class="form-text small text-muted" style="font-size: 0.75rem;">الملفات المدعومة: PDF, JPG, PNG بحجم أقصى 20MB.</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small text-dark mb-1">عنوان أو وصف الملف المرفق (اختياري):</label>
                            <input type="text" name="attachment_title" class="form-control form-control-sm" value="{{ old('attachment_title', $requestDetails['attachment_title'] ?? '') }}" placeholder="مثال: تقرير CBC كامل من الجهاز">
                        </div>
                    </div>
                </div>
            </div>

            <!-- البطاقة ب: جدول إدخال النتائج الرقمية والمديات المرجعية -->
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
                $savedNotes = is_array($resultData) ? ($resultData['notes'] ?? (is_string($request->result) ? $request->result : '')) : (is_string($request->result) ? $request->result : '');
            @endphp

            <div class="card shadow-sm border-0 bg-white mb-3 rounded-3 overflow-hidden">
                <div class="card-header bg-gradient bg-primary text-white d-flex justify-content-between align-items-center py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-table fs-5"></i>
                        <h6 class="mb-0 fw-bold">جدول النتائج الرقمية والمديات المرجعية ({{ count($testsList) }} فحص)</h6>
                    </div>
                    <div>
                        <span class="badge bg-white text-primary fw-bold">{{ $request->visit?->patient?->user?->name }}</span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0" id="resultsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 45px;">#</th>
                                    <th>اسم الفحص الطبي</th>
                                    <th style="width: 220px;">النتيجة (Value)</th>
                                    <th style="width: 100px;">الوحدة</th>
                                    <th style="width: 180px;">المدى الطبيعي المرجعي</th>
                                    <th style="width: 120px;" class="text-center">الحالة (Flag)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($testsList as $index => $test)
                                    @php
                                        $testIcon   = getTestIcon($test);
                                        $labTestObj = $labTestMap[$test] ?? null;
                                        $hasSubTests = $labTestObj && $labTestObj->subTests->count() > 0;
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
                                            <td colspan="2">
                                                @if($hasAttachment)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 fw-semibold">
                                                        <i class="fas fa-check-circle me-1"></i> مرفق بالتقرير أعلاه
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-2 fw-semibold">
                                                        <i class="fas fa-upload me-1 text-warning"></i> يرجى إرفاق ملف التقرير
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-muted border">مدرج بتقرير الجهاز</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary-subtle text-secondary border">تقرير مرفق</span>
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
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="fas fa-flask fa-2x mb-2 opacity-25"></i>
                                            <p class="mb-0">لا توجد تحاليل محددة في هذا الطلب بعد</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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

            <!-- شريط الإجراءات السفلي (Action Footer) -->
            <div class="card shadow-sm border-0 bg-white sticky-bottom p-3 mb-4 rounded-3" style="bottom: 15px; z-index: 100;">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <label for="status" class="form-label mb-0 fw-bold small text-dark">حالة الطلب:</label>
                        <select class="form-select form-select-sm" id="status" name="status" style="width: 160px;" required>
                            <option value="in_progress" {{ $request->status == 'in_progress' ? 'selected' : '' }}>قيد المعالجة (مسودة)</option>
                            <option value="completed" {{ $request->status == 'completed' ? 'selected' : '' }}>مكتمل ومعتمد ✅</option>
                            <option value="pending" {{ $request->status == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                        </select>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-outline-primary px-3 fw-bold">
                            <i class="fas fa-save me-1"></i> حفظ النتائج
                        </button>
                        <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" onclick="completeAndSave()">
                            <i class="fas fa-check-circle me-1"></i> اعتماد وإنهاء الفحص
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <!-- 5. معلومات الزيارة والتشخيص الطبي (للاطلاع السريري) -->
    @if($request->visit)
    <div class="card shadow-sm border-0 bg-white mb-4 rounded-3">
        <div class="card-header bg-light py-2 px-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="fas fa-history text-muted me-2"></i>
                سياق الزيارة والتشخيص الطبي للطبيب المعالج
            </h6>
        </div>
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <small class="text-muted d-block">تاريخ ونوع الزيارة</small>
                    <strong class="text-dark">{{ $request->visit->visit_date ? $request->visit->visit_date->format('Y-m-d') : 'اليوم' }} ({{ $request->visit->visit_type_text ?? 'كشفية عادية' }})</strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">الشكوى الرئيسية</small>
                    <span class="text-dark">{{ $request->visit->chief_complaint ?: 'غير محددة' }}</span>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block">التشخيص الطبي (ICD-10)</small>
                    @php $diag = is_string($request->visit->diagnosis) ? json_decode($request->visit->diagnosis, true) : $request->visit->diagnosis; @endphp
                    @if(is_array($diag) && !empty($diag['code']))
                        <span class="badge bg-primary-subtle text-primary">{{ $diag['code'] }}</span>
                        <span class="text-dark small ms-1">{{ $diag['description'] ?? '' }}</span>
                    @else
                        <span class="text-muted small">غير مدخل</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

<script>
function completeAndSave() {
    const statusSelect = document.getElementById('status');
    if (statusSelect) {
        statusSelect.value = 'completed';
    }
    document.getElementById('labResultForm').submit();
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
