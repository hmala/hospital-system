@extends('layouts.app')

@section('content')
<style>
/* POS Custom Layout Styling */
.pos-container {
    min-height: calc(100vh - 120px);
}
.pos-search-wrapper {
    position: relative;
}
.pos-search-results {
    position: absolute;
    top: 100%;
    right: 0;
    left: 0;
    z-index: 1040;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    max-height: 380px;
    overflow-y: auto;
    border: 1px solid #e2e8f0;
    margin-top: 4px;
}
.pos-search-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.pos-search-item:last-child {
    border-bottom: none;
}
.pos-search-item:hover, .pos-search-item.active {
    background-color: #f8fafc;
}
.pos-cart-table th {
    font-size: 0.82rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background-color: #f8fafc;
    color: #475569;
    border-top: none;
}
.pos-cart-table td {
    vertical-align: middle;
    padding: 0.75rem 0.6rem;
}
.qty-btn {
    width: 28px;
    height: 28px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-weight: bold;
}
.qty-input {
    width: 50px;
    text-align: center;
    font-weight: bold;
    border-radius: 6px;
    padding: 2px 4px;
    font-size: 0.9rem;
}
.unit-toggle-btn {
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}
/* Live Prescription Stream Cards */
.rx-stream-container {
    max-height: calc(100vh - 240px);
    overflow-y: auto;
    padding-right: 2px;
}
.rx-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1rem;
    margin-bottom: 0.85rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    border-right: 4px solid #3b82f6;
}
.rx-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(0,0,0,0.06);
}
.rx-card.rx-emergency {
    border-right-color: #ef4444;
    background: linear-gradient(to left, #ffffff, #fef2f2);
}
.rx-card.rx-ready {
    border-right-color: #10b981;
    background: linear-gradient(to left, #ffffff, #f0fdf4);
}
.rx-card.rx-pending {
    border-right-color: #f59e0b;
    background: linear-gradient(to left, #ffffff, #fffbeb);
}
.filter-pill.active {
    background-color: #0d6efd !important;
    color: #fff !important;
    border-color: #0d6efd !important;
}
</style>

<div class="container-fluid py-3 px-lg-4 pos-container">

    <!-- 1. الشريط العلوي لمحطة الصيدلية ونقطة البيع -->
    <div class="card border-0 shadow-sm rounded-4 mb-3 bg-dark text-white p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white p-2 rounded-3 fs-4 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                    <i class="fas fa-pills"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-white d-flex align-items-center gap-2">
                        صيدلية المستشفى | نقطة البيع وصرف الأدوية
                        <span class="badge bg-success-subtle text-success fs-6 rounded-pill px-3 py-1 border border-success">
                            <i class="fas fa-satellite-dish me-1 fa-fade"></i> استقبال مباشر
                        </span>
                    </h5>
                    <small class="text-secondary">خط صرف وتجهيز الأدوية والبيع المباشر بنظام FEFO والربط الإلكتروني</small>
                </div>
            </div>

            <!-- أزرار الإجراءات السريعة -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                @if($heldCount > 0)
                <a href="{{ route('pharmacy.pos.held') }}" class="btn btn-warning btn-sm px-3 py-2 rounded-3 fw-bold shadow-xs">
                    <i class="fas fa-pause-circle me-1"></i> فواتير معلقة ({{ $heldCount }})
                </a>
                @endif

                <button type="button" class="btn btn-outline-light btn-sm px-3 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#manualSearchModal">
                    <i class="fas fa-search me-1"></i> فحص رصيد / بدائل
                </button>

                <a href="{{ route('pharmacy.pos.sales.history') }}" class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-3 text-white">
                    <i class="fas fa-history me-1"></i> سجل المبيعات
                </a>

                <div class="form-check form-switch small mb-0 ms-2 text-white d-flex align-items-center gap-2">
                    <input class="form-check-input" type="checkbox" id="autoRefreshSwitch" checked>
                    <label class="form-check-label text-secondary small" for="autoRefreshSwitch">تحديث الوصفات تلقائياً</label>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. الشبكة الرئيسية المنقسمة (Split View): كاشير يميناً + طابور الوصفات يساراً -->
    <div class="row g-3">

        <!-- ========================================== -->
        <!-- الجانب الأيمن: كاشير وسلة نقطة البيع (POS Cart) -->
        <!-- ========================================== -->
        <div class="col-12 col-xl-7 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100 d-flex flex-column bg-white">

                <!-- رأس السلة: حقل البحث السريع وقارئ الباركود -->
                <div class="card-header bg-white border-0 pt-3 px-3 px-md-4 pb-2">
                    <div class="pos-search-wrapper mb-2">
                        <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden border">
                            <span class="input-group-text bg-light border-0 text-primary ps-3">
                                <i class="fas fa-barcode fa-lg"></i>
                            </span>
                            <input type="text" 
                                   id="posOmniSearchInput" 
                                   class="form-control border-0 py-2 fs-6" 
                                   placeholder="امسح الباركود، أو اكتب اسم الدواء أو المادة الفعالة... (اضغط Enter للإضافة السريعة)" 
                                   autocomplete="off" 
                                   autofocus>
                            <span class="input-group-text bg-light border-0 text-muted pe-3">
                                <kbd class="bg-white border text-secondary px-2 py-1 small">F2 للتركيز</kbd>
                            </span>
                        </div>

                        <!-- قائمة نتائج البحث اللحظي المنسدلة -->
                        <div id="posSearchResultsDropdown" class="pos-search-results" style="display: none;"></div>
                    </div>

                    <!-- شريط بيانات العميل والوضع المالي المستمد آلياً من الاستعلامات -->
                    <div class="row g-2 align-items-center pt-2 pb-1 border-bottom">
                        <div class="col-12 col-md-5">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light text-muted"><i class="fas fa-user"></i></span>
                                <input type="text" id="posPatientNameInput" class="form-control fw-semibold" placeholder="اسم المريض (اختياري للبيع المباشر)" value="مريض مباشر OTC">
                            </div>
                        </div>

                        <div class="col-12 col-md-7 text-md-end">
                            <div id="receptionInsuranceStatusContainer" class="d-inline-flex align-items-center gap-1">
                                <span class="badge bg-light text-dark border px-3 py-1.5" id="posActiveBillTypeBadge">
                                    <i class="fas fa-shopping-bag text-primary me-1"></i> بيع مباشر OTC (تسعيرة الصيدلية كاش)
                                </span>
                            </div>
                            <input type="hidden" id="posInsuranceTypeSelect" value="none">
                            <input type="hidden" id="posInsuranceCategorySelect" value="">
                        </div>
                    </div>

                    <!-- شريط تنبيه إذا كانت الفاتورة محملة من وصفة طبية إلكترونية -->
                    <div id="loadedPrescriptionBanner" class="alert alert-info border-2 border-info shadow-xs rounded-3 p-2 mb-0 mt-2 d-flex justify-content-between align-items-center" style="display: none;">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary fs-6 px-2.5 py-1" id="bannerRxNumber">RX-0000</span>
                            <span class="small fw-bold text-dark" id="bannerRxDoctor">د. الطبيب</span>
                            <span class="badge bg-success-subtle text-success border border-success" id="bannerRxSource">وصفة إلكترونية</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 rounded-2" onclick="unloadPrescriptionFromCart()" title="إلغاء ربط الوصفة والعودة للبيع المباشر">
                            <i class="fas fa-times me-1"></i> فك الربط
                        </button>
                    </div>
                </div>

                <!-- جدول سلة الفاتورة الحالية -->
                <div class="card-body p-0 flex-grow-1 overflow-auto" style="min-height: 280px; max-height: calc(100vh - 460px);">
                    <div class="table-responsive h-100">
                        <table class="table table-hover pos-cart-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 45px;" class="text-center">#</th>
                                    <th>الصنف الدوائي / المستلزم</th>
                                    <th style="width: 130px;" class="text-center">الوحدة المباعة</th>
                                    <th style="width: 140px;" class="text-center">الكمية</th>
                                    <th style="width: 110px;" class="text-end">السعر</th>
                                    <th style="width: 120px;" class="text-end">المجموع</th>
                                    <th style="width: 50px;" class="text-center">حذف</th>
                                </tr>
                            </thead>
                            <tbody id="posCartTableBody">
                                <!-- سيتم تعبئة الأصناف عبر JavaScript -->
                            </tbody>
                        </table>

                        <!-- حالة السلة الفارغة -->
                        <div id="posCartEmptyState" class="text-center py-5 text-muted">
                            <div class="mb-3">
                                <i class="fas fa-shopping-cart fa-3x text-secondary opacity-25"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">سلة الفاتورة فارغة حالياً</h6>
                            <p class="small text-secondary mb-0">امسح الباركود، أو ابحث عن دواء، أو اضغط <strong>«تحميل للسلة»</strong> من قائمة الوصفات يساراً.</p>
                        </div>
                    </div>
                </div>

                <!-- أسفل السلة: ملخص الفاتورة وأزرار الدفع السريع -->
                <div class="card-footer bg-light border-top p-3 p-md-4">
                    <div class="row g-3 align-items-center">
                        <!-- مؤشرات المبالغ المالية -->
                        <div class="col-12 col-md-7">
                            <div class="row g-2 text-center text-md-start">
                                <div class="col-4">
                                    <small class="text-muted d-block">إجمالي الفاتورة</small>
                                    <h4 class="fw-bold text-dark mb-0 font-monospace" id="posTotalAmountDisplay">0.00</h4>
                                    <small class="text-muted">د.ع</small>
                                </div>
                                <div class="col-4 border-start">
                                    <small class="text-success fw-semibold d-block">حصة المريض (نقداً)</small>
                                    <h4 class="fw-bold text-success mb-0 font-monospace" id="posPatientShareDisplay">0.00</h4>
                                    <small class="text-muted">د.ع</small>
                                </div>
                                <div class="col-4 border-start">
                                    <small class="text-primary fw-semibold d-block">حصة التأمين / الضمان</small>
                                    <h4 class="fw-bold text-primary mb-0 font-monospace" id="posInsuranceShareDisplay">0.00</h4>
                                    <small class="text-muted">د.ع</small>
                                </div>
                            </div>
                        </div>

                        <!-- أزرار الدفع والتعليق -->
                        <div class="col-12 col-md-5 d-flex gap-2 justify-content-md-end flex-wrap">
                            <button type="button" class="btn btn-outline-secondary px-3 py-2.5 rounded-3 fw-semibold shadow-xs" onclick="clearCart()" title="إلغاء الفاتورة بالكامل">
                                <i class="fas fa-trash-alt"></i>
                            </button>

                            <button type="button" class="btn btn-warning text-dark px-3 py-2.5 rounded-3 fw-bold shadow-xs d-flex align-items-center gap-1" onclick="submitPosSale(true)" id="btnHoldSale" title="تعليق الفاتورة (F4)">
                                <i class="fas fa-pause"></i>
                                <span>تعليق (F4)</span>
                            </button>

                            <button type="button" class="btn btn-success px-4 py-2.5 rounded-3 fw-bold shadow-sm d-flex align-items-center gap-2 flex-grow-1 justify-content-center" onclick="submitPosSale(false)" id="btnCheckoutSale">
                                <i class="fas fa-check-circle fa-lg"></i>
                                <span>تأكيد وصرف (F9)</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ======================================================== -->
        <!-- الجانب الأيسر: طابور الوصفات الطبية الإلكترونية الواردة كصفوف سريعة -->
        <!-- ======================================================== -->
        <div class="col-12 col-xl-5 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white d-flex flex-column">

                <!-- رأس الطابور -->
                <div class="card-header bg-white border-0 p-3 pb-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-primary-subtle text-primary p-2 rounded-circle fs-6 d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                <i class="fas fa-list-ol"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">طابور الوصفات الواردة</h6>
                                <small class="text-muted">جدول صفوف سريع للمعاينة والتحميل الفوري</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-pill font-monospace fs-6 px-2.5 py-1" id="badgeTotalPendingRx">0</span>
                            <button type="button" class="btn btn-sm btn-light border rounded-circle p-1.5" onclick="refreshPrescriptionsQueue(true)" title="تحديث القائمة">
                                <i class="fas fa-sync-alt text-muted" id="rxRefreshIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- شريط البحث اللحظي السريع في طابور الوصفات -->
                    <div class="input-group input-group-sm mb-2 shadow-xs">
                        <span class="input-group-text bg-light border-end-0 text-muted ps-2.5">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" 
                               id="rxStreamSearchInput" 
                               class="form-control border-start-0 border-end-0 bg-light" 
                               placeholder="ابحث برقم الوصفة، المريض، الطبيب، أو اسم الدواء..." 
                               oninput="handleRxStreamSearch(this.value)"
                               autocomplete="off">
                        <button type="button" 
                                class="btn btn-light border border-start-0 text-muted" 
                                id="btnRxClearSearch" 
                                onclick="clearRxStreamSearch()" 
                                style="display: none;" 
                                title="مسح البحث">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- أزرار تصفية سريعة للطابور -->
                    <div class="d-flex gap-1 overflow-auto pb-1 align-items-center justify-content-between">
                        <div class="d-flex gap-1 overflow-auto">
                            <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2.5 py-1 filter-pill active" data-rxfilter="all" onclick="filterRxStream('all')">
                                الكل (<span id="rxFilterCountAll">0</span>)
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-1 filter-pill" data-rxfilter="new" onclick="filterRxStream('new')">
                                جديدة (<span id="rxFilterCountNew">0</span>)
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-success rounded-pill px-2.5 py-1 filter-pill" data-rxfilter="ready" onclick="filterRxStream('ready')">
                                جاهزة (<span id="rxFilterCountReady">0</span>)
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-warning text-dark rounded-pill px-2.5 py-1 filter-pill" data-rxfilter="pending" onclick="filterRxStream('pending')">
                                بدائل (<span id="rxFilterCountPending">0</span>)
                            </button>
                        </div>
                        <span class="small text-muted font-monospace pe-1" id="rxSearchMatchCounter" style="display: none; font-size: 0.75rem;"></span>
                    </div>
                </div>

                <!-- جدول صفوف الوصفات الواردة -->
                <div class="card-body p-0 flex-grow-1 overflow-auto" style="min-height: 280px; max-height: calc(100vh - 460px);">
                    <div class="table-responsive h-100">
                        <table class="table table-hover table-sm align-middle mb-0 rx-stream-table">
                            <thead class="table-light sticky-top small text-muted border-bottom">
                                <tr>
                                    <th style="width: 105px;">الوصفة</th>
                                    <th>المريض / الطبيب</th>
                                    <th style="width: 70px;" class="text-center">الأدوية</th>
                                    <th style="width: 80px;" class="text-center">الحالة</th>
                                    <th style="width: 115px;" class="text-center">إجراء</th>
                                </tr>
                            </thead>
                            <tbody id="rxStreamTableBody">
                                <!-- سيتم تحميل الصفوف عبر JavaScript -->
                            </tbody>
                        </table>
                        <div id="rxStreamEmptyState" class="text-center py-5 text-muted" style="display: none;">
                            <i class="fas fa-clipboard-check fa-3x text-secondary opacity-25 mb-2"></i>
                            <h6 class="fw-bold text-dark mb-1">لا توجد وصفات طبية معلقة</h6>
                            <small class="text-secondary">أي وصفة يكتبها الطبيب ستصلك هنا فوراً.</small>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- ======================================================== -->
<!-- Modal 1: نافذة فحص وتجهيز البدائل الطبية التفاعلية -->
<!-- ======================================================== -->
<div class="modal fade" id="dispensingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header bg-white py-3 px-4 border-bottom">
                <div class="d-flex flex-wrap justify-content-between align-items-center w-100 gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white p-3 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="fas fa-prescription"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-primary px-3 py-1 fs-6 font-monospace" id="modalDispRxNumber">RX-0000</span>
                                <span class="badge bg-success-subtle text-success border border-success px-2 py-1" id="modalDispRxStatus">جاهزة للصرف</span>
                                <span class="small text-muted font-monospace" id="modalDispRxTime">-</span>
                            </div>
                            <h4 class="fw-bold text-dark mb-0" id="modalDispPatientName">اسم المريض</h4>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end">
                            <div class="text-dark fw-bold mb-0">
                                <i class="fas fa-user-md text-primary me-1"></i> <span id="modalDispDoctorName">د. الطبيب</span>
                            </div>
                            <small class="text-muted" id="modalDispClinicSpecialty">عيادة الاستشارية</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
            </div>

            <div class="modal-body p-4 bg-light">
                <div id="modalRxSubstitutionFeedbackBanner" class="mb-3" style="display: none;"></div>

                <div class="alert alert-white bg-white border rounded-3 p-3 mb-3 d-flex align-items-center gap-2 small shadow-xs">
                    <i class="fas fa-stethoscope text-info fs-5 me-2"></i>
                    <div>
                        <strong class="text-dark">التشخيص / توصيات الطبيب:</strong>
                        <span class="text-muted ms-1" id="modalDispDiagnosisText">لا يوجد تشخيص مسجل</span>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fas fa-tablets text-primary me-2"></i>الأدوية والجرعات المطلوب تجهيزها:
                        </h6>
                        <span class="badge bg-secondary rounded-pill font-monospace" id="modalDispItemsCount">0 أدوية</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0" id="modalDispensingItemsTable">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th style="width: 5%;" class="text-center">#</th>
                                    <th style="width: 35%;">اسم الدواء والشكل</th>
                                    <th style="width: 15%;" class="text-center">الكمية المطلوبة</th>
                                    <th style="width: 25%;">الجرعة وتعليمات الاستخدام</th>
                                    <th style="width: 20%;" class="text-center">حالة التوفر والبدائل</th>
                                </tr>
                            </thead>
                            <tbody id="modalDispensingItemsBody">
                                <!-- Dynamic Items -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-white p-3 border-top justify-content-between">
                <div class="text-muted small">
                    <i class="fas fa-shield-alt text-success me-1"></i> يتم الصرف بنظام FEFO وتحديث سجلات المريض تلقائياً.
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="#" target="_blank" class="btn btn-outline-dark px-3 py-2 fw-bold rounded-3" id="modalBtnPrintPrescription">
                        <i class="fas fa-print me-1"></i> طباعة الوصفة (RX)
                    </a>

                    <button type="button" class="btn btn-primary px-3 py-2 fw-bold shadow-sm rounded-3" onclick="loadCurrentModalRxIntoCart()">
                        <i class="fas fa-arrow-right me-1"></i> نقل إلى سلة الكاشير
                    </button>

                    <button type="button" class="btn btn-success btn-lg px-4 py-2 fw-bold shadow-sm rounded-3 d-flex align-items-center gap-2" id="modalBtnExecuteDispense" onclick="executeDispenseFromModal()">
                        <i class="fas fa-check-circle fa-lg"></i>
                        <span>تأكيد صرف وتجهيز الدواء</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- Modal 2: فحص سريع لرصيد الأدوية والبدائل المتاحة -->
<!-- ======================================================== -->
<div class="modal fade" id="manualSearchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-search me-2 text-info"></i>فحص سريع لرصيد الأدوية والبدائل المتاحة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="input-group input-group-lg mb-3">
                    <span class="input-group-text bg-primary text-white"><i class="fas fa-barcode"></i></span>
                    <input type="text" id="quickSearchMedInput" class="form-control" placeholder="امسح الباركود أو اكتب اسم الدواء / المادة الفعالة..." autocomplete="off">
                </div>

                <div id="quickSearchResultsList" class="list-group shadow-xs" style="max-height: 380px; overflow-y: auto;">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-search fa-2x mb-2 opacity-50"></i>
                        <p class="small mb-0">اكتب اسم أي دواء أو امسح الباركود للتحقق من الرصيد والبدائل</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- Modal 3: استبدال الدواء ببديل مكافئ متوفر -->
<!-- ======================================================== -->
<div class="modal fade" id="alternativeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exchange-alt me-2"></i>
                    اختيار واقتراح بديل للدواء: <span id="modalTargetMedName" class="text-primary font-monospace">-</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="modalAltSearchInput" class="form-control" placeholder="ابحث في دليل الأدوية بالاسم التجاري أو العلمي أو التركيز...">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted mb-1">
                        <i class="fas fa-comment-medical text-primary me-1"></i>
                        ملاحظة / سبب الاستبدال الموجه للطبيب (اختياري):
                    </label>
                    <input type="text" id="modalAltReasonInput" class="form-control form-control-sm" placeholder="مثال: غير متوفر الصنف الأصلي - متوفر نفس المادة الفعالة بشركة بديلة">
                </div>

                <h6 class="fw-bold text-dark mb-2 small d-flex align-items-center justify-content-between">
                    <span><i class="fas fa-list-check text-success me-1"></i> البدائل المتاحة للاختيار:</span>
                    <span class="badge bg-secondary-subtle text-secondary" id="modalAltCountBadge">0 بديل</span>
                </h6>

                <div class="list-group shadow-xs" id="alternativesModalList" style="max-height: 320px; overflow-y: auto;">
                    <!-- Dynamic Alternatives List -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- تنبيه صوتي خفيف عند الصرف -->
<audio id="dispenseAudio" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>
@endsection

@push('scripts')
<script>
    // ==========================================
    // الحالة العامة ونظام السلة اللحظي (POS State)
    // ==========================================
    let posCart = []; // بنود السلة الحالية
    let currentLoadedPrescription = null; // الوصفة المحملة حالياً إن وجدت
    let currentSelectedRxId = null;
    let currentPrescriptionData = null;
    let targetItemForAlternative = null;
    let allPrescriptionsCache = [];
    let currentRxStreamFilter = 'all';
    let currentRxSearchQuery = '';
    let autoRefreshTimer = null;

    // ذاكرة ماسح الباركود
    let barcodeScannerBuffer = '';
    let barcodeScannerLastTime = 0;

    document.addEventListener('DOMContentLoaded', function() {
        // نقل النوافذ المنبثقة مباشرة إلى body لمنع حجبها خلف الـ backdrop
        ['dispensingModal', 'manualSearchModal', 'alternativeModal'].forEach(function(modalId) {
            const modalEl = document.getElementById(modalId);
            if (modalEl && modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
        });

        // تشغيل تدفق طابور الوصفات
        refreshPrescriptionsQueue(true);
        startAutoRefresh();

        // ربط حقل البحث اللحظي السريع عن الأدوية
        const searchInput = document.getElementById('posOmniSearchInput');
        searchInput.addEventListener('input', debounce(function() {
            handleOmniSearch(this.value.trim());
        }, 250));

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                selectFirstSearchResult();
            }
        });

        // إغلاق قائمة البحث عند النقر خارجها
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('posSearchResultsDropdown');
            if (dropdown && !dropdown.contains(e.target) && e.target !== searchInput) {
                dropdown.style.display = 'none';
            }
        });

        // التحديث التلقائي
        document.getElementById('autoRefreshSwitch').addEventListener('change', function(e) {
            if (e.target.checked) {
                startAutoRefresh();
            } else {
                clearInterval(autoRefreshTimer);
            }
        });

        // اختصارات لوحة المفاتيح
        document.addEventListener('keydown', function(e) {
            // F2: التركيز على حقل البحث
            if (e.key === 'F2') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
            // F9: دفع وصرف
            if (e.key === 'F9') {
                e.preventDefault();
                submitPosSale(false);
            }
            // F4: تعليق الفاتورة
            if (e.key === 'F4') {
                e.preventDefault();
                submitPosSale(true);
            }
            // مفتاح Space لتأكيد الصرف من داخل المودال إن كان مفتوحاً
            if (e.code === 'Space' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                const modal = document.getElementById('dispensingModal');
                if (modal && modal.classList.contains('show')) {
                    e.preventDefault();
                    executeDispenseFromModal();
                }
            }
        });

        // مستمع الباركود التلقائي (Hardware Gun)
        document.addEventListener('keydown', function(e) {
            const now = Date.now();
            if (now - barcodeScannerLastTime > 100) {
                barcodeScannerBuffer = '';
            }
            barcodeScannerLastTime = now;

            if (e.key === 'Enter') {
                if (barcodeScannerBuffer.length >= 3) {
                    const scannedCode = barcodeScannerBuffer.trim();
                    // إذا كان يبدأ بـ RX أو يحتوي على شرطة، قد يكون رقم وصفة
                    const matchedRx = allPrescriptionsCache.find(r => 
                        (r.prescription_number || '').toLowerCase() === scannedCode.toLowerCase()
                    );

                    if (matchedRx) {
                        e.preventDefault();
                        loadPrescriptionIntoCart(matchedRx.id);
                    } else {
                        // مسح باركود دواء عادي
                        quickScanMedicineBarcode(scannedCode);
                    }
                }
                barcodeScannerBuffer = '';
            } else if (e.key.length === 1) {
                barcodeScannerBuffer += e.key;
            }
        });

        // حقل فحص رصيد الأدوية بالمودال
        const quickSearchMedInput = document.getElementById('quickSearchMedInput');
        if (quickSearchMedInput) {
            quickSearchMedInput.addEventListener('input', debounce(function() {
                const q = this.value.trim();
                if (q.length < 1) {
                    document.getElementById('quickSearchResultsList').innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-search fa-2x mb-2 opacity-50"></i>
                            <p class="small mb-0">اكتب اسم أي دواء أو امسح الباركود للتحقق من الرصيد والبدائل</p>
                        </div>`;
                    return;
                }

                fetch(`{{ route('pharmacy.pos.search') }}?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(data => {
                        renderQuickSearchResults(data.medicines || []);
                    });
            }, 300));
        }

        // مودال البدائل
        const modalAltSearchInput = document.getElementById('modalAltSearchInput');
        if (modalAltSearchInput) {
            modalAltSearchInput.addEventListener('input', debounce(function() {
                const q = this.value.trim();
                if (q.length < 1) {
                    renderAlternativesList([]);
                    return;
                }

                fetch(`{{ route('pharmacy.pos.search') }}?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(data => {
                        renderAlternativesList(data.medicines || []);
                    });
            }, 300));
        }

        renderCart();
    });

    // ==========================================
    // إدارة سلة نقطة البيع (POS Cart Management)
    // ==========================================

    function addToCart(medicine, unitType = 'main_unit', quantity = 1, dosageInstructions = '') {
        const existingIdx = posCart.findIndex(item => item.medicine_id === medicine.id && item.unit_type === unitType);

        if (existingIdx >= 0) {
            posCart[existingIdx].quantity += quantity;
        } else {
            posCart.push({
                item_type: 'medicine',
                medicine_id: medicine.id,
                name: medicine.name,
                dosage_form: medicine.dosage_form || '',
                strength: medicine.strength || '',
                unit_type: unitType,
                main_unit: medicine.main_unit || 'علبة',
                sub_unit: medicine.sub_unit || 'شريط',
                sub_units_count: medicine.sub_units_count || 1,
                quantity: quantity,
                sale_price: parseFloat(medicine.sale_price) || 0,
                sub_unit_sale_price: parseFloat(medicine.sub_unit_sale_price) || (parseFloat(medicine.sale_price) / (medicine.sub_units_count || 1)),
                hi_price: parseFloat(medicine.hi_price) || parseFloat(medicine.sale_price) || 0,
                moi_price: parseFloat(medicine.moi_price) || parseFloat(medicine.sale_price) || 0,
                total_stock: medicine.total_stock || 0,
                dosage_instructions: dosageInstructions
            });
        }

        renderCart();
        const searchInput = document.getElementById('posOmniSearchInput');
        searchInput.value = '';
        document.getElementById('posSearchResultsDropdown').style.display = 'none';
        searchInput.focus();
    }

    function updateCartItemQty(index, newQty) {
        newQty = parseFloat(newQty);
        if (isNaN(newQty) || newQty <= 0) {
            removeFromCart(index);
            return;
        }
        posCart[index].quantity = newQty;
        renderCart();
    }

    function toggleCartItemUnit(index) {
        const item = posCart[index];
        item.unit_type = (item.unit_type === 'main_unit') ? 'sub_unit' : 'main_unit';
        renderCart();
    }

    function removeFromCart(index) {
        posCart.splice(index, 1);
        renderCart();
    }

    function clearCart() {
        posCart = [];
        currentLoadedPrescription = null;
        document.getElementById('loadedPrescriptionBanner').style.display = 'none';
        document.getElementById('posPatientNameInput').value = 'مريض مباشر OTC';
        document.getElementById('posInsuranceTypeSelect').value = 'none';
        handleInsuranceTypeChange();
        renderCart();
    }

    function renderCart() {
        const tbody = document.getElementById('posCartTableBody');
        const emptyState = document.getElementById('posCartEmptyState');

        if (posCart.length === 0) {
            tbody.innerHTML = '';
            emptyState.style.display = 'block';
            calculateCartTotals();
            return;
        }

        emptyState.style.display = 'none';
        let html = '';

        const insuranceType = document.getElementById('posInsuranceTypeSelect').value;
        const categoryId = document.getElementById('posInsuranceCategorySelect').value;
        const copayPercent = currentLoadedPrescription ? (parseFloat(currentLoadedPrescription.copay_percentage) || 0) : 0;

        posCart.forEach((item, index) => {
            const isSub = (item.unit_type === 'sub_unit');
            const unitLabel = isSub ? item.sub_unit : item.main_unit;
            const subUnitsCount = item.sub_units_count || 1;
            const isCovered = (item.is_insurance_covered !== false && item.is_insurance_covered !== 0);

            // احتساب سعر الوحدة الفعلي بناءً على الوضع المالي المعتمد من الاستعلامات
            let effectiveUnitPrice = 0;
            let priceModeBadge = '';

            if (insuranceType === 'health_insurance') {
                if (isCovered && item.hi_price > 0) {
                    effectiveUnitPrice = isSub ? Math.round(item.hi_price / subUnitsCount) : item.hi_price;
                    priceModeBadge = `<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle small ms-1"><i class="fas fa-shield-alt"></i> ضمان معتمد</span>`;
                } else {
                    effectiveUnitPrice = isSub ? item.sub_unit_sale_price : item.sale_price;
                    priceModeBadge = `<span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle small ms-1">كاش كامل (غير مشمول)</span>`;
                }
            } else if (insuranceType === 'interior_ministry') {
                effectiveUnitPrice = isSub ? Math.round(item.moi_price / subUnitsCount) : item.moi_price;
                priceModeBadge = `<span class="badge bg-info bg-opacity-10 text-info border border-info-subtle small ms-1"><i class="fas fa-shield-alt"></i> داخلية</span>`;
            } else {
                // كاش نقدي كامل (أسعار الصيدلية المتعاقدة)
                effectiveUnitPrice = isSub ? item.sub_unit_sale_price : item.sale_price;
                priceModeBadge = `<span class="badge bg-light text-dark border small ms-1">كاش</span>`;
            }

            const lineTotal = effectiveUnitPrice * item.quantity;
            let breakdownHtml = '';

            if (insuranceType === 'health_insurance') {
                if (isCovered && item.hi_price > 0) {
                    const linePatientShare = Math.round((lineTotal * copayPercent) / 100);
                    const lineInsShare = lineTotal - linePatientShare;
                    breakdownHtml = `
                        <div class="small text-muted mt-0.5" style="font-size: 0.74rem;">
                            <span class="text-success fw-bold">حصة المريض (${copayPercent}%): ${formatMoney(linePatientShare)} د.ع</span> | 
                            <span class="text-primary">الضمان: ${formatMoney(lineInsShare)} د.ع</span>
                        </div>
                    `;
                } else {
                    breakdownHtml = `
                        <div class="small text-warning-emphasis mt-0.5" style="font-size: 0.74rem;">
                            غير مشمول بالضمان - يُدفع بالكامل نقداً
                        </div>
                    `;
                }
            }

            html += `
                <tr>
                    <td class="text-center text-muted fw-bold">${index + 1}</td>
                    <td>
                        <div class="fw-bold text-dark">${item.name}</div>
                        <div class="small text-muted">
                            ${item.dosage_form ? `<span class="badge bg-light text-secondary border me-1">${item.dosage_form}</span>` : ''}
                            ${item.strength ? `<span class="badge bg-light text-secondary border me-1">${item.strength}</span>` : ''}
                            ${subUnitsCount > 1 ? `<span class="badge bg-light text-dark border me-1">[1 علبة = ${subUnitsCount} ${item.sub_unit}]</span>` : ''}
                            ${item.dosage_instructions ? `<span class="text-primary small ms-1"><i class="fas fa-info-circle me-1"></i>${item.dosage_instructions}</span>` : ''}
                        </div>
                    </td>
                    <td class="text-center">
                        <button type="button" 
                                class="btn btn-sm ${isSub ? 'btn-info text-dark' : 'btn-primary text-white'} unit-toggle-btn shadow-xs" 
                                onclick="toggleCartItemUnit(${index})" 
                                title="اضغط للتبديل بين علبة وشريط">
                            <i class="fas fa-exchange-alt me-1"></i> ${unitLabel}
                        </button>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-light border qty-btn" onclick="updateCartItemQty(${index}, ${item.quantity - 1})">-</button>
                            <input type="number" 
                                   class="form-control form-control-sm qty-input" 
                                   value="${item.quantity}" 
                                   min="0.1" 
                                   step="1" 
                                   onchange="updateCartItemQty(${index}, this.value)">
                            <button type="button" class="btn btn-sm btn-light border qty-btn" onclick="updateCartItemQty(${index}, ${item.quantity + 1})">+</button>
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="fw-semibold text-dark font-monospace">${formatMoney(effectiveUnitPrice)} د.ع</div>
                        <div>${priceModeBadge}</div>
                    </td>
                    <td class="text-end">
                        <div class="fw-bold text-success font-monospace">${formatMoney(lineTotal)} د.ع</div>
                        ${breakdownHtml}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm text-danger p-0" onclick="removeFromCart(${index})" title="حذف الصنف">
                            <i class="fas fa-times-circle fa-lg"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        calculateCartTotals();
    }

    function calculateCartTotals() {
        const insuranceType = document.getElementById('posInsuranceTypeSelect').value;
        const copayPercent = currentLoadedPrescription ? (parseFloat(currentLoadedPrescription.copay_percentage) || 0) : 0;

        let total = 0;
        let patientShare = 0;
        let insuranceShare = 0;

        posCart.forEach(item => {
            const isSub = (item.unit_type === 'sub_unit');
            const subUnitsCount = item.sub_units_count || 1;
            const isCovered = (item.is_insurance_covered !== false && item.is_insurance_covered !== 0);
            let basePrice = isSub ? item.sub_unit_sale_price : item.sale_price;

            if (insuranceType === 'health_insurance') {
                if (isCovered && item.hi_price > 0) {
                    basePrice = isSub ? Math.round(item.hi_price / subUnitsCount) : item.hi_price;
                } else {
                    basePrice = isSub ? item.sub_unit_sale_price : item.sale_price;
                }
            } else if (insuranceType === 'interior_ministry') {
                basePrice = isSub ? Math.round(item.moi_price / subUnitsCount) : item.moi_price;
            }

            const lineTotal = basePrice * item.quantity;
            total += lineTotal;

            if (insuranceType === 'none') {
                patientShare += lineTotal;
            } else if (insuranceType === 'health_insurance') {
                if (isCovered && item.hi_price > 0) {
                    const linePatientShare = Math.round((lineTotal * copayPercent) / 100);
                    patientShare += linePatientShare;
                    insuranceShare += (lineTotal - linePatientShare);
                } else {
                    // غير مشمول بالضمان: يتحمله المريض كاملاً نقداً
                    patientShare += lineTotal;
                }
            } else if (insuranceType === 'interior_ministry') {
                insuranceShare += lineTotal;
            }
        });

        document.getElementById('posTotalAmountDisplay').innerText = formatMoney(total);
        document.getElementById('posPatientShareDisplay').innerText = formatMoney(patientShare);
        document.getElementById('posInsuranceShareDisplay').innerText = formatMoney(insuranceShare);
    }

    function handleInsuranceTypeChange() {
        const type = document.getElementById('posInsuranceTypeSelect').value;
        const badge = document.getElementById('posActiveBillTypeBadge');

        if (type === 'health_insurance') {
            const catName = currentLoadedPrescription ? (currentLoadedPrescription.category_name || 'فئة معتمدة') : 'فئة معتمدة';
            const copay = currentLoadedPrescription ? (parseFloat(currentLoadedPrescription.copay_percentage) || 0) : 0;
            badge.innerHTML = `<i class="fas fa-shield-alt text-success me-1"></i> 🔒 معتمد ومسجل بالاستعلامات: ضمان صحي (${catName} - استقطاع ${copay}%)`;
            badge.className = 'badge bg-success-subtle text-success border border-success px-3 py-1.5';
        } else if (type === 'interior_ministry') {
            badge.innerHTML = '<i class="fas fa-shield-alt text-info me-1"></i> 🔒 معتمد ومسجل بالاستعلامات: وزارة الداخلية (MOI)';
            badge.className = 'badge bg-info-subtle text-info border border-info px-3 py-1.5';
        } else {
            badge.innerHTML = '<i class="fas fa-shopping-bag text-primary me-1"></i> بيع مباشر OTC (تسعيرة الصيدلية كاش)';
            badge.className = 'badge bg-light text-dark border px-3 py-1.5';
        }

        renderCart();
    }

    // ==========================================
    // البحث اللحظي عن الأدوية (Omni Search)
    // ==========================================

    let lastSearchResults = [];

    function handleOmniSearch(query) {
        const dropdown = document.getElementById('posSearchResultsDropdown');
        if (!query || query.length < 1) {
            dropdown.style.display = 'none';
            return;
        }

        fetch(`{{ route('pharmacy.pos.search') }}?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                lastSearchResults = data.medicines || [];
                renderSearchResultsDropdown(lastSearchResults);
            })
            .catch(err => {
                console.error('Error searching medicines:', err);
            });
    }

    function renderSearchResultsDropdown(medicines) {
        const dropdown = document.getElementById('posSearchResultsDropdown');
        if (!medicines || medicines.length === 0) {
            dropdown.innerHTML = `
                <div class="p-3 text-center text-muted small">
                    <i class="fas fa-exclamation-circle me-1 text-warning"></i> لم يتم العثور على أدوية مطابقة
                </div>`;
            dropdown.style.display = 'block';
            return;
        }

        let html = '';
        medicines.forEach((med, idx) => {
            const hasStock = (med.total_stock > 0 || (med.total_open_sub_units && med.total_open_sub_units > 0));
            const subUnitsCount = med.sub_units_count || 1;
            const hiPrice = parseFloat(med.hi_price) || parseFloat(med.sale_price) || 0;
            const hiSubPrice = Math.round(hiPrice / subUnitsCount);
            const isCovered = (med.is_insurance_covered !== false && med.is_insurance_covered !== 0);

            html += `
                <div class="pos-search-item d-flex justify-content-between align-items-center" onclick="addToCartByIndex(${idx})">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-dark">${med.name}</span>
                            ${med.national_code ? `<span class="badge bg-dark bg-opacity-75 font-monospace" style="font-size: 0.72rem;">#${med.national_code}</span>` : ''}
                        </div>
                        <div class="small text-muted mt-0.5">
                            ${med.generic_name ? `<span class="me-2"><i class="fas fa-flask text-secondary me-1"></i>${med.generic_name}</span>` : ''}
                            ${med.dosage_form ? `<span class="badge bg-light text-secondary border me-1">${med.dosage_form}</span>` : ''}
                            ${med.strength ? `<span class="badge bg-light text-secondary border me-1">${med.strength}</span>` : ''}
                            ${subUnitsCount > 1 ? `<span class="badge bg-light text-dark border">[1 علبة = ${subUnitsCount} ${med.sub_unit || 'شريط'}]</span>` : ''}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="d-flex align-items-center justify-content-end gap-2">
                            <span class="badge bg-light text-dark border font-monospace">كاش: ${formatMoney(med.sale_price)} د.ع</span>
                            ${isCovered 
                                ? `<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle font-monospace"><i class="fas fa-shield-alt"></i> ضمان: ${formatMoney(hiPrice)} د.ع</span>`
                                : `<span class="badge bg-warning bg-opacity-10 text-warning-emphasis small">غير مشمول</span>`}
                        </div>
                        <div class="small mt-1 d-flex align-items-center justify-content-end gap-2">
                            <span class="text-muted" style="font-size: 0.75rem;">(الشريط: ${formatMoney(med.sub_unit_sale_price)} كاش | ${formatMoney(hiSubPrice)} ضمان)</span>
                            ${hasStock 
                                ? `<span class="badge bg-success-subtle text-success">متوفر: ${med.total_stock} ${med.main_unit || 'علبة'}${med.total_open_sub_units > 0 ? ` + ${med.total_open_sub_units} ${med.sub_unit || 'شريط'}` : ''}</span>`
                                : `<span class="badge bg-danger-subtle text-danger">نفد الرصيد</span>`}
                        </div>
                    </div>
                </div>
            `;
        });

        dropdown.innerHTML = html;
        dropdown.style.display = 'block';
    }

    function addToCartByIndex(index) {
        if (lastSearchResults[index]) {
            addToCart(lastSearchResults[index]);
        }
    }

    function selectFirstSearchResult() {
        if (lastSearchResults.length > 0) {
            addToCart(lastSearchResults[0]);
        }
    }

    function quickScanMedicineBarcode(barcode) {
        fetch(`{{ route('pharmacy.pos.search') }}?q=${encodeURIComponent(barcode)}`)
            .then(res => res.json())
            .then(data => {
                if (data.medicines && data.medicines.length > 0) {
                    addToCart(data.medicines[0]);
                }
            });
    }

    // ========================================================
    // طابور الوصفات الواردة اللحظية (Prescriptions Stream)
    // ========================================================

    function startAutoRefresh() {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = setInterval(() => {
            refreshPrescriptionsQueue(false);
        }, 8000);
    }

    function refreshPrescriptionsQueue(showSpinner = false) {
        const icon = document.getElementById('rxRefreshIcon');
        if (showSpinner && icon) icon.classList.add('fa-spin');

        fetch(`{{ route('pharmacy.pos.pending-prescriptions') }}`)
            .then(res => res.json())
            .then(data => {
                if (showSpinner && icon) icon.classList.remove('fa-spin');
                if (data.success) {
                    allPrescriptionsCache = data.prescriptions || [];
                    renderRxStream();
                }
            })
            .catch(err => {
                if (showSpinner && icon) icon.classList.remove('fa-spin');
                console.error('Error fetching prescriptions queue:', err);
            });
    }

    function filterRxStream(filter) {
        currentRxStreamFilter = filter;
        document.querySelectorAll('.filter-pill').forEach(btn => {
            if (btn.getAttribute('data-rxfilter') === filter) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        renderRxStream();
    }

    function handleRxStreamSearch(val) {
        currentRxSearchQuery = (val || '').trim().toLowerCase();
        const clearBtn = document.getElementById('btnRxClearSearch');
        if (clearBtn) {
            clearBtn.style.display = currentRxSearchQuery ? 'block' : 'none';
        }
        renderRxStream();
    }

    function clearRxStreamSearch() {
        const input = document.getElementById('rxStreamSearchInput');
        if (input) input.value = '';
        currentRxSearchQuery = '';
        const clearBtn = document.getElementById('btnRxClearSearch');
        if (clearBtn) clearBtn.style.display = 'none';
        renderRxStream();
    }

    function renderRxStream() {
        const tbody = document.getElementById('rxStreamTableBody');
        const emptyState = document.getElementById('rxStreamEmptyState');
        if (!tbody) return;

        const all = allPrescriptionsCache;
        const newCount = all.filter(r => r.stage === 'new').length;
        const readyCount = all.filter(r => r.stage === 'approved_ready').length;
        const pendingCount = all.filter(r => r.stage === 'awaiting_doctor').length;

        document.getElementById('badgeTotalPendingRx').innerText = all.length;
        document.getElementById('rxFilterCountAll').innerText = all.length;
        document.getElementById('rxFilterCountNew').innerText = newCount;
        document.getElementById('rxFilterCountReady').innerText = readyCount;
        document.getElementById('rxFilterCountPending').innerText = pendingCount;

        let filtered = all;
        if (currentRxStreamFilter === 'new') {
            filtered = all.filter(r => r.stage === 'new');
        } else if (currentRxStreamFilter === 'ready') {
            filtered = all.filter(r => r.stage === 'approved_ready');
        } else if (currentRxStreamFilter === 'pending') {
            filtered = all.filter(r => r.stage === 'awaiting_doctor');
        }

        // تطبيق البحث النصي اللحظي
        if (currentRxSearchQuery) {
            filtered = filtered.filter(rx => {
                const rxNum = (rx.prescription_number || '').toLowerCase();
                const patName = (rx.patient_name || '').toLowerCase();
                const docName = (rx.doctor_name || '').toLowerCase();
                const medNames = (rx.items_summary || []).map(i => (i.name || '').toLowerCase()).join(' ');
                
                return rxNum.includes(currentRxSearchQuery) ||
                       patName.includes(currentRxSearchQuery) ||
                       docName.includes(currentRxSearchQuery) ||
                       medNames.includes(currentRxSearchQuery);
            });
        }

        const matchCounter = document.getElementById('rxSearchMatchCounter');
        if (matchCounter) {
            if (currentRxSearchQuery) {
                matchCounter.innerText = `${filtered.length} نتيجة`;
                matchCounter.style.display = 'inline-block';
            } else {
                matchCounter.style.display = 'none';
            }
        }

        if (filtered.length === 0) {
            tbody.innerHTML = '';
            if (emptyState) {
                emptyState.style.display = 'block';
                const emptyTitle = emptyState.querySelector('h6');
                const emptyDesc = emptyState.querySelector('small');
                if (currentRxSearchQuery) {
                    if (emptyTitle) emptyTitle.innerText = 'لا توجد نتائج مطابقة للبحث';
                    if (emptyDesc) emptyDesc.innerText = `لم يتم العثور على وصفات تطابق: "${currentRxSearchQuery}"`;
                } else {
                    if (emptyTitle) emptyTitle.innerText = 'لا توجد وصفات طبية معلقة';
                    if (emptyDesc) emptyDesc.innerText = 'أي وصفة يكتبها الطبيب ستصلك هنا فوراً.';
                }
            }
            return;
        }

        if (emptyState) emptyState.style.display = 'none';

        let html = '';
        filtered.forEach(rx => {
            const isEmerg = rx.is_emergency;
            let statusBadge = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">جديدة</span>';

            if (isEmerg) {
                statusBadge = '<span class="badge bg-danger">🚨 طوارئ</span>';
            } else if (rx.stage === 'approved_ready') {
                statusBadge = '<span class="badge bg-success-subtle text-success border border-success">معتمدة جاهزة</span>';
            } else if (rx.stage === 'awaiting_doctor') {
                statusBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning">بانتظار الطبيب</span>';
            }

            const itemsPreview = (rx.items_summary || []).map(i => i.name).join('، ');
            const itemsCount = (rx.items_summary || []).length;

            html += `
                <tr class="${isEmerg ? 'table-danger' : (rx.stage === 'approved_ready' ? 'table-success-subtle' : '')}" style="cursor: pointer;" onclick="loadPrescriptionIntoCart(${rx.id})" title="انقر لتحميل الوصفة إلى سلة الكاشير">
                    <td>
                        <div class="fw-bold ${isEmerg ? 'text-danger' : 'text-primary'} font-monospace small">
                            ${rx.prescription_number}
                        </div>
                        <small class="text-muted font-monospace" style="font-size: 0.72rem;">${rx.time_ago}</small>
                    </td>
                    <td>
                        <div class="fw-bold text-dark text-truncate" style="max-width: 150px;">${rx.patient_name}</div>
                        <small class="text-muted d-block text-truncate" style="max-width: 150px; font-size: 0.73rem;">
                            <i class="fas fa-user-md text-primary me-1"></i>${rx.doctor_name}
                        </small>
                        <div class="mt-0.5">
                            <span class="badge ${rx.insurance_type === 'health_insurance' ? 'bg-success-subtle text-success border border-success' : (rx.insurance_type === 'interior_ministry' ? 'bg-info-subtle text-info border border-info' : 'bg-light text-dark border')}" style="font-size: 0.70rem;">
                                ${rx.insurance_badge || 'كاش'}
                            </span>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border px-2 py-1 font-monospace" title="${itemsPreview}">
                            ${itemsCount} أصناف
                        </span>
                    </td>
                    <td class="text-center">
                        ${statusBadge}
                    </td>
                    <td class="text-center" onclick="event.stopPropagation()">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-primary btn-sm fw-bold px-2 py-1 shadow-xs" onclick="loadPrescriptionIntoCart(${rx.id})" title="تحميل أدوية الوصفة للسلة">
                                <i class="fas fa-arrow-right"></i> تحميل
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1" onclick="openDispensingModal(${rx.id})" title="فحص الأدوية واقتراح البدائل">
                                <i class="fas fa-sliders-h"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    // تحميل أدوية الوصفة مباشرة في سلة الكاشير
    function loadPrescriptionIntoCart(rxId) {
        fetch(`{{ url('pharmacy/pos/prescriptions') }}/${rxId}`)
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.prescription) {
                    alert('تعذر تحميل بيانات الوصفة');
                    return;
                }

                const rx = data.prescription;
                currentLoadedPrescription = rx;

                // تفريغ السلة القديمة وتحميل بنود الوصفة
                posCart = [];
                (rx.items || []).forEach(item => {
                    posCart.push({
                        item_type: 'medicine',
                        medicine_id: item.medicine_id,
                        name: item.name,
                        dosage_form: item.dosage_form || '',
                        strength: item.strength || '',
                        unit_type: item.unit_type || 'main_unit',
                        main_unit: item.main_unit || 'علبة',
                        sub_unit: item.sub_unit || 'شريط',
                        sub_units_count: item.sub_units_count || 1,
                        quantity: parseFloat(item.quantity) || 1,
                        sale_price: parseFloat(item.sale_price) || 0,
                        sub_unit_sale_price: parseFloat(item.sub_unit_sale_price) || 0,
                        hi_price: parseFloat(item.hi_price) || parseFloat(item.sale_price) || 0,
                        moi_price: parseFloat(item.moi_price) || parseFloat(item.sale_price) || 0,
                        is_insurance_covered: (item.is_insurance_covered !== false && item.is_insurance_covered !== 0),
                        total_stock: item.total_stock || 0,
                        dosage_instructions: trim((item.dosage_frequency || '') + ' ' + (item.instructions || ''))
                    });
                });

                // تحديث بيانات المريض والشريط العلوي والوضع المالي المثبت آلياً من الاستعلامات
                document.getElementById('posPatientNameInput').value = rx.patient_name || 'مريض';
                document.getElementById('posPatientNameInput').readOnly = true;

                const insType = rx.insurance_type || 'none';
                const catId = rx.health_insurance_category_id || '';
                const copay = parseFloat(rx.copay_percentage) || 0;

                document.getElementById('posInsuranceTypeSelect').value = insType;
                document.getElementById('posInsuranceCategorySelect').value = catId;

                const badge = document.getElementById('posActiveBillTypeBadge');
                if (insType === 'health_insurance') {
                    badge.innerHTML = `<i class="fas fa-shield-alt text-success me-1"></i> 🔒 معتمد ومسجل بالاستعلامات: ضمان صحي (${rx.category_name || 'فئة معتمدة'} - استقطاع ${copay}%)`;
                    badge.className = 'badge bg-success-subtle text-success border border-success px-3 py-1.5';
                } else if (insType === 'interior_ministry') {
                    badge.innerHTML = '<i class="fas fa-shield-alt text-info me-1"></i> 🔒 معتمد ومسجل بالاستعلامات: وزارة الداخلية (MOI)';
                    badge.className = 'badge bg-info-subtle text-info border border-info px-3 py-1.5';
                } else {
                    badge.innerHTML = '<i class="fas fa-receipt text-primary me-1"></i> 🔒 مسجل بالاستعلامات: نقدي كاش (100% نقداً)';
                    badge.className = 'badge bg-light text-dark border px-3 py-1.5';
                }

                const banner = document.getElementById('loadedPrescriptionBanner');
                document.getElementById('bannerRxNumber').innerText = rx.prescription_number;
                document.getElementById('bannerRxDoctor').innerText = rx.doctor_name || 'الاستشاري';
                document.getElementById('bannerRxSource').innerText = rx.is_emergency ? '🚨 طوارئ STAT' : 'وصفة عيادة استشارية';
                banner.style.display = 'flex';

                renderCart();
            })
            .catch(err => {
                console.error('Error loading prescription to cart:', err);
                alert('حدث خطأ أثناء تحميل الوصفة للسلة.');
            });
    }

    function unloadPrescriptionFromCart() {
        currentLoadedPrescription = null;
        document.getElementById('loadedPrescriptionBanner').style.display = 'none';
        document.getElementById('posPatientNameInput').value = 'مريض مباشر OTC';
        document.getElementById('posPatientNameInput').readOnly = false;
        document.getElementById('posInsuranceTypeSelect').value = 'none';
        document.getElementById('posInsuranceCategorySelect').value = '';
        const badge = document.getElementById('posActiveBillTypeBadge');
        badge.innerHTML = '<i class="fas fa-shopping-bag text-primary me-1"></i> بيع مباشر OTC (تسعيرة الصيدلية كاش)';
        badge.className = 'badge bg-light text-dark border px-3 py-1.5';
        renderCart();
    }

    function loadCurrentModalRxIntoCart() {
        if (currentSelectedRxId) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('dispensingModal')).hide();
            loadPrescriptionIntoCart(currentSelectedRxId);
        }
    }

    // ==========================================
    // حفظ وصرف الفاتورة (Checkout / Hold)
    // ==========================================

    function submitPosSale(isHeld = false) {
        if (posCart.length === 0) {
            alert('⚠️ سلة الفاتورة فارغة! يرجى إضافة أدوية أو اختيار وصفة أولاً.');
            return;
        }

        const patientName = document.getElementById('posPatientNameInput').value.trim() || 'مريض مباشر OTC';
        const insuranceType = document.getElementById('posInsuranceTypeSelect').value;
        const categoryId = document.getElementById('posInsuranceCategorySelect').value || null;

        const payload = {
            sale_type: currentLoadedPrescription ? (currentLoadedPrescription.is_emergency ? 'emergency' : 'prescription') : 'direct_otc',
            prescription_id: currentLoadedPrescription ? currentLoadedPrescription.id : null,
            patient_name: patientName,
            patient_id: currentLoadedPrescription ? currentLoadedPrescription.patient_id : null,
            insurance_type: insuranceType,
            health_insurance_category_id: categoryId,
            payment_route: 'pharmacy_cashier',
            is_held: isHeld ? 1 : 0,
            notes: currentLoadedPrescription ? `صرف عبر نقطة البيع للوصفة ${currentLoadedPrescription.prescription_number}` : 'بيع مباشر OTC',
            items: posCart.map(item => ({
                item_type: 'medicine',
                medicine_id: item.medicine_id,
                unit_type: item.unit_type,
                quantity: item.quantity,
                dosage_instructions: item.dosage_instructions || null
            }))
        };

        const btn = isHeld ? document.getElementById('btnHoldSale') : document.getElementById('btnCheckoutSale');
        btn.disabled = true;

        fetch(`{{ route('pharmacy.pos.store') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                try {
                    document.getElementById('dispenseAudio').play();
                } catch(e) {}

                if (!isHeld && data.print_url) {
                    window.open(data.print_url, '_blank', 'width=800,height=600');
                }

                alert(`✅ ${data.message}`);
                clearCart();
                refreshPrescriptionsQueue(false);
            } else {
                alert(`❌ ${data.message || 'حدث خطأ أثناء حفظ الفاتورة'}`);
            }
        })
        .catch(err => {
            btn.disabled = false;
            console.error('Error submitting POS sale:', err);
            alert('حدث خطأ في الاتصال أثناء حفظ الفاتورة.');
        });
    }

    // ==========================================
    // دوال المودالات والبدائل السابقة
    // ==========================================

    function openDispensingModal(rxId) {
        currentSelectedRxId = rxId;

        const modalEl = document.getElementById('dispensingModal');
        if (modalEl && modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }

        fetch(`{{ url('pharmacy/pos/prescriptions') }}/${rxId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.prescription) {
                    renderModalPrescription(data.prescription);
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                } else {
                    alert('لم يتم العثور على بيانات الوصفة المطلوبة.');
                }
            })
            .catch(err => {
                console.error('Error loading prescription:', err);
                alert('حدث خطأ أثناء تحميل تفاصيل الوصفة.');
            });
    }

    function renderModalPrescription(rx) {
        currentPrescriptionData = rx;

        const elRxNum = document.getElementById('modalDispRxNumber');
        if (elRxNum) {
            elRxNum.innerHTML = `${rx.prescription_number || ''}${rx.is_emergency ? ' <span class="badge bg-danger ms-1">🚨 طوارئ STAT</span>' : ''}`;
        }
        const elPatient = document.getElementById('modalDispPatientName');
        if (elPatient) elPatient.innerText = rx.patient_name || 'مريض';

        const elDoctor = document.getElementById('modalDispDoctorName');
        if (elDoctor) elDoctor.innerText = (rx.is_emergency ? '🚨 طوارئ — د. ' : 'د. ') + (rx.doctor_name || 'الاستشاري');

        const elDiag = document.getElementById('modalDispDiagnosisText');
        if (elDiag) elDiag.innerText = rx.diagnosis || rx.notes || 'لا يوجد تشخيص إضافي مسجل';

        const elItemsCount = document.getElementById('modalDispItemsCount');
        if (elItemsCount) elItemsCount.innerText = (rx.items ? rx.items.length : 0) + ' أدوية';

        const feedbackBanner = document.getElementById('modalRxSubstitutionFeedbackBanner');
        if (feedbackBanner) {
            const approvedItems = (rx.items || []).filter(i => i.substitution_status === 'approved');
            const pendingItems = (rx.items || []).filter(i => i.substitution_status === 'pending_approval');
            const rejectedItems = (rx.items || []).filter(i => i.substitution_status === 'rejected');

            if (approvedItems.length > 0) {
                feedbackBanner.style.display = 'block';
                feedbackBanner.innerHTML = `
                    <div class="alert alert-success border-2 border-success shadow-sm rounded-3 p-3 d-flex align-items-center gap-3">
                        <div class="bg-success text-white p-2 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-check-double fa-bounce"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong class="text-success fs-6">✅ وافق الطبيب المعالج على استبدال (${approvedItems.length}) دواء!</strong>
                            <div class="small text-dark mt-1">
                                ${approvedItems.map(ai => `<span class="me-3"><i class="fas fa-pills text-success me-1"></i><strong>${ai.name}</strong></span>`).join('')}
                            </div>
                        </div>
                    </div>`;
            } else if (pendingItems.length > 0) {
                feedbackBanner.style.display = 'block';
                feedbackBanner.innerHTML = `
                    <div class="alert alert-warning border-2 border-warning shadow-sm rounded-3 p-3 d-flex align-items-center gap-3">
                        <div class="bg-warning text-dark p-2 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-hourglass-half fa-spin"></i>
                        </div>
                        <div>
                            <strong class="text-dark fs-6">⏳ بانتظار موافقة الطبيب على البديل المقترح</strong>
                        </div>
                    </div>`;
            } else if (rejectedItems.length > 0) {
                feedbackBanner.style.display = 'block';
                feedbackBanner.innerHTML = `
                    <div class="alert alert-danger border-2 border-danger shadow-sm rounded-3 p-3 d-flex align-items-center gap-3">
                        <div class="bg-danger text-white p-2 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div>
                            <strong class="text-danger fs-6">❌ رفض الطبيب المعالج مقترح البديل</strong>
                        </div>
                    </div>`;
            } else {
                feedbackBanner.style.display = 'none';
            }
        }

        const printBtn = document.getElementById('modalBtnPrintPrescription');
        if (printBtn) {
            if (rx.is_emergency) {
                printBtn.style.display = 'none';
            } else {
                printBtn.style.display = '';
                printBtn.href = `{{ url('doctor/visits') }}/${rx.patient_id || ''}/prescription/print`;
            }
        }

        const tbody = document.getElementById('modalDispensingItemsBody');
        let html = '';

        (rx.items || []).forEach((item, index) => {
            const hasStock = item.is_in_stock;
            let stockBadge = '';

            if (item.substitution_status === 'pending_approval') {
                stockBadge = `<span class="badge bg-warning text-dark border border-warning px-2 py-1"><i class="fas fa-hourglass-half fa-spin me-1"></i> بانتظار الطبيب</span>`;
            } else if (item.substitution_status === 'approved') {
                stockBadge = `<span class="badge bg-success text-white px-2 py-1"><i class="fas fa-check-circle me-1"></i> بديل معتمد</span>`;
            } else {
                stockBadge = `
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <span class="badge ${hasStock ? 'bg-success' : 'bg-danger'} px-2 py-1">${hasStock ? 'متوفر' : 'غير متوفر'}</span>
                        <button type="button" class="btn btn-xs btn-outline-warning text-dark py-1 px-2 rounded" onclick="openAlternativesModal(${item.id})">
                            <i class="fas fa-exchange-alt me-1"></i> اقتراح بديل
                        </button>
                    </div>`;
            }

            html += `
                <tr>
                    <td class="text-center fw-bold text-muted">${index + 1}</td>
                    <td>
                        <div class="fw-bold text-dark">${item.name}</div>
                        <div class="small text-muted">${item.dosage_form || ''} ${item.strength || ''}</div>
                    </td>
                    <td class="text-center font-monospace fw-bold">${item.quantity} ${item.unit_type === 'sub_unit' ? (item.sub_unit || 'شريط') : (item.main_unit || 'علبة')}</td>
                    <td>${item.dosage_frequency || 'حسب الإرشادات'}</td>
                    <td class="text-center">${stockBadge}</td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    function openAlternativesModal(itemId) {
        targetItemForAlternative = itemId;
        if (!currentPrescriptionData) return;

        const item = currentPrescriptionData.items.find(i => i.id === itemId);
        if (!item) return;

        document.getElementById('modalTargetMedName').innerText = `${item.name} (${item.dosage_form || ''} - ${item.strength || ''})`;
        document.getElementById('modalAltReasonInput').value = '';
        const searchInp = document.getElementById('modalAltSearchInput');
        searchInp.value = '';

        renderAlternativesList([]);

        const altModalEl = document.getElementById('alternativeModal');
        if (altModalEl && altModalEl.parentElement !== document.body) {
            document.body.appendChild(altModalEl);
        }
        const altModal = bootstrap.Modal.getOrCreateInstance(altModalEl);
        altModal.show();

        altModalEl.addEventListener('shown.bs.modal', function () {
            searchInp.focus();
        }, { once: true });
    }

    function renderAlternativesList(alternatives) {
        const listContainer = document.getElementById('alternativesModalList');
        document.getElementById('modalAltCountBadge').innerText = `${alternatives.length} بديل`;

        if (alternatives.length === 0) {
            listContainer.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-search fa-2x mb-2 text-warning opacity-75"></i>
                    <p class="small mb-1 fw-bold">اكتب اسم الدواء البديل في حقل البحث أعلاه</p>
                </div>`;
            return;
        }

        let html = '';
        alternatives.forEach(alt => {
            html += `
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                    <div>
                        <h6 class="mb-1 fw-bold text-dark">${alt.name}</h6>
                        <small class="text-muted">${alt.generic_name || ''} | ${alt.dosage_form || ''} (${alt.strength || ''})</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary fw-bold px-3 py-1.5" onclick="submitAlternativeSuggestion(${alt.id})">
                        <i class="fas fa-paper-plane me-1"></i> إرسال المقترح
                    </button>
                </div>`;
        });

        listContainer.innerHTML = html;
    }

    function submitAlternativeSuggestion(suggestedMedId) {
        if (!targetItemForAlternative) return;

        const reason = document.getElementById('modalAltReasonInput').value.trim();

        fetch(`{{ url('pharmacy/pos/prescription-items') }}/${targetItemForAlternative}/suggest-alternative`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                suggested_medicine_id: suggestedMedId,
                substitution_reason: reason
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('alternativeModal')).hide();
                alert(data.message);
                if (currentSelectedRxId) {
                    openDispensingModal(currentSelectedRxId);
                }
                refreshPrescriptionsQueue(false);
            } else {
                alert(data.message || 'حدث خطأ أثناء إرسال المقترح.');
            }
        })
        .catch(err => {
            console.error('Error suggesting alternative:', err);
            alert('حدث خطأ في الاتصال بالخادم.');
        });
    }

    function executeDispenseFromModal() {
        if (!currentPrescriptionData) return;

        const btn = document.getElementById('modalBtnExecuteDispense');
        btn.disabled = true;

        fetch(`{{ url('pharmacy/pos/prescriptions') }}/${currentPrescriptionData.id}/dispense`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ items: [] })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                try {
                    document.getElementById('dispenseAudio').play();
                } catch(e) {}

                bootstrap.Modal.getOrCreateInstance(document.getElementById('dispensingModal')).hide();
                alert(`✅ ${data.message}`);
                refreshPrescriptionsQueue(true);
            } else {
                alert(data.message || 'حدث خطأ أثناء صرف الوصفة.');
            }
        })
        .catch(err => {
            btn.disabled = false;
            console.error('Error dispensing prescription:', err);
            alert('حدث خطأ في الاتصال أثناء الصرف.');
        });
    }

    function renderQuickSearchResults(medicines) {
        const list = document.getElementById('quickSearchResultsList');
        if (!medicines || medicines.length === 0) {
            list.innerHTML = `<div class="text-center py-4 text-muted">لم يتم العثور على أدوية مطابقة</div>`;
            return;
        }

        let html = '';
        medicines.forEach(med => {
            html += `
                <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                    <div>
                        <h6 class="mb-1 fw-bold text-dark">${med.name}</h6>
                        <small class="text-muted">${med.generic_name || ''} | ${med.dosage_form || ''} (${med.strength || ''})</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary font-monospace">${formatMoney(med.sale_price)} د.ع</div>
                        <span class="badge ${med.total_stock > 0 ? 'bg-success' : 'bg-danger'}">رصيد: ${med.total_stock}</span>
                    </div>
                </div>`;
        });
        list.innerHTML = html;
    }

    // دوال مساعدة
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    function formatMoney(amount) {
        return (parseFloat(amount) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function trim(str) {
        return (str || '').trim();
    }
</script>
@endpush
