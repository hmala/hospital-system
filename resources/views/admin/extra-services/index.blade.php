@extends('layouts.app')

@section('title', __('الخدمات المتفرقة للمستشفى'))

@section('content')
<style>
    .extra-kpi-card {
        border: 0;
        border-radius: 14px;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .extra-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08) !important;
    }
    .quick-price-input {
        width: 125px;
        font-family: monospace;
        font-weight: 700;
        text-align: center;
    }
    .quick-price-saved {
        animation: quickPriceFlash 1.2s ease-out;
    }
    @keyframes quickPriceFlash {
        0% { background-color: #d1fae5; border-color: #10b981; }
        100% { background-color: #ffffff; border-color: #cbd5e1; }
    }
</style>

<div class="container-fluid py-3">
    <!-- Page Header -->
    <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="border-radius: 16px;">
        <div class="card-header text-white p-3 p-md-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f766e 100%);">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white bg-opacity-15 rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-concierge-bell fa-lg text-warning"></i>
                    </div>
                    <div>
                        <h4 class="mb-1 fw-bold">{{ __('الخدمات المتفرقة للمستشفى') }}</h4>
                        <p class="mb-0 text-white-50 small">
                            {{ __('إدارة وتسعير وتصنيف كافة الخدمات الإضافية والفندقية والإدارية والغرامات (العمليات الجراحية، قسم الخدج NICU، والخدمات المشتركة)') }}
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-warning fw-bold text-dark px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addExtraServiceModal">
                        <i class="fas fa-plus-circle me-1"></i> {{ __('إضافة خدمة متفرقة جديدة') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-xs mb-3 rounded-3" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-xs mb-3 rounded-3" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card extra-kpi-card shadow-xs bg-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block">{{ __('إجمالي الخدمات') }}</span>
                        <h3 class="mb-0 fw-bold font-monospace text-dark">{{ $stats['total'] }}</h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                        <i class="fas fa-layer-group fa-lg"></i>
                    </div>
                </div>
                <small class="text-success mt-2 d-block"><i class="fas fa-check-circle me-1"></i>{{ __('فعّالة:') }} <strong>{{ $stats['active'] }}</strong></small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card extra-kpi-card shadow-xs bg-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block">{{ __('العمليات والخدمات العامة') }}</span>
                        <h3 class="mb-0 fw-bold font-monospace text-primary">{{ $stats['general'] }}</h3>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-3">
                        <i class="fas fa-procedures fa-lg"></i>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">{{ __('تظهر في كاشير العمليات والطلبات') }}</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card extra-kpi-card shadow-xs bg-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block">{{ __('قسم الخدج والحاضنات (NICU)') }}</span>
                        <h3 class="mb-0 fw-bold font-monospace text-success">{{ $stats['nicu'] }}</h3>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                        <i class="fas fa-baby fa-lg"></i>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">{{ __('تظهر حصراً في كاشير الخدج') }}</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card extra-kpi-card shadow-xs bg-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block">{{ __('مشترك (كافة الأقسام)') }}</span>
                        <h3 class="mb-0 fw-bold font-monospace text-warning">{{ $stats['shared'] }}</h3>
                    </div>
                    <div class="bg-warning bg-opacity-15 text-warning rounded-circle p-3">
                        <i class="fas fa-globe fa-lg"></i>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">{{ __('تظهر بالعمليات والخدج معاً') }}</small>
            </div>
        </div>
    </div>

    <!-- Department Tabs & Filters -->
    <div class="card border-0 shadow-sm mb-4 rounded-4 bg-white">
        <div class="card-body p-3">
            <!-- Quick Department Pills -->
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-3 border-bottom">
                <ul class="nav nav-pills gap-2 mb-0">
                    <li class="nav-item">
                        <a class="nav-link fw-bold py-1.5 px-3 {{ $department === 'all_depts' ? 'active bg-dark' : 'text-secondary bg-light' }}"
                           href="{{ route('admin.extra-services.index', ['department' => 'all_depts']) }}">
                            <i class="fas fa-th-list me-1"></i> {{ __('كل الأقسام') }} ({{ $stats['total'] }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold py-1.5 px-3 {{ $department === 'general' ? 'active bg-primary' : 'text-secondary bg-light' }}"
                           href="{{ route('admin.extra-services.index', ['department' => 'general']) }}">
                            <i class="fas fa-procedures me-1"></i> {{ __('العمليات والخدمات العامة') }} ({{ $stats['general'] }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold py-1.5 px-3 {{ $department === 'nicu' ? 'active bg-success' : 'text-secondary bg-light' }}"
                           href="{{ route('admin.extra-services.index', ['department' => 'nicu']) }}">
                            <i class="fas fa-baby me-1"></i> {{ __('قسم الخدج والحاضنات (NICU)') }} ({{ $stats['nicu'] }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-bold py-1.5 px-3 {{ $department === 'all' ? 'active bg-warning text-dark' : 'text-secondary bg-light' }}"
                           href="{{ route('admin.extra-services.index', ['department' => 'all']) }}">
                            <i class="fas fa-globe me-1"></i> {{ __('مشترك (كافة الأقسام)') }} ({{ $stats['shared'] }})
                        </a>
                    </li>
                </ul>
                <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 small">
                    <i class="fas fa-bolt me-1"></i> {{ __('تعديل السعر يحفظ تلقائياً عند الضغط على Enter أو الخروج من الخانة') }}
                </span>
            </div>

            <!-- Filter Bar -->
            <form action="{{ route('admin.extra-services.index') }}" method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="department" value="{{ $department }}">
                <div class="col-md-5">
                    <label class="form-label small text-muted fw-bold mb-1">{{ __('بحث بالاسم أو الكود') }}</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="{{ __('ابحث عن خدمة (مثلاً: تخطيط قلب، علاج ضوئي، مرافق، غرامة...)') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted fw-bold mb-1">{{ __('التصنيف') }}</label>
                    <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $category === 'all' ? 'selected' : '' }}>{{ __('كافة التصنيفات') }}</option>
                        @foreach($categories as $catKey => $catMeta)
                            <option value="{{ $catKey }}" {{ $category === $catKey ? 'selected' : '' }}>{{ $catMeta['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted fw-bold mb-1">{{ __('الحالة') }}</label>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>{{ __('الكل') }}</option>
                        <option value="active" {{ $status === 'active' ? 'selected' : '' }}>{{ __('فعّال فقط') }}</option>
                        <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>{{ __('معطّل فقط') }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1 fw-bold">
                        <i class="fas fa-filter me-1"></i> {{ __('تصفية') }}
                    </button>
                    @if($search !== '' || $category !== 'all' || $status !== 'all')
                        <a href="{{ route('admin.extra-services.index', ['department' => $department]) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('مسح الفلاتر') }}">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Services Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="fas fa-list-ul text-primary me-2"></i>
                {{ __('قائمة الخدمات المتفرقة وأسعارها المعتمدة') }}
                <span class="badge bg-primary-subtle text-primary border ms-2">{{ $services->count() }} {{ __('خدمة') }}</span>
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="ps-4" style="width: 55px;">#</th>
                        <th>{{ __('اسم الخدمة') }}</th>
                        <th class="text-center">{{ __('النطاق / القسم') }}</th>
                        <th class="text-center">{{ __('التصنيف') }}</th>
                        <th class="text-center" style="width: 220px;">{{ __('السعر الافتراضي (د.ع)') }}</th>
                        <th class="text-center">{{ __('الحالة') }}</th>
                        <th class="text-center pe-4" style="width: 170px;">{{ __('الإجراءات') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($services as $index => $srv)
                        @php
                            $catMeta = $categories[$srv->category] ?? $categories['general'];
                            $deptBadge = match($srv->department) {
                                'nicu' => 'bg-success-subtle text-success border border-success-subtle',
                                'all' => 'bg-warning-subtle text-dark border border-warning-subtle',
                                default => 'bg-primary-subtle text-primary border border-primary-subtle',
                            };
                        @endphp
                        <tr class="{{ $srv->is_active ? '' : 'table-light text-muted opacity-75' }}">
                            <td class="ps-4 font-monospace small text-muted">{{ $index + 1 }}</td>
                            <td>
                                <strong class="text-dark d-block">{{ $srv->name }}</strong>
                                @if($srv->code)
                                    <span class="badge bg-light text-muted border font-monospace" style="font-size: 10px;">{{ $srv->code }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $deptBadge }} px-2.5 py-1">
                                    {{ $srv->department_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $catMeta['badge'] }} px-2.5 py-1">
                                    <i class="fas {{ $catMeta['icon'] }} me-1"></i>{{ $catMeta['name'] }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-1">
                                    <input type="number"
                                           class="form-control form-control-sm text-success quick-price-input"
                                           value="{{ (float)$srv->default_price }}"
                                           min="0"
                                           step="any"
                                           data-url="{{ route('admin.extra-services.quick-price', $srv) }}"
                                           data-original="{{ (float)$srv->default_price }}">
                                    <span class="small text-muted fw-bold">{{ __('د.ع') }}</span>
                                </div>
                                <div class="small text-success fw-bold d-none quick-save-badge" style="font-size: 11px;">
                                    <i class="fas fa-check me-1"></i>{{ __('تم الحفظ') }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if($srv->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">
                                        <i class="fas fa-circle me-1" style="font-size: 7px;"></i>{{ __('فعّال') }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1">
                                        <i class="fas fa-pause-circle me-1"></i>{{ __('معطّل') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-inline-flex gap-1">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary edit-service-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editExtraServiceModal"
                                            data-action="{{ route('admin.extra-services.update', $srv) }}"
                                            data-name="{{ $srv->name }}"
                                            data-code="{{ $srv->code }}"
                                            data-department="{{ $srv->department ?? 'general' }}"
                                            data-category="{{ $srv->category ?? 'general' }}"
                                            data-price="{{ (float)$srv->default_price }}"
                                            data-active="{{ $srv->is_active ? '1' : '0' }}"
                                            title="{{ __('تعديل كامل') }}">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('admin.extra-services.toggle', $srv) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $srv->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}" title="{{ $srv->is_active ? __('تعطيل الخدمة') : __('تفعيل الخدمة') }}">
                                            <i class="fas {{ $srv->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.extra-services.destroy', $srv) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد من حذف هذه الخدمة من الدليل؟ لن تتأثر الفواتير المسجلة سابقاً.') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('حذف') }}">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                {{ __('لا توجد خدمات متفرقة مطابقة لخيارات البحث الحالية.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: إضافة خدمة متفرقة جديدة -->
<div class="modal fade" id="addExtraServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-plus-circle text-warning me-2"></i>{{ __('إضافة خدمة متفرقة جديدة') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.extra-services.store') }}" method="POST">
                @csrf
                <input type="hidden" name="redirect_department" value="{{ $department }}">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">{{ __('اسم الخدمة *') }}</label>
                        <input type="text" name="name" class="form-control" required placeholder="{{ __('مثال: علاج ضوئي إضافي، مبيت مرافق، فتح طبلة...') }}">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">{{ __('القسم / النطاق *') }}</label>
                            <select name="department" class="form-select" required>
                                @foreach($departments as $deptKey => $deptName)
                                    <option value="{{ $deptKey }}" {{ ($department === $deptKey) ? 'selected' : '' }}>{{ $deptName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">{{ __('التصنيف *') }}</label>
                            <select name="category" class="form-select" required>
                                @foreach($categories as $catKey => $catMeta)
                                    <option value="{{ $catKey }}">{{ $catMeta['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold small">{{ __('السعر الافتراضي المعتمد (د.ع) *') }}</label>
                            <div class="input-group">
                                <input type="number" name="default_price" class="form-control fw-bold text-success font-monospace" min="0" step="any" value="0" required>
                                <span class="input-group-text fw-bold">{{ __('د.ع') }}</span>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">{{ __('كود اختياري') }}</label>
                            <input type="text" name="code" class="form-control font-monospace" placeholder="SRV-01">
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="add_is_active" checked>
                        <label class="form-check-label fw-bold small" for="add_is_active">{{ __('تفعيل الخدمة وإظهارها في الكاشير') }}</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark px-4">
                        <i class="fas fa-save me-1"></i> {{ __('حفظ الخدمة') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: تعديل خدمة متفرقة -->
<div class="modal fade" id="editExtraServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-edit me-2"></i>{{ __('تعديل الخدمة المتفرقة') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editExtraServiceForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">{{ __('اسم الخدمة *') }}</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">{{ __('القسم / النطاق *') }}</label>
                            <select name="department" id="edit_department" class="form-select" required>
                                @foreach($departments as $deptKey => $deptName)
                                    <option value="{{ $deptKey }}">{{ $deptName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">{{ __('التصنيف *') }}</label>
                            <select name="category" id="edit_category" class="form-select" required>
                                @foreach($categories as $catKey => $catMeta)
                                    <option value="{{ $catKey }}">{{ $catMeta['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-bold small">{{ __('السعر الافتراضي المعتمد (د.ع) *') }}</label>
                            <div class="input-group">
                                <input type="number" name="default_price" id="edit_price" class="form-control fw-bold text-success font-monospace" min="0" step="any" required>
                                <span class="input-group-text fw-bold">{{ __('د.ع') }}</span>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">{{ __('كود اختياري') }}</label>
                            <input type="text" name="code" id="edit_code" class="form-control font-monospace">
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active">
                        <label class="form-check-label fw-bold small" for="edit_is_active">{{ __('تفعيل الخدمة وإظهارها في الكاشير') }}</label>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="fas fa-check me-1"></i> {{ __('حفظ التغييرات') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. تعبئة مودال التعديل
    document.querySelectorAll('.edit-service-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const form = document.getElementById('editExtraServiceForm');
            if (form) form.action = this.dataset.action;
            document.getElementById('edit_name').value = this.dataset.name || '';
            document.getElementById('edit_code').value = this.dataset.code || '';
            document.getElementById('edit_department').value = this.dataset.department || 'general';
            document.getElementById('edit_category').value = this.dataset.category || 'general';
            document.getElementById('edit_price').value = this.dataset.price || 0;
            document.getElementById('edit_is_active').checked = (this.dataset.active === '1');
        });
    });

    // 2. الحفظ التلقائي الفوري للسعر عبر AJAX
    async function saveQuickPrice(input) {
        const val = parseFloat(input.value);
        const orig = parseFloat(input.dataset.original);
        if (isNaN(val) || val < 0) {
            input.value = orig;
            return;
        }
        if (val === orig) return;

        const url = input.dataset.url;
        const cell = input.closest('td');
        const badge = cell ? cell.querySelector('.quick-save-badge') : null;

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ default_price: val })
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const data = await response.json();
            if (data.success) {
                input.dataset.original = val;
                input.classList.remove('quick-price-saved');
                void input.offsetWidth;
                input.classList.add('quick-price-saved');

                if (badge) {
                    badge.classList.remove('d-none');
                    setTimeout(() => badge.classList.add('d-none'), 2200);
                }
            }
        } catch (err) {
            input.value = orig;
            alert('{{ __('تعذر حفظ السعر، يرجى المحاولة مرة أخرى.') }}');
        }
    }

    document.querySelectorAll('.quick-price-input').forEach(function(input) {
        input.addEventListener('blur', function() {
            saveQuickPrice(this);
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });
});
</script>
@endsection
