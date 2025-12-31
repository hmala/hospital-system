@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-shield"></i> إدارة الأدوار</h2>
        <a href="{{ route('roles.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> إضافة دور جديد
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        @foreach($roles as $role)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-{{ 
                    $role->name == 'admin' ? 'danger' : 
                    ($role->name == 'doctor' ? 'success' : 
                    ($role->name == 'patient' ? 'info' : 
                    ($role->name == 'receptionist' ? 'warning' : 'secondary'))) 
                }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            @switch($role->name)
                                @case('admin') <i class="fas fa-crown"></i> مدير النظام @break
                                @case('doctor') <i class="fas fa-user-md"></i> طبيب @break
                                @case('patient') <i class="fas fa-user-injured"></i> مريض @break
                                @case('receptionist') <i class="fas fa-user-tie"></i> موظف استقبال @break
                                @case('lab_staff') <i class="fas fa-flask"></i> موظف مختبر @break
                                @case('radiology_staff') <i class="fas fa-x-ray"></i> موظف أشعة @break
                                @case('pharmacy_staff') <i class="fas fa-pills"></i> موظف صيدلية @break
                                @case('surgery_staff') <i class="fas fa-procedures"></i> موظف عمليات @break
                                @default {{ $role->name }}
                            @endswitch
                        </h5>
                        @if(!in_array($role->name, ['admin', 'doctor', 'patient', 'receptionist', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'surgery_staff']))
                        <form action="{{ route('roles.destroy', $role) }}" 
                              method="POST" 
                              class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-light">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">
                                <i class="fas fa-users"></i> المستخدمين
                            </span>
                            <span class="badge bg-primary">{{ $role->users_count }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-muted">
                                <i class="fas fa-key"></i> الصلاحيات
                            </span>
                            <span class="badge bg-info">{{ $role->permissions_count }}</span>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-edit"></i> تعديل الصلاحيات
                        </a>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<style>
    .card {
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-5px);
    }
</style>
@endsection
