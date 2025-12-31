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
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-edit"></i> تعديل صلاحيات: 
                        @switch($role->name)
                            @case('admin') مدير النظام @break
                            @case('doctor') طبيب @break
                            @case('patient') مريض @break
                            @case('receptionist') موظف استقبال @break
                            @case('lab_staff') موظف مختبر @break
                            @case('radiology_staff') موظف أشعة @break
                            @case('pharmacy_staff') موظف صيدلية @break
                            @case('surgery_staff') موظف عمليات @break
                            @default {{ $role->name }}
                        @endswitch
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">اسم الدور</label>
                            <input type="text" class="form-control" value="{{ $role->name }}" disabled>
                            <small class="text-muted">لا يمكن تعديل اسم الدور</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">الصلاحيات</label>
                            @error('permissions')
                                <div class="text-danger small mb-2">{{ $message }}</div>
                            @enderror
                            
                            @foreach($permissions as $module => $perms)
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
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
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($perms as $permission)
                                        <div class="col-md-6 col-lg-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="permissions[]" 
                                                       value="{{ $permission->name }}" 
                                                       id="perm_{{ $permission->id }}"
                                                       {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
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
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> حفظ التعديلات
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
@endsection
