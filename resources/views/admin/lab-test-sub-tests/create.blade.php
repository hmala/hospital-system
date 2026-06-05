@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus me-2"></i>
                        إضافة فحص فرعي لـ: {{ $labTest->name }}
                    </h5>
                </div>

                <div class="card-body">
                    <form action="{{ route('admin.lab-test-sub-tests.store', $labTest->id) }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label for="name" class="form-label required">اسم الفحص الفرعي</label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}"
                                       placeholder="مثال: pH, WBC, Color"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="sort_order" class="form-label">ترتيب الظهور</label>
                                <input type="number" 
                                       class="form-control @error('sort_order') is-invalid @enderror" 
                                       id="sort_order" 
                                       name="sort_order" 
                                       value="{{ old('sort_order', 0) }}">
                                @error('sort_order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">الأرقام الأصغر تظهر أولاً</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="unit" class="form-label">الوحدة</label>
                                <input type="text" 
                                       class="form-control @error('unit') is-invalid @enderror" 
                                       id="unit" 
                                       name="unit" 
                                       value="{{ old('unit') }}"
                                       placeholder="مثال: mg/dL, %, mmHg">
                                @error('unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="result_type" class="form-label required">نوع النتيجة</label>
                                <select class="form-select @error('result_type') is-invalid @enderror" 
                                        id="result_type" 
                                        name="result_type" 
                                        required>
                                    <option value="">-- اختر النوع --</option>
                                    <option value="numeric" {{ old('result_type') === 'numeric' ? 'selected' : '' }}>رقمي</option>
                                    <option value="text" {{ old('result_type') === 'text' ? 'selected' : '' }}>نصي</option>
                                </select>
                                @error('result_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">رقمي للقيم العددية، نصي للنصوص (مثل: Nil, Positive)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reference_range" class="form-label">القيمة المرجعية</label>
                            <input type="text" 
                                   class="form-control @error('reference_range') is-invalid @enderror" 
                                   id="reference_range" 
                                   name="reference_range" 
                                   value="{{ old('reference_range') }}"
                                   placeholder="مثال: 7.35-7.45, >15, Nil">
                            @error('reference_range')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">النطاق الطبيعي للقيمة</small>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">ملاحظات</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="2"
                                      placeholder="ملاحظات إضافية (مثال: Physical examination, Microscopic examination)">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.lab-test-sub-tests.index', $labTest->id) }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                حفظ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
