@extends('layouts.app')

@section('content')
<style>
/* Kanban Board Styling */
.kanban-board {
    display: grid;
    grid-template-columns: repeat(3, minmax(320px, 1fr));
    gap: 1.25rem;
    align-items: start;
}
@media (max-width: 992px) {
    .kanban-board {
        grid-template-columns: 1fr;
    }
}
.kanban-col {
    background: #f8fafc;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 210px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}
.kanban-col-header {
    padding: 1rem 1.25rem;
    border-bottom: 2px solid transparent;
    border-radius: 14px 14px 0 0;
    background: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.kanban-col-new .kanban-col-header {
    border-bottom-color: #3b82f6;
}
.kanban-col-pending .kanban-col-header {
    border-bottom-color: #f59e0b;
}
.kanban-col-ready .kanban-col-header {
    border-bottom-color: #10b981;
}
.kanban-col-body {
    padding: 0.85rem;
    overflow-y: auto;
    flex-grow: 1;
    min-height: 250px;
}
.kanban-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.85rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
}
.kanban-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.08);
}
.kanban-card.card-ready {
    border-left: 4px solid #10b981;
    background: linear-gradient(to left, #ffffff, #f0fdf4);
}
.kanban-card.card-pending {
    border-left: 4px solid #f59e0b;
    background: linear-gradient(to left, #ffffff, #fffbeb);
}
.kanban-card.card-new {
    border-left: 4px solid #3b82f6;
}
.kanban-empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #94a3b8;
}
/* Compact Table Styling */
.table-pos-compact tbody tr {
    transition: background-color 0.15s ease;
    cursor: pointer;
}
.table-pos-compact tbody tr:hover {
    background-color: #f1f5f9 !important;
}
.sla-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.76rem;
    font-weight: 600;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
}
.sla-green {
    background-color: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.sla-yellow {
    background-color: #fffbeb;
    color: #d97706;
    border: 1px solid #fde68a;
}
.sla-red {
    background-color: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
    animation: pulseRed 2s infinite;
}
@keyframes pulseRed {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}
.filter-btn.active {
    background-color: #0d6efd !important;
    color: #fff !important;
    border-color: #0d6efd !important;
}
</style>

<div class="container-fluid py-3 px-lg-4">
    <!-- الشريط العلوي لمحطة صرف الأدوية -->
    <div class="card border-0 shadow-sm rounded-4 mb-3 bg-dark text-white p-3">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success text-white p-2 rounded-3 fs-4 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-pills"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-white d-flex align-items-center gap-2">
                        خط صرف وتجهيز الأدوية (Pharmacy POS Flow)
                        <span class="badge bg-success-subtle text-success fs-6 rounded-pill px-3 py-1 border border-success">
                            <i class="fas fa-satellite-dish me-1 fa-fade"></i> استقبال مباشر
                        </span>
                    </h5>
                    <small class="text-secondary">صرف وتجهيز الوصفات الطبية الإلكترونية ومقترحات البدائل لحظياً</small>
                </div>
            </div>

            <!-- المؤشرات وأزرار التحكم السريعة -->
            <div class="d-flex align-items-center flex-wrap gap-2">
                <!-- أزرار التبديل بين الجدول والكانبان -->
                <div class="btn-group btn-group-sm bg-secondary p-1 rounded-3" role="group">
                    <button type="button" class="btn btn-sm text-white fw-bold px-3 py-1 rounded-2" id="btnViewTable" onclick="switchPosView('table')">
                        <i class="fas fa-list-ul me-1"></i> جدول مدمج سريع
                    </button>
                    <button type="button" class="btn btn-sm text-white fw-bold px-3 py-1 rounded-2" id="btnViewKanban" onclick="switchPosView('kanban')">
                        <i class="fas fa-columns me-1"></i> خط كانبان
                    </button>
                </div>

                <!-- حقل البحث السريع الفوري / مسح الباركود -->
                <div class="input-group input-group-sm" style="width: 260px;">
                    <span class="input-group-text bg-secondary border-0 text-white"><i class="fas fa-barcode"></i></span>
                    <input type="text" id="kanbanSearchInput" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="امسح باركود RX أو ابحث بالاسم...">
                </div>

                <button type="button" class="btn btn-outline-info btn-sm px-3 py-2 rounded-3" id="btnRefreshQueue" title="تحديث يدوي">
                    <i class="fas fa-sync-alt me-1" id="refreshIcon"></i> تحديث
                </button>

                <button type="button" class="btn btn-outline-light btn-sm px-3 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#manualSearchModal">
                    <i class="fas fa-search me-1"></i> فحص رصيد / بدائل
                </button>

                <a href="{{ route('pharmacy.pos.sales.history') }}" class="btn btn-outline-secondary btn-sm px-3 py-2 rounded-3 text-white">
                    <i class="fas fa-history me-1"></i> سجل الصرف
                </a>

                <div class="form-check form-switch small mb-0 ms-2 text-white d-flex align-items-center gap-2">
                    <input class="form-check-input" type="checkbox" id="autoRefreshSwitch" checked>
                    <label class="form-check-label text-secondary small" for="autoRefreshSwitch">تحديث تلقائي</label>
                </div>
            </div>
        </div>

        <!-- شريط الفلاتر السريعة والإحصائيات اللحظية -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 pt-3 mt-3 border-top border-secondary border-opacity-50">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <span class="small text-secondary me-1"><i class="fas fa-filter me-1"></i>تصفية سريعة:</span>
                <button type="button" class="btn btn-sm btn-outline-light py-1 px-3 rounded-pill filter-btn active" data-filter="all" onclick="setPosFilter('all')">
                    الكل <span class="badge bg-secondary ms-1" id="filterCountAll">0</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-success py-1 px-3 rounded-pill filter-btn" data-filter="ready" onclick="setPosFilter('ready')">
                    <i class="fas fa-check-double me-1"></i> جاهزة للصرف الفوري <span class="badge bg-success ms-1" id="filterCountReady">0</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning text-white py-1 px-3 rounded-pill filter-btn" data-filter="pending" onclick="setPosFilter('pending')">
                    <i class="fas fa-hourglass-half me-1"></i> بانتظار قرار الطبيب <span class="badge bg-warning text-dark ms-1" id="filterCountPending">0</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill filter-btn" data-filter="new" onclick="setPosFilter('new')">
                    <i class="fas fa-inbox me-1"></i> جديدة واردة <span class="badge bg-primary ms-1" id="filterCountNew">0</span>
                </button>
            </div>

            <div class="small text-secondary d-flex align-items-center gap-3">
                <span class="d-flex align-items-center gap-1"><span class="badge bg-success rounded-circle p-1"> </span> جاهز للصرف</span>
                <span class="d-flex align-items-center gap-1"><span class="badge bg-warning rounded-circle p-1"> </span> بديل معلق</span>
                <span class="d-flex align-items-center gap-1"><span class="badge bg-primary rounded-circle p-1"> </span> وارد جديد</span>
                <span class="badge bg-dark border border-secondary text-info"><i class="fas fa-keyboard me-1"></i> المسح بالباركود مفعل تلقائياً</span>
            </div>
        </div>
    </div>

    <!-- 1) عرض الجدول المدمج فائق السرعة (Table View) -->
    <div id="posTableViewContainer" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover table-pos-compact align-middle mb-0" id="posCompactTable">
                <thead class="table-light text-muted small border-bottom">
                    <tr>
                        <th style="width: 140px;">رقم الوصفة (RX)</th>
                        <th style="width: 130px;">وقت الوصول</th>
                        <th style="min-width: 180px;">المريض</th>
                        <th style="min-width: 180px;">الطبيب والعيادة</th>
                        <th style="min-width: 280px;">الأدوية والجرعات المطلوبة</th>
                        <th style="width: 150px;" class="text-center">حالة الصرف</th>
                        <th style="width: 150px;" class="text-center">الإجراء السريع</th>
                    </tr>
                </thead>
                <tbody id="posTableBody">
                    <!-- Dynamic Table Rows -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2) عرض خط الصرف KANBAN BOARD (3 أعمدة) -->
    <div class="kanban-board" id="posKanbanViewContainer" style="display: none;">
        <!-- العمود 1: 📥 وصفات جديدة واردة -->
        <div class="kanban-col kanban-col-new">
            <div class="kanban-col-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary text-white p-2 rounded-circle fs-6 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">وصفات واردة جديدة</h6>
                        <small class="text-muted">بانتظار المراجعة والتجهيز</small>
                    </div>
                </div>
                <span class="badge bg-primary rounded-pill font-monospace fs-6 px-3 py-1" id="badgeCountNew">0</span>
            </div>
            <div class="kanban-col-body" id="colBodyNew">
                <!-- Dynamic Cards -->
            </div>
        </div>

        <!-- العمود 2: ⏳ بانتظار موافقة الطبيب على البديل -->
        <div class="kanban-col kanban-col-pending">
            <div class="kanban-col-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-warning text-dark p-2 rounded-circle fs-6 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fas fa-hourglass-half fa-spin"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">بانتظار قرار الطبيب</h6>
                        <small class="text-muted">مقترحات بدائل أدوية معلقة</small>
                    </div>
                </div>
                <span class="badge bg-warning text-dark rounded-pill font-monospace fs-6 px-3 py-1" id="badgeCountPending">0</span>
            </div>
            <div class="kanban-col-body" id="colBodyPending">
                <!-- Dynamic Cards -->
            </div>
        </div>

        <!-- العمود 3: ✅ معتمدة وجاهزة للصرف الفوري -->
        <div class="kanban-col kanban-col-ready">
            <div class="kanban-col-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-success text-white p-2 rounded-circle fs-6 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">معتمدة وجاهزة للصرف</h6>
                        <small class="text-muted">وافق الطبيب أو متوفرة بالكامل</small>
                    </div>
                </div>
                <span class="badge bg-success rounded-pill font-monospace fs-6 px-3 py-1" id="badgeCountReady">0</span>
            </div>
            <div class="kanban-col-body" id="colBodyReady">
                <!-- Dynamic Cards -->
            </div>
        </div>
    </div>
</div>

<!-- Modal: نافذة فحص وصرف وتجهيز الوصفة السريعة -->
<div class="modal fade" id="dispensingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <!-- Modal Header -->
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

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                <!-- إشعار موافقة / رفض الطبيب على البديل -->
                <div id="modalRxSubstitutionFeedbackBanner" class="mb-3" style="display: none;"></div>

                <!-- ملاحظات / التشخيص إن وجد -->
                <div class="alert alert-white bg-white border rounded-3 p-3 mb-3 d-flex align-items-center gap-2 small shadow-xs">
                    <i class="fas fa-stethoscope text-info fs-5 me-2"></i>
                    <div>
                        <strong class="text-dark">التشخيص / توصيات الطبيب:</strong>
                        <span class="text-muted ms-1" id="modalDispDiagnosisText">لا يوجد تشخيص مسجل</span>
                    </div>
                </div>

                <!-- جدول الأدوية المطلوب صرفها -->
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
                                    <th style="width: 20%;" class="text-center">حالة التوفر بالمخزون</th>
                                </tr>
                            </thead>
                            <tbody id="modalDispensingItemsBody">
                                <!-- Dynamic Items Table -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white p-3 border-top justify-content-between">
                <div class="text-muted small">
                    <i class="fas fa-shield-alt text-success me-1"></i> يتم خصم الأرصدة تلقائياً بنظام FEFO (الأقرب انتهاءً أولاً) وإشعار الطبيب فوراً.
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="#" target="_blank" class="btn btn-outline-dark px-3 py-2 fw-bold rounded-3" id="modalBtnPrintPrescription">
                        <i class="fas fa-print me-1"></i> طباعة الوصفة (RX)
                    </a>

                    <button type="button" class="btn btn-success btn-lg px-4 py-2 fw-bold shadow-sm rounded-3 d-flex align-items-center gap-2" id="modalBtnExecuteDispense" onclick="executeDispenseFromModal()">
                        <i class="fas fa-check-circle fa-lg"></i>
                        <span>تأكيد صرف وتجهيز الدواء</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: فحص رصيد وبدائل دواء سريع -->
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

<!-- Modal: استبدال الدواء ببديل مكافئ متوفر -->
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

<!-- صوت تنبيه خفيف عند اكتمال الصرف -->
<audio id="dispenseAudio" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>
@endsection

@push('scripts')
<script>
    let currentSelectedRxId = null;
    let currentPrescriptionData = null;
    let autoRefreshTimer = null;
    let targetItemForAlternative = null;
    let allPrescriptionsCache = [];
    let currentViewMode = localStorage.getItem('pos_view_mode') || 'table';
    let currentPosFilter = 'all';

    // مخزن الباركود من الماسح الضوئي
    let barcodeScannerBuffer = '';
    let barcodeScannerLastTime = 0;

    document.addEventListener('DOMContentLoaded', function() {
        // تهيئة وضع العرض المفضل
        switchPosView(currentViewMode, false);

        // تشغيل التحديث التلقائي كل 10 ثوان
        startAutoRefresh();
        refreshPrescriptionsQueue(true);

        // التبديل اليدوي للتحديث التلقائي
        document.getElementById('autoRefreshSwitch').addEventListener('change', function(e) {
            if (e.target.checked) {
                startAutoRefresh();
            } else {
                clearInterval(autoRefreshTimer);
            }
        });

        // زر التحديث اليدوي
        document.getElementById('btnRefreshQueue').addEventListener('click', function() {
            refreshPrescriptionsQueue(true);
        });

        // البحث الفوري في الجدول والكانبان
        document.getElementById('kanbanSearchInput').addEventListener('input', function() {
            renderAllViews();
        });

        // ماسح الباركود الضوئي المباشر (Hardware Barcode Gun Listener)
        document.addEventListener('keydown', function(e) {
            const now = Date.now();
            // إذا كان المدخل سريعاً (أقل من 60ms بين المفاتيح فهو ماسح باركود)
            if (now - barcodeScannerLastTime > 100) {
                barcodeScannerBuffer = '';
            }
            barcodeScannerLastTime = now;

            if (e.key === 'Enter') {
                if (barcodeScannerBuffer.length >= 3) {
                    const scannedCode = barcodeScannerBuffer.trim().toLowerCase();
                    const matchedRx = allPrescriptionsCache.find(r => 
                        (r.prescription_number || '').toLowerCase() === scannedCode ||
                        (r.prescription_number || '').toLowerCase().replace(/[^a-z0-9]/g, '') === scannedCode.replace(/[^a-z0-9]/g, '')
                    );
                    if (matchedRx) {
                        e.preventDefault();
                        openDispensingModal(matchedRx.id);
                    }
                }
                barcodeScannerBuffer = '';
            } else if (e.key.length === 1) {
                barcodeScannerBuffer += e.key;
            }
        });

        // نافذة فحص الأدوية السريعة
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

        // البحث عن بدائل داخل مودال البدائل
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

        // اختصار لوحة المفاتيح: Space لتأكيد الصرف عند فتح المودال
        document.addEventListener('keydown', function(e) {
            if (e.code === 'Space' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                const modal = document.getElementById('dispensingModal');
                if (modal.classList.contains('show')) {
                    e.preventDefault();
                    executeDispenseFromModal();
                }
            }
        });
    });

    // تبديل نمط العرض (جدول / كانبان)
    function switchPosView(mode, savePreference = true) {
        currentViewMode = mode;
        if (savePreference) {
            localStorage.setItem('pos_view_mode', mode);
        }

        const tableBtn = document.getElementById('btnViewTable');
        const kanbanBtn = document.getElementById('btnViewKanban');
        const tableContainer = document.getElementById('posTableViewContainer');
        const kanbanContainer = document.getElementById('posKanbanViewContainer');

        if (mode === 'table') {
            tableBtn.className = 'btn btn-sm btn-primary text-white fw-bold px-3 py-1 rounded-2 shadow-xs';
            kanbanBtn.className = 'btn btn-sm text-white-50 fw-bold px-3 py-1 rounded-2';
            tableContainer.style.display = 'block';
            kanbanContainer.style.display = 'none';
        } else {
            tableBtn.className = 'btn btn-sm text-white-50 fw-bold px-3 py-1 rounded-2';
            kanbanBtn.className = 'btn btn-sm btn-primary text-white fw-bold px-3 py-1 rounded-2 shadow-xs';
            tableContainer.style.display = 'none';
            kanbanContainer.style.display = 'grid';
        }

        renderAllViews();
    }

    // تعيين فلتر التصفية السريع
    function setPosFilter(filter) {
        currentPosFilter = filter;
        document.querySelectorAll('.filter-btn').forEach(btn => {
            if (btn.getAttribute('data-filter') === filter) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        renderAllViews();
    }

    function startAutoRefresh() {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = setInterval(() => {
            refreshPrescriptionsQueue(false);
        }, 10000);
    }

    // تحديث طابور الوصفات بالـ AJAX
    function refreshPrescriptionsQueue(showSpinner = false) {
        const refreshIcon = document.getElementById('refreshIcon');
        if (showSpinner && refreshIcon) refreshIcon.classList.add('fa-spin');

        fetch(`{{ route('pharmacy.pos.pending-prescriptions') }}`)
            .then(res => res.json())
            .then(data => {
                if (showSpinner && refreshIcon) refreshIcon.classList.remove('fa-spin');
                if (data.success) {
                    allPrescriptionsCache = data.prescriptions || [];
                    renderAllViews();
                }
            })
            .catch(err => {
                if (showSpinner && refreshIcon) refreshIcon.classList.remove('fa-spin');
                console.error('Error fetching pending prescriptions:', err);
            });
    }

    // رسم كافة العروض (الجدول والكانبان والعدادات)
    function renderAllViews() {
        const searchInput = document.getElementById('kanbanSearchInput');
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        // 1. تحديث إحصائيات العدادات العامة
        const allItems = allPrescriptionsCache;
        const newItems = allItems.filter(rx => rx.stage === 'new');
        const pendingItems = allItems.filter(rx => rx.stage === 'awaiting_doctor');
        const readyItems = allItems.filter(rx => rx.stage === 'approved_ready');

        document.getElementById('filterCountAll').innerText = allItems.length;
        document.getElementById('filterCountReady').innerText = readyItems.length;
        document.getElementById('filterCountPending').innerText = pendingItems.length;
        document.getElementById('filterCountNew').innerText = newItems.length;

        document.getElementById('badgeCountNew').innerText = newItems.length;
        document.getElementById('badgeCountPending').innerText = pendingItems.length;
        document.getElementById('badgeCountReady').innerText = readyItems.length;

        // 2. تطبيق التصفية والبحث
        let filtered = allItems;

        if (currentPosFilter === 'ready') {
            filtered = filtered.filter(rx => rx.stage === 'approved_ready');
        } else if (currentPosFilter === 'pending') {
            filtered = filtered.filter(rx => rx.stage === 'awaiting_doctor');
        } else if (currentPosFilter === 'new') {
            filtered = filtered.filter(rx => rx.stage === 'new');
        }

        if (query.length > 0) {
            filtered = filtered.filter(rx => 
                (rx.prescription_number || '').toLowerCase().includes(query) ||
                (rx.patient_name || '').toLowerCase().includes(query) ||
                (rx.doctor_name || '').toLowerCase().includes(query) ||
                (rx.items_summary || []).some(i => (i.name || '').toLowerCase().includes(query))
            );
        }

        // 3. رسم الجدول ورسم الكانبان
        renderCompactTable(filtered);
        renderKanbanBoard(filtered);
    }

    // رسم الجدول المدمج فائق السرعة
    function renderCompactTable(prescriptions) {
        const tbody = document.getElementById('posTableBody');
        if (!tbody) return;

        if (prescriptions.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i>
                        <h6 class="fw-bold">لا توجد طلبات وصفات تطابق خيارات التصفية الحالية</h6>
                        <small>ستظهر الوصفات تلقائياً فور كتابتها في العيادات الاستشارية</small>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        prescriptions.forEach((rx, index) => {
            // احتساب شارة وقت الانتظار SLA
            let slaClass = 'sla-green';
            let slaText = rx.time_ago || 'الآن';
            if (rx.time_ago && (rx.time_ago.includes('ساعة') || rx.time_ago.includes('ساعات') || rx.time_ago.includes('hour'))) {
                slaClass = 'sla-red';
            } else if (rx.time_ago && (rx.time_ago.includes('15') || rx.time_ago.includes('20') || rx.time_ago.includes('30') || rx.time_ago.includes('40') || rx.time_ago.includes('50'))) {
                slaClass = 'sla-yellow';
            }

            // ملخص الأدوية
            const itemsPills = (rx.items_summary || []).map(it => {
                let badgeStyle = 'bg-light text-dark border';
                let icon = 'fa-pills';
                if (it.substitution_status === 'approved') {
                    badgeStyle = 'bg-success text-white';
                    icon = 'fa-check';
                } else if (it.substitution_status === 'pending_approval') {
                    badgeStyle = 'bg-warning text-dark border border-warning';
                    icon = 'fa-hourglass-half';
                }
                return `<span class="badge ${badgeStyle} small me-1 mb-1 px-2 py-1"><i class="fas ${icon} me-1"></i>${it.name}</span>`;
            }).join('');

            // شارة الحالة وزر الإجراء
            let statusBadge = '';
            let actionBtn = '';

            if (rx.stage === 'approved_ready') {
                statusBadge = `<span class="badge bg-success px-3 py-2 fs-6 rounded-pill"><i class="fas fa-check-circle me-1"></i> جاهزة للصرف</span>`;
                actionBtn = `
                    <button type="button" class="btn btn-success btn-sm w-100 fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1" onclick="openDispensingModal(${rx.id}); event.stopPropagation();">
                        <i class="fas fa-check-double fa-lg"></i> ⚡ صرف فوري
                    </button>
                `;
            } else if (rx.stage === 'awaiting_doctor') {
                statusBadge = `<span class="badge bg-warning text-dark px-3 py-2 fs-6 rounded-pill border border-warning"><i class="fas fa-hourglass-half me-1"></i> بانتظار الطبيب</span>`;
                actionBtn = `
                    <button type="button" class="btn btn-outline-warning text-dark btn-sm w-100 fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1" onclick="openDispensingModal(${rx.id}); event.stopPropagation();">
                        <i class="fas fa-search-plus"></i> مراجعة البديل
                    </button>
                `;
            } else {
                statusBadge = `<span class="badge bg-primary px-3 py-2 fs-6 rounded-pill"><i class="fas fa-inbox me-1"></i> واردة جديدة</span>`;
                actionBtn = `
                    <button type="button" class="btn btn-primary btn-sm w-100 fw-bold shadow-xs py-2 d-flex align-items-center justify-content-center gap-1" onclick="openDispensingModal(${rx.id}); event.stopPropagation();">
                        <i class="fas fa-tasks"></i> ⚡ فحص وتجهيز
                    </button>
                `;
            }

            html += `
                <tr onclick="openDispensingModal(${rx.id})">
                    <td class="font-monospace fw-bold text-primary">
                        <span class="fs-6 d-block">${rx.prescription_number}</span>
                        <small class="text-muted font-monospace">#${rx.id}</small>
                    </td>
                    <td>
                        <span class="sla-badge ${slaClass}">
                            <i class="far fa-clock"></i> ${slaText}
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold text-dark fs-6">
                            <i class="fas fa-user text-secondary me-1"></i>${rx.patient_name}
                        </div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark small">
                            <i class="fas fa-user-md text-info me-1"></i>${rx.doctor_name}
                        </div>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap align-items-center">
                            ${itemsPills}
                        </div>
                    </td>
                    <td class="text-center">
                        ${statusBadge}
                    </td>
                    <td class="text-center" style="min-width: 140px;">
                        ${actionBtn}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    // رسم لوحة الكانبان بالأعمدة الثلاثة
    function renderKanbanBoard(prescriptions) {
        const colNew = document.getElementById('colBodyNew');
        const colPending = document.getElementById('colBodyPending');
        const colReady = document.getElementById('colBodyReady');
        if (!colNew || !colPending || !colReady) return;

        const newItems = prescriptions.filter(rx => rx.stage === 'new');
        const pendingItems = prescriptions.filter(rx => rx.stage === 'awaiting_doctor');
        const readyItems = prescriptions.filter(rx => rx.stage === 'approved_ready');

        // العمود 1: جديدة
        if (newItems.length === 0) {
            colNew.innerHTML = `
                <div class="kanban-empty-state">
                    <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-50"></i>
                    <p class="small mb-0">لا توجد وصفات جديدة</p>
                </div>`;
        } else {
            colNew.innerHTML = newItems.map(rx => createKanbanCardHtml(rx, 'new')).join('');
        }

        // العمود 2: بانتظار الطبيب
        if (pendingItems.length === 0) {
            colPending.innerHTML = `
                <div class="kanban-empty-state">
                    <i class="fas fa-clock fa-2x mb-2 text-warning opacity-50"></i>
                    <p class="small mb-0">لا توجد طلبات بانتظار الطبيب</p>
                </div>`;
        } else {
            colPending.innerHTML = pendingItems.map(rx => createKanbanCardHtml(rx, 'pending')).join('');
        }

        // العمود 3: جاهزة للصرف
        if (readyItems.length === 0) {
            colReady.innerHTML = `
                <div class="kanban-empty-state">
                    <i class="fas fa-prescription-bottle-alt fa-2x mb-2 text-success opacity-50"></i>
                    <p class="small mb-0">لا توجد وصفات جاهزة للصرف</p>
                </div>`;
        } else {
            colReady.innerHTML = readyItems.map(rx => createKanbanCardHtml(rx, 'ready')).join('');
        }
    }

    // توليد HTML بطاقة الكانبان
    function createKanbanCardHtml(rx, type) {
        const itemsSummaryHtml = (rx.items_summary || []).map(it => {
            let badgeClass = 'bg-light text-dark border';
            if (it.substitution_status === 'approved') badgeClass = 'bg-success text-white';
            else if (it.substitution_status === 'pending_approval') badgeClass = 'bg-warning text-dark border border-warning';
            return `<span class="badge ${badgeClass} small me-1 mb-1 px-2 py-1"><i class="fas fa-pills me-1"></i>${it.name}</span>`;
        }).join('');

        // شريط طوارئ في أعلى البطاقة
        const emergencyBanner = rx.is_emergency
            ? `<div class="d-flex align-items-center gap-1 mb-2 px-2 py-1 bg-danger bg-opacity-10 rounded border border-danger" style="margin: -4px -4px 8px -4px;">
                   <i class="fas fa-ambulance text-danger small"></i>
                   <span class="text-danger fw-bold small">🚨 وصفة طوارئ</span>
               </div>`
            : '';

        let actionButtonHtml = '';
        if (type === 'ready') {
            actionButtonHtml = `
                <div class="d-flex gap-1 mt-3 pt-2 border-top">
                    <button type="button" class="btn btn-success btn-sm w-100 fw-bold shadow-xs d-flex align-items-center justify-content-center gap-1" onclick="openDispensingModal(${rx.id}); event.stopPropagation();">
                        <i class="fas fa-check-circle fa-lg"></i> ⚡ صرف فوري وتجهيز
                    </button>
                </div>`;
        } else if (type === 'pending') {
            actionButtonHtml = `
                <div class="d-flex gap-1 mt-3 pt-2 border-top">
                    <button type="button" class="btn btn-outline-warning text-dark btn-sm w-100 fw-bold shadow-xs d-flex align-items-center justify-content-center gap-1" onclick="openDispensingModal(${rx.id}); event.stopPropagation();">
                        <i class="fas fa-hourglass-half"></i> مراجعة المقترح
                    </button>
                </div>`;
        } else {
            actionButtonHtml = `
                <div class="d-flex gap-1 mt-3 pt-2 border-top">
                    <button type="button" class="btn ${rx.is_emergency ? 'btn-danger' : 'btn-primary'} btn-sm w-100 fw-bold shadow-xs d-flex align-items-center justify-content-center gap-1" onclick="openDispensingModal(${rx.id}); event.stopPropagation();">
                        <i class="fas fa-tasks"></i> ${rx.is_emergency ? '🚨 صرف طوارئ STAT' : '⚡ فحص وتجهيز الصرف'}
                    </button>
                </div>`;
        }

        return `
            <div class="kanban-card card-${type}${rx.is_emergency ? ' border border-danger' : ''}"
                 data-id="${rx.id}"
                 data-rx-number="${rx.prescription_number}"
                 data-patient-name="${rx.patient_name}"
                 onclick="openDispensingModal(${rx.id})">
                ${emergencyBanner}
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge ${type === 'ready' ? 'bg-success' : (type === 'pending' ? 'bg-warning text-dark' : 'bg-primary-subtle text-primary')} font-monospace fw-bold px-2 py-1">
                        <i class="fas fa-hashtag me-1"></i>${rx.prescription_number}
                    </span>
                    <span class="badge bg-light text-muted border font-monospace small">
                        <i class="far fa-clock me-1"></i>${rx.time_ago}
                    </span>
                </div>
                <div class="fw-bold text-dark fs-6 mb-1">
                    <i class="fas fa-user-injured text-secondary me-1"></i>${rx.patient_name}
                </div>
                <div class="small text-muted mb-2">
                    <i class="fas fa-user-md text-info me-1"></i>${rx.doctor_name}
                    ${rx.is_emergency ? '<span class="badge bg-danger text-white ms-1 small">طوارئ</span>' : ''}
                </div>
                <div class="mb-2">
                    ${itemsSummaryHtml}
                </div>
                ${actionButtonHtml}
            </div>
        `;
    }

    // فتح نافذة تفاصيل وصرف الوصفة
    function openDispensingModal(rxId) {
        currentSelectedRxId = rxId;

        // جلب تفاصيل الوصفة بالـ AJAX
        fetch(`{{ url('pharmacy/pos/prescriptions') }}/${rxId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.prescription) {
                    renderModalPrescription(data.prescription);
                    const modal = new bootstrap.Modal(document.getElementById('dispensingModal'));
                    modal.show();
                }
            })
            .catch(err => {
                console.error('Error loading prescription:', err);
                alert('حدث خطأ أثناء تحميل تفاصيل الوصفة.');
            });
    }

    // تعبئة بيانات نافذة الصرف
    function renderModalPrescription(rx) {
        currentPrescriptionData = rx;

        document.getElementById('modalDispRxNumber').innerText = rx.prescription_number;
        document.getElementById('modalDispPatientName').innerText = rx.patient_name || 'مريض';
        document.getElementById('modalDispDoctorName').innerText =
            (rx.is_emergency ? '🚨 طوارئ — د. ' : 'د. ') + (rx.doctor_name || 'الاستشاري');
        document.getElementById('modalDispDiagnosisText').innerText = rx.diagnosis || rx.notes || 'لا يوجد تشخيص إضافي مسجل';
        document.getElementById('modalDispItemsCount').innerText = (rx.items ? rx.items.length : 0) + ' أدوية';

        // شارة طوارئ في عنوان المودال
        const modalTitle = document.getElementById('modalDispRxNumber');
        if (modalTitle) {
            modalTitle.innerHTML = `${rx.prescription_number}${rx.is_emergency ? ' <span class="badge bg-danger ms-1">🚨 طوارئ STAT</span>' : ''}`;
        }

        // فحص إشعارات موافقة أو رفض الطبيب على البدائل
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
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-success fs-6">✅ وافق الطبيب المعالج على استبدال (${approvedItems.length}) دواء!</strong>
                                <span class="badge bg-success text-white">معتمد وجاهز للصرف</span>
                            </div>
                            <div class="small text-dark mt-1">
                                ${approvedItems.map(ai => `<span class="me-3"><i class="fas fa-pills text-success me-1"></i><strong>${ai.name}</strong> ${ai.substitution_response_notes ? `(${ai.substitution_response_notes})` : ''}</span>`).join('')}
                            </div>
                            <small class="text-muted d-block mt-1">تم تعديل الوصفة الطبية رسمياً، اضغط على <strong>«تأكيد صرف وتجهيز الدواء»</strong> أدناه لإتمام الصرف وخصم المخزون.</small>
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
                            <small class="text-muted d-block">تم إرسال المقترح إلى شاشة الطبيب. ستصلك الموافقة هنا تلقائياً فور اعتمادها من قبل الطبيب.</small>
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
                            <strong class="text-danger fs-6">❌ رفض الطبيب المعالج مقترح البديل:</strong>
                            <small class="text-dark d-block">${rejectedItems.map(ri => ri.substitution_response_notes || 'يرجى البحث عن الصنف الأصلي أو اقتراح بديل آخر').join(', ')}</small>
                        </div>
                    </div>`;
            } else {
                feedbackBanner.style.display = 'none';
            }
        }

        // رابط الطباعة (غير متاح لوصفات الطوارئ)
        const printBtn = document.getElementById('modalBtnPrintPrescription');
        if (rx.is_emergency) {
            printBtn.style.display = 'none';
        } else {
            printBtn.style.display = '';
            printBtn.href = `{{ url('doctor/visits') }}/${rx.patient_id}/prescription/print`;
        }

        // رسم جدول الأدوية
        const tbody = document.getElementById('modalDispensingItemsBody');
        let html = '';

        rx.items.forEach((item, index) => {
            const hasStock = item.is_in_stock;
            let stockBadge = '';

            if (item.substitution_status === 'pending_approval') {
                stockBadge = `
                    <div class="d-flex flex-column gap-1 align-items-center">
                        <span class="badge bg-warning text-dark border border-warning px-2 py-1 shadow-xs mb-1">
                            <i class="fas fa-hourglass-half fa-spin me-1"></i> بانتظار موافقة الطبيب
                        </span>
                        <small class="text-muted font-monospace mb-1">مقترح: <strong>${item.suggested_medicine_name || 'بديل'}</strong></small>
                        <button type="button" class="btn btn-xs btn-outline-warning text-dark fw-bold py-1 px-2 rounded shadow-xs" onclick="openAlternativesModal(${item.id})">
                            <i class="fas fa-exchange-alt me-1"></i> تغيير البديل المقترح
                        </button>
                    </div>`;
            } else if (item.substitution_status === 'approved') {
                stockBadge = `
                    <div class="d-flex flex-column gap-1 align-items-center">
                        <span class="badge bg-success text-white px-2 py-1 shadow-xs mb-1">
                            <i class="fas fa-check-circle me-1"></i> وافق الطبيب على البديل ✅
                        </span>
                        <small class="text-success font-monospace">معتمد للصرف الفوري</small>
                    </div>`;
            } else if (item.substitution_status === 'rejected') {
                stockBadge = `
                    <div class="d-flex flex-column gap-1 align-items-center">
                        <span class="badge bg-danger text-white px-2 py-1 shadow-xs mb-1">
                            <i class="fas fa-times-circle me-1"></i> رفض الطبيب البديل ❌
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-xs btn-outline-success py-1 px-2 rounded shadow-xs" onclick="toggleItemStock(${item.id})">
                                <i class="fas fa-check me-1"></i> توفر الأصلي
                            </button>
                            <button type="button" class="btn btn-xs btn-warning text-dark fw-bold py-1 px-2 rounded shadow-xs" onclick="openAlternativesModal(${item.id})">
                                <i class="fas fa-exchange-alt me-1"></i> اقتراح بديل آخر
                            </button>
                        </div>
                    </div>`;
            } else {
                stockBadge = `
                    <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                        <div class="btn-group btn-group-sm shadow-xs" role="group">
                            <button type="button" 
                                    class="btn ${hasStock ? 'btn-success fw-bold text-white shadow-xs' : 'btn-outline-success'}" 
                                    onclick="setItemAvailability(${item.id}, true)"
                                    title="تحديد كـ متوفر على الرف">
                                <i class="fas fa-check-circle me-1"></i> متوفر
                            </button>
                            <button type="button" 
                                    class="btn ${!hasStock ? 'btn-danger fw-bold text-white shadow-xs' : 'btn-outline-danger'}" 
                                    onclick="setItemAvailability(${item.id}, false)"
                                    title="تحديد كـ غير متوفر">
                                <i class="fas fa-times-circle me-1"></i> غير متوفر
                            </button>
                        </div>
                        <button type="button" 
                                class="btn btn-sm ${!hasStock ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary'} py-1 px-2 rounded-2 shadow-xs" 
                                onclick="openAlternativesModal(${item.id})" 
                                title="اقتراح بديل مكافئ لهذا الدواء">
                            <i class="fas fa-exchange-alt me-1"></i> اقتراح بديل
                        </button>
                    </div>`;
            }

            html += `
                <tr id="modalDispRow-${item.id}" class="${!hasStock && item.substitution_status !== 'approved' ? 'table-warning' : ''}">
                    <td class="text-center fw-bold text-muted">${index + 1}</td>
                    <td>
                        <div class="fw-bold text-dark fs-6" id="modalMedName-${item.id}">
                            ${item.name}
                            ${item.substitution_status === 'approved' ? '<span class="badge bg-success ms-1">بديل معتمد من الطبيب</span>' : ''}
                        </div>
                        <div class="small text-muted">
                            ${item.generic_name ? `<span class="badge bg-light text-secondary border me-1">${item.generic_name}</span>` : ''}
                            ${item.dosage_form ? `<span class="text-secondary">${item.dosage_form}</span>` : ''}
                            ${item.strength ? `<span class="text-secondary ms-1">(${item.strength})</span>` : ''}
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary fs-6 font-monospace px-3 py-1">
                            ${item.quantity} ${item.unit_type === 'sub_unit' ? (item.sub_unit || 'شريط') : (item.main_unit || 'علبة')}
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold text-primary">
                            <i class="fas fa-clock me-1"></i>${item.dosage_frequency || 'حسب إرشادات الطبيب'}
                        </div>
                        ${item.instructions ? `<div class="small text-muted"><i class="fas fa-info-circle me-1"></i>${item.instructions}</div>` : ''}
                        ${item.duration_days ? `<div class="small text-muted"><i class="far fa-calendar-alt me-1"></i>لمدة ${item.duration_days} يوم</div>` : ''}
                    </td>
                    <td class="text-center" id="modalStockCol-${item.id}">
                        ${stockBadge}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    // تعيين حالة توفر الدواء (متوفر / غير متوفر)
    function setItemAvailability(itemId, isAvailable) {
        if (!currentPrescriptionData) return;
        const item = currentPrescriptionData.items.find(i => i.id === itemId);
        if (!item) return;

        item.is_in_stock = isAvailable;
        if (item.substitution_status === 'rejected') {
            item.substitution_status = 'none';
        }
        renderModalPrescription(currentPrescriptionData);
    }

    // تبديل حالة توفر الدواء يدوياً من الشاشة
    function toggleItemStock(itemId) {
        if (!currentPrescriptionData) return;
        const item = currentPrescriptionData.items.find(i => i.id === itemId);
        if (!item) return;

        item.is_in_stock = !item.is_in_stock;
        if (item.substitution_status === 'rejected') {
            item.substitution_status = 'none';
        }
        renderModalPrescription(currentPrescriptionData);
    }

    // فتح نافذة اقتراح البديل للدواء المحدد
    function openAlternativesModal(itemId) {
        targetItemForAlternative = itemId;
        if (!currentPrescriptionData) return;

        const item = currentPrescriptionData.items.find(i => i.id === itemId);
        if (!item) return;

        document.getElementById('modalTargetMedName').innerText = `${item.name} (${item.dosage_form || ''} - ${item.strength || ''})`;
        document.getElementById('modalAltReasonInput').value = '';
        const searchInp = document.getElementById('modalAltSearchInput');
        searchInp.value = '';

        // لا نظهر أي مقترحات تلقائياً - المودال يبدأ فارغاً للبحث فقط
        renderAlternativesList([]);

        const altModalEl = document.getElementById('alternativeModal');
        const altModal = new bootstrap.Modal(altModalEl);
        altModal.show();

        altModalEl.addEventListener('shown.bs.modal', function () {
            searchInp.focus();
        }, { once: true });
    }

    // رسم قائمة البدائل داخل النافذة (اختيار يدوي للموظف)
    function renderAlternativesList(alternatives) {
        const listContainer = document.getElementById('alternativesModalList');
        document.getElementById('modalAltCountBadge').innerText = `${alternatives.length} بديل`;

        if (alternatives.length === 0) {
            listContainer.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-search fa-2x mb-2 text-warning opacity-75"></i>
                    <p class="small mb-1 fw-bold">اكتب اسم الدواء البديل في حقل البحث أعلاه</p>
                    <small class="text-secondary">يمكنك اختيار أي دواء من الدليل لاقتراحه على الطبيب المعالج.</small>
                </div>`;
            return;
        }

        let html = '';
        alternatives.forEach(alt => {
            html += `
                <div class="list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center gap-3">
                    <div>
                        <div class="fw-bold text-dark fs-6">
                            <i class="fas fa-pills text-primary me-1"></i>${alt.name}
                        </div>
                        <div class="small text-muted mt-1">
                            ${alt.dosage_form ? `<span class="badge bg-light text-secondary border me-1">${alt.dosage_form}</span>` : ''}
                            ${alt.strength ? `<span class="badge bg-light text-secondary border me-1">${alt.strength}</span>` : ''}
                            ${alt.sale_price ? `<span class="badge bg-success-subtle text-success border border-success">${Number(alt.sale_price).toLocaleString()} د.ع</span>` : ''}
                        </div>
                    </div>
                    <button type="button" class="btn btn-warning btn-sm text-dark fw-bold px-3 py-2 rounded-3 shadow-xs d-flex align-items-center gap-1" onclick="submitAlternativeSuggestion(${alt.id})">
                        <i class="fas fa-check-circle"></i> اختيار هذا البديل
                    </button>
                </div>`;
        });

        listContainer.innerHTML = html;
    }

    // إرسال اقتراح البديل إلى الطبيب
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
                bootstrap.Modal.getInstance(document.getElementById('alternativeModal')).hide();
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

    // تنفيذ عملية الصرف من داخل المودال
    function executeDispenseFromModal() {
        if (!currentPrescriptionData) return;

        const pendingSubs = currentPrescriptionData.items.filter(i => i.substitution_status === 'pending_approval');
        if (pendingSubs.length > 0) {
            if (!confirm(`⚠️ تنبيه: توجد (${pendingSubs.length}) أدوية ما زالت بانتظار موافقة الطبيب على البديل.\nهل ترغب في متابعة الصرف الجزئي للأدوية المتوفرة وتخطي الأصناف المعلقة؟`)) {
                return;
            }
        }

        const btn = document.getElementById('modalBtnExecuteDispense');
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i> جاري الصرف وخصم المخزون...`;

        const itemsPayload = currentPrescriptionData.items.map(item => {
            return {
                id: item.id,
                medicine_id: item.medicine_id,
                quantity: item.quantity,
                is_available: (item.is_in_stock || item.substitution_status === 'approved') && item.substitution_status !== 'pending_approval'
            };
        });

        fetch(`{{ url('pharmacy/pos/prescriptions') }}/${currentPrescriptionData.id}/dispense`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                items: itemsPayload
            })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-check-circle fa-lg"></i> <span>تأكيد صرف وتجهيز الدواء</span>`;

            if (data.success) {
                try {
                    document.getElementById('dispenseAudio').play();
                } catch(e) {}

                bootstrap.Modal.getInstance(document.getElementById('dispensingModal')).hide();
                alert(`✅ ${data.message}`);
                refreshPrescriptionsQueue(true);
            } else {
                alert(data.message || 'حدث خطأ أثناء صرف الوصفة.');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = `<i class="fas fa-check-circle fa-lg"></i> <span>تأكيد صرف وتجهيز الدواء</span>`;
            console.error('Error dispensing prescription:', err);
            alert('حدث خطأ في الاتصال أثناء الصرف.');
        });
    }

    // رسم نتائج الفحص السريع للأدوية
    function renderQuickSearchResults(medicines) {
        const container = document.getElementById('quickSearchResultsList');
        if (medicines.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-exclamation-circle fa-2x mb-2 opacity-50"></i>
                    <p class="small mb-0">لم يتم العثور على أدوية مطابقة لبحثك</p>
                </div>`;
            return;
        }

        let html = '';
        medicines.forEach(med => {
            const hasStock = med.total_stock > 0 || med.total_open_sub_units > 0;
            html += `
                <div class="list-group-item list-group-item-action p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong class="text-dark fs-6">${med.name}</strong>
                        <span class="badge ${hasStock ? 'bg-success' : 'bg-danger'} font-monospace">
                            ${hasStock ? `متوفر (${med.total_stock} ${med.main_unit || 'علبة'})` : 'رصيد نافد'}
                        </span>
                    </div>
                    <div class="small text-muted d-flex justify-content-between">
                        <span>المادة الفعالة: ${med.generic_name || '-'} | الشكل: ${med.dosage_form || '-'} (${med.strength || ''})</span>
                        <span class="fw-bold text-primary font-monospace">${Number(med.sale_price).toLocaleString()} د.ع</span>
                    </div>
                </div>`;
        });

        container.innerHTML = html;
    }

    // دالة مساعدة للـ Debounce
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
</script>
@endpush
