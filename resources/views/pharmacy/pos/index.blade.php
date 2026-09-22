@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <!-- شريط علوي سريع للـ POS -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-dark text-white p-2">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-3">
                <span class="fs-5 fw-bold text-warning"><i class="fas fa-cash-register me-2"></i>نقطة بيع وصرف الصيدلية (POS)</span>
                <span class="badge bg-secondary font-monospace" id="clockDisplay">00:00:00</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-warning" id="btnShowHeldBills" data-bs-toggle="modal" data-bs-target="#heldBillsModal">
                    <i class="fas fa-pause-circle me-1"></i> الفواتير المعلقة (<span id="heldCountBadge">{{ $heldCount }}</span>)
                </button>
                <a href="{{ route('pharmacy.pos.sales.history') }}" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-history me-1"></i> سجل المبيعات
                </a>
                <button type="button" class="btn btn-sm btn-danger" id="btnClearCart" title="مسح الفاتورة الحالية">
                    <i class="fas fa-trash-alt me-1"></i> مسح
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- القسم الأيمن: جدول الفاتورة والبنود (Main Cart) -->
        <div class="col-lg-8">
            <!-- شريط المسح والبحث السريع -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-2">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-7">
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white"><i class="fas fa-barcode"></i></span>
                                <input type="text" id="barcodeInput" class="form-control form-control-lg font-monospace fw-bold" placeholder="امسح الباركود أو اكتب اسم الدواء / الرمز الوطني..." autofocus autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-syringe text-info"></i></span>
                                <select id="serviceSelect" class="form-select">
                                    <option value="">-- إضافة خدمة صيدلانية (حقن، قياس...) --</option>
                                    @foreach($pharmacyServices as $srv)
                                        <option value="{{ $srv->id }}" data-name="{{ $srv->name }}" data-price="{{ $srv->price }}" data-hi="{{ $srv->hi_price ?? $srv->price }}">
                                            {{ $srv->name }} ({{ number_format($srv->price) }} د.ع)
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-info" id="btnAddService"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- قائمة نتائج البحث المنسدلة التلقائية -->
                    <div id="searchResultsDropdown" class="list-group position-absolute w-75 shadow-lg d-none" style="z-index: 1050; max-height: 350px; overflow-y: auto;"></div>
                </div>
            </div>

            <!-- جدول بنود الفاتورة -->
            <div class="card border-0 shadow-sm rounded-3" style="min-height: 480px;">
                <div class="card-header bg-white py-2 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold text-dark"><i class="fas fa-shopping-basket me-2 text-primary"></i>بنود الفاتورة الحالية</span>
                    <span class="badge bg-primary rounded-pill" id="cartCountBadge">0 صنف</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="cartTable">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 35%;">الصنف / الخدمة</th>
                                    <th style="width: 15%;">الوحدة</th>
                                    <th style="width: 12%;">الكمية</th>
                                    <th style="width: 15%;">السعر (د.ع)</th>
                                    <th style="width: 13%;">المجموع</th>
                                    <th style="width: 5%;" class="text-center">حذف</th>
                                </tr>
                            </thead>
                            <tbody id="cartTableBody">
                                <tr id="emptyCartRow">
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-barcode fa-3x mb-3 text-secondary opacity-50"></i>
                                        <p class="mb-1 fw-bold">الفاتورة فارغة</p>
                                        <p class="small mb-0">قم بمسح باركود الدواء أو ابحث بالاسم لإضافته مباشرة.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- القسم الأيسر: بيانات المريض، جهة التأمين، والحسابات والدفع -->
        <div class="col-lg-4">
            <!-- بطاقة المريض ونوع التأمين -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white py-2 border-bottom">
                    <h6 class="fw-bold text-primary mb-0"><i class="fas fa-user-injured me-2"></i>بيانات المريض والتأمين</h6>
                </div>
                <div class="card-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-muted mb-1">نوع المبيعات</label>
                        <select id="saleType" class="form-select form-select-sm">
                            <option value="direct_otc">مباشر / صيدلية خارجية (OTC)</option>
                            <option value="prescription">وصفة استشارية</option>
                            <option value="emergency">طوارئ</option>
                            <option value="inpatient">مريض راقد</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold text-muted mb-1">اسم المريض</label>
                        <input type="text" id="patientName" class="form-control form-control-sm" placeholder="اسم المريض (اختياري للـ OTC)">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold text-muted mb-1">جهة التأمين / الضمان</label>
                        <select id="insuranceType" class="form-select form-select-sm fw-bold">
                            <option value="none" selected>نقدي عادي (كاش 100%)</option>
                            <option value="health_insurance">هيئة الضمان الصحي الوطني</option>
                            <option value="interior_ministry">ضمان وزارة الداخلية</option>
                        </select>
                    </div>

                    <!-- حقول خاصة بالضمان الصحي -->
                    <div id="healthInsuranceFields" class="d-none p-2 bg-light rounded border mb-2">
                        <label class="form-label small fw-bold text-success mb-1">فئة الضمان الصحي للمريض</label>
                        <select id="healthInsuranceCategory" class="form-select form-select-sm mb-2">
                            <option value="">-- اختر الفئة --</option>
                            @foreach($insuranceCategories as $cat)
                                <option value="{{ $cat->id }}" data-copay="{{ $cat->medication_copay }}">
                                    الفئة {{ $cat->code }} - {{ $cat->name }} (نسبة الاستقطاع: {{ $cat->medication_copay }}%)
                                </option>
                            @endforeach
                        </select>

                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label small fw-bold mb-0">نسبة التحمل المطبقة (%):</label>
                            <input type="number" id="copayPercentage" class="form-control form-control-sm w-25 text-center fw-bold" value="0" min="0" max="100">
                        </div>
                    </div>
                </div>
            </div>

            <!-- بطاقة الملخص المالي والدفع -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-2 border-bottom">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-calculator me-2 text-success"></i>الملخص المالي والتحصيل</h6>
                </div>
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">المجموع الإجمالي:</span>
                        <span class="fs-5 fw-bold text-dark" id="displayTotal">0 د.ع</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2 text-danger">
                        <span class="fw-bold">حصة المريض (المطلوب سداده):</span>
                        <span class="fs-4 fw-bold" id="displayPatientShare">0 د.ع</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 text-success" id="insuranceShareRow">
                        <span class="small fw-semibold">حصة الضمان (مطالبة):</span>
                        <span class="fw-bold" id="displayInsuranceShare">0 د.ع</span>
                    </div>

                    <hr class="my-2">

                    <!-- أزرار الإجراءات والدفع السريع -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-success btn-lg shadow-sm fw-bold py-2" id="btnPayDirect">
                            <i class="fas fa-check-circle me-1"></i> قبض مباشر ونهاية الفاتورة (F8)
                        </button>

                        <button type="button" class="btn btn-outline-primary fw-bold" id="btnSendCentralCashier">
                            <i class="fas fa-file-invoice-dollar me-1"></i> إرسال للكاشير المركزي (F9)
                        </button>

                        <button type="button" class="btn btn-outline-warning text-dark fw-bold" id="btnHoldBill">
                            <i class="fas fa-pause me-1"></i> تعليق الفاتورة مؤقتاً (Hold)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal البدائل الدوائية العلمية -->
<div class="modal fade" id="alternativesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning bg-opacity-10">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-random me-2 text-warning"></i>البدائل الدوائية العلمية المكافئة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom">
                    <span class="text-muted small">الدواء المطلوب:</span>
                    <h6 class="fw-bold text-primary mb-0" id="altModalMedName">-</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>اسم البديل المكافئ</th>
                                <th>الشكل والعيار</th>
                                <th>الرصيد المتوفر</th>
                                <th>سعر العلبة</th>
                                <th>سعر الشريط</th>
                                <th class="text-center">اختيار البديل</th>
                            </tr>
                        </thead>
                        <tbody id="altModalTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal الفواتير المعلقة (Held Bills) -->
<div class="modal fade" id="heldBillsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-pause-circle me-2 text-warning"></i>الفواتير المعلقة (Held Invoices)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>المريض</th>
                                <th>عدد البنود</th>
                                <th>المبلغ الإجمالي</th>
                                <th>توقيت التعليق</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="heldBillsTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ساعة رقمية سريعة
    function updateClock() {
        const now = new Date();
        document.getElementById('clockDisplay').textContent = now.toLocaleTimeString('ar-IQ');
    }
    setInterval(updateClock, 1000);
    updateClock();

    // حالة سلة المشتريات
    let cart = [];
    let currentAlternativeRowIndex = null;

    // عناصر الـ DOM
    const barcodeInput = document.getElementById('barcodeInput');
    const searchDropdown = document.getElementById('searchResultsDropdown');
    const cartTableBody = document.getElementById('cartTableBody');
    const emptyCartRow = document.getElementById('emptyCartRow');
    const cartCountBadge = document.getElementById('cartCountBadge');
    const displayTotal = document.getElementById('displayTotal');
    const displayPatientShare = document.getElementById('displayPatientShare');
    const displayInsuranceShare = document.getElementById('displayInsuranceShare');
    const insuranceTypeSelect = document.getElementById('insuranceType');
    const healthInsuranceFields = document.getElementById('healthInsuranceFields');
    const healthInsuranceCategorySelect = document.getElementById('healthInsuranceCategory');
    const copayPercentageInput = document.getElementById('copayPercentage');

    // صوت تنبيه نقي عند مسح الباركود (Web Audio API)
    function playBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(1200, ctx.currentTime);
            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.1);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.1);
        } catch (e) {}
    }

    // تبديل ظهور حقول الضمان الصحي
    insuranceTypeSelect.addEventListener('change', function () {
        if (this.value === 'health_insurance') {
            healthInsuranceFields.classList.remove('d-none');
        } else {
            healthInsuranceFields.classList.add('d-none');
            copayPercentageInput.value = 0;
        }
        recalculateCart();
    });

    healthInsuranceCategorySelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const copay = selected.getAttribute('data-copay') || 0;
        copayPercentageInput.value = copay;
        recalculateCart();
    });

    copayPercentageInput.addEventListener('input', recalculateCart);

    // البحث السريع اللحظي
    let searchTimeout = null;
    barcodeInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const q = this.value.trim();
        if (q.length < 2) {
            searchDropdown.classList.add('d-none');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`{{ route('pharmacy.pos.search') }}?q=${encodeURIComponent(q)}`)
                .then(res => res.json())
                .then(data => {
                    renderSearchResults(data);
                });
        }, 200);
    });

    // إدخال مباشر عبر قارئ الباركود عند الضغط على Enter
    barcodeInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const q = this.value.trim();
            if (!q) return;

            fetch(`{{ route('pharmacy.pos.search') }}?q=${encodeURIComponent(q)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.medicines && data.medicines.length > 0) {
                        // مطابقة تامة للباركود أولاً
                        const exact = data.medicines.find(m => m.barcode === q || m.sub_barcode === q) || data.medicines[0];
                        const isSub = (exact.sub_barcode === q);
                        addItemToCart(exact, isSub ? 'sub_unit' : 'main_unit');
                        barcodeInput.value = '';
                        searchDropdown.classList.add('d-none');
                    } else {
                        alert('لم يتم العثور على أي صنف بهذا الباركود!');
                    }
                });
        }
    });

    // عرض نتائج البحث المنسدلة
    function renderSearchResults(data) {
        searchDropdown.innerHTML = '';
        const allItems = [...(data.medicines || []), ...(data.services || [])];

        if (allItems.length === 0) {
            searchDropdown.classList.add('d-none');
            return;
        }

        allItems.forEach(item => {
            const a = document.createElement('a');
            a.href = 'javascript:void(0)';
            a.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2';

            if (item.type === 'medicine') {
                const stockBadge = (item.total_stock <= 0 && item.total_open_sub_units <= 0)
                    ? `<span class="badge bg-danger">نفد الرصيد</span>`
                    : `<span class="badge bg-success">${item.total_stock} علبة</span>`;

                const altBadge = (item.alternatives && item.alternatives.length > 0 && item.total_stock <= 0)
                    ? `<span class="badge bg-warning text-dark ms-1"><i class="fas fa-random me-1"></i>يوجد بدائل</span>`
                    : '';

                a.innerHTML = `
                    <div>
                        <span class="fw-bold text-primary">${item.name}</span>
                        <small class="text-muted fst-italic">(${item.dosage_form || ''} ${item.strength || ''})</small>
                        ${altBadge}
                    </div>
                    <div>
                        ${stockBadge}
                        <span class="fw-bold text-dark ms-2">${Number(item.sale_price).toLocaleString()} د.ع</span>
                    </div>
                `;

                a.addEventListener('click', () => {
                    addItemToCart(item, 'main_unit');
                    barcodeInput.value = '';
                    searchDropdown.classList.add('d-none');
                    barcodeInput.focus();
                });
            } else {
                a.innerHTML = `
                    <div>
                        <span class="fw-bold text-info"><i class="fas fa-syringe me-1"></i>${item.name}</span>
                        <small class="text-muted">(خدمة صيدلانية)</small>
                    </div>
                    <div>
                        <span class="fw-bold text-dark">${Number(item.price).toLocaleString()} د.ع</span>
                    </div>
                `;

                a.addEventListener('click', () => {
                    addServiceToCart(item);
                    barcodeInput.value = '';
                    searchDropdown.classList.add('d-none');
                    barcodeInput.focus();
                });
            }

            searchDropdown.appendChild(a);
        });

        searchDropdown.classList.remove('d-none');
    }

    // إضافة خدمة صيدلانية من القائمة المنسدلة
    document.getElementById('btnAddService').addEventListener('click', function () {
        const select = document.getElementById('serviceSelect');
        const selected = select.options[select.selectedIndex];
        if (!selected.value) return;

        addServiceToCart({
            id: selected.value,
            name: selected.getAttribute('data-name'),
            price: parseFloat(selected.getAttribute('data-price')) || 0,
            hi_price: parseFloat(selected.getAttribute('data-hi')) || 0,
        });

        select.value = '';
    });

    // إضافة دواء للسلة
    function addItemToCart(medicine, unitType = 'main_unit') {
        playBeep();

        // هل الصنف موجود بالسلة بنفس الوحدة؟
        const existing = cart.find(i => i.item_type === 'medicine' && i.medicine_id === medicine.id && i.unit_type === unitType);
        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push({
                item_type: 'medicine',
                medicine_id: medicine.id,
                name: medicine.name,
                generic_name: medicine.generic_name,
                main_unit: medicine.main_unit || 'علبة',
                sub_unit: medicine.sub_unit || 'شريط',
                sub_units_count: medicine.sub_units_count || 1,
                unit_type: unitType,
                quantity: 1,
                sale_price: parseFloat(medicine.sale_price) || 0,
                sub_unit_sale_price: parseFloat(medicine.sub_unit_sale_price) || (parseFloat(medicine.sale_price) / (medicine.sub_units_count || 1)),
                hi_price: parseFloat(medicine.hi_price) || parseFloat(medicine.sale_price),
                moi_price: parseFloat(medicine.moi_price) || parseFloat(medicine.sale_price),
                is_insurance_covered: medicine.is_insurance_covered,
                total_stock: medicine.total_stock,
                total_open_sub_units: medicine.total_open_sub_units,
                alternatives: medicine.alternatives || [],
            });
        }

        renderCart();
    }

    // إضافة خدمة للسلة
    function addServiceToCart(service) {
        playBeep();
        const existing = cart.find(i => i.item_type === 'service' && i.service_id === service.id);
        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push({
                item_type: 'service',
                service_id: service.id,
                name: service.name,
                unit_type: 'main_unit',
                quantity: 1,
                price: parseFloat(service.price) || 0,
                hi_price: parseFloat(service.hi_price) || parseFloat(service.price),
                moi_price: parseFloat(service.moi_price) || parseFloat(service.price),
            });
        }

        renderCart();
    }

    // إعادة رسم جدول السلة
    function renderCart() {
        if (cart.length === 0) {
            cartTableBody.innerHTML = `
                <tr id="emptyCartRow">
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="fas fa-barcode fa-3x mb-3 text-secondary opacity-50"></i>
                        <p class="mb-1 fw-bold">الفاتورة فارغة</p>
                        <p class="small mb-0">قم بمسح باركود الدواء أو ابحث بالاسم لإضافته مباشرة.</p>
                    </td>
                </tr>
            `;
            cartCountBadge.textContent = '0 صنف';
            recalculateCart();
            return;
        }

        cartTableBody.innerHTML = '';
        cartCountBadge.textContent = `${cart.length} صنف`;

        cart.forEach((item, index) => {
            const tr = document.createElement('tr');

            // حساب السعر الفعلي حسب الوحدة والتأمين
            const unitPrice = getItemUnitPrice(item);
            const subtotal = unitPrice * item.quantity;

            // اسم الصنف وأزرار البدائل
            let nameHtml = `<span class="fw-bold text-dark">${item.name}</span>`;
            if (item.item_type === 'medicine') {
                if (item.total_stock <= 0 && item.total_open_sub_units <= 0) {
                    nameHtml += ` <span class="badge bg-danger small">رصيد صفري</span>`;
                }
                if (item.alternatives && item.alternatives.length > 0) {
                    nameHtml += ` <button type="button" class="btn btn-sm btn-outline-warning text-dark py-0 px-1 ms-1 btn-open-alt" data-index="${index}" title="عرض البدائل المتاحة"><i class="fas fa-random me-1"></i>بدائل (${item.alternatives.length})</button>`;
                }
            } else {
                nameHtml += ` <span class="badge bg-info text-dark small">خدمة</span>`;
            }

            // محدد الوحدة (علبة أو شريط للأدوية)
            let unitHtml = '';
            if (item.item_type === 'medicine') {
                unitHtml = `
                    <select class="form-select form-select-sm cart-unit-select" data-index="${index}">
                        <option value="main_unit" ${item.unit_type === 'main_unit' ? 'selected' : ''}>${item.main_unit} (كاملة)</option>
                        <option value="sub_unit" ${item.unit_type === 'sub_unit' ? 'selected' : ''}>${item.sub_unit} (مفرد)</option>
                    </select>
                `;
            } else {
                unitHtml = `<span class="text-muted small">خدمة</span>`;
            }

            tr.innerHTML = `
                <td>${index + 1}</td>
                <td>${nameHtml}</td>
                <td>${unitHtml}</td>
                <td>
                    <input type="number" step="1" min="1" class="form-control form-control-sm text-center fw-bold cart-qty-input" data-index="${index}" value="${item.quantity}">
                </td>
                <td class="fw-semibold text-dark">${Number(unitPrice).toLocaleString()} د.ع</td>
                <td class="fw-bold text-primary">${Number(subtotal).toLocaleString()} د.ع</td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm border-0 btn-remove-item" data-index="${index}"><i class="fas fa-times"></i></button>
                </td>
            `;

            cartTableBody.appendChild(tr);
        });

        // ربط الأحداث
        document.querySelectorAll('.cart-unit-select').forEach(sel => {
            sel.addEventListener('change', function () {
                const idx = this.getAttribute('data-index');
                cart[idx].unit_type = this.value;
                renderCart();
            });
        });

        document.querySelectorAll('.cart-qty-input').forEach(inp => {
            inp.addEventListener('change', function () {
                const idx = this.getAttribute('data-index');
                cart[idx].quantity = Math.max(0.1, parseFloat(this.value) || 1);
                renderCart();
            });
        });

        document.querySelectorAll('.btn-remove-item').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = this.getAttribute('data-index');
                cart.splice(idx, 1);
                renderCart();
            });
        });

        document.querySelectorAll('.btn-open-alt').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = this.getAttribute('data-index');
                openAlternativesModal(idx);
            });
        });

        recalculateCart();
    }

    // حساب سعر الوحدة الفعلي للعنصر
    function getItemUnitPrice(item) {
        const insType = insuranceTypeSelect.value;
        const isSub = (item.unit_type === 'sub_unit');

        if (item.item_type === 'medicine') {
            if (insType === 'health_insurance' && item.is_insurance_covered) {
                return isSub ? Math.round(item.hi_price / item.sub_units_count) : item.hi_price;
            } else if (insType === 'interior_ministry' && item.is_insurance_covered) {
                return isSub ? Math.round(item.moi_price / item.sub_units_count) : item.moi_price;
            } else {
                return isSub ? item.sub_unit_sale_price : item.sale_price;
            }
        } else {
            if (insType === 'health_insurance') return item.hi_price;
            if (insType === 'interior_ministry') return item.moi_price;
            return item.price;
        }
    }

    // إعادة احتساب المجاميع وحصص المريض والضمان
    function recalculateCart() {
        let total = 0;
        let patientShare = 0;
        let insuranceShare = 0;

        const insType = insuranceTypeSelect.value;
        const copayPercent = parseFloat(copayPercentageInput.value) || 0;

        cart.forEach(item => {
            const unitPrice = getItemUnitPrice(item);
            const subtotal = unitPrice * item.quantity;
            total += subtotal;

            if (insType === 'none') {
                patientShare += subtotal;
            } else {
                // إذا كان الصنف مشمولاً بالتأمين
                const isCovered = (item.item_type === 'service') || (item.item_type === 'medicine' && item.is_insurance_covered);
                if (isCovered) {
                    const pItemShare = Math.round(subtotal * (copayPercent / 100));
                    patientShare += pItemShare;
                    insuranceShare += (subtotal - pItemShare);
                } else {
                    patientShare += subtotal;
                }
            }
        });

        displayTotal.textContent = `${Number(total).toLocaleString()} د.ع`;
        displayPatientShare.textContent = `${Number(patientShare).toLocaleString()} د.ع`;
        displayInsuranceShare.textContent = `${Number(insuranceShare).toLocaleString()} د.ع`;
    }

    // فتح نافذة البدائل
    function openAlternativesModal(index) {
        currentAlternativeRowIndex = index;
        const item = cart[index];
        document.getElementById('altModalMedName').textContent = `${item.name} (${item.main_unit} / ${item.sub_unit})`;

        const tbody = document.getElementById('altModalTableBody');
        tbody.innerHTML = '';

        if (!item.alternatives || item.alternatives.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-3 text-muted">لا توجد بدائل مسجلة لهذا الدواء.</td></tr>`;
        } else {
            item.alternatives.forEach(alt => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        <span class="fw-bold text-primary">${alt.name}</span>
                    </td>
                    <td>${alt.dosage_form || ''} ${alt.strength || ''}</td>
                    <td>
                        ${alt.total_stock > 0 ? `<span class="badge bg-success">${alt.total_stock} علبة</span>` : `<span class="badge bg-danger">نفد</span>`}
                    </td>
                    <td class="fw-bold">${Number(alt.sale_price).toLocaleString()} د.ع</td>
                    <td>${Number(alt.sub_unit_sale_price).toLocaleString()} د.ع</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-primary btn-replace-with-alt" data-alt-id="${alt.id}">
                            <i class="fas fa-check me-1"></i> استبدال بهذا
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.querySelectorAll('.btn-replace-with-alt').forEach(btn => {
                btn.addEventListener('click', function () {
                    const altId = this.getAttribute('data-alt-id');
                    fetch(`{{ route('pharmacy.pos.search') }}?q=${encodeURIComponent(altId)}`)
                        .then(r => r.json())
                        .then(d => {
                            if (d.medicines && d.medicines.length > 0) {
                                const newMed = d.medicines[0];
                                // استبدال الصنف في السلة
                                cart[currentAlternativeRowIndex] = {
                                    item_type: 'medicine',
                                    medicine_id: newMed.id,
                                    name: newMed.name,
                                    generic_name: newMed.generic_name,
                                    main_unit: newMed.main_unit || 'علبة',
                                    sub_unit: newMed.sub_unit || 'شريط',
                                    sub_units_count: newMed.sub_units_count || 1,
                                    unit_type: 'main_unit',
                                    quantity: cart[currentAlternativeRowIndex].quantity,
                                    sale_price: parseFloat(newMed.sale_price) || 0,
                                    sub_unit_sale_price: parseFloat(newMed.sub_unit_sale_price) || 0,
                                    hi_price: parseFloat(newMed.hi_price) || parseFloat(newMed.sale_price),
                                    moi_price: parseFloat(newMed.moi_price) || parseFloat(newMed.sale_price),
                                    is_insurance_covered: newMed.is_insurance_covered,
                                    total_stock: newMed.total_stock,
                                    total_open_sub_units: newMed.total_open_sub_units,
                                    alternatives: newMed.alternatives || [],
                                };
                                renderCart();
                                bootstrap.Modal.getInstance(document.getElementById('alternativesModal')).hide();
                            }
                        });
                });
            });
        }

        new bootstrap.Modal(document.getElementById('alternativesModal')).show();
    }

    // معالجة الدفع وحفظ الفاتورة
    function processSale(paymentRoute, isHeld = false) {
        if (cart.length === 0) {
            alert('الفاتورة فارغة! يرجى إضافة أصناف أولاً.');
            return;
        }

        const payload = {
            sale_type: document.getElementById('saleType').value,
            patient_name: document.getElementById('patientName').value.trim() || 'مريض مباشر OTC',
            insurance_type: insuranceTypeSelect.value,
            health_insurance_category_id: document.getElementById('healthInsuranceCategory').value || null,
            copay_percentage: parseFloat(copayPercentageInput.value) || 0,
            payment_route: paymentRoute,
            is_held: isHeld ? 1 : 0,
            items: cart.map(i => ({
                item_type: i.item_type,
                medicine_id: i.medicine_id || null,
                service_id: i.service_id || null,
                unit_type: i.unit_type,
                quantity: i.quantity,
            })),
            _token: '{{ csrf_token() }}',
        };

        fetch('{{ route("pharmacy.pos.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify(payload),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (isHeld) {
                    alert('تم تعليق الفاتورة بنجاح.');
                    cart = [];
                    renderCart();
                    loadHeldCount();
                } else {
                    // فتح نافذة طباعة الوصل الحراري 80mm
                    window.open(data.print_url, '_blank', 'width=450,height=600');
                    cart = [];
                    renderCart();
                    document.getElementById('patientName').value = '';
                }
            } else {
                alert('فشل حفظ الفاتورة: ' + (data.message || 'حدث خطأ غير متوقع'));
            }
        })
        .catch(err => {
            alert('حدث خطأ في الاتصال بالخادم!');
        });
    }

    // أزرار الحفظ والدفع
    document.getElementById('btnPayDirect').addEventListener('click', () => processSale('pharmacy_cashier', false));
    document.getElementById('btnSendCentralCashier').addEventListener('click', () => processSale('central_cashier', false));
    document.getElementById('btnHoldBill').addEventListener('click', () => processSale('pharmacy_cashier', true));
    document.getElementById('btnClearCart').addEventListener('click', () => {
        if (cart.length > 0 && confirm('هل تريد مسح الفاتورة الحالية؟')) {
            cart = [];
            renderCart();
        }
    });

    // اختصارات لوحة المفاتيح
    document.addEventListener('keydown', function (e) {
        if (e.key === 'F8') {
            e.preventDefault();
            document.getElementById('btnPayDirect').click();
        } else if (e.key === 'F9') {
            e.preventDefault();
            document.getElementById('btnSendCentralCashier').click();
        }
    });

    // تحديث عدد الفواتير المعلقة
    function loadHeldCount() {
        fetch('{{ route("pharmacy.pos.held") }}')
            .then(r => r.json())
            .then(data => {
                document.getElementById('heldCountBadge').textContent = data.length;
            });
    }

    // فتح نافذة الفواتير المعلقة
    document.getElementById('btnShowHeldBills').addEventListener('click', function () {
        const tbody = document.getElementById('heldBillsTableBody');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3">جاري التحميل...</td></tr>';

        fetch('{{ route("pharmacy.pos.held") }}')
            .then(r => r.json())
            .then(data => {
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">لا توجد فواتير معلقة حالياً.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                data.forEach(sale => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="font-monospace fw-bold text-primary">${sale.invoice_number}</td>
                        <td>${sale.patient_name}</td>
                        <td><span class="badge bg-light text-dark border">${sale.items ? sale.items.length : 0} بنود</span></td>
                        <td class="fw-bold">${Number(sale.total_amount).toLocaleString()} د.ع</td>
                        <td><small class="text-muted">${sale.created_at ? new Date(sale.created_at).toLocaleTimeString('ar-IQ') : ''}</small></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-success btn-resume-held me-1" data-id="${sale.id}"><i class="fas fa-play me-1"></i> استئناف</button>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-held" data-id="${sale.id}"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                document.querySelectorAll('.btn-resume-held').forEach(btn => {
                    btn.addEventListener('click', function () {
                        const id = this.getAttribute('data-id');
                        fetch(`{{ url('/pharmacy/pos/held') }}/${id}/resume`)
                            .then(r => r.json())
                            .then(d => {
                                if (d.success && d.sale) {
                                    // تحميل الفاتورة للسلة
                                    cart = d.sale.items.map(it => ({
                                        item_type: it.item_type,
                                        medicine_id: it.medicine_id,
                                        service_id: it.service_id,
                                        name: it.medicine ? it.medicine.name : (it.service ? it.service.name : 'صنف'),
                                        main_unit: it.medicine ? it.medicine.main_unit : 'علبة',
                                        sub_unit: it.medicine ? it.medicine.sub_unit : 'شريط',
                                        sub_units_count: it.medicine ? it.medicine.sub_units_count : 1,
                                        unit_type: it.unit_type,
                                        quantity: parseFloat(it.quantity) || 1,
                                        sale_price: it.medicine ? parseFloat(it.medicine.sale_price) : (it.service ? parseFloat(it.service.price) : 0),
                                        sub_unit_sale_price: it.medicine ? parseFloat(it.medicine.sub_unit_sale_price) : 0,
                                        hi_price: it.medicine ? parseFloat(it.medicine.hi_price) : 0,
                                        moi_price: it.medicine ? parseFloat(it.medicine.moi_price) : 0,
                                        is_insurance_covered: it.medicine ? it.medicine.is_insurance_covered : true,
                                        total_stock: it.medicine ? it.medicine.total_stock : 10,
                                        total_open_sub_units: 0,
                                        alternatives: [],
                                    }));

                                    document.getElementById('patientName').value = d.sale.patient_name || '';
                                    insuranceTypeSelect.value = d.sale.insurance_type || 'none';
                                    copayPercentageInput.value = d.sale.copay_percentage || 0;

                                    renderCart();
                                    bootstrap.Modal.getInstance(document.getElementById('heldBillsModal')).hide();

                                    // حذف الفاتورة المعلقة بعد استئنافها لتجنب التكرار
                                    fetch(`{{ url('/pharmacy/pos/held') }}/${id}`, {
                                        method: 'DELETE',
                                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                    }).then(() => loadHeldCount());
                                }
                            });
                    });
                });

                document.querySelectorAll('.btn-delete-held').forEach(btn => {
                    btn.addEventListener('click', function () {
                        if (confirm('هل أنت متأكد من حذف هذه الفاتورة المعلقة؟')) {
                            const id = this.getAttribute('data-id');
                            fetch(`{{ url('/pharmacy/pos/held') }}/${id}`, {
                                method: 'DELETE',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            }).then(() => {
                                document.getElementById('btnShowHeldBills').click();
                                loadHeldCount();
                            });
                        }
                    });
                });
            });
    });
});
</script>
@endpush
@endsection
