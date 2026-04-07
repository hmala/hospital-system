@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px;">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-shopping-cart me-3"></i>إدخال مشتريات جديدة
                            </h2>
                            <p class="mb-0 opacity-75">سجل فاتورة مشتريات جديدة لتوريد المواد إلى المخزن الرئيسي.</p>
                        </div>
                        <span class="badge bg-light text-primary py-2 px-3 rounded-pill">
                            <i class="fas fa-warehouse me-1"></i>توريد المخزن الرئيسي
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-12">
            <form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm">
                @csrf

                <div class="card border-0 shadow-lg mb-4" style="border-radius: 15px;">
                    <div class="card-header bg-light border-0" style="border-radius: 15px 15px 0 0;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary rounded-circle p-2">
                                <i class="fas fa-info-circle text-white"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">بيانات الفاتورة</h5>
                                <small class="text-muted">اختر المورد وأضف معلومات الفاتورة</small>
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
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="supplierSelect">المورد <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="invoice_number" class="form-control" id="invoiceNumber" placeholder="رقم الفاتورة" required>
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
                                <small class="text-muted">أضف المواد والكميات وسعر التكلفة</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive mb-3">
                            <table class="table table-hover align-middle mb-0" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>المادة</th>
                                        <th>الكمية</th>
                                        <th>سعر التكلفة</th>
                                        <th>تاريخ الانتهاء</th>
                                        <th class="text-center" style="width: 90px;">حذف</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" id="addRow">
                                <i class="fas fa-plus me-2"></i>إضافة مادة للفاتورة
                            </button>
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-2"></i>حفظ وتوليد الباركودات
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
let rowIdx = 0;

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
            <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-row">حذف</button></td>
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

addRowButton.addEventListener('click', function() {
    itemsTableBody.insertAdjacentHTML('beforeend', createRow(rowIdx));
    setExpiryValidation(rowIdx);
    rowIdx++;
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-row')) {
        e.target.closest('tr').remove();
    }
});
</script>

<style>
    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
    }

    .form-control, .form-select {
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .form-control:focus, .form-select:focus {
        transform: translateY(-2px);
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .btn {
        transition: all 0.3s ease;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(102, 126, 234, 0.05);
    }
</style>
@endsection
