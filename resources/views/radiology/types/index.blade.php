<!-- resources/views/radiology/types/index.blade.php -->
@extends('layouts.app')

@section('styles')
<style>
    .table-services {
        width: 100%;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    .table-services thead th {
        background-color: #f8fafc !important;
        color: #334155 !important;
        font-weight: 700 !important;
        font-size: 0.84rem !important;
        padding: 12px 14px !important;
        border-bottom: 2px solid #e2e8f0 !important;
        white-space: nowrap !important;
    }
    .table-services tbody td {
        padding: 11px 14px !important;
        vertical-align: middle !important;
        font-size: 0.88rem !important;
        border-bottom: 1px solid #f1f5f9 !important;
        color: #1e293b !important;
    }
    .table-services tbody tr:hover td {
        background-color: #f8fafc !important;
    }
    .table-services tbody tr.table-active td {
        background-color: #eff6ff !important;
    }
    .status-badge-active {
        background-color: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
        padding: 3px 8px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
    }
    .status-badge-inactive {
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        padding: 3px 8px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
    }
    .bulk-toolbar {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 16px;
        transition: all 0.3s ease;
    }
    .form-switch .form-check-input {
        cursor: pointer;
        width: 2.3em;
        height: 1.25em;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border-radius: 20px;">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-x-ray me-3"></i>دليل وتسعير أنواع الأشعة والتصوير
                            </h2>
                            <p class="mb-0 opacity-75">إدارة فحوصات الأشعة والسونار والمفراس والرنين مع التحكم الفوري بشمولية الضمان الصحي والداخلية والتعديل الجماعي السريع</p>
                        </div>
                        <div class="d-flex gap-2">
                            @can('manage radiology types')
                            <button type="button" class="btn btn-light text-primary fw-bold px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#createRadiologyTypeModal">
                                <i class="fas fa-plus me-2"></i>إضافة فحص جديد
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                        <i class="fas fa-x-ray fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">إجمالي الفحوصات</div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['total'] ?? $types->total() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">مشمولة بالضمان (HI)</div>
                        <div class="fs-4 fw-bold text-success">{{ $stats['hi_active'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3 text-warning">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">خارج الضمان (كاش فقط)</div>
                        <div class="fs-4 fw-bold text-warning">{{ $stats['hi_inactive'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3 text-info">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">الفحوصات المفعلة</div>
                        <div class="fs-4 fw-bold text-info">{{ $stats['active'] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center">
            <i class="fas fa-check-circle fa-lg me-3 text-success"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center">
            <i class="fas fa-exclamation-circle fa-lg me-3 text-danger"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- Dynamic AJAX Toast Alert -->
    <div id="ajaxToast" class="alert border-0 shadow-sm rounded-3 mb-4 align-items-center" style="display: none;">
        <i id="ajaxToastIcon" class="fas fa-lg me-3"></i>
        <div id="ajaxToastMsg" class="fw-bold"></div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('radiology.types.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="بحث باسم الفحص أو الكود..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="subcategory" class="form-select bg-light border-0">
                        <option value="">كل الأقسام</option>
                        @foreach(['أشعة', 'سونار', 'مفراس', 'الرنين', 'إيكو'] as $sub)
                            <option value="{{ $sub }}" {{ request('subcategory') == $sub ? 'selected' : '' }}>{{ $sub }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="insurance_filter" class="form-select bg-light border-0 fw-semibold text-primary">
                        <option value="">🛡️ كل تغطيات الضمان</option>
                        <option value="hi_active" {{ request('insurance_filter') === 'hi_active' ? 'selected' : '' }}>💚 مشمولة بالضمان الصحي (HI)</option>
                        <option value="none_active" {{ request('insurance_filter') === 'none_active' ? 'selected' : '' }}>🚫 غير مشمولة بالضمان (كاش فقط)</option>
                        <option value="moi_active" {{ request('insurance_filter') === 'moi_active' ? 'selected' : '' }}>👮 مشمولة بضمان الداخلية (MOI)</option>
                        <option value="both_active" {{ request('insurance_filter') === 'both_active' ? 'selected' : '' }}>⭐ مشمولة بالضمانين معاً</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="is_active" class="form-select bg-light border-0">
                        <option value="">كل الحالات</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>مفعلة</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>معطلة</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold" title="فلترة">
                        <i class="fas fa-filter"></i>
                    </button>
                    @if(request()->hasAny(['search', 'subcategory', 'is_active', 'insurance_filter']))
                        <a href="{{ route('radiology.types.index') }}" class="btn btn-outline-secondary rounded-pill" title="إعادة تعيين">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Category & Global Switch Card -->
    @can('manage radiology types')
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-2 text-primary">
                        <i class="fas fa-bolt fa-lg"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark fs-6">التحكم السريع الشامل (تفعيل / إطفاء الكل لكل قسم)</div>
                        <div class="text-muted small">تطبيق تفعيل أو استبعاد الضمان بنقرة واحدة لقسم محدد (أشعة، سونار، رنين، مفراس) أو لكافة الفحوصات</div>
                    </div>
                </div>

                <!-- Global Toggle Action Form -->
                <form action="{{ route('radiology.types.global-toggle') }}" method="POST" class="d-flex flex-wrap align-items-center gap-2 m-0" onsubmit="return confirm('هل أنت متأكد من تنفيذ هذا التعديل الشامل؟');">
                    @csrf
                    <!-- Subcategory Selector -->
                    <select name="subcategory" class="form-select form-select-sm rounded-pill border bg-light" style="width: auto; min-width: 170px;">
                        <option value="">⚡ كافة الأقسام (الكل)</option>
                        @foreach(['أشعة', 'سونار', 'مفراس', 'الرنين', 'إيكو'] as $sub)
                            <option value="{{ $sub }}" {{ request('subcategory') == $sub ? 'selected' : '' }}>قسم: {{ $sub }}</option>
                        @endforeach
                    </select>

                    <!-- HI Dropdown -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle rounded-pill px-3 fw-bold" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-shield-alt me-1"></i> هيئة الضمان (HI)
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                            <li>
                                <button type="submit" name="field" value="is_hi_active" class="dropdown-item text-success fw-bold py-2" onclick="this.form.querySelector('input[name=state]').value='1'">
                                    <i class="fas fa-check-circle me-2"></i> تفعيل الضمان (HI) للقسم المحدد
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button type="submit" name="field" value="is_hi_active" class="dropdown-item text-danger fw-bold py-2" onclick="this.form.querySelector('input[name=state]').value='0'">
                                    <i class="fas fa-ban me-2"></i> إطفاء / استبعاد من الضمان (كاش فقط)
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- MOI Dropdown -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle rounded-pill px-3 fw-bold" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-id-badge me-1"></i> ضمان الداخلية (MOI)
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                            <li>
                                <button type="submit" name="field" value="is_moi_active" class="dropdown-item text-primary fw-bold py-2" onclick="this.form.querySelector('input[name=state]').value='1'">
                                    <i class="fas fa-check-circle me-2"></i> تفعيل ضمان الداخلية للقسم
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button type="submit" name="field" value="is_moi_active" class="dropdown-item text-danger fw-bold py-2" onclick="this.form.querySelector('input[name=state]').value='0'">
                                    <i class="fas fa-times-circle me-2"></i> إطفاء ضمان الداخلية للقسم
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle rounded-pill px-3 fw-bold" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-toggle-on me-1"></i> حالة الفحوصات
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                            <li>
                                <button type="submit" name="field" value="is_active" class="dropdown-item text-primary fw-bold py-2" onclick="this.form.querySelector('input[name=state]').value='1'">
                                    <i class="fas fa-play me-2"></i> تفعيل كل الفحوصات للقسم
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button type="submit" name="field" value="is_active" class="dropdown-item text-secondary fw-bold py-2" onclick="this.form.querySelector('input[name=state]').value='0'">
                                    <i class="fas fa-pause me-2"></i> تعطيل كل الفحوصات للقسم
                                </button>
                            </li>
                        </ul>
                    </div>

                    <input type="hidden" name="state" value="1">
                </form>
            </div>
        </div>
    </div>
    @endcan

    <!-- Bulk Actions Bar (Shown when items are checked) -->
    <div id="bulkActionsBar" class="card border-0 shadow-lg bulk-toolbar text-white mb-4 p-3" style="display: none;">
        <form id="bulkActionForm" action="{{ route('radiology.types.bulk-action') }}" method="POST">
            @csrf
            <input type="hidden" name="action" id="bulkActionInput" value="">
            <div id="bulkHiddenInputs"></div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill fw-bold" id="selectedCountBadge">0 فحوصات مختارة</span>
                    <span class="text-white-50 small">اختر الإجراء الجماعي لتطبيقه على الفحوصات المحددة:</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <!-- HI Actions -->
                    <button type="button" class="btn btn-sm btn-success rounded-pill fw-bold px-3 shadow-sm" onclick="submitBulkAction('hi_enable')">
                        <i class="fas fa-shield-alt me-1"></i> تفعيل في الضمان الصحي (HI)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning text-white rounded-pill fw-bold px-3 shadow-sm" onclick="submitBulkAction('hi_disable')">
                        <i class="fas fa-ban me-1"></i> استبعاد من الضمان (كاش فقط)
                    </button>

                    <!-- MOI Actions -->
                    <button type="button" class="btn btn-sm btn-info text-dark rounded-pill fw-bold px-3 shadow-sm" onclick="submitBulkAction('moi_enable')">
                        <i class="fas fa-id-badge me-1"></i> تفعيل الداخلية
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light rounded-pill fw-bold px-3 shadow-sm" onclick="submitBulkAction('moi_disable')">
                        <i class="fas fa-times me-1"></i> استبعاد الداخلية
                    </button>

                    <!-- Status Actions -->
                    <button type="button" class="btn btn-sm btn-primary rounded-pill fw-bold px-3 shadow-sm" onclick="submitBulkAction('activate')">
                        <i class="fas fa-check me-1"></i> تفعيل الفحص
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill fw-bold px-3 shadow-sm" onclick="submitBulkAction('deactivate')">
                        <i class="fas fa-pause me-1"></i> تعطيل الفحص
                    </button>

                    <!-- Delete Action -->
                    <button type="button" class="btn btn-sm btn-outline-danger text-white rounded-pill fw-bold px-3 shadow-sm" onclick="if(confirm('هل أنت متأكد من حذف الفحوصات المحددة غير المستخدمة؟')) submitBulkAction('delete');">
                        <i class="fas fa-trash-alt me-1"></i> حذف المحدد
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Types Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-list me-2 text-primary"></i>قائمة فحوصات الأشعة والتصوير
                </h5>
                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold">
                    {{ $types->total() }} فحص
                </span>
                @if(request('subcategory'))
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-bold">
                        قسم: {{ request('subcategory') }}
                    </span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                @can('manage radiology types')
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="toggleSelectAll()">
                    <i class="fas fa-check-double me-1"></i> <span id="selectAllBtnText">تحديد كل الصفحة</span>
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            @if($types->count() > 0)
            <div class="table-responsive w-100 m-0">
                <table class="table table-hover table-services align-middle mb-0">
                    <thead>
                        <tr>
                            @can('manage radiology types')
                            <th style="width: 40px;" class="text-center">
                                <input type="checkbox" class="form-check-input" id="checkAll" onchange="handleMasterCheck(this)">
                            </th>
                            @endcan
                            <th style="width: 50px;">#</th>
                            <th>اسم الفحص والكود</th>
                            <th>القسم</th>
                            <th>سعر الكاش (د.ع)</th>
                            <th class="text-center" style="width: 190px;">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-shield-alt text-success"></i>
                                    <span>هيئة الضمان (HI)</span>
                                </div>
                            </th>
                            <th class="text-center" style="width: 180px;">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-id-badge text-primary"></i>
                                    <span>ضمان الداخلية (MOI)</span>
                                </div>
                            </th>
                            <th class="text-center" style="width: 110px;">الحالة</th>
                            <th class="text-center" style="width: 120px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($types as $type)
                        <tr id="serviceRow{{ $type->id }}">
                            @can('manage radiology types')
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input service-check" value="{{ $type->id }}" onchange="handleRowCheck()">
                            </td>
                            @endcan
                            <td class="text-muted fw-bold">#{{ $type->id }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $type->name }}</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="badge bg-light text-muted border px-1 py-0 font-monospace" style="font-size: 0.7rem;">{{ $type->code }}</span>
                                    @if($type->requires_contrast)
                                        <span class="badge bg-warning-subtle text-danger border border-warning-subtle" style="font-size: 0.62rem;">مع صبغة</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @php
                                    $subBadgeColor = match($type->subcategory) {
                                        'أشعة' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'سونار' => 'bg-info-subtle text-info border border-info-subtle',
                                        'مفراس' => 'bg-warning-subtle text-dark border border-warning-subtle',
                                        'الرنين' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        'إيكو' => 'bg-success-subtle text-success border border-success-subtle',
                                        default => 'bg-light text-secondary border'
                                    };
                                @endphp
                                <span class="badge {{ $subBadgeColor }} rounded-pill px-2 py-1 fw-bold">{{ $type->subcategory ?: 'عام' }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">
                                    {{ number_format($type->base_price, 0) }} د.ع
                                </div>
                            </td>

                            {{-- تبديل هيئة الضمان الصحي HI --}}
                            <td class="text-center">
                                <div class="d-inline-flex flex-column align-items-center">
                                    @can('manage radiology types')
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" 
                                               id="switchHi{{ $type->id }}" 
                                               {{ $type->is_hi_active ? 'checked' : '' }}
                                               onchange="quickToggleType({{ $type->id }}, 'is_hi_active', this)">
                                    </div>
                                    @endcan
                                    <span id="badgeHi{{ $type->id }}" class="badge {{ $type->is_hi_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} px-2 py-1" style="font-size: 0.72rem;">
                                        {{ $type->is_hi_active ? ($type->hi_price ? number_format($type->hi_price, 0).' د.ع' : 'مشمول (سعر الكاش)') : 'غير مشمول (كاش)' }}
                                    </span>
                                </div>
                            </td>

                            {{-- تبديل ضمان الداخلية MOI --}}
                            <td class="text-center">
                                <div class="d-inline-flex flex-column align-items-center">
                                    @can('manage radiology types')
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" 
                                               id="switchMoi{{ $type->id }}" 
                                               {{ $type->is_moi_active ? 'checked' : '' }}
                                               onchange="quickToggleType({{ $type->id }}, 'is_moi_active', this)">
                                    </div>
                                    @endcan
                                    <span id="badgeMoi{{ $type->id }}" class="badge {{ $type->is_moi_active ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} px-2 py-1" style="font-size: 0.72rem;">
                                        {{ $type->is_moi_active ? ($type->moi_price ? number_format($type->moi_price, 0).' د.ع' : 'مشمول') : 'غير مشمول' }}
                                    </span>
                                </div>
                            </td>

                            {{-- تبديل حالة الفحص --}}
                            <td class="text-center">
                                @can('manage radiology types')
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" 
                                           id="switchActive{{ $type->id }}" 
                                           {{ $type->is_active ? 'checked' : '' }}
                                           onchange="quickToggleType({{ $type->id }}, 'is_active', this)">
                                </div>
                                @else
                                <span class="{{ $type->is_active ? 'status-badge-active' : 'status-badge-inactive' }}">
                                    {{ $type->is_active ? 'مفعل' : 'معطل' }}
                                </span>
                                @endcan
                            </td>

                            {{-- أزرار الإجراءات --}}
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center align-items-center">
                                    <a href="{{ route('radiology.types.show', $type) }}" class="btn btn-sm btn-outline-info px-2" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @can('manage radiology types')
                                    <!-- Edit Button -->
                                    <button type="button" class="btn btn-sm btn-outline-warning text-dark px-2" title="تعديل تفاصيل وأسعار الفحص" data-bs-toggle="modal" data-bs-target="#editRadiologyTypeModal{{ $type->id }}">
                                        <i class="fas fa-edit text-warning"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    @if($type->requests_count == 0)
                                    <form action="{{ route('radiology.types.destroy', $type) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف فحص {{ $type->name }}؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger px-2" title="حذف الفحص">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                    @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-2 opacity-50" title="لا يمكن الحذف لارتباطه بـ {{ $type->requests_count }} طلب سابق" disabled>
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($types->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $types->links() }}
                </div>
            @endif

            @else
            <div class="text-center py-5">
                <div class="rounded-circle bg-light d-inline-flex p-4 mb-3 text-muted">
                    <i class="fas fa-x-ray fa-3x"></i>
                </div>
                <h5 class="text-muted fw-bold">لا توجد فحوصات أشعة مطابقة</h5>
                <p class="text-muted small">يمكنك إضافة فحوصات جديدة باستخدام زر الإضافة بالأعلى أو إعادة ضبط الفلاتر</p>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    const quickToggleUrl = "{{ url('radiology/types') }}";
    const csrfToken = "{{ csrf_token() }}";

    function showToast(message, isSuccess = true) {
        const toast = document.getElementById('ajaxToast');
        const icon = document.getElementById('ajaxToastIcon');
        const msg = document.getElementById('ajaxToastMsg');

        toast.className = 'alert border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center ' + (isSuccess ? 'alert-success' : 'alert-danger');
        icon.className = 'fas fa-lg me-3 ' + (isSuccess ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-danger');
        msg.textContent = message;

        toast.style.display = 'flex';
        clearTimeout(window.toastTimeout);
        window.toastTimeout = setTimeout(() => {
            toast.style.display = 'none';
        }, 4000);
    }

    // التبديل الفوري بنقرة واحدة عبر AJAX
    function quickToggleType(typeId, field, inputElem) {
        const isChecked = inputElem.checked;
        fetch(`${quickToggleUrl}/${typeId}/quick-toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                field: field,
                value: isChecked
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, true);
                if (field === 'is_hi_active') {
                    const badge = document.getElementById(`badgeHi${typeId}`);
                    if (badge) {
                        badge.className = `badge ${isChecked ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle'} px-2 py-1`;
                        badge.textContent = isChecked ? 'مشمول' : 'غير مشمول (كاش)';
                    }
                } else if (field === 'is_moi_active') {
                    const badge = document.getElementById(`badgeMoi${typeId}`);
                    if (badge) {
                        badge.className = `badge ${isChecked ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle'} px-2 py-1`;
                        badge.textContent = isChecked ? 'مشمول' : 'غير مشمول';
                    }
                }
            } else {
                inputElem.checked = !isChecked;
                showToast(data.message || 'حدث خطأ أثناء الحفظ', false);
            }
        })
        .catch(err => {
            inputElem.checked = !isChecked;
            showToast('فشل الاتصال بالخادم، يرجى المحاولة ثانية', false);
        });
    }

    // إدارة التحديد الجماعي
    function handleMasterCheck(masterElem) {
        const checkboxes = document.querySelectorAll('.service-check');
        checkboxes.forEach(cb => {
            cb.checked = masterElem.checked;
            const row = document.getElementById(`serviceRow${cb.value}`);
            if (row) {
                if (masterElem.checked) row.classList.add('table-active');
                else row.classList.remove('table-active');
            }
        });
        updateBulkBar();
    }

    function toggleSelectAll() {
        const master = document.getElementById('checkAll');
        if (master) {
            master.checked = !master.checked;
            handleMasterCheck(master);
        }
    }

    function handleRowCheck() {
        const checkboxes = document.querySelectorAll('.service-check');
        let allChecked = true;
        let anyChecked = false;

        checkboxes.forEach(cb => {
            const row = document.getElementById(`serviceRow${cb.value}`);
            if (cb.checked) {
                anyChecked = true;
                if (row) row.classList.add('table-active');
            } else {
                allChecked = false;
                if (row) row.classList.remove('table-active');
            }
        });

        const master = document.getElementById('checkAll');
        if (master) {
            master.checked = (checkboxes.length > 0 && allChecked);
        }

        updateBulkBar();
    }

    function updateBulkBar() {
        const checked = document.querySelectorAll('.service-check:checked');
        const bar = document.getElementById('bulkActionsBar');
        const badge = document.getElementById('selectedCountBadge');
        const container = document.getElementById('bulkHiddenInputs');

        if (checked.length > 0) {
            bar.style.display = 'block';
            badge.textContent = `${checked.length} فحوصات مختارة`;
            
            container.innerHTML = '';
            checked.forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'service_ids[]';
                input.value = cb.value;
                container.appendChild(input);
            });
        } else {
            bar.style.display = 'none';
            container.innerHTML = '';
        }
    }

    function submitBulkAction(action) {
        const input = document.getElementById('bulkActionInput');
        input.value = action;
        document.getElementById('bulkActionForm').submit();
    }
</script>
@endpush
@endsection

@push('modals')
<!-- Create Radiology Type Modal -->
@can('manage radiology types')
<div class="modal fade" id="createRadiologyTypeModal" tabindex="-1" aria-labelledby="createRadiologyTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('radiology.types.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white border-0 py-3 px-4 rounded-top-4">
                    <h5 class="modal-title fw-bold" id="createRadiologyTypeModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>إضافة فحص أشعة / سونار جديد
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">اسم الفحص <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-3" placeholder="مثال: سونار بطن وحوض، أشعة صدر X-Ray..." required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">القسم والتصنيف <span class="text-danger">*</span></label>
                            <select name="subcategory" class="form-select rounded-3" required>
                                <option value="أشعة">أشعة سينية (X-Ray)</option>
                                <option value="سونار">سونار ودوبلر (Ultrasound)</option>
                                <option value="مفراس">مفراس حلزوني (CT Scan)</option>
                                <option value="الرنين">رنين مغناطيسي (MRI)</option>
                                <option value="إيكو">إيكو قلب (Echo)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">كود الفحص (اختياري)</label>
                            <input type="text" name="code" class="form-control rounded-3" placeholder="مثال: US-ABD-01 (سيتم توليده تلقائياً إن ترك فارغاً)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">سعر الكاش العادي (د.ع) <span class="text-danger">*</span></label>
                            <input type="number" step="500" name="base_price" class="form-control rounded-3" placeholder="مثال: 25000" required>
                        </div>

                        <!-- قسم تسعير الضمان الصحي وضمان الداخلية -->
                        <div class="col-12">
                            <div class="card border-primary border-opacity-25 bg-light bg-opacity-50 rounded-3">
                                <div class="card-header bg-primary bg-opacity-10 py-2">
                                    <h6 class="mb-0 text-primary fw-bold small">
                                        <i class="fas fa-shield-alt me-1"></i> تسعير وتغطية الضمان الصحي وضمان الداخلية
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-3">
                                        {{-- ضمان الداخلية --}}
                                        <div class="col-md-6 border-start">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold small mb-0"><i class="fas fa-id-badge text-primary me-1"></i>ضمان الداخلية (MOI)</label>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" name="is_moi_active" value="1" id="createIsMoiActive" checked>
                                                    <label class="form-check-label small" for="createIsMoiActive">مشمول</label>
                                                </div>
                                            </div>
                                            <input type="number" step="500" name="moi_price" class="form-control form-control-sm rounded-2" placeholder="سعر الداخلية (اتركه فارغاً لاعتماد الكاش)">
                                        </div>
                                        {{-- هيئة الضمان --}}
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold small mb-0"><i class="fas fa-shield-alt text-success me-1"></i>هيئة الضمان (HI)</label>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" name="is_hi_active" value="1" id="createIsHiActive" checked>
                                                    <label class="form-check-label small" for="createIsHiActive">مشمول</label>
                                                </div>
                                            </div>
                                            <input type="number" step="500" name="hi_price" class="form-control form-control-sm rounded-2" placeholder="سعر هيئة الضمان (اتركه فارغاً لاعتماد الكاش)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">المدة التقديرية (بالدقائق)</label>
                            <input type="number" name="estimated_duration" class="form-control rounded-3" value="15" min="1" max="480">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="requires_contrast" value="1" id="createContrast">
                                <label class="form-check-label fw-semibold" for="createContrast">يتطلب مادة تباين (صبغة)</label>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createIsActiveSwitch" checked>
                                <label class="form-check-label fw-semibold" for="createIsActiveSwitch">تفعيل الفحص فوراً</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">إضافة الفحص</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modals -->
@foreach($types as $type)
<div class="modal fade" id="editRadiologyTypeModal{{ $type->id }}" tabindex="-1" aria-labelledby="editRadiologyTypeModalLabel{{ $type->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('radiology.types.update', $type) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-dark border-0 py-3 px-4 rounded-top-4">
                    <h5 class="modal-title fw-bold" id="editRadiologyTypeModalLabel{{ $type->id }}">
                        <i class="fas fa-edit me-2"></i>تعديل فحص الأشعة / السونار
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">اسم الفحص <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-3" value="{{ $type->name }}" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">القسم والتصنيف <span class="text-danger">*</span></label>
                            <select name="subcategory" class="form-select rounded-3" required>
                                <option value="أشعة" {{ $type->subcategory === 'أشعة' ? 'selected' : '' }}>أشعة سينية (X-Ray)</option>
                                <option value="سونار" {{ $type->subcategory === 'سونار' ? 'selected' : '' }}>سونار ودوبلر (Ultrasound)</option>
                                <option value="مفراس" {{ $type->subcategory === 'مفراس' ? 'selected' : '' }}>مفراس حلزوني (CT Scan)</option>
                                <option value="الرنين" {{ $type->subcategory === 'الرنين' ? 'selected' : '' }}>رنين مغناطيسي (MRI)</option>
                                <option value="إيكو" {{ $type->subcategory === 'إيكو' ? 'selected' : '' }}>إيكو قلب (Echo)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">كود الفحص</label>
                            <input type="text" name="code" class="form-control rounded-3" value="{{ $type->code }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">سعر الكاش العادي (د.ع) <span class="text-danger">*</span></label>
                            <input type="number" step="500" name="base_price" class="form-control rounded-3" value="{{ (int)$type->base_price }}" required>
                        </div>

                        <!-- قسم تسعير الضمان الصحي وضمان الداخلية -->
                        <div class="col-12">
                            <div class="card border-primary border-opacity-25 bg-light bg-opacity-50 rounded-3">
                                <div class="card-header bg-primary bg-opacity-10 py-2">
                                    <h6 class="mb-0 text-primary fw-bold small">
                                        <i class="fas fa-shield-alt me-1"></i> تسعير وتغطية الضمان الصحي وضمان الداخلية
                                    </h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-3">
                                        {{-- ضمان الداخلية --}}
                                        <div class="col-md-6 border-start">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold small mb-0"><i class="fas fa-id-badge text-primary me-1"></i>ضمان الداخلية (MOI)</label>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" name="is_moi_active" value="1" id="editIsMoiActive{{ $type->id }}" {{ $type->is_moi_active ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="editIsMoiActive{{ $type->id }}">مشمول</label>
                                                </div>
                                            </div>
                                            <input type="number" step="500" name="moi_price" class="form-control form-control-sm rounded-2" value="{{ $type->moi_price ? (int)$type->moi_price : '' }}" placeholder="سعر الداخلية (اتركه فارغاً لاعتماد الكاش)">
                                        </div>
                                        {{-- هيئة الضمان --}}
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold small mb-0"><i class="fas fa-shield-alt text-success me-1"></i>هيئة الضمان (HI)</label>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" name="is_hi_active" value="1" id="editIsHiActive{{ $type->id }}" {{ $type->is_hi_active ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="editIsHiActive{{ $type->id }}">مشمول</label>
                                                </div>
                                            </div>
                                            <input type="number" step="500" name="hi_price" class="form-control form-control-sm rounded-2" value="{{ $type->hi_price ? (int)$type->hi_price : '' }}" placeholder="سعر هيئة الضمان (اتركه فارغاً لاعتماد الكاش)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">المدة التقديرية (بالدقائق)</label>
                            <input type="number" name="estimated_duration" class="form-control rounded-3" value="{{ $type->estimated_duration ?: 15 }}" min="1" max="480">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="requires_contrast" value="1" id="editContrast{{ $type->id }}" {{ $type->requires_contrast ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="editContrast{{ $type->id }}">يتطلب مادة تباين (صبغة)</label>
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveSwitch{{ $type->id }}" {{ $type->is_active ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="isActiveSwitch{{ $type->id }}">تفعيل الفحص</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endcan
@endpush