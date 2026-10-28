@extends('layouts.app')

@php
use App\Models\LabTest;
use App\Models\LabTestResult;

// الحصول على جميع التحاليل النشطة للاستخدام في القوائم و JavaScript
$labTests = LabTest::active()->get()->keyBy('name');

if (!function_exists('getEmergencyTestIcon')) {
    function getEmergencyTestIcon($testName) {
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
}

if (!function_exists('getEmergencyTestUnit')) {
    function getEmergencyTestUnit($testName, $labTests) {
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
}

if (!function_exists('normalizeEmergencyTestIds')) {
    function normalizeEmergencyTestIds($testIds) {
        if (empty($testIds)) return [];
        if (is_string($testIds)) {
            $decoded = json_decode($testIds, true);
            if (is_array($decoded)) return $decoded;
            return array_filter(array_map('trim', explode(',', $testIds)), fn($item) => $item !== '');
        }
        if (is_array($testIds)) return $testIds;
        return [];
    }
}

if (!function_exists('normalizeEmergencyTestsField')) {
    function normalizeEmergencyTestsField($tests) {
        if (empty($tests)) return [];
        if (is_string($tests)) {
            $decoded = json_decode($tests, true);
            if (is_array($decoded)) return $decoded;
            return array_filter(array_map('trim', explode(',', $tests)), fn($item) => $item !== '');
        }
        if (is_array($tests)) return $tests;
        return [];
    }
}

$requestDetails = is_string($emergencyLab->details) ? json_decode($emergencyLab->details, true) : ($emergencyLab->details ?? []);
if (!is_array($requestDetails)) $requestDetails = [];

$hasAttachment = !empty($requestDetails['attachment']);
$attachmentUrl = $hasAttachment ? asset('storage/' . $requestDetails['attachment']) : '';
$isImageAttachment = $hasAttachment && str_starts_with($requestDetails['attachment_mime'] ?? '', 'image/');
$testAttachments = is_array($requestDetails['test_attachments'] ?? null) ? $requestDetails['test_attachments'] : [];
$hasAnyAttachment = $hasAttachment || count($testAttachments) > 0;

$patient = $emergencyLab->patient;
$patientUser = $patient?->user;
$emergency = $emergencyLab->emergency;
$doctorUser = $emergency?->doctor?->user;
$patientPhone = $emergencyLab->getPatientPhone();
$gender = $patientUser?->gender ?? ($patient?->gender ?? 'male');
$age = $patient?->age ?? null;

// تجميع قائمة التحاليل المعتمدة للطلب
$testsList = $emergencyLab->labTests->pluck('name')->toArray();
if (empty($testsList) && !empty($requestDetails)) {
    if (!empty($requestDetails['package_id'])) {
        $pkg = \App\Models\Package::find($requestDetails['package_id']);
        if ($pkg) {
            $testsList = array_merge($testsList, $pkg->labTests->pluck('name')->toArray());
        }
    }
    if (!empty($requestDetails['lab_test_ids'])) {
        $ids = normalizeEmergencyTestIds($requestDetails['lab_test_ids']);
        foreach ($ids as $tId) {
            $lt = \App\Models\LabTest::find($tId);
            if ($lt) $testsList[] = trim($lt->name);
        }
    }
    if (isset($requestDetails['tests'])) {
        $tests = normalizeEmergencyTestsField($requestDetails['tests']);
        foreach ($tests as $tName) {
            $testsList[] = trim((string)$tName);
        }
    }
}
$testsList = array_values(array_unique(array_filter($testsList, fn($t) => !empty(trim((string)$t)))));

// خريطة أسماء بديلة واختصارات شائعة لربط التحاليل بدقة
$codeAliases = [
    'CBC' => 'CBC_3DIFF',
];
$normalizedSearchTerms = array_values(array_unique(array_merge(
    $testsList,
    array_map(fn($t) => $codeAliases[$t] ?? $t, $testsList)
)));

$rawLabTests = \App\Models\LabTest::with(['subTests' => function($q) {
    $q->orderBy('sort_order')->orderBy('id');
}, 'references'])->where(function($query) use ($normalizedSearchTerms) {
    $query->whereIn('name', $normalizedSearchTerms)
          ->orWhereIn('code', $normalizedSearchTerms);
})
->orderByRaw('is_active DESC')
->get();

$labTestMap = collect();
foreach ($rawLabTests as $ltItem) {
    if (!isset($labTestMap[$ltItem->name]) || $ltItem->is_active) {
        $labTestMap[$ltItem->name] = $ltItem;
    }
    if ($ltItem->code && (!isset($labTestMap[$ltItem->code]) || $ltItem->is_active)) {
        $labTestMap[$ltItem->code] = $ltItem;
    }
}
foreach ($codeAliases as $alias => $targetCode) {
    if (isset($labTestMap[$targetCode]) && !isset($labTestMap[$alias])) {
        $labTestMap[$alias] = $labTestMap[$targetCode];
    }
}

// استخراج النتائج المحفوظة مسبقاً
$savedTestResults = is_array($requestDetails['test_results'] ?? null) ? $requestDetails['test_results'] : [];
$requestTestsKeyed = $emergencyLab->requestTests->keyBy('lab_test_id');

$savedNotes = '';
if (!empty($emergencyLab->notes)) {
    $savedNotes = $emergencyLab->notes;
} elseif (isset($requestDetails['notes']) && is_string($requestDetails['notes'])) {
    $savedNotes = $requestDetails['notes'];
}
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
                                طلب تحاليل طوارئ #{{ $emergency->id }}
                            </h5>
                            <span class="badge {{ $emergencyLab->priority === 'critical' ? 'bg-danger' : 'bg-warning text-dark' }} py-1 px-2 fw-bold">
                                <i class="fas {{ $emergencyLab->priority === 'critical' ? 'fa-fire' : 'fa-bolt' }} me-1"></i> {{ $emergencyLab->priority_text }}
                            </span>
                            @if($emergencyLab->status === 'in_progress')
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-2" title="الطلب محفوظ كمسودة قيد الفحص بالمختبر ولم يُرسل للطبيب بعد">
                                    <i class="fas fa-edit me-1 text-warning"></i> مسودة قيد الفحص
                                </span>
                            @elseif($emergencyLab->status === 'completed')
                                <span class="badge bg-success py-1 px-2">
                                    <i class="fas fa-check-circle me-1"></i> مكتمل ومعتمد
                                </span>
                            @else
                                <span class="badge bg-secondary py-1 px-2">
                                    <i class="fas fa-hourglass-half me-1"></i> بانتظار الفحص
                                </span>
                            @endif
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i> {{ $emergencyLab->requested_at ? $emergencyLab->requested_at->format('Y-m-d H:i') : $emergencyLab->created_at->format('Y-m-d H:i') }}
                        </small>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if(count($testsList) > 0 || $emergencyLab->status === 'completed')
                        <a href="{{ route('staff.emergency-lab.print', $emergencyLab) }}" target="_blank" class="btn btn-outline-dark fw-bold shadow-sm px-3" title="طباعة قائمة التحاليل المطلوبة / تقرير النتائج">
                            <i class="fas fa-print me-1 text-primary"></i> طباعة {{ $emergencyLab->status === 'completed' ? 'التقرير' : 'طلب التحاليل' }}
                        </a>
                        @php
                            $waShareUrl = $emergencyLab->getWhatsAppShareUrl();
                        @endphp
                        @if($waShareUrl)
                            <a href="{{ $waShareUrl }}" target="_blank" class="btn fw-bold shadow-sm px-3 text-white" style="background-color: #25d366; border-color: #20ba5a;" title="إرسال رابط التقرير الطبي المعتمد للمريض عبر واتساب">
                                <i class="fab fa-whatsapp me-1"></i> إرسال النتيجة عبر واتساب
                            </a>
                        @endif
                    @endif

                    <button type="submit" form="labResultForm" name="action" value="draft" onclick="submitLabForm('draft')" class="btn btn-warning text-dark fw-bold shadow-sm px-3 border-warning" title="حفظ النتائج والمدخلات كمسودة في المختبر دون إرسالها للطبيب">
                        <i class="fas fa-save me-1"></i> حفظ مؤقت (مسودة)
                    </button>

                    <button type="submit" form="labResultForm" name="action" value="complete" onclick="submitLabForm('complete')" class="btn btn-success fw-bold shadow-sm px-3" title="اعتماد النتائج نهائياً وإرسالها لطبيب الطوارئ">
                        <i class="fas fa-paper-plane me-1"></i> اعتماد نهائي وإرسال للطبيب
                    </button>

                    <a href="#appendTestsSection" data-bs-toggle="collapse" class="btn btn-outline-secondary" title="إلحاق تحاليل أو باقات إضافية لطلب الطوارئ هذا">
                        <i class="fas fa-plus-circle me-1"></i> إضافة تحاليل
                    </a>

                    <a href="{{ route('lab.index', ['source' => 'emergency']) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> القائمة
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
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
                            ملف: #{{ $patient?->national_id ?: ($patient?->id ?? $emergencyLab->patient_id) }}
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
                                    <strong class="text-dark fs-6">{{ $patientUser?->name ?? ($patient?->name ?? 'غير محدد') }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- رقم الهاتف -->
                        <div class="col-xl-2 col-md-6 col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-success-subtle text-success rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">رقم الهاتف</small>
                                    @if($patientPhone)
                                        <div class="d-flex align-items-center gap-1">
                                            <a href="tel:{{ $patientPhone }}" class="text-dark fw-bold text-decoration-none font-monospace fs-6" dir="ltr" title="انقر للاتصال">
                                                {{ $patientPhone }}
                                            </a>
                                            @if($emergencyLab->getWhatsAppShareUrl())
                                                <a href="{{ $emergencyLab->getWhatsAppShareUrl() }}" target="_blank" class="badge rounded-pill text-white text-decoration-none px-2 py-1 ms-1 shadow-2xs" style="background-color: #25d366; font-size: 0.70rem;" title="مراسلة المريض عبر واتساب">
                                                    <i class="fab fa-whatsapp"></i> واتساب
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">غير مسجل</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- العمر والجنس -->
                        <div class="col-xl-2 col-md-4 col-6">
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
                        <div class="col-xl-1 col-md-2 col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-danger-subtle text-danger rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-tint"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">الفصيلة</small>
                                    <strong class="text-danger fw-bold">{{ $patient?->blood_type ?: '—' }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- الطبيب المرسل والعيادة -->
                        <div class="col-xl-2 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-subtle text-primary rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">الطبيب والعيادة</small>
                                    <strong class="text-dark">
                                        {{ $doctorUser?->name ? 'د. ' . $doctorUser->name : 'طبيب الطوارئ' }}
                                    </strong>
                                    <small class="text-muted d-block">قسم الطوارئ (STAT)</small>
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
                                    @php
                                        $emergencyPayment = $emergency?->payment ?? $emergency?->payments?->first();
                                        $isPaid = ($emergencyPayment && $emergencyPayment->status === 'completed') || ($emergency?->is_insured && $patient?->copay_percentage == 0);
                                    @endphp
                                    @if($isPaid)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> مسدد بالكامل
                                        </span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                                            <i class="fas fa-bolt me-1"></i> طوارئ (حساب موحد)
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($emergencyLab->notes) || !empty($emergency?->doctor_notes))
        <div class="alert alert-warning bg-warning bg-opacity-10 border-warning border-opacity-25 rounded-3 mb-3 p-3 shadow-xs">
            <div class="d-flex align-items-center gap-2">
                <div class="badge bg-warning text-dark p-2 rounded-circle fs-6">
                    <i class="fas fa-sticky-note"></i>
                </div>
                <div>
                    <h6 class="fw-bold text-dark mb-1">
                        <i class="fas fa-user-md text-warning me-1"></i> ملاحظات وتوجيهات الطبيب المحول:
                    </h6>
                    <div class="text-dark fw-semibold fs-6">
                        {{ $emergencyLab->notes ?: $emergency?->doctor_notes }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 3. قسم إضافة تحاليل إضافية (Append Tests Collapsible) -->
    @php
        $showAppendSection = request()->query('append') == '1';
        $currentTestIds = $emergencyLab->labTests->pluck('id')->toArray();
        $favorites = \App\Models\UserLabTestStat::getFavoritesForUser(auth()->id());
        $packages = $packages ?? \App\Models\Package::where('is_active', true)->with('labTests')->orderBy('name')->get();
        $userGroups = $userGroups ?? \App\Models\UserLabTestGroup::where('user_id', auth()->id())->with('labTests')->orderBy('name')->get();
    @endphp

    <div class="row mb-3 collapse {{ ($showAppendSection || count($testsList) == 0) ? 'show' : '' }}" id="appendTestsSection">
        <div class="col-12">
            <div class="card shadow-sm border border-primary-subtle bg-white">
                <div class="card-header bg-primary-subtle text-primary border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>{{ count($testsList) == 0 ? 'تحديد واختيار التحاليل المطلوبة لهذا الطلب' : 'إضافة تحاليل إضافية إلى هذا الطلب (إلحاق تحاليل أو باقات إضافية لطلب الطوارئ هذا)' }}</h6>
                    <button type="button" data-bs-toggle="collapse" data-bs-target="#appendTestsSection" class="btn btn-outline-secondary btn-sm py-0 px-2">
                        <i class="fas fa-times me-1"></i> إغلاق
                    </button>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('staff.emergency-lab.append-tests', $emergencyLab) }}" method="POST" id="appendLabTestsForm">
                        @csrf
                        <input type="hidden" name="package_id" id="selectedPackageId" value="">

                        <div class="alert alert-info py-2 px-3 mb-3 small">
                            <i class="fas fa-info-circle me-1"></i>
                            {{ count($testsList) == 0 ? 'هذا الطلب بانتظار تحديد الفحوصات. يمكنك اختيار باقة طبية كاملة (بكج) أو اختيار من مجموعات المفضلات أو تحديد الفحوصات يدوياً ثم الضغط على زر الحفظ.' : 'سيتم إدراج التحاليل المختارة أو الباقة الطبية إلى طلب الطوارئ الحالي مباشرة وتحديث الكاشير دون حذف أي من التحاليل المسجلة مسبقاً.' }}
                        </div>

                        {{-- 1. قسم باقات المختبر الطبية (البكجات Packages) --}}
                        @if(isset($packages) && $packages->count() > 0)
                        <div class="mb-3 p-3 bg-light rounded border border-primary border-opacity-25 shadow-xs">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold small text-primary d-flex align-items-center gap-1">
                                    <i class="fas fa-boxes text-primary"></i> باقات المختبر الطبية (البكجات Packages):
                                </span>
                                <span class="text-muted small" style="font-size: 0.72rem;">اختر باقة لإضافة تحاليلها وسعرها دفعة واحدة</span>
                            </div>
                            <!-- عرض الباقات في صف واحد أفقي أنيق -->
                            <div class="d-flex flex-nowrap gap-2 overflow-auto py-1 px-1 mb-2" style="scrollbar-width: thin;">
                                @foreach($packages as $pkg)
                                    @php
                                        $pkgTestIds = $pkg->labTests->pluck('id')->toArray();
                                    @endphp
                                    <div class="card flex-shrink-0 border package-card p-2 position-relative bg-white shadow-xs"
                                         data-package-id="{{ $pkg->id }}"
                                         data-package-name="{{ $pkg->name }}"
                                         data-test-ids="{{ json_encode($pkgTestIds) }}"
                                         style="width: 230px; cursor: pointer; transition: all 0.2s ease; border-radius: 8px;">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <strong class="text-dark small text-truncate" title="{{ $pkg->name }}" style="max-width: 140px;">
                                                <i class="fas fa-box text-primary me-1"></i>{{ $pkg->name }}
                                            </strong>
                                            <span class="badge bg-primary text-white" style="font-size: 0.68rem;">{{ count($pkgTestIds) }} تحاليل</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-auto pt-1 border-top">
                                            <span class="fw-bold text-success small" style="font-size: 0.8rem;">{{ number_format($pkg->price) }} IQD</span>
                                            <button type="button" class="btn btn-xs btn-outline-primary py-0 px-2 fw-bold select-package-btn" style="font-size: 0.7rem;">
                                                <i class="fas fa-check me-1"></i> اختيار
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div id="selectedPackageAlert" class="alert alert-success py-1 px-3 mt-1 mb-2 small d-none d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-check-circle me-1"></i> تم اختيار باقة: <strong id="selectedPackageName"></strong> وتم تفعيل تحاليلها في القائمة.</span>
                                <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2" id="clearSelectedPackage">إلغاء الباقة</button>
                            </div>
                        </div>
                        @endif

                        {{-- 2. قسم مجموعات المفضلات (User Lab Test Groups) --}}
                        @if(isset($userGroups) && $userGroups->count() > 0)
                        <div class="mb-3">
                            <span class="fw-bold small text-dark d-block mb-1">
                                <i class="fas fa-layer-group text-warning me-1"></i> مجموعات المفضلات الخاصة بي:
                            </span>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($userGroups as $group)
                                    @php
                                        $grpTestIds = $group->labTests->pluck('id')->toArray();
                                    @endphp
                                    <button type="button" class="btn btn-sm btn-outline-warning text-dark py-1 px-2 group-select-btn" 
                                            data-test-ids="{{ json_encode($grpTestIds) }}" 
                                            style="font-size: 0.8rem;" 
                                            title="تحديد كافة تحاليل هذه المجموعة">
                                        <i class="fas fa-star text-warning me-1"></i>{{ $group->name }}
                                        <span class="badge bg-secondary-subtle text-dark ms-1">({{ count($grpTestIds) }})</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @php
                            $favoriteIds = $favorites->pluck('lab_test_id')->toArray();
                        @endphp

                        @if($favorites->isNotEmpty())
                        <div class="mb-3">
                            <span class="fw-bold small text-dark d-block mb-1"><i class="fas fa-star text-warning me-1"></i> مفضلاتي الفردية:</span>
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
                                $allLabTestsGrouped = \App\Models\LabTest::where('is_active', true)
                                    ->orderBy('main_category')->orderBy('name')
                                    ->get()->groupBy('main_category');
                            @endphp
                            @foreach($allLabTestsGrouped as $cat => $tests)
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

    <!-- 4. النموذج الرئيسي لإدخال ومعالجة النتائج ورفع التقارير -->
    @php
        $patientGender = $gender ?? 'both';
        $patientAge    = (int) ($age ?? 0);
    @endphp

    <form action="{{ route('staff.emergency-lab.complete', $emergencyLab) }}" method="POST" enctype="multipart/form-data" id="labResultForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="from_station" value="1">
        <input type="hidden" name="status" value="{{ $emergencyLab->status === 'completed' ? 'completed' : 'in_progress' }}" id="labFormStatus">
        <input type="hidden" name="action" value="complete" id="labFormAction">

        <div class="row g-3 mb-3">
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
                                            <i class="fas fa-paperclip text-primary me-1"></i> تقرير الجهاز / المرفق
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($testsList as $index => $test)
                                        @php
                                            $testIcon   = getEmergencyTestIcon($test);
                                            $labTestObj = $labTestMap[$test] ?? null;
                                            $hasSubTests = $labTestObj && $labTestObj->subTests->count() > 0;
                                            
                                            $rt = $labTestObj ? $requestTestsKeyed->get($labTestObj->id) : null;
                                            $hasSpecificAttachment = !empty($rt?->image_path) || !empty($testAttachments[$test]);
                                            
                                            // تحديد نمط إدخال النتائج
                                            $entryType = $labTestObj?->result_entry_type;
                                            if (empty($entryType)) {
                                                $entryType = $hasSubTests ? \App\Models\LabTest::ENTRY_TYPE_SUBTESTS : \App\Models\LabTest::ENTRY_TYPE_SINGLE;
                                            }
                                        @endphp

                                        @if($entryType === \App\Models\LabTest::ENTRY_TYPE_ATTACHMENT || ($entryType === \App\Models\LabTest::ENTRY_TYPE_SUBTESTS && !$hasSubTests))
                                            {{-- فحص يعتمد حصراً على تقرير الجهاز المرفق --}}
                                            <tr class="table-info bg-opacity-10">
                                                <td class="text-center text-muted small fw-bold">{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="{{ $testIcon }}"></i>
                                                        <strong class="text-dark">{{ $test }}</strong>
                                                        @if($hasSubTests)
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" style="font-size: 0.72rem;">
                                                                <i class="fas fa-layer-group me-1"></i> فحص مركب ({{ $labTestObj->subTests->count() }} فرعي)
                                                            </span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle ms-1" style="font-size: 0.72rem;">
                                                                <i class="fas fa-file-medical-alt me-1"></i> مرفق تقرير جهاز
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <small class="text-muted d-block mt-1">
                                                        <i class="fas fa-info-circle me-1"></i> تعتمد قراءة نتائج هذا الفحص على التقرير أو الرسم البياني المرفق
                                                    </small>
                                                </td>
                                                <td colspan="4">
                                                    <div class="d-flex align-items-center gap-2 text-muted small">
                                                        <i class="fas fa-arrow-left"></i> يرجى إرفاق تقرير الجهاز الخاص بهذا الفحص في الخانة المجاورة
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    @if($hasSpecificAttachment)
                                                        @php
                                                            $attUrl = !empty($rt?->image_path)
                                                                ? route('staff.emergency-lab.attachment', ['emergencyLab' => $emergencyLab, 'testId' => $labTestObj?->id])
                                                                : (isset($testAttachments[$test]) ? asset('storage/' . $testAttachments[$test]) : '');
                                                        @endphp
                                                        <div class="d-inline-flex align-items-center gap-1 bg-white border border-primary-subtle rounded-pill p-1 shadow-sm">
                                                            <a href="{{ $attUrl }}" target="_blank" class="btn btn-xs btn-primary-subtle text-primary rounded-pill py-1 px-2.5 d-inline-flex align-items-center gap-1 text-decoration-none fw-bold" style="font-size: 0.72rem;" title="معاينة التقرير المرفق">
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
                                        @elseif(($entryType === \App\Models\LabTest::ENTRY_TYPE_SUBTESTS || $entryType === \App\Models\LabTest::ENTRY_TYPE_FLEXIBLE) && $hasSubTests)
                                            {{-- فحص يحتوي قيماً فرعية مفصلة --}}
                                            <tr class="table-secondary bg-opacity-25 border-top border-bottom">
                                                <td class="text-center fw-bold">{{ $index + 1 }}</td>
                                                <td colspan="5">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="{{ $testIcon }} text-primary"></i>
                                                        <strong class="text-dark fs-6">{{ $test }}</strong>
                                                        <span class="badge" style="background-color: #6f42c1; color: white; font-size: 0.72rem;">
                                                            <i class="fas fa-list-ol me-1"></i> قيم فرعية ({{ $labTestObj->subTests->count() }} باراميتر)
                                                        </span>
                                                        @if($entryType === \App\Models\LabTest::ENTRY_TYPE_FLEXIBLE)
                                                            <span class="badge bg-success-subtle text-success border border-success ms-1" style="font-size: 0.7rem;">
                                                                <i class="fas fa-random me-1"></i> نمط مرن (إدخال قيم + مرفق تقرير)
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    @if($hasSpecificAttachment)
                                                        @php
                                                            $attUrl = !empty($rt?->image_path)
                                                                ? route('staff.emergency-lab.attachment', ['emergencyLab' => $emergencyLab, 'testId' => $labTestObj?->id])
                                                                : (isset($testAttachments[$test]) ? asset('storage/' . $testAttachments[$test]) : '');
                                                        @endphp
                                                        <div class="d-inline-flex align-items-center gap-1 bg-white border border-primary-subtle rounded-pill p-1 shadow-sm">
                                                            <a href="{{ $attUrl }}" target="_blank" class="btn btn-xs btn-primary-subtle text-primary rounded-pill py-1 px-2.5 d-inline-flex align-items-center gap-1 text-decoration-none fw-bold" style="font-size: 0.72rem;" title="معاينة التقرير المرفق">
                                                                <i class="fas fa-paperclip"></i>
                                                                <span>معاينة التقرير</span>
                                                            </a>
                                                            <label class="btn btn-xs btn-outline-danger rounded-circle p-0 mb-0 d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px; cursor: pointer;" title="حذف هذا المرفق عند الحفظ">
                                                                <input type="checkbox" name="remove_test_attachment[{{ $test }}]" value="1" class="d-none" onchange="this.checked ? (this.closest('label').classList.add('active', 'btn-danger', 'text-white'), this.closest('.d-inline-flex').classList.add('border-danger', 'bg-danger-subtle')) : (this.closest('label').classList.remove('active', 'btn-danger', 'text-white'), this.closest('.d-inline-flex').classList.remove('border-danger', 'bg-danger-subtle'))">
                                                                <i class="fas fa-times" style="font-size: 0.65rem;"></i>
                                                            </label>
                                                        </div>
                                                    @elseif($entryType === \App\Models\LabTest::ENTRY_TYPE_FLEXIBLE)
                                                        <div class="pill-upload-wrapper d-inline-block">
                                                            <label class="btn btn-sm btn-outline-primary rounded-pill py-1 px-2.5 mb-0 d-inline-flex align-items-center gap-1.5 shadow-sm pill-upload-btn" style="cursor: pointer; font-size: 0.75rem; border-width: 1.5px;" title="إرفاق تقرير الجهاز الخاص بهذا الفحص">
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
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            {{-- صفوف الباراميترات الفرعية --}}
                                            @foreach($labTestObj->subTests as $subIdx => $subTest)
                                                @php
                                                    $subKey = 'sub_' . $subTest->id;
                                                    $savedSubVal = '';
                                                    if (isset($savedTestResults[$subKey])) {
                                                        $savedSubVal = is_array($savedTestResults[$subKey]) ? ($savedTestResults[$subKey]['value'] ?? '') : $savedTestResults[$subKey];
                                                    } elseif (isset($savedTestResults[$subTest->name])) {
                                                        $resItem = $savedTestResults[$subTest->name];
                                                        $savedSubVal = is_array($resItem) ? ($resItem['value'] ?? '') : $resItem;
                                                    } elseif ($rt) {
                                                        $rawRt = $rt->result;
                                                        $parsedRt = is_string($rawRt) ? json_decode($rawRt, true) : $rawRt;
                                                        if (is_array($parsedRt) && isset($parsedRt[$subKey])) {
                                                            $savedSubVal = is_array($parsedRt[$subKey]) ? ($parsedRt[$subKey]['value'] ?? '') : $parsedRt[$subKey];
                                                        }
                                                    }
                                                @endphp
                                                <tr class="test-row subtest-item-row" data-test="{{ $subTest->name }}"
                                                    data-ref-min="{{ $subTest->ref_min }}"
                                                    data-ref-max="{{ $subTest->ref_max }}"
                                                    style="background-color: rgba(248, 249, 250, 0.7);">
                                                    <td class="text-center text-muted small font-monospace" style="font-size: 0.75rem;">
                                                        {{ $index + 1 }}.{{ $subIdx + 1 }}
                                                    </td>
                                                    <td class="ps-4">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="text-muted small"><i class="fas fa-level-down-alt fa-rotate-90 text-primary"></i></span>
                                                            <span class="fw-semibold text-dark">{{ $subTest->name }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                               class="form-control form-control-sm test-value font-monospace fw-bold"
                                                               name="test_results[{{ $subKey }}][value]"
                                                               value="{{ old('test_results.' . $subKey . '.value', old('test_results.' . $subTest->name . '.value', $savedSubVal)) }}"
                                                               placeholder="أدخل النتيجة..."
                                                               data-test="{{ $subTest->name }}">
                                                        <input type="hidden" name="test_results[{{ $subKey }}][test_name]" value="{{ $subTest->name }}">
                                                        <input type="hidden" name="test_results[{{ $subKey }}][parent_test_name]" value="{{ $test }}">
                                                        <input type="hidden" name="test_results[{{ $subKey }}][sub_test_id]" value="{{ $subTest->id }}">
                                                        <input type="hidden" name="test_results[{{ $subKey }}][lab_test_id]" value="{{ $labTestObj->id }}">
                                                        <input type="hidden" name="test_results[{{ $subKey }}][unit]" value="{{ $subTest->unit }}">
                                                        <input type="hidden" name="test_results[{{ $subKey }}][reference_range]" value="{{ $subTest->reference_range }}">
                                                    </td>
                                                    <td class="text-muted small fw-semibold">{{ $subTest->unit ?: '-' }}</td>
                                                    <td>
                                                        @if(!empty($subTest->reference_range))
                                                            <span class="badge bg-white text-dark border font-monospace">{{ $subTest->reference_range }}</span>
                                                        @else
                                                            <span class="text-muted small">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="result-flag">
                                                            <i class="fas fa-circle text-muted small"></i>
                                                        </span>
                                                    </td>
                                                    <td class="text-center text-muted small" style="font-size: 0.72rem;">
                                                        —
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            {{-- فحص رقمي فردي --}}
                                            @php
                                                $refObj = $labTestObj
                                                    ? \App\Models\LabTestReference::forPatient($labTestObj->id, $patientGender, $patientAge)
                                                    : null;
                                                $refDisplay  = $refObj ? $refObj->range_display : '—';
                                                $refMin      = $refObj?->ref_min;
                                                $refMax      = $refObj?->ref_max;
                                                $unitDisplay = $refObj?->unit ?? getEmergencyTestUnit($test, $labTests);
                                                
                                                // القيمة المحفوظة مسبقاً
                                                $savedVal = '';
                                                if ($rt) {
                                                    $rawRt = $rt->result;
                                                    $parsedRt = is_string($rawRt) ? json_decode($rawRt, true) : $rawRt;
                                                    if (is_array($parsedRt)) {
                                                        $savedVal = $parsedRt['value'] ?? '';
                                                    } else {
                                                        $savedVal = (string) $rawRt;
                                                    }
                                                }
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
                                                            <a href="{{ !empty($rt?->image_path) ? route('staff.emergency-lab.attachment', ['emergencyLab' => $emergencyLab, 'testId' => $labTestObj?->id]) : asset('storage/' . ($testAttachments[$test] ?? '')) }}" target="_blank" class="badge bg-info-subtle text-info border border-info ms-1 text-decoration-none" title="يوجد تقرير منفصل لهذا الفحص">
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
                                                        @php
                                                            $attUrl = !empty($rt?->image_path)
                                                                ? route('staff.emergency-lab.attachment', ['emergencyLab' => $emergencyLab, 'testId' => $labTestObj?->id])
                                                                : (isset($testAttachments[$test]) ? asset('storage/' . $testAttachments[$test]) : '');
                                                        @endphp
                                                        <div class="d-inline-flex align-items-center gap-1 bg-white border border-primary-subtle rounded-pill p-1 shadow-sm">
                                                            <a href="{{ $attUrl }}" target="_blank" class="btn btn-xs btn-primary-subtle text-primary rounded-pill py-1 px-2.5 d-inline-flex align-items-center gap-1 text-decoration-none fw-bold" style="font-size: 0.72rem;" title="معاينة التقرير المرفق">
                                                                <i class="fas fa-paperclip"></i>
                                                                <span>معاينة التقرير</span>
                                                            </a>
                                                            <label class="btn btn-xs btn-outline-danger rounded-circle p-0 mb-0 d-inline-flex align-items-center justify-content-center" style="width: 22px; height: 22px; cursor: pointer;" title="حذف هذا المرفق عند الحفظ">
                                                                <input type="checkbox" name="remove_test_attachment[{{ $test }}]" value="1" class="d-none" onchange="this.checked ? (this.closest('label').classList.add('active', 'btn-danger', 'text-white'), this.closest('.d-inline-flex').classList.add('border-danger', 'bg-danger-subtle')) : (this.closest('label').classList.remove('active', 'btn-danger', 'text-white'), this.closest('.d-inline-flex').classList.remove('border-danger', 'bg-danger-subtle'))">
                                                                <i class="fas fa-times" style="font-size: 0.65rem;"></i>
                                                            </label>
                                                        </div>
                                                    @elseif($entryType === \App\Models\LabTest::ENTRY_TYPE_FLEXIBLE || true)
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
                                                    @else
                                                        <span class="text-muted small">—</span>
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
                                                <a href="{{ route('staff.emergency-lab.show', ['emergencyLab' => $emergencyLab, 'append' => 1]) }}#appendTestsSection" class="btn btn-primary btn-sm px-3 shadow-sm fw-bold">
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

        <!-- شريط أزرار الحفظ والاعتماد السفلي -->
        <div class="card shadow-sm border-0 bg-white rounded-3 p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    @if($emergencyLab->status === 'in_progress')
                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-2 px-3 fs-6">
                            <i class="fas fa-info-circle me-1 text-warning"></i> هذا الطلب محفوظ كـ <strong>مسودة قيد الفحص بالمختبر</strong> (لم يُرسل للطبيب بعد)
                        </span>
                    @elseif($emergencyLab->status === 'completed')
                        <span class="badge bg-success-subtle text-success border border-success-subtle py-2 px-3 fs-6">
                            <i class="fas fa-check-double me-1 text-success"></i> هذا الطلب <strong>معتمد ومكتمل ومُرسل لطبيب الطوارئ</strong>
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary py-2 px-3 fs-6">
                            <i class="fas fa-clock me-1"></i> حالة الطلب الحالية: {{ $emergencyLab->status_text }}
                        </span>
                    @endif
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" name="action" value="draft" onclick="submitLabForm('draft')" class="btn btn-warning text-dark fw-bold shadow-sm px-4 py-2 border-warning" title="حفظ النتائج والمدخلات كمسودة في المختبر دون إرسالها للطبيب">
                        <i class="fas fa-save me-1"></i> حفظ مؤقت (مسودة)
                    </button>

                    <button type="submit" name="action" value="complete" onclick="submitLabForm('complete')" class="btn btn-success fw-bold shadow-sm px-4 py-2" title="اعتماد النتائج نهائياً وإرسالها لطبيب الطوارئ">
                        <i class="fas fa-paper-plane me-1"></i> اعتماد نهائي وإرسال للطبيب
                    </button>
                </div>
            </div>
        </div>

    </form>

</div>

<script>
// ──────────────── دالة ضبط نوع الإجراء (مسودة أو اعتماد نهائي) ────────────────
function submitLabForm(actionType) {
    const actionInput = document.getElementById('labFormAction');
    const statusInput = document.getElementById('labFormStatus');
    
    if (actionType === 'draft') {
        if (actionInput) actionInput.value = 'draft';
        if (statusInput) statusInput.value = 'in_progress';
    } else {
        if (actionInput) actionInput.value = 'complete';
        if (statusInput) statusInput.value = 'completed';
    }
}

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

// ──────────────── الحساب الآلي الذكي لمعادلات باقة الدهون (Lipid Profile - Friedewald Eq) ────────────────
function getNormalizedTestName(row) {
    if (!row) return '';
    const input = row.querySelector('.test-value');
    let name = (row.dataset.test || (input ? input.dataset.test : '') || '').toLowerCase().trim();
    if (!name) {
        const titleEl = row.querySelector('td:nth-child(2)');
        name = (titleEl ? titleEl.textContent : '').toLowerCase().trim();
    }
    return name;
}

function attachCalculatedBadge(row, text, type = 'info') {
    if (!row) return;
    const titleCell = row.querySelector('td:nth-child(2) .d-flex');
    if (!titleCell) return;
    let badge = titleCell.querySelector('.lipid-calc-badge');
    if (!badge) {
        badge = document.createElement('span');
        badge.className = `badge bg-${type}-subtle text-${type} border border-${type}-subtle rounded-pill ms-2 lipid-calc-badge shadow-xs`;
        badge.style.fontSize = '0.70rem';
        badge.style.fontWeight = 'bold';
        titleCell.appendChild(badge);
    }
    badge.className = `badge bg-${type}-subtle text-${type} border border-${type}-subtle rounded-pill ms-2 lipid-calc-badge shadow-xs`;
    badge.innerHTML = `<i class="fas fa-calculator me-1"></i> ${text}`;
}

function runLipidProfileCalculations() {
    let tgRow = null, tcRow = null, hdlRow = null, ldlRow = null, vldlRow = null;

    document.querySelectorAll('.test-row').forEach(row => {
        const name = getNormalizedTestName(row);
        if (!name) return;

        // مطابقة الدهون الثلاثية Triglycerides
        if (name.includes('triglyceride') || name === 'tg' || name.includes('دهون ثلاثية')) {
            tgRow = row;
        }
        // مطابقة الكوليسترول الكلي Total Cholesterol (مع استبعاد HDL, LDL, VLDL)
        else if ((name.includes('cholesterol') || name === 'tc' || name.includes('كوليسترول') || name.includes('كولسترول')) 
                 && !name.includes('hdl') && !name.includes('ldl') && !name.includes('vldl') 
                 && !name.includes('high') && !name.includes('low') && !name.includes('عالي') && !name.includes('منخفض')) {
            tcRow = row;
        }
        // مطابقة الكوليسترول عالي الكثافة HDL
        else if (name.includes('hdl') || (name.includes('high') && name.includes('density')) || name.includes('عالي الكثافة') || name.includes('عالية الكثافة') || name.includes('نافع')) {
            hdlRow = row;
        }
        // مطابقة VLDL
        else if (name.includes('vldl') || (name.includes('very') && name.includes('low')) || name.includes('منخفض الكثافة جدا') || name.includes('منخفضة الكثافة جدا')) {
            vldlRow = row;
        }
        // مطابقة LDL (مع استبعاد VLDL)
        else if ((name.includes('ldl') || (name.includes('low') && name.includes('density')) || name.includes('منخفض الكثافة') || name.includes('منخفضة الكثافة') || name.includes('ضار')) 
                 && !name.includes('vldl') && !name.includes('very') && !name.includes('جدا')) {
            ldlRow = row;
        }
    });

    const tgInput = tgRow ? tgRow.querySelector('.test-value') : null;
    const tcInput = tcRow ? tcRow.querySelector('.test-value') : null;
    const hdlInput = hdlRow ? hdlRow.querySelector('.test-value') : null;
    const ldlInput = ldlRow ? ldlRow.querySelector('.test-value') : null;
    const vldlInput = vldlRow ? vldlRow.querySelector('.test-value') : null;

    const tgVal = tgInput && tgInput.value.trim() !== '' ? parseFloat(tgInput.value.replace(/,/g, '')) : NaN;
    const tcVal = tcInput && tcInput.value.trim() !== '' ? parseFloat(tcInput.value.replace(/,/g, '')) : NaN;
    const hdlVal = hdlInput && hdlInput.value.trim() !== '' ? parseFloat(hdlInput.value.replace(/,/g, '')) : NaN;

    // 1. حساب VLDL المحسوب: VLDL = TG / 5
    if (vldlInput && !isNaN(tgVal) && tgVal >= 0) {
        if (vldlInput.dataset.manual !== '1' || vldlInput.value.trim() === '') {
            const calculatedVLDL = Math.round((tgVal / 5) * 10) / 10;
            vldlInput.value = calculatedVLDL;
            vldlInput.dataset.autoCalc = '1';
            attachCalculatedBadge(vldlRow, 'محسوب: TG / 5', 'primary');
            evaluateRow(vldlRow);
        }
    }

    // 2. التحقق من تنبيه فريدوالد السريري (TG >= 400 mg/dL)
    let alertBanner = document.getElementById('lipid-friedewald-alert');
    if (!isNaN(tgVal) && tgVal >= 400) {
        if (!alertBanner && (ldlRow || tgRow)) {
            const targetRow = ldlRow || tgRow;
            alertBanner = document.createElement('tr');
            alertBanner.id = 'lipid-friedewald-alert';
            alertBanner.className = 'table-warning border-warning';
            alertBanner.innerHTML = `
                <td colspan="7" class="py-2.5 px-3 bg-warning-subtle text-dark">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-warning text-dark fs-6 px-2 py-1 shadow-xs"><i class="fas fa-exclamation-triangle"></i> تنبيه سريري</span>
                            <strong class="text-dark small">
                                نسبة الدهون الثلاثية (${tgVal} mg/dL) مرتفعة وتساوي أو تتجاوز 400 mg/dL:
                            </strong>
                            <span class="text-muted small">
                                تصبح معادلة فريدوالد (Friedewald) غير دقيقة علمياً في حساب LDL، ويُفضّل الاعتماد على القياس المخبري المباشر (Direct LDL).
                            </span>
                        </div>
                        <span class="badge bg-white text-secondary border font-monospace small">Friedewald: TG ≥ 400 mg/dL</span>
                    </div>
                </td>
            `;
            targetRow.parentNode.insertBefore(alertBanner, targetRow.nextSibling);
        } else if (alertBanner) {
            alertBanner.style.display = '';
            const strongEl = alertBanner.querySelector('strong');
            if (strongEl) strongEl.textContent = `نسبة الدهون الثلاثية (${tgVal} mg/dL) مرتفعة وتساوي أو تتجاوز 400 mg/dL:`;
        }
    } else if (alertBanner) {
        alertBanner.style.display = 'none';
    }

    // 3. حساب LDL المحسوب: LDL = TC - HDL - (TG / 5)
    if (ldlInput && !isNaN(tcVal) && !isNaN(hdlVal) && !isNaN(tgVal) && tcVal > 0 && hdlVal > 0 && tgVal >= 0) {
        if (ldlInput.dataset.manual !== '1' || ldlInput.value.trim() === '') {
            if (tgVal < 400) {
                const calculatedLDL = Math.round((tcVal - hdlVal - (tgVal / 5)) * 10) / 10;
                ldlInput.value = calculatedLDL;
                ldlInput.dataset.autoCalc = '1';
                attachCalculatedBadge(ldlRow, 'محسوب: TC - HDL - (TG/5)', 'primary');
                evaluateRow(ldlRow);
            } else {
                attachCalculatedBadge(ldlRow, '⚠️ TG ≥ 400: يفضل Direct LDL', 'warning');
            }
        }
    }
}

function initializeLabResults() {
    document.querySelectorAll('.test-row .test-value').forEach((input) => {
        input.addEventListener('input', function () {
            if (this.dataset.autoCalc === '1' && document.activeElement === this) {
                this.dataset.manual = '1';
                this.dataset.autoCalc = '0';
                attachCalculatedBadge(this.closest('.test-row'), 'مدخل يدوياً', 'secondary');
            }
            evaluateRow(this.closest('.test-row'));
            runLipidProfileCalculations();
            updateSummary();
        });
        input.addEventListener('change', function () {
            evaluateRow(this.closest('.test-row'));
            runLipidProfileCalculations();
            updateSummary();
        });
        input.addEventListener('keyup', function () {
            evaluateRow(this.closest('.test-row'));
            runLipidProfileCalculations();
            updateSummary();
        });
        if (input.value.trim() !== '') {
            evaluateRow(input.closest('.test-row'));
        }
    });

    // تشغيل الحساب التلقائي للدهون عند فتح الصفحة
    runLipidProfileCalculations();
    updateSummary();
}

document.addEventListener('DOMContentLoaded', function() {
    initializeLabResults();

    // بحث سريع متقدم في إضافة التحاليل مع دعم الحروف العربية وإخفاء المجموعات الفارغة
    const appendSearch = document.getElementById('appendTestSearch');
    const clearAppend = document.getElementById('clearAppendSearch');

    function normalizeArabic(text) {
        if (!text) return '';
        return text
            .toString()
            .toLowerCase()
            .replace(/[أإآ]/g, 'ا')
            .replace(/ة/g, 'ه')
            .replace(/ى/g, 'ي')
            .trim();
    }

    if (appendSearch) {
        appendSearch.addEventListener('input', function() {
            const term = normalizeArabic(this.value);

            document.querySelectorAll('.append-test-group').forEach(group => {
                let hasVisible = false;
                group.querySelectorAll('.append-test-item').forEach(item => {
                    const label = normalizeArabic(item.textContent);
                    const match = !term || label.includes(term);
                    item.style.display = match ? '' : 'none';
                    if (match) hasVisible = true;
                });
                group.style.display = hasVisible ? '' : 'none';
            });
        });
    }

    if (clearAppend && appendSearch) {
        clearAppend.addEventListener('click', function() {
            appendSearch.value = '';
            appendSearch.dispatchEvent(new Event('input'));
            appendSearch.focus();
        });
    }

    // تفاعل اختيار الباقات الطبية (Packages)
    document.querySelectorAll('.package-card').forEach(card => {
        card.addEventListener('click', function() {
            const pkgId = this.getAttribute('data-package-id');
            const pkgName = this.getAttribute('data-package-name');
            const testIds = JSON.parse(this.getAttribute('data-test-ids') || '[]');

            document.querySelectorAll('.package-card').forEach(c => {
                c.classList.remove('border-primary', 'bg-primary-subtle', 'shadow');
                const btn = c.querySelector('.select-package-btn');
                if (btn) {
                    btn.classList.remove('btn-primary', 'text-white');
                    btn.classList.add('btn-outline-primary');
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> اختيار الباقة';
                }
            });

            this.classList.add('border-primary', 'bg-primary-subtle', 'shadow');
            const btn = this.querySelector('.select-package-btn');
            if (btn) {
                btn.classList.add('btn-primary', 'text-white');
                btn.classList.remove('btn-outline-primary');
                btn.innerHTML = '<i class="fas fa-check-circle me-1"></i> تم اختيارها';
            }

            document.getElementById('selectedPackageId').value = pkgId;
            const alertBox = document.getElementById('selectedPackageAlert');
            if (alertBox) {
                document.getElementById('selectedPackageName').textContent = pkgName;
                alertBox.classList.remove('d-none');
            }

            // تصفير أي تحديد سابق لضمان اقتصار التحديد على تحاليل الباقة المختارة بدقة
            document.querySelectorAll('input[name="extra_lab_test_ids[]"]').forEach(cb => {
                if (!cb.disabled) cb.checked = false;
            });

            // تفعيل وتحديد كافة تحاليل هذه الباقة في القائمة
            let activatedCount = 0;
            testIds.forEach(id => {
                const cb = document.getElementById('append_' + id);
                if (cb && !cb.disabled) {
                    cb.checked = true;
                    activatedCount++;
                }
            });
        });
    });

    // زر إلغاء اختيار الباقة
    const clearPkgBtn = document.getElementById('clearSelectedPackage');
    if (clearPkgBtn) {
        clearPkgBtn.addEventListener('click', function() {
            document.getElementById('selectedPackageId').value = '';
            document.querySelectorAll('.package-card').forEach(c => {
                c.classList.remove('border-primary', 'bg-primary-subtle', 'shadow');
                const btn = c.querySelector('.select-package-btn');
                if (btn) {
                    btn.classList.remove('btn-primary', 'text-white');
                    btn.classList.add('btn-outline-primary');
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> اختيار الباقة';
                }
            });
            document.getElementById('selectedPackageAlert').classList.add('d-none');
        });
    }

    // تفاعل اختيار مجموعات المفضلات (User Lab Test Groups)
    document.querySelectorAll('.group-select-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const testIds = JSON.parse(this.getAttribute('data-test-ids') || '[]');
            testIds.forEach(id => {
                const cb = document.getElementById('append_' + id);
                if (cb && !cb.disabled) {
                    cb.checked = true;
                }
            });
            this.classList.toggle('btn-warning');
            this.classList.toggle('btn-outline-warning');
        });
    });
});
</script>
@endsection
