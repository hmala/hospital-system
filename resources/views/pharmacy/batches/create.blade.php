@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-file-invoice-dollar me-2"></i>توريد شحنة أدوية بنظام الوحدات المزدوج (FEFO & Dual-Unit Supply)
            </h2>
            <p class="text-muted small mb-0">إدخال قائمة التوريد واشتقاق تكلفة الشريط/القطعة آلياً واحتساب البونص وتحديث أسعار البيع المعتمدة</p>
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
                    <i class="fas fa-list-ol me-2"></i>قائمة الأدوية والشحنات المستلمة (البنود والتسعير والكميات)
                </h6>
                <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" id="addRowBtn">
                    <i class="fas fa-plus me-1"></i> إضافة دواء آخر للقائمة
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="itemsTable">
                        <thead class="table-light small">
                            <tr>
                                <th style="width: 3%;">#</th>
                                <th style="width: 22%;">الدواء ومعامل التعبئة <span class="text-danger">*</span></th>
                                <th style="width: 12%;">رقم التشغيلة (Batch) <span class="text-danger">*</span></th>
                                <th style="width: 10%;">الصلاحية <span class="text-danger">*</span></th>
                                <th style="width: 9%;">أشرطة/العلبة <span class="text-danger">*</span></th>
                                <th style="width: 14%;">الكمية + بونص مجاني <span class="text-danger">*</span></th>
                                <th style="width: 13%;">سعر شراء العلبة <span class="text-danger">*</span></th>
                                <th style="width: 11%;">أسعار البيع للجمهور</th>
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

<!-- Raw medicines options buffer for fast DOM cloning -->
<template id="medicinesOptionsTemplate">
    <option value="">-- اختر الدواء من الدليل --</option>
    @foreach($medicines as $med)
        <option value="{{ $med->id }}"
                data-cost-price="{{ $med->cost_price ?? 0 }}"
                data-sale-price="{{ $med->sale_price ?? 0 }}"
                data-sub-unit-sale-price="{{ $med->sub_unit_sale_price ?? 0 }}"
                data-main-unit="{{ $med->main_unit ?? 'علبة' }}"
                data-sub-unit="{{ $med->sub_unit ?? 'شريط' }}"
                data-sub-units-count="{{ max(1, $med->sub_units_count ?? 1) }}"
                {{ old('medicine_id', $selectedMedicineId) == $med->id ? 'selected' : '' }}>
            {{ $med->name }} ({{ $med->dosage_form }} - {{ $med->strength }})
            [{{ $med->main_unit }} = {{ $med->sub_units_count }} {{ $med->sub_unit }}]
            @if($med->national_code) | #{{ $med->national_code }} @endif
        </option>
    @endforeach
</template>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('itemsTableBody');
    const optionsHtml = document.getElementById('medicinesOptionsTemplate').innerHTML;
    let rowIndex = 0;

    function addRow(prefillMedId = null) {
        rowIndex++;
        const tr = document.createElement('tr');
        tr.className = 'batch-row';
        tr.dataset.index = rowIndex;

        tr.innerHTML = `
            <td class="text-muted fw-bold row-num text-center">${tableBody.children.length + 1}</td>
            <td>
                <select name="items[${rowIndex}][medicine_id]" class="form-select form-select-sm medicine-select mb-1" required>
                    ${optionsHtml}
                </select>
                <div class="conversion-info d-flex align-items-center gap-1 text-muted" style="font-size: 0.75rem;">
                    <i class="fas fa-boxes text-primary"></i>
                    <span class="conv-text">اختر الدواء لمعاينة التعبئة</span>
                </div>
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][batch_number]" class="form-control form-select-sm font-monospace" placeholder="مثال: LOT-2026-X" required>
            </td>
            <td>
                <input type="date" name="items[${rowIndex}][expiry_date]" class="form-control form-select-sm" required>
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <input type="number" name="items[${rowIndex}][sub_units_count]" class="form-control text-center fw-bold sub-count-input" min="1" value="1" title="عدد الأشرطة أو القطع في العلبة">
                    <span class="input-group-text bg-light p-1 sub-unit-label" style="font-size: 0.7rem;">شريط</span>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text bg-light text-muted" title="العلب المدفوعة" style="font-size: 0.75rem;">شراء</span>
                    <input type="number" name="items[${rowIndex}][initial_quantity]" class="form-control fw-bold text-center qty-input" min="1" value="10" required>
                </div>
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text bg-success bg-opacity-10 text-success" title="علب مجانية بونص" style="font-size: 0.75rem;">بونص</span>
                    <input type="number" name="items[${rowIndex}][bonus_quantity]" class="form-control text-center bonus-input" min="0" value="0" placeholder="0">
                </div>
                <div class="text-secondary fw-bold text-center" style="font-size: 0.72rem;">
                    <span class="row-total-sub-units">= 10</span> <span class="sub-unit-label-total">شريط</span>
                </div>
            </td>
            <td>
                <div class="input-group input-group-sm mb-1">
                    <input type="number" step="0.01" name="items[${rowIndex}][purchase_price]" class="form-control text-center price-input fw-bold" min="0" value="0.00" required>
                    <span class="input-group-text bg-light text-muted" style="font-size: 0.75rem;">د.ع</span>
                </div>
                <div class="derived-cost text-primary fw-bold" style="font-size: 0.75rem;">
                    <i class="fas fa-tag me-1"></i>تكلفة <span class="sub-unit-label">الشريط</span>: <span class="derived-sub-cost">0.00</span> د.ع
                </div>
            </td>
            <td>
                <div class="mb-1 d-flex gap-1">
                    <input type="number" step="0.01" name="items[${rowIndex}][sale_price]" class="form-control form-control-sm text-center sale-price-input" placeholder="بيع العلبة" title="سعر بيع العلبة للجمهور">
                    <input type="number" step="0.01" name="items[${rowIndex}][sub_unit_sale_price]" class="form-control form-control-sm text-center sub-sale-price-input" placeholder="بيع الشريط" title="سعر بيع الشريط/القطعة">
                </div>
                <div class="form-check form-switch m-0" style="font-size: 0.72rem;">
                    <input class="form-check-input update-price-switch" type="checkbox" name="items[${rowIndex}][update_prices]" value="1" id="sw_${rowIndex}" checked>
                    <label class="form-check-label text-muted" for="sw_${rowIndex}">تحديث بدليل الأدوية</label>
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
        `;

        tableBody.appendChild(tr);

        // Preselect medicine if specified
        const medSelect = tr.querySelector('.medicine-select');
        if (prefillMedId) {
            medSelect.value = prefillMedId;
            populateMedicineData(tr);
        }

        bindRowEvents(tr);
        recalcTotals();
    }

    function populateMedicineData(tr) {
        const medSelect = tr.querySelector('.medicine-select');
        const opt = medSelect.options[medSelect.selectedIndex];
        if (!opt || !opt.value) return;

        const mainUnit = opt.dataset.mainUnit || 'علبة';
        const subUnit = opt.dataset.subUnit || 'شريط';
        const subCount = parseInt(opt.dataset.subUnitsCount) || 1;
        const defaultCost = parseFloat(opt.dataset.costPrice) || 0;
        const defaultSale = parseFloat(opt.dataset.salePrice) || 0;
        const defaultSubSale = parseFloat(opt.dataset.subUnitSalePrice) || 0;

        tr.querySelector('.conv-text').textContent = `1 ${mainUnit} = ${subCount} ${subUnit}`;
        tr.querySelector('.sub-unit-label').textContent = subUnit;
        const subCountInput = tr.querySelector('.sub-count-input');
        if (subCountInput) subCountInput.value = subCount;
        const subUnitTotalLabel = tr.querySelector('.sub-unit-label-total');
        if (subUnitTotalLabel) subUnitTotalLabel.textContent = subUnit;

        const priceInput = tr.querySelector('.price-input');
        if (parseFloat(priceInput.value) === 0 && defaultCost > 0) {
            priceInput.value = defaultCost.toFixed(2);
        }

        const saleInput = tr.querySelector('.sale-price-input');
        if (!saleInput.value || parseFloat(saleInput.value) === 0) {
            saleInput.value = defaultSale.toFixed(2);
        }

        const subSaleInput = tr.querySelector('.sub-sale-price-input');
        if (!subSaleInput.value || parseFloat(subSaleInput.value) === 0) {
            subSaleInput.value = defaultSubSale.toFixed(2);
        }

        recalcRow(tr);
    }

    function bindRowEvents(tr) {
        const medSelect = tr.querySelector('.medicine-select');
        const qtyInput = tr.querySelector('.qty-input');
        const bonusInput = tr.querySelector('.bonus-input');
        const subCountInput = tr.querySelector('.sub-count-input');
        const priceInput = tr.querySelector('.price-input');
        const salePriceInput = tr.querySelector('.sale-price-input');
        const deleteBtn = tr.querySelector('.delete-row-btn');

        medSelect.addEventListener('change', function () {
            populateMedicineData(tr);
        });

        qtyInput.addEventListener('input', () => recalcRow(tr));
        bonusInput.addEventListener('input', () => recalcRow(tr));
        if (subCountInput) {
            subCountInput.addEventListener('input', () => recalcRow(tr));
        }
        priceInput.addEventListener('input', function() {
            // Auto suggest strip price if strip sale price is empty
            const opt = medSelect.options[medSelect.selectedIndex];
            const subCount = opt ? (parseInt(opt.dataset.subUnitsCount) || 1) : 1;
            const subSaleInput = tr.querySelector('.sub-sale-price-input');
            const saleInput = tr.querySelector('.sale-price-input');

            if (saleInput.value && (!subSaleInput.value || parseFloat(subSaleInput.value) === 0)) {
                subSaleInput.value = (parseFloat(saleInput.value) / subCount).toFixed(2);
            }

            recalcRow(tr);
        });

        salePriceInput.addEventListener('input', function() {
            const opt = medSelect.options[medSelect.selectedIndex];
            const subCount = opt ? (parseInt(opt.dataset.subUnitsCount) || 1) : 1;
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

        recalcRow(tr);
    }

    function recalcRow(tr) {
        const medSelect = tr.querySelector('.medicine-select');
        const opt = medSelect.options[medSelect.selectedIndex];
        const subCountInput = tr.querySelector('.sub-count-input');
        const subCount = subCountInput ? (parseInt(subCountInput.value) || 1) : (opt ? (parseInt(opt.dataset.subUnitsCount) || 1) : 1);

        const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
        const bonus = parseFloat(tr.querySelector('.bonus-input').value) || 0;
        const price = parseFloat(tr.querySelector('.price-input').value) || 0;

        const totalRowCost = qty * price;
        const totalReceived = qty + bonus;
        const totalStrips = totalReceived * subCount;

        const totalStripsElem = tr.querySelector('.row-total-sub-units');
        if (totalStripsElem) {
            totalStripsElem.textContent = `= ${totalStrips}`;
        }

        // Effective box cost after bonus
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
            const medSelect = tr.querySelector('.medicine-select');
            const opt = medSelect ? medSelect.options[medSelect.selectedIndex] : null;
            const subCountInput = tr.querySelector('.sub-count-input');
            const subCount = subCountInput ? (parseInt(subCountInput.value) || 1) : (opt ? (parseInt(opt.dataset.subUnitsCount) || 1) : 1);

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

    // Attach listeners to buttons
    document.getElementById('addRowBtn').addEventListener('click', () => addRow());
    document.getElementById('addAnotherRowBtn').addEventListener('click', () => addRow());

    // Initialize first row
    const initialSelectedMed = "{{ $selectedMedicineId ?? '' }}";
    addRow(initialSelectedMed || null);
});
</script>
@endpush
@endsection
