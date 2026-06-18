@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="mb-1"><i class="fas fa-user-shield"></i> إدارة الأدوار</h2>
            <p class="text-muted mb-0">عرض سريع لكل الأدوار المسجلة مع عدد المستخدمين والصلاحيات المرتبطة.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('roles.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة دور جديد
            </a>
            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#summaryCards" aria-expanded="false" aria-controls="summaryCards">
                <i class="fas fa-chart-pie"></i> إظهار الإحصائيات
            </button>
        </div>
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

    <div class="collapse show mb-4" id="summaryCards">
        <div class="row g-3">
            <div class="col-sm-4">
                <div class="card border-primary shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-1">إجمالي الأدوار</h5>
                                <p class="text-muted mb-0">عدد الأدوار الموجودة في النظام</p>
                            </div>
                            <span class="badge bg-primary fs-5">{{ $roles->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-success shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-1">مجموع المستخدمين</h5>
                                <p class="text-muted mb-0">المستخدمون المرتبطون بكل الأدوار</p>
                            </div>
                            <span class="badge bg-success fs-5">{{ $roles->sum('users_count') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card border-info shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title mb-1">إجمالي الصلاحيات</h5>
                                <p class="text-muted mb-0">الصلاحيات المخصصة للأدوار</p>
                            </div>
                            <span class="badge bg-info fs-5">{{ $roles->sum('permissions_count') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input id="roleSearch" type="search" class="form-control" placeholder="ابحث عن دور..." aria-label="Search roles">
            </div>
        </div>
    </div>

    @if($roles->isEmpty())
        <div class="alert alert-secondary" role="alert">
            <i class="fas fa-info-circle"></i> لا توجد أدوار لعرضها حالياً.
        </div>
    @else
    <div class="row" id="rolesGrid">
        @foreach($roles as $role)
            @php
                $roleLabel = match($role->name) {
                    'admin' => ['label' => 'مدير النظام', 'icon' => 'crown', 'color' => 'danger'],
                    'doctor' => ['label' => 'طبيب', 'icon' => 'user-md', 'color' => 'success'],
                    'patient' => ['label' => 'مريض', 'icon' => 'user-injured', 'color' => 'info'],
                    'receptionist' => ['label' => 'موظف استقبال', 'icon' => 'user-tie', 'color' => 'warning'],
                    'lab_staff' => ['label' => 'موظف مختبر', 'icon' => 'flask', 'color' => 'secondary'],
                    'radiology_staff' => ['label' => 'موظف أشعة', 'icon' => 'x-ray', 'color' => 'secondary'],
                    'pharmacy_staff' => ['label' => 'موظف صيدلية', 'icon' => 'pills', 'color' => 'secondary'],
                    'surgery_staff' => ['label' => 'موظف عمليات', 'icon' => 'procedures', 'color' => 'secondary'],
                    'التخدير' => ['label' => 'مقيم تخدير', 'icon' => 'syringe', 'color' => 'warning'],
                    'الجراح' => ['label' => 'جراح', 'icon' => 'user-md', 'color' => 'info'],
                    default => ['label' => $role->name, 'icon' => 'user-shield', 'color' => 'dark'],
                };
            @endphp
            <div class="col-md-6 col-lg-4 mb-4 role-card" data-role-name="{{ mb_strtolower($roleLabel['label']) }}">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-{{ $roleLabel['color'] }} text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0"><i class="fas fa-{{ $roleLabel['icon'] }}"></i> {{ $roleLabel['label'] }}</h5>
                                <small class="text-light opacity-75">{{ $role->name }}</small>
                            </div>
                            @if(!in_array($role->name, ['admin', 'doctor', 'patient', 'receptionist', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'surgery_staff']))
                                <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light" title="حذف الدور">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fas fa-users me-2"></i> المستخدمين</span>
                                <span class="badge bg-primary rounded-pill">{{ $role->users_count }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span><i class="fas fa-key me-2"></i> الصلاحيات</span>
                                <span class="badge bg-info rounded-pill">{{ $role->permissions_count }}</span>
                            </li>
                        </ul>
                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-edit"></i> تعديل الصلاحيات
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('roleSearch');
        const cards = document.querySelectorAll('.role-card');

        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();

            cards.forEach(card => {
                const roleName = card.dataset.roleName || '';
                card.style.display = roleName.includes(query) ? '' : 'none';
            });
        });
    });
</script>
@endpush

<style>
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
        background: rgba(255, 255, 255, 0.95) !important;
        border: 1px solid rgba(148, 163, 184, 0.35) !important;
        color: #0f172a !important;
    }
    body.dark-mode .card {
        background: #070707 !important;
        border-color: rgba(255, 255, 255, 0.12) !important;
        color: #ffffff !important;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.75rem 1.5rem rgba(15, 23, 42, 0.15);
    }
    .card .card-body,
    .card .card-header {
        background: transparent !important;
        color: inherit !important;
    }
    .card .card-header small,
    .card .card-title,
    .card .card-body p,
    .card .text-muted,
    .card .list-group-item,
    .card .list-group-item span,
    .card .btn,
    .card .badge,
    .card .input-group-text,
    .card h5,
    .card p,
    .card small {
        color: inherit !important;
    }
    .card .text-muted {
        color: rgba(15, 23, 42, 0.65) !important;
    }
    body.dark-mode .card .text-muted {
        color: rgba(255, 255, 255, 0.72) !important;
    }
    .role-card .card-header h5 {
        font-size: 1rem;
    }
    .list-group-item {
        border: none;
        padding-left: 0;
        padding-right: 0;
        background: transparent !important;
    }
    #roleSearch {
        background: #ffffff;
        border: 1px solid rgba(148, 163, 184, 0.35);
        color: #0f172a;
    }
    body.dark-mode #roleSearch {
        background: rgba(15, 23, 42, 0.85);
        border-color: rgba(255, 255, 255, 0.12);
        color: #e5e7eb;
    }
    #roleSearch::placeholder {
        color: #94a3b8;
    }
    body.dark-mode #roleSearch::placeholder {
        color: #cbd5e1;
    }
    .btn-outline-primary {
        color: #2563eb;
        border-color: rgba(37, 99, 235, 0.6);
    }
    body.dark-mode .btn-outline-primary {
        color: #bfdbfe;
    }
    .btn-outline-primary:hover {
        background: rgba(59, 130, 246, 0.08);
    }
</style>
@endsection
