@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-key"></i> إدارة الصلاحيات</h2>
        <a href="{{ route('permissions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> إضافة صلاحية جديدة
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @foreach($permissions as $module => $perms)
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-folder-open"></i> 
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
                <span class="badge bg-light text-dark ms-2">{{ $perms->count() }}</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50%">اسم الصلاحية</th>
                            <th style="width: 30%">الأدوار المرتبطة</th>
                            <th style="width: 20%">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perms as $permission)
                        <tr>
                            <td>
                                <i class="fas fa-shield-alt text-primary"></i>
                                <code>{{ $permission->name }}</code>
                            </td>
                            <td>
                                @if($permission->roles_count > 0)
                                    <span class="badge bg-success">{{ $permission->roles_count }} دور</span>
                                @else
                                    <span class="text-muted">غير مستخدمة</span>
                                @endif
                            </td>
                            <td>
                                @if($permission->roles_count == 0)
                                <form action="{{ route('permissions.destroy', $permission) }}" 
                                      method="POST" 
                                      class="d-inline"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذه الصلاحية؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="btn btn-sm btn-outline-danger" 
                                            title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @else
                                <button class="btn btn-sm btn-outline-secondary" disabled title="لا يمكن الحذف - مرتبطة بأدوار">
                                    <i class="fas fa-lock"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach
</div>

<style>
    code {
        background-color: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
        color: #d63384;
    }
</style>
@endsection
