@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">

    {{-- 1. رأس الصفحة والتحكم العام --}}
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4 gap-3">
        <div>
            <h2 class="mb-1 fw-bold text-dark d-flex align-items-center gap-2">
                <span class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fas fa-user-shield fs-4"></i>
                </span>
                <span>إدارة وتوزيع الأدوار الوظيفية</span>
            </h2>
            <p class="text-muted mb-0">لوحة التحكم المركزية بالصلاحيات السريرية والإدارية والمالية لكافة كوادر المستشفى</p>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- مبدل نمط العرض (شبكة / جدول) --}}
            <div class="btn-group shadow-sm" role="group" aria-label="View Mode Switcher">
                <button type="button" class="btn btn-outline-secondary active" id="viewModeGridBtn" onclick="switchViewMode('grid')" title="عرض البطاقات التنفيذية">
                    <i class="fas fa-th-large me-1"></i> <span class="d-none d-sm-inline">بطاقات</span>
                </button>
                <button type="button" class="btn btn-outline-secondary" id="viewModeTableBtn" onclick="switchViewMode('table')" title="عرض الجدول الإداري المدمج">
                    <i class="fas fa-list me-1"></i> <span class="d-none d-sm-inline">جدول</span>
                </button>
            </div>

            <a href="{{ route('permissions.index') }}" class="btn btn-outline-primary shadow-sm">
                <i class="fas fa-key me-1"></i> دليل الصلاحيات ({{ $totalPermissionsCount ?? 145 }})
            </a>

            <a href="{{ route('roles.create') }}" class="btn btn-primary shadow-sm fw-bold">
                <i class="fas fa-plus-circle me-1"></i> إضافة دور جديد
            </a>
        </div>
    </div>

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 d-flex align-items-center gap-2" role="alert">
        <i class="fas fa-check-circle fs-5"></i>
        <div class="flex-grow-1">{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 d-flex align-items-center gap-2" role="alert">
        <i class="fas fa-exclamation-circle fs-5"></i>
        <div class="flex-grow-1">{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- 2. شريط المؤشرات التنفيذية (KPI Strip) --}}
    @php
        $totalRolesCount = $roles->count();
        $assignedUsersCount = $roles->sum('users_count');
        $activeRolesCount = $roles->where('users_count', '>', 0)->count();
        $vacantRolesCount = $roles->where('users_count', 0)->count();
        $avgPerms = $totalRolesCount > 0 ? round($roles->avg('permissions_count')) : 0;
    @endphp

    <div class="row g-3 mb-4">
        {{-- كارت 1: إجمالي الأدوار --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white kpi-card">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1">إجمالي الأدوار المسجلة</span>
                        <h3 class="mb-0 fw-bold text-primary">{{ $totalRolesCount }}</h3>
                        <small class="text-muted">موزعة على 6 قطاعات</small>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3">
                        <i class="fas fa-user-shield fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- كارت 2: المستخدمين المخصصين --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white kpi-card">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1">المستخدمين المنتسبين</span>
                        <h3 class="mb-0 fw-bold text-success">{{ number_format($assignedUsersCount) }}</h3>
                        <small class="text-success"><i class="fas fa-check-circle me-1"></i>{{ $activeRolesCount }} دوراً مأهولاً</small>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-3">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- كارت 3: إجمالي الصلاحيات --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white kpi-card">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1">الصلاحيات السريرية والتشغيلية</span>
                        <h3 class="mb-0 fw-bold text-info">{{ $totalPermissionsCount ?? 145 }}</h3>
                        <small class="text-muted">تغطي كافة أقسام المستشفى</small>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 text-info p-3">
                        <i class="fas fa-key fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- كارت 4: التوزيع الرقابي والشاغر --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white kpi-card">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1">الأدوار الشاغرة (بدون موظف)</span>
                        <h3 class="mb-0 fw-bold {{ $vacantRolesCount > 0 ? 'text-warning' : 'text-muted' }}">
                            {{ $vacantRolesCount }}
                        </h3>
                        <small class="text-muted">معدل {{ $avgPerms }} صلاحية لكل دور</small>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-3">
                        <i class="fas fa-user-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. شريط الفلاتر السريعة والبحث اللحظي --}}
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                {{-- حقل البحث اللحظي --}}
                <div class="col-lg-4 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="search" 
                               id="roleSearchInput" 
                               class="form-control bg-light border-start-0" 
                               placeholder="بحث باسم الدور، الوصف، أو الرمز البرمجي..." 
                               autocomplete="off">
                        <button class="btn btn-outline-secondary border-start-0" type="button" id="clearSearchBtn" onclick="clearRoleSearch()" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                {{-- أزرار التصفية السريعة بالقطاعات --}}
                <div class="col-lg-8 col-md-7">
                    <div class="d-flex align-items-center gap-1 flex-wrap filter-pills-container">
                        <button type="button" class="btn btn-sm btn-dark filter-pill active" data-category="all" onclick="filterByCategory('all', this)">
                            الكل <span class="badge bg-light text-dark ms-1">{{ $totalRolesCount }}</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success filter-pill" data-category="medical" onclick="filterByCategory('medical', this)">
                            <i class="fas fa-user-md me-1"></i> الطبية والجراحية
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning filter-pill" data-category="financial" onclick="filterByCategory('financial', this)">
                            <i class="fas fa-cash-register me-1"></i> المالية والكاشير
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info filter-pill" data-category="diagnostic" onclick="filterByCategory('diagnostic', this)">
                            <i class="fas fa-flask me-1"></i> التشخيصية
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger filter-pill" data-category="reception_nursing" onclick="filterByCategory('reception_nursing', this)">
                            <i class="fas fa-ambulance me-1"></i> الاستقبال والطوارئ
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary filter-pill" data-category="pharmacy_services" onclick="filterByCategory('pharmacy_services', this)">
                            <i class="fas fa-pills me-1"></i> الصيدلية والمستودع
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary filter-pill" data-category="admin_system" onclick="filterByCategory('admin_system', this)">
                            <i class="fas fa-crown me-1"></i> الإدارة والأنظمة
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- رسالة عند عدم وجود نتائج للبحث --}}
    <div id="noResultsAlert" class="alert alert-secondary text-center py-4 rounded-3 shadow-sm" style="display: none;">
        <i class="fas fa-search-minus fa-3x text-muted mb-2 d-block"></i>
        <h5 class="fw-bold mb-1">لا توجد أدوار تطابق معايير البحث</h5>
        <p class="text-muted small mb-3">جرّب كتابة مصطلح بحث آخر أو إعادة ضبط فلاتر القطاعات</p>
        <button class="btn btn-sm btn-primary" onclick="resetAllRoleFilters()">
            <i class="fas fa-redo me-1"></i> إعادة ضبط الفلاتر
        </button>
    </div>

    {{-- 4. نمط العرض الأول: شبكة البطاقات التنفيذية (Grid Cards) --}}
    @php
        $moduleArabicNames = [
            'emergency' => 'طوارئ',
            'cashier' => 'كاشير',
            'accounting' => 'حسابات',
            'radiology' => 'أشعة',
            'lab' => 'مختبر',
            'pharmacy' => 'صيدلية',
            'consultant_doctors' => 'استشارية',
            'surgeries' => 'عمليات',
            'inquiry' => 'استعلامات',
            'whatsapp' => 'واتساب',
            'system_admin' => 'إدارة النظام',
        ];
        $roleMgmtController = new \App\Http\Controllers\RoleManagementController();
    @endphp

    <div class="row g-3" id="rolesGridView">
        @foreach($roles as $role)
            @php
                $roleLabel = \App\Http\Controllers\RoleManagementController::getRoleLabel($role->name);
                $roleCategory = \App\Http\Controllers\RoleManagementController::getRoleCategory($role->name);
                $coveragePct = ($totalPermissionsCount ?? 145) > 0 
                    ? min(100, round(($role->permissions_count / ($totalPermissionsCount ?? 145)) * 100)) 
                    : 0;

                // استخراج أهم الأقسام المفتوحة للدور
                $roleModules = $role->permissions->map(function($p) use ($roleMgmtController, $moduleArabicNames) {
                    $g = $roleMgmtController->permissionGroup($p->name);
                    return $moduleArabicNames[$g] ?? $g;
                })->unique()->values()->take(4);

                $isSystemProtected = in_array($role->name, ['admin', 'doctor', 'patient', 'receptionist', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'surgery_staff']);
                $searchKeywords = mb_strtolower($roleLabel['label'] . ' ' . $role->name . ' ' . $roleCategory['name']);
            @endphp

            <div class="col-md-6 col-lg-4 col-xl-3 role-item" 
                 data-category="{{ $roleCategory['key'] }}"
                 data-search="{{ $searchKeywords }}"
                 data-users="{{ $role->users_count }}">
                
                <div class="card border-0 shadow-sm rounded-3 h-100 role-card position-relative overflow-hidden">
                    {{-- شريط ملون علوي رفيع يمثل تصنيف الدور --}}
                    <div class="role-card-top-bar bg-{{ $roleLabel['color'] }}"></div>

                    <div class="card-body p-3 d-flex flex-column">
                        {{-- ترويسة البطاقة --}}
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <div class="role-icon-avatar rounded-circle bg-{{ $roleLabel['color'] }} bg-opacity-10 text-{{ $roleLabel['color'] }} d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="fas fa-{{ $roleLabel['icon'] }} fs-5"></i>
                                </div>
                                <div class="min-w-0">
                                    <h5 class="mb-0 fw-bold text-dark text-truncate" title="{{ $roleLabel['label'] }}">
                                        {{ $roleLabel['label'] }}
                                    </h5>
                                    <div class="d-flex align-items-center gap-1 mt-1">
                                        <code class="text-muted small px-1 py-0 bg-light rounded" style="font-size: 0.75rem;">{{ $role->name }}</code>
                                    </div>
                                </div>
                            </div>

                            {{-- شارة القطاع وحذف الدور إن كان مخصصاً --}}
                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                @if(!$isSystemProtected)
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور الوظيفي المخصص؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0 px-1" title="حذف الدور">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        {{-- شارة القطاع --}}
                        <div class="mb-3">
                            <span class="badge bg-{{ $roleCategory['color'] }} bg-opacity-10 text-{{ $roleCategory['color'] }} border border-{{ $roleCategory['color'] }} border-opacity-25 rounded-pill px-2 py-1 small">
                                <i class="fas fa-{{ $roleCategory['icon'] }} me-1"></i> {{ $roleCategory['name'] }}
                            </span>
                        </div>

                        {{-- إحصائيات سريعة: المستخدمين والصلاحيات --}}
                        <div class="row g-2 mb-3">
                            {{-- المستخدمين --}}
                            <div class="col-6">
                                <div class="bg-light bg-opacity-75 p-2 rounded-2 text-center h-100 cursor-pointer role-users-trigger" 
                                     onclick="openRoleUsersModal('{{ $role->id }}', '{{ addslashes($roleLabel['label']) }}', '{{ $role->name }}')" 
                                     title="انقر لعرض قائمة الموظفين المنتمين لهذا الدور">
                                    <span class="text-muted small d-block mb-0" style="font-size: 0.75rem;">المستخدمين</span>
                                    <span class="fw-bold fs-6 {{ $role->users_count > 0 ? 'text-primary' : 'text-muted' }}">
                                        <i class="fas fa-users me-1"></i> {{ $role->users_count }}
                                    </span>
                                </div>
                            </div>

                            {{-- الصلاحيات --}}
                            <div class="col-6">
                                <div class="bg-light bg-opacity-75 p-2 rounded-2 text-center h-100">
                                    <span class="text-muted small d-block mb-0" style="font-size: 0.75rem;">الصلاحيات</span>
                                    <span class="fw-bold fs-6 text-dark">
                                        <i class="fas fa-key me-1 text-warning"></i> {{ $role->permissions_count }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- شريط نسبة التغطية --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center small text-muted mb-1" style="font-size: 0.75rem;">
                                <span>نسبة التغطية بالصلاحيات</span>
                                <span class="fw-bold {{ $coveragePct > 50 ? 'text-success' : ($coveragePct > 15 ? 'text-primary' : 'text-secondary') }}">{{ $coveragePct }}%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-{{ $roleLabel['color'] }}" role="progressbar" style="width: {{ $coveragePct }}%;" aria-valuenow="{{ $coveragePct }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>

                        {{-- أبرز الأقسام المتاحة --}}
                        <div class="mt-auto mb-3">
                            <span class="text-muted small d-block mb-1" style="font-size: 0.72rem;">أبرز الأقسام المتاحة:</span>
                            <div class="d-flex gap-1 flex-wrap">
                                @forelse($roleModules as $mod)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-10 rounded px-1 py-0" style="font-size: 0.7rem;">
                                        {{ $mod }}
                                    </span>
                                @empty
                                    <span class="text-muted small fst-italic" style="font-size: 0.7rem;">لا توجد أقسام مخصصة</span>
                                @endforelse
                            </div>
                        </div>

                        {{-- أزرار الإجراءات --}}
                        <div class="d-flex gap-2 pt-2 border-top">
                            @if($role->name === 'admin' && !auth()->user()->hasRole('admin'))
                                <button type="button" class="btn btn-secondary btn-sm w-100" disabled title="هذا الدور محمي لمدير النظام العام فقط">
                                    <i class="fas fa-lock me-1"></i> دور نظام محمي
                                </button>
                            @else
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-outline-primary btn-sm flex-grow-1 fw-bold">
                                    <i class="fas fa-sliders-h me-1"></i> تعديل الصلاحيات
                                </a>
                                <button type="button" 
                                        class="btn btn-light btn-sm text-secondary px-2" 
                                        onclick="openRoleUsersModal('{{ $role->id }}', '{{ addslashes($roleLabel['label']) }}', '{{ $role->name }}')" 
                                        title="عرض قائمة الموظفين">
                                    <i class="fas fa-users"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- 5. نمط العرض الثاني: جدول المقارنة المدمج (Table View) --}}
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden bg-white" id="rolesTableView" style="display: none;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th style="width: 50px;" class="text-center">#</th>
                        <th>الدور الوظيفي والمسمى</th>
                        <th style="width: 220px;">القطاع والتصنيف</th>
                        <th style="width: 140px;" class="text-center">المستخدمون</th>
                        <th style="width: 180px;">الصلاحيات والتغطية</th>
                        <th>أبرز الأقسام المفتوحة</th>
                        <th style="width: 160px;" class="text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $index => $role)
                        @php
                            $roleLabel = \App\Http\Controllers\RoleManagementController::getRoleLabel($role->name);
                            $roleCategory = \App\Http\Controllers\RoleManagementController::getRoleCategory($role->name);
                            $coveragePct = ($totalPermissionsCount ?? 145) > 0 
                                ? min(100, round(($role->permissions_count / ($totalPermissionsCount ?? 145)) * 100)) 
                                : 0;
                            $roleModules = $role->permissions->map(function($p) use ($roleMgmtController, $moduleArabicNames) {
                                $g = $roleMgmtController->permissionGroup($p->name);
                                return $moduleArabicNames[$g] ?? $g;
                            })->unique()->values()->take(5);
                            $isSystemProtected = in_array($role->name, ['admin', 'doctor', 'patient', 'receptionist', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'surgery_staff']);
                            $searchKeywords = mb_strtolower($roleLabel['label'] . ' ' . $role->name . ' ' . $roleCategory['name']);
                        @endphp
                        <tr class="role-table-row role-item" 
                            data-category="{{ $roleCategory['key'] }}"
                            data-search="{{ $searchKeywords }}"
                            data-users="{{ $role->users_count }}">
                            
                            <td class="text-center text-muted small fw-semibold">
                                {{ $loop->iteration }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-{{ $roleLabel['color'] }} bg-opacity-10 text-{{ $roleLabel['color'] }} p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                        <i class="fas fa-{{ $roleLabel['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark d-block">{{ $roleLabel['label'] }}</span>
                                        <code class="text-muted small">{{ $role->name }}</code>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $roleCategory['color'] }} bg-opacity-10 text-{{ $roleCategory['color'] }} border border-{{ $roleCategory['color'] }} border-opacity-25 rounded-pill px-2 py-1">
                                    <i class="fas fa-{{ $roleCategory['icon'] }} me-1"></i> {{ $roleCategory['name'] }}
                                </span>
                            </td>
                            <td class="text-center">
                                <button type="button" 
                                        class="btn btn-sm btn-light border px-2 py-1 text-primary fw-bold"
                                        onclick="openRoleUsersModal('{{ $role->id }}', '{{ addslashes($roleLabel['label']) }}', '{{ $role->name }}')">
                                    <i class="fas fa-users me-1"></i> {{ $role->users_count }}
                                </button>
                            </td>
                            <td>
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <span class="fw-bold">{{ $role->permissions_count }} صلاحية</span>
                                    <span class="text-muted">{{ $coveragePct }}%</span>
                                </div>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-{{ $roleLabel['color'] }}" role="progressbar" style="width: {{ $coveragePct }}%;"></div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @forelse($roleModules as $mod)
                                        <span class="badge bg-light text-dark border px-2 py-0" style="font-size: 0.72rem;">
                                            {{ $mod }}
                                        </span>
                                    @empty
                                        <span class="text-muted small fst-italic">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    @if($role->name === 'admin' && !auth()->user()->hasRole('admin'))
                                        <button class="btn btn-sm btn-secondary" disabled title="محمي لمدير النظام">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    @else
                                        <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-primary" title="تعديل الصلاحيات">
                                            <i class="fas fa-edit me-1"></i> تعديل
                                        </a>
                                    @endif

                                    @if(!$isSystemProtected)
                                        <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- 6. النافذة المنبثقة الذكية لاستعراض الموظفين (Quick Role Users Modal) --}}
<div class="modal fade" id="roleUsersModal" tabindex="-1" aria-labelledby="roleUsersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-primary text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-users-cog fs-4"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="roleUsersModalLabel">الموظفون المسجلون في هذا الدور</h5>
                        <small class="text-white-50" id="roleUsersModalSub"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                {{-- شريط التحميل --}}
                <div id="roleUsersLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جارٍ التحميل...</span>
                    </div>
                    <p class="text-muted small mt-2">جارٍ جلب بيانات الكادر الوظيفي...</p>
                </div>

                {{-- جدول عرض المستخدمين --}}
                <div id="roleUsersContent" style="display: none;">
                    <div class="alert alert-info py-2 px-3 small rounded-3 d-flex align-items-center justify-content-between mb-3">
                        <span><i class="fas fa-info-circle me-1"></i> إجمالي الكادر المنتسب لهذا الدور: <strong id="modalUsersCountBadge">0</strong></span>
                        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-primary bg-white py-0 px-2" style="font-size: 0.75rem;">
                            إدارة المستخدمين <i class="fas fa-arrow-left ms-1"></i>
                        </a>
                    </div>

                    <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small sticky-top">
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>اسم الموظف / المستخدم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>رقم الهاتف</th>
                                    <th>تاريخ الانضمام</th>
                                </tr>
                            </thead>
                            <tbody id="roleUsersTableBody">
                                {{-- يتم ملء البيانات عبر AJAX --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- رسالة لا يوجد مستخدمين --}}
                <div id="roleUsersEmpty" class="text-center py-5" style="display: none;">
                    <i class="fas fa-user-slash fa-3x text-muted mb-2 d-block"></i>
                    <h6 class="fw-bold text-dark">لا يوجد أي مستخدم مسجل في هذا الدور حالياً</h6>
                    <p class="text-muted small">هذا الدور شاغر، يمكنك إسناده لأي موظف عبر شاشة إدارة المستخدمين.</p>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-4">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let activeCategory = 'all';
    let currentViewMode = 'grid';

    // 1. تبديل نمط العرض (شبكة / جدول)
    function switchViewMode(mode) {
        currentViewMode = mode;
        const gridView = document.getElementById('rolesGridView');
        const tableView = document.getElementById('rolesTableView');
        const gridBtn = document.getElementById('viewModeGridBtn');
        const tableBtn = document.getElementById('viewModeTableBtn');

        if (mode === 'grid') {
            gridView.style.display = '';
            tableView.style.display = 'none';
            gridBtn.classList.add('active');
            tableBtn.classList.remove('active');
        } else {
            gridView.style.display = 'none';
            tableView.style.display = '';
            tableBtn.classList.add('active');
            gridBtn.classList.remove('active');
        }
        localStorage.setItem('roles_view_mode', mode);
    }

    // استعادة النمط المفضل للمستخدم
    document.addEventListener('DOMContentLoaded', function () {
        const savedMode = localStorage.getItem('roles_view_mode');
        if (savedMode && (savedMode === 'grid' || savedMode === 'table')) {
            switchViewMode(savedMode);
        }
    });

    // 2. تصفية بالقطاعات (Category Pills)
    function filterByCategory(categoryKey, btn) {
        activeCategory = categoryKey;
        
        document.querySelectorAll('.filter-pill').forEach(b => {
            b.classList.remove('active', 'btn-dark', 'btn-success', 'btn-warning', 'btn-info', 'btn-danger', 'btn-primary', 'btn-secondary');
            const cat = b.dataset.category;
            if (cat === 'all') b.classList.add('btn-outline-dark');
            else if (cat === 'medical') b.classList.add('btn-outline-success');
            else if (cat === 'financial') b.classList.add('btn-outline-warning');
            else if (cat === 'diagnostic') b.classList.add('btn-outline-info');
            else if (cat === 'reception_nursing') b.classList.add('btn-outline-danger');
            else if (cat === 'pharmacy_services') b.classList.add('btn-outline-primary');
            else b.classList.add('btn-outline-secondary');
        });

        btn.classList.add('active');
        if (categoryKey === 'all') {
            btn.classList.remove('btn-outline-dark');
            btn.classList.add('btn-dark');
        } else if (categoryKey === 'medical') {
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-success');
        } else if (categoryKey === 'financial') {
            btn.classList.remove('btn-outline-warning');
            btn.classList.add('btn-warning');
        } else if (categoryKey === 'diagnostic') {
            btn.classList.remove('btn-outline-info');
            btn.classList.add('btn-info');
        } else if (categoryKey === 'reception_nursing') {
            btn.classList.remove('btn-outline-danger');
            btn.classList.add('btn-danger');
        } else if (categoryKey === 'pharmacy_services') {
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary');
        } else {
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('btn-secondary');
        }

        applyFilters();
    }

    // 3. البحث اللحظي وتطبيق الفلاتر المشتركة
    const searchInput = document.getElementById('roleSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');

    searchInput.addEventListener('input', function () {
        clearSearchBtn.style.display = this.value.trim().length > 0 ? '' : 'none';
        applyFilters();
    });

    function clearRoleSearch() {
        searchInput.value = '';
        clearSearchBtn.style.display = 'none';
        applyFilters();
        searchInput.focus();
    }

    function resetAllRoleFilters() {
        searchInput.value = '';
        clearSearchBtn.style.display = 'none';
        const allBtn = document.querySelector('.filter-pill[data-category="all"]');
        if (allBtn) filterByCategory('all', allBtn);
        else applyFilters();
    }

    function applyFilters() {
        const query = searchInput.value.trim().toLowerCase();
        const items = document.querySelectorAll('.role-item');
        let visibleCount = 0;

        items.forEach(item => {
            const itemCategory = item.dataset.category || 'other';
            const itemSearch = item.dataset.search || '';

            const matchesCategory = (activeCategory === 'all' || itemCategory === activeCategory);
            const matchesQuery = (query === '' || itemSearch.includes(query));

            if (matchesCategory && matchesQuery) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        const noResults = document.getElementById('noResultsAlert');
        if (noResults) {
            noResults.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    // 4. النافذة المنبثقة الذكية لعرض مستخدمي الدور (Quick Role Users Modal via AJAX)
    function openRoleUsersModal(roleId, roleLabel, roleName) {
        const modal = new bootstrap.Modal(document.getElementById('roleUsersModal'));
        document.getElementById('roleUsersModalLabel').textContent = 'كادر دور: ' + roleLabel;
        document.getElementById('roleUsersModalSub').textContent = 'رمز الدور: ' + roleName;
        
        const loading = document.getElementById('roleUsersLoading');
        const content = document.getElementById('roleUsersContent');
        const empty = document.getElementById('roleUsersEmpty');
        const tbody = document.getElementById('roleUsersTableBody');

        loading.style.display = '';
        content.style.display = 'none';
        empty.style.display = 'none';
        tbody.innerHTML = '';

        modal.show();

        const url = "{{ url('roles') }}/" + roleId + "/users";
        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';

            if (!data.users || data.users.length === 0) {
                empty.style.display = '';
                return;
            }

            document.getElementById('modalUsersCountBadge').textContent = data.users.length + ' موظف ومستخدم';

            data.users.forEach((u, index) => {
                const tr = document.createElement('tr');
                const createdDate = u.created_at ? new Date(u.created_at).toLocaleDateString('ar-IQ') : '-';
                tr.innerHTML = `
                    <td class="text-center text-muted small">${index + 1}</td>
                    <td>
                        <span class="fw-bold text-dark d-block">${escapeHtml(u.name)}</span>
                    </td>
                    <td><span class="text-muted small">${escapeHtml(u.email || '-')}</span></td>
                    <td><span class="badge bg-light text-dark">${escapeHtml(u.phone || '-')}</span></td>
                    <td><small class="text-muted">${createdDate}</small></td>
                `;
                tbody.appendChild(tr);
            });

            content.style.display = '';
        })
        .catch(err => {
            loading.style.display = 'none';
            empty.style.display = '';
            document.getElementById('roleUsersEmpty').innerHTML = `
                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-2 d-block"></i>
                <h6 class="text-danger fw-bold">حدث خطأ أثناء تحميل بيانات المستخدمين</h6>
                <p class="text-muted small">${err.message || 'يرجى المحاولة مرة أخرى'}</p>
            `;
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
</script>
@endpush

<style>
    /* لمسات جمالية تنفيذية متطورة */
    .role-card {
        transition: transform 0.22s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(226, 232, 240, 0.9) !important;
    }
    .role-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 0.85rem 1.6rem rgba(15, 23, 42, 0.08) !important;
    }
    .role-card-top-bar {
        height: 4px;
        width: 100%;
    }
    .role-icon-avatar {
        width: 44px;
        height: 44px;
    }
    .kpi-card {
        border: 1px solid rgba(226, 232, 240, 0.8) !important;
        transition: transform 0.2s ease;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
    }
    .filter-pill {
        border-radius: 50rem !important;
        padding: 0.35rem 0.85rem !important;
        font-weight: 600 !important;
        font-size: 0.82rem !important;
        transition: all 0.18s ease;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    .role-users-trigger:hover {
        background-color: #e0e7ff !important;
    }

    /* التوافق مع الوضع الليلي Dark Mode */
    body.dark-mode .card,
    body.dark-mode .modal-content {
        background: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #f8fafc !important;
    }
    body.dark-mode .text-dark {
        color: #f1f5f9 !important;
    }
    body.dark-mode .table {
        color: #f1f5f9 !important;
    }
    body.dark-mode .table-light {
        background: #1e293b !important;
        color: #cbd5e1 !important;
    }
    body.dark-mode .bg-light {
        background: #1e293b !important;
        color: #cbd5e1 !important;
    }
    body.dark-mode .border-top {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
</style>
@endsection
