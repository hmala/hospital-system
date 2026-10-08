@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3 bg-white p-3 rounded-3 shadow-sm border">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary p-2 rounded-circle fs-6">
                <i class="fas fa-layer-group"></i>
            </span>
            <div>
                <h4 class="mb-0 fw-bold text-dark">{{ $group->name }}</h4>
                <p class="text-muted small mb-0">{{ $group->description ?: 'مجموعة التحاليل المفضلة للطبيب' }}</p>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('lab-tests.groups.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة للمجموعات
            </a>
            <button type="submit" form="groupTestsForm" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="fas fa-save me-1"></i> حفظ التحاليل (<span class="selected-count-badge">{{ count($selectedTestIds) }}</span>)
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form id="groupTestsForm" action="{{ route('lab-tests.groups.update', $group) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Controls Toolbar -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <!-- Search -->
                    <div class="col-md-5 col-12">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 bg-light" id="labSearchInput" placeholder="بحث سريع بالاسم أو الكود (مثال: CBC, Sugar, TSH)..." autocomplete="off">
                            <button class="btn btn-outline-secondary d-none" type="button" id="clearSearchBtn"><i class="fas fa-times"></i></button>
                        </div>
                    </div>

                    <!-- Quick Filters & Actions -->
                    <div class="col-md-7 col-12 d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary" id="btnSelectAll">
                                <i class="fas fa-check-double me-1"></i> تحديد المعروض
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="btnUnselectAll">
                                <i class="fas fa-times me-1"></i> إلغاء المعروض
                            </button>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-info" id="filterSelectedOnlyToggle">
                            <i class="fas fa-filter me-1"></i> عرض المحددة فقط (<span class="selected-count-badge">{{ count($selectedTestIds) }}</span>)
                        </button>
                    </div>
                </div>

                <!-- Category Pills Horizontal Bar -->
                <div class="category-pills-wrapper mt-3 pt-2 border-top">
                    <div class="d-flex align-items-center gap-1 overflow-x-auto pb-1" id="categoryTabsList" style="white-space: nowrap;">
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1 cat-pill-btn active" data-category="ALL">
                            الكل <span class="badge bg-white text-primary ms-1" id="allTestsCount">0</span>
                        </button>
                        @foreach($labTests as $catName => $tests)
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 cat-pill-btn" data-category="{{ $catName }}">
                                {{ $catName }} <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $tests->count() }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Selected Items Quick Preview Strip -->
        <div class="card border-0 shadow-sm mb-3 bg-light" id="selectedSummaryCard" style="{{ count($selectedTestIds) === 0 ? 'display: none;' : '' }}">
            <div class="card-body p-2 px-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="small fw-bold text-dark">
                        <i class="fas fa-check-circle text-success me-1"></i> التحاليل المختارة حالياً في المجموعة (<span class="selected-count-badge">{{ count($selectedTestIds) }}</span>)
                    </span>
                    <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" id="btnClearAllSelected">
                        <i class="fas fa-trash-alt me-1"></i> إفراغ الكل
                    </button>
                </div>
                <div class="d-flex flex-wrap gap-1" id="selectedChipsContainer" style="max-height: 120px; overflow-y: auto;">
                    <!-- Filled dynamically via JS -->
                </div>
            </div>
        </div>

        <!-- Tests Grid Section -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div id="noResultsAlert" class="text-center py-5 d-none">
                    <i class="fas fa-search fa-3x text-muted mb-2"></i>
                    <h6 class="text-muted">لا توجد تحاليل مطابقة للبحث أو القسم المختار</h6>
                </div>

                <div class="row g-2" id="testsGrid">
                    @foreach($labTests as $catName => $tests)
                        @foreach($tests as $test)
                            @php
                                $isChecked = in_array($test->id, $selectedTestIds);
                            @endphp
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 test-card-col" 
                                 data-id="{{ $test->id }}"
                                 data-name="{{ strtolower($test->name) }}"
                                 data-code="{{ strtolower($test->code ?? '') }}"
                                 data-category="{{ $catName }}"
                                 data-checked="{{ $isChecked ? '1' : '0' }}">
                                <label for="test_chk_{{ $test->id }}" 
                                       class="test-item-card p-2 rounded-3 border d-flex align-items-center justify-content-between h-100 mb-0 w-100 user-select-none {{ $isChecked ? 'border-primary bg-primary-subtle-custom' : 'bg-white' }}" 
                                       style="cursor: pointer; transition: all 0.15s ease;">
                                    <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                                        <div class="form-check m-0 d-flex align-items-center">
                                            <input class="form-check-input test-checkbox m-0" 
                                                   type="checkbox" 
                                                   name="lab_test_ids[]" 
                                                   value="{{ $test->id }}" 
                                                   id="test_chk_{{ $test->id }}"
                                                   data-name="{{ $test->name }}"
                                                   data-code="{{ $test->code ?? '' }}"
                                                   {{ $isChecked ? 'checked' : '' }}
                                                   style="cursor: pointer; width: 1.15em; height: 1.15em;">
                                        </div>
                                        <div class="text-truncate">
                                            <div class="fw-semibold text-dark text-truncate small" title="{{ $test->name }}">{{ $test->name }}</div>
                                            <div class="d-flex align-items-center gap-1">
                                                @if($test->code)
                                                    <span class="badge bg-light text-muted border px-1 py-0 font-monospace" style="font-size: 0.7rem;">{{ $test->code }}</span>
                                                @endif
                                                <span class="text-muted" style="font-size: 0.7rem;">{{ Str::limit($catName, 20) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="check-icon-indicator ms-1 text-primary {{ $isChecked ? '' : 'd-none' }}">
                                        <i class="fas fa-check-circle fs-6"></i>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Floating Bottom Action Bar -->
        <div class="sticky-bottom bg-white p-3 rounded-top-3 shadow-lg border border-bottom-0 d-flex justify-content-between align-items-center" style="z-index: 1020; margin: 0 -15px -15px -15px;">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark">المجموع:</span>
                <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill">
                    <span class="selected-count-badge">{{ count($selectedTestIds) }}</span> تحليل محدد
                </span>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('lab-tests.groups.index') }}" class="btn btn-light border">إلغاء</a>
                <button type="submit" class="btn btn-primary px-4 fw-bold">
                    <i class="fas fa-save me-1"></i> حفظ المجموعة
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .bg-primary-subtle-custom {
        background-color: #eef6ff !important;
        border-color: #3b82f6 !important;
    }
    .test-item-card:hover {
        border-color: #93c5fd !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .category-pills-wrapper::-webkit-scrollbar {
        height: 4px;
    }
    .category-pills-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
</style>
@endpush

@section('scripts')
<script>
(function() {
    function initLabGroupEdit() {
        const searchInput = document.getElementById('labSearchInput');
        const clearSearchBtn = document.getElementById('clearSearchBtn');
        const catPillBtns = document.querySelectorAll('.cat-pill-btn');
        const testCardCols = Array.from(document.querySelectorAll('.test-card-col'));
        const btnSelectAll = document.getElementById('btnSelectAll');
        const btnUnselectAll = document.getElementById('btnUnselectAll');
        const filterSelectedOnlyToggle = document.getElementById('filterSelectedOnlyToggle');
        const btnClearAllSelected = document.getElementById('btnClearAllSelected');
        const selectedChipsContainer = document.getElementById('selectedChipsContainer');
        const selectedSummaryCard = document.getElementById('selectedSummaryCard');
        const countBadges = document.querySelectorAll('.selected-count-badge');
        const noResultsAlert = document.getElementById('noResultsAlert');
        const allTestsCountBadge = document.getElementById('allTestsCount');

        let activeCategory = 'ALL';
        let filterSelectedOnly = false;

        if (allTestsCountBadge) {
            allTestsCountBadge.textContent = testCardCols.length;
        }

        // Checkbox change handler (natively fired on card click via <label>)
        function updateCardState(chk) {
            const col = chk.closest('.test-card-col');
            if (!col) return;
            const card = col.querySelector('.test-item-card');
            const checkIcon = col.querySelector('.check-icon-indicator');

            col.setAttribute('data-checked', chk.checked ? '1' : '0');

            if (chk.checked) {
                if (card) {
                    card.classList.add('border-primary', 'bg-primary-subtle-custom');
                    card.classList.remove('bg-white');
                }
                if (checkIcon) checkIcon.classList.remove('d-none');
            } else {
                if (card) {
                    card.classList.remove('border-primary', 'bg-primary-subtle-custom');
                    card.classList.add('bg-white');
                }
                if (checkIcon) checkIcon.classList.add('d-none');
            }
        }

        document.querySelectorAll('.test-checkbox').forEach(chk => {
            chk.addEventListener('change', function() {
                updateCardState(this);
                renderSelectedChips();
                updateCount();
                if (filterSelectedOnly && !this.checked) {
                    applyFilters();
                }
            });
        });

        // Render Selected Chips
        function renderSelectedChips() {
            const selectedCheckboxes = document.querySelectorAll('.test-checkbox:checked');
            if (!selectedChipsContainer || !selectedSummaryCard) return;

            selectedChipsContainer.innerHTML = '';

            if (selectedCheckboxes.length === 0) {
                selectedSummaryCard.style.display = 'none';
                return;
            }

            selectedSummaryCard.style.display = 'block';

            selectedCheckboxes.forEach(chk => {
                const id = chk.value;
                const name = chk.getAttribute('data-name') || '';
                const code = chk.getAttribute('data-code') || '';

                const chip = document.createElement('span');
                chip.className = 'badge bg-white text-dark border p-1 px-2 d-inline-flex align-items-center gap-1 shadow-sm';
                chip.style.fontSize = '0.78rem';
                chip.innerHTML = `
                    <span class="fw-semibold">${name}</span>
                    ${code ? `<span class="text-muted small">(${code})</span>` : ''}
                    <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.55rem;" aria-label="Remove"></button>
                `;

                chip.querySelector('.btn-close').addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    chk.checked = false;
                    updateCardState(chk);
                    renderSelectedChips();
                    updateCount();
                    if (filterSelectedOnly) applyFilters();
                });

                selectedChipsContainer.appendChild(chip);
            });
        }

        // Update Counter Badges
        function updateCount() {
            const count = document.querySelectorAll('.test-checkbox:checked').length;
            countBadges.forEach(b => b.textContent = count);
        }

        // Filters
        function applyFilters() {
            const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
            let visibleCount = 0;

            testCardCols.forEach(col => {
                const name = col.getAttribute('data-name') || '';
                const code = col.getAttribute('data-code') || '';
                const category = col.getAttribute('data-category') || '';
                const isChecked = col.getAttribute('data-checked') === '1';

                const matchesCategory = (activeCategory === 'ALL' || category === activeCategory);
                const matchesQuery = (!query || name.includes(query) || code.includes(query));
                const matchesSelected = (!filterSelectedOnly || isChecked);

                if (matchesCategory && matchesQuery && matchesSelected) {
                    col.style.display = '';
                    visibleCount++;
                } else {
                    col.style.display = 'none';
                }
            });

            if (noResultsAlert) {
                if (visibleCount === 0) {
                    noResultsAlert.classList.remove('d-none');
                } else {
                    noResultsAlert.classList.add('d-none');
                }
            }
        }

        // Search Events
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                if (clearSearchBtn) {
                    if (this.value.trim().length > 0) {
                        clearSearchBtn.classList.remove('d-none');
                    } else {
                        clearSearchBtn.classList.add('d-none');
                    }
                }
                applyFilters();
            });
        }

        if (clearSearchBtn && searchInput) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                clearSearchBtn.classList.add('d-none');
                searchInput.focus();
                applyFilters();
            });
        }

        // Category Pills
        catPillBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                catPillBtns.forEach(b => {
                    b.classList.remove('btn-primary', 'active');
                    b.classList.add('btn-outline-secondary');
                    const badge = b.querySelector('.badge');
                    if (badge) {
                        badge.classList.remove('bg-white', 'text-primary');
                        badge.classList.add('bg-secondary-subtle', 'text-secondary');
                    }
                });

                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-primary', 'active');
                const badge = this.querySelector('.badge');
                if (badge) {
                    badge.classList.remove('bg-secondary-subtle', 'text-secondary');
                    badge.classList.add('bg-white', 'text-primary');
                }

                activeCategory = this.getAttribute('data-category');
                applyFilters();
            });
        });

        // Filter Selected Only Toggle
        if (filterSelectedOnlyToggle) {
            filterSelectedOnlyToggle.addEventListener('click', function() {
                filterSelectedOnly = !filterSelectedOnly;
                if (filterSelectedOnly) {
                    this.classList.remove('btn-outline-info');
                    this.classList.add('btn-info', 'text-white');
                } else {
                    this.classList.remove('btn-info', 'text-white');
                    this.classList.add('btn-outline-info');
                }
                applyFilters();
            });
        }

        // Select All Visible
        if (btnSelectAll) {
            btnSelectAll.addEventListener('click', function() {
                testCardCols.forEach(col => {
                    if (col.style.display !== 'none') {
                        const chk = col.querySelector('.test-checkbox');
                        if (chk) {
                            chk.checked = true;
                            updateCardState(chk);
                        }
                    }
                });
                renderSelectedChips();
                updateCount();
            });
        }

        // Unselect All Visible
        if (btnUnselectAll) {
            btnUnselectAll.addEventListener('click', function() {
                testCardCols.forEach(col => {
                    if (col.style.display !== 'none') {
                        const chk = col.querySelector('.test-checkbox');
                        if (chk) {
                            chk.checked = false;
                            updateCardState(chk);
                        }
                    }
                });
                renderSelectedChips();
                updateCount();
                if (filterSelectedOnly) applyFilters();
            });
        }

        // Clear All Selected
        if (btnClearAllSelected) {
            btnClearAllSelected.addEventListener('click', function() {
                testCardCols.forEach(col => {
                    const chk = col.querySelector('.test-checkbox');
                    if (chk) {
                        chk.checked = false;
                        updateCardState(chk);
                    }
                });
                renderSelectedChips();
                updateCount();
                if (filterSelectedOnly) applyFilters();
            });
        }

        // Initial render
        renderSelectedChips();
        updateCount();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLabGroupEdit);
    } else {
        initLabGroupEdit();
    }
})();
</script>
@endsection
