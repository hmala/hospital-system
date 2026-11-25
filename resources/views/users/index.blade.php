@extends('layouts.app')

@section('title', 'إدارة المستخدمين وحسابات الكادر')

@push('styles')
<style>
    .user-stat-card {
        border-radius: 16px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .user-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08) !important;
    }
    .user-avatar-sm {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
        color: #ffffff;
        box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.15);
        flex-shrink: 0;
    }
    .filter-card {
        border-radius: 18px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
    }
    .modern-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    .modern-table thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        border-bottom: 2px solid #e2e8f0;
        padding: 12px 16px;
    }
    .modern-table tbody td {
        padding: 14px 16px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }
    .modern-table tbody tr:hover {
        background-color: #f8fafc;
    }
    .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-inline-end: 6px;
    }
    .status-dot-active {
        background-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    }
    .status-dot-inactive {
        background-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
    }
    .search-spinner {
        display: none;
    }
    .search-spinner.active {
        display: inline-block;
    }
    .active-filter-chip {
        font-size: 0.8rem;
        background-color: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 9999px;
        padding: 4px 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .active-filter-chip:hover {
        background-color: #dbeafe;
        color: #1e40af;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 px-lg-4">

    <!-- رأس الصفحة وأزرار الإجراء السريع -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-3 bg-primary text-white p-2 d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px;">
                    <i class="fas fa-users-gear fs-4"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark">إدارة حسابات الكادر والمستخدمين</h3>
                    <small class="text-muted">مراقبة الصلاحيات، مواقع التعيين، والحالة التشغيلية لحسابات المنظومة</small>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 shadow-xs fw-semibold">
                <i class="fas fa-shield-halved me-1 text-primary"></i> مصفوفة الصلاحيات
            </a>
            <a href="{{ route('users.create') }}" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-bold">
                <i class="fas fa-user-plus me-1.5"></i> إضافة مستخدم جديد
            </a>
        </div>
    </div>

    <!-- رسائل التنبيه والنجاح -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-check-circle fs-5 text-success"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-exclamation-circle fs-5 text-danger"></i>
                <div>{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- بطاقات الإحصائيات السريعة (KPIs) -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm user-stat-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold mb-1">إجمالي الحسابات المسجلة</div>
                        <h3 class="fw-bold text-dark mb-0 font-monospace">{{ $stats['total'] }}</h3>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-users fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm user-stat-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold mb-1">الحسابات النشطة والمفعلة</div>
                        <h3 class="fw-bold text-success mb-0 font-monospace">{{ $stats['active'] }}</h3>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-user-check fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm user-stat-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold mb-1">الحسابات المعطلة مؤقتاً</div>
                        <h3 class="fw-bold text-danger mb-0 font-monospace">{{ $stats['inactive'] }}</h3>
                    </div>
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-user-slash fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm user-stat-card h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold mb-1">الأدوار والصلاحيات الوظيفية</div>
                        <h3 class="fw-bold text-info mb-0 font-monospace">{{ $stats['roles_count'] }}</h3>
                    </div>
                    <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-id-badge fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط الفلترة والبحث اللحظي الفوري بدون أزرار (Live Instant Filter) -->
    <div class="card shadow-sm border-0 filter-card mb-4">
        <div class="card-body p-3 p-lg-4">
            <form action="{{ route('users.index') }}" method="GET" id="usersFilterForm" class="row g-3 align-items-end">

                <!-- 1. البحث النصي الفوري اللحظي -->
                <div class="col-lg-6 col-md-12">
                    <label for="search" class="form-label small fw-bold text-secondary mb-1">
                        <i class="fas fa-magnifying-glass me-1 text-primary"></i> البحث السريع (الاسم، البريد، الهاتف، الاختصاص)
                    </label>
                    <div class="input-group input-group-lg shadow-xs rounded-pill border bg-light overflow-hidden">
                        <span class="input-group-text bg-transparent border-0 pe-2 ps-3">
                            <i class="fas fa-search text-muted" id="searchDefaultIcon"></i>
                            <span class="spinner-border spinner-border-sm text-primary search-spinner" id="searchSpinner" role="status"></span>
                        </span>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               class="form-control bg-transparent border-0 fs-6" 
                               placeholder="اكتب للبحث الفوري دون الحاجة لزر..." 
                               value="{{ request('search') }}" 
                               autocomplete="off">
                        @if(request('search'))
                            <button type="button" 
                                    class="btn bg-transparent border-0 text-muted px-3" 
                                    onclick="clearSearchFilter()" 
                                    title="مسح نص البحث">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- 2. فلتر الصلاحية / الدور (لحظي فوري) -->
                <div class="col-lg-3 col-md-6">
                    <label for="role" class="form-label small fw-bold text-secondary mb-1">
                        <i class="fas fa-user-shield me-1 text-info"></i> الصلاحية / الدور الوظيفي
                    </label>
                    <select name="role" 
                            id="role" 
                            class="form-select form-select-lg rounded-pill border shadow-xs fs-6" 
                            onchange="this.form.submit()">
                        <option value="">جميع الصلاحيات (الكل)</option>
                        @foreach($roles as $role)
                            @php
                                $rInfo = \App\Http\Controllers\RoleManagementController::getRoleLabel($role->name);
                            @endphp
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                {{ $rInfo['label'] }} ({{ $role->name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 3. فلتر الحالة (لحظي فوري) -->
                <div class="col-lg-3 col-md-6">
                    <label for="status" class="form-label small fw-bold text-secondary mb-1">
                        <i class="fas fa-toggle-on me-1 text-success"></i> حالة الحساب
                    </label>
                    <select name="status" 
                            id="status" 
                            class="form-select form-select-lg rounded-pill border shadow-xs fs-6" 
                            onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط (Active)</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>معطل (Inactive)</option>
                    </select>
                </div>
            </form>

            <!-- وسوم الفلاتر النشطة وزر مسح كل الفلاتر -->
            @php
                $activeFiltersCount = collect(['search', 'role', 'status'])->filter(fn($k) => request()->filled($k))->count();
            @endphp
            @if($activeFiltersCount > 0)
                <div class="d-flex flex-wrap align-items-center gap-2 pt-3 mt-3 border-top">
                    <span class="small text-muted fw-bold"><i class="fas fa-filter me-1"></i> الفلاتر المطبقة حالياً:</span>

                    @if(request('search'))
                        <a href="{{ route('users.index', request()->except('search')) }}" class="active-filter-chip" title="حذف هذا الفلتر">
                            <span>البحث: "{{ request('search') }}"</span>
                            <i class="fas fa-times"></i>
                        </a>
                    @endif

                    @if(request('role'))
                        @php
                            $roleLabel = \App\Http\Controllers\RoleManagementController::getRoleLabel(request('role'))['label'] ?? request('role');
                        @endphp
                        <a href="{{ route('users.index', request()->except('role')) }}" class="active-filter-chip" title="حذف هذا الفلتر">
                            <span>الصلاحية: {{ $roleLabel }}</span>
                            <i class="fas fa-times"></i>
                        </a>
                    @endif

                    @if(request('status'))
                        <a href="{{ route('users.index', request()->except('status')) }}" class="active-filter-chip" title="حذف هذا الفلتر">
                            <span>الحالة: {{ request('status') === 'active' ? 'نشط' : 'معطل' }}</span>
                            <i class="fas fa-times"></i>
                        </a>
                    @endif

                    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 ms-auto fw-bold" title="إعادة تعيين كافة الفلاتر">
                        <i class="fas fa-rotate-left me-1"></i> مسح جميع الفلاتر
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- جدول المستخدمين العصري -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <h5 class="fw-bold mb-0 text-dark">قائمة الكادر والمستخدمين</h5>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1 font-monospace">
                    {{ $users->total() }} حساب
                </span>
            </div>
            <small class="text-muted">الصفحة {{ $users->currentPage() }} من {{ $users->lastPage() }}</small>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table modern-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px;" class="text-center">#</th>
                            <th style="min-width: 240px;">المستخدم والبيانات</th>
                            <th style="min-width: 200px;">البريد الإلكتروني</th>
                            <th style="min-width: 170px;">الموقع / القسم الوظيفي</th>
                            <th style="min-width: 220px;">الصلاحيات والأدوار</th>
                            <th style="min-width: 130px;" class="text-center">الحالة</th>
                            <th style="min-width: 130px;" class="text-center">تاريخ الإنشاء</th>
                            <th style="min-width: 130px;" class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $avatarColors = ['#2563eb', '#059669', '#d97706', '#7c3aed', '#db2777', '#0891b2', '#4f46e5'];
                        @endphp
                        @forelse($users as $index => $user)
                            @php
                                $color = $avatarColors[$user->id % count($avatarColors)];
                                $firstLetter = mb_substr(trim($user->name), 0, 1, 'UTF-8');
                                $isSuperAdmin = $user->hasRole(['admin', 'super_admin']);
                                $isCurrentUser = $user->id === auth()->id();
                            @endphp
                            <tr>
                                <td class="text-center font-monospace text-muted small fw-bold">
                                    {{ $user->id }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="user-avatar-sm" style="background-color: {{ $color }};">
                                            {{ $firstLetter }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-1.5">
                                                <span>{{ $user->name }}</span>
                                                @if($isCurrentUser)
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">حسابك</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted d-flex align-items-center gap-2 mt-0.5">
                                                @if($user->phone)
                                                    <span class="font-monospace"><i class="fas fa-phone-alt me-1 text-secondary opacity-75"></i>{{ $user->phone }}</span>
                                                @endif
                                                @if($user->specialization)
                                                    <span>• {{ $user->specialization }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1.5 font-monospace text-secondary">
                                        <i class="far fa-envelope text-muted"></i>
                                        <a href="mailto:{{ $user->email }}" class="text-decoration-none text-secondary">
                                            {{ $user->email }}
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    @if($user->location)
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5">
                                            <i class="fas fa-warehouse text-info"></i> {{ $user->location->name }}
                                        </span>
                                    @else
                                        <span class="text-muted small">— عام (غير مقيد)</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse($user->roles as $role)
                                            @php
                                                $rInfo = \App\Http\Controllers\RoleManagementController::getRoleLabel($role->name);
                                            @endphp
                                            <span class="badge bg-{{ $rInfo['color'] }}-subtle text-{{ $rInfo['color'] }}-emphasis border border-{{ $rInfo['color'] }}-subtle rounded-pill px-2.5 py-1 d-inline-flex align-items-center gap-1">
                                                <i class="fas fa-{{ $rInfo['icon'] }} small"></i> {{ $rInfo['label'] }}
                                            </span>
                                        @empty
                                            <span class="text-muted small">بدون أدوار محددة</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($user->is_active)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold d-inline-flex align-items-center">
                                            <span class="status-dot status-dot-active"></span> نشط ومفعل
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1.5 fw-bold d-inline-flex align-items-center">
                                            <span class="status-dot status-dot-inactive"></span> معطل
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center text-muted font-monospace small">
                                    {{ $user->created_at ? $user->created_at->format('Y-m-d') : '—' }}
                                </td>
                                <td class="text-center">
                                    @if($isSuperAdmin && !auth()->user()->hasRole('admin'))
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1">
                                            <i class="fas fa-lock me-1"></i> محمي
                                        </span>
                                    @else
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <!-- زر التعديل -->
                                            <a href="{{ route('users.edit', $user) }}" 
                                               class="btn btn-sm btn-outline-primary rounded-circle d-flex align-items-center justify-content-center shadow-xs" 
                                               style="width: 32px; height: 32px;" 
                                               title="تعديل ملف المستخدم">
                                                <i class="fas fa-pen-to-square"></i>
                                            </a>

                                            @if(!$isCurrentUser && !$isSuperAdmin)
                                                <!-- زر التعطيل / التفعيل -->
                                                <form action="{{ route('users.toggle-status', $user) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-{{ $user->is_active ? 'warning' : 'success' }} rounded-circle d-flex align-items-center justify-content-center shadow-xs" 
                                                            style="width: 32px; height: 32px;" 
                                                            title="{{ $user->is_active ? 'تعطيل الحساب' : 'تفعيل الحساب' }}">
                                                        <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }}"></i>
                                                    </button>
                                                </form>

                                                <!-- زر الحذف -->
                                                <form action="{{ route('users.destroy', $user) }}" 
                                                      method="POST" 
                                                      class="d-inline" 
                                                      onsubmit="return confirm('هل أنت متأكد من حذف حساب ({{ $user->name }}) نهائياً؟');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="btn btn-sm btn-outline-danger rounded-circle d-flex align-items-center justify-content-center shadow-xs" 
                                                            style="width: 32px; height: 32px;" 
                                                            title="حذف الحساب">
                                                        <i class="fas fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center justify-content-center text-muted">
                                        <div class="rounded-circle bg-light p-3 mb-3 text-secondary">
                                            <i class="fas fa-users-slash fa-2x"></i>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">لم يتم العثور على أي مستخدمين مطابِقين</h6>
                                        <p class="small text-muted mb-3">جرب تعديل كلمات البحث أو مسح الفلاتر لعرض كافة الحسابات</p>
                                        @if(request()->anyFilled(['search', 'role', 'location_id', 'status']))
                                            <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-4 fw-bold">
                                                <i class="fas fa-rotate-left me-1"></i> مسح الفلاتر وإعادة الضبط
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- الترقيم والصفحات -->
            @if($users->hasPages())
                <div class="card-footer bg-white border-0 py-3 d-flex justify-content-center">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('search');
        const filterForm = document.getElementById('usersFilterForm');
        const searchSpinner = document.getElementById('searchSpinner');
        const searchDefaultIcon = document.getElementById('searchDefaultIcon');
        let searchDebounceTimer = null;

        if (searchInput && filterForm) {
            // التركيز ووضع المؤشر في نهاية الكلمة إذا كان هناك بحث نشط مسبقاً
            if (searchInput.value.trim().length > 0) {
                searchInput.focus();
                const len = searchInput.value.length;
                searchInput.setSelectionRange(len, len);
            }

            // البحث اللحظي التلقائي فور التوقف عن الكتابة (Debounce 400ms)
            searchInput.addEventListener('input', function () {
                clearTimeout(searchDebounceTimer);

                if (searchSpinner && searchDefaultIcon) {
                    searchDefaultIcon.classList.add('d-none');
                    searchSpinner.classList.add('active');
                }

                searchDebounceTimer = setTimeout(function () {
                    filterForm.submit();
                }, 420);
            });

            // دعم ضغط زر Enter للبحث الفوري
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    clearTimeout(searchDebounceTimer);
                    filterForm.submit();
                }
            });
        }
    });

    function clearSearchFilter() {
        const searchInput = document.getElementById('search');
        const filterForm = document.getElementById('usersFilterForm');
        if (searchInput && filterForm) {
            searchInput.value = '';
            filterForm.submit();
        }
    }
</script>
@endpush
@endsection
