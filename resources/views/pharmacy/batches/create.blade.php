@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-file-invoice-dollar me-2"></i>توريد قائمة أدوية وشحنات جديدة للصيدلية
            </h2>
            <p class="text-muted small mb-0">إدخال فاتورة / إذن توريد متعدد البنود وتثبيت أرقام التشغيلات وتواريخ الانتهاء لتطبيق نظام (FEFO)</p>
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
                    <i class="fas fa-info-circle me-2"></i>بيانات إذن الاستلام والتوريد العام
                </h6>
            </div>
            <div class="card-body p-4 pt-1">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">اسم المورد / شركة التوزيع <span class="text-danger">*</span></label>
                        <input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name') }}" placeholder="مثال: شركة الرافدين للمستلزمات الطبية" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-bold">رقم الفاتورة الورقية / إذن الاستلام</label>
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
                    <i class="fas fa-list-ol me-2"></i>قائمة الأدوية والمستلزمات المستلمة
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
                                <th style="width: 4%;">#</th>
                                <th style="width: 32%;">الدواء / المستلزم <span class="text-danger">*</span></th>
                                <th style="width: 16%;">رقم التشغيلة (Batch/Lot) <span class="text-danger">*</span></th>
                                <th style="width: 14%;">تاريخ الصلاحية <span class="text-danger">*</span></th>
                                <th style="width: 12%;">الكمية (علب) <span class="text-danger">*</span></th>
                                <th style="width: 12%;">سعر الشراء للعلبة (د.ع) <span class="text-danger">*</span></th>
                                <th style="width: 6%;">الإجمالي</th>
                                <th style="width: 4%;" class="text-center">حذف</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <!-- Rows injected via JS or template -->
                        </tbody>
                    </table>
                </div>

                <!-- شريط الإجماليات وملخص الفاتورة -->
                <div class="p-3 bg-light border-top d-flex flex-wrap justify-content-between align-items-center">
                    <div class="d-flex gap-4">
                        <span class="small">عدد الأصناف: <strong class="text-primary fs-6" id="totalItemsCount">0</strong> صنف</span>
                        <span class="small">إجمالي العلب: <strong class="text-success fs-6" id="totalQtyCount">0</strong> علبة</span>
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
                <i class="fas fa-check-circle me-1"></i> اعتماد وحفظ قائمة التوريد بالصيدلية
            </button>
        </div>
    </form>
</div>

<!-- Raw medicines options buffer for fast DOM cloning -->
<template id="medicinesOptionsTemplate">
    <option value="">-- اختر الدواء --</option>
    @foreach($medicines as $med)
        <option value="{{ $med->id }}"
                data-cost-price="{{ $med->cost_price ?? 0 }}"
                data-unit="{{ $med->main_unit }}"
                {{ old('medicine_id', $selectedMedicineId) == $med->id ? 'selected' : '' }}>
            {{ $med->name }} ({{ $med->dosage_form }} - {{ $med->strength }}) [{{ $med->main_unit }}]
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
            <td class="text-muted fw-bold row-num">${tableBody.children.length + 1}</td>
            <td>
                <select name="items[${rowIndex}][medicine_id]" class="form-select form-select-sm medicine-select" required>
                    ${optionsHtml}
                </select>
            </td>
            <td>
                <input type="text" name="items[${rowIndex}][batch_number]" class="form-control form-select-sm font-monospace" placeholder="مثال: LOT-2026-X" required>
            </td>
            <td>
                <input type="date" name="items[${rowIndex}][expiry_date]" class="form-control form-select-sm" required>
            </td>
            <td>
                <input type="number" name="items[${rowIndex}][initial_quantity]" class="form-control form-select-sm fw-bold text-center qty-input" min="1" value="10" required>
            </td>
            <td>
                <input type="number" step="0.01" name="items[${rowIndex}][purchase_price]" class="form-control form-select-sm text-center price-input" min="0" value="0.00" required>
            </td>
            <td>
                <span class="fw-bold small row-total text-secondary">0.00</span>
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
            const opt = medSelect.options[medSelect.selectedIndex];
            if (opt && opt.dataset.costPrice) {
                tr.querySelector('.price-input').value = parseFloat(opt.dataset.costPrice).toFixed(2);
            }
        }

        bindRowEvents(tr);
        recalcTotals();
    }

    function bindRowEvents(tr) {
        const medSelect = tr.querySelector('.medicine-select');
        const qtyInput = tr.querySelector('.qty-input');
        const priceInput = tr.querySelector('.price-input');
        const deleteBtn = tr.querySelector('.delete-row-btn');

        medSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.costPrice && parseFloat(priceInput.value) === 0) {
                priceInput.value = parseFloat(opt.dataset.costPrice).toFixed(2);
            }
            recalcRow(tr);
        });

        qtyInput.addEventListener('input', () => recalcRow(tr));
        priceInput.addEventListener('input', () => recalcRow(tr));

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
        const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
        const price = parseFloat(tr.querySelector('.price-input').value) || 0;
        const total = qty * price;
        tr.querySelector('.row-total').textContent = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
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
        let totalSum = 0;

        Array.from(tableBody.children).forEach(tr => {
            const qty = parseFloat(tr.querySelector('.qty-input')?.value) || 0;
            const price = parseFloat(tr.querySelector('.price-input')?.value) || 0;
            totalQty += qty;
            totalSum += (qty * price);
        });

        document.getElementById('totalItemsCount').textContent = totalItems;
        document.getElementById('totalQtyCount').textContent = totalQty.toLocaleString();
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
