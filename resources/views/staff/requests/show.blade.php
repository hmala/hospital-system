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
    } elseif (strpos($name, 'أشعة') !== false || strpos($name, 'x-ray') !== false) {
        return 'fas fa-x-ray text-primary';
    } elseif (strpos($name, 'تصوير') !== false || strpos($name, 'imaging') !== false) {
        return 'fas fa-camera text-secondary';
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
                    <i class="fas fa-file-medical me-2"></i>
                    تفاصيل الطلب الطبي
                </h2>
                <div class="d-flex gap-2">
                    @if($request->status == 'completed' && $request->type == 'lab')
                        <a href="{{ route('staff.requests.print', $request) }}" 
                           class="btn btn-success" 
                           target="_blank">
                            <i class="fas fa-print me-1"></i>
                            طباعة النتائج
                        </a>
                    @endif
                    <a href="{{ route('staff.requests.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
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
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- معلومات الطلب -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        معلومات الطلب
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>رقم الطلب:</strong> #{{ $request->id }}</p>
                            <p><strong>نوع الطلب:</strong>
                                <span class="badge bg-{{ $request->type == 'lab' ? 'primary' : ($request->type == 'radiology' ? 'info' : 'success') }}">
                                    {{ $request->type_text }}
                                </span>
                            </p>
                            <p><strong>تاريخ الطلب:</strong> {{ $request->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>الحالة:</strong>
                                <span class="badge bg-{{ $request->status == 'completed' ? 'success' : ($request->status == 'pending' ? 'warning' : 'info') }}">
                                    {{ $request->status_text }}
                                </span>
                            </p>
                            <p><strong>الأولوية:</strong>
                                <span class="badge bg-{{ ($request->details['priority'] ?? 'normal') == 'urgent' ? 'danger' : (($request->details['priority'] ?? 'normal') == 'emergency' ? 'dark' : 'secondary') }}">
                                    {{ ($request->details['priority'] ?? 'normal') == 'urgent' ? 'عاجل' : (($request->details['priority'] ?? 'normal') == 'emergency' ? 'طوارئ' : 'عادي') }}
                                </span>
                            </p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <p><strong>وصف الطلب:</strong></p>
                            <p class="text-muted">{{ $request->details['description'] ?? $request->description ?? 'لا يوجد وصف' }}</p>
                        </div>
                    </div>
                    @if($request->type === 'lab')
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <p class="mb-0"><strong>الفحوصات المطلوبة:</strong></p>
                                    <small class="text-muted">اختر التحاليل المطلوبة من القائمة</small>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#selectTestsModal">
                                    <i class="fas fa-plus-circle me-1"></i>
                                    إضافة فحوصات
                                </button>
                            </div>
                            <div class="row">
                                @php
                                    // دعم كل من tests و lab_test_ids
                                    $testsList = [];
                                    if (isset($request->details['tests']) && is_array($request->details['tests'])) {
                                        $testsList = $request->details['tests'];
                                    } elseif (isset($request->details['lab_test_ids']) && is_array($request->details['lab_test_ids'])) {
                                        // تحويل IDs إلى أسماء
                                        foreach ($request->details['lab_test_ids'] as $testId) {
                                            $labTest = \App\Models\LabTest::find($testId);
                                            if ($labTest) {
                                                $testsList[] = $labTest->name;
                                            }
                                        }
                                    }
                                @endphp
                                @foreach($testsList as $test)
                                <div class="col-md-4 mb-2">
                                    <div class="d-flex align-items-center border rounded p-2">
                                        <i class="{{ getTestIcon($test) }} me-2"></i>
                                        <span>{{ $test }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4">
            @if($request->visit)
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        معلومات المريض
                    </h6>
                </div>
                <div class="card-body">
                    <p><strong>الاسم:</strong> {{ $request->visit->patient?->user?->name ?? 'غير محدد' }}</p>
                    <p><strong>العمر:</strong> {{ $request->visit->patient?->age ?? 'غير محدد' }} سنة</p>
                    <p><strong>الجنس:</strong> {{ $request->visit->patient?->gender == 'male' ? 'ذكر' : ($request->visit->patient?->gender == 'female' ? 'أنثى' : 'غير محدد') }}</p>
                    <p><strong>رقم الهاتف:</strong> {{ $request->visit->patient?->phone ?? 'غير محدد' }}</p>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user-md me-2"></i>
                        معلومات الطبيب
                    </h6>
                </div>
                <div class="card-body">
                    <p><strong>الاسم:</strong> د. {{ $request->visit->doctor?->user?->name ?? 'غير محدد' }}</p>
                    <p><strong>التخصص:</strong> {{ $request->visit->doctor?->specialization ?? 'غير محدد' }}</p>
                    <p><strong>القسم:</strong> {{ $request->visit->doctor?->department?->name ?? 'غير محدد' }}</p>
                </div>
            </div>
            @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                لا توجد معلومات زيارة مرتبطة بهذا الطلب
            </div>
            @endif
        </div>
    </div>

    <!-- Modal اختيار التحاليل -->
    <div class="modal fade" id="selectTestsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-flask me-2"></i>
                        اختيار التحاليل المطلوبة
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('staff.requests.update', $request->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="alert alert-info border-0">
                            <i class="fas fa-info-circle me-2"></i>
                            اختر التحاليل المطلوبة من القائمة أدناه
                        </div>

                        <!-- Search Box -->
                        <div class="mb-4">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white">
                                    <i class="fas fa-search text-primary"></i>
                                </span>
                                <input type="text" class="form-control" id="testSearchInput" 
                                    placeholder="ابحث عن التحليل بالاسم أو الوصف..." 
                                    autocomplete="off">
                                <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="mt-2 text-muted small">
                                <span id="searchResultsCount"></span>
                            </div>
                        </div>

                        @php
                            $categoryNames = [
                                'كيمياء سريرية' => 'كيمياء سريرية',
                                'أمراض الدم' => 'أمراض الدم',
                                'مصرف الدم' => 'مصرف الدم',
                                'الطفيليات' => 'الطفيليات',
                                'الأحياء المجهرية' => 'الأحياء المجهرية',
                                'المناعة السريرية' => 'المناعة السريرية',
                                'فيروسات' => 'فيروسات',
                                'هرمونات' => 'هرمونات',
                                'الخلايا' => 'الخلايا',
                                'متفرقة' => 'متفرقة',
                                'أخرى' => 'أخرى'
                            ];

                            $categoryIcons = [
                                'كيمياء سريرية' => 'fas fa-flask',
                                'أمراض الدم' => 'fas fa-tint',
                                'مصرف الدم' => 'fas fa-syringe',
                                'الطفيليات' => 'fas fa-bug',
                                'الأحياء المجهرية' => 'fas fa-microscope',
                                'المناعة السريرية' => 'fas fa-shield-alt',
                                'فيروسات' => 'fas fa-virus',
                                'هرمونات' => 'fas fa-dna',
                                'الخلايا' => 'fas fa-search',
                                'متفرقة' => 'fas fa-list',
                                'أخرى' => 'fas fa-plus'
                            ];

                            // تجميع التحاليل حسب الفئة
                            $grouped = LabTest::active()->get()->groupBy('category');

                            // تجميع الفئات في مجموعات أكبر
                            $mainGroups = [
                                'كيمياء سريرية' => [
                                    'categories' => ['كيمياء سريرية'],
                                    'icon' => 'fas fa-flask',
                                    'color' => 'success'
                                ],
                                'أمراض الدم والمصارف' => [
                                    'categories' => ['أمراض الدم', 'مصرف الدم'],
                                    'icon' => 'fas fa-tint',
                                    'color' => 'danger'
                                ],
                                'الميكروبيولوجيا' => [
                                    'categories' => ['الأحياء المجهرية', 'الطفيليات'],
                                    'icon' => 'fas fa-microscope',
                                    'color' => 'info'
                                ],
                                'المناعة والهرمونات' => [
                                    'categories' => ['المناعة السريرية', 'فيروسات', 'هرمونات'],
                                    'icon' => 'fas fa-shield-alt',
                                    'color' => 'warning'
                                ],
                                'الخلايا والأنسجة' => [
                                    'categories' => ['الخلايا'],
                                    'icon' => 'fas fa-search',
                                    'color' => 'secondary'
                                ],
                                'متفرقة' => [
                                    'categories' => ['متفرقة', 'أخرى'],
                                    'icon' => 'fas fa-list',
                                    'color' => 'dark'
                                ]
                            ];
                        @endphp

                        @php $groupIndex = 0; @endphp
                        @foreach($mainGroups as $mainGroupName => $mainGroupData)
                            @php $groupId = 'group_' . $groupIndex; $groupIndex++; @endphp
                            <div class="main-group-section mb-3">
                                <div class="main-group-header bg-{{ $mainGroupData['color'] }} text-white p-3 rounded-top d-flex justify-content-between align-items-center" data-bs-toggle="collapse" data-bs-target="#{{ $groupId }}" style="cursor: pointer;">
                                    <h5 class="mb-0 d-flex align-items-center">
                                        <i class="{{ $mainGroupData['icon'] }} me-2"></i>
                                        {{ $mainGroupName }}
                                        <span class="badge bg-white text-{{ $mainGroupData['color'] }} ms-2">
                                            @php
                                                $totalCount = 0;
                                                foreach($mainGroupData['categories'] as $cat) {
                                                    if(isset($grouped[$cat])) {
                                                        $totalCount += $grouped[$cat]->count();
                                                    }
                                                }
                                                echo $totalCount;
                                            @endphp
                                        </span>
                                    </h5>
                                    <i class="fas fa-chevron-down toggle-icon"></i>
                                </div>
                                <div id="{{ $groupId }}" class="collapse show main-group-body p-3 border border-top-0 rounded-bottom">
                                    <div class="row g-3">
                                        @foreach($mainGroupData['categories'] as $category)
                                            @if(isset($grouped[$category]) && $grouped[$category]->count() > 0)
                                                <div class="col-12">
                                                    <div class="sub-category-section mb-3 p-3 bg-light rounded">
                                                        <h6 class="text-primary mb-3 d-flex align-items-center">
                                                            <i class="{{ $categoryIcons[$category] ?? 'fas fa-list' }} me-2"></i>
                                                            {{ $categoryNames[$category] ?? ucfirst($category) }}
                                                            <span class="badge bg-primary ms-2">{{ $grouped[$category]->count() }}</span>
                                                        </h6>
                                                        <div class="row g-2">
                                                            @foreach($grouped[$category] as $test)
                                                            <div class="col-md-6 col-lg-4">
                                                                <div class="form-check test-item p-2 border rounded hover-shadow">
                                                                    <input class="form-check-input" type="checkbox" 
                                                                        name="tests[]" 
                                                                        value="{{ $test->name }}" 
                                                                        id="test_{{ $test->id }}"
                                                                        {{ in_array($test->name, $request->details['tests'] ?? []) ? 'checked' : '' }}>
                                                                    <label class="form-check-label w-100" for="test_{{ $test->id }}">
                                                                        <div class="d-flex justify-content-between align-items-start">
                                                                            <div>
                                                                                <strong>{{ $test->name }}</strong>
                                                                                @if($test->description)
                                                                                    <br><small class="text-muted">{{ Str::limit($test->description, 50) }}</small>
                                                                                @endif
                                                                            </div>
                                                                            <i class="fas fa-check-circle text-success opacity-0 check-icon"></i>
                                                                        </div>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            حفظ التحاليل المختارة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- عرض النتائج إذا كانت موجودة -->
    @if($request->result && $request->status == 'completed')
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            نتائج الفحص
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
                            $testResults = [];
                            if (is_array($resultData)) {
                                $testResults = $resultData['test_results'] ?? [];
                            }
                            $notes = is_array($resultData) ? ($resultData['notes'] ?? $request->result) : $request->result;
                        @endphp

                        @if(count($testResults) > 0)
                            <div class="table-responsive mb-3">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>الفحص</th>
                                            <th>القيمة</th>
                                            <th>الوحدة</th>
                                            <th>المرجع</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($testResults as $testName => $testData)
                                        <tr>
                                            <td><strong>{{ $testName }}</strong></td>
                                            <td>{{ (is_array($testData) ? ($testData['value'] ?? '-') : '-') }}</td>
                                            <td>{{ (is_array($testData) ? ($testData['unit'] ?? '-') : '-') }}</td>
                                            <td>{{ (is_array($testData) ? ($testData['reference'] ?? '-') : '-') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if($notes)
                            <div class="mt-3">
                                <h6>ملاحظات إضافية:</h6>
                                <p class="text-muted">{{ $notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- نموذج تحديث حالة الطلب -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        تحديث حالة الطلب
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('staff.requests.update', $request) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">حالة الطلب</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="pending" {{ $request->status == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                                        <option value="in_progress" {{ $request->status == 'in_progress' ? 'selected' : '' }}>قيد التنفيذ</option>
                                        <option value="completed" {{ $request->status == 'completed' ? 'selected' : '' }}>مكتمل</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                @if($request->type == 'lab')
                                    <!-- جدول إدخال نتائج التحاليل -->
                                    <div class="lab-results-section">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center" style="width: 50px;">#</th>
                                                        <th>التحليل</th>
                                                        <th style="width: 200px;">القيمة</th>
                                                        <th style="width: 120px;">الوحدة</th>
                                                        <th style="width: 100px;">الحالة</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        // دعم كل من tests و lab_test_ids
                                                        $testsList = [];
                                                        $requestDetails = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                                                        
                                                        if (isset($requestDetails['tests']) && is_array($requestDetails['tests'])) {
                                                            $testsList = $requestDetails['tests'];
                                                        } elseif (isset($requestDetails['lab_test_ids']) && is_array($requestDetails['lab_test_ids'])) {
                                                            // تحويل IDs إلى أسماء
                                                            foreach ($requestDetails['lab_test_ids'] as $testId) {
                                                                $labTest = \App\Models\LabTest::find($testId);
                                                                if ($labTest) {
                                                                    $testsList[] = $labTest->name;
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    @foreach($testsList as $index => $test)
                                                        @php
                                                            $testIcon = getTestIcon($test);
                                                        @endphp
                                                        <tr class="test-row" data-test="{{ $test }}">
                                                            <td class="text-center">
                                                                {{ $index + 1 }}
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="test-icon me-2">
                                                                        <i class="{{ $testIcon }}"></i>
                                                                    </div>
                                                                    <div>
                                                                        <strong>{{ $test }}</strong>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="input-group">
                                                                    <span class="input-group-text">
                                                                        <i class="fas fa-tachometer-alt text-success"></i>
                                                                    </span>
                                                                    <input type="text"
                                                                           class="form-control test-value"
                                                                           name="test_results[{{ $test }}][value]"
                                                                           value="{{ old('test_results.' . $test . '.value', (is_array($savedTestResults) && isset($savedTestResults[$test]) && is_array($savedTestResults[$test])) ? $savedTestResults[$test]['value'] : '') }}"
                                                                           placeholder="أدخل القيمة"
                                                                           data-test="{{ $test }}">
                                                                    <!-- إضافة حقل مخفي لوحدة القياس -->
                                                                    <input type="hidden"
                                                                           name="test_results[{{ $test }}][unit]"
                                                                           value="{{ getTestUnit($test, $labTests) }}">
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center unit-display" data-test="{{ $test }}">
                                                                    <i class="fas fa-balance-scale text-info me-2"></i>
                                                                    <span class="unit-value">{{ getTestUnit($test, $labTests) }}</span>
                                                                    <span class="unit-tooltip ms-1" data-bs-toggle="tooltip" 
                                                                          data-bs-placement="top" 
                                                                          title="وحدة القياس المعيارية للتحليل">
                                                                        <i class="fas fa-info-circle text-info"></i>
                                                                    </span>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="text-center">
                                                                    <span class="status-indicator" id="indicator-{{ $index }}">
                                                                        <i class="fas fa-circle text-muted"></i>
                                                                    </span>
                                                                    <small class="status-text d-block mt-1" id="status-{{ $index }}">
                                                                        قيد الإدخال
                                                                    </small>
                                                                </div>
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
                                                    <span id="pending-count">{{ count($testsList) }}</span> غير مكتمل
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mt-3">
                                            <label for="result" class="form-label">
                                                <i class="fas fa-comment-medical text-secondary me-2"></i>
                                                ملاحظات إضافية
                                            </label>
                                            <textarea class="form-control"
                                                      id="result"
                                                      name="result"
                                                      rows="3"
                                                      placeholder="ملاحظات إضافية حول النتائج أو تفسيرها">{{ old('result', $savedNotes) }}</textarea>
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
                                @else
                                    <label for="result" class="form-label">النتيجة / التقرير</label>
                                    <textarea class="form-control" id="result" name="result"
                                              rows="4" placeholder="أدخل نتائج الفحص أو التقرير">{{ old('result', $request->result) }}</textarea>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>
                                حفظ التحديث
                            </button>
                            @if($request->status == 'pending')
                                <button type="button" class="btn btn-primary" onclick="startProcessing()">
                                    <i class="fas fa-play me-1"></i>
                                    بدء المعالجة
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- تاريخ الزيارة -->
    <div class="row">
        <div class="col-12">
            @if($request->visit)
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        معلومات الزيارة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>تاريخ الزيارة:</strong> {{ $request->visit->visit_date ? $request->visit->visit_date->format('Y-m-d') : 'غير محدد' }}</p>
                            <p><strong>نوع الزيارة:</strong> {{ $request->visit->visit_type_text ?? 'غير محدد' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>الشكوى الرئيسية:</strong></p>
                            <p class="text-muted">{{ $request->visit->chief_complaint ? Str::limit($request->visit->chief_complaint, 100) : 'غير محدد' }}</p>
                        </div>
                    </div>
                    @if($request->visit->diagnosis)
                    <hr>
                    <p><strong>التشخيص:</strong></p>
                    @php $diag = is_string($request->visit->diagnosis) ? json_decode($request->visit->diagnosis, true) : $request->visit->diagnosis; @endphp
                    @if($diag['code'] ?? false)
                        @if($diag['code'] === 'other' && isset($diag['custom_code']))
                            <p class="text-muted"><strong>رمز ICD:</strong> {{ $diag['custom_code'] }}</p>
                        @elseif($diag['code'] !== 'other')
                            <p class="text-muted"><strong>رمز ICD-10:</strong> {{ $diag['code'] }}</p>
                        @endif
                    @endif
                    <p class="text-muted"><strong>الوصف:</strong> {{ $diag['description'] ?? $request->visit->diagnosis }}</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function startProcessing() {
    document.getElementById('status').value = 'in_progress';
    document.querySelector('form').submit();
}

// وظائف تحليل النتائج المخبرية
document.addEventListener('DOMContentLoaded', function() {
    initializeLabResults();
    
    // تفعيل tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // إضافة تأثيرات تفاعلية لعرض الوحدات
    const unitDisplays = document.querySelectorAll('.unit-display');
    unitDisplays.forEach(display => {
        display.addEventListener('mouseover', function() {
            this.style.transform = 'scale(1.05)';
        });
        display.addEventListener('mouseout', function() {
            this.style.transform = 'scale(1)';
        });
    });
});

// دالة للحصول على الوحدة التلقائية للفحص
function getTestUnit(testName) {
    // البحث في بيانات قاعدة البيانات المرسلة من PHP
    const labTestsData = @json($labTests->toArray());

    if (labTestsData[testName] && labTestsData[testName].unit) {
        return labTestsData[testName].unit;
    }

    // الاحتياطي للفحوصات غير الموجودة في قاعدة البيانات
    const name = testName.toLowerCase();

    if (name.includes('سكر') || name.includes('glucose')) {
        return 'mg/dL';
    } else if (name.includes('ضغط') || name.includes('pressure')) {
        return 'mmHg';
    } else if (name.includes('كوليسترول') || name.includes('cholesterol')) {
        return 'mg/dL';
    } else if (name.includes('بيليروبين') || name.includes('bilirubin')) {
        return 'mg/dL';
    } else if (name.includes('كرياتينين') || name.includes('creatinine')) {
        return 'mg/dL';
    } else if (name.includes('يوريا') || name.includes('urea')) {
        return 'mg/dL';
    } else if (name.includes('sgot') || name.includes('ast')) {
        return 'U/L';
    } else if (name.includes('sgpt') || name.includes('alt')) {
        return 'U/L';
    } else if (name.includes('الصفائح') || name.includes('platelets')) {
        return '/µL';
    } else if (name.includes('الهيموغلوبين') || name.includes('hemoglobin')) {
        return 'g/dL';
    } else if (name.includes('الكرات البيضاء') || name.includes('wbc')) {
        return '/µL';
    } else if (name.includes('الكرات الحمراء') || name.includes('rbc')) {
        return 'million/µL';
    } else if (name.includes('الهيماتوكريت') || name.includes('hematocrit')) {
        return '%';
    } else if (name.includes('هرمون') || name.includes('hormone')) {
        return 'mIU/mL';
    } else if (name.includes('فيتامين') || name.includes('vitamin')) {
        return 'ng/mL';
    } else if (name.includes('حديد') || name.includes('iron')) {
        return 'µg/dL';
    } else if (name.includes('كالسيوم') || name.includes('calcium')) {
        return 'mg/dL';
    } else if (name.includes('صوديوم') || name.includes('sodium')) {
        return 'mEq/L';
    } else if (name.includes('بوتاسيوم') || name.includes('potassium')) {
        return 'mEq/L';
    } else {
        return '';
    }
}

function initializeLabResults() {
    const testInputs = document.querySelectorAll('.test-value');
    const unitInputs = document.querySelectorAll('.test-unit');

    // تحديث الوحدات التلقائية عند التحميل
    unitInputs.forEach((unitInput) => {
        const testName = unitInput.dataset.test;
        const defaultUnit = unitInput.dataset.defaultUnit || getTestUnit(testName);
        // تحديث الوحدة إذا كانت فارغة
        if (defaultUnit && !unitInput.value.trim()) {
            unitInput.value = defaultUnit;
        }
    });

    testInputs.forEach((input, index) => {
        // تحليل فوري عند الإدخال
        input.addEventListener('keyup', function() {
            analyzeResult(this, index);
        });

        // تحليل عند الإدخال
        input.addEventListener('input', function() {
            analyzeResult(this, index);
        });

        // تحليل عند فقدان التركيز
        input.addEventListener('blur', function() {
            analyzeResult(this, index);
        });

        // تحليل القيمة الموجودة عند التحميل
        analyzeResult(input, index);
    });

    updateSummary();
}

function analyzeResult(input, index) {
    const value = parseFloat(input.value);
    const testRow = input.closest('.test-row');
    const indicator = testRow.querySelector('.status-indicator i');
    const statusText = testRow.querySelector('.status-text');
    const testName = input.dataset.test ? input.dataset.test.toLowerCase() : '';

    // console.log('تحليل النتيجة:', { testName, value, inputValue: input.value });

    if (isNaN(value) || input.value.trim() === '' || value === 0) {
        // قيمة غير صحيحة أو فارغة أو صفر
        indicator.className = 'fas fa-circle text-muted';
        statusText.innerHTML = '<i class="fas fa-info-circle"></i> أدخل قيمة صحيحة';
        testRow.classList.remove('result-normal', 'result-high', 'result-low');
        return;
    }

    // ملاحظة مهمة: هذه قيم مرجعية تقريبية عامة فقط
    // القيم الحقيقية تختلف حسب المختبر، عمر المريض، جنسه، والوحدات المستخدمة
    // يجب دائمًا مراجعة الطبيب أو المختبر للقيم المرجعية الصحيحة
    let resultType = 'normal';
    let statusMessage = '<i class="fas fa-info-circle text-info"></i> تم إدخال القيمة - راجع المرجع الطبي';
    let indicatorClass = 'fas fa-circle text-info';

    // تحليلات أساسية لأنواع شائعة من الفحوصات (قيم تقريبية عامة)
    if (testName.includes('سكر') || testName.includes('glucose')) {
        if (value < 70) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else if (value > 140) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (70-140 mg/dL)';
        }
    } else if (testName.includes('ضغط') || testName.includes('pressure')) {
        if (value > 140) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<140 mmHg)';
        }
    } else if (testName.includes('كوليسترول') || testName.includes('cholesterol')) {
        if (value > 200) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<200 mg/dL)';
        }
    } else if (testName.includes('بيليروبين') || testName.includes('bilirubin')) {
        if (value > 1.2) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<1.2 mg/dL)';
        }
    } else if (testName.includes('كرياتينين') || testName.includes('creatinine')) {
        if (value > 1.2) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<1.2 mg/dL)';
        }
    } else if (testName.includes('يوريا') || testName.includes('urea')) {
        if (value > 50) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<50 mg/dL)';
        }
    } else if (testName.includes('sgot') || testName.includes('ast')) {
        if (value > 40) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<40 U/L)';
        }
    } else if (testName.includes('sgpt') || testName.includes('alt')) {
        if (value > 41) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (<41 U/L)';
        }
    } else if (testName.includes('الصفائح') || testName.includes('platelets')) {
        if (value < 150000) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else if (value > 450000) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (150k-450k /µL)';
        }
    } else if (testName.includes('الهيموغلوبين') || testName.includes('hemoglobin')) {
        if (value < 12) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (>12 g/dL)';
        }
    } else if (testName.includes('الكرات البيضاء') || testName.includes('wbc')) {
        if (value < 4000) {
            resultType = 'low';
            statusMessage = '<i class="fas fa-arrow-down text-warning"></i> قيمة منخفضة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-down text-warning';
        } else if (value > 11000) {
            resultType = 'high';
            statusMessage = '<i class="fas fa-arrow-up text-danger"></i> قيمة مرتفعة (تحتاج مراجعة طبية)';
            indicatorClass = 'fas fa-arrow-up text-danger';
        } else {
            statusMessage = '<i class="fas fa-check-circle text-success"></i> في المدى الطبيعي العام (4k-11k /µL)';
        }
    } else {
        // للفحوصات غير المحددة، نعتبرها طبيعية
        statusMessage = '<i class="fas fa-info-circle text-info"></i> تم إدخال القيمة - راجع المرجع الطبي';
    }

    // تحديث المؤشرات البصرية
    indicator.className = indicatorClass;
    statusText.innerHTML = statusMessage;

    // تحديث فئة الصف
    testRow.classList.remove('result-normal', 'result-high', 'result-low');
    testRow.classList.add('result-' + resultType);

    updateSummary();
}

function updateSummary() {
    const testRows = document.querySelectorAll('.test-row');
    let normal = 0, high = 0, low = 0, pending = 0;

    testRows.forEach(row => {
        const input = row.querySelector('.test-value');
        const value = parseFloat(input.value);

        if (isNaN(value) || value === 0) {
            pending++;
        } else if (row.classList.contains('result-high')) {
            high++;
        } else if (row.classList.contains('result-low')) {
            low++;
        } else {
            normal++;
        }
    });

    document.getElementById('normal-count').textContent = normal;
    document.getElementById('high-count').textContent = high;
    document.getElementById('low-count').textContent = low;
    document.getElementById('pending-count').textContent = pending;
}
</script>

<style>
/* تصميم محسن للقراءات المخبرية */
.lab-results-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 20px;
    margin: 15px 0;
    border: 1px solid #dee2e6;
}

.lab-tests-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.lab-test-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
    overflow: hidden;
}

.lab-test-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    border-color: #007bff;
}

.lab-test-card.result-normal {
    border-color: #28a745;
    background: linear-gradient(135deg, #f8fff8 0%, #ffffff 100%);
}

.lab-test-card.result-high {
    border-color: #dc3545;
    background: linear-gradient(135deg, #fff8f8 0%, #ffffff 100%);
}

.lab-test-card.result-low {
    border-color: #ffc107;
    background: linear-gradient(135deg, #fffef8 0%, #ffffff 100%);
}

.test-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 12px 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.test-icon {
    background: rgba(255,255,255,0.2);
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.3s ease;
}

.test-icon:hover {
    transform: scale(1.1);
    background: rgba(255,255,255,0.3);
}

.test-info h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.test-info small {
    font-size: 11px;
    opacity: 0.9;
}

.test-inputs {
    padding: 15px;
}

.input-group-wrapper {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.input-group-text {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #6c757d;
    min-width: 40px;
    justify-content: center;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.result-indicator {
    background: transparent !important;
    border-left: none !important;
}

.test-status {
    padding: 8px 15px 12px;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
    text-align: center;
}

.status-text {
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.results-summary {
    border: 1px solid #dee2e6;
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
}

.stat-item i {
    font-size: 16px;
}

/* تحسينات إضافية للتصميم */
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

.stat-item {
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

/* تحسينات للأزرار */
.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
}

/* تحسينات للنصوص */
.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.text-muted {
    color: #6c757d !important;
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

/* تحسينات لقسم المراجع */
details {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 10px 15px;
    background: #f8f9fa;
}

details summary {
    list-style: none;
    cursor: pointer;
    user-select: none;
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

/* تحسينات للأيقونات */
.fas, .far {
    transition: all 0.3s ease;
}

.test-icon i {
    filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
}

/* تأثيرات انتقال سلسة */
.lab-test-card {
    position: relative;
}

.lab-test-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #007bff;
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.lab-test-card:hover::before,
.lab-test-card.result-normal::before {
    background: #28a745;
    transform: scaleX(1);
}

.lab-test-card.result-high::before {
    background: #dc3545;
    transform: scaleX(1);
}

.lab-test-card.result-low::before {
    background: #ffc107;
    transform: scaleX(1);
}

/* تحسينات لوحدة القياس */
.unit-display {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    padding: 4px 8px;
    border-radius: 0 4px 4px 0;
    transition: all 0.3s ease;
}

.unit-display:hover {
    background: #e9ecef;
}

.unit-value {
    font-weight: 500;
    color: #495057;
    font-size: 0.9rem;
}

.unit-tooltip {
    cursor: help;
    opacity: 0.7;
    transition: opacity 0.3s ease;
}

.unit-tooltip:hover {
    opacity: 1;
}

.unit-display[data-test*="سكر"] .unit-value,
.unit-display[data-test*="glucose"] .unit-value {
    color: #dc3545;
}

.unit-display[data-test*="ضغط"] .unit-value,
.unit-display[data-test*="pressure"] .unit-value {
    color: #0d6efd;
}

.unit-display[data-test*="كوليسترول"] .unit-value,
.unit-display[data-test*="cholesterol"] .unit-value {
    color: #fd7e14;
}

.unit-display[data-test*="كرياتينين"] .unit-value,
.unit-display[data-test*="creatinine"] .unit-value,
.unit-display[data-test*="يوريا"] .unit-value,
.unit-display[data-test*="urea"] .unit-value {
    color: #198754;
}

.unit-display[data-test*="هيموغلوبين"] .unit-value,
.unit-display[data-test*="hemoglobin"] .unit-value {
    color: #6f42c1;
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

.test-row .test-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0, 123, 255, 0.1);
    transition: all 0.3s ease;
}

.test-row:hover .test-icon {
    transform: scale(1.1);
    background: rgba(0, 123, 255, 0.2);
}

.test-row .form-control {
    border-radius: 0 4px 4px 0;
}

.test-row .input-group-text {
    border-radius: 4px 0 0 4px;
}

.status-indicator {
    font-size: 1.2rem;
    display: inline-block;
    transition: all 0.3s ease;
}

.status-text {
    font-size: 0.75rem;
    color: #6c757d;
}

/* تصميم متجاوب للجدول */
.table-responsive {
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
}

@media (max-width: 768px) {
    .table > :not(caption) > * > * {
        padding: 0.5rem;
    }
    
    .test-row .test-icon {
        width: 28px;
        height: 28px;
    }
}

/* تحسين مظهر الحقول في الجدول */
.test-row .form-control:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    border-color: #80bdff;
}

.test-row.result-normal {
    background-color: rgba(40, 167, 69, 0.05);
}

.test-row.result-high {
    background-color: rgba(220, 53, 69, 0.05);
}

.test-row.result-low {
    background-color: rgba(255, 193, 7, 0.05);
}

/* تحسينات للأجهزة المحمولة */
@media (max-width: 768px) {
    .lab-tests-grid {
        grid-template-columns: 1fr;
    }

    .summary-stats {
        flex-direction: column;
        align-items: center;
    }

    .test-header {
        padding: 10px 12px;
    }

    .test-inputs {
        padding: 12px;
    }

    .lab-results-section {
        padding: 15px;
        margin: 10px 0;
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
}

/* تحسينات للشاشات الكبيرة */
@media (min-width: 1200px) {
    .lab-tests-grid {
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    }
}

.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

.test-item {
    transition: all 0.2s ease;
}

.test-item:hover {
    background-color: #f8f9fa;
}

.form-check-input:checked + .form-check-label .check-icon {
    opacity: 1 !important;
}

.main-group-header {
    transition: all 0.3s ease;
}

.main-group-header:hover {
    opacity: 0.9;
}

.toggle-icon {
    transition: transform 0.3s ease;
}

.collapsed .toggle-icon {
    transform: rotate(-90deg);
}
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // البحث في التحاليل
    $('#testSearchInput').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleCount = 0;
        let totalCount = 0;

        if (searchTerm === '') {
            // إظهار كل شيء عند مسح البحث
            $('.test-item').closest('.col-md-6').show();
            $('.sub-category-section').show();
            $('.main-group-section').show();
            $('#searchResultsCount').text('');
            return;
        }

        // إخفاء كل شيء أولاً
        $('.main-group-section').hide();
        $('.sub-category-section').hide();
        $('.test-item').closest('.col-md-6').hide();

        // البحث في كل اختبار
        $('.test-item').each(function() {
            totalCount++;
            const testName = $(this).find('strong').text().toLowerCase();
            const testDesc = $(this).find('.text-muted').text().toLowerCase();
            
            if (testName.includes(searchTerm) || testDesc.includes(searchTerm)) {
                visibleCount++;
                $(this).closest('.col-md-6').show();
                $(this).closest('.sub-category-section').show();
                $(this).closest('.main-group-section').show();
            }
        });

        // تحديث عداد النتائج
        if (visibleCount === 0) {
            $('#searchResultsCount').html('<i class="fas fa-exclamation-circle text-warning"></i> لا توجد نتائج للبحث');
        } else {
            $('#searchResultsCount').html(`<i class="fas fa-check-circle text-success"></i> تم العثور على ${visibleCount} نتيجة من أصل ${totalCount}`);
        }
    });

    // زر مسح البحث
    $('#clearSearch').on('click', function() {
        $('#testSearchInput').val('').trigger('keyup').focus();
    });

    // تفعيل الـ collapse للمجموعات
    $('.main-group-header').on('click', function() {
        const target = $(this).data('bs-target');
        $(target).collapse('toggle');
    });

    // تحديث أيقونة التبديل
    $('.collapse').on('shown.bs.collapse hidden.bs.collapse', function() {
        const header = $(this).prev('.main-group-header');
        const icon = header.find('.toggle-icon');
        if ($(this).hasClass('show')) {
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        } else {
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        }
    });

    // تحديث أيقونات التحقق عند تغيير التحاليل
    $('.form-check-input').on('change', function() {
        const checkIcon = $(this).siblings('.form-check-label').find('.check-icon');
        if ($(this).is(':checked')) {
            checkIcon.removeClass('opacity-0');
        } else {
            checkIcon.addClass('opacity-0');
        }
    });

    // تحديث حالة الأيقونات عند التحميل
    $('.form-check-input:checked').each(function() {
        $(this).siblings('.form-check-label').find('.check-icon').removeClass('opacity-0');
    });
});
</script>
@endsection