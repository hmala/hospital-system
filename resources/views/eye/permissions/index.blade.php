@extends('layouts.app')

@section('title', 'صلاحيات مركز وجراحة العيون')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 text-gray-800 mb-0"><i class="fas fa-user-shield text-primary me-2"></i> صلاحيات ومستخدمي العيون</h2>
            <p class="text-muted mb-0 mt-1">إدارة صلاحيات الوصول الخاصة بمركز العيون (الاستعلامات، المخزن، العمليات، الخ) للموظفين والأطباء</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 py-3">
            <form action="{{ route('eye.permissions.index') }}" method="GET" class="d-flex gap-2 w-50">
                <input type="text" name="search" class="form-control" placeholder="ابحث عن مستخدم بالاسم أو الإيميل..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
                @if(request()->has('search'))
                    <a href="{{ route('eye.permissions.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">الموظف / الطبيب</th>
                            <th>الدور (Role)</th>
                            <th class="text-center">صلاحيات العيون</th>
                            <th class="text-end pe-4">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $user->name }}</div>
                                    <div class="small text-muted">{{ $user->email }}</div>
                                </td>
                                <td>
                                    @foreach($user->roles as $role)
                                        <span class="badge bg-secondary rounded-pill">{{ $role->name }}</span>
                                    @endforeach
                                    @if($user->roles->isEmpty())
                                        <span class="text-muted small">لا يوجد دور</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $userEyePerms = $user->permissions->filter(function($p) use ($eyePermissions) {
                                            return $eyePermissions->contains('name', $p->name);
                                        })->count();
                                    @endphp
                                    
                                    @if($userEyePerms > 0)
                                        <span class="badge bg-success rounded-pill px-3">{{ $userEyePerms }} / {{ $eyePermissions->count() }} صلاحيات</span>
                                    @else
                                        <span class="badge bg-light text-muted rounded-pill px-3 border">بدون صلاحيات</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#permissionsModal{{ $user->id }}">
                                        <i class="fas fa-edit"></i> تعديل الصلاحيات
                                    </button>

                                    <!-- Modal -->
                                    <div class="modal fade" id="permissionsModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content text-start">
                                                <form action="{{ route('eye.permissions.update', $user) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-header bg-light">
                                                        <h5 class="modal-title"><i class="fas fa-shield-alt text-primary me-2"></i> صلاحيات مركز العيون لـ ({{ $user->name }})</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-muted mb-4">اختر الصلاحيات المخصصة لمركز العيون التي تود منحها لهذا المستخدم. (الصلاحيات الأخرى لن تتأثر).</p>
                                                        
                                                        <div class="row g-3">
                                                            @foreach($eyePermissions as $perm)
                                                                <div class="col-md-6">
                                                                    <div class="form-check form-switch card p-3 shadow-sm border-0 bg-light h-100">
                                                                        <div>
                                                                            <input class="form-check-input ms-0 me-3 mt-1" style="transform: scale(1.3)" type="checkbox" name="permissions[]" value="{{ $perm->name }}" id="perm_{{ $user->id }}_{{ $perm->id }}" {{ $user->hasPermissionTo($perm->name) ? 'checked' : '' }}>
                                                                            <label class="form-check-label fw-bold d-block w-100 cursor-pointer" for="perm_{{ $user->id }}_{{ $perm->id }}">
                                                                                {{ $perm->name }}
                                                                            </label>
                                                                            <small class="text-muted d-block mt-1">
                                                                                @if($perm->name == 'view eye center') الوصول لقسم العيون
                                                                                @elseif($perm->name == 'manage eye appointments') استعلامات العيون والطابور
                                                                                @elseif($perm->name == 'manage eye cashier') كاشير وفواتير العيون
                                                                                @elseif($perm->name == 'manage eye store') مخزن وعدسات العيون
                                                                                @elseif($perm->name == 'manage eye investigations') فحوصات الأجهزة التشخيصية
                                                                                @elseif($perm->name == 'perform eye surgeries') صالة عمليات وزرع عدسات العيون
                                                                                @elseif($perm->name == 'conduct eye examinations') الفحص السريري للعيون OD/OS
                                                                                @endif
                                                                            </small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 bg-light">
                                                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">إلغاء</button>
                                                        <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="fas fa-save me-1"></i> حفظ الصلاحيات</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">لا يوجد مستخدمين لعرضهم.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="px-4 py-3 border-top">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
