@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-gradient bg-primary text-white p-3 rounded-top-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                    <i class="fas fa-edit fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold">تعديل طلبية تعزيز المخزون #{{ $stockTransferRequest->id }}</h5>
                    <small class="text-white-50">تعديل المواد والكميات المطلوبة لعهدة الطابق قبل اعتمادها من المخزن الرئيسي</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('stock-transfers.requests.show', $stockTransferRequest) }}" class="btn btn-light btn-sm rounded-pill px-3 py-1.5 fw-bold shadow-xs text-primary">
                    <i class="fas fa-arrow-right me-1"></i> إلغاء ورجوع للطلب
                </a>
            </div>
        </div>

        <div class="card-body p-4">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                        <div>
                            <strong>يرجى تصحيح الأخطاء التالية:</strong>
                            <ul class="mb-0 mt-1 small">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('stock-transfers.requests.update', $stockTransferRequest) }}" id="transferEditForm">
                @csrf
                @method('PUT')

                <!-- مسار التحويل المدمج -->
                <div class="p-3 bg-light rounded-4 border mb-4">
                    <div class="row align-items-center g-3">
                        <!-- From: Main Warehouse -->
                        <div class="col-md-5">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-medium">المصدر المرسل:</span>
                                <span class="badge bg-primary text-white px-3 py-2 rounded-pill fs-6 fw-bold">
                                    <i class="fas fa-warehouse me-1 text-warning"></i> {{ $stockTransferRequest->fromLocation?->name ?? 'المخزن الرئيسي' }}
                                </span>
                            </div>
                        </div>

                        <!-- Direction Arrow -->
                        <div class="col-md-2 text-center d-none d-md-block">
                            <i class="fas fa-arrow-left text-primary fs-5"></i>
                        </div>

                        <!-- To: Floor Store -->
                        <div class="col-md-5">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-medium">المخزن الفرعي المستلم:</span>
                                <span class="badge bg-info-subtle text-info-emphasis border border-info px-3 py-2 rounded-pill fs-6 fw-bold">
                                    <i class="fas fa-lock text-warning me-1"></i> {{ $stockTransferRequest->toLocation?->name ?? 'مخزن الطابق' }}
                                </span>
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill small">عهدتك المعتمدة</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- جدول المواد المطلوبة للتعديل -->
                <div class="card border rounded-4 shadow-none mb-4" style="overflow: visible !important;">
                    <div class="card-header bg-white py-3 px-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-boxes text-success fs-5"></i>
                            <h6 class="mb-0 fw-bold text-dark">المواد والمستلزمات في الطلب</h6>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small" id="itemsCountBadge">{{ count($currentItems) }} مواد</span>
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold" id="addItemRow">
                            <i class="fas fa-plus me-1"></i> + إضافة مادة أخرى
                        </button>
                    </div>

                    <div class="card-body p-0" style="overflow: visible !important;">
                        <div class="table-responsive" style="overflow: visible !important;">
                            <table class="table table-hover align-middle mb-0" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width: 50px;">#</th>
                                        <th style="min-width: 380px;">اسم المادة أو الدواء (بحث ذكي مباشر) <span class="text-danger">*</span></th>
                                        <th style="width: 140px;" class="text-center">الوحدة</th>
                                        <th style="width: 180px;" class="text-center">الكمية المطلوبة <span class="text-danger">*</span></th>
                                        <th style="width: 70px;" class="text-center pe-3">إجراء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- تضاف الصفوف ديناميكياً بواسطة الجافاسكريبت مع تعبئة المواد الحالية --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer Summary & Submit -->
                <div class="card border-0 bg-light rounded-4 p-3 shadow-none">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="text-muted small">
                            <i class="fas fa-info-circle text-primary me-1"></i>
                            حفظ التعديلات سيقوم بتحديث الطلبية الحالية مع بقاء حالتها (قيد المراجعة) لاعتمادها من المخزن الرئيسي.
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('stock-transfers.requests.show', $stockTransferRequest) }}" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                                إلغاء التعديل
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 fw-bold shadow-sm">
                                <i class="fas fa-save me-1.5"></i> حفظ تعديلات الطلبية
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
/* تصميم القائمة الذكية للمواد على نسق التشخيص الدولي ICD-10 */
.product-autocomplete-wrapper {
    position: relative;
    overflow: visible !important;
}
.product-autocomplete-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    left: 0;
    width: 100% !important;
    min-width: 340px;
    z-index: 999999 !important;
    max-height: 360px;
    overflow-y: auto;
    background: #ffffff !important;
    border: 2px solid #005a9c !important;
    border-radius: 10px;
    box-shadow: 0 18px 40px rgba(0, 0, 0, 0.25), 0 4px 12px rgba(0, 90, 156, 0.15) !important;
}
.product-dropdown-header {
    background: linear-gradient(135deg, #f0f7ff, #e0f2fe);
    border-bottom: 2px solid #bae6fd;
    padding: 8px 14px;
    font-size: 0.82rem;
    font-weight: 700;
    color: #0369a1;
}
.product-item {
    padding: 10px 14px;
    border-bottom: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: right;
    background: #ffffff;
}
.product-item:hover, .product-item.active {
    background-color: #f0f7ff !important;
    border-right: 4px solid #005a9c;
}
.product-item:last-child {
    border-bottom: none;
}
.product-code-badge {
    font-family: monospace;
    font-weight: 800;
    font-size: 0.82rem;
    background: linear-gradient(135deg, #005a9c, #0284c7);
    color: #ffffff;
    padding: 2px 8px;
    border-radius: 5px;
    letter-spacing: 0.5px;
    display: inline-block;
}
.product-unit-tag {
    font-size: 0.78rem;
    color: #0369a1;
    background-color: #e0f2fe;
    padding: 2px 8px;
    border-radius: 5px;
    font-weight: 600;
    border: 1px solid #bae6fd;
}
.product-title-text {
    font-weight: 700;
    color: #0f172a;
    font-size: 0.95rem;
    line-height: 1.4;
}
.product-empty-hint {
    padding: 18px 14px;
    text-align: center;
    color: #64748b;
    background: #ffffff;
    font-size: 0.88rem;
}
</style>

<script>
    if (typeof window.__stockTransferEditInit === 'undefined') {
        window.__stockTransferEditInit = true;

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('transferEditForm');
            const addItemRowBtn = document.getElementById('addItemRow');
            const itemsTableBody = document.querySelector('#itemsTable tbody');
            const itemsCountBadge = document.getElementById('itemsCountBadge');

            @php
                $productsPayload = $products->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code ?: 'PRD-'.$p->id,
                        'unit' => $p->unit ?: 'وحدة',
                    ];
                })->values();
            @endphp
            const productsData = @json($productsPayload, JSON_UNESCAPED_UNICODE);
            const initialItems = @json($currentItems, JSON_UNESCAPED_UNICODE);

            let itemIndex = 0;

            function updateItemCounter() {
                const count = itemsTableBody.querySelectorAll('tr').length;
                itemsCountBadge.textContent = `${count} ${count === 1 ? 'مادة' : 'مواد'}`;
                itemsTableBody.querySelectorAll('tr').forEach((tr, idx) => {
                    const numEl = tr.querySelector('.row-num');
                    if (numEl) numEl.textContent = idx + 1;
                });
            }

            function escapeHtml(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            function createItemRow(index, initial = null) {
                const prodName = initial ? initial.name : '';
                const prodId = initial ? initial.product_id : '';
                const prodUnit = initial ? initial.unit : '-';
                const prodQty = initial ? initial.qty : 1;
                const hasItem = Boolean(prodId);

                return `
                    <tr id="row${index}" class="item-row">
                        <td class="ps-3 fw-bold text-muted row-num">${itemsTableBody.children.length + 1}</td>
                        <td>
                            <div class="product-autocomplete-wrapper">
                                <div class="input-group shadow-xs">
                                    <span class="input-group-text bg-white border-end-0 text-primary">
                                        <i class="fas fa-search product-search-icon"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control border-start-0 border-end-0 product-search-input" 
                                           placeholder="ابحث بالاسم أو الكود (مثال: سلاين أو كانولا)..." 
                                           value="${escapeHtml(prodName)}"
                                           autocomplete="off" 
                                           required>
                                    <button type="button" class="btn btn-outline-secondary border-start-0 btn-clear-product ${hasItem ? '' : 'd-none'}" title="مسح المادة">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <input type="hidden" 
                                       name="items[${index}][product_id]" 
                                       class="product-id-input" 
                                       value="${prodId}"
                                       required>
                                <div class="invalid-feedback small">يرجى اختيار مادة من القائمة الذكية.</div>
                                <div class="product-autocomplete-dropdown shadow-lg d-none"></div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge ${hasItem ? 'bg-primary-subtle text-primary border border-primary-subtle fw-bold' : 'bg-light text-secondary border'} rounded-pill px-3 py-1.5 product-unit-badge">
                                ${escapeHtml(prodUnit)}
                            </span>
                        </td>
                        <td>
                            <div class="input-group input-group-sm justify-content-center">
                                <button type="button" class="btn btn-outline-secondary px-2.5 btn-step-down rounded-start-pill">-</button>
                                <input type="number" 
                                       name="items[${index}][qty]" 
                                       class="form-control text-center fw-bold item-qty-input" 
                                       min="1" 
                                       value="${prodQty}" 
                                       style="max-width: 80px;" 
                                       required>
                                <button type="button" class="btn btn-outline-secondary px-2.5 btn-step-up rounded-end-pill">+</button>
                            </div>
                        </td>
                        <td class="text-center pe-3">
                            <button type="button" class="btn btn-outline-danger btn-sm rounded-circle remove-item-row" title="حذف هذا السطر" style="width: 32px; height: 32px; padding: 0;">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }

            // تحميل المواد الحالية في الطلب
            if (initialItems && initialItems.length > 0) {
                initialItems.forEach(item => {
                    itemsTableBody.insertAdjacentHTML('beforeend', createItemRow(itemIndex, item));
                    itemIndex++;
                });
                updateItemCounter();
            } else {
                itemsTableBody.insertAdjacentHTML('beforeend', createItemRow(itemIndex));
                itemIndex++;
                updateItemCounter();
            }

            // إغلاق أي قائمة مفتوحة عند النقر خارجها
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.product-autocomplete-wrapper')) {
                    document.querySelectorAll('.product-autocomplete-dropdown').forEach(dd => {
                        dd.classList.add('d-none');
                        dd.innerHTML = '';
                    });
                }
            });

            // إضافة سطر جديد
            addItemRowBtn.addEventListener('click', function () {
                itemsTableBody.insertAdjacentHTML('beforeend', createItemRow(itemIndex));
                itemIndex++;
                updateItemCounter();
                const lastInput = itemsTableBody.querySelector('tr:last-child .product-search-input');
                if (lastInput) lastInput.focus();
            });

            // البحث الذكي
            itemsTableBody.addEventListener('input', function (e) {
                if (e.target.classList.contains('product-search-input')) {
                    const input = e.target;
                    const wrapper = input.closest('.product-autocomplete-wrapper');
                    const dropdown = wrapper.querySelector('.product-autocomplete-dropdown');
                    const hiddenId = wrapper.querySelector('.product-id-input');
                    const clearBtn = wrapper.querySelector('.btn-clear-product');
                    const tr = input.closest('tr');
                    const unitBadge = tr.querySelector('.product-unit-badge');
                    const query = input.value.trim().toLowerCase();

                    if (query.length > 0) {
                        clearBtn.classList.remove('d-none');
                    } else {
                        clearBtn.classList.add('d-none');
                        hiddenId.value = '';
                        unitBadge.textContent = '-';
                        unitBadge.className = 'badge bg-light text-secondary border rounded-pill px-3 py-1.5 product-unit-badge';
                        dropdown.classList.add('d-none');
                        dropdown.innerHTML = '';
                        return;
                    }

                    const results = productsData.filter(p => {
                        return p.name.toLowerCase().includes(query) || (p.code && p.code.toLowerCase().includes(query));
                    }).slice(0, 15);

                    renderProductDropdown(wrapper, query, results);
                }
            });

            function renderProductDropdown(wrapper, query, results) {
                const dropdown = wrapper.querySelector('.product-autocomplete-dropdown');
                const input = wrapper.querySelector('.product-search-input');
                const hiddenId = wrapper.querySelector('.product-id-input');
                const tr = wrapper.closest('tr');
                const unitBadge = tr.querySelector('.product-unit-badge');
                const qtyInput = tr.querySelector('.item-qty-input');

                dropdown.innerHTML = '';

                if (results.length === 0) {
                    dropdown.innerHTML = `
                        <div class="product-empty-hint">
                            <i class="fas fa-box-open text-muted me-1"></i> لا توجد مادة مطابقة لـ "<strong>${escapeHtml(query)}</strong>"
                        </div>
                    `;
                    dropdown.classList.remove('d-none');
                    return;
                }

                const fragment = document.createDocumentFragment();

                const header = document.createElement('div');
                header.className = 'product-dropdown-header d-flex justify-content-between align-items-center';
                header.innerHTML = `
                    <span><i class="fas fa-boxes me-1 text-primary"></i> المواد المقترحة (${results.length})</span>
                    <small class="text-muted">انقر للاختيار أو تنقل بالأسهم</small>
                `;
                fragment.appendChild(header);

                results.forEach(product => {
                    const row = document.createElement('div');
                    row.className = 'product-item';
                    row.innerHTML = `
                        <div class="d-flex align-items-center justify-content-between mb-1 gap-2">
                            <span class="product-code-badge">${escapeHtml(product.code)}</span>
                            <span class="product-unit-tag"><i class="fas fa-tag me-1"></i>${escapeHtml(product.unit)}</span>
                        </div>
                        <div class="product-title-text">${escapeHtml(product.name)}</div>
                    `;

                    row.addEventListener('click', function () {
                        input.value = product.name;
                        hiddenId.value = product.id;
                        unitBadge.textContent = product.unit;
                        unitBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 product-unit-badge fw-bold';
                        input.classList.remove('is-invalid');
                        dropdown.classList.add('d-none');
                        dropdown.innerHTML = '';
                        if (qtyInput) qtyInput.focus();
                    });

                    fragment.appendChild(row);
                });

                dropdown.appendChild(fragment);
                dropdown.classList.remove('d-none');
            }

            // التنقل بالأسهم
            itemsTableBody.addEventListener('keydown', function (e) {
                if (e.target.classList.contains('product-search-input')) {
                    const input = e.target;
                    const wrapper = input.closest('.product-autocomplete-wrapper');
                    const dropdown = wrapper.querySelector('.product-autocomplete-dropdown');
                    if (!dropdown || dropdown.classList.contains('d-none')) return;

                    const items = dropdown.querySelectorAll('.product-item');
                    if (items.length === 0) return;

                    let activeIdx = -1;
                    items.forEach((it, idx) => {
                        if (it.classList.contains('active')) activeIdx = idx;
                    });

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        activeIdx = Math.min(activeIdx + 1, items.length - 1);
                        items.forEach((it, idx) => it.classList.toggle('active', idx === activeIdx));
                        if (items[activeIdx]) items[activeIdx].scrollIntoView({ block: 'nearest' });
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        activeIdx = Math.max(activeIdx - 1, 0);
                        items.forEach((it, idx) => it.classList.toggle('active', idx === activeIdx));
                        if (items[activeIdx]) items[activeIdx].scrollIntoView({ block: 'nearest' });
                    } else if (e.key === 'Enter') {
                        if (activeIdx >= 0 && items[activeIdx]) {
                            e.preventDefault();
                            items[activeIdx].click();
                        }
                    } else if (e.key === 'Escape') {
                        dropdown.classList.add('d-none');
                    }
                }
            });

            // الأحداث والنقر
            itemsTableBody.addEventListener('click', function (e) {
                const btn = e.target.closest('button');
                if (!btn) return;

                if (btn.classList.contains('btn-clear-product')) {
                    const wrapper = btn.closest('.product-autocomplete-wrapper');
                    const input = wrapper.querySelector('.product-search-input');
                    const hiddenId = wrapper.querySelector('.product-id-input');
                    const dropdown = wrapper.querySelector('.product-autocomplete-dropdown');
                    const tr = btn.closest('tr');
                    const unitBadge = tr.querySelector('.product-unit-badge');

                    input.value = '';
                    hiddenId.value = '';
                    btn.classList.add('d-none');
                    unitBadge.textContent = '-';
                    unitBadge.className = 'badge bg-light text-secondary border rounded-pill px-3 py-1.5 product-unit-badge';
                    dropdown.classList.add('d-none');
                    dropdown.innerHTML = '';
                    input.focus();
                } else if (btn.classList.contains('remove-item-row')) {
                    const tr = btn.closest('tr');
                    if (itemsTableBody.querySelectorAll('tr').length > 1) {
                        tr.remove();
                        updateItemCounter();
                    } else {
                        alert('يجب أن يحتوي الطلب على مادة واحدة على الأقل.');
                    }
                } else if (btn.classList.contains('btn-step-up')) {
                    const tr = btn.closest('tr');
                    const qtyInput = tr.querySelector('.item-qty-input');
                    qtyInput.value = Math.max(1, parseInt(qtyInput.value || 0) + 1);
                } else if (btn.classList.contains('btn-step-down')) {
                    const tr = btn.closest('tr');
                    const qtyInput = tr.querySelector('.item-qty-input');
                    qtyInput.value = Math.max(1, parseInt(qtyInput.value || 1) - 1);
                }
            });

            // التحقق قبل الإرسال
            form.addEventListener('submit', function (e) {
                let hasError = false;
                const rows = itemsTableBody.querySelectorAll('tr');

                if (rows.length === 0) {
                    alert('يجب إضافة مادة واحدة على الأقل في الطلب.');
                    e.preventDefault();
                    return;
                }

                rows.forEach(tr => {
                    const hiddenId = tr.querySelector('.product-id-input');
                    const searchInput = tr.querySelector('.product-search-input');
                    const qtyInput = tr.querySelector('.item-qty-input');

                    if (!hiddenId.value) {
                        searchInput.classList.add('is-invalid');
                        searchInput.focus();
                        hasError = true;
                    } else {
                        searchInput.classList.remove('is-invalid');
                    }

                    if (!qtyInput.value || parseInt(qtyInput.value) < 1) {
                        qtyInput.classList.add('is-invalid');
                        hasError = true;
                    }
                });

                if (hasError) {
                    e.preventDefault();
                    alert('يرجى اختيار المواد من القائمة الذكية والتأكد من تحديد كمية صحيحة لكل مادة.');
                }
            });
        });
    }
</script>
@endsection
