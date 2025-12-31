@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> رجوع
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user-plus"></i> إضافة مستخدم جديد</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">الاسم <span class="text-danger">*</span></label>
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

                        <div class="mb-3">
                            <label for="email" class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">كلمة المرور <span class="text-danger">*</span></label>
                                <input type="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       id="password" 
                                       name="password" 
                                       required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">8 أحرف على الأقل</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirmation" class="form-label">تأكيد كلمة المرور <span class="text-danger">*</span></label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">الصلاحيات <span class="text-danger">*</span></label>
                            @error('roles')
                                <div class="text-danger small mb-2">{{ $message }}</div>
                            @enderror
                            <div class="row">
                                @foreach($roles as $role)
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="roles[]" 
                                               value="{{ $role->name }}" 
                                               id="role_{{ $role->id }}"
                                               {{ in_array($role->name, old('roles', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="role_{{ $role->id }}">
                                            @switch($role->name)
                                                @case('admin') 
                                                    <span class="badge bg-danger">مدير النظام</span>
                                                @break
                                                @case('doctor') 
                                                    <span class="badge bg-success">طبيب</span>
                                                @break
                                                @case('patient') 
                                                    <span class="badge bg-info">مريض</span>
                                                @break
                                                @case('receptionist') 
                                                    <span class="badge bg-warning">موظف استقبال</span>
                                                @break
                                                @case('lab_staff') 
                                                    <span class="badge bg-secondary">موظف مختبر</span>
                                                @break
                                                @case('radiology_staff') 
                                                    <span class="badge bg-dark">موظف أشعة</span>
                                                @break
                                                @case('pharmacy_staff') 
                                                    <span class="badge bg-primary">موظف صيدلية</span>
                                                @break
                                                @case('surgery_staff') 
                                                    <span class="badge bg-danger">موظف عمليات</span>
                                                @break
                                                @default 
                                                    <span class="badge bg-secondary">{{ $role->name }}</span>
                                            @endswitch
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
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
    }
</style>
@endsection
