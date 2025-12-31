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

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>الاسم</th>
                            <th>البريد الإلكتروني</th>
                            <th>الصلاحيات</th>
                            <th>تاريخ الإنشاء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>
                                <i class="fas fa-user text-primary"></i>
                                {{ $user->name }}
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @foreach($user->roles as $role)
                                <span class="badge bg-{{ 
                                    $role->name == 'admin' ? 'danger' : 
                                    ($role->name == 'doctor' ? 'success' : 
                                    ($role->name == 'patient' ? 'info' : 
                                    ($role->name == 'receptionist' ? 'warning' : 'secondary'))) 
                                }}">
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
                                </span>
                                @endforeach
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
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>لا توجد مستخدمين</p>
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
