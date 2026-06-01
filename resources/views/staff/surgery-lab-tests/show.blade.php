@extends('layouts.app')

@php
use App\Models\LabTest;

// الحصول على جميع التحاليل النشطة للاستخدام في JavaScript
$labTests = LabTest::active()->get()->keyBy('name');

// معلومات المريض للقيم المرجعية
$patientGender = $test->surgery?->patient?->gender ?? 'male';
$patientAge = $test->surgery?->patient?->age ?? 30;

function getTestIcon($testName) {
    $name = strtolower($testName);

    if (strpos($name, 'سكر') !== false || strpos($name, 'glucose') !== false) {
        return 'fas fa-tint text-danger';
    } elseif (strpos($name, 'ضغط') !== false || strpos($name, 'pressure') !== false) {
        return 'fas fa-heartbeat text-danger';
    } elseif (strpos($name, 'كوليسترول') !== false || strpos($name, 'cholesterol') !== false) {
        return 'fas fa-oil-can text-warning';
    } elseif (strpos($name, 'دم') !== false || strpos($name, 'blood') !== false) {
        return 'fas fa-tint text-danger';
    } elseif (strpos($name, 'بول') !== false || strpos($name, 'urine') !== false) {
        return 'fas fa-flask text-warning';
    } elseif (strpos($name, 'كبد') !== false || strpos($name, 'liver') !== false) {
        return 'fas fa-lungs text-success';
    } elseif (strpos($name, 'كلى') !== false || strpos($name, 'kidney') !== false) {
        return 'fas fa-kidney text-info';
    } elseif (strpos($name, 'هرمون') !== false || strpos($name, 'hormone') !== false) {
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
    // البحث في قاعدة البيانات أولاً
    if (isset($labTests[$testName]) && !empty($labTests[$testName]->unit)) {
        return $labTests[$testName]->unit;
    }

    // الاحتياطي للفحوصات غير الموجودة في قاعدة البيانات
    $name = strtolower($testName);

    if (strpos($name, 'سكر') !== false || strpos($name, 'glucose') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'ضغط') !== false || strpos($name, 'pressure') !== false) {
        return 'mmHg';
    } elseif (strpos($name, 'كوليسترول') !== false || strpos($name, 'cholesterol') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'بيليروبين') !== false || strpos($name, 'bilirubin') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'كرياتينين') !== false || strpos($name, 'creatinine') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'يوريا') !== false || strpos($name, 'urea') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'sgot') !== false || strpos($name, 'ast') !== false) {
        return 'U/L';
    } elseif (strpos($name, 'sgpt') !== false || strpos($name, 'alt') !== false) {
        return 'U/L';
    } elseif (strpos($name, 'الصفائح') !== false || strpos($name, 'platelets') !== false) {
        return '/µL';
    } elseif (strpos($name, 'الهيموغلوبين') !== false || strpos($name, 'hemoglobin') !== false) {
        return 'g/dL';
    } elseif (strpos($name, 'الكرات البيضاء') !== false || strpos($name, 'wbc') !== false) {
        return '/µL';
    } elseif (strpos($name, 'الكرات الحمراء') !== false || strpos($name, 'rbc') !== false) {
        return 'million/µL';
    } elseif (strpos($name, 'الهيماتوكريت') !== false || strpos($name, 'hematocrit') !== false) {
        return '%';
    } elseif (strpos($name, 'هرمون') !== false || strpos($name, 'hormone') !== false) {
        return 'mIU/mL';
    } elseif (strpos($name, 'فيتامين') !== false || strpos($name, 'vitamin') !== false) {
        return 'ng/mL';
    } elseif (strpos($name, 'حديد') !== false || strpos($name, 'iron') !== false) {
        return 'µg/dL';
    } elseif (strpos($name, 'كالسيوم') !== false || strpos($name, 'calcium') !== false) {
        return 'mg/dL';
    } elseif (strpos($name, 'صوديوم') !== false || strpos($name, 'sodium') !== false) {
        return 'mEq/L';
    } elseif (strpos($name, 'بوتاسيوم') !== false || strpos($name, 'potassium') !== false) {
        return 'mEq/L';
    } else {
        return '';
    }
}
@endphp

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-flask me-2"></i>
                    تفاصيل طلب التحليل المخبري للعملية
                </h2>
                <a href="{{ route('staff.surgery-lab-tests.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <!-- معلومات العملية -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-procedures me-2"></i>معلومات العملية</h5>
                </div>
                <div class="card-body">
                    @if($test->surgery)
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>المريض:</strong> {{ optional($test->surgery->patient?->user)->name ?? 'غير معروف' }}</p>
                            <p><strong>الطبيب:</strong> د. {{ optional($test->surgery->doctor?->user)->name ?? 'غير محدد' }}</p>
                            <p><strong>نوع العملية:</strong> {{ $test->surgery->surgery_type }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>تاريخ العملية:</strong> {{ $test->surgery->scheduled_date ? $test->surgery->scheduled_date->format('Y-m-d') : 'غير محدد' }}</p>
                            <p><strong>وقت العملية:</strong> {{ $test->surgery->scheduled_time }}</p>
                            <p><strong>الحالة:</strong>
                                @if($test->surgery->status == 'scheduled')
                                    <span class="badge bg-secondary">مجدولة</span>
                                @elseif($test->surgery->status == 'in_progress')
                                    <span class="badge bg-warning">جارية</span>
                                @elseif($test->surgery->status == 'completed')
                                    <span class="badge bg-success">مكتملة</span>
                                @else
                                    <span class="badge bg-danger">ملغاة</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        معلومات العملية غير متوفرة
                    </div>
                    @endif
                </div>
            </div>

            <!-- معلومات التحليل -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-vial me-2"></i>معلومات التحليل المطلوب</h5>
                </div>
                <div class="card-body">
                    @if($test->labTest)
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>اسم التحليل:</strong> {{ $test->labTest->name }}</p>
                            <p><strong>الفئة:</strong> {{ $test->labTest->category ?? 'غير محدد' }}</p>
                            <p><strong>الوحدة:</strong> {{ $test->labTest->unit ?? 'غير محدد' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>الحالة:</strong>
                                <span class="badge bg-{{ $test->status_color }}">
                                    {{ $test->status_text }}
                                </span>
                            </p>
                            <p><strong>تاريخ الطلب:</strong> {{ $test->created_at->format('Y-m-d H:i') }}</p>
                            @if($test->completed_at)
                            <p><strong>تاريخ الإكمال:</strong> {{ $test->completed_at->format('Y-m-d H:i') }}</p>
                            @endif
                        </div>
                    </div>

                    @if($test->labTest->description)
                    <hr>
                    <h6>وصف التحليل:</h6>
                    <p>{{ $test->labTest->description }}</p>
                    @endif
                    @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>طلب مختبر عام</strong>
                        <p class="mb-0 mt-2">يرجى اختيار التحاليل المطلوبة أدناه.</p>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>الحالة:</strong>
                                <span class="badge bg-{{ $test->status_color }}">
                                    {{ $test->status_text }}
                                </span>
                            </p>
                            <p><strong>تاريخ الطلب:</strong> {{ $test->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>

                    <!-- قسم اختيار التحاليل -->
                    <div class="card mt-3 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-flask me-2"></i>اختر التحاليل المطلوبة</h6>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('staff.surgery-lab-tests.select-tests', $test) }}" method="POST" id="selectTestsForm">
                                @csrf
                                @method('PUT')

                                {{-- قسم المفضلات --}}
                                @php
                                    $favorites = \App\Models\UserLabTestStat::getFavoritesForUser(auth()->id());
                                @endphp

                                @if($favorites->isNotEmpty())
                                <div class="alert alert-dismissible fade show p-0 mb-3" style="background: linear-gradient(135deg, #FFF9E6 0%, #FFF4D6 100%); border: 2px solid #FFD700; border-radius: 12px;">
                                    <div class="d-flex align-items-center px-3 py-2" style="background: linear-gradient(90deg, #FFD700 0%, #FFC107 100%); border-radius: 10px 10px 0 0;">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-star text-white"></i>
                                            <strong class="text-dark">الاختصارات السريعة</strong>
                                            <small class="text-dark" style="opacity: 0.8;">{{ $favorites->count() }} تحليل</small>
                                        </div>
                                    </div>
                                    <div class="p-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            @foreach($favorites as $stat)
                                                @php
                                                    $favTest = $stat->labTest;
                                                    if (!$favTest || !$favTest->is_active) continue;
                                                @endphp
                                                <label class="favorite-pill">
                                                    <input class="form-check-input lab-test-checkbox d-none" type="checkbox" name="lab_test_ids[]" value="{{ $favTest->id }}">
                                                    <div class="pill-content px-3 py-2 rounded-pill border bg-white">
                                                        <i class="fas fa-flask text-warning"></i>
                                                        <span>{{ $favTest->name }}</span>
                                                        <span class="badge bg-warning text-dark">{{ $favTest->code }}</span>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <style>
                                .favorite-pill {
                                    cursor: pointer;
                                    display: inline-block;
                                }
                                .favorite-pill input:checked + .pill-content {
                                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
                                    color: white !important;
                                    border-color: #28a745 !important;
                                }
                                .favorite-pill input:checked + .pill-content span {
                                    color: white !important;
                                }
                                .favorite-pill input:checked + .pill-content i {
                                    color: white !important;
                                }
                                .favorite-pill input:checked + .pill-content .badge {
                                    background: white !important;
                                    color: #28a745 !important;
                                }
                                </style>
                                @endif

                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-search me-1"></i>
                                        <strong>ابحث عن تحليل:</strong>
                                    </label>
                                    <input type="text" class="form-control" id="surgeryLabSearchInput" placeholder="ابحث بالاسم أو الرمز...">
                                </div>

                                <div class="border rounded p-3" id="surgeryLabTestsContainer" style="max-height: 400px; overflow-y: auto;">
                                    @php
                                        $allLabTests = \App\Models\LabTest::where('is_active', true)
                                            ->orderBy('main_category')
                                            ->orderBy('name')
                                            ->get()
                                            ->groupBy('main_category');
                                    @endphp
                                    @foreach($allLabTests as $category => $tests)
                                        <div class="lab-category mb-3">
                                            <h6 class="text-primary border-bottom pb-2">
                                                <i class="fas fa-folder-open me-1"></i>{{ $category }}
                                            </h6>
                                            @foreach($tests as $labTestItem)
                                                <div class="form-check test-item">
                                                    <input class="form-check-input lab-test-checkbox" 
                                                           type="checkbox" 
                                                           name="lab_test_ids[]" 
                                                           value="{{ $labTestItem->id }}" 
                                                           id="surgery_lab_{{ $labTestItem->id }}"
                                                           data-name="{{ $labTestItem->name }}"
                                                           data-code="{{ $labTestItem->code }}">
                                                    <label class="form-check-label" for="surgery_lab_{{ $labTestItem->id }}">
                                                        {{ $labTestItem->name }} 
                                                        <small class="text-muted">({{ $labTestItem->code }})</small>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>

                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-1"></i>
                                    تم اختيار <strong><span id="selectedCount">0</span></strong> تحليل
                                </div>

                                <button type="submit" class="btn btn-success w-100 mt-3" id="confirmTestsBtn">
                                    <i class="fas fa-check-circle me-2"></i>
                                    تأكيد التحاليل المختارة
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- النتائج المحفوظة -->
            @if($test->result || $test->result_file)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>نتائج التحليل المحفوظة</h5>
                </div>
                <div class="card-body">
                    @if($test->result)
                    <div class="mb-3">
                        <h6>النتيجة:</h6>
                        <div class="bg-light p-3 rounded">
                            {{ $test->result }}
                        </div>
                    </div>
                    @endif

                    @if($test->result_file)
                    <div class="mb-3">
                        <h6>ملف النتيجة:</h6>
                        <a href="{{ asset('storage/' . $test->result_file) }}" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-file-download me-2"></i>عرض/تحميل الملف
                        </a>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-edit me-2"></i>تحديث حالة التحليل</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.surgery-lab-tests.update-all', $test->surgery) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        @php
                            // جلب جميع تحاليل العملية
                            $allSurgeryTests = $test->surgery->labTests()->whereNotNull('lab_test_id')->with('labTest')->get();
                            $testCount = $allSurgeryTests->count();
                        @endphp

                        @if($testCount > 0)
                        <div class="lab-results-section">
                            <h6 class="mb-3"><i class="fas fa-clipboard-list me-2"></i>إدخال نتائج التحاليل</h6>
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered align-middle" id="resultsTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width:40px;">#</th>
                                            <th>التحليل</th>
                                            <th style="width:180px;">القيمة</th>
                                            <th style="width:90px;">الوحدة</th>
                                            <th style="width:130px;">المرجع</th>
                                            <th style="width:90px;" class="text-center">الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($allSurgeryTests as $index => $surgeryTest)
                                            @php
                                                $testIcon = getTestIcon($surgeryTest->labTest->name);
                                                $labTestObj = $surgeryTest->labTest;
                                                
                                                // جلب المرجع الطبيعي بناءً على جنس وعمر المريض
                                                $refObj = \App\Models\LabTestReference::forPatient($labTestObj->id, $patientGender, $patientAge);
                                                $refDisplay = $refObj ? $refObj->range_display : '—';
                                                $refMin = $refObj?->ref_min;
                                                $refMax = $refObj?->ref_max;
                                                $unitDisplay = $refObj?->unit ?? getTestUnit($surgeryTest->labTest->name, $labTests);
                                            @endphp
                                            
                                            <tr class="test-row" data-test="{{ $surgeryTest->labTest->name }}"
                                                data-ref-min="{{ $refMin }}"
                                                data-ref-max="{{ $refMax }}"
                                                data-ref-text="{{ $refObj?->ref_text }}">
                                                <td class="text-center text-muted small">{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="{{ $testIcon }}"></i>
                                                        <strong>{{ $surgeryTest->labTest->name }}</strong>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text"
                                                           class="form-control form-control-sm test-value"
                                                           name="test_results[{{ $surgeryTest->id }}][value]"
                                                           value="{{ old('test_results.' . $surgeryTest->id . '.value', $surgeryTest->result) }}"
                                                           placeholder="أدخل القيمة"
                                                           tabindex="{{ $index + 1 }}"
                                                           data-test="{{ $surgeryTest->labTest->name }}">
                                                    <input type="hidden" name="test_results[{{ $surgeryTest->id }}][unit]" value="{{ $unitDisplay }}">
                                                    <input type="hidden" name="test_results[{{ $surgeryTest->id }}][test_id]" value="{{ $surgeryTest->id }}">
                                                </td>
                                                <td class="text-muted small">{{ $unitDisplay }}</td>
                                                <td>
                                                    @if($refObj)
                                                        <span class="badge bg-light text-dark border">{{ $refDisplay }}</span>
                                                    @else
                                                        <span class="text-muted small">غير محدد</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="result-flag" id="flag-{{ $index }}">
                                                        <i class="fas fa-circle text-muted small"></i>
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- ملخص سريع للنتائج -->
                            <div class="results-summary mt-3 p-3 bg-light rounded">
                                <h6 class="mb-2">
                                    <i class="fas fa-chart-bar text-primary me-2"></i>
                                    ملخص النتائج
                                </h6>
                                <div class="summary-stats">
                                    <span class="stat-item">
                                        <i class="fas fa-check-circle text-success"></i>
                                        <span id="normal-count">0</span> طبيعي
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-arrow-up text-danger"></i>
                                        <span id="high-count">0</span> مرتفع
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-arrow-down text-warning"></i>
                                        <span id="low-count">0</span> منخفض
                                    </span>
                                    <span class="stat-item">
                                        <i class="fas fa-question-circle text-muted"></i>
                                        <span id="pending-count">{{ $testCount }}</span> غير مكتمل
                                    </span>
                                </div>
                            </div>

                            <!-- ملاحظات إضافية -->
                            <div class="mt-3">
                                <label for="notes" class="form-label">
                                    <i class="fas fa-comment-medical text-secondary me-2"></i>
                                    ملاحظات إضافية
                                </label>
                                <textarea class="form-control"
                                          id="notes"
                                          name="notes"
                                          rows="3"
                                          placeholder="ملاحظات إضافية حول النتائج أو تفسيرها">{{ old('notes', $test->surgery->lab_notes ?? '') }}</textarea>
                                <small class="text-muted">
                                    <i class="fas fa-lightbulb text-warning me-1"></i>
                                    اكتب أي ملاحظات مهمة أو تفسيرات للنتائج
                                </small>
                            </div>

                            <!-- قسم المراجع المرجعية -->
                            <div class="mt-4">
                                <details class="mb-3">
                                    <summary class="text-primary fw-bold cursor-pointer">
                                        <i class="fas fa-info-circle me-2"></i>
                                        القيم المرجعية الطبيعية (معلومات فقط)
                                    </summary>
                                    <div class="mt-3 p-3 bg-light rounded">
                                        <small class="text-muted">
                                            <strong>ملاحظة:</strong> هذه القيم تقريبية عامة وتختلف حسب المختبر، عمر المريض، وجنسه. استشر الطبيب للقيم الصحيحة.
                                        </small>
                                        <ul class="mt-2 mb-0 small">
                                            <li><strong>سكر الدم:</strong> 70-140 mg/dL</li>
                                            <li><strong>ضغط الدم:</strong> أقل من 140 mmHg</li>
                                            <li><strong>الكوليسترول:</strong> أقل من 200 mg/dL</li>
                                            <li><strong>البيليروبين:</strong> أقل من 1.2 mg/dL</li>
                                            <li><strong>الكرياتينين:</strong> 0.6-1.2 mg/dL</li>
                                            <li><strong>اليوريا:</strong> 7-50 mg/dL</li>
                                            <li><strong>SGOT (AST):</strong> أقل من 40 U/L</li>
                                            <li><strong>SGPT (ALT):</strong> أقل من 41 U/L</li>
                                            <li><strong>الهيموغلوبين:</strong> 12-16 g/dL (نساء)، 14-18 g/dL (رجال)</li>
                                            <li><strong>الكرات البيضاء:</strong> 4,000-11,000 /µL</li>
                                            <li><strong>الصفائح:</strong> 150,000-450,000 /µL</li>
                                        </ul>
                                    </div>
                                </details>
                            </div>
                        </div>

                        <hr>
                        <div class="row align-items-center g-2 mt-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label mb-1 fw-bold">حالة التحاليل</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="pending">في الانتظار</option>
                                    <option value="in_progress">قيد التنفيذ</option>
                                    <option value="completed">مكتملة</option>
                                </select>
                            </div>
                            <div class="col-md-9 d-flex justify-content-end align-items-end gap-2 pt-3">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i>
                                    حفظ جميع النتائج
                                </button>
                            </div>
                        </div>
                        @else
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            لا توجد تحاليل محددة لهذه العملية
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- معلومات إضافية -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات إضافية</h6>
                </div>
                <div class="card-body">
                    <p><strong>رقم الطلب:</strong> {{ $test->id }}</p>
                    <p><strong>تاريخ الإنشاء:</strong> {{ $test->created_at->format('Y-m-d H:i') }}</p>
                    <p><strong>آخر تحديث:</strong> {{ $test->updated_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* تصميم محسن للقراءات المخبرية */
.lab-results-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 20px;
    margin: 15px 0;
    border: 1px solid #dee2e6;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.results-summary {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
}

.results-summary h6 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 15px;
}

.summary-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    justify-content: center;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 14px;
    font-weight: 500;
    background: white;
    padding: 8px 12px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.stat-item i {
    font-size: 16px;
}

/* تحسينات جدول التحاليل */
.table {
    margin-bottom: 0;
    --bs-table-hover-bg: rgba(0, 123, 255, 0.05);
}

.table > :not(caption) > * > * {
    padding: 0.75rem;
    vertical-align: middle;
}

.table thead th {
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    font-weight: 600;
    text-align: center;
    border-bottom: 2px solid #dee2e6;
}

.test-row {
    transition: all 0.3s ease;
}

.test-row:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.test-row.table-success,
.test-row.result-normal {
    background-color: rgba(40, 167, 69, 0.05);
}

.test-row.table-danger,
.test-row.result-high {
    background-color: rgba(220, 53, 69, 0.05);
}

.test-row.table-warning,
.test-row.result-low {
    background-color: rgba(255, 193, 7, 0.05);
}

.test-row .form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    border-color: #80bdff;
}

/* تصميم متجاوب للجدول */
.table-responsive {
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
}

/* تحسينات للأزرار */
.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-success:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

/* تحسينات للبطاقات */
.card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.card-header {
    border-radius: 10px 10px 0 0 !important;
    border: none;
    font-weight: 600;
}

/* تحسينات للتنبيهات */
.alert {
    border: none;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-left: 4px solid #ffc107;
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    border-left: 4px solid #17a2b8;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    border-left: 4px solid #dc3545;
}

/* تحسينات لقسم المراجع */
details {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 10px 15px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

details:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

details summary {
    list-style: none;
    cursor: pointer;
    user-select: none;
    transition: color 0.3s ease;
}

details summary::-webkit-details-marker {
    display: none;
}

details summary::before {
    content: '▶';
    margin-right: 8px;
    transition: transform 0.3s ease;
}

details[open] summary::before {
    transform: rotate(90deg);
}

details summary:hover {
    color: #0056b3;
}

details div {
    margin-top: 15px;
    padding: 15px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
}

details ul li {
    margin-bottom: 5px;
    color: #495057;
}

/* تحسينات للحقول */
.form-control, .form-select {
    border-radius: 6px;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

/* تحسينات للأيقونات */
.test-icon i {
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
}

@media (max-width: 768px) {
    .table > :not(caption) > * > * {
        padding: 0.5rem;
    }
    
    .lab-results-section {
        padding: 15px;
        margin: 10px 0;
    }

    .summary-stats {
        flex-direction: column;
        align-items: center;
    }

    .stat-item {
        width: 100%;
        margin-bottom: 8px;
        text-align: center;
    }

    .container-fluid {
        padding-left: 10px;
        padding-right: 10px;
    }

    .btn {
        font-size: 14px;
        padding: 8px 12px;
    }
}

@media (min-width: 1200px) {
    .lab-results-section {
        padding: 25px;
    }
}

/* تحسينات عامة */
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}
</style>

<script>
// ──────────────── تلوين نتائج التحاليل تلقائياً ────────────────
function evaluateRow(row) {
    const input = row.querySelector('.test-value');
    const flag = row.querySelector('.result-flag');
    if (!input || !flag) return null;

    const val = input.value.trim();
    const refMin = row.dataset.refMin !== '' ? parseFloat(row.dataset.refMin) : null;
    const refMax = row.dataset.refMax !== '' ? parseFloat(row.dataset.refMax) : null;

    row.classList.remove('table-success', 'table-danger', 'table-warning', 'result-normal', 'result-high', 'result-low');
    flag.innerHTML = '<i class="fas fa-circle text-muted small"></i>';

    if (val === '') {
        flag.innerHTML = '<span class="badge bg-secondary text-white">غير مكتمل</span>';
        return 'pending';
    }

    const refText = row.dataset.refText?.trim() || '';
    const numeric = parseFloat(val);

    if (refText && refMin === null && refMax === null) {
        const expected = refText.toLowerCase().trim();
        const actual = val.toLowerCase().trim();

        const cmpMatch = expected.match(/^\s*(<=|≥|>=|≤|<|>)\s*([\d\.]+)\s*$/);
        if (cmpMatch && !isNaN(numeric)) {
            const operator = cmpMatch[1];
            const threshold = parseFloat(cmpMatch[2]);
            if (operator === '<' && numeric >= threshold) {
                row.classList.add('table-danger', 'result-high');
                flag.innerHTML = '<span class="badge bg-danger">↑ مرتفع</span>';
                return 'high';
            }
            if ((operator === '<=' || operator === '≤') && numeric > threshold) {
                row.classList.add('table-danger', 'result-high');
                flag.innerHTML = '<span class="badge bg-danger">↑ مرتفع</span>';
                return 'high';
            }
            if (operator === '>' && numeric <= threshold) {
                row.classList.add('table-warning', 'result-low');
                flag.innerHTML = '<span class="badge bg-warning text-dark">↓ منخفض</span>';
                return 'low';
            }
            if ((operator === '>=' || operator === '≥') && numeric < threshold) {
                row.classList.add('table-warning', 'result-low');
                flag.innerHTML = '<span class="badge bg-warning text-dark">↓ منخفض</span>';
                return 'low';
            }
            row.classList.add('table-success', 'result-normal');
            flag.innerHTML = '<span class="badge bg-success">✓ طبيعي</span>';
            return 'normal';
        }

        if (expected === actual) {
            row.classList.add('table-success', 'result-normal');
            flag.innerHTML = '<span class="badge bg-success">✓ طبيعي</span>';
            return 'normal';
        }

        flag.innerHTML = '<span class="badge bg-info text-white">راجع المرجع الطبي</span>';
        return 'unknown';
    }

    if (isNaN(numeric)) {
        flag.innerHTML = '<span class="badge bg-secondary text-white">قيمة غير صالحة</span>';
        return 'unknown';
    }

    if (refMin === null && refMax === null) {
        flag.innerHTML = '<span class="badge bg-info text-white">راجع المرجع الطبي</span>';
        return 'unknown';
    }

    if (refMin !== null && numeric < refMin) {
        row.classList.add('table-warning', 'result-low');
        flag.innerHTML = '<span class="badge bg-warning text-dark">↓ منخفض</span>';
        return 'low';
    }
    if (refMax !== null && numeric > refMax) {
        row.classList.add('table-danger', 'result-high');
        flag.innerHTML = '<span class="badge bg-danger">↑ مرتفع</span>';
        return 'high';
    }
    row.classList.add('table-success', 'result-normal');
    flag.innerHTML = '<span class="badge bg-success">✓ طبيعي</span>';
    return 'normal';
}

function updateSummary() {
    const testRows = document.querySelectorAll('#resultsTable .test-row');
    let normal = 0, high = 0, low = 0, pending = 0;

    testRows.forEach(row => {
        const input = row.querySelector('.test-value');
        const value = parseFloat(input.value);

        if (isNaN(value) || input.value.trim() === '') {
            pending++;
        } else if (row.classList.contains('result-high')) {
            high++;
        } else if (row.classList.contains('result-low')) {
            low++;
        } else {
            normal++;
        }
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
    document.querySelectorAll('#resultsTable .test-value').forEach((input, i) => {
        input.addEventListener('input', function () {
            evaluateRow(this.closest('.test-row'));
            updateSummary();
        });
        // تقييم القيم الموجودة مسبقاً عند التحميل
        if (input.value.trim() !== '') {
            evaluateRow(input.closest('.test-row'));
        }
    });
    updateSummary();
}

// وظائف تحليل النتائج المخبرية
document.addEventListener('DOMContentLoaded', function() {
    initializeLabResults();
    
    // البحث في التحاليل
    const searchInput = document.getElementById('surgeryLabSearchInput');
    const testsContainer = document.getElementById('surgeryLabTestsContainer');
    
    if (searchInput && testsContainer) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const categories = testsContainer.querySelectorAll('.lab-category');
            
            categories.forEach(category => {
                const tests = category.querySelectorAll('.test-item');
                let visibleCount = 0;
                
                tests.forEach(test => {
                    const checkbox = test.querySelector('input[type="checkbox"]');
                    const name = checkbox?.dataset.name?.toLowerCase() || '';
                    const code = checkbox?.dataset.code?.toLowerCase() || '';
                    
                    if (name.includes(searchTerm) || code.includes(searchTerm)) {
                        test.style.display = '';
                        visibleCount++;
                    } else {
                        test.style.display = 'none';
                    }
                });
                
                // إخفاء الفئة إذا لم يكن بها نتائج
                category.style.display = visibleCount > 0 ? '' : 'none';
            });
        });
    }
    
    // عد التحاليل المختارة
    const checkboxes = document.querySelectorAll('.lab-test-checkbox');
    const selectedCountSpan = document.getElementById('selectedCount');
    const confirmBtn = document.getElementById('confirmTestsBtn');
    
    function updateSelectedCount() {
        const selected = document.querySelectorAll('.lab-test-checkbox:checked').length;
        if (selectedCountSpan) {
            selectedCountSpan.textContent = selected;
        }
        if (confirmBtn) {
            confirmBtn.disabled = selected === 0;
        }
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });
    
    updateSelectedCount();
    
    // تفعيل tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
@endsection
