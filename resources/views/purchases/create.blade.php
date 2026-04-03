@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">إدخال قائمة مشتريات جديدة (توريد للمخزن الرئيسي)</div>
        <div class="card-body">
            <form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm">
                @csrf
                <div class="row mb-4">
                    <div class="col-md-4">
                        <label>المورد</label>
                        <select name="supplier_id" class="form-control" required>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>رقم الفاتورة الورقية</label>
                        <input type="text" name="invoice_number" class="form-control" required>
                    </div>
                </div>

                <hr>

                <table class="table table-bordered" id="itemsTable">
                    <thead>
                        <tr>
                            <th>المادة</th>
                            <th>الكمية</th>
                            <th>سعر التكلفة (للوحدة)</th>
                            <th>تاريخ الانتهاء</th>
                            <th>حذف</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>

                <button type="button" class="btn btn-secondary" id="addRow">+ إضافة مادة للفاتورة</button>
                <button type="submit" class="btn btn-primary float-end">حفظ وتوليد الباركودات</button>
            </form>
        </div>
    </div>
</div>

<script>
let rowIdx = 0;

// دالة إضافة سطر جديد
document.getElementById('addRow').addEventListener('click', function() {
    let tableBody = document.querySelector('#itemsTable tbody');
    let row = `
        <tr id="row${rowIdx}">
            <td>
                <select name="items[${rowIdx}][product_id]" class="form-control item-product" required>
                    <option value="" data-is-perishable="0">اختر المادة...</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" data-is-perishable="{{ $product->is_perishable ? '1' : '0' }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="items[${rowIdx}][qty]" class="form-control" min="1" required></td>
            <td><input type="number" step="0.01" name="items[${rowIdx}][cost_price]" class="form-control" required></td>
            <td><input type="date" name="items[${rowIdx}][expiry_date]" class="form-control expiry-date"></td>
            <td><button type="button" class="btn btn-danger remove-row">X</button></td>
        </tr>
    `;
    tableBody.insertAdjacentHTML('beforeend', row);
    setExpiryValidation(rowIdx);
    rowIdx++;
});

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

// دالة حذف سطر
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-row')) {
        e.target.closest('tr').remove();
    }
});
</script>
@endsection