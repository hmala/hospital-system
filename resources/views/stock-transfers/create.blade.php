@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-header bg-gradient bg-primary text-white p-3 rounded-top-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                    <i class="fas fa-dolly-flatbed fs-5"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold">طلب تعزيز مواد لعهدة الطابق</h5>
                    <small class="text-white-50">إرسال طلبية استلام مواد ومستلزمات من المخزن الرئيسي إلى عهدة الطابق</small>
                </div>
            </div>
            <div>
                <a href="{{ request()->query('from') == 'nursing' || !empty($lockedLocation) ? route('nursing-station.index') : url()->previous() }}" class="btn btn-light btn-sm rounded-pill px-3 py-1.5 fw-bold shadow-xs text-primary">
                    <i class="fas fa-arrow-right me-1"></i> العودة لمحطة التمريض
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

            <form method="POST" action="{{ route('stock-transfers.requests.store') }}" id="transferForm">
                @csrf

                <!-- Compact Transfer Route Strip -->
                <div class="p-3 bg-light rounded-4 border mb-4">
                    <div class="row align-items-center g-3">
                        <!-- From: Main Warehouse -->
                        <div class="col-md-5">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-medium">المصدر المرسل:</span>
                                @if($mainLocations->isNotEmpty())
                                    <input type="hidden" name="from_location_id" value="{{ $mainLocations->first()->id }}">
                                    <span class="badge bg-primary text-white px-3 py-2 rounded-pill fs-6 fw-bold">
                                        <i class="fas fa-warehouse me-1 text-warning"></i> {{ $mainLocations->first()->name }}
                                    </span>
                                @else
                                    <span class="badge bg-danger rounded-pill px-3 py-2">لا يوجد مخزن رئيسي</span>
                                @endif
                            </div>
                        </div>

                        <!-- Direction Arrow -->
                        <div class="col-md-2 text-center d-none d-md-block">
                            <i class="fas fa-arrow-left text-primary fs-5"></i>
                        </div>

                        <!-- To: Floor Store -->
                        <div class="col-md-5">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-muted small fw-medium">المخزن الفرعي المستلم (عهدتك المعتمدة):</span>
                                @if(!empty($isLockedToLocation) && !empty($lockedLocation))
                                    <input type="hidden" name="to_location_id" value="{{ $lockedLocation->id }}">
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info px-3 py-2 rounded-pill fs-6 fw-bold">
                                        <i class="fas fa-lock text-warning me-1"></i> {{ $lockedLocation->name }}
                                    </span>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill small">محدد تلقائياً</span>
                                @else
                                    <select name="to_location_id" id="toLocationSelect" class="form-select form-select-sm rounded-pill w-auto flex-grow-1" required>
                                        <option value="">اختر المخزن الفرعي...</option>
                                        @foreach($subLocations as $location)
                                            <option value="{{ $location->id }}" {{ (old('to_location_id', $lockedLocation?->id) == $location->id) ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Datalist for Fast Instant Search -->
                <datalist id="productsDatalist">
                    @foreach($products as $product)
                        <option value="{{ $product->name }}" data-id="{{ $product->id }}" data-unit="{{ $product->unit ?? 'وحدة' }}" data-code="{{ $product->code ?? '' }}">
                            [{{ $product->code ?? 'عام' }}] ({{ $product->unit ?? 'وحدة' }})
                        </option>
                    @endforeach
                </datalist>

                <!-- Items Section -->
                <div class="card border rounded-4 shadow-none mb-4">
                    <div class="card-header bg-white py-3 px-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-boxes text-success fs-5"></i>
                            <h6 class="mb-0 fw-bold text-dark">جدول المواد والمستلزمات المطلوبة</h6>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small" id="itemsCountBadge">1 مادة</span>
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-3 fw-bold" id="addItemRow">
                            <i class="fas fa-plus me-1"></i> + إضافة مادة أخرى
                        </button>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3" style="width: 55px;">#</th>
                                        <th style="min-width: 320px;">اسم المادة أو الدواء (بحث فوري) <span class="text-danger">*</span></th>
                                        <th style="width: 150px;" class="text-center">الوحدة</th>
                                        <th style="width: 180px;" class="text-center">الكمية المطلوبة <span class="text-danger">*</span></th>
                                        <th style="width: 80px;" class="text-center pe-3">إجراء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- الصفوف تضاف ديناميكياً بواسطة الجافاسكريبت --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer Summary & Submit -->
                <div class="card border-0 bg-light rounded-4 p-3 shadow-none">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                        <div class="text-muted small">
                            <i class="fas fa-shield-alt text-primary me-1"></i>
                            سيتم تسجيل الطلبية باسمك واعتمادها من قِبل أمين المخزن الرئيسي وفق نظام FIFO المعتمد.
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ request()->query('from') == 'nursing' || !empty($lockedLocation) ? route('nursing-station.index') : url()->previous() }}" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 fw-bold shadow-sm">
                                <i class="fas fa-paper-plane me-1.5"></i> إرسال الطلبية للمخزن الرئيسي
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
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('transferForm');
        const addItemRowBtn = document.getElementById('addItemRow');
        const itemsTableBody = document.querySelector('#itemsTable tbody');
        const itemsCountBadge = document.getElementById('itemsCountBadge');
        const productsDatalist = document.getElementById('productsDatalist');

        // خريطة لتخزين بيانات المواد لسرعة المطابقة اللحظية
        const productsMap = {};
        if (productsDatalist) {
            Array.from(productsDatalist.options).forEach(opt => {
                const name = opt.value.trim().toLowerCase();
                productsMap[name] = {
                    id: opt.getAttribute('data-id'),
                    unit: opt.getAttribute('data-unit') || 'وحدة',
                    code: opt.getAttribute('data-code') || ''
                };
            });
        }

        let itemIndex = 0;

        function updateItemCounter() {
            const count = itemsTableBody.querySelectorAll('tr').length;
            itemsCountBadge.textContent = `${count} ${count === 1 ? 'مادة' : 'مواد'}`;
            // إعادة ترقيم السطور
            itemsTableBody.querySelectorAll('tr').forEach((tr, idx) => {
                const numEl = tr.querySelector('.row-num');
                if (numEl) numEl.textContent = idx + 1;
            });
        }

        function createItemRow(index) {
            return `
                <tr id="row${index}" class="item-row">
                    <td class="ps-3 fw-bold text-muted row-num">${itemsTableBody.children.length + 1}</td>
                    <td>
                        <div class="position-relative">
                            <input type="text" 
                                   list="productsDatalist" 
                                   class="form-control rounded-pill product-search-input" 
                                   placeholder="🔍 اكتب اسم المادة أو الدواء..." 
                                   autocomplete="off" 
                                   required>
                            <input type="hidden" 
                                   name="items[${index}][product_id]" 
                                   class="product-id-input" 
                                   required>
                            <div class="invalid-feedback small">يرجى اختيار مادة صحيحة من القائمة.</div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-secondary border rounded-pill px-3 py-1.5 product-unit-badge">
                            -
                        </span>
                    </td>
                    <td>
                        <div class="input-group input-group-sm justify-content-center">
                            <button type="button" class="btn btn-outline-secondary px-2.5 btn-step-down rounded-start-pill">-</button>
                            <input type="number" 
                                   name="items[${index}][qty]" 
                                   class="form-control text-center fw-bold item-qty-input" 
                                   min="1" 
                                   value="1" 
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

        // إضافة سطر جديد
        addItemRowBtn.addEventListener('click', function () {
            itemsTableBody.insertAdjacentHTML('beforeend', createItemRow(itemIndex));
            itemIndex++;
            updateItemCounter();
            // تركيز مباشر على حقل السطر الجديد
            const lastInput = itemsTableBody.querySelector('tr:last-child .product-search-input');
            if (lastInput) lastInput.focus();
        });

        // التعامل مع الأحداث التفاعلية داخل الجدول
        itemsTableBody.addEventListener('input', function (e) {
            if (e.target.classList.contains('product-search-input')) {
                const tr = e.target.closest('tr');
                const hiddenId = tr.querySelector('.product-id-input');
                const unitBadge = tr.querySelector('.product-unit-badge');
                const enteredVal = e.target.value.trim().toLowerCase();

                if (productsMap[enteredVal]) {
                    hiddenId.value = productsMap[enteredVal].id;
                    unitBadge.textContent = productsMap[enteredVal].unit;
                    unitBadge.className = 'badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 product-unit-badge fw-bold';
                    e.target.classList.remove('is-invalid');
                } else {
                    hiddenId.value = '';
                    unitBadge.textContent = '-';
                    unitBadge.className = 'badge bg-light text-secondary border rounded-pill px-3 py-1.5 product-unit-badge';
                }
            }
        });

        // التحكم في أزرار الزيادة والنقصان والحذف
        itemsTableBody.addEventListener('click', function (e) {
            const btn = e.target.closest('button');
            if (!btn) return;

            if (btn.classList.contains('remove-item-row')) {
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

        // إضافة أول سطر تلقائياً
        if (itemsTableBody.children.length === 0) {
            itemsTableBody.insertAdjacentHTML('beforeend', createItemRow(itemIndex));
            itemIndex++;
            updateItemCounter();
        }

        // التحقق قبل الإرسال لمنع إرسال مواد فارغة
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
                alert('يرجى اختيار المادة بشكل صحيح من القائمة المنسدلة والتأكد من تحديد كمية صحيحة.');
            }
        });
    });
</script>
@endsection
