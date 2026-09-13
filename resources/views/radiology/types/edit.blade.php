@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">تعديل نوع الإشعة: {{ $type->name }}</div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('radiology.types.update', $type) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">اسم نوع الإشعة</label>
                                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $type->name) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="code" class="form-label">الكود <small class="text-muted">(حتى 50 حرفاً)</small></label>
                                <input type="text" id="code" name="code" class="form-control" value="{{ old('code', $type->code) }}" placeholder="أدخل كوداً أو اتركه فارغاً للحفاظ على الحالي">
                            </div>

                            <div class="col-md-6">
                                <label for="subcategory" class="form-label">قسم الإشعة <span class="text-danger">*</span></label>
                                <select id="subcategory" name="subcategory" class="form-select" required>
                                    <option value="">اختر قسم الإشعة</option>
                                    <option value="أشعة" {{ old('subcategory', $type->subcategory) === 'أشعة' ? 'selected' : '' }}>أشعة عامة</option>
                                    <option value="سونار" {{ old('subcategory', $type->subcategory) === 'سونار' ? 'selected' : '' }}>سونار</option>
                                    <option value="الرنين" {{ old('subcategory', $type->subcategory) === 'الرنين' ? 'selected' : '' }}>رنين مغناطيسي</option>
                                    <option value="إيكو" {{ old('subcategory', $type->subcategory) === 'إيكو' ? 'selected' : '' }}>إيكو</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">الوصف</label>
                                <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $type->description) }}</textarea>
                            </div>

                            <div class="col-md-4">
                                <label for="base_price" class="form-label fw-bold">سعر الكاش العادي (د.ع) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" id="base_price" name="base_price" class="form-control" value="{{ old('base_price', $type->base_price) }}" required>
                                <div class="form-text small">سعر المراجع بدون ضمان</div>
                            </div>

                            <div class="col-md-4">
                                <label for="estimated_duration" class="form-label">المدة التقديرية (بالدقائق)</label>
                                <input type="number" min="1" max="480" id="estimated_duration" name="estimated_duration" class="form-control" value="{{ old('estimated_duration', $type->estimated_duration) }}" required>
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check me-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $type->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">الفحص نشط</label>
                                </div>
                            </div>

                            <!-- قسم تسعير الضمان الصحي وضمان وزارة الداخلية -->
                            <div class="col-12">
                                <div class="card border-primary border-opacity-25 bg-light bg-opacity-25">
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
                                                                   {{ old('is_moi_active', $type->is_moi_active ?? true) ? 'checked' : '' }}>
                                                            <label class="form-check-label small fw-bold" for="is_moi_active">مشمول بالضمان</label>
                                                        </div>
                                                    </div>
                                                    <label for="moi_price" class="form-label small text-muted">السعر الرسمي للداخلية (د.ع)</label>
                                                    <input type="number" step="0.01" class="form-control" id="moi_price" name="moi_price"
                                                           value="{{ old('moi_price', $type->moi_price) }}" placeholder="اتركه فارغاً لاعتماد سعر الكاش">
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
                                                                   {{ old('is_hi_active', $type->is_hi_active ?? true) ? 'checked' : '' }}>
                                                            <label class="form-check-label small fw-bold" for="is_hi_active">مشمول بالضمان</label>
                                                        </div>
                                                    </div>
                                                    <label for="hi_price" class="form-label small text-muted">السعر الرسمي لهيئة الضمان (د.ع)</label>
                                                    <input type="number" step="0.01" class="form-control" id="hi_price" name="hi_price"
                                                           value="{{ old('hi_price', $type->hi_price) }}" placeholder="اتركه فارغاً لاعتماد سعر الكاش">
                                                    <div class="form-text small">إذا كان فارغاً، يتم اعتماد سعر الكاش الأساسي تلقائياً</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="requires_contrast" name="requires_contrast" value="1" {{ old('requires_contrast', $type->requires_contrast) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_contrast">يتطلب صبغة (Contrast)</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="requires_preparation" name="requires_preparation" value="1" {{ old('requires_preparation', $type->requires_preparation) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_preparation">يتطلب تحضير مسبق</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="preparation_instructions" class="form-label">تعليمات التحضير</label>
                                <textarea id="preparation_instructions" name="preparation_instructions" class="form-control" rows="3" placeholder="مثال: صيام 6 ساعات، إحضار نتائج سابقة ...">{{ old('preparation_instructions', $type->preparation_instructions) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                            <a href="{{ route('radiology.types.show', $type) }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
