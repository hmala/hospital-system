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

    @php
        $moduleDefs = \App\Http\Controllers\RoleManagementController::getModuleOrder();
        $permDefs = \App\Http\Controllers\RoleManagementController::getPermissionDefinitions();
    @endphp

    @foreach($permissions as $module => $perms)
    @php
        $moduleInfo = $moduleDefs[$module] ?? [
            'name' => match($module) {
                'patients' => 'المرضى',
                'doctors' => 'الأطباء',
                'departments' => 'العيادات',
                'appointments' => 'المواعيد',
                'visits' => 'الزيارات',
                'surgeries' => 'العمليات',
                'radiology' => 'الأشعة',
                'tests' => 'التحاليل',
                'inquiries' => 'الاستعلامات',
                'cashier' => 'الكاشير',
                'types' => 'أنواع التحاليل',
                'rooms' => 'الغرف',
                'inventory' => 'المخزون',
                'accounting' => 'الحسابات العامة والمالية',
                'consultant_doctors' => 'العيادات والاستشارية',
                'system_admin' => 'إدارة النظام',
                'whatsapp' => 'خدمات الواتساب',
                default => $module,
            },
            'icon' => 'fa-folder-open',
            'color' => 'primary',
        ];
    @endphp
    <div class="card mb-4 shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-header bg-{{ $moduleInfo['color'] ?? 'primary' }} text-white py-3">
            <h5 class="mb-0 d-flex align-items-center justify-content-between">
                <span>
                    <i class="fas {{ $moduleInfo['icon'] ?? 'fa-folder-open' }} me-2"></i> 
                    {{ $moduleInfo['name'] }}
                </span>
                <span class="badge bg-light text-dark fs-6">{{ $perms->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 55%">اسم الصلاحية والوظيفة</th>
                            <th style="width: 25%">الأدوار المرتبطة</th>
                            <th style="width: 20%" class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perms as $permission)
                        @php
                            $def = $permDefs[$permission->name] ?? null;
                            $label = $def['label'] ?? $permission->name;
                            $action = $def['action'] ?? 'إجراء';
                            $badge = $def['badge'] ?? 'secondary';
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-shield-alt text-{{ $badge }} me-2 fs-5"></i>
                                    <div>
                                        <span class="fw-bold text-dark d-block">{{ $label }}</span>
                                        <code class="text-muted small" style="font-size: 0.8rem;">{{ $permission->name }}</code>
                                    </div>
                                    <span class="badge bg-{{ $badge }} bg-opacity-10 text-{{ $badge }} border border-{{ $badge }} border-opacity-25 ms-auto me-3 px-2 py-1">
                                        {{ $action }}
                                    </span>
                                </div>
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
