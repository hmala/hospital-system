@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-edit me-2"></i>
                    تعديل الفحص المختبري
                </h2>
                <div>
                    <a href="{{ route('lab-tests.sub-tests.index', $labTest) }}" class="btn btn-success me-2">
                        <i class="fas fa-list-check me-1"></i>
                        الفحوصات الفرعية
                        @if($labTest->subTests()->count() > 0)
                            <span class="badge bg-light text-success ms-1">{{ $labTest->subTests()->count() }}</span>
                        @endif
                    </a>
                    <a href="{{ route('lab-tests.references.index', $labTest) }}" class="btn btn-primary me-2">
                        <i class="fas fa-ruler-combined me-1"></i>
                        القيم المرجعية
                    </a>
                    <a href="{{ route('lab-tests.show', $labTest) }}" class="btn btn-info me-2">
                        <i class="fas fa-eye me-1"></i>
                        عرض
                    </a>
                    <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-flask me-2"></i>
                        تعديل بيانات الفحص المختبري
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('lab-tests.update', $labTest) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="code" class="form-label">الكود</label>
                                <input type="text" class="form-control" id="code" name="code"
                                       value="{{ old('code', $labTest->code) }}" maxlength="50">
                                <div class="form-text">رمز مختصر للتعريف (اختياري)</div>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="name" class="form-label">اسم الفحص <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="{{ old('name', $labTest->name) }}" required>
                                <div class="form-text">أدخل اسم الفحص المختبري باللغة العربية</div>
                            </div>

                            <input type="hidden" name="main_category" value="المختبر">
                            <div class="col-md-6 mb-3">
                                <label for="subcategory" class="form-label">الفئة الفرعية</label>
                                <select class="form-select" id="subcategory_select" onchange="toggleSubcategoryInput()">
                                    <option value="">اختر الفئة الفرعية (اختياري)</option>
                                    @foreach($subcategories as $subcat)
                                        <option value="{{ $subcat }}" {{ old('subcategory', $labTest->subcategory) == $subcat ? 'selected' : '' }}>
                                            {{ $subcat }}
                                        </option>
                                    @endforeach
                                    <option value="__other__" {{ old('subcategory', $labTest->subcategory) && !in_array(old('subcategory', $labTest->subcategory), $subcategories) ? 'selected' : '' }}>أخرى (إدخال يدوي)</option>
                                </select>
                                <input type="text" class="form-control mt-2" id="subcategory_custom" 
                                       value="{{ old('subcategory', $labTest->subcategory) && !in_array(old('subcategory', $labTest->subcategory), $subcategories) ? old('subcategory', $labTest->subcategory) : '' }}" 
                                       placeholder="اكتب الفئة الفرعية بالإنجليزية" 
                                       style="display: {{ old('subcategory', $labTest->subcategory) && !in_array(old('subcategory', $labTest->subcategory), $subcategories) ? 'block' : 'none' }};">
                                <input type="hidden" id="subcategory_value" name="subcategory" value="{{ old('subcategory', $labTest->subcategory) }}">
                                <div class="form-text">اختر من القائمة أو اختر "أخرى" لإدخال قيمة جديدة</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">وحدة القياس</label>
                                <input type="text" class="form-control" id="unit" name="unit" value="{{ old('unit', $labTest->unit) }}" placeholder="مثال: mg/dL أو U/L أو ml">
                                <div class="form-text">اكتب وحدة القياس المناسبة لهذا التحليل</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label fw-bold">سعر الكاش العادي (د.ع) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" value="{{ old('price', $labTest->price) }}">
                                <div class="form-text">السعر الافتراضي للمراجع بدون ضمان</div>
                            </div>
                        </div>

                        <!-- قسم نمط إدخال نتيجة الفحص في المختبر -->
                        <div class="card mb-4 border-info border-opacity-25 bg-light bg-opacity-25">
                            <div class="card-header bg-info bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 text-dark fw-bold">
                                    <i class="fas fa-sliders-h text-info me-1"></i> نمط إدخال نتيجة الفحص في المختبر
                                </h6>
                                @if($labTest->subTests()->count() > 0)
                                    <span class="badge" style="background-color: #6f42c1; color: white;">
                                        <i class="fas fa-list-ol me-1"></i> يحتوي {{ $labTest->subTests()->count() }} قيمة فرعية
                                    </span>
                                @endif
                            </div>
                            <div class="card-body">
                                @php
                                    $currentEntryType = old('result_entry_type', $labTest->result_entry_type ?: 'single_value');
                                    $subCount = $labTest->subTests()->count();
                                @endphp
                                <div class="row g-2">
                                    {{-- خيار 1: قيمة مباشرة --}}
                                    <div class="col-md-3">
                                        <label class="p-2 border rounded d-block h-100 bg-white {{ $currentEntryType === 'single_value' ? 'border-primary shadow-xs' : '' }}" style="cursor: pointer;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="result_entry_type" id="entry_type_single" value="single_value"
                                                       {{ $currentEntryType === 'single_value' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold small text-dark d-block" for="entry_type_single">
                                                    1. قيمة مباشرة
                                                </label>
                                            </div>
                                            <span class="badge bg-primary-subtle text-primary mt-1" style="font-size: 0.68rem;">Single Value</span>
                                            <div class="text-muted mt-1" style="font-size: 0.72rem;">إدخال قيمة رقمية/نصية مفردة للفحص</div>
                                        </label>
                                    </div>

                                    {{-- خيار 2: قيم فرعية مفصلة --}}
                                    <div class="col-md-3">
                                        <label class="p-2 border rounded d-block h-100 bg-white {{ $currentEntryType === 'sub_tests' ? 'border-primary shadow-xs' : '' }}" style="cursor: pointer;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="result_entry_type" id="entry_type_subtests" value="sub_tests"
                                                       {{ $currentEntryType === 'sub_tests' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold small text-dark d-block" for="entry_type_subtests">
                                                    2. قيم فرعية
                                                </label>
                                            </div>
                                            <span class="badge" style="background-color: rgba(111, 66, 193, 0.15); color: #6f42c1; font-size: 0.68rem;">Sub-Tests</span>
                                            <div class="text-muted mt-1" style="font-size: 0.72rem;">جدول باراميترات فرعية لكل فحص</div>
                                            @if($subCount === 0)
                                                <div class="alert alert-warning py-1 px-1.5 mt-1.5 mb-0" style="font-size: 0.68rem;">
                                                    <i class="fas fa-exclamation-triangle me-1"></i> لا توجد قيم فرعية مسجلة حالياً
                                                </div>
                                            @endif
                                        </label>
                                    </div>

                                    {{-- خيار 3: مرفق جهاز --}}
                                    <div class="col-md-3">
                                        <label class="p-2 border rounded d-block h-100 bg-white {{ $currentEntryType === 'attachment_only' ? 'border-warning shadow-xs' : '' }}" style="cursor: pointer;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="result_entry_type" id="entry_type_attachment" value="attachment_only"
                                                       {{ $currentEntryType === 'attachment_only' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold small text-dark d-block" for="entry_type_attachment">
                                                    3. مرفق جهاز
                                                </label>
                                            </div>
                                            <span class="badge bg-warning-subtle text-dark mt-1" style="font-size: 0.68rem;">Attachment</span>
                                            <div class="text-muted mt-1" style="font-size: 0.72rem;">رفع تقرير PDF أو صورة صادرة من الجهاز</div>
                                        </label>
                                    </div>

                                    {{-- خيار 4: كلاهما (مرن) --}}
                                    <div class="col-md-3">
                                        <label class="p-2 border rounded d-block h-100 bg-white {{ $currentEntryType === 'flexible' ? 'border-success shadow-xs' : '' }}" style="cursor: pointer;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="result_entry_type" id="entry_type_flexible" value="flexible"
                                                       {{ $currentEntryType === 'flexible' ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold small text-dark d-block" for="entry_type_flexible">
                                                    4. كلاهما (مرن)
                                                </label>
                                            </div>
                                            <span class="badge bg-success-subtle text-success mt-1" style="font-size: 0.68rem;">Flexible</span>
                                            <div class="text-muted mt-1" style="font-size: 0.72rem;">إدخال نتائج وإمكانية إرفاق تقرير إضافي</div>
                                        </label>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2.5 pt-2 border-top">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i> يحدد هذا الخيار كيفية ظهور الفحص لموظف المختبر عند إدخال نتائج المرضى.
                                    </small>
                                    <a href="{{ route('lab-tests.sub-tests.index', $labTest) }}" class="btn btn-xs btn-outline-primary fw-bold py-1 px-2.5" style="font-size: 0.75rem;">
                                        <i class="fas fa-external-link-alt me-1"></i> إدارة القيم الفرعية ({{ $subCount }})
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- قسم تسعير الضمان الصحي وضمان وزارة الداخلية -->
                        <div class="card mb-4 border-primary border-opacity-25 bg-light bg-opacity-25">
                            <div class="card-header bg-primary bg-opacity-10 py-2">
                                <h6 class="mb-0 text-primary fw-bold">
                                    <i class="fas fa-shield-alt me-1"></i> تسعير وتغطية الضمان الصحي وضمان الداخلية
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    {{-- ضمان وزارة الداخلية --}}
                                    <div class="col-md-6 border-start">
                                        <div class="p-2 bg-white rounded border">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label fw-bold text-dark mb-0">
                                                    <i class="fas fa-id-badge text-primary me-1"></i> ضمان وزارة الداخلية (MOI)
                                                </label>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" id="is_moi_active" name="is_moi_active" value="1"
                                                           {{ old('is_moi_active', $labTest->is_moi_active ?? true) ? 'checked' : '' }}>
                                                    <label class="form-check-label small fw-bold" for="is_moi_active">مشمول بالضمان</label>
                                                </div>
                                            </div>
                                            <label for="moi_price" class="form-label small text-muted">السعر الرسمي للداخلية (د.ع)</label>
                                            <input type="number" step="0.01" class="form-control" id="moi_price" name="moi_price"
                                                   value="{{ old('moi_price', $labTest->moi_price) }}" placeholder="اتركه فارغاً لاعتماد سعر الكاش">
                                            <div class="form-text small">إذا كان فارغاً، يتم اعتماد سعر الكاش الأساسي تلقائياً</div>
                                        </div>
                                    </div>

                                    {{-- هيئة الضمان الصحي --}}
                                    <div class="col-md-6">
                                        <div class="p-2 bg-white rounded border">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label fw-bold text-dark mb-0">
                                                    <i class="fas fa-heartbeat text-success me-1"></i> هيئة الضمان الصحي (HI)
                                                </label>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" id="is_hi_active" name="is_hi_active" value="1"
                                                           {{ old('is_hi_active', $labTest->is_hi_active ?? true) ? 'checked' : '' }}>
                                                    <label class="form-check-label small fw-bold" for="is_hi_active">مشمول بالضمان</label>
                                                </div>
                                            </div>
                                            <label for="hi_price" class="form-label small text-muted">السعر الرسمي لهيئة الضمان (د.ع)</label>
                                            <input type="number" step="0.01" class="form-control" id="hi_price" name="hi_price"
                                                   value="{{ old('hi_price', $labTest->hi_price) }}" placeholder="اتركه فارغاً لاعتماد سعر الكاش">
                                            <div class="form-text small">إذا كان فارغاً، يتم اعتماد سعر الكاش الأساسي تلقائياً</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">الوصف</label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="3" placeholder="وصف تفصيلي للفحص المختبري">{{ old('description', $labTest->description) }}</textarea>
                            <div class="form-text">وصف الفحص والمعلومات المهمة عنه</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $labTest->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    الفحص نشط
                                </label>
                            </div>
                            <div class="form-text">إذا تم إلغاء التفعيل، لن يظهر الفحص في قوائم الاختيار</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>
                                حفظ التغييرات
                            </button>
                            <a href="{{ route('lab-tests.show', $labTest) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSubcategoryInput() {
    const select = document.getElementById('subcategory_select');
    const customInput = document.getElementById('subcategory_custom');
    const hiddenInput = document.getElementById('subcategory_value');
    
    if (select.value === '__other__') {
        customInput.style.display = 'block';
        customInput.name = 'subcategory';
        hiddenInput.name = '';
        customInput.focus();
    } else {
        customInput.style.display = 'none';
        customInput.name = '';
        hiddenInput.name = 'subcategory';
        hiddenInput.value = select.value;
    }
}

// تعيين القيمة عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    toggleSubcategoryInput();
});
</script>
@endsection