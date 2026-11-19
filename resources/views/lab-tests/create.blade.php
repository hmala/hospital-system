@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-plus me-2"></i>
                    {{ __("إضافة فحص مختبري جديد") }}
                </h2>
                <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    {{ __("العودة للقائمة") }}
                </a>
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
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-flask me-2"></i>
                        {{ __("بيانات الفحص المختبري") }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('lab-tests.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="code" class="form-label">{{ __("الكود") }}</label>
                                <input type="text" class="form-control" id="code" name="code"
                                       value="{{ old('code') }}" maxlength="50">
                                <div class="form-text">{{ __("رمز مختصر للتعريف (اختياري)") }}</div>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label for="name" class="form-label">{{ __("اسم الفحص") }}<span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="{{ old('name') }}" required>
                                <div class="form-text">{{ __("أدخل اسم الفحص المختبري باللغة العربية") }}</div>
                            </div>

                            <input type="hidden" name="main_category" value="المختبر">
                            <div class="col-md-6 mb-3">
                                <label for="subcategory" class="form-label">{{ __("الفئة الفرعية") }}</label>
                                <select class="form-select" id="subcategory_select" onchange="toggleSubcategoryInput()">
                                    <option value="">{{ __("اختر الفئة الفرعية (اختياري)") }}</option>
                                    @foreach($subcategories as $subcat)
                                        <option value="{{ $subcat }}" {{ old('subcategory') == $subcat ? 'selected' : '' }}>
                                            {{ $subcat }}
                                        </option>
                                    @endforeach
                                    <option value="__other__" {{ old('subcategory') && !in_array(old('subcategory'), $subcategories) ? 'selected' : '' }}>{{ __("أخرى (إدخال يدوي)") }}</option>
                                </select>
                                <input type="text" class="form-control mt-2" id="subcategory_custom" 
                                       value="{{ old('subcategory') && !in_array(old('subcategory'), $subcategories) ? old('subcategory') : '' }}" 
                                       placeholder="{{ __("اكتب الفئة الفرعية بالإنجليزية") }}" 
                                       style="display: {{ old('subcategory') && !in_array(old('subcategory'), $subcategories) ? 'block' : 'none' }};">
                                <input type="hidden" id="subcategory_value" name="subcategory" value="{{ old('subcategory') }}">
                                <div class="form-text">{{ __("اختر من القائمة أو اختر \"أخرى\" لإدخال قيمة جديدة") }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="unit" class="form-label">{{ __("وحدة القياس") }}</label>
                                <input type="text" class="form-control" id="unit" name="unit" value="{{ old('unit') }}" placeholder="{{ __("مثال: mg/dL أو U/L أو ml") }}">
                                <div class="form-text">{{ __("اكتب وحدة القياس المناسبة لهذا التحليل") }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label fw-bold">{{ __("سعر الكاش العادي (د.ع)") }}<span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" value="{{ old('price') }}">
                                <div class="form-text">{{ __("السعر الافتراضي للمراجع بدون ضمان") }}</div>
                            </div>
                        </div>

                        <!-- قسم تسعير الضمان الصحي وضمان وزارة الداخلية -->
                        <div class="card mb-4 border-primary border-opacity-25 bg-light bg-opacity-25">
                            <div class="card-header bg-primary bg-opacity-10 py-2">
                                <h6 class="mb-0 text-primary fw-bold">
                                    <i class="fas fa-shield-alt me-1"></i> {{ __("تسعير وتغطية الضمان الصحي وضمان الداخلية") }}
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    {{-- ضمان وزارة الداخلية --}}
                                    <div class="col-md-6 border-start">
                                        <div class="p-2 bg-white rounded border">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label fw-bold text-dark mb-0">
                                                    <i class="fas fa-id-badge text-primary me-1"></i> {{ __("ضمان وزارة الداخلية (MOI)") }}
                                                </label>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" id="is_moi_active" name="is_moi_active" value="1"
                                                           {{ old('is_moi_active', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label small fw-bold" for="is_moi_active">{{ __("مشمول بالضمان") }}</label>
                                                </div>
                                            </div>
                                            <label for="moi_price" class="form-label small text-muted">{{ __("السعر الرسمي للداخلية (د.ع)") }}</label>
                                            <input type="number" step="0.01" class="form-control" id="moi_price" name="moi_price"
                                                   value="{{ old('moi_price') }}" placeholder="{{ __("اتركه فارغاً لاعتماد سعر الكاش") }}">
                                            <div class="form-text small">{{ __("إذا كان فارغاً، يتم اعتماد سعر الكاش الأساسي تلقائياً") }}</div>
                                        </div>
                                    </div>

                                    {{-- هيئة الضمان الصحي --}}
                                    <div class="col-md-6">
                                        <div class="p-2 bg-white rounded border">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label fw-bold text-dark mb-0">
                                                    <i class="fas fa-heartbeat text-success me-1"></i> {{ __("هيئة الضمان الصحي (HI)") }}
                                                </label>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" id="is_hi_active" name="is_hi_active" value="1"
                                                           {{ old('is_hi_active', true) ? 'checked' : '' }}>
                                                    <label class="form-check-label small fw-bold" for="is_hi_active">{{ __("مشمول بالضمان") }}</label>
                                                </div>
                                            </div>
                                            <label for="hi_price" class="form-label small text-muted">{{ __("السعر الرسمي لهيئة الضمان (د.ع)") }}</label>
                                            <input type="number" step="0.01" class="form-control" id="hi_price" name="hi_price"
                                                   value="{{ old('hi_price') }}" placeholder="{{ __("اتركه فارغاً لاعتماد سعر الكاش") }}">
                                            <div class="form-text small">{{ __("إذا كان فارغاً، يتم اعتماد سعر الكاش الأساسي تلقائياً") }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __("الوصف") }}</label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="3" placeholder="{{ __("وصف تفصيلي للفحص المختبري") }}">{{ old('description') }}</textarea>
                            <div class="form-text">{{ __("وصف الفحص والمعلومات المهمة عنه") }}</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    {{ __("الفحص نشط") }}
                                </label>
                            </div>
                            <div class="form-text">{{ __("إذا تم إلغاء التفعيل، لن يظهر الفحص في قوائم الاختيار") }}</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>
                                {{ __("حفظ الفحص") }}
                            </button>
                            <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                {{ __("إلغاء") }}
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