@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users"></i> إدارة المستخدمين</h2>
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> إضافة مستخدم جديد
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

    <!-- فلترة وبحث المستخدمين -->
    <div class="card shadow-sm mb-4 animate__animated animate__fadeIn">
        <div class="card-header bg-transparent border-0 pb-0">
            <h5 class="card-title text-primary mb-0">
                <i class="fas fa-search me-1"></i> فلترة وبحث المستخدمين
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('users.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="search" class="form-label small text-muted">البحث العام (الاسم، البريد، الهاتف، الاختصاص)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" id="search" class="form-control border-start-0 bg-light" 
                               placeholder="اكتب الاسم، البريد، الهاتف..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <label for="role" class="form-label small text-muted">الصلاحية / الدور</label>
                    <select name="role" id="role" class="form-select bg-light">
                        <option value="">كل الصلاحيات</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                @switch($role->name)
                                    @case('admin') مدير النظام @break
                                    @case('doctor') طبيب @break
                                    @case('patient') مريض @break
                                    @case('receptionist') موظف استقبال @break
                                    @case('lab_staff') موظف مختبر @break
                                    @case('radiology_staff') موظف أشعة @break
                                    @case('pharmacy_staff') موظف صيدلية @break
                                    @case('inventory_manager') موظف مخزن @break
                                    @case('consultation_receptionist') موظف استعلامات استشارية @break
                                    @case('surgery_staff') موظف عمليات @break
                                    @default {{ $role->name }}
                                @endswitch
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="location_id" class="form-label small text-muted">الموقع / القسم</label>
                    <select name="location_id" id="location_id" class="form-select bg-light">
                        <option value="">كل المواقع</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }} ({{ $location->type }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="status" class="form-label small text-muted">الحالة</label>
                    <select name="status" id="status" class="form-select bg-light">
                        <option value="">كل الحالات</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>معطل</option>
                    </select>
                </div>

                <div class="col-md-1 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100" title="بحث">
                        <i class="fas fa-filter"></i>
                    </button>
                    @if(request()->anyFilled(['search', 'role', 'location_id', 'status']))
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary" title="إعادة تعيين">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>الاسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>الموقع / القسم</th>
                            <th>الصلاحيات</th>
                            <th>الحالة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>
                                <i class="fas fa-user text-primary me-1"></i>
                                {{ $user->name }}
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->location)
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        {{ $user->location->name }}
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                @foreach($user->roles as $role)
                                <span class="badge bg-{{ 
                                    $role->name == 'admin' ? 'danger' : 
                                    ($role->name == 'doctor' ? 'success' : 
                                    ($role->name == 'patient' ? 'info' : 
                                    ($role->name == 'receptionist' ? 'warning' : 
                                    ($role->name == 'consultation_receptionist' ? 'info' : 'secondary')))) 
                                }}">
                                    @switch($role->name)
                                        @case('admin') مدير النظام @break
                                        @case('doctor') طبيب @break
                                        @case('patient') مريض @break
                                        @case('receptionist') موظف استقبال @break
                                        @case('lab_staff') موظف مختبر @break
                                        @case('radiology_staff') موظف أشعة @break
                                        @case('pharmacy_staff') موظف صيدلية @break
                                        @case('inventory_manager') موظف مخزن @break
                                        @case('consultation_receptionist') موظف استعلامات استشارية @break
                                        @case('surgery_staff') موظف عمليات @break
                                        @default {{ $role->name }}
                                    @endswitch
                                </span>
                                @endforeach
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="fas fa-check-circle me-1"></i> نشط
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        <i class="fas fa-times-circle me-1"></i> معطل
                                    </span>
                                @endif
                            </td>
                            <td>{{ $user->created_at->format('Y-m-d') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('users.edit', $user) }}" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    @if($user->id !== auth()->id())
                                    <form action="{{ route('users.toggle-status', $user) }}" 
                                          method="POST" 
                                          class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" 
                                                class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }}" 
                                                title="{{ $user->is_active ? 'تعطيل الحساب' : 'تفعيل الحساب' }}">
                                            <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }}"></i>
                                        </button>
                                    </form>

                                    <form action="{{ route('users.destroy', $user) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-outline-danger" 
                                                title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>لا يوجد مستخدمين</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>

<style>
    .table th {
        font-weight: 600;
        color: #2c3e50;
    }
    .badge {
        padding: 0.4em 0.8em;
        font-size: 0.85em;
    }
    .btn-group .btn {
        margin: 0 2px;
    }
</style>
@endsection
