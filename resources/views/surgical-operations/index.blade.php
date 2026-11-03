@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4">
                    <i class="fas fa-procedures fa-2x"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-1">دليل العمليات الجراحية والأقسام</h2>
                    <p class="text-muted mb-0">إدارة وتصنيف أنواع العمليات الجراحية وتحديث بياناتها وأقسامها</p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            @if($canEdit ?? false)
            <a href="{{ route('surgical-operations.create') }}" class="btn btn-primary px-3 py-2 shadow-sm rounded-3 me-2">
                <i class="fas fa-plus-circle me-1"></i>
                إضافة عملية جديدة
            </a>
            <a href="{{ route('surgical-operations.trashed') }}" class="btn btn-outline-warning px-3 py-2 rounded-3">
                <i class="fas fa-trash-alt me-1"></i>
                المحذوفة
            </a>
            @endif
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 d-flex align-items-center" role="alert">
            <i class="fas fa-check-circle fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm rounded-3 d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle fs-5 me-2"></i>
            <div>{{ session('warning') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 d-flex align-items-center" role="alert">
            <i class="fas fa-times-circle fs-5 me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $operationsByCategory = $operations->groupBy('category');
        $totalOps = $operations->count();
        $totalCats = $operationsByCategory->count();
        $activeOps = $operations->where('is_active', true)->count();
        $inactiveOps = $operations->where('is_active', false)->count();
    @endphp

    <!-- KPI Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">إجمالي العمليات</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $totalOps }}</h3>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3">
                        <i class="fas fa-layer-group fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">أقسام / أصناف العمليات</span>
                        <h3 class="fw-bold mb-0 text-info">{{ $totalCats }}</h3>
                    </div>
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-3">
                        <i class="fas fa-folder-tree fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">العمليات المفعلة</span>
                        <h3 class="fw-bold mb-0 text-success">{{ $activeOps }}</h3>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body d-flex align-items-center justify-content-between p-3">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">معطلة / متوقفة</span>
                        <h3 class="fw-bold mb-0 text-secondary">{{ $inactiveOps }}</h3>
                    </div>
                    <div class="p-3 bg-secondary bg-opacity-10 text-secondary rounded-3">
                        <i class="fas fa-pause-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Container Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <!-- Search and Filter Bar -->
        <div class="card-header bg-white border-bottom p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" 
                               id="opSearchInput" 
                               class="form-control bg-light border-start-0 ps-0" 
                               placeholder="بحث سريع باسم العملية أو القسم..." 
                               onkeyup="filterOperations()">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2">
                        <label class="text-muted small text-nowrap">الحالة:</label>
                        <select id="statusFilterSelect" class="form-select form-select-sm bg-light" onchange="filterOperations()">
                            <option value="all">كافة الحالات</option>
                            <option value="active">مفعلة فقط (نشط)</option>
                            <option value="inactive">معطلة فقط</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 text-md-end">
                    <span class="text-muted small" id="filteredCountText">عرض {{ $totalOps }} عملية</span>
                </div>
            </div>
        </div>

        <!-- Category Filter Pills / Tabs -->
        <div class="p-3 bg-light border-bottom">
            <div class="d-flex align-items-center gap-2 flex-wrap" id="categoryTabs">
                <button type="button" 
                        class="btn btn-sm btn-primary rounded-pill px-3 cat-tab-btn active" 
                        data-category="all" 
                        onclick="selectCategoryTab('all', this)">
                    كافة الأقسام
                    <span class="badge bg-white text-primary ms-1 rounded-pill">{{ $totalOps }}</span>
                </button>

                @foreach($operationsByCategory as $catName => $catOps)
                    <div class="btn-group cat-btn-group" role="group">
                        <button type="button" 
                                class="btn btn-sm btn-outline-secondary rounded-start-pill px-3 cat-tab-btn" 
                                data-category="{{ $catName }}" 
                                onclick="selectCategoryTab('{{ addslashes($catName) }}', this)">
                            <i class="fas fa-folder text-primary me-1"></i>
                            {{ $catName }}
                            <span class="badge bg-secondary ms-1 rounded-pill">{{ $catOps->count() }}</span>
                        </button>
                        @if($canEdit ?? false)
                        <button type="button" 
                                class="btn btn-sm btn-outline-secondary rounded-end-pill px-2 rename-cat-btn" 
                                data-cat="{{ $catName }}" 
                                title="تعديل اسم هذا القسم">
                            <i class="fas fa-edit text-primary"></i>
                        </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Operations Structured Table -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-right" id="operationsTable" style="direction: rtl;">
                    <thead class="table-light text-muted small">
                        <tr>
                            <th class="ps-3" style="width: 70px;">#</th>
                            <th style="min-width: 250px;">اسم العملية الجراحية</th>
                            <th style="min-width: 180px;">القسم / الصنف</th>
                            <th class="text-center" style="width: 120px;">الحالة</th>
                            <th class="text-center pe-3" style="width: 150px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($operations as $operation)
                        <tr class="operation-row" 
                            data-name="{{ mb_strtolower($operation->name) }}" 
                            data-category="{{ $operation->category }}" 
                            data-active="{{ $operation->is_active ? 'active' : 'inactive' }}">
                            <td class="ps-3">
                                <span class="badge bg-light text-muted border">#{{ $operation->id }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 me-2">
                                        <i class="fas fa-procedures"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark op-name-text">{{ $operation->name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 rounded-pill">
                                    <i class="fas fa-folder me-1"></i>
                                    {{ $operation->category }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($operation->is_active)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill">
                                        <i class="fas fa-check-circle me-1"></i> نشط
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded-pill">
                                        <i class="fas fa-minus-circle me-1"></i> معطل
                                    </span>
                                @endif
                            </td>
                            <td class="text-center pe-3">
                                @if($canEdit ?? false)
                                <div class="btn-group shadow-sm rounded-3" role="group">
                                    <a href="{{ route('surgical-operations.edit', $operation) }}" 
                                       class="btn btn-sm btn-light text-primary border" 
                                       title="تعديل العملية والصنف">
                                        <i class="fas fa-edit me-1"></i> تعديل
                                    </a>
                                    <form action="{{ route('surgical-operations.destroy', $operation) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('هل أنت متأكد من حذف العملية: {{ $operation->name }}؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn btn-sm btn-light text-danger border border-start-0" 
                                                title="حذف العملية">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                                @else
                                <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr id="emptyTableRow">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 d-block text-secondary opacity-50"></i>
                                <h6>لا توجد أي عمليات جراحية مسجلة حالياً</h6>
                                @if($canEdit ?? false)
                                <a href="{{ route('surgical-operations.create') }}" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i> إضافة عملية جديدة الآن
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                        <tr id="noResultsRow" style="display: none;">
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-3x mb-3 d-block text-secondary opacity-50"></i>
                                <h6>لم يتم العثور على عمليات تطابق معايير البحث والفلترة</h6>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Custom Bulletproof Modal Overlay -->
<div id="renameCategoryModal" style="display: none; position: fixed; inset: 0; z-index: 999999; background: rgba(15, 23, 42, 0.65); align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 15px;">
    <div style="background: #ffffff; border-radius: 16px; width: 100%; max-width: 480px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35); overflow: hidden; animation: customModalIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);">
        <form action="{{ route('surgical-operations.rename-category') }}" method="POST">
            @csrf
            <div class="bg-primary text-white p-3 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-edit fs-5"></i>
                    <h6 class="m-0 fw-bold">تعديل اسم قسم / صنف العمليات</h6>
                </div>
                <button type="button" class="btn text-white p-0 fs-4 border-0" onclick="closeRenameModal()" style="line-height: 1; opacity: 0.85;">&times;</button>
            </div>
            <div class="p-4 text-end">
                <input type="hidden" name="old_category" id="modalOldCategory">
                
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">الاسم الحالي للقسم:</label>
                    <input type="text" class="form-control bg-light rounded-3" id="displayOldCategory" readonly>
                </div>

                <div class="mb-3">
                    <label for="modalNewCategory" class="form-label fw-bold text-dark">الاسم الجديد للقسم <span class="text-danger">*</span></label>
                    <input type="text" class="form-control py-2 rounded-3" name="new_category" id="modalNewCategory" required placeholder="أدخل الاسم الجديد للقسم...">
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle text-primary me-1"></i>
                        سيتم تحديث اسم هذا القسم في جميع العمليات التابعة له تلقائياً وفوراً.
                    </small>
                </div>
            </div>
            <div class="bg-light p-3 d-flex justify-content-end gap-2 border-top">
                <button type="button" class="btn btn-secondary px-3 rounded-3" onclick="closeRenameModal()">إلغاء</button>
                <button type="submit" class="btn btn-primary fw-bold px-4 rounded-3 shadow-sm">
                    <i class="fas fa-save me-1"></i> حفظ الاسم الجديد
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes customModalIn {
    from { opacity: 0; transform: scale(0.95) translateY(-10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
</style>

<script>
let currentSelectedCategory = 'all';

function selectCategoryTab(category, btnElement) {
    currentSelectedCategory = category;
    
    document.querySelectorAll('.cat-tab-btn').forEach(btn => {
        btn.classList.remove('btn-primary', 'active');
        btn.classList.add('btn-outline-secondary');
        const badge = btn.querySelector('.badge');
        if (badge) {
            badge.classList.remove('bg-white', 'text-primary');
            badge.classList.add('bg-secondary', 'text-white');
        }
    });

    if (btnElement) {
        btnElement.classList.remove('btn-outline-secondary');
        btnElement.classList.add('btn-primary', 'active');
        const badge = btnElement.querySelector('.badge');
        if (badge) {
            badge.classList.remove('bg-secondary', 'text-white');
            badge.classList.add('bg-white', 'text-primary');
        }
    }

    filterOperations();
}

function filterOperations() {
    const searchText = (document.getElementById('opSearchInput').value || '').toLowerCase().trim();
    const statusFilter = document.getElementById('statusFilterSelect').value;
    const rows = document.querySelectorAll('.operation-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const opName = (row.getAttribute('data-name') || '').toLowerCase();
        const opCategory = row.getAttribute('data-category') || '';
        const opActive = row.getAttribute('data-active') || 'active';

        const matchesSearch = !searchText || opName.includes(searchText) || opCategory.toLowerCase().includes(searchText);
        const matchesCategory = currentSelectedCategory === 'all' || opCategory === currentSelectedCategory;
        const matchesStatus = statusFilter === 'all' || opActive === statusFilter;

        if (matchesSearch && matchesCategory && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countText = document.getElementById('filteredCountText');
    if (countText) {
        countText.innerText = 'عرض ' + visibleCount + ' عملية';
    }

    const noResultsRow = document.getElementById('noResultsRow');
    if (noResultsRow) {
        noResultsRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
}

function openRenameCategoryModal(catName) {
    const oldInput = document.getElementById('modalOldCategory');
    const displayInput = document.getElementById('displayOldCategory');
    const newInput = document.getElementById('modalNewCategory');
    const modalEl = document.getElementById('renameCategoryModal');
    
    if (oldInput) oldInput.value = catName;
    if (displayInput) displayInput.value = catName;
    if (newInput) newInput.value = catName;
    
    if (modalEl) {
        modalEl.style.display = 'flex';
    }

    setTimeout(function() {
        if (newInput) {
            newInput.focus();
            newInput.select();
        }
    }, 150);
}

function closeRenameModal() {
    const modalEl = document.getElementById('renameCategoryModal');
    if (modalEl) {
        modalEl.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Attach click events to all rename buttons safely
    document.querySelectorAll('.rename-cat-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const cat = this.getAttribute('data-cat');
            openRenameCategoryModal(cat);
        });
    });

    // Close modal if clicked outside
    const modalEl = document.getElementById('renameCategoryModal');
    if (modalEl) {
        modalEl.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRenameModal();
            }
        });
    }

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRenameModal();
        }
    });
});
</script>
@endsection
