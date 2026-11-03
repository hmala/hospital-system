@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 px-md-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning bg-opacity-20 text-warning d-flex align-items-center justify-content-center shadow-sm" style="width: 56px; height: 56px;">
                                <i class="fas fa-undo-alt fa-2x"></i>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-white">إرجاع مواد وعُهدة إلى المخزن الرئيسي</h3>
                                <p class="mb-0 text-light opacity-75 small">
                                    توثيق وإرجاع الأدوية والمستلزمات الفائضة أو غير المستهلكة من عهدة الطابق إلى المستودع المركزي.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            @if(request()->filled('from_nursing') || (Auth::user()?->location_id && $selectedFromLocation && Auth::user()->location_id == $selectedFromLocation->id))
                                <a href="{{ route('nursing-station.index', ['location_id' => $selectedFromLocation?->id]) }}" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold">
                                    <i class="fas fa-arrow-right me-1"></i> العودة لمحطة التمريض
                                </a>
                            @endif
                            <a href="{{ route('stock-transfers.requests.index') }}" class="btn btn-light text-dark rounded-pill px-3 py-2 fw-bold shadow-sm">
                                <i class="fas fa-history me-1"></i> سجل الطلبيات والإرجاع
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Banner -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 p-3 mb-0" style="background-color: #f0f9ff; border-right: 5px solid #0284c7 !important;">
                <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2.5 d-flex align-items-center justify-content-center">
                    <i class="fas fa-shield-alt fa-lg"></i>
                </div>
                <div>
                    <strong class="text-primary-emphasis d-block mb-0.5">ضوابط إرجاع العهدة المخزنية:</strong>
                    <span class="text-muted small">
                        المواد المختارة ستظل مقيدة على عهدة الطابق حتى يقوم أمين المخزن الرئيسي بفحصها واعتماد استلامها في المستودع المركزي، وعندها يتم تفريغ رصيدها تلقائياً.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-clipboard-list text-primary fs-5"></i>
                        <h5 class="mb-0 fw-bold text-dark">نموذج تفاصيل الإرجاع</h5>
                    </div>
                    @if($selectedFromLocation)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-semibold">
                            <i class="fas fa-hospital me-1"></i> العهدة المصدر: {{ $selectedFromLocation->name }}
                        </span>
                    @endif
                </div>

                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
                            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i>توجد أخطاء في البيانات المدخلة:</div>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('stock-transfers.returns.store') }}" id="returnStockForm">
                        @csrf
                        @if(request()->filled('from_nursing'))
                            <input type="hidden" name="from_nursing" value="1">
                        @endif

                        {{-- اختيار المخازن --}}
                        <div class="row g-4 mb-4 pb-3 border-bottom">
                            {{-- المخزن الفرعي (المصدر) --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small mb-1.5">
                                    <i class="fas fa-warehouse text-warning me-1"></i> المخزن الفرعي (المصدر) <span class="text-danger">*</span>
                                </label>
                                @if(!empty($isLockedToLocation) && $selectedFromLocation)
                                    <input type="hidden" name="from_location_id" value="{{ $selectedFromLocation->id }}">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock text-warning"></i></span>
                                        <input type="text" class="form-control bg-light border-start-0 fw-bold text-dark" value="{{ $selectedFromLocation->name }}" readonly>
                                    </div>
                                    <small class="text-muted mt-1 d-block">مقفل تلقائياً على عهدة الطابق المرتبطة بحسابك.</small>
                                @else
                                    <select name="from_location_id" id="from_location_id" class="form-select rounded-3 py-2" required onchange="this.form.submit()">
                                        <option value="">-- اختر المخزن الفرعي --</option>
                                        @foreach($subLocations as $loc)
                                            <option value="{{ $loc->id }}" {{ (old('from_location_id', $selectedFromLocation?->id) == $loc->id) ? 'selected' : '' }}>
                                                {{ $loc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted mt-1 d-block">سيتم تحديث قائمة المواد المتاحة حسب المخزن المختار.</small>
                                @endif
                            </div>

                            {{-- المخزن الرئيسي (المستلم) --}}
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark small mb-1.5">
                                    <i class="fas fa-truck-loading text-success me-1"></i> إلى المخزن الرئيسي (الوجهة) <span class="text-danger">*</span>
                                </label>
                                <select name="to_location_id" class="form-select rounded-3 py-2" required>
                                    @foreach($mainLocations as $mLoc)
                                        <option value="{{ $mLoc->id }}" {{ old('to_location_id') == $mLoc->id ? 'selected' : '' }}>
                                            {{ $mLoc->name }} (المستودع المركزي)
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted mt-1 d-block">يتم تسليم المواد وإضافتها إلى رصيد المستودع المركزي بعد الاعتماد.</small>
                            </div>
                        </div>

                        {{-- جدول أو بنود المواد المراد إرجاعها --}}
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">
                                        <i class="fas fa-boxes text-primary me-2"></i> المواد والوجبات المراد إرجاعها
                                    </h6>
                                    <small class="text-muted">اختر المواد من رصيد عهدة الطابق المتوفر فعلياً، وحدد الكمية وسبب الإرجاع.</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold" id="addRowBtn">
                                    <i class="fas fa-plus-circle me-1"></i> + إضافة مادة أخرى للإرجاع
                                </button>
                            </div>

                            @if($floorBatches->isEmpty())
                                <div class="alert alert-warning border-0 rounded-4 p-4 text-center">
                                    <i class="fas fa-box-open fa-3x mb-3 text-warning opacity-75"></i>
                                    <h6 class="fw-bold">لا يوجد رصيد مواد متوفر حالياً في هذا المخزن الفرعي!</h6>
                                    <p class="text-muted small mb-0">لا يمكن إنشاء طلب إرجاع لأن رصيد عهدة الطابق فارغ تماماً.</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0" id="returnItemsTable">
                                        <thead class="table-light text-muted small">
                                            <tr>
                                                <th style="min-width: 320px;">المادة والوجبة في عهدة الطابق <span class="text-danger">*</span></th>
                                                <th style="width: 130px;" class="text-center">الرصيد المتاح</th>
                                                <th style="width: 140px;" class="text-center">الكمية المرتجعة <span class="text-danger">*</span></th>
                                                <th style="min-width: 220px;">سبب الإرجاع <span class="text-danger">*</span></th>
                                                <th style="min-width: 180px;">ملاحظات إضافية</th>
                                                <th style="width: 60px;" class="text-center">حذف</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsTableBody">
                                            {{-- سطر البداية الأول --}}
                                            <tr class="item-row">
                                                <td>
                                                    <select name="items[0][batch_id]" class="form-select batch-select rounded-3" required onchange="handleBatchChange(this)">
                                                        <option value="" data-product-id="" data-max="0">-- اختر المادة والوجبة --</option>
                                                        @foreach($floorBatches as $fb)
                                                            @php
                                                                $isSelected = ($preselectedBatch && $preselectedBatch->id == $fb->id) || old('items.0.batch_id') == $fb->id;
                                                                $lotStr = $fb->internal_barcode ?: ($fb->manufacturer_lot_number ?: '#'.$fb->id);
                                                                $expStr = $fb->expiry_date ? $fb->expiry_date->format('Y-m-d') : 'غير محدد';
                                                            @endphp
                                                            <option value="{{ $fb->id }}"
                                                                    data-product-id="{{ $fb->product_id }}"
                                                                    data-max="{{ $fb->current_qty }}"
                                                                    data-unit="{{ $fb->product?->unit ?? 'وحدة' }}"
                                                                    {{ $isSelected ? 'selected' : '' }}>
                                                                {{ $fb->product?->name }} — [وجبة: {{ $lotStr }} | انتهاء: {{ $expStr }} | متاح: {{ $fb->current_qty }}]
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="items[0][product_id]" class="product-id-input" value="{{ $preselectedBatch ? $preselectedBatch->product_id : old('items.0.product_id') }}">
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1 available-badge">
                                                        {{ $preselectedBatch ? $preselectedBatch->current_qty : '0' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="input-group">
                                                        <input type="number" name="items[0][qty]" class="form-control qty-input text-center rounded-3 fw-bold"
                                                               min="1" max="{{ $preselectedBatch ? $preselectedBatch->current_qty : 9999 }}"
                                                               value="{{ old('items.0.qty', 1) }}" required>
                                                    </div>
                                                </td>
                                                <td>
                                                    <select name="items[0][reason]" class="form-select rounded-3" required>
                                                        @foreach($standardReasons as $reasonKey => $reasonText)
                                                            <option value="{{ $reasonText }}" {{ old('items.0.reason') == $reasonText ? 'selected' : '' }}>
                                                                {{ $reasonText }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" name="items[0][notes]" class="form-control rounded-3" placeholder="ملاحظات اختيارية..." value="{{ old('items.0.notes') }}">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-circle delete-row-btn" disabled title="لا يمكن حذف السطر الوحيد">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        {{-- أزرار الإجراءات --}}
                        @if($floorBatches->isNotEmpty())
                            <div class="d-flex justify-content-between align-items-center gap-3 pt-3 border-top flex-wrap">
                                <a href="{{ request()->filled('from_nursing') || (Auth::user()?->location_id && $selectedFromLocation && Auth::user()->location_id == $selectedFromLocation->id) ? route('nursing-station.index', ['location_id' => $selectedFromLocation?->id]) : route('stock-transfers.requests.index') }}" class="btn btn-light rounded-pill px-4 py-2 text-muted border">
                                    <i class="fas fa-arrow-left me-1"></i> إلغاء ورجوع
                                </a>
                                <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 fw-bold shadow-sm" id="submitReturnBtn">
                                    <i class="fas fa-undo-alt me-2"></i> تأكيد وإرسال طلب الإرجاع للمخزن الرئيسي
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let rowIndex = 1;

    // خيارات الوجبات المحملة من السيرفر
    const batchesData = @json($batchesData ?? []);

    const reasonsList = @json(array_values($standardReasons));

    function handleBatchChange(selectElem) {
        const row = selectElem.closest('.item-row');
        const selectedOpt = selectElem.options[selectElem.selectedIndex];
        const productId = selectedOpt.getAttribute('data-product-id') || '';
        const maxQty = parseInt(selectedOpt.getAttribute('data-max') || '0', 10);

        row.querySelector('.product-id-input').value = productId;
        const availableBadge = row.querySelector('.available-badge');
        const qtyInput = row.querySelector('.qty-input');

        availableBadge.textContent = maxQty;
        if (maxQty > 0) {
            qtyInput.max = maxQty;
            if (parseInt(qtyInput.value, 10) > maxQty) {
                qtyInput.value = maxQty;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const tableBody = document.getElementById('itemsTableBody');
        const addRowBtn = document.getElementById('addRowBtn');

        // تحديث السطر الأول عند الفتح
        const initialSelect = document.querySelector('.batch-select');
        if (initialSelect && initialSelect.value) {
            handleBatchChange(initialSelect);
        }

        if (addRowBtn && tableBody) {
            addRowBtn.addEventListener('click', function () {
                let optionsHtml = '<option value="" data-product-id="" data-max="0">-- اختر المادة والوجبة --</option>';
                batchesData.forEach(b => {
                    optionsHtml += `<option value="${b.id}" data-product-id="${b.product_id}" data-max="${b.qty}" data-unit="${b.unit}">
                        ${b.name} — [وجبة: ${b.lot} | انتهاء: ${b.exp} | متاح: ${b.qty}]
                    </option>`;
                });

                let reasonsHtml = '';
                reasonsList.forEach(r => {
                    reasonsHtml += `<option value="${r}">${r}</option>`;
                });

                const tr = document.createElement('tr');
                tr.className = 'item-row';
                tr.innerHTML = `
                    <td>
                        <select name="items[${rowIndex}][batch_id]" class="form-select batch-select rounded-3" required onchange="handleBatchChange(this)">
                            ${optionsHtml}
                        </select>
                        <input type="hidden" name="items[${rowIndex}][product_id]" class="product-id-input" value="">
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1 available-badge">0</span>
                    </td>
                    <td>
                        <div class="input-group">
                            <input type="number" name="items[${rowIndex}][qty]" class="form-control qty-input text-center rounded-3 fw-bold" min="1" max="9999" value="1" required>
                        </div>
                    </td>
                    <td>
                        <select name="items[${rowIndex}][reason]" class="form-select rounded-3" required>
                            ${reasonsHtml}
                        </select>
                    </td>
                    <td>
                        <input type="text" name="items[${rowIndex}][notes]" class="form-control rounded-3" placeholder="ملاحظات اختيارية...">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle delete-row-btn" onclick="removeRow(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                `;

                tableBody.appendChild(tr);
                rowIndex++;
                updateDeleteButtons();
            });
        }

        window.removeRow = function(btn) {
            const row = btn.closest('.item-row');
            if (row) {
                row.remove();
                updateDeleteButtons();
            }
        };

        function updateDeleteButtons() {
            const rows = tableBody.querySelectorAll('.item-row');
            rows.forEach(r => {
                const delBtn = r.querySelector('.delete-row-btn');
                if (delBtn) {
                    delBtn.disabled = rows.length <= 1;
                }
            });
        }

        // فحص التحقق قبل الإرسال
        const form = document.getElementById('returnStockForm');
        if (form) {
            form.addEventListener('submit', function (e) {
                const rows = form.querySelectorAll('.item-row');
                let hasError = false;

                rows.forEach(r => {
                    const select = r.querySelector('.batch-select');
                    const qtyInput = r.querySelector('.qty-input');
                    const maxQty = parseInt(select.options[select.selectedIndex]?.getAttribute('data-max') || '0', 10);
                    const qtyVal = parseInt(qtyInput.value, 10);

                    if (!select.value) {
                        alert('يرجى اختيار المادة والوجبة لكل سطر.');
                        select.focus();
                        hasError = true;
                        e.preventDefault();
                        return;
                    }

                    if (qtyVal < 1 || qtyVal > maxQty) {
                        alert(`الكمية المحددة للمادة (${qtyVal}) غير صالحة. الرصيد المتاح هو (${maxQty}) فقط.`);
                        qtyInput.focus();
                        hasError = true;
                        e.preventDefault();
                        return;
                    }
                });
            });
        }
    });
</script>
@endsection
