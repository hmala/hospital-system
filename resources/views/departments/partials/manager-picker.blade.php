@php
    $selectedManagerId = old('manager_id', $department->manager_id ?? null);
    $selectedManager = null;
    if ($selectedManagerId) {
        $selectedManager = ($managers ?? collect())->firstWhere('id', (int) $selectedManagerId)
            ?? (($department->manager ?? null) && $department->manager->id == $selectedManagerId ? $department->manager : null)
            ?? \App\Models\User::find($selectedManagerId, ['id', 'name', 'email', 'phone', 'role']);
    }

    $initialManagersPayload = ($managers ?? collect())->map(function ($m) {
        return [
            'id' => $m->id,
            'name' => $m->name,
            'email' => $m->email,
            'phone' => $m->phone ?? null,
            'role' => $m->role ?? null,
        ];
    })->values();
@endphp

<div class="department-manager-picker position-relative" id="departmentManagerPicker" data-search-url="{{ route('departments.managers.search') }}">
    <input type="hidden" name="manager_id" id="manager_id" value="{{ $selectedManager?->id ?? '' }}">

    {{-- بطاقة الشخص المختار حالياً --}}
    <div id="selectedManagerCard" class="d-flex align-items-center justify-content-between gap-2 p-2.5 px-3 mb-2 rounded-3 border border-primary border-opacity-25 bg-primary bg-opacity-10 {{ $selectedManager ? '' : 'd-none' }}">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 34px; height: 34px; font-size: 0.9rem;" id="selectedManagerAvatar">
                {{ $selectedManager ? mb_substr($selectedManager->name, 0, 1) : '' }}
            </div>
            <div class="min-w-0">
                <div class="fw-bold text-dark text-truncate" id="selectedManagerName">{{ $selectedManager?->name ?? '' }}</div>
                <div class="small text-muted text-truncate" dir="ltr" id="selectedManagerEmail">{{ $selectedManager?->email ?? '' }}</div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 flex-shrink-0" id="clearSelectedManagerBtn" title="إزالة مدير القسم">
            <i class="fas fa-times me-1"></i>إزالة
        </button>
    </div>

    {{-- حقل البحث الفوري بالاسم أو الإيميل --}}
    <div class="input-group">
        <span class="input-group-text bg-white border-end-0 text-muted rounded-start-3">
            <i class="fas fa-search" id="managerSearchIcon"></i>
            <span class="spinner-border spinner-border-sm text-primary d-none" id="managerSearchSpinner" role="status" aria-hidden="true"></span>
        </span>
        <input type="text"
               id="managerSearchInput"
               class="form-control border-start-0 rounded-end-3 @error('manager_id') is-invalid @enderror"
               placeholder="اكتب اسم الموظف أو البريد الإلكتروني للبحث والاختيار..."
               autocomplete="off"
               aria-autocomplete="list"
               aria-controls="managerSearchDropdown"
               aria-expanded="false">
    </div>

    {{-- قائمة نتائج البحث --}}
    <div id="managerSearchDropdown"
         class="list-group shadow-lg border rounded-3 position-absolute w-100 mt-1 d-none bg-white"
         style="z-index: 1055; max-height: 280px; overflow-y: auto;"
         role="listbox">
    </div>
</div>

@error('manager_id')
    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
@enderror

<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.getElementById('departmentManagerPicker');
    if (!wrapper) return;

    const searchUrl = wrapper.dataset.searchUrl;
    const hiddenInput = document.getElementById('manager_id');
    const searchInput = document.getElementById('managerSearchInput');
    const dropdown = document.getElementById('managerSearchDropdown');
    const searchIcon = document.getElementById('managerSearchIcon');
    const searchSpinner = document.getElementById('managerSearchSpinner');

    const selectedCard = document.getElementById('selectedManagerCard');
    const selectedAvatar = document.getElementById('selectedManagerAvatar');
    const selectedName = document.getElementById('selectedManagerName');
    const selectedEmail = document.getElementById('selectedManagerEmail');
    const clearBtn = document.getElementById('clearSelectedManagerBtn');

    const localManagers = @json($initialManagersPayload);
    let currentItems = [];
    let highlightedIndex = -1;
    let debounceTimer = null;
    let activeController = null;

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function setLoading(loading) {
        if (searchIcon) searchIcon.classList.toggle('d-none', loading);
        if (searchSpinner) searchSpinner.classList.toggle('d-none', !loading);
    }

    function selectManager(item) {
        hiddenInput.value = item.id;
        selectedAvatar.textContent = (item.name || '?').trim().charAt(0);
        selectedName.textContent = item.name || '';
        selectedEmail.textContent = item.email || '';
        selectedCard.classList.remove('d-none');
        searchInput.value = '';
        hideDropdown();
    }

    function clearManager() {
        hiddenInput.value = '';
        selectedCard.classList.add('d-none');
        selectedName.textContent = '';
        selectedEmail.textContent = '';
        searchInput.focus();
    }

    function hideDropdown() {
        dropdown.classList.add('d-none');
        searchInput.setAttribute('aria-expanded', 'false');
        highlightedIndex = -1;
    }

    function renderResults(items, query) {
        currentItems = items || [];
        highlightedIndex = -1;

        if (!currentItems.length) {
            dropdown.innerHTML = `<div class="list-group-item text-muted text-center py-3 small">
                <i class="fas fa-user-slash me-1"></i> لا توجد نتائج مطابقة لـ «${escapeHtml(query)}»
            </div>`;
            dropdown.classList.remove('d-none');
            searchInput.setAttribute('aria-expanded', 'true');
            return;
        }

        dropdown.innerHTML = currentItems.map((item, idx) => {
            const initial = escapeHtml((item.name || '?').trim().charAt(0));
            const isSelected = String(hiddenInput.value) === String(item.id);
            const roleBadge = item.role ? `<span class="badge bg-light text-secondary border ms-1">${escapeHtml(item.role)}</span>` : '';
            const phonePart = item.phone ? `<span class="ms-2 text-muted small"><i class="fas fa-phone-alt me-1"></i>${escapeHtml(item.phone)}</span>` : '';

            return `<button type="button"
                            class="list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-2 py-2 px-3 manager-option-item ${isSelected ? 'active' : ''}"
                            data-index="${idx}"
                            role="option">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="rounded-circle ${isSelected ? 'bg-white text-primary' : 'bg-primary bg-opacity-10 text-primary'} d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                ${initial}
                            </div>
                            <div class="min-w-0 text-start">
                                <div class="fw-semibold text-truncate">${escapeHtml(item.name)} ${roleBadge}</div>
                                <div class="small ${isSelected ? 'text-white-50' : 'text-muted'} text-truncate">
                                    <span dir="ltr">${escapeHtml(item.email || '')}</span>${phonePart}
                                </div>
                            </div>
                        </div>
                        ${isSelected ? '<i class="fas fa-check-circle flex-shrink-0"></i>' : ''}
                    </button>`;
        }).join('');

        dropdown.classList.remove('d-none');
        searchInput.setAttribute('aria-expanded', 'true');

        dropdown.querySelectorAll('.manager-option-item').forEach(btn => {
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                const idx = parseInt(this.dataset.index, 10);
                if (currentItems[idx]) {
                    selectManager(currentItems[idx]);
                }
            });
        });
    }

    function filterLocal(query) {
        const q = (query || '').trim().toLowerCase();
        if (!q) {
            return localManagers.slice(0, 15);
        }
        return localManagers.filter(m => {
            return (m.name && m.name.toLowerCase().includes(q))
                || (m.email && m.email.toLowerCase().includes(q))
                || (m.phone && String(m.phone).toLowerCase().includes(q));
        }).slice(0, 15);
    }

    async function fetchManagers(query) {
        // عرض فوري من القائمة المحلية بينما يتم جلب النتائج المحدثة من السيرفر
        renderResults(filterLocal(query), query);

        if (activeController) {
            activeController.abort();
        }
        activeController = new AbortController();
        setLoading(true);

        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', query.trim());

            const response = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: activeController.signal,
            });

            if (response.ok) {
                const payload = await response.json();
                if (Array.isArray(payload.data)) {
                    renderResults(payload.data, query);
                }
            }
        } catch (err) {
            if (err.name !== 'AbortError') {
                // نعتمد نتائج البحث المحلي في حال انقطاع الشبكة
            }
        } finally {
            setLoading(false);
        }
    }

    function updateHighlight() {
        const items = dropdown.querySelectorAll('.manager-option-item');
        items.forEach((el, idx) => {
            el.classList.toggle('bg-light', idx === highlightedIndex && !el.classList.contains('active'));
            if (idx === highlightedIndex) {
                el.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    searchInput.addEventListener('focus', function () {
        renderResults(filterLocal(this.value), this.value);
    });

    searchInput.addEventListener('input', function () {
        const q = this.value;
        renderResults(filterLocal(q), q);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchManagers(q), 200);
    });

    searchInput.addEventListener('keydown', function (e) {
        if (dropdown.classList.contains('d-none')) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (currentItems.length) {
                highlightedIndex = (highlightedIndex + 1) % currentItems.length;
                updateHighlight();
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (currentItems.length) {
                highlightedIndex = (highlightedIndex - 1 + currentItems.length) % currentItems.length;
                updateHighlight();
            }
        } else if (e.key === 'Enter') {
            if (highlightedIndex >= 0 && currentItems[highlightedIndex]) {
                e.preventDefault();
                selectManager(currentItems[highlightedIndex]);
            } else if (currentItems.length === 1) {
                e.preventDefault();
                selectManager(currentItems[0]);
            }
        } else if (e.key === 'Escape') {
            hideDropdown();
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', clearManager);
    }

    document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
            hideDropdown();
        }
    });
});
</script>
