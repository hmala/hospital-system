<!-- resources/views/radiology/types/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <h3 class="fw-bold text-dark mb-1">
                <i class="fas fa-x-ray text-primary me-2"></i>
                دليل وتسعير أنواع الأشعة والتصوير
            </h3>
            <p class="text-muted small mb-0">إدارة فحوصات الأشعة، السونار، المفراس، الرنين، والإيكو مع تسعير الضمان الصحي ووزارة الداخلية.</p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            @can('manage radiology types')
            <a href="{{ route('radiology.types.create') }}" class="btn btn-primary px-4 rounded-pill shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> إضافة نوع فحص جديد
            </a>
            @endcan
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-xs border-0 rounded-3 mb-4" role="alert">
            <i class="fas fa-check-circle me-2 fs-5 align-middle"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-xs border-0 rounded-3 mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2 fs-5 align-middle"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-light bg-opacity-75 py-3 d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-sliders-h text-primary"></i>
                <h6 class="mb-0 fw-bold text-dark">خيارات البحث والتصفية</h6>
            </div>
            <i class="fas fa-chevron-down text-muted"></i>
        </div>
        <div class="collapse {{ request()->hasAny(['search', 'is_active', 'insurance_filter', 'subcategory', 'main_category', 'min_price', 'max_price']) ? 'show' : '' }}" id="filterCollapse">
            <div class="card-body p-4">
                <form method="GET" action="{{ route('radiology.types.index') }}" id="filterForm">
                    <div class="row g-3">
                        <!-- Search Box -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-secondary">البحث بالاسم أو الرمز</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0" placeholder="اسم الفحص أو الكود..." value="{{ request('search') }}">
                            </div>
                        </div>

                        <!-- Subcategory -->
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary">قسم الفحص</label>
                            <select name="subcategory" class="form-select">
                                <option value="">كافة الأقسام</option>
                                @foreach(['أشعة', 'سونار', 'مفراس', 'الرنين', 'إيكو'] as $sub)
                                    <option value="{{ $sub }}" {{ request('subcategory') === $sub ? 'selected' : '' }}>{{ $sub }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Insurance Filter -->
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-secondary">تغطية الضمان والتأمين</label>
                            <select name="insurance_filter" class="form-select">
                                <option value="">الكل (مشمول وغير مشمول)</option>
                                <option value="hi_active" {{ request('insurance_filter') === 'hi_active' ? 'selected' : '' }}>🟢 مشمول بالضمان الصحي الوطني (HI)</option>
                                <option value="moi_active" {{ request('insurance_filter') === 'moi_active' ? 'selected' : '' }}>🔵 مشمول بضمان وزارة الداخلية (MOI)</option>
                                <option value="both_active" {{ request('insurance_filter') === 'both_active' ? 'selected' : '' }}>⭐ مشمول بالضمانين معاً</option>
                                <option value="none_active" {{ request('insurance_filter') === 'none_active' ? 'selected' : '' }}>⚪ غير مشمول بالضمان (نقدي فقط)</option>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary">الحالة التشغيلية</label>
                            <select name="is_active" class="form-select">
                                <option value="">الكل</option>
                                <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>نشط ومتاح</option>
                                <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>معطل مؤقتاً</option>
                            </select>
                        </div>

                        <!-- Sort By -->
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary">ترتيب النتائج</label>
                            <select name="sort_by" class="form-select">
                                <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>الاسم أبجدياً</option>
                                <option value="base_price" {{ request('sort_by') === 'base_price' ? 'selected' : '' }}>السعر الأساسي</option>
                                <option value="subcategory" {{ request('sort_by') === 'subcategory' ? 'selected' : '' }}>القسم</option>
                                <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>تاريخ الإضافة</option>
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary">نطاق السعر النقدي (د.ع)</label>
                            <div class="input-group">
                                <input type="number" name="min_price" class="form-control" placeholder="الحد الأدنى" value="{{ request('min_price') }}" min="0">
                                <span class="input-group-text bg-light">-</span>
                                <input type="number" name="max_price" class="form-control" placeholder="الحد الأعلى" value="{{ request('max_price') }}" min="0">
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="col-md-8 d-flex align-items-end justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary px-4 rounded-pill">
                                <i class="fas fa-filter me-1"></i> تطبيق الفلتر
                            </button>
                            <a href="{{ route('radiology.types.index') }}" class="btn btn-outline-secondary px-3 rounded-pill">
                                <i class="fas fa-undo me-1"></i> إعادة تعيين
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Main Listing Table Card with Unified Top Actions Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
        <!-- Unified Top Header Bar -->
        <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <h5 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-list text-primary me-2"></i> قائمة الفحوصات والتسعير
                </h5>
                <span class="badge bg-primary rounded-pill px-3 py-2">{{ $types->total() }} فحص</span>
                <span id="topSelectedBadge" class="badge bg-dark rounded-pill px-3 py-2 d-none">
                    <i class="fas fa-check-double text-warning me-1"></i> تم تحديد <b id="topSelectedCount">0</b> فحص
                </span>
            </div>

            <!-- Top Bulk & Quick Action Buttons -->
            @can('manage radiology types')
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- Group of bulk buttons (shown when rows are selected) -->
                <div id="topBulkActionsGroup" class="d-none d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-xs" onclick="submitBulkStatus('active')" title="تفعيل الفحوصات المحددة">
                        <i class="fas fa-check-circle me-1"></i> تفعيل المحدد
                    </button>
                    <button type="button" class="btn btn-sm btn-warning text-dark rounded-pill px-3 fw-bold shadow-xs" onclick="submitBulkStatus('inactive')" title="تعطيل الفحوصات المحددة">
                        <i class="fas fa-ban me-1"></i> تعطيل المحدد
                    </button>
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold shadow-xs" onclick="openBulkDeleteModal()" title="حذف الفحوصات المحددة">
                        <i class="fas fa-trash-alt me-1"></i> حذف المحدد
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2" onclick="clearAllSelections()" title="إلغاء التحديد">
                        <i class="fas fa-times me-1"></i> إلغاء
                    </button>
                    <div class="vr mx-1"></div>
                </div>

                <a href="{{ route('radiology.types.create') }}" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-xs">
                    <i class="fas fa-plus me-1"></i> إضافة فحص جديد
                </a>
            </div>
            @endcan
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="radiologyTypesTable">
                    <thead class="table-light text-secondary text-nowrap" style="font-size: 0.85rem;">
                        <tr>
                            @can('manage radiology types')
                            <th style="width: 40px;" class="text-center">
                                <input type="checkbox" class="form-check-input" id="selectAllCheckbox" title="تحديد الكل في هذه الصفحة">
                            </th>
                            @endcan
                            <th>القسم والتصنيف</th>
                            <th>اسم الفحص والكود</th>
                            <th class="text-center">السعر النقدي (كاش)</th>
                            <th class="text-center">سعر هيئة الضمان (HI)</th>
                            <th class="text-center">سعر الداخلية (MOI)</th>
                            <th class="text-center">المواصفات</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-center" style="width: 140px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($types as $type)
                        <tr id="row-{{ $type->id }}" class="{{ !$type->is_active ? 'bg-light text-muted' : '' }}">
                            @can('manage radiology types')
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input row-checkbox" value="{{ $type->id }}" data-requests-count="{{ $type->requests_count }}" data-name="{{ $type->name }}">
                            </td>
                            @endcan
                            
                            <!-- Subcategory -->
                            <td>
                                @php
                                    $badgeColor = match($type->subcategory) {
                                        'أشعة' => 'bg-primary',
                                        'سونار' => 'bg-info text-dark',
                                        'مفراس' => 'bg-warning text-dark',
                                        'الرنين' => 'bg-purple text-white',
                                        'إيكو' => 'bg-success',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $badgeColor }} rounded-pill px-2 py-1 small fw-bold">
                                    {{ $type->subcategory ?: 'عام' }}
                                </span>
                                @if($type->main_category && $type->main_category !== $type->subcategory)
                                    <div class="small text-muted mt-1">{{ $type->main_category }}</div>
                                @endif
                            </td>

                            <!-- Name & Code -->
                            <td>
                                <div class="fw-bold text-dark">{{ $type->name }}</div>
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <code class="text-secondary small bg-light px-1 rounded">{{ $type->code }}</code>
                                    @if($type->description)
                                        <small class="text-muted text-truncate" style="max-width: 220px;" title="{{ $type->description }}">
                                            {{ $type->description }}
                                        </small>
                                    @endif
                                </div>
                            </td>

                            <!-- Base Price -->
                            <td class="text-center">
                                <strong class="text-dark fs-6">{{ number_format($type->base_price) }}</strong>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">د.ع</small>
                            </td>

                            <!-- Health Insurance (HI) Price -->
                            <td class="text-center">
                                @if($type->is_hi_active)
                                    <div class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-2">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <strong class="fs-6">{{ number_format($type->hi_price ?: $type->base_price) }}</strong> د.ع
                                    </div>
                                    <small class="d-block text-success" style="font-size: 0.7rem;">مشمول بالضمان</small>
                                @else
                                    <span class="badge bg-light text-muted border px-2 py-1 rounded-pill">
                                        <i class="fas fa-times-circle me-1 text-danger"></i> غير مشمول
                                    </span>
                                @endif
                            </td>

                            <!-- Ministry of Interior (MOI) Price -->
                            <td class="text-center">
                                @if($type->is_moi_active)
                                    <div class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-2">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        <strong class="fs-6">{{ number_format($type->moi_price ?: $type->base_price) }}</strong> د.ع
                                    </div>
                                    <small class="d-block text-primary" style="font-size: 0.7rem;">مشمول بالداخلية</small>
                                @else
                                    <span class="badge bg-light text-muted border px-2 py-1 rounded-pill">
                                        <i class="fas fa-times-circle me-1 text-danger"></i> غير مشمول
                                    </span>
                                @endif
                            </td>

                            <!-- Specifications -->
                            <td class="text-center">
                                <div class="d-flex flex-column gap-1 align-items-center">
                                    <span class="small text-muted"><i class="fas fa-clock text-secondary me-1"></i> {{ $type->estimated_duration }} د</span>
                                    @if($type->requires_contrast)
                                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning px-2 py-0" style="font-size: 0.7rem;">صبغة (Contrast)</span>
                                    @endif
                                    @if($type->requires_preparation)
                                        <span class="badge bg-info bg-opacity-25 text-dark border border-info px-2 py-0" style="font-size: 0.7rem;">يتطلب تحضير</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status Toggle -->
                            <td class="text-center">
                                @can('manage radiology types')
                                <form action="{{ route('radiology.types.toggle', $type) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $type->is_active ? 'btn-outline-success' : 'btn-outline-danger' }} rounded-pill px-2 py-1" style="font-size: 0.78rem;" title="{{ $type->is_active ? 'انقر للتعطيل' : 'انقر للتفعيل' }}">
                                        <i class="fas {{ $type->is_active ? 'fa-check-circle' : 'fa-ban' }} me-1"></i>
                                        {{ $type->is_active ? 'نشط' : 'معطل' }}
                                    </button>
                                </form>
                                @else
                                <span class="badge {{ $type->is_active ? 'bg-success' : 'bg-danger' }}">
                                    {{ $type->is_active ? 'نشط' : 'معطل' }}
                                </span>
                                @endcan
                            </td>

                            <!-- Actions -->
                            <td class="text-center">
                                <div class="btn-group btn-group-sm rounded-pill shadow-xs">
                                    <a href="{{ route('radiology.types.show', $type) }}" class="btn btn-light text-info" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @can('manage radiology types')
                                    <a href="{{ route('radiology.types.edit', $type) }}" class="btn btn-light text-warning" title="تعديل الفحص والتسعير">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($type->requests_count == 0)
                                    <form action="{{ route('radiology.types.destroy', $type) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-light text-danger" title="حذف الفحص" onclick="return confirm('هل أنت متأكد من حذف هذا الفحص نهائياً؟')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                    @else
                                    <button type="button" class="btn btn-light text-muted" title="لا يمكن الحذف لارتباطه بـ {{ $type->requests_count }} طلب أشعة سابق" disabled>
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-x-ray fa-3x mb-3 text-secondary opacity-50"></i>
                                <div class="fw-bold fs-6">لا توجد أنواع أشعة مطابقة للبحث</div>
                                <p class="small text-muted">يمكنك تعديل خيارات الفلترة أو إضافة نوع فحص جديد.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($types->hasPages())
        <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
            {{ $types->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Bulk Delete Confirmation Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle me-2"></i> تأكيد الحذف الجماعي</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="mb-3 text-danger">
                    <i class="fas fa-trash-alt fa-3x"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2">هل أنت متأكد من حذف الفحوصات المحددة؟</h5>
                <p class="text-muted small mb-3">
                    أنت على وشك حذف <strong class="text-danger" id="modalDeleteCount">0</strong> نوع أشعة.
                </p>
                <div class="alert alert-warning text-start small mb-0">
                    <i class="fas fa-shield-alt me-1"></i> <strong>حماية الترابط المالي والملفات:</strong> الفحوصات المرتبطة بطلبات أشعة أو فواتير سابقة لن تُحذف وسيتم حمايتها تلقائياً.
                </div>
            </div>
            <div class="modal-footer bg-light p-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-bold" onclick="executeBulkDelete()">
                    <i class="fas fa-trash-alt me-1"></i> تأكيد الحذف الآن
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden Forms for Bulk Actions -->
<form id="bulkDeleteForm" action="{{ route('radiology.types.bulk-delete') }}" method="POST" class="d-none">
    @csrf
    <div id="bulkDeleteInputs"></div>
</form>

<form id="bulkStatusForm" action="{{ route('radiology.types.bulk-toggle-status') }}" method="POST" class="d-none">
    @csrf
    <input type="hidden" name="status" id="bulkStatusValue" value="active">
    <div id="bulkStatusInputs"></div>
</form>

<style>
.bg-purple {
    background-color: #7c3aed !important;
}
.shadow-xs {
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
}
#radiologyTypesTable tbody tr:hover {
    background-color: #f8fafc;
}
.row-checkbox:checked {
    background-color: #3b82f6;
    border-color: #3b82f6;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    const topBulkActionsGroup = document.getElementById('topBulkActionsGroup');
    const topSelectedBadge = document.getElementById('topSelectedBadge');
    const topSelectedCount = document.getElementById('topSelectedCount');

    function updateBulkToolbar() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        const count = checkedBoxes.length;

        if (count > 0) {
            if (topBulkActionsGroup) topBulkActionsGroup.classList.remove('d-none');
            if (topSelectedBadge) topSelectedBadge.classList.remove('d-none');
            if (topSelectedCount) topSelectedCount.textContent = count;
        } else {
            if (topBulkActionsGroup) topBulkActionsGroup.classList.add('d-none');
            if (topSelectedBadge) topSelectedBadge.classList.add('d-none');
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = (count > 0 && count === rowCheckboxes.length);
            selectAllCheckbox.indeterminate = (count > 0 && count < rowCheckboxes.length);
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
            updateBulkToolbar();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkToolbar);
    });

    window.clearAllSelections = function() {
        rowCheckboxes.forEach(cb => cb.checked = false);
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
        updateBulkToolbar();
    };

    window.openBulkDeleteModal = function() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        if (checkedBoxes.length === 0) return;
        document.getElementById('modalDeleteCount').textContent = checkedBoxes.length;
        const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
        modal.show();
    };

    window.executeBulkDelete = function() {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        const container = document.getElementById('bulkDeleteInputs');
        container.innerHTML = '';
        checkedBoxes.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        document.getElementById('bulkDeleteForm').submit();
    };

    window.submitBulkStatus = function(status) {
        const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
        if (checkedBoxes.length === 0) return;

        const actionText = (status === 'active') ? 'تفعيل' : 'تعطيل';
        if (!confirm(`هل أنت متأكد من ${actionText} عدد (${checkedBoxes.length}) فحص؟`)) {
            return;
        }

        const container = document.getElementById('bulkStatusInputs');
        container.innerHTML = '';
        document.getElementById('bulkStatusValue').value = status;
        checkedBoxes.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        document.getElementById('bulkStatusForm').submit();
    };
});
</script>
@endsection