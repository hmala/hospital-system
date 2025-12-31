@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> رجوع
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-plus-circle"></i> إضافة دور جديد</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">الاسم الإنجليزي <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       placeholder="مثال: custom_role"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">استخدم أحرف إنجليزية صغيرة وشرطة سفلية فقط</small>
                            </div>

                            <div class="col-md-6">
                                <label for="display_name" class="form-label">الاسم المعروض <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('display_name') is-invalid @enderror" 
                                       id="display_name" 
                                       name="display_name" 
                                       value="{{ old('display_name') }}" 
                                       placeholder="مثال: مدير مخصص"
                                       required>
                                @error('display_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">الصلاحيات</label>
                            @error('permissions')
                                <div class="text-danger small mb-2">{{ $message }}</div>
                            @enderror
                            
                            @foreach($permissions as $module => $perms)
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <div class="form-check">
                                        <input class="form-check-input module-checkbox" 
                                               type="checkbox" 
                                               id="module_{{ $loop->index }}"
                                               data-module="{{ $loop->index }}">
                                        <label class="form-check-label fw-bold" for="module_{{ $loop->index }}">
                                            <i class="fas fa-folder"></i> 
                                            @switch($module)
                                                @case('patients') المرضى @break
                                                @case('doctors') الأطباء @break
                                                @case('departments') العيادات @break
                                                @case('appointments') المواعيد @break
                                                @case('visits') الزيارات @break
                                                @case('surgeries') العمليات @break
                                                @case('radiology') الأشعة @break
                                                @case('tests') التحاليل @break
                                                @case('inquiries') الاستعلامات @break
                                                @default {{ $module }}
                                            @endswitch
                                            (تحديد الكل)
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($perms as $permission)
                                        <div class="col-md-6 col-lg-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input permission-checkbox" 
                                                       type="checkbox" 
                                                       name="permissions[]" 
                                                       value="{{ $permission->name }}" 
                                                       id="perm_{{ $permission->id }}"
                                                       data-module="{{ $loop->parent->index }}"
                                                       {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                    {{ $permission->name }}
                                                </label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-check {
        padding: 0.5rem;
        border-radius: 5px;
        transition: background-color 0.2s;
    }
    .form-check:hover {
        background-color: #f8f9fa;
    }
    .form-check-input:checked ~ .form-check-label {
        font-weight: 600;
        color: #0d6efd;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحديد/إلغاء تحديد جميع الصلاحيات في القسم
    document.querySelectorAll('.module-checkbox').forEach(moduleCheckbox => {
        moduleCheckbox.addEventListener('change', function() {
            const moduleIndex = this.dataset.module;
            const checked = this.checked;
            document.querySelectorAll(`.permission-checkbox[data-module="${moduleIndex}"]`).forEach(permCheckbox => {
                permCheckbox.checked = checked;
            });
        });
    });

    // تحديث checkbox القسم عند تغيير أي صلاحية
    document.querySelectorAll('.permission-checkbox').forEach(permCheckbox => {
        permCheckbox.addEventListener('change', function() {
            const moduleIndex = this.dataset.module;
            const moduleCheckbox = document.querySelector(`.module-checkbox[data-module="${moduleIndex}"]`);
            const allPerms = document.querySelectorAll(`.permission-checkbox[data-module="${moduleIndex}"]`);
            const checkedPerms = document.querySelectorAll(`.permission-checkbox[data-module="${moduleIndex}"]:checked`);
            
            moduleCheckbox.checked = allPerms.length === checkedPerms.length;
            moduleCheckbox.indeterminate = checkedPerms.length > 0 && checkedPerms.length < allPerms.length;
        });
    });
});
</script>
@endsection
