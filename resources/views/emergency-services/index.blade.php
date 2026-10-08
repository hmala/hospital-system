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
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-radius: 20px;">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-hand-holding-medical me-3"></i>إدارة خدمات الطوارئ وضمان الصحة
                            </h2>
                            <p class="mb-0 opacity-75">التحكم الفوري بأسعار خدمات الطوارئ وشموليتها بالضمان الصحي (HI) أو احتسابها كاش عادي مع التعديل الجماعي السريع</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light text-danger fw-bold px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                                <i class="fas fa-plus me-2"></i>إضافة خدمة جديدة
                            </button>
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
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3 text-danger">
                        <i class="fas fa-list-ul fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">إجمالي الخدمات</div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                        <i class="fas fa-heartbeat fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">مشمولة بالضمان (HI)</div>
                        <div class="fs-4 fw-bold text-success">{{ $stats['hi_active'] }}</div>
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
                        <div class="fs-4 fw-bold text-warning">{{ $stats['hi_inactive'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">الخدمات المفعلة</div>
                        <div class="fs-4 fw-bold text-primary">{{ $stats['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center">
            <i class="fas fa-check-circle fa-lg me-3"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-lg me-3"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- Toast Notification for Ajax -->
    <div id="ajaxToast" class="alert alert-info border-0 shadow-sm rounded-3 mb-4 align-items-center" style="display: none;">
        <i class="fas fa-info-circle fa-lg me-3" id="ajaxToastIcon"></i>
        <div id="ajaxToastMsg" class="fw-bold"></div>
    </div>

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('emergency-services.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="بحث باسم الخدمة أو التصنيف..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select bg-light border-0">
                        <option value="">كل التصنيفات</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="insurance_filter" class="form-select bg-light border-0 fw-semibold text-primary">
                        <option value="">🛡️ كل تغطيات الضمان</option>
                        <option value="hi_active" {{ request('insurance_filter') === 'hi_active' ? 'selected' : '' }}>💚 مشمولة بالضمان الصحي (HI)</option>
                        <option value="hi_inactive" {{ request('insurance_filter') === 'hi_inactive' ? 'selected' : '' }}>🚫 غير مشمولة بالضمان (كاش فقط)</option>
                        <option value="moi_active" {{ request('insurance_filter') === 'moi_active' ? 'selected' : '' }}>👮 مشمولة بضمان الداخلية (MOI)</option>
                        <option value="moi_inactive" {{ request('insurance_filter') === 'moi_inactive' ? 'selected' : '' }}>🚫 غير مشمولة بضمان الداخلية</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select bg-light border-0">
                        <option value="">كل الحالات</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>مفعلة</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>معطلة</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold" title="فلترة">
                        <i class="fas fa-filter"></i>
                    </button>
                    @if(request()->hasAny(['search', 'category', 'status', 'insurance_filter']))
                        <a href="{{ route('emergency-services.index') }}" class="btn btn-outline-secondary rounded-pill" title="إعادة تعيين">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Category & Global Switch Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-2 text-danger">
                        <i class="fas fa-bolt fa-lg"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark fs-6">التحكم السريع الشامل (تفعيل / إطفاء الكل لكل صنف)</div>
                        <div class="text-muted small">تطبيق تفعيل أو استبعاد الضمان بنقرة واحدة لصنف محدد أو لكافة خدمات المستشفى</div>
                    </div>
                </div>

                <!-- Global Toggle Action Form -->
                <form action="{{ route('emergency-services.global-toggle') }}" method="POST" class="d-flex flex-wrap align-items-center gap-2 m-0" onsubmit="return confirm('هل أنت متأكد من تنفيذ هذا التعديل الشامل؟');">
                    @csrf
                    <!-- Category Selector -->
                    <select name="category" class="form-select form-select-sm rounded-pill border bg-light" style="width: auto; min-width: 170px;">
                        <option value="">⚡ كافة الأصناف (الكل)</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>صنف: {{ $cat }}</option>
                        @endforeach
                    </select>

                    <!-- HI Dropdown -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-success dropdown-toggle rounded-pill px-3 fw-bold" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-heartbeat me-1"></i> هيئة الضمان (HI)
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                            <li>
                                <button type="submit" name="field" value="is_hi_active" class="dropdown-item text-success fw-bold py-2" onclick="this.form.querySelector('input[name=state]').value='1'">
                                    <i class="fas fa-check-circle me-2"></i> تفعيل الضمان (HI) للصنف المحدد
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
                                    <i class="fas fa-check-circle me-2"></i> تفعيل ضمان الداخلية للصنف
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button type="submit" name="field" value="is_moi_active" class="dropdown-item text-danger fw-bold py-2" onclick="this.form.querySelector('input[name=state]').value='0'">
                                    <i class="fas fa-times-circle me-2"></i> إطفاء ضمان الداخلية للصنف
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Status Dropdown -->
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle rounded-pill px-3 fw-bold" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-toggle-on me-1"></i> حالة الخدمات
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                            <li>
                                <button type="submit" name="field" value="is_active" class="dropdown-item text-primary fw-bold py-2" onclick="this.form.querySelector('input[name=state]').value='1'">
                                    <i class="fas fa-play me-2"></i> تفعيل كل الخدمات للصنف
                                </button>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <button type="submit" name="field" value="is_active" class="dropdown-item text-secondary fw-bold py-2" onclick="this.form.querySelector('input[name=state]').value='0'">
                                    <i class="fas fa-pause me-2"></i> تعطيل كل الخدمات للصنف
                                </button>
                            </li>
                        </ul>
                    </div>

                    <input type="hidden" name="state" value="1">
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Actions Bar (Shown when items are checked) -->
    <div id="bulkActionsBar" class="card border-0 shadow-lg bulk-toolbar text-white mb-4 p-3" style="display: none;">
        <form id="bulkActionForm" action="{{ route('emergency-services.bulk-action') }}" method="POST">
            @csrf
            <input type="hidden" name="action" id="bulkActionInput" value="">
            <div id="bulkHiddenInputs"></div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill fw-bold" id="selectedCountBadge">0 خدمات مختارة</span>
                    <span class="text-white-50 small">اختر الإجراء الجماعي لتطبيقه على الخدمات المحددة:</span>
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
                        <i class="fas fa-check me-1"></i> تفعيل الخدمة
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill fw-bold px-3 shadow-sm" onclick="submitBulkAction('deactivate')">
                        <i class="fas fa-pause me-1"></i> تعطيل الخدمة
                    </button>

                    <!-- Delete Action -->
                    <button type="button" class="btn btn-sm btn-outline-danger text-white rounded-pill fw-bold px-3 shadow-sm" onclick="if(confirm('هل أنت متأكد من حذف الخدمات المحددة غير المستخدمة؟')) submitBulkAction('delete');">
                        <i class="fas fa-trash-alt me-1"></i> حذف المحددة
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Services Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-list me-2 text-danger"></i>قائمة خدمات الطوارئ
                </h5>
                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold">
                    {{ $services->total() }} خدمة
                </span>
                @if(request('category'))
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-3 py-2 rounded-pill fw-bold">
                        صنف: {{ request('category') }}
                    </span>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="toggleSelectAll()">
                    <i class="fas fa-check-double me-1"></i> <span id="selectAllBtnText">تحديد كل الصفحة</span>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            @if($services->count() > 0)
            <div class="table-responsive w-100 m-0">
                <table class="table table-hover table-services align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="text-center">
                                <input type="checkbox" class="form-check-input" id="checkAll" onchange="handleMasterCheck(this)">
                            </th>
                            <th style="width: 50px;">#</th>
                            <th>اسم الخدمة</th>
                            <th>التصنيف</th>
                            <th>سعر الكاش (د.ع)</th>
                            <th class="text-center" style="width: 190px;">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <i class="fas fa-heartbeat text-success"></i>
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
                        @foreach($services as $service)
                        <tr id="serviceRow{{ $service->id }}">
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input service-check" value="{{ $service->id }}" onchange="handleRowCheck()">
                            </td>
                            <td class="text-muted fw-bold">#{{ $service->id }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $service->name }}</div>
                            </td>
                            <td>
                                @if($service->category)
                                    <span class="badge bg-light text-secondary border">{{ $service->category }}</span>
                                @else
                                    <span class="text-muted small">عام</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-success">
                                    {{ number_format($service->price, 0) }} د.ع
                                </div>
                            </td>

                            {{-- تبديل هيئة الضمان الصحي HI --}}
                            <td class="text-center">
                                <div class="d-inline-flex flex-column align-items-center">
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" 
                                               id="switchHi{{ $service->id }}" 
                                               {{ $service->is_hi_active ? 'checked' : '' }}
                                               onchange="quickToggleService({{ $service->id }}, 'is_hi_active', this)">
                                    </div>
                                    <span id="badgeHi{{ $service->id }}" class="badge {{ $service->is_hi_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} px-2 py-1" style="font-size: 0.72rem;">
                                        {{ $service->is_hi_active ? ($service->hi_price ? number_format($service->hi_price, 0).' د.ع' : 'مشمول (سعر الكاش)') : 'غير مشمول (كاش)' }}
                                    </span>
                                </div>
                            </td>

                            {{-- تبديل ضمان الداخلية MOI --}}
                            <td class="text-center">
                                <div class="d-inline-flex flex-column align-items-center">
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input" type="checkbox" 
                                               id="switchMoi{{ $service->id }}" 
                                               {{ $service->is_moi_active ? 'checked' : '' }}
                                               onchange="quickToggleService({{ $service->id }}, 'is_moi_active', this)">
                                    </div>
                                    <span id="badgeMoi{{ $service->id }}" class="badge {{ $service->is_moi_active ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} px-2 py-1" style="font-size: 0.72rem;">
                                        {{ $service->is_moi_active ? ($service->moi_price ? number_format($service->moi_price, 0).' د.ع' : 'مشمول') : 'غير مشمول' }}
                                    </span>
                                </div>
                            </td>

                            {{-- تبديل حالة الخدمة --}}
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input" type="checkbox" 
                                           id="switchActive{{ $service->id }}" 
                                           {{ $service->is_active ? 'checked' : '' }}
                                           onchange="quickToggleService({{ $service->id }}, 'is_active', this)">
                                </div>
                            </td>

                            {{-- أزرار الإجراءات --}}
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center align-items-center">
                                    <!-- Edit Button -->
                                    <button type="button" class="btn btn-sm btn-outline-warning text-dark px-2" title="تعديل تفاصيل وأسعار الخدمة" data-bs-toggle="modal" data-bs-target="#editServiceModal{{ $service->id }}">
                                        <i class="fas fa-edit text-warning"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <form action="{{ route('emergency-services.destroy', $service) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف خدمة {{ $service->name }}؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger px-2" title="حذف الخدمة">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($services->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $services->links() }}
                </div>
            @endif

            @else
            <div class="text-center py-5">
                <div class="rounded-circle bg-light d-inline-flex p-4 mb-3 text-muted">
                    <i class="fas fa-hand-holding-medical fa-3x"></i>
                </div>
                <h5 class="text-muted fw-bold">لا توجد خدمات طوارئ مطابقة</h5>
                <p class="text-muted small">يمكنك إضافة خدمات جديدة باستخدام زر الإضافة بالأعلى أو إعادة ضبط الفلاتر</p>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    const quickToggleUrl = "{{ url('emergency-services') }}";
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
    function quickToggleService(serviceId, field, inputElem) {
        const isChecked = inputElem.checked;
        fetch(`${quickToggleUrl}/${serviceId}/quick-toggle`, {
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
                // تحديث الشارة
                if (field === 'is_hi_active') {
                    const badge = document.getElementById(`badgeHi${serviceId}`);
                    if (badge) {
                        badge.className = `badge ${isChecked ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle'} px-2 py-1`;
                        badge.textContent = isChecked ? 'مشمول' : 'غير مشمول (كاش)';
                    }
                } else if (field === 'is_moi_active') {
                    const badge = document.getElementById(`badgeMoi${serviceId}`);
                    if (badge) {
                        badge.className = `badge ${isChecked ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle'} px-2 py-1`;
                        badge.textContent = isChecked ? 'مشمول' : 'غير مشمول';
                    }
                }
            } else {
                inputElem.checked = !isChecked; // إرجاع الحالة عند الفشل
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
        master.checked = !master.checked;
        handleMasterCheck(master);
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
        if (master) master.checked = allChecked && checkboxes.length > 0;

        updateBulkBar();
    }

    function updateBulkBar() {
        const checked = document.querySelectorAll('.service-check:checked');
        const bar = document.getElementById('bulkActionsBar');
        const badge = document.getElementById('selectedCountBadge');
        const container = document.getElementById('bulkHiddenInputs');

        if (checked.length > 0) {
            bar.style.display = 'block';
            badge.textContent = `${checked.length} خدمات مختارة`;
            
            // تعبئة المدخلات المخفية
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
<!-- Create Service Modal -->
<div class="modal fade" id="createServiceModal" tabindex="-1" aria-labelledby="createServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('emergency-services.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white border-0 py-3 px-4 rounded-top-4">
                    <h5 class="modal-title fw-bold" id="createServiceModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>إضافة خدمة طوارئ جديدة
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">اسم الخدمة <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-3" placeholder="مثال: غسل معدة، سحب دم..." required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">التصنيف (اختياري)</label>
                            <input type="text" name="category" class="form-control rounded-3" placeholder="مثال: تمريض، جراحة صغرى، تنفسية...">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">سعر الكاش العادي (د.ع) <span class="text-danger">*</span></label>
                            <input type="number" step="500" name="price" class="form-control rounded-3" placeholder="مثال: 25000" required>
                            <div class="form-text small">السعر الافتراضي للمراجع بدون ضمان</div>
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
                                                <label class="form-label fw-bold small mb-0"><i class="fas fa-heartbeat text-success me-1"></i>هيئة الضمان (HI)</label>
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

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createIsActiveSwitch" checked>
                                <label class="form-check-label fw-semibold" for="createIsActiveSwitch">تفعيل الخدمة فوراً</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">إضافة الخدمة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modals -->
@foreach($services as $service)
<div class="modal fade" id="editServiceModal{{ $service->id }}" tabindex="-1" aria-labelledby="editServiceModalLabel{{ $service->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('emergency-services.update', $service) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-dark border-0 py-3 px-4 rounded-top-4">
                    <h5 class="modal-title fw-bold" id="editServiceModalLabel{{ $service->id }}">
                        <i class="fas fa-edit me-2"></i>تعديل خدمة الطوارئ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">اسم الخدمة <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-3" value="{{ $service->name }}" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">التصنيف (اختياري)</label>
                            <input type="text" name="category" class="form-control rounded-3" value="{{ $service->category }}" placeholder="مثال: تمريض، جراحة صغرى، تنفسية...">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">سعر الكاش العادي (د.ع) <span class="text-danger">*</span></label>
                            <input type="number" step="500" name="price" class="form-control rounded-3" value="{{ (int)$service->price }}" required>
                            <div class="form-text small">السعر الافتراضي للمراجع بدون ضمان</div>
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
                                                    <input class="form-check-input" type="checkbox" name="is_moi_active" value="1" id="editIsMoiActive{{ $service->id }}" {{ $service->is_moi_active ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="editIsMoiActive{{ $service->id }}">مشمول</label>
                                                </div>
                                            </div>
                                            <input type="number" step="500" name="moi_price" class="form-control form-control-sm rounded-2" value="{{ $service->moi_price ? (int)$service->moi_price : '' }}" placeholder="سعر الداخلية (اتركه فارغاً لاعتماد الكاش)">
                                        </div>
                                        {{-- هيئة الضمان --}}
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <label class="form-label fw-bold small mb-0"><i class="fas fa-heartbeat text-success me-1"></i>هيئة الضمان (HI)</label>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" name="is_hi_active" value="1" id="editIsHiActive{{ $service->id }}" {{ $service->is_hi_active ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="editIsHiActive{{ $service->id }}">مشمول</label>
                                                </div>
                                            </div>
                                            <input type="number" step="500" name="hi_price" class="form-control form-control-sm rounded-2" value="{{ $service->hi_price ? (int)$service->hi_price : '' }}" placeholder="سعر هيئة الضمان (اتركه فارغاً لاعتماد الكاش)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveSwitch{{ $service->id }}" {{ $service->is_active ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="isActiveSwitch{{ $service->id }}">تفعيل الخدمة</label>
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
@endpush
