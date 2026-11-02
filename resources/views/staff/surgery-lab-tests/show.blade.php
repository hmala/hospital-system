@extends('layouts.app')

@php
use App\Models\LabTest;
use App\Models\LabTestReference;
use App\Models\UserLabTestStat;

$surgery = $test->surgery;
$patient = $surgery?->patient;
$patientUser = $patient?->user;
$doctorUser = $surgery?->doctor?->user;

$patientGender = $patientUser?->gender ?? ($patient?->gender ?? 'male');
$patientAge    = (int) ($patientUser?->age ?? ($patient?->age ?? 30));
$patientPhone  = $patientUser?->phone ?: ($patient?->phone ?? null);

// جلب كافة تحاليل هذه العملية الجراحية
$allSurgeryTests = $surgery ? $surgery->labTests()->whereNotNull('lab_test_id')->with('labTest')->get() : collect();
$hasTests = $allSurgeryTests->count() > 0;
$completedCount = $allSurgeryTests->where('status', 'completed')->count();
$isAllCompleted = $hasTests && ($completedCount === $allSurgeryTests->count());
$currentTestIds = $allSurgeryTests->pluck('lab_test_id')->toArray();

// التحاليل النشطة
$labTests = LabTest::active()->get()->keyBy('name');

// مفضلات المستخدم
$favorites = UserLabTestStat::getFavoritesForUser(auth()->id());

if (!function_exists('getSurgeryTestIcon')) {
    function getSurgeryTestIcon($testName) {
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

if (!function_exists('getSurgeryTestUnit')) {
    function getSurgeryTestUnit($testName, $labTests) {
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
        if (strpos($name, 'صفائح') !== false || strpos($name, 'platelets') !== false || strpos($name, 'wbc') !== false) return '/µL';
        return '';
    }
}
@endphp

@section('content')
<div class="container-fluid py-2">

    <!-- 1. شريط العنوان والإجراءات العلوي المدمج (مطابق تماماً لـ lab/show.blade.php) -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 bg-transparent pb-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary p-2 rounded-circle d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px;">
                        <i class="fas fa-microscope fs-5"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="mb-0 fw-bold text-dark">
                                طلب تحاليل العملية #SURG-{{ $surgery?->id ?? $test->id }}
                            </h5>
                            @if(!$hasTests)
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-2" title="العملية بانتظار تحديد باقة الفحوصات">
                                    <i class="fas fa-hourglass-half me-1 text-warning"></i> بانتظار تحديد الفحوصات
                                </span>
                            @elseif($isAllCompleted)
                                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2">
                                    <i class="fas fa-check-circle me-1"></i> مكتمل ومعتمد
                                </span>
                            @elseif($allSurgeryTests->where('status', 'in_progress')->count() > 0)
                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-2" title="الطلب محفوظ كمسودة قيد الفحص بالمختبر">
                                    <i class="fas fa-edit me-1 text-warning"></i> مسودة قيد الفحص
                                </span>
                            @else
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                    <i class="fas fa-vial me-1"></i> بانتظار الفحص
                                </span>
                            @endif
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-calendar-alt me-1"></i>
                            موعد العملية: {{ $surgery?->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : 'غير محدد' }}
                            @if($surgery?->scheduled_time)
                                • {{ date('h:i A', strtotime($surgery->scheduled_time)) }}
                            @endif
                        </small>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    @if($hasTests)
                        <a href="{{ route('staff.surgery-lab-tests.print', $test) }}" target="_blank" class="btn btn-outline-dark fw-bold shadow-sm px-3" title="طباعة تقرير النتائج">
                            <i class="fas fa-print me-1 text-primary"></i> طباعة {{ $isAllCompleted ? 'التقرير' : 'طلب التحاليل' }}
                        </a>

                        @if($patientPhone)
                            @php
                                $cleanPhone = preg_replace('/[^0-9]/', '', $patientPhone);
                                if (str_starts_with($cleanPhone, '07')) {
                                    $cleanPhone = '964' . substr($cleanPhone, 1);
                                }
                                $printUrl = route('staff.surgery-lab-tests.print', $test);
                                $patientName = optional($patientUser)->name ?? 'المريض';
                                $waText = urlencode("مرحباً {$patientName}، تقرير نتائج تحاليل العملية الجراحية في مستشفى الفرات جاهز. يمكنك معاينة التقرير عبر الرابط: {$printUrl}");
                                $waShareUrl = "https://wa.me/{$cleanPhone}?text={$waText}";
                            @endphp
                            <a href="{{ $waShareUrl }}" target="_blank" class="btn fw-bold shadow-sm px-3 text-white" style="background-color: #25d366; border-color: #20ba5a;" title="إرسال رابط التقرير الطبي المعتمد للمريض عبر واتساب">
                                <i class="fab fa-whatsapp me-1"></i> إرسال النتيجة عبر واتساب
                            </a>
                        @endif

                        <button type="submit" form="surgeryLabResultForm" name="action" value="draft" class="btn btn-warning text-dark fw-bold shadow-sm px-3 border-warning" title="حفظ النتائج والمدخلات كمسودة في المختبر">
                            <i class="fas fa-save me-1"></i> حفظ مؤقت (مسودة)
                        </button>

                        <button type="submit" form="surgeryLabResultForm" name="action" value="complete" class="btn btn-success fw-bold shadow-sm px-3" title="اعتماد النتائج نهائياً وإرسالها لطبيب وصالة العمليات">
                            <i class="fas fa-paper-plane me-1"></i> اعتماد نهائي وإرسال
                        </button>

                        <a href="#appendTestsSection" class="btn btn-outline-secondary" title="إضافة تحاليل إضافية">
                            <i class="fas fa-plus-circle me-1"></i> إضافة تحاليل
                        </a>
                    @endif

                    <a href="{{ route('staff.surgery-lab-tests.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> القائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger shadow-sm rounded-3 mb-3">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- 2. بطاقة معلومات المريض والطلب الموحدة (مطابقة حرفياً لبطاقة /lab/requests/show) -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-transparent">
                <div class="card-header bg-transparent text-dark border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-id-card text-primary"></i>
                        <h6 class="mb-0 fw-bold">بيانات المريض والعملية الجراحية</h6>
                    </div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary font-monospace fw-bold">
                            ملف: #{{ $patient?->national_id ?: ($patient?->id ?? $surgery?->patient_id) }}
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
                                            @if(!empty($waShareUrl))
                                                <a href="{{ $waShareUrl }}" target="_blank" class="badge rounded-pill text-white text-decoration-none px-2 py-1 ms-1 shadow-2xs" style="background-color: #25d366; font-size: 0.70rem;" title="مراسلة المريض عبر واتساب">
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
                                    <i class="fas {{ $patientGender === 'female' ? 'fa-venus text-danger' : 'fa-mars text-primary' }}"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">العمر / الجنس</small>
                                    <strong class="text-dark">
                                        {{ $patientAge ? ($patientAge . ' سنة') : 'غير محدد' }} / 
                                        {{ $patientGender === 'male' ? 'ذكر' : ($patientGender === 'female' ? 'أنثى' : 'غير محدد') }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <!-- الموقع السريري ورقم الغرفة -->
                        <div class="col-xl-2 col-md-4 col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-subtle text-primary rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-bed"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">الموقع والردهة</small>
                                    @if($surgery?->room)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2 font-monospace fw-bold" style="font-size: 0.8rem;">
                                            غرفة {{ $surgery->room->room_number }} ({{ $surgery->room->floor ?? 'الطابق' }})
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border">لم تسند غرفة</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- الطبيب والجراح -->
                        <div class="col-xl-2 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary-subtle text-primary rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">الجراح والعملية</small>
                                    <strong class="text-dark d-block text-truncate" style="max-width: 140px;">
                                        د. {{ optional($doctorUser)->name ?? 'غير محدد' }}
                                    </strong>
                                    <small class="text-muted d-block text-truncate" style="max-width: 140px;">{{ $surgery?->surgery_type ?? 'عملية جراحية' }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- حالة السداد المالي -->
                        <div class="col-xl-1 col-md-6 col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-warning-subtle text-warning rounded-circle p-2 text-center" style="width: 38px; height: 38px;">
                                    <i class="fas fa-cash-register"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">حالة الدفع</small>
                                    @if(($surgery?->surgery_fee_paid === 'paid') || ($allSurgeryTests->where('payment_status', 'paid')->count() > 0))
                                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> مسدد
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
                </div>
            </div>
        </div>
    </div>

    <!-- 3. قسم البكجات والمفضلات واختيار التحاليل (مطابق بنسبة 100% لقسم appendTestsSection في lab/show.blade.php) -->
    <div class="row mb-3" id="appendTestsSection">
        <div class="col-12">
            <div class="card shadow-sm border border-primary-subtle bg-white">
                <div class="card-header bg-primary-subtle text-primary border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        {{ !$hasTests ? 'تحديد واختيار التحاليل المطلوبة لهذه العملية' : 'إضافة تحاليل إضافية إلى هذه العملية' }}
                    </h6>
                    @if($hasTests)
                        <a href="{{ route('staff.surgery-lab-tests.show', $test) }}" class="btn btn-outline-secondary btn-sm py-0 px-2">
                            <i class="fas fa-times me-1"></i> إغلاق
                        </a>
                    @endif
                </div>
                <div class="card-body p-3">
                    @php
                        $selectionFormAction = !$hasTests 
                            ? route('staff.surgery-lab-tests.select-tests', $test)
                            : route('staff.surgery-lab-tests.append', $surgery);
                        $selectionFormMethod = !$hasTests ? 'PUT' : 'POST';
                        $selectionInputName = !$hasTests ? 'lab_test_ids[]' : 'extra_lab_test_ids[]';
                    @endphp

                    <form action="{{ $selectionFormAction }}" method="POST" id="appendLabTestsForm">
                        @csrf
                        @if($selectionFormMethod === 'PUT')
                            @method('PUT')
                        @endif
                        <input type="hidden" name="package_id" id="selectedPackageId" value="">

                        <div class="alert alert-info py-2 px-3 mb-3 small">
                            <i class="fas fa-info-circle me-1"></i>
                            {{ !$hasTests ? 'هذه العملية بانتظار تحديد الفحوصات. يمكنك اختيار باقة طبية كاملة (بكج) أو اختيار من مجموعات المفضلات أو تحديد الفحوصات يدوياً ثم الضغط على زر الحفظ.' : 'سيتم إدراج التحاليل المختارة أو الباقة الطبية إلى العملية الحالية مباشرة دون حذف أي من التحاليل المسجلة مسبقاً.' }}
                        </div>

                        {{-- 1. قسم باقات المختبر الطبية (البكجات Packages) في صف أفقي قابل للتمرير --}}
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

                        {{-- 3. مفضلاتي الفردية --}}
                        @if($favorites->isNotEmpty())
                        <div class="mb-3">
                            <span class="fw-bold small text-dark d-block mb-1"><i class="fas fa-star text-warning me-1"></i> مفضلاتي الفردية:</span>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($favorites as $stat)
                                    @php
                                        $favTest = $stat->labTest;
                                        if (!$favTest || !$favTest->is_active) continue;
                                    @endphp
                                    <label class="btn btn-sm {{ in_array($favTest->id, $currentTestIds) ? 'btn-secondary disabled' : 'btn-outline-primary' }} mb-0 py-1 px-2" style="font-size: 0.8rem;">
                                        <input type="checkbox" name="{{ $selectionInputName }}" value="{{ $favTest->id }}" class="d-none append-test-cb" {{ in_array($favTest->id, $currentTestIds) ? 'disabled' : '' }}>
                                        {{ $favTest->name }}
                                        @if($favTest->code) <small class="text-muted font-monospace">({{ $favTest->code }})</small> @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <!-- شريط البحث السريع في التحاليل -->
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="appendTestSearch" placeholder="ابحث عن تحليل بالاسم أو الكود لإضافته...">
                            <button class="btn btn-outline-secondary" type="button" id="clearAppendSearch"><i class="fas fa-times"></i></button>
                        </div>

                        <!-- الحاوية المقسمة بالتصنيفات الرئيسية -->
                        <div class="border rounded p-2 mb-3 bg-light" style="max-height: 280px; overflow-y: auto;" id="appendTestsContainer">
                            @php
                                $allLabTests = LabTest::where('is_active', true)
                                    ->orderBy('main_category')->orderBy('name')
                                    ->get()->groupBy('main_category');
                            @endphp
                            @foreach($allLabTests as $cat => $tests)
                                <div class="mb-2 append-test-group">
                                    <h6 class="text-primary border-bottom pb-1 small fw-bold">
                                        <i class="fas fa-folder-open me-1"></i>{{ $cat ?: 'فحوصات عامة' }}
                                    </h6>
                                    <div class="row g-1">
                                        @foreach($tests as $t)
                                            <div class="col-md-4 col-sm-6 append-test-item">
                                                <div class="form-check small">
                                                    <input class="form-check-input append-test-cb" type="checkbox"
                                                           name="{{ $selectionInputName }}" value="{{ $t->id }}"
                                                           id="append_{{ $t->id }}"
                                                           {{ in_array($t->id, $currentTestIds) ? 'disabled' : '' }}>
                                                    <label class="form-check-label {{ in_array($t->id, $currentTestIds) ? 'text-muted' : '' }}"
                                                           for="append_{{ $t->id }}" style="cursor: pointer;">
                                                        {{ $t->name }}
                                                        @if($t->code)
                                                            <small class="text-muted font-monospace">({{ $t->code }})</small>
                                                        @endif
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

                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-muted small">
                                تم اختيار: <strong id="selectedTestsCounter" class="text-primary font-monospace">0</strong> تحليل
                            </span>
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-xs">
                                <i class="fas fa-save me-1"></i>
                                {{ !$hasTests ? 'تأكيد التحاليل المختارة والبدء بإدخال النتائج' : 'إضافة التحاليل المختارة إلى العملية' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. جدول إدخال واعتماد نتائج التحاليل السريرية (مطابق تماماً لـ lab/show.blade.php) -->
    @if($hasTests)
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0 bg-transparent h-100 rounded-3 overflow-hidden d-flex flex-column">
                <div class="card-header bg-transparent text-dark border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-table fs-5 text-primary"></i>
                        <h6 class="mb-0 fw-bold">جدول النتائج والمديات المرجعية ({{ $allSurgeryTests->count() }} فحص)</h6>
                    </div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary fw-bold font-monospace">{{ $allSurgeryTests->count() }} فحوصات</span>
                    </div>
                </div>

                <div class="card-body p-0 flex-grow-1">
                    <form action="{{ route('staff.surgery-lab-tests.update-all', $surgery) }}" method="POST" id="surgeryLabResultForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="completed" id="labFormStatus">
                        <input type="hidden" name="action" value="complete" id="labFormAction">

                        <div class="table-responsive">
                            <table class="table table-hover table-bordered align-middle mb-0" id="resultsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">#</th>
                                        <th>اسم الفحص الطبي</th>
                                        <th style="width: 200px;">النتيجة (Value)</th>
                                        <th style="width: 80px;">الوحدة</th>
                                        <th style="width: 150px;">المدى المرجعي</th>
                                        <th style="width: 110px;" class="text-center">الحالة (Flag)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allSurgeryTests as $index => $surgeryTest)
                                        @php
                                            $tObj = $surgeryTest->labTest;
                                            $testName = $tObj->name;
                                            $testIcon = getSurgeryTestIcon($testName);

                                            // حساب المرجع الطبيعي المعتمد بناءً على جنس وعمر المريض
                                            $refObj = LabTestReference::forPatient($tObj->id, $patientGender, $patientAge);
                                            $refDisplay = $refObj ? $refObj->range_display : ($tObj->reference_range ?: '—');
                                            $refMin = $refObj?->ref_min;
                                            $refMax = $refObj?->ref_max;
                                            $refText = $refObj?->ref_text;
                                            $unitDisplay = $refObj?->unit ?? getSurgeryTestUnit($testName, $labTests);
                                        @endphp
                                        <tr class="test-row" 
                                            data-test="{{ $testName }}"
                                            data-ref-min="{{ $refMin }}"
                                            data-ref-max="{{ $refMax }}"
                                            data-ref-text="{{ $refText }}">
                                            <!-- الرقم -->
                                            <td class="text-center text-muted font-monospace small fw-bold">
                                                {{ $index + 1 }}
                                            </td>

                                            <!-- اسم التحليل -->
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="{{ $testIcon }}"></i>
                                                    <div>
                                                        <strong class="text-dark">{{ $testName }}</strong>
                                                        @if($tObj->code)
                                                            <small class="text-muted font-monospace d-block" style="font-size: 0.7rem;">
                                                                كود: {{ $tObj->code }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- حقل إدخال القيمة -->
                                            <td>
                                                <input type="text"
                                                       class="form-control form-control-sm test-value font-monospace fw-bold"
                                                       name="test_results[{{ $surgeryTest->id }}][value]"
                                                       value="{{ old('test_results.' . $surgeryTest->id . '.value', $surgeryTest->result) }}"
                                                       placeholder="أدخل القيمة..."
                                                       tabindex="{{ $index + 1 }}"
                                                       data-test="{{ $testName }}">
                                                <input type="hidden" name="test_results[{{ $surgeryTest->id }}][unit]" value="{{ $unitDisplay }}">
                                                <input type="hidden" name="test_results[{{ $surgeryTest->id }}][test_id]" value="{{ $surgeryTest->id }}">
                                            </td>

                                            <!-- الوحدة -->
                                            <td class="text-muted small font-monospace">
                                                {{ $unitDisplay ?: '—' }}
                                            </td>

                                            <!-- المدى المرجعي -->
                                            <td>
                                                <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.78rem;">
                                                    {{ $refDisplay }}
                                                </span>
                                            </td>

                                            <!-- الحالة الحية (Flag) -->
                                            <td class="text-center">
                                                <span class="result-flag font-monospace" id="flag-{{ $index }}">
                                                    <span class="badge bg-secondary-subtle text-muted">غير محدد</span>
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- شريط الملخص والإحصاءات المباشرة -->
                        <div class="p-3 bg-light border-top d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary-subtle text-primary p-2 rounded-circle"><i class="fas fa-chart-pie"></i></span>
                                <strong class="text-dark small">ملخص نتائج الفحوصات:</strong>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-3 small fw-bold">
                                <span class="text-success d-inline-flex align-items-center gap-1">
                                    <i class="fas fa-check-circle"></i> <span id="normal-count">0</span> طبيعي
                                </span>
                                <span class="text-danger d-inline-flex align-items-center gap-1">
                                    <i class="fas fa-arrow-up"></i> <span id="high-count">0</span> مرتفع
                                </span>
                                <span class="text-warning d-inline-flex align-items-center gap-1">
                                    <i class="fas fa-arrow-down"></i> <span id="low-count">0</span> منخفض
                                </span>
                                <span class="text-muted d-inline-flex align-items-center gap-1">
                                    <i class="fas fa-question-circle"></i> <span id="pending-count">{{ $allSurgeryTests->count() }}</span> غير مكتمل
                                </span>
                            </div>
                        </div>

                        <!-- حقل الملاحظات النصية -->
                        <div class="p-3 bg-white border-top">
                            <label for="notes" class="form-label small fw-bold text-dark">
                                <i class="fas fa-comment-medical text-primary me-1"></i> ملاحظات المختبر حول نتائج العملية:
                            </label>
                            <textarea class="form-control form-control-sm" id="notes" name="notes" rows="2" placeholder="اكتب أي ملاحظات أو تفسيرات موجهة لصالة العمليات أو طبيب التخدير...">{{ old('notes', $test->notes) }}</textarea>
                        </div>

                        <!-- أزرار الحفظ السفلية -->
                        <div class="p-3 bg-light border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <a href="#appendTestsSection" class="btn btn-outline-primary btn-sm px-3 rounded-pill">
                                    <i class="fas fa-plus-circle me-1"></i> إضافة تحاليل أخرى
                                </a>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="submit" name="action" value="draft" class="btn btn-warning text-dark btn-sm fw-bold px-3 py-1.5 rounded-pill shadow-xs border-warning">
                                    <i class="fas fa-save me-1"></i> حفظ مؤقت (مسودة)
                                </button>
                                <button type="submit" name="action" value="complete" class="btn btn-success btn-sm fw-bold px-4 py-1.5 rounded-pill shadow-xs">
                                    <i class="fas fa-paper-plane me-1"></i> اعتماد نهائي وإرسال
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

<style>
.package-card:hover {
    border-color: #0d6efd !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15) !important;
}
.table-danger {
    background-color: #fee2e2 !important;
}
.table-warning {
    background-color: #fef3c7 !important;
}
.table-success {
    background-color: #d1fae5 !important;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. تفاعل اختيار الباقات الطبية (Packages)
    document.querySelectorAll('.package-card').forEach(card => {
        card.addEventListener('click', function(e) {
            const pkgId = this.getAttribute('data-package-id');
            const pkgName = this.getAttribute('data-package-name');
            const testIds = JSON.parse(this.getAttribute('data-test-ids') || '[]');

            document.querySelectorAll('.package-card').forEach(c => {
                c.classList.remove('border-primary', 'bg-primary-subtle', 'shadow');
                const btn = c.querySelector('.select-package-btn');
                if (btn) {
                    btn.classList.remove('btn-primary', 'text-white');
                    btn.classList.add('btn-outline-primary');
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> اختيار';
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

            // تصفير أي تحديد سابق في القائمة
            document.querySelectorAll('.append-test-cb').forEach(cb => {
                if (!cb.disabled) cb.checked = false;
            });

            // تفعيل وتحديد كافة تحاليل هذه الباقة في القائمة
            testIds.forEach(id => {
                const cb = document.getElementById('append_' + id);
                if (cb && !cb.disabled) {
                    cb.checked = true;
                }
            });
            updateTestsCounter();
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
                    btn.innerHTML = '<i class="fas fa-check me-1"></i> اختيار';
                }
            });
            document.getElementById('selectedPackageAlert').classList.add('d-none');
            updateTestsCounter();
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
            updateTestsCounter();
        });
    });

    // عداد الفحوصات المختارة
    function updateTestsCounter() {
        const checked = document.querySelectorAll('.append-test-cb:checked').length;
        const counter = document.getElementById('selectedTestsCounter');
        if (counter) counter.textContent = checked;
    }
    document.querySelectorAll('.append-test-cb').forEach(cb => {
        cb.addEventListener('change', updateTestsCounter);
    });
    updateTestsCounter();

    // البحث السريع في حاوية التحاليل
    const searchInput = document.getElementById('appendTestSearch');
    const clearBtn = document.getElementById('clearAppendSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.append-test-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(q) ? '' : 'none';
            });
        });
    }
    if (clearBtn && searchInput) {
        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            document.querySelectorAll('.append-test-item').forEach(item => item.style.display = '');
        });
    }

    // 2. تقييم النتائج السريرية الحية وتحديث شريط الملخص
    function parseRangeValues(row) {
        let min = null, max = null;
        const minAttr = row.dataset.refMin;
        const maxAttr = row.dataset.refMax;

        if (minAttr !== undefined && minAttr !== '' && !isNaN(parseFloat(minAttr))) {
            min = parseFloat(minAttr);
        }
        if (maxAttr !== undefined && maxAttr !== '' && !isNaN(parseFloat(maxAttr))) {
            max = parseFloat(maxAttr);
        }
        return { min, max };
    }

    function evaluateRow(row) {
        const input = row.querySelector('.test-value');
        const flag = row.querySelector('.result-flag');
        if (!input) return 'unknown';

        const val = input.value.trim();
        row.classList.remove('table-danger', 'table-warning', 'table-success');

        if (val === '') {
            if (flag) flag.innerHTML = '<span class="badge bg-secondary-subtle text-muted">غير مكتمل</span>';
            return 'pending';
        }

        const lower = val.toLowerCase();
        if (lower === 'positive' || lower === 'موجب' || lower === 'pos' || lower === '+') {
            row.classList.add('table-danger');
            if (flag) flag.innerHTML = '<span class="badge bg-danger">↑ موجب (غير طبيعي)</span>';
            return 'high';
        }
        if (lower === 'negative' || lower === 'سالب' || lower === 'neg' || lower === '-' || lower === 'normal' || lower === 'طبيعي') {
            row.classList.add('table-success');
            if (flag) flag.innerHTML = '<span class="badge bg-success">✓ سالب (طبيعي)</span>';
            return 'normal';
        }

        const numeric = parseFloat(val.replace(/,/g, ''));
        const { min: refMin, max: refMax } = parseRangeValues(row);

        if (isNaN(numeric) || (refMin === null && refMax === null)) {
            if (flag) flag.innerHTML = '<span class="badge bg-info-subtle text-dark">تم الإدخال</span>';
            return 'unknown';
        }

        if (refMin !== null && numeric < refMin) {
            row.classList.add('table-warning');
            if (flag) flag.innerHTML = '<span class="badge bg-warning text-dark">↓ منخفض</span>';
            return 'low';
        }
        if (refMax !== null && numeric > refMax) {
            row.classList.add('table-danger');
            if (flag) flag.innerHTML = '<span class="badge bg-danger">↑ مرتفع</span>';
            return 'high';
        }

        row.classList.add('table-success');
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

    document.querySelectorAll('.test-value').forEach(input => {
        input.addEventListener('input', function () {
            evaluateRow(this.closest('.test-row'));
            updateSummary();
        });
    });

    // تقييم أولي عند التحميل
    updateSummary();
});
</script>
@endpush
@endsection
