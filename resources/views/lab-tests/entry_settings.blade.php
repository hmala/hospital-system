@extends('layouts.app')

@section('title', 'إعدادات وتخصيص أنماط إدخال نتائج الفحوصات المختبرية')

@section('content')
<div class="container-fluid py-3">

    <!-- شريط العنوان والرجوع -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                <i class="fas fa-sliders-h fs-4"></i>
            </div>
            <div>
                <h4 class="mb-1 fw-bold text-dark">إعدادات أنماط إدخال نتائج الفحوصات المختبرية</h4>
                <p class="text-muted mb-0 small">
                    تحديد آلية إدخال النتائج لكل فحص (قيمة رقمية مباشرة، قيم فرعية مفصلة، مرفق تقرير الجهاز، أو كلاهما معاً) على مستوى النظام
                </p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('lab-tests.pricing-settings.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-tags me-1"></i> إعدادات الأسعار
            </a>
            <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> قائمة الفحوصات
            </a>
        </div>
    </div>

    <!-- بطاقات الإحصائيات (KPI Cards) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-body p-3 text-center">
                    <span class="text-muted d-block small mb-1 fw-semibold">إجمالي الفحوصات</span>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">{{ number_format($stats['total']) }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">فحص نشط بالنظام</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 bg-primary bg-opacity-10 border-start border-primary border-3">
                <div class="card-body p-3 text-center">
                    <span class="text-primary d-block small mb-1 fw-bold">
                        <i class="fas fa-keyboard me-1"></i> قيمة مباشرة مفردة
                    </span>
                    <h3 class="fw-bold text-primary mb-0 font-monospace">{{ number_format($stats['single_value']) }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">إدخال قيمة ورقم مباشرة</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 bg-purple bg-opacity-10 border-start border-3" style="border-color: #6f42c1 !important; background-color: rgba(111, 66, 193, 0.08);">
                <div class="card-body p-3 text-center">
                    <span class="d-block small mb-1 fw-bold" style="color: #6f42c1;">
                        <i class="fas fa-list-ol me-1"></i> قيم فرعية مفصلة
                    </span>
                    <h3 class="fw-bold mb-0 font-monospace" style="color: #6f42c1;">{{ number_format($stats['sub_tests']) }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">معاملات وباراميترات فرعية</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 bg-warning bg-opacity-10 border-start border-warning border-3">
                <div class="card-body p-3 text-center">
                    <span class="text-warning-emphasis d-block small mb-1 fw-bold">
                        <i class="fas fa-file-medical-alt me-1"></i> مرفق تقرير جهاز
                    </span>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">{{ number_format($stats['attachment_only']) }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">رفع مستند / تقرير PDF/صورة</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 bg-success bg-opacity-10 border-start border-success border-3">
                <div class="card-body p-3 text-center">
                    <span class="text-success d-block small mb-1 fw-bold">
                        <i class="fas fa-random me-1"></i> كلاهما (مرن)
                    </span>
                    <h3 class="fw-bold text-success mb-0 font-monospace">{{ number_format($stats['flexible']) }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">قيم فرعية + إرفاق تقرير</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="card border-0 shadow-sm h-100 bg-info bg-opacity-10 border-start border-info border-3">
                <div class="card-body p-3 text-center">
                    <span class="text-info d-block small mb-1 fw-bold">
                        <i class="fas fa-layer-group me-1"></i> تحاليل لها قيم فرعية
                    </span>
                    <h3 class="fw-bold text-info mb-0 font-monospace">{{ number_format($stats['with_subtests_count']) }}</h3>
                    <small class="text-muted" style="font-size: 0.72rem;">(CBC, ABG, GUE, GSE, Semen)</small>
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

    <!-- بطاقة الفلاتر والبحث -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('lab-tests.entry-settings') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control" placeholder="ابحث باسم الفحص أو الكود..." value="{{ request('q') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">جميع التصنيفات الفرعية</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="entry_type" class="form-select form-select-sm">
                        <option value="">جميع أنماط الإدخال</option>
                        <option value="single_value" {{ request('entry_type') == 'single_value' ? 'selected' : '' }}>قيمة مباشرة مفردة</option>
                        <option value="sub_tests" {{ request('entry_type') == 'sub_tests' ? 'selected' : '' }}>قيم فرعية مفصلة</option>
                        <option value="attachment_only" {{ request('entry_type') == 'attachment_only' ? 'selected' : '' }}>مرفق تقرير جهاز</option>
                        <option value="flexible" {{ request('entry_type') == 'flexible' ? 'selected' : '' }}>كلاهما (مرن)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="sub_tests_only" class="form-select form-select-sm">
                        <option value="">القيم الفرعية (الكل)</option>
                        <option value="yes" {{ request('sub_tests_only') == 'yes' ? 'selected' : '' }}>يحتوي قيم فرعية فقط</option>
                        <option value="no" {{ request('sub_tests_only') == 'no' ? 'selected' : '' }}>لا يحتوي قيم فرعية</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <select name="per_page" class="form-select form-select-sm" title="عدد العناصر في الصفحة">
                        <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page', '50') == '50' ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                        <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>200</option>
                        <option value="500" {{ request('per_page') == '500' ? 'selected' : '' }}>500</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                    @if(request()->anyFilled(['q', 'category', 'entry_type', 'sub_tests_only', 'per_page']))
                        <a href="{{ route('lab-tests.entry-settings') }}" class="btn btn-sm btn-outline-secondary" title="إعادة تعيين الفلاتر">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- شريط الإجراءات الجماعية (Bulk Actions Bar) -->
    <div class="card border-0 shadow-sm mb-3 bg-light">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="selectAllCheckbox" style="cursor: pointer;">
                        <label class="form-check-label fw-bold small text-dark" for="selectAllCheckbox" style="cursor: pointer;">
                            تحديد الكل في هذه الصفحة (<span id="selectedCount">0</span> محدد)
                        </label>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted fw-semibold">تطبيق نمط موحد على المحدد:</span>
                    <button type="button" class="btn btn-xs btn-outline-primary fw-bold py-1 px-2" onclick="applyBulkEntryType('single_value')">
                        <i class="fas fa-keyboard me-1"></i> قيمة مباشرة
                    </button>
                    <button type="button" class="btn btn-xs fw-bold py-1 px-2 text-white" style="background-color: #6f42c1;" onclick="applyBulkEntryType('sub_tests')">
                        <i class="fas fa-list-ol me-1"></i> قيم فرعية
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-warning fw-bold py-1 px-2" onclick="applyBulkEntryType('attachment_only')">
                        <i class="fas fa-file-medical-alt me-1"></i> مرفق جهاز
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-success fw-bold py-1 px-2" onclick="applyBulkEntryType('flexible')">
                        <i class="fas fa-random me-1"></i> كلاهما (مرن)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول الفحوصات والخيارات الأربعة (Checkboxes per Test) -->
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0" id="entrySettingsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 45px;">
                                <i class="fas fa-check-square text-muted"></i>
                            </th>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th style="min-width: 240px;">اسم الفحص الطبي</th>
                            <th style="width: 130px;">التصنيف الفرعي</th>
                            <th style="width: 100px;" class="text-center">القيم الفرعية</th>
                            <!-- الأعمدة الأربعة للخيارات حسب طلب المستخدم -->
                            <th class="text-center bg-primary bg-opacity-10" style="width: 145px;">
                                <div class="fw-bold text-primary small">
                                    <i class="fas fa-keyboard me-1"></i> 1. قيمة مباشرة
                                </div>
                                <span class="badge bg-primary-subtle text-primary" style="font-size: 0.68rem;">Single Value</span>
                            </th>
                            <th class="text-center" style="width: 145px; background-color: rgba(111, 66, 193, 0.08);">
                                <div class="fw-bold small" style="color: #6f42c1;">
                                    <i class="fas fa-list-ol me-1"></i> 2. قيم فرعية
                                </div>
                                <span class="badge" style="background-color: rgba(111, 66, 193, 0.15); color: #6f42c1; font-size: 0.68rem;">Sub-Tests</span>
                            </th>
                            <th class="text-center bg-warning bg-opacity-10" style="width: 145px;">
                                <div class="fw-bold text-warning-emphasis small">
                                    <i class="fas fa-file-medical-alt me-1"></i> 3. مرفق جهاز
                                </div>
                                <span class="badge bg-warning-subtle text-dark" style="font-size: 0.68rem;">Attachment</span>
                            </th>
                            <th class="text-center bg-success bg-opacity-10" style="width: 145px;">
                                <div class="fw-bold text-success small">
                                    <i class="fas fa-random me-1"></i> 4. كلاهما (مرن)
                                </div>
                                <span class="badge bg-success-subtle text-success" style="font-size: 0.68rem;">Flexible</span>
                            </th>
                            <th class="text-center" style="width: 80px;">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($labTests as $index => $test)
                            @php
                                $currentType = $test->result_entry_type ?: 'single_value';
                                $hasSub = $test->sub_tests_count > 0;
                            @endphp
                            <tr class="test-row-item" id="row-test-{{ $test->id }}" data-test-id="{{ $test->id }}">
                                <!-- خانة التحديد الجماعي -->
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input bulk-test-checkbox" value="{{ $test->id }}" style="cursor: pointer;">
                                </td>

                                <!-- التسلسل -->
                                <td class="text-center text-muted small fw-bold font-monospace">
                                    {{ $labTests->firstItem() + $index }}
                                </td>

                                <!-- اسم الفحص والكود -->
                                <td>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <strong class="text-dark d-block">{{ $test->name }}</strong>
                                            <div class="d-flex align-items-center gap-1 mt-0.5">
                                                @if($test->code)
                                                    <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.7rem;">
                                                        {{ $test->code }}
                                                    </span>
                                                @endif
                                                <small class="text-muted" style="font-size: 0.74rem;">{{ $test->unit ?: 'بدون وحدة' }}</small>
                                            </div>
                                        </div>
                                        <!-- مؤشر الحفظ الآني -->
                                        <div class="save-indicator ms-2 d-none" id="save-indicator-{{ $test->id }}">
                                            <span class="badge bg-success rounded-pill px-2 py-1 shadow-xs" style="font-size: 0.7rem;">
                                                <i class="fas fa-check me-1"></i> تم الحفظ
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- التصنيف الفرعي -->
                                <td>
                                    <span class="badge bg-light text-dark border fw-normal" style="font-size: 0.75rem;">
                                        {{ $test->subcategory ?: 'غير محدد' }}
                                    </span>
                                </td>

                                <!-- عدد القيم الفرعية -->
                                <td class="text-center">
                                    @if($hasSub)
                                        <span class="badge bg-info-subtle text-info border border-info-subtle fw-bold" title="{{ $test->sub_tests_count }} باراميتر فرعي مسجل">
                                            <i class="fas fa-layer-group me-1"></i> {{ $test->sub_tests_count }} فرعي
                                        </span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>

                                <!-- خيار 1: قيمة مباشرة مفردة -->
                                <td class="text-center align-middle bg-primary bg-opacity-10 py-2">
                                    <label class="option-box-label d-flex align-items-center justify-content-center p-2 rounded cursor-pointer m-0 {{ $currentType === 'single_value' ? 'active-option-single' : '' }}" style="cursor: pointer;">
                                        <input type="checkbox"
                                               class="form-check-input entry-type-checkbox entry-box-{{ $test->id }}"
                                               name="test_entry_type_{{ $test->id }}"
                                               value="single_value"
                                               data-test-id="{{ $test->id }}"
                                               {{ $currentType === 'single_value' ? 'checked' : '' }}
                                               onchange="handleEntryTypeChange(this, {{ $test->id }}, 'single_value')">
                                    </label>
                                </td>

                                <!-- خيار 2: قيم فرعية مفصلة -->
                                <td class="text-center align-middle py-2" style="background-color: rgba(111, 66, 193, 0.08);">
                                    <label class="option-box-label d-flex align-items-center justify-content-center p-2 rounded cursor-pointer m-0 {{ $currentType === 'sub_tests' ? 'active-option-subtests' : '' }}" style="cursor: pointer;">
                                        <input type="checkbox"
                                               class="form-check-input entry-type-checkbox entry-box-{{ $test->id }}"
                                               name="test_entry_type_{{ $test->id }}"
                                               value="sub_tests"
                                               data-test-id="{{ $test->id }}"
                                               {{ $currentType === 'sub_tests' ? 'checked' : '' }}
                                               onchange="handleEntryTypeChange(this, {{ $test->id }}, 'sub_tests')">
                                    </label>
                                </td>

                                <!-- خيار 3: مرفق تقرير جهاز -->
                                <td class="text-center align-middle bg-warning bg-opacity-10 py-2">
                                    <label class="option-box-label d-flex align-items-center justify-content-center p-2 rounded cursor-pointer m-0 {{ $currentType === 'attachment_only' ? 'active-option-attachment' : '' }}" style="cursor: pointer;">
                                        <input type="checkbox"
                                               class="form-check-input entry-type-checkbox entry-box-{{ $test->id }}"
                                               name="test_entry_type_{{ $test->id }}"
                                               value="attachment_only"
                                               data-test-id="{{ $test->id }}"
                                               {{ $currentType === 'attachment_only' ? 'checked' : '' }}
                                               onchange="handleEntryTypeChange(this, {{ $test->id }}, 'attachment_only')">
                                    </label>
                                </td>

                                <!-- خيار 4: كلاهما (مرن) -->
                                <td class="text-center align-middle bg-success bg-opacity-10 py-2">
                                    <label class="option-box-label d-flex align-items-center justify-content-center p-2 rounded cursor-pointer m-0 {{ $currentType === 'flexible' ? 'active-option-flexible' : '' }}" style="cursor: pointer;">
                                        <input type="checkbox"
                                               class="form-check-input entry-type-checkbox entry-box-{{ $test->id }}"
                                               name="test_entry_type_{{ $test->id }}"
                                               value="flexible"
                                               data-test-id="{{ $test->id }}"
                                               {{ $currentType === 'flexible' ? 'checked' : '' }}
                                               onchange="handleEntryTypeChange(this, {{ $test->id }}, 'flexible')">
                                    </label>
                                </td>

                                <!-- حالة الفحص -->
                                <td class="text-center">
                                    <span class="badge {{ $test->is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill" style="font-size: 0.72rem;">
                                        {{ $test->is_active ? 'مفعل' : 'معطل' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fas fa-search me-1"></i> لم يتم العثور على أي فحوصات تطابق خيارات البحث الحالية.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($labTests->hasPages())
            <div class="card-footer bg-white border-top py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        عرض الفحوصات من {{ $labTests->firstItem() }} إلى {{ $labTests->lastItem() }} من إجمالي {{ $labTests->total() }} فحص
                    </small>
                    <div>
                        {{ $labTests->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>

</div>

<!-- Modal تأكيد التطبيق الجماعي -->
<div class="modal fade" id="bulkConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-layer-group me-2"></i>تأكيد تطبيق النمط الجماعي</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">هل أنت متأكد من تطبيق النمط التالي:</p>
                <div class="alert alert-info py-2 px-3 fw-bold" id="bulkTargetLabel"></div>
                <p class="mb-0 text-muted small">
                    على عدد (<strong id="bulkTargetCount" class="text-danger">0</strong>) فحص مختبري محدد؟
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary fw-bold" id="confirmBulkSubmitBtn" onclick="executeBulkUpdate()">
                    <i class="fas fa-check me-1"></i> نعم، تطبيق التغيير الآن
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .cursor-pointer { cursor: pointer; }
    .form-check-input.entry-type-checkbox {
        width: 22px;
        height: 22px;
        margin: 0;
        cursor: pointer;
        border-width: 2px;
        transition: transform 0.15s ease, background-color 0.15s ease;
    }
    .form-check-input.entry-type-checkbox:hover {
        transform: scale(1.15);
    }
    .option-box-label {
        transition: background-color 0.2s ease;
        border-radius: 6px;
    }
    .option-box-label:hover {
        background-color: rgba(0, 0, 0, 0.04);
    }
    .active-option-single {
        background-color: rgba(13, 110, 253, 0.15) !important;
    }
    .active-option-subtests {
        background-color: rgba(111, 66, 193, 0.2) !important;
    }
    .active-option-attachment {
        background-color: rgba(255, 193, 7, 0.25) !important;
    }
    .active-option-flexible {
        background-color: rgba(25, 135, 84, 0.2) !important;
    }
    .save-indicator {
        animation: fadeInOut 2s ease forwards;
    }
    @keyframes fadeInOut {
        0% { opacity: 0; transform: translateY(-3px); }
        20% { opacity: 1; transform: translateY(0); }
        80% { opacity: 1; }
        100% { opacity: 0; }
    }
</style>

<script>
    // 1. معالجة النقر على أحد الخيارات الأربعة لكل فحص (حصرية الخيار + حفظ فوري AJAX)
    function handleEntryTypeChange(checkbox, testId, selectedType) {
        // إذا قام المستخدم بإلغاء التحديد نفسه، نعيده إجبارياً لـ single_value
        if (!checkbox.checked) {
            checkbox.checked = true;
            return;
        }

        // إلغاء تحديد الخيارات الأخرى لنفس الفحص
        const rowCheckboxes = document.querySelectorAll('.entry-box-' + testId);
        rowCheckboxes.forEach(cb => {
            if (cb !== checkbox) {
                cb.checked = false;
                const parentLabel = cb.closest('.option-box-label');
                if (parentLabel) {
                    parentLabel.classList.remove('active-option-single', 'active-option-subtests', 'active-option-attachment', 'active-option-flexible');
                }
            }
        });

        // تمييز الخيار النشط بصرياً
        const currentLabel = checkbox.closest('.option-box-label');
        if (currentLabel) {
            if (selectedType === 'single_value') currentLabel.classList.add('active-option-single');
            else if (selectedType === 'sub_tests') currentLabel.classList.add('active-option-subtests');
            else if (selectedType === 'attachment_only') currentLabel.classList.add('active-option-attachment');
            else if (selectedType === 'flexible') currentLabel.classList.add('active-option-flexible');
        }

        // إرسال طلب التحديث عبر AJAX
        fetch("{{ route('lab-tests.entry-settings.update') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                lab_test_id: testId,
                result_entry_type: selectedType
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSaveIndicator(testId);
            } else {
                alert('تعذر تحديث نمط الإدخال: ' + (data.message || 'خطأ غير معروف'));
            }
        })
        .catch(err => {
            console.error(err);
            alert('حدث خطأ في الاتصال بالخادم.');
        });
    }

    // إظهار مؤشر الحفظ الأخضر مؤقتاً
    function showSaveIndicator(testId) {
        const indicator = document.getElementById('save-indicator-' + testId);
        if (indicator) {
            indicator.classList.remove('d-none');
            setTimeout(() => {
                indicator.classList.add('d-none');
            }, 2500);
        }
    }

    // 2. إدارة التحديد الجماعي (Select All)
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const bulkCheckboxes = document.querySelectorAll('.bulk-test-checkbox');
    const selectedCountSpan = document.getElementById('selectedCount');

    function updateSelectedCount() {
        const checkedCount = document.querySelectorAll('.bulk-test-checkbox:checked').length;
        if (selectedCountSpan) selectedCountSpan.textContent = checkedCount;
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = checkedCount > 0 && checkedCount === bulkCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < bulkCheckboxes.length;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            bulkCheckboxes.forEach(cb => {
                cb.checked = this.checked;
            });
            updateSelectedCount();
        });
    }

    bulkCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    // 3. الإجراءات الجماعية
    let targetBulkType = null;

    function applyBulkEntryType(type) {
        const checked = Array.from(document.querySelectorAll('.bulk-test-checkbox:checked')).map(cb => cb.value);
        if (checked.length === 0) {
            alert('يرجى تحديد فحص واحد على الأقل من الجدول لتطبيق الإجراء الجماعي.');
            return;
        }

        targetBulkType = type;
        const labels = {
            'single_value': 'قيمة مباشرة مفردة (Single Value)',
            'sub_tests': 'قيم فرعية مفصلة (Sub-Tests)',
            'attachment_only': 'مرفق تقرير جهاز (Attachment Only)',
            'flexible': 'كلاهما (مرن - Flexible)'
        };

        document.getElementById('bulkTargetLabel').textContent = labels[type] || type;
        document.getElementById('bulkTargetCount').textContent = checked.length;

        const modal = new bootstrap.Modal(document.getElementById('bulkConfirmModal'));
        modal.show();
    }

    function executeBulkUpdate() {
        const checked = Array.from(document.querySelectorAll('.bulk-test-checkbox:checked')).map(cb => cb.value);
        if (checked.length === 0 || !targetBulkType) return;

        const btn = document.getElementById('confirmBulkSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الحفظ...';

        fetch("{{ route('lab-tests.entry-settings.bulk-update') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                test_ids: checked,
                result_entry_type: targetBulkType
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // تحديث واجهة الخيارات في الجدول فوراً
                checked.forEach(testId => {
                    const rowCheckboxes = document.querySelectorAll('.entry-box-' + testId);
                    rowCheckboxes.forEach(cb => {
                        cb.checked = (cb.value === targetBulkType);
                        const parentLabel = cb.closest('.option-box-label');
                        if (parentLabel) {
                            parentLabel.classList.remove('active-option-single', 'active-option-subtests', 'active-option-attachment', 'active-option-flexible');
                            if (cb.value === targetBulkType) {
                                if (targetBulkType === 'single_value') parentLabel.classList.add('active-option-single');
                                else if (targetBulkType === 'sub_tests') parentLabel.classList.add('active-option-subtests');
                                else if (targetBulkType === 'attachment_only') parentLabel.classList.add('active-option-attachment');
                                else if (targetBulkType === 'flexible') parentLabel.classList.add('active-option-flexible');
                            }
                        }
                    });
                    showSaveIndicator(testId);
                });

                bootstrap.Modal.getInstance(document.getElementById('bulkConfirmModal')).hide();
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i> نعم، تطبيق التغيير الآن';
            } else {
                alert('فشل التحديث الجماعي: ' + (data.message || 'خطأ'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check me-1"></i> نعم، تطبيق التغيير الآن';
            }
        })
        .catch(err => {
            console.error(err);
            alert('حدث خطأ بالاتصال أثناء التحديث الجماعي.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i> نعم، تطبيق التغيير الآن';
        });
    }
</script>
@endsection
