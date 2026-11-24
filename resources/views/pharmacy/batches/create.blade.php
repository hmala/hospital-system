@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-file-invoice-dollar me-2"></i>توريد شحنة أدوية بنظام الوحدات المزدوج (FEFO & Dual-Unit Supply)
            </h2>
            <p class="text-muted small mb-0">بحث ذكي بنظام ICD-10 مع بيان الرمز الوطني ومعامل التعبئة وأسعار الضمان والكاش واحتساب البونص</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pharmacy.batches.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-boxes me-1"></i> لوحة شحنات الصيدلية
            </a>
            <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-pills me-1"></i> دليل الأدوية
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <h6 class="fw-bold mb-2"><i class="fas fa-exclamation-circle me-1"></i> يرجى تصحيح الأخطاء التالية:</h6>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('pharmacy.batches.store') }}" method="POST" id="batchSupplyForm">
        @csrf

        <!-- 1. بيانات الفاتورة / المورد العامة -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold text-primary mb-0">
                    <i class="fas fa-info-circle me-2"></i>بيانات إذن الاستلام والتوريد العام (GRN Inward Details)
                </h6>
            </div>
            <div class="card-body p-4 pt-1">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">اسم المورد / شركة التوزيع <span class="text-danger">*</span></label>
                        <input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name') }}" placeholder="مثال: شركة الرافدين للمستلزمات الطبية" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">رقم الفاتورة الورقية / إذن التوريد</label>
                        <input type="text" name="invoice_number" class="form-control font-monospace" value="{{ old('invoice_number') }}" placeholder="مثال: INV-98214">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">تاريخ الاستلام والتوريد <span class="text-danger">*</span></label>
                        <input type="date" name="received_at" class="form-control" value="{{ old('received_at', date('Y-m-d')) }}" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold">حالة الوجبات الأولية <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>نشطة (متاحة للصرف فوراً)</option>
                            <option value="quarantined" {{ old('status') === 'quarantined' ? 'selected' : '' }}>محجورة (قيد الفحص المخبري)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. جدول بنود الأدوية المستلمة في الفاتورة -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold text-success mb-0">
                    <i class="fas fa-list-ol me-2"></i>قائمة الأدوية والشحنات المستلمة (بحث ذكي بنمط ICD-10)
                </h6>
                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" id="addRowBtn">
                    <i class="fas fa-plus me-1"></i> إضافة دواء آخر للقائمة
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="overflow-x: auto; min-height: 250px;">
                    <table class="table table-hover align-middle mb-0" id="itemsTable">
                        <thead class="table-light small">
                            <tr>
                                <th style="width: 3%;">#</th>
                                <th style="width: 25%;">الدواء ومعامل التعبئة (بحث ICD-10) <span class="text-danger">*</span></th>
                                <th style="width: 12%;">رقم التشغيلة (Batch) <span class="text-danger">*</span></th>
                                <th style="width: 10%;">الصلاحية <span class="text-danger">*</span></th>
                                <th style="width: 8%;">أشرطة/العلبة <span class="text-danger">*</span></th>
                                <th style="width: 13%;">الكمية + بونص مجاني <span class="text-danger">*</span></th>
                                <th style="width: 13%;">سعر شراء العلبة <span class="text-danger">*</span></th>
                                <th style="width: 13%;">أسعار البيع (كاش / ضمان)</th>
                                <th style="width: 5%;">الإجمالي</th>
                                <th style="width: 3%;" class="text-center">حذف</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <!-- Dynamic rows injected by JS -->
                        </tbody>
                    </table>
                </div>

                <!-- شريط الإجماليات وملخص الفاتورة القياسي -->
                <div class="p-3 bg-light border-top d-flex flex-wrap justify-content-between align-items-center">
                    <div class="d-flex flex-wrap gap-4">
                        <span class="small">عدد الأصناف: <strong class="text-primary fs-6" id="totalItemsCount">0</strong> صنف</span>
                        <span class="small">إجمالي العلب المشتراة: <strong class="text-success fs-6" id="totalQtyCount">0</strong> علبة</span>
                        <span class="small">إجمالي البونص المجاني: <strong class="text-info fs-6" id="totalBonusCount">0</strong> علبة</span>
                        <span class="small">إجمالي الأشرطة المعادل: <strong class="text-secondary fs-6" id="totalSubUnitsCount">0</strong> شريط/قطعة</span>
                        <span class="small">إجمالي قيمة الفاتورة: <strong class="text-danger fs-6" id="totalCostSum">0.00</strong> د.ع</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-success" id="addAnotherRowBtn">
                            <i class="fas fa-plus-circle me-1"></i> إضافة صنف إضافي
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- زر الحفظ النهائي -->
        <div class="d-flex justify-content-between align-items-center mb-5">
            <a href="{{ route('pharmacy.batches.index') }}" class="btn btn-outline-secondary px-4">
                <i class="fas fa-times me-1"></i> إلغاء
            </a>
            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" id="submitBtn">
                <i class="fas fa-check-circle me-1"></i> اعتماد وتوريد الشحنة إلى الصيدلية
            </button>
        </div>
    </form>
</div>

@push('styles')
<style>
/* تصميم القائمة الذكية للأدوية على نسق التشخيص الدولي ICD-10 */
.medicine-autocomplete-wrapper {
    position: relative;
    overflow: visible !important;
}
.medicine-autocomplete-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    right: 0;
    left: 0;
    width: 100% !important;
    min-width: 380px;
    z-index: 999999 !important;
    max-height: 330px;
    overflow-y: auto;
    background: #ffffff !important;
    border: 2px solid #0d6efd !important;
    border-radius: 12px;
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.22), 0 4px 12px rgba(13, 110, 253, 0.15) !important;
}
.medicine-dropdown-header {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    border-bottom: 2px solid #bfdbfe;
    padding: 6px 12px;
    font-size: 0.76rem;
    font-weight: 700;
    color: #1e40af;
}
.medicine-search-item {
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: all 0.12s ease;
    text-align: right;
    background: #ffffff;
}
.medicine-search-item:hover, .medicine-search-item.active {
    background: #f0fdf4 !important;
    border-right: 4px solid #16a34a !important;
}
</style>
@endpush

@push('scripts')
<script>
// تخزين بيانات الأدوية للمحرك الذكي بنمط ICD-10
const medicinesData = @json($medicines);
const prefilledMedId = "{{ $selectedMedicineId ?? '' }}";

document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('itemsTableBody');
    let rowIndex = 0;

    function createRow(idx, prefillMed = null) {
        return `
        <tr class="batch-row" data-index="${idx}">
            <td class="text-muted fw-bold row-num text-center">${tableBody.children.length + 1}</td>
            <td>
                <div class="medicine-autocomplete-wrapper">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-primary"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control medicine-search-input border-start-0" placeholder="ابحث باسم الدواء، الكود، الباركود..." autocomplete="off" value="${prefillMed ? (prefillMed.name + ' (' + (prefillMed.dosage_form || '') + ' ' + (prefillMed.strength || '') + ')') : ''}">
                        <button type="button" class="btn btn-outline-secondary btn-clear-medicine ${prefillMed ? '' : 'd-none'}" title="مسح"><i class="fas fa-times"></i></button>
                    </div>
                    <input type="hidden" name="items[${idx}][medicine_id]" class="medicine-id-input" value="${prefillMed ? prefillMed.id : ''}" required>
                    <div class="medicine-autocomplete-dropdown d-none"></div>
                </div>
                <div class="medicine-details-badge mt-1 d-flex flex-wrap gap-2 align-items-center" style="font-size: 0.72rem;">
                    <span class="conv-text text-muted">
                        <i class="fas fa-boxes text-primary me-1"></i>
                        <span class="conv-val">${prefillMed ? ('1 ' + (prefillMed.main_unit || 'علبة') + ' = ' + (prefillMed.sub_units_count || 1) + ' ' + (prefillMed.sub_unit || 'شريط')) : 'ابحث واختر الدواء'}</span>
                    </span>
                    <span class="hi-price-badge text-success fw-bold ${prefillMed && prefillMed.hi_price ? '' : 'd-none'}">
                        <i class="fas fa-shield-alt me-1"></i>ضمان: <span class="hi-price-val">${prefillMed ? (parseFloat(prefillMed.hi_price).toLocaleString()) : '0'}</span> د.ع
                    </span>
                </div>
            </td>
            <td>
                <input type="text" name="items[${idx}][batch_number]" class="form-control form-select-sm font-monospace" placeholder="LOT-2026-X" required>
            </td>
            <td>
                <input type="date" name="items[${idx}][expiry_date]" class="form-control form-select-sm" required>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" name="items[${idx}][sub_units_count]" class="form-control text-center fw-bold sub-count-input" min="1" value="${prefillMed ? (prefillMed.sub_units_count || 1) : 1}" title="عدد الأشرطة بالعلبة">
                    <span class="input-group-text bg-light p-1 sub-unit-label" style="font-size: 0.7rem;">${prefillMed ? (prefillMed.sub_unit || 'شريط') : 'شريط'}</span>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text bg-light text-muted" title="العلب المدفوعة" style="font-size: 0.72rem;">شراء</span>
                    <input type="number" name="items[${idx}][initial_quantity]" class="form-control fw-bold text-center qty-input" min="1" value="10" required>
                </div>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text bg-success bg-opacity-10 text-success" title="علب مجانية بونص" style="font-size: 0.72rem;">بونص</span>
                    <input type="number" name="items[${idx}][bonus_quantity]" class="form-control text-center bonus-input" min="0" value="0" placeholder="0">
                </div>
                <div class="text-secondary fw-bold text-center" style="font-size: 0.72rem;">
                    <span class="row-total-sub-units">= ${prefillMed ? (10 * (prefillMed.sub_units_count || 1)) : 10}</span> <span class="sub-unit-label-total">${prefillMed ? (prefillMed.sub_unit || 'شريط') : 'شريط'}</span>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm mb-1">
                    <input type="number" step="0.01" name="items[${idx}][purchase_price]" class="form-control text-center price-input fw-bold" min="0" value="${prefillMed ? (parseFloat(prefillMed.cost_price || 0).toFixed(2)) : '0.00'}" required>
                    <span class="input-group-text bg-light text-muted" style="font-size: 0.75rem;">د.ع</span>
                </div>
                <div class="derived-cost text-primary fw-bold" style="font-size: 0.75rem;">
                    <i class="fas fa-tag me-1"></i>تكلفة <span class="sub-unit-label-cost">${prefillMed ? (prefillMed.sub_unit || 'شريط') : 'شريط'}</span>: <span class="derived-sub-cost">0.00</span> د.ع
                </div>
            </td>
            <td>
                <div class="mb-1 d-flex gap-1">
                    <input type="number" step="0.01" name="items[${idx}][sale_price]" class="form-control form-control-sm text-center sale-price-input" placeholder="بيع العلبة" value="${prefillMed ? (parseFloat(prefillMed.sale_price || 0).toFixed(2)) : ''}" title="سعر بيع العلبة كاش">
                    <input type="number" step="0.01" name="items[${idx}][sub_unit_sale_price]" class="form-control form-control-sm text-center sub-sale-price-input" placeholder="بيع الشريط" value="${prefillMed ? (parseFloat(prefillMed.sub_unit_sale_price || 0).toFixed(2)) : ''}" title="سعر بيع الشريط كاش">
                </div>
                <div class="form-check form-switch m-0" style="font-size: 0.72rem;">
                    <input class="form-check-input update-price-switch" type="checkbox" name="items[${idx}][update_prices]" value="1" id="sw_${idx}" checked>
                    <label class="form-check-label text-muted" for="sw_${idx}">تحديث بالدليل</label>
                </div>
            </td>
            <td class="text-end">
                <span class="fw-bold small row-total text-dark">0.00</span>
                <div class="text-muted" style="font-size: 0.7rem;">د.ع</div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm border-0 delete-row-btn" title="حذف السطر">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
        `;
    }

    function addRow(prefillMed = null) {
        rowIndex++;
        tableBody.insertAdjacentHTML('beforeend', createRow(rowIndex, prefillMed));
        const tr = tableBody.querySelector(`tr[data-index="${rowIndex}"]`);
        bindRowEvents(tr);
        recalcRow(tr);
        recalcTotals();
        return tr;
    }

    function bindRowEvents(tr) {
        const searchInput = tr.querySelector('.medicine-search-input');
        const clearBtn = tr.querySelector('.btn-clear-medicine');
        const dropdown = tr.querySelector('.medicine-autocomplete-dropdown');
        const hiddenId = tr.querySelector('.medicine-id-input');

        const qtyInput = tr.querySelector('.qty-input');
        const bonusInput = tr.querySelector('.bonus-input');
        const subCountInput = tr.querySelector('.sub-count-input');
        const priceInput = tr.querySelector('.price-input');
        const salePriceInput = tr.querySelector('.sale-price-input');
        const deleteBtn = tr.querySelector('.delete-row-btn');

        // البحث اللحظي بنمط ICD-10
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            if (query.length < 1) {
                clearBtn.classList.add('d-none');
                dropdown.classList.add('d-none');
                dropdown.innerHTML = '';
                return;
            }

            clearBtn.classList.remove('d-none');

            // تصفية الأدوية بالاسم أو الرمز الوطني أو الاسم العلمي أو الباركود
            const matches = medicinesData.filter(m => {
                const nameMatch = (m.name || '').toLowerCase().includes(query);
                const genericMatch = (m.generic_name || '').toLowerCase().includes(query);
                const codeMatch = (m.national_code || '').toLowerCase().includes(query);
                const barcodeMatch = (m.barcode || '').toLowerCase().includes(query);
                return nameMatch || genericMatch || codeMatch || barcodeMatch;
            }).slice(0, 20); // أفضل 20 نتيجة لأقصى سرعة واستجابة

            if (matches.length === 0) {
                dropdown.innerHTML = `
                    <div class="medicine-dropdown-header d-flex justify-content-between">
                        <span>نتائج البحث</span>
                        <span class="text-danger">0 دواء مطابقة</span>
                    </div>
                    <div class="p-3 text-center text-muted small">لم يتم العثور على أدوية مطابقة لهذا البحث.</div>
                `;
            } else {
                let html = `
                    <div class="medicine-dropdown-header d-flex justify-content-between">
                        <span><i class="fas fa-list-check me-1"></i>الأدوية المطابقة (التنقل بالأسهم ⬆ ⬇ واختيار بـ Enter)</span>
                        <span class="badge bg-primary">${matches.length} نتيجة</span>
                    </div>
                `;

                matches.forEach((m, idx) => {
                    const subCount = m.sub_units_count || 1;
                    const hiPriceFormatted = m.hi_price ? (parseFloat(m.hi_price).toLocaleString() + ' د.ع') : 'غير مشمول';
                    const cashPriceFormatted = parseFloat(m.sale_price || 0).toLocaleString() + ' د.ع';

                    html += `
                        <div class="medicine-search-item ${idx === 0 ? 'active' : ''}" data-id="${m.id}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-bold text-dark">${m.name}</span>
                                    <span class="badge bg-light text-primary border ms-1">${m.dosage_form || 'دواء'} - ${m.strength || ''}</span>
                                    ${m.national_code ? `<span class="badge bg-secondary bg-opacity-10 text-secondary font-monospace ms-1">#${m.national_code}</span>` : ''}
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-info bg-opacity-10 text-info font-monospace">📦 1 ${m.main_unit || 'علبة'} = ${subCount} ${m.sub_unit || 'شريط'}</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1" style="font-size: 0.74rem;">
                                <span class="text-muted">${m.generic_name || 'بدون اسم علمي'}</span>
                                <div class="d-flex gap-2">
                                    <span class="badge bg-success bg-opacity-10 text-success"><i class="fas fa-shield-alt me-1"></i>ضمان: ${hiPriceFormatted}</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">كاش: ${cashPriceFormatted}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });

                dropdown.innerHTML = html;

                // ربط النقر لاختيار الدواء
                dropdown.querySelectorAll('.medicine-search-item').forEach(itemElem => {
                    itemElem.addEventListener('click', function () {
                        const medId = parseInt(this.dataset.id);
                        const selectedMed = medicinesData.find(m => m.id === medId);
                        if (selectedMed) selectMedicine(tr, selectedMed);
                    });
                });
            }

            dropdown.classList.remove('d-none');
        });

        // زر المسح
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            hiddenId.value = '';
            clearBtn.classList.add('d-none');
            dropdown.classList.add('d-none');
            dropdown.innerHTML = '';
            tr.querySelector('.conv-val').textContent = 'ابحث واختر الدواء';
            tr.querySelector('.hi-price-badge').classList.add('d-none');
            searchInput.focus();
        });

        // التنقل بالأسهم واختيار الدواء بالـ Enter مثل ICD-10
        searchInput.addEventListener('keydown', function (e) {
            if (dropdown.classList.contains('d-none')) return;
            const items = dropdown.querySelectorAll('.medicine-search-item');
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
        });

        qtyInput.addEventListener('input', () => recalcRow(tr));
        bonusInput.addEventListener('input', () => recalcRow(tr));
        subCountInput.addEventListener('input', () => recalcRow(tr));

        priceInput.addEventListener('input', function() {
            const subCount = parseInt(subCountInput.value) || 1;
            const saleInput = tr.querySelector('.sale-price-input');
            const subSaleInput = tr.querySelector('.sub-sale-price-input');
            if (saleInput.value && (!subSaleInput.value || parseFloat(subSaleInput.value) === 0)) {
                subSaleInput.value = (parseFloat(saleInput.value) / subCount).toFixed(2);
            }
            recalcRow(tr);
        });

        salePriceInput.addEventListener('input', function() {
            const subCount = parseInt(subCountInput.value) || 1;
            const subSaleInput = tr.querySelector('.sub-sale-price-input');
            if (this.value && subCount > 0) {
                subSaleInput.value = (parseFloat(this.value) / subCount).toFixed(2);
            }
        });

        deleteBtn.addEventListener('click', function () {
            if (tableBody.children.length > 1) {
                tr.remove();
                renumberRows();
                recalcTotals();
            } else {
                alert('يجب أن تحتوي قائمة التوريد على سطر واحد على الأقل.');
            }
        });
    }

    function selectMedicine(tr, med) {
        const searchInput = tr.querySelector('.medicine-search-input');
        const hiddenId = tr.querySelector('.medicine-id-input');
        const clearBtn = tr.querySelector('.btn-clear-medicine');
        const dropdown = tr.querySelector('.medicine-autocomplete-dropdown');

        searchInput.value = `${med.name} (${med.dosage_form || ''} ${med.strength || ''})`;
        hiddenId.value = med.id;
        clearBtn.classList.remove('d-none');
        dropdown.classList.add('d-none');
        dropdown.innerHTML = '';

        const subCount = parseInt(med.sub_units_count) || 1;
        const mainUnit = med.main_unit || 'علبة';
        const subUnit = med.sub_unit || 'شريط';

        tr.querySelector('.conv-val').textContent = `1 ${mainUnit} = ${subCount} ${subUnit}`;
        tr.querySelector('.sub-count-input').value = subCount;
        tr.querySelector('.sub-unit-label').textContent = subUnit;
        tr.querySelector('.sub-unit-label-total').textContent = subUnit;
        tr.querySelector('.sub-unit-label-cost').textContent = subUnit;

        const hiBadge = tr.querySelector('.hi-price-badge');
        if (med.hi_price && parseFloat(med.hi_price) > 0) {
            hiBadge.classList.remove('d-none');
            tr.querySelector('.hi-price-val').textContent = parseFloat(med.hi_price).toLocaleString();
        } else {
            hiBadge.classList.add('d-none');
        }

        const priceInput = tr.querySelector('.price-input');
        if (parseFloat(priceInput.value) === 0 && med.cost_price > 0) {
            priceInput.value = parseFloat(med.cost_price).toFixed(2);
        }

        const saleInput = tr.querySelector('.sale-price-input');
        if (!saleInput.value || parseFloat(saleInput.value) === 0) {
            saleInput.value = parseFloat(med.sale_price || 0).toFixed(2);
        }

        const subSaleInput = tr.querySelector('.sub-sale-price-input');
        if (!subSaleInput.value || parseFloat(subSaleInput.value) === 0) {
            subSaleInput.value = parseFloat(med.sub_unit_sale_price || 0).toFixed(2);
        }

        recalcRow(tr);
    }

    function recalcRow(tr) {
        const subCountInput = tr.querySelector('.sub-count-input');
        const subCount = parseInt(subCountInput ? subCountInput.value : 1) || 1;

        const qty = parseFloat(tr.querySelector('.qty-input')?.value) || 0;
        const bonus = parseFloat(tr.querySelector('.bonus-input')?.value) || 0;
        const price = parseFloat(tr.querySelector('.price-input')?.value) || 0;

        const totalRowCost = qty * price;
        const totalReceived = qty + bonus;
        const totalStrips = totalReceived * subCount;

        const totalStripsElem = tr.querySelector('.row-total-sub-units');
        if (totalStripsElem) totalStripsElem.textContent = `= ${totalStrips}`;

        const effectiveBoxCost = totalReceived > 0 ? (totalRowCost / totalReceived) : price;
        const stripCost = subCount > 0 ? (effectiveBoxCost / subCount) : effectiveBoxCost;

        tr.querySelector('.derived-sub-cost').textContent = stripCost.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        tr.querySelector('.row-total').textContent = totalRowCost.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

        recalcTotals();
    }

    function renumberRows() {
        Array.from(tableBody.children).forEach((tr, idx) => {
            tr.querySelector('.row-num').textContent = idx + 1;
        });
    }

    function recalcTotals() {
        let totalItems = tableBody.children.length;
        let totalQty = 0;
        let totalBonus = 0;
        let totalSubUnits = 0;
        let totalSum = 0;

        Array.from(tableBody.children).forEach(tr => {
            const subCountInput = tr.querySelector('.sub-count-input');
            const subCount = parseInt(subCountInput ? subCountInput.value : 1) || 1;

            const qty = parseFloat(tr.querySelector('.qty-input')?.value) || 0;
            const bonus = parseFloat(tr.querySelector('.bonus-input')?.value) || 0;
            const price = parseFloat(tr.querySelector('.price-input')?.value) || 0;

            totalQty += qty;
            totalBonus += bonus;
            totalSubUnits += ((qty + bonus) * subCount);
            totalSum += (qty * price);
        });

        document.getElementById('totalItemsCount').textContent = totalItems;
        document.getElementById('totalQtyCount').textContent = totalQty.toLocaleString();
        document.getElementById('totalBonusCount').textContent = totalBonus.toLocaleString();
        document.getElementById('totalSubUnitsCount').textContent = totalSubUnits.toLocaleString();
        document.getElementById('totalCostSum').textContent = totalSum.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // إغلاق أي قائمة مفتوحة عند النقر في الخارج
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.medicine-autocomplete-wrapper')) {
            document.querySelectorAll('.medicine-autocomplete-dropdown').forEach(dd => {
                dd.classList.add('d-none');
            });
        }
    });

    // أزرار إضافة سطر
    document.getElementById('addRowBtn').addEventListener('click', () => {
        const newTr = addRow();
        const input = newTr.querySelector('.medicine-search-input');
        if (input) input.focus();
    });

    document.getElementById('addAnotherRowBtn').addEventListener('click', () => {
        const newTr = addRow();
        const input = newTr.querySelector('.medicine-search-input');
        if (input) input.focus();
    });

    // التهيئة المبدئية للسطر الأول
    let prefillMed = null;
    if (prefilledMedId) {
        prefillMed = medicinesData.find(m => m.id === parseInt(prefilledMedId));
    }
    addRow(prefillMed);
});
</script>
@endpush
@endsection
