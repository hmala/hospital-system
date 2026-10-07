@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 bg-white p-3 rounded-3 shadow-sm border">
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-success-subtle text-success p-3 rounded-circle fs-5 shadow-xs">
                <i class="fas fa-pills"></i>
            </span>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h4 class="mb-0 fw-bold text-dark">{{ $group->name }}</h4>
                    <span class="badge bg-success rounded-pill px-2 py-1 small">
                        <span class="selected-count-badge">{{ $selectedCount }}</span> دواء
                    </span>
                </div>
                <p class="text-muted small mb-0">{{ $group->description ?: 'مجموعة الأدوية المفضلة للاستخدام السريع بالعيادة' }}</p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('medicine-groups.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fas fa-arrow-right me-1"></i> العودة للمجموعات
            </a>
            <button type="submit" form="groupMedicinesForm" class="btn btn-success btn-sm px-4 shadow-sm fw-bold">
                <i class="fas fa-save me-1"></i> حفظ التغييرات
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-3" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form id="groupMedicinesForm" action="{{ route('medicine-groups.update', $group) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Group Info Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">اسم المجموعة</label>
                        <input type="text" name="name" class="form-control form-control-sm bg-light" value="{{ old('name', $group->name) }}" required placeholder="اسم المجموعة...">
                    </div>
                    <div class="{{ (Auth::user()->isAdmin() || Auth::user()->hasRole('admin')) ? 'col-md-5' : 'col-md-8' }}">
                        <label class="form-label small fw-bold text-muted mb-1">وصف المجموعة (اختياري)</label>
                        <input type="text" name="description" class="form-control form-control-sm bg-light" value="{{ old('description', $group->description) }}" placeholder="وصف استخدام هذه المجموعة...">
                    </div>
                    @if(Auth::user()->isAdmin() || Auth::user()->hasRole('admin'))
                    <div class="col-md-3">
                        <div class="form-check form-switch mt-3 pt-1">
                            <input class="form-check-input" type="checkbox" role="switch" id="groupIsPublicEdit" name="is_public" value="1" {{ old('is_public', $group->is_public) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-bold text-primary cursor-pointer" for="groupIsPublicEdit">
                                <i class="fas fa-hospital me-1"></i> باقة عامة للمستشفى
                            </label>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Drug Selection & Category Filter Box -->
        <div class="card border-0 shadow-sm rounded-3 mb-3 border-start border-4 border-success">
            <div class="card-body p-3">
                <!-- Category Tabs (Pills) -->
                <div class="d-flex flex-wrap align-items-center gap-1 mb-3" id="categoryPillsWrapper">
                    <span class="small fw-bold text-muted me-2"><i class="fas fa-filter me-1"></i> الأقسام:</span>
                    <button type="button" class="btn btn-sm btn-success category-pill active" data-category="all">
                        الكل <span class="badge bg-white text-success rounded-pill ms-1">{{ $totalActiveMedicines }}</span>
                    </button>
                    @php
                        $formNames = [
                            'tablet' => 'حبوب وأقراص',
                            'syrup' => 'شراب ومعلق',
                            'injection' => 'إبر وحقن',
                            'cream' => 'مراهم وكريمات',
                            'drops' => 'قطرات وبخاخات',
                            'spray' => 'بخاخات',
                            'capsule' => 'كبسولات',
                            'suspension' => 'معلق',
                            'ointment' => 'مرهم',
                            'suppository' => 'تحاميل',
                            'inhaler' => 'بخاخ استنشاق',
                            'other' => 'أخرى',
                        ];
                    @endphp
                    @foreach($dosageForms as $df)
                        <button type="button" class="btn btn-sm btn-outline-secondary category-pill" data-category="{{ $df->dosage_form }}">
                            {{ $formNames[$df->dosage_form] ?? $df->dosage_form }}
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill ms-1">{{ $df->total_count }}</span>
                        </button>
                    @endforeach
                </div>

                <!-- Search Input with Dropdown -->
                <div class="position-relative">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-success">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 bg-light" id="medSearchInput" placeholder="ابحث باسم الدواء التجاري، الاسم العلمي، أو التركيز... (أو اضغط للتصفح السريع)" autocomplete="off">
                        <button class="btn btn-outline-secondary d-none" type="button" id="clearSearchBtn">
                            <i class="fas fa-times"></i>
                        </button>
                        <button class="btn btn-success px-3" type="button" id="browseMedicinesBtn">
                            <i class="fas fa-th-list me-1"></i> تصفح أدوية القسم (<span id="currentCategoryCount">{{ $totalActiveMedicines }}</span>)
                        </button>
                    </div>

                    <!-- Autocomplete Dropdown List -->
                    <div id="searchResultsDropdown" class="position-absolute w-100 bg-white shadow-lg rounded-3 border mt-1 d-none" style="z-index: 1050; max-height: 350px; overflow-y: auto;">
                        <div id="dropdownListContent"></div>
                        <div id="dropdownLoading" class="p-3 text-center text-muted small d-none">
                            <div class="spinner-border spinner-border-sm text-success me-1" role="status"></div> جاري تحميل الأدوية...
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected Prescription Table Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="mb-0 fw-bold text-dark fs-6">
                        <i class="fas fa-list-check text-primary me-2"></i> قائمة أدوية المجموعة
                    </h5>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 small">
                        <span class="selected-count-badge">{{ $selectedCount }}</span> دواء
                    </span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" id="clearAllMedicinesBtn">
                        <i class="fas fa-trash-alt me-1"></i> إفراغ الكل
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <!-- Empty State -->
                <div id="emptyMedicinesState" class="text-center py-5 {{ $selectedCount > 0 ? 'd-none' : '' }}">
                    <div class="mb-3">
                        <i class="fas fa-prescription-bottle-alt fa-3x text-muted opacity-50"></i>
                    </div>
                    <h6 class="fw-bold text-muted mb-1">المجموعة فارغة حالياً</h6>
                    <p class="text-muted small mb-0">اختر أحد الأقسام أو ابحث بالاسم لإضافة الأدوية وضبط جرعاتها</p>
                </div>

                <!-- Table -->
                <div class="table-responsive {{ $selectedCount === 0 ? 'd-none' : '' }}" id="medicinesTableWrapper">
                    <table class="table table-hover align-middle mb-0 text-center text-nowrap">
                        <thead class="table-light text-muted small">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th class="text-start" style="min-width: 200px;">الدواء والتركيز</th>
                                <th style="min-width: 140px;">الشكل الصيدلاني</th>
                                <th style="min-width: 130px;">الجرعة</th>
                                <th style="min-width: 140px;">التكرار</th>
                                <th style="min-width: 130px;">المدة</th>
                                <th style="min-width: 180px;">تعليمات إضافية</th>
                                <th style="width: 50px;">حذف</th>
                            </tr>
                        </thead>
                        <tbody id="medicinesTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Sticky Bottom Bar -->
        <div class="bg-white p-3 rounded-3 shadow border d-flex flex-wrap justify-content-between align-items-center gap-3 sticky-bottom mb-3" style="z-index: 1000;">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-muted small">إجمالي أدوية المجموعة:</span>
                <span class="badge bg-success fs-6 px-3 py-2 rounded-pill shadow-xs">
                    <span class="selected-count-badge">{{ $selectedCount }}</span> دواء
                </span>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('medicine-groups.index') }}" class="btn btn-outline-secondary px-3">
                    إلغاء
                </a>
                <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm">
                    <i class="fas fa-save me-1"></i> حفظ المجموعة
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .category-pill {
        border-radius: 20px;
        transition: all 0.2s ease-in-out;
    }
    .search-item-result {
        cursor: pointer;
        transition: background-color 0.15s ease-in-out;
    }
    .search-item-result:hover, .search-item-result.active {
        background-color: #f0fdf4 !important;
    }
    .table-highlight-add {
        animation: highlightRow 1.2s ease;
    }
    @keyframes highlightRow {
        0% { background-color: #dcfce7; }
        100% { background-color: transparent; }
    }
</style>
@endpush

@section('scripts')
<script>
window.selectedMedicinesData = {!! $selectedMedicinesJson !!};
window.searchMedicinesUrl = "{{ route('medicine-groups.search-medicines') }}";
</script>
<script>
(function() {
    function initMedicineGroupEdit() {
        const searchInput = document.getElementById('medSearchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const browseBtn = document.getElementById('browseMedicinesBtn');
        const searchDropdown = document.getElementById('searchResultsDropdown');
        const dropdownContent = document.getElementById('dropdownListContent');
        const dropdownLoading = document.getElementById('dropdownLoading');
        const tableBody = document.getElementById('medicinesTableBody');
        const tableWrapper = document.getElementById('medicinesTableWrapper');
        const emptyState = document.getElementById('emptyMedicinesState');
        const countBadges = document.querySelectorAll('.selected-count-badge');
        const clearAllBtn = document.getElementById('clearAllMedicinesBtn');
        const categoryPills = document.querySelectorAll('.category-pill');
        const currentCategoryCount = document.getElementById('currentCategoryCount');

        let currentCategory = 'all';
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        let searchResults = [];
        let selectedDropdownIndex = -1;

        const FREQ_OPTIONS = [
            ['1', '1x يومياً'], ['2', '2x يومياً'], ['3', '3x يومياً'],
            ['4', '4x يومياً'], ['as_needed', 'عند الحاجة'],
        ];
        const DOSAGE_FORM_OPTIONS = [
            ['tablet', 'حبوب / أقراص'], ['syrup', 'شراب'], ['injection', 'إبرة / حقن'],
            ['cream', 'كريم / مرهم'], ['drops', 'قطرات'], ['spray', 'بخاخ'],
            ['capsule', 'كبسولات'], ['suspension', 'معلق'], ['ointment', 'مرهم'],
            ['suppository', 'تحاميل'], ['inhaler', 'بخاخ استنشاق'], ['other', 'أخرى'],
        ];

        // Medicines State Map (id -> Object)
        const medicinesMap = new Map();
        (window.selectedMedicinesData || []).forEach(m => {
            medicinesMap.set(m.id, {
                id: m.id,
                name: m.name || '',
                generic: m.generic || '',
                strength: m.strength || '',
                dosage_form: m.dosage_form || 'tablet',
                dosage: m.dosage || '',
                frequency: m.frequency || '1',
                duration: m.duration || '',
                instructions: m.instructions || '',
            });
        });

        function escapeHtml(str) {
            return (str || '').toString()
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function updateCounters() {
            const count = medicinesMap.size;
            countBadges.forEach(b => b.textContent = count);

            if (count === 0) {
                tableWrapper.classList.add('d-none');
                emptyState.classList.remove('d-none');
            } else {
                tableWrapper.classList.remove('d-none');
                emptyState.classList.add('d-none');
            }
        }

        function renderRow(med, index) {
            const dosageFormOpts = DOSAGE_FORM_OPTIONS.map(([v, l]) =>
                `<option value="${v}" ${v === (med.dosage_form || 'tablet') ? 'selected' : ''}>${l}</option>`).join('');

            const freqOpts = FREQ_OPTIONS.map(([v, l]) =>
                `<option value="${v}" ${v === (med.frequency || '1') ? 'selected' : ''}>${l}</option>`).join('');

            return `
                <tr data-id="${med.id}" class="medicine-row">
                    <td class="text-muted fw-bold">${index + 1}</td>
                    <td class="text-start">
                        <input type="hidden" name="medicine_ids[]" value="${med.id}">
                        <div class="fw-bold text-dark text-truncate" style="max-width: 250px;">${escapeHtml(med.name)}</div>
                        <div class="text-muted small text-truncate" style="max-width: 250px;">
                            ${escapeHtml(med.generic)} ${med.strength ? '<span class="badge bg-light text-secondary border ms-1">' + escapeHtml(med.strength) + '</span>' : ''}
                        </div>
                    </td>
                    <td>
                        <select class="form-select form-select-sm bg-light field-dosage-form" name="dosage_form[${med.id}]">
                            ${dosageFormOpts}
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm bg-light field-dosage" name="dosage[${med.id}]" placeholder="مثال: 500mg" value="${escapeHtml(med.dosage)}">
                    </td>
                    <td>
                        <select class="form-select form-select-sm bg-light field-frequency" name="frequency[${med.id}]">
                            ${freqOpts}
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm bg-light field-duration" name="duration[${med.id}]" placeholder="مثال: 7 أيام" value="${escapeHtml(med.duration)}">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm bg-light field-instructions text-start" name="instructions[${med.id}]" placeholder="مثال: بعد الأكل..." value="${escapeHtml(med.instructions)}">
                    </td>
                    <td>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle remove-med-btn" title="حذف الدواء من المجموعة">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>`;
        }

        function renderTable() {
            let html = '';
            let idx = 0;
            medicinesMap.forEach(med => {
                html += renderRow(med, idx++);
            });
            tableBody.innerHTML = html;
            updateCounters();
        }

        function addMedicine(med) {
            if (medicinesMap.has(med.id)) {
                const existingRow = tableBody.querySelector(`tr[data-id="${med.id}"]`);
                if (existingRow) {
                    existingRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    existingRow.classList.remove('table-highlight-add');
                    void existingRow.offsetWidth;
                    existingRow.classList.add('table-highlight-add');
                }
                return;
            }

            medicinesMap.set(med.id, {
                id: med.id,
                name: med.name || '',
                generic: med.generic || '',
                strength: med.strength || '',
                dosage_form: med.dosage_form || 'tablet',
                dosage: '',
                frequency: '1',
                duration: '',
                instructions: '',
            });

            renderTable();

            const newRow = tableBody.querySelector(`tr[data-id="${med.id}"]`);
            if (newRow) {
                newRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                newRow.classList.add('table-highlight-add');
            }
        }

        // Table input sync
        tableBody.addEventListener('input', function(e) {
            const row = e.target.closest('tr');
            if (!row) return;
            const id = parseInt(row.getAttribute('data-id'), 10);
            const med = medicinesMap.get(id);
            if (!med) return;

            if (e.target.classList.contains('field-dosage')) med.dosage = e.target.value;
            if (e.target.classList.contains('field-duration')) med.duration = e.target.value;
            if (e.target.classList.contains('field-instructions')) med.instructions = e.target.value;
        });

        tableBody.addEventListener('change', function(e) {
            const row = e.target.closest('tr');
            if (!row) return;
            const id = parseInt(row.getAttribute('data-id'), 10);
            const med = medicinesMap.get(id);
            if (!med) return;

            if (e.target.classList.contains('field-dosage-form')) med.dosage_form = e.target.value;
            if (e.target.classList.contains('field-frequency')) med.frequency = e.target.value;
        });

        // Remove row
        tableBody.addEventListener('click', function(e) {
            const btn = e.target.closest('.remove-med-btn');
            if (!btn) return;
            const row = btn.closest('tr');
            const id = parseInt(row.getAttribute('data-id'), 10);

            row.style.transition = 'all 0.25s ease';
            row.style.opacity = '0';
            row.style.transform = 'scale(0.95)';

            setTimeout(() => {
                medicinesMap.delete(id);
                renderTable();
                updateDropdownItemState(id, false);
            }, 250);
        });

        // Clear all
        clearAllBtn.addEventListener('click', function() {
            if (medicinesMap.size === 0) return;
            if (confirm('هل أنت متأكد من إفراغ كافة أدوية المجموعة؟')) {
                medicinesMap.clear();
                renderTable();
                if (!searchDropdown.classList.contains('d-none')) {
                    renderDropdown(searchResults, false);
                }
            }
        });

        function updateDropdownItemState(medId, isAdded) {
            const el = dropdownContent.querySelector(`.search-item-result[data-id="${medId}"]`);
            if (el) {
                const actionCol = el.querySelector('.action-badge-container');
                if (actionCol) {
                    actionCol.innerHTML = isAdded 
                        ? '<span class="badge bg-success-subtle text-success small"><i class="fas fa-check me-1"></i> مضاف</span>'
                        : '<button type="button" class="btn btn-sm btn-outline-success px-2 py-1"><i class="fas fa-plus me-1"></i> إضافة</button>';
                }
            }
        }

        // Category switching
        categoryPills.forEach(pill => {
            pill.addEventListener('click', function() {
                categoryPills.forEach(p => {
                    p.classList.remove('btn-success', 'active');
                    p.classList.add('btn-outline-secondary');
                    const b = p.querySelector('.badge');
                    if (b) {
                        b.classList.remove('bg-white', 'text-success');
                        b.classList.add('bg-secondary-subtle', 'text-secondary');
                    }
                });

                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-success', 'active');
                const badge = this.querySelector('.badge');
                if (badge) {
                    badge.classList.remove('bg-secondary-subtle', 'text-secondary');
                    badge.classList.add('bg-white', 'text-success');
                    if (currentCategoryCount) currentCategoryCount.textContent = badge.textContent.trim();
                }

                currentCategory = this.getAttribute('data-category');
                currentPage = 1;
                hasMore = true;
                searchResults = [];
                dropdownContent.innerHTML = '';
                fetchMedicines(true);
            });
        });

        function closeDropdown() {
            searchDropdown.classList.add('d-none');
            dropdownContent.innerHTML = '';
            searchResults = [];
            selectedDropdownIndex = -1;
        }

        function renderDropdown(items, append = false) {
            if (!append) {
                searchResults = items;
                selectedDropdownIndex = -1;
            } else {
                searchResults = searchResults.concat(items);
            }

            if (searchResults.length === 0) {
                dropdownContent.innerHTML = `
                    <div class="p-3 text-center text-muted small">
                        <i class="fas fa-exclamation-circle me-1"></i> لا توجد أدوية مطابقة للبحث أو في هذا القسم
                    </div>`;
                searchDropdown.classList.remove('d-none');
                return;
            }

            let html = append ? dropdownContent.querySelector('.list-group').innerHTML : '<div class="list-group list-group-flush">';
            items.forEach((item, i) => {
                const actualIdx = append ? searchResults.length - items.length + i : i;
                const isAdded = medicinesMap.has(item.id);
                const itemHtml = `
                    <div class="list-group-item search-item-result p-2 d-flex justify-content-between align-items-center" data-index="${actualIdx}" data-id="${item.id}">
                        <div>
                            <div class="fw-bold text-dark small">${escapeHtml(item.name)} ${item.strength ? '<span class="badge bg-light text-secondary border">' + escapeHtml(item.strength) + '</span>' : ''}</div>
                            <div class="text-muted" style="font-size: 0.75rem;">${escapeHtml(item.generic || 'بدون اسم علمي')}</div>
                        </div>
                        <div class="action-badge-container">
                            ${isAdded 
                                ? '<span class="badge bg-success-subtle text-success small"><i class="fas fa-check me-1"></i> مضاف</span>' 
                                : '<button type="button" class="btn btn-sm btn-outline-success px-2 py-1"><i class="fas fa-plus me-1"></i> إضافة</button>'
                            }
                        </div>
                    </div>`;
                if (append) {
                    html += itemHtml;
                } else {
                    html += itemHtml;
                }
            });
            if (!append) html += '</div>';

            dropdownContent.innerHTML = append ? `<div class="list-group list-group-flush">${html}</div>` : html;
            searchDropdown.classList.remove('d-none');
        }

        function fetchMedicines(isNewSearch = false) {
            if (isLoading) return;
            const q = (searchInput.value || '').trim();

            if (isNewSearch) {
                currentPage = 1;
                hasMore = true;
                searchResults = [];
                dropdownContent.innerHTML = '';
            }

            if (!hasMore) return;

            // If empty search and category is all, only fetch when explicitly asked
            if (q.length < 2 && currentCategory === 'all' && isNewSearch && !dropdownContent.innerHTML) {
                // Show hint
            }

            isLoading = true;
            dropdownLoading.classList.remove('d-none');
            searchDropdown.classList.remove('d-none');

            const params = new URLSearchParams({
                q: q,
                dosage_form: currentCategory,
                page: currentPage,
                limit: 30,
            });

            fetch(`${window.searchMedicinesUrl}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(list => {
                    isLoading = false;
                    dropdownLoading.classList.add('d-none');
                    if (list.length < 30) {
                        hasMore = false;
                    }
                    renderDropdown(list, !isNewSearch);
                    currentPage++;
                })
                .catch(() => {
                    isLoading = false;
                    dropdownLoading.classList.add('d-none');
                    if (isNewSearch) closeDropdown();
                });
        }

        // Infinite scroll inside dropdown
        searchDropdown.addEventListener('scroll', function() {
            if (this.scrollTop + this.clientHeight >= this.scrollHeight - 40 && hasMore && !isLoading) {
                fetchMedicines(false);
            }
        });

        // Search Input Events
        let searchDebounce = null;
        searchInput.addEventListener('input', function() {
            const hasVal = this.value.trim().length > 0;
            clearSearchBtn.classList.toggle('d-none', !hasVal);
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => fetchMedicines(true), 200);
        });

        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            clearSearchBtn.classList.add('d-none');
            fetchMedicines(true);
            searchInput.focus();
        });

        browseBtn.addEventListener('click', function() {
            if (searchDropdown.classList.contains('d-none') || searchResults.length === 0) {
                fetchMedicines(true);
            } else {
                closeDropdown();
            }
        });

        searchDropdown.addEventListener('click', function(e) {
            const itemEl = e.target.closest('.search-item-result');
            if (!itemEl) return;
            const idx = parseInt(itemEl.getAttribute('data-index'), 10);
            const med = searchResults[idx];
            if (med) {
                addMedicine(med);
                updateDropdownItemState(med.id, true);
            }
        });

        // Keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            if (searchDropdown.classList.contains('d-none') || searchResults.length === 0) return;

            const items = dropdownContent.querySelectorAll('.search-item-result');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedDropdownIndex = Math.min(selectedDropdownIndex + 1, items.length - 1);
                updateDropdownSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedDropdownIndex = Math.max(selectedDropdownIndex - 1, 0);
                updateDropdownSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const targetIdx = selectedDropdownIndex >= 0 ? selectedDropdownIndex : 0;
                const med = searchResults[targetIdx];
                if (med) {
                    addMedicine(med);
                    updateDropdownItemState(med.id, true);
                }
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        function updateDropdownSelection(items) {
            items.forEach((item, i) => {
                if (i === selectedDropdownIndex) {
                    item.classList.add('active');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('active');
                }
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target) && !browseBtn.contains(e.target) && !e.target.closest('#categoryPillsWrapper')) {
                closeDropdown();
            }
        });

        // Initialize table
        renderTable();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMedicineGroupEdit);
    } else {
        initMedicineGroupEdit();
    }
})();
</script>
@endsection
