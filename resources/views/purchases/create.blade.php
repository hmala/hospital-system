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
                        <div class="row mb-3 align-items-center g-2">
                            <div class="col-md-7">
                                <label class="form-label text-muted small fw-bold mb-1"><i class="fas fa-plus-circle text-success me-1"></i>إضافة مادة سريعة إلى الفاتورة</label>
                                <select id="quickProductAdd" class="form-control">
                                    <option value=""></option>
                                    {!! $optionsBuffer !!}
                                </select>
                            </div>
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label text-muted small fw-bold mb-0"><i class="fas fa-filter text-primary me-1"></i>فلترة الأسطر المعروضة</label>
                                    <small class="text-muted fw-bold" id="filteredRowCount"></small>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 text-primary" style="border-radius: 10px 0 0 10px;"><i class="fas fa-search"></i></span>
                                    <input type="text" id="tableItemSearch" class="form-control border-start-0" placeholder="بحث باسم المادة، السعر، الكمية..." style="border-radius: 0 10px 10px 0;">
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mb-3">
                            <table class="table table-hover align-middle mb-0" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">#</th>
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
                                <i class="fas fa-plus me-2"></i>إضافة مادة فارغة
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
const productOptionsTemplate = `{!! $optionsBuffer !!}`;

const addRowButton = document.getElementById('addRow');
const itemsTableBody = document.querySelector('#itemsTable tbody');

function updateRowIndices() {
    document.querySelectorAll('#itemsTable tbody tr').forEach((tr, i) => {
        const indexCell = tr.querySelector('.row-index');
        if (indexCell) {
            indexCell.textContent = i + 1;
        }
    });
}

function filterItemsTable(query) {
    const term = (query || '').toLowerCase().trim();
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    let visibleCount = 0;

    rows.forEach(function(row) {
        if (!term) {
            row.style.display = '';
            visibleCount++;
            return;
        }

        let textParts = [];

        // 1. Text inside inputs (qty, cost_price, expiry_date)
        row.querySelectorAll('input').forEach(function(input) {
            if (input.value) textParts.push(input.value);
        });

        // 2. Selected option text ONLY
        row.querySelectorAll('select.item-product').forEach(function(select) {
            if (select.value && select.selectedIndex >= 0) {
                const opt = select.options[select.selectedIndex];
                if (opt && opt.text && opt.value) {
                    textParts.push(opt.text);
                }
            }
        });

        // 3. Rendered Select2 text label
        row.querySelectorAll('.select2-selection__rendered').forEach(function(s2) {
            const title = s2.getAttribute('title') || s2.textContent;
            if (title && !title.includes('اختر')) {
                textParts.push(title);
            }
        });

        // 4. Badges / codes inside cells
        row.querySelectorAll('code, small, .badge').forEach(function(el) {
            if (el.textContent) textParts.push(el.textContent);
        });

        const combinedText = textParts.join(' ').toLowerCase();

        if (combinedText.includes(term)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const countElem = document.getElementById('filteredRowCount');
    if (countElem) {
        countElem.textContent = term !== '' ? `عناصر معروضة: ${visibleCount} من ${rows.length}` : '';
    }
}

function createRow(index) {
    return `
        <tr id="row${index}">
            <td class="text-center fw-bold row-index"></td>
            <td>
                <select name="items[${index}][product_id]" class="form-control item-product" required>
                    <option value=""></option>
                    ${productOptionsTemplate}
                </select>
            </td>
            <td><input type="number" name="items[${index}][qty]" class="form-control" min="1" required></td>
            <td><input type="number" step="0.01" name="items[${index}][cost_price]" class="form-control" required></td>
            <td><input type="date" name="items[${index}][expiry_date]" class="form-control expiry-date"></td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-row">حذف</button></td>
        </tr>
    `;
}

function initSelect2OnRow(rowElement) {
    const select = $(rowElement).find('.item-product');
    if (select.length && typeof $.fn.select2 !== 'undefined') {
        select.select2({
            dir: 'rtl',
            width: '100%',
            placeholder: 'اختر أو ابحث عن المادة...',
            allowClear: true
        });
        select.on('select2:select select2:clear change', function() {
            const tr = $(this).closest('tr');
            const expiryInput = tr.find('.expiry-date');
            const selectedOpt = this.options[this.selectedIndex];
            const isPerishable = selectedOpt ? selectedOpt.dataset.isPerishable === '1' : false;
            expiryInput.prop('required', isPerishable);
            if (!isPerishable) expiryInput.val('');
            
            // إعادة تفعيل الفلترة الفورية
            const searchInput = document.getElementById('tableItemSearch');
            if (searchInput && searchInput.value) {
                filterItemsTable(searchInput.value);
            }
        });
    }
}

$(document).ready(function() {
    if (typeof $.fn.select2 !== 'undefined') {
        $('#supplierSelect').select2({
            dir: 'rtl',
            width: '100%',
            placeholder: 'اختر أو ابحث عن المورد...',
            allowClear: true
        });

        $('#quickProductAdd').select2({
            dir: 'rtl',
            width: '100%',
            placeholder: '🔍 ابحث عن مادة واضغط لإضافتها فوراً...',
            allowClear: true
        }).on('select2:select', function(e) {
            const productId = $(this).val();
            if (productId) {
                addRowButton.click();
                const newRow = $('#itemsTable tbody tr').last();
                const select = newRow.find('.item-product');
                select.val(productId).trigger('change');
                newRow.find('input[name*="[qty]"]').focus();
                $(this).val('').trigger('change.select2');
            }
        });
    }

    const searchInput = document.getElementById('tableItemSearch');
    if (searchInput) {
        ['input', 'keyup', 'change', 'paste'].forEach(function(evt) {
            searchInput.addEventListener(evt, function() {
                filterItemsTable(this.value);
            });
        });
    }

    addRowButton.addEventListener('click', function() {
        itemsTableBody.insertAdjacentHTML('beforeend', createRow(rowIdx));
        const newRow = itemsTableBody.lastElementChild;
        initSelect2OnRow(newRow);
        rowIdx++;
        updateRowIndices();
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.closest('tr').remove();
            updateRowIndices();
            if (searchInput && searchInput.value) {
                filterItemsTable(searchInput.value);
            }
        }
    });

    // إدخال السطر الأول تلقائياً
    addRowButton.click();
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

    .select2-container--default .select2-selection--single {
        height: 48px;
        border-radius: 10px;
        border: 1px solid #ced4da;
        display: flex;
        align-items: center;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 46px;
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
