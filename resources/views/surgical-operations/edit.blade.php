@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-edit me-2 text-primary"></i>
                        تعديل العملية الجراحية
                    </h2>
                    <p class="text-muted">تعديل بيانات العملية والصنف التابعة له</p>
                </div>
                <a href="{{ route('surgical-operations.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        تعديل بيانات العملية: {{ $surgicalOperation->name }}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('surgical-operations.update', $surgicalOperation) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-tag me-1"></i>
                                    اسم العملية <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('name') is-invalid @enderror"
                                       id="name"
                                       name="name"
                                       value="{{ old('name', $surgicalOperation->name) }}"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">
                                    <i class="fas fa-folder me-1"></i>
                                    الصنف <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('category') is-invalid @enderror"
                                        id="category"
                                        name="category"
                                        required>
                                    <option value="">اختر الصنف</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}" {{ old('category', $surgicalOperation->category) == $cat ? 'selected' : '' }}>
                                            {{ $cat }}
                                        </option>
                                    @endforeach
                                    <option value="new">+ أضف صنف جديد</option>
                                </select>
                                @error('category')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="mt-2" id="newCategoryDiv" style="display: none;">
                                    <input type="text"
                                           class="form-control @error('new_category') is-invalid @enderror"
                                           id="new_category"
                                           name="new_category"
                                           value="{{ old('new_category') }}"
                                           placeholder="أدخل اسم الصنف الجديد">
                                    @error('new_category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $surgicalOperation->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="is_active">العملية مفعلة ونشطة في القوائم</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('surgical-operations.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('category').addEventListener('change', function() {
    const newCategoryDiv = document.getElementById('newCategoryDiv');
    const newCategoryInput = document.getElementById('new_category');
    
    if (this.value === 'new') {
        newCategoryDiv.style.display = 'block';
        newCategoryInput.required = true;
        newCategoryInput.focus();
    } else {
        newCategoryDiv.style.display = 'none';
        newCategoryInput.required = false;
        newCategoryInput.value = '';
    }
});

// Check on page load if "new" is selected
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category');
    if (categorySelect.value === 'new') {
        document.getElementById('newCategoryDiv').style.display = 'block';
        document.getElementById('new_category').required = true;
    }
});
</script>
@endsection
