@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border-radius: 20px;">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-edit me-3"></i>تعديل فاتورة المشتريات
                            </h2>
                            <p class="mb-0 opacity-75">تعديل بيانات الفاتورة والمواد الواردة للمخزن الرئيسي.</p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('purchases.show', $purchase) }}" class="btn btn-light text-primary px-3 py-2 rounded-pill fw-bold">
                                <i class="fas fa-eye me-1"></i>عرض الفاتورة
                            </a>
                            <a href="{{ route('purchases.index') }}" class="btn btn-outline-light px-3 py-2 rounded-pill fw-bold">
                                <i class="fas fa-arrow-right me-1"></i>رجوع للقائمة
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Form -->
    <div class="row">
        <div class="col-12">
            <form action="{{ route('purchases.update', $purchase) }}" method="POST" id="purchaseForm">
                @csrf
                @method('PUT')

                <div class="card border-0 shadow-lg mb-4" style="border-radius: 15px;">
                    <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary rounded-circle p-2">
                                <i class="fas fa-info-circle text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">بيانات الفاتورة</h5>
                                <small class="text-muted">تعديل المورد ورقم الفاتورة</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select name="supplier_id" id="supplierSelect" class="form-select" required>
                                        <option value="">اختر المورد</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" {{ (old('supplier_id', $purchase->supplier_id) == $supplier->id) ? 'selected' : '' }}>
                                                {{ $supplier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="supplierSelect">المورد <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="invoice_number" class="form-control" id="invoiceNumber" value="{{ old('invoice_number', $purchase->invoice_number) }}" placeholder="رقم الفاتورة" required>
                                    <label for="invoiceNumber">رقم الفاتورة الورقية <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-lg mb-4" style="border-radius: 15px;">
                    <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success rounded-circle p-2">
                                <i class="fas fa-boxes text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">عناصر الفاتورة</h5>
                                <small class="text-muted">تعديل المواد، الكميات، وسعر التكلفة</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive mb-3">
                            <table class="table table-hover align-middle mb-0" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>المادة</th>
                                        <th style="width: 130px;">الكمية</th>
                                        <th style="width: 160px;">سعر التكلفة (د.ع)</th>
                                        <th style="width: 170px;">تاريخ الانتهاء</th>
                                        <th class="text-center" style="width: 90px;">حذف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purchase->items as $idx => $item)
                                    <tr id="row{{ $idx }}">
                                        <td>
                                            <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $item->id }}">
                                            <select name="items[{{ $idx }}][product_id]" class="form-control item-product" required>
                                                <option value="" data-is-perishable="0">اختر المادة...</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" 
                                                            data-is-perishable="{{ $product->is_perishable ? '1' : '0' }}"
                                                            {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                        {{ $product->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if($item->stockBatch)
                                                <small class="text-muted d-block mt-1">الباركود: <code>{{ $item->stockBatch->internal_barcode }}</code></small>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $idx }}][qty]" class="form-control" value="{{ $item->qty }}" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[{{ $idx }}][cost_price]" class="form-control" value="{{ $item->unit_cost }}" required>
                                        </td>
                                        <td>
                                            <input type="date" name="items[{{ $idx }}][expiry_date]" class="form-control expiry-date" value="{{ $item->expiry_date ? $item->expiry_date->format('Y-m-d') : '' }}">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm remove-row" title="حذف المادة">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="addRow">
                                <i class="fas fa-plus me-2"></i>إضافة مادة للفاتورة
                            </button>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">
                                <i class="fas fa-save me-2"></i>حفظ التعديلات وتحديث المخزون
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
<script>
let rowIdx = {{ $purchase->items->count() }};

const addRowButton = document.getElementById('addRow');
const itemsTableBody = document.querySelector('#itemsTable tbody');

function createRow(index) {
    return `
        <tr id="row${index}">
            <td>
                <select name="items[${index}][product_id]" class="form-control item-product" required>
                    <option value="" data-is-perishable="0">اختر المادة...</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-is-perishable="{{ $product->is_perishable ? '1' : '0' }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="items[${index}][qty]" class="form-control" min="1" required></td>
            <td><input type="number" step="0.01" name="items[${index}][cost_price]" class="form-control" required></td>
            <td><input type="date" name="items[${index}][expiry_date]" class="form-control expiry-date"></td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm remove-row" title="حذف">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `;
}

function setExpiryValidation(index) {
    const row = document.getElementById(`row${index}`);
    if (!row) return;

    const productSelect = row.querySelector('.item-product');
    const expiryInput = row.querySelector('.expiry-date');

    const updateExpiry = () => {
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const isPerishable = selectedOption?.dataset?.isPerishable === '1';
        expiryInput.required = isPerishable;
        if (!isPerishable) {
            expiryInput.value = '';
        }
    };

    productSelect.addEventListener('change', updateExpiry);
    updateExpiry();
}

// تفعيل التحقق من الصلاحية للأسطر الموجودة
document.querySelectorAll('#itemsTable tbody tr').forEach((tr, i) => {
    setExpiryValidation(i);
});

addRowButton.addEventListener('click', function() {
    itemsTableBody.insertAdjacentHTML('beforeend', createRow(rowIdx));
    setExpiryValidation(rowIdx);
    rowIdx++;
});

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.remove-row');
    if (btn) {
        btn.closest('tr').remove();
    }
});
</script>

<style>
    .card {
        transition: all 0.3s ease;
    }
    .form-control, .form-select {
        border-radius: 10px;
    }
</style>
@endsection
