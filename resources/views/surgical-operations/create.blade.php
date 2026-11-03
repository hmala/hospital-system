@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-plus-circle me-2 text-success"></i>
                        إضافة عملية جراحية جديدة
                    </h2>
                    <p class="text-muted">أدخل بيانات العملية الجراحية الجديدة</p>
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
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus me-2"></i>
                        بيانات العملية الجراحية
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('surgical-operations.store') }}" method="POST">
                        @csrf

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
                                       value="{{ old('name') }}"
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
                                        <option value="{{ $cat }}" {{ old('category') == $cat ? 'selected' : '' }}>
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

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('surgical-operations.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>
                                حفظ العملية
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