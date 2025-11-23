@extends('layouts.app')

@section('title', 'تعديل رمز ICD10')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        تعديل رمز ICD10: {{ $icd10->code }}
                    </h4>
                </div>

                <div class="card-body">
                    <form action="{{ route('icd10.update', $icd10) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">
                                    الرمز <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('code') is-invalid @enderror"
                                       id="code"
                                       name="code"
                                       value="{{ old('code', $icd10->code) }}"
                                       placeholder="مثال: J00"
                                       required>
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">الفئة</label>
                                <select class="form-select @error('category') is-invalid @enderror"
                                        id="category"
                                        name="category">
                                    <option value="">اختر الفئة</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $icd10->category) == $cat ? 'selected' : '' }}>
                                            {{ $cat }}
                                        </option>
                                    @endforeach
                                    <option value="other">أخرى</option>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">
                                الوصف (إنجليزي) <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description"
                                      name="description"
                                      rows="3"
                                      placeholder="الوصف باللغة الإنجليزية"
                                      required>{{ old('description', $icd10->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description_ar" class="form-label">
                                الوصف (عربي)
                            </label>
                            <textarea class="form-control @error('description_ar') is-invalid @enderror"
                                      id="description_ar"
                                      name="description_ar"
                                      rows="3"
                                      placeholder="الوصف باللغة العربية">{{ old('description_ar', $icd10->description_ar) }}</textarea>
                            @error('description_ar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('icd10.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>
                                العودة
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-1"></i>
                                تحديث الرمز
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('category').addEventListener('change', function() {
    if (this.value === 'other') {
        const newCategory = prompt('أدخل اسم الفئة الجديدة:');
        if (newCategory && newCategory.trim()) {
            // إضافة خيار جديد
            const option = new Option(newCategory.trim(), newCategory.trim());
            this.add(option);
            this.value = newCategory.trim();
        } else {
            this.value = '';
        }
    }
});
</script>
@endpush