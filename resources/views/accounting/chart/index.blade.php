@extends('accounting.layout')

@section('title', 'دليل الحسابات وشجرة النظام المحاسبي الموحد')

@push('styles')
<style>
    .coa-card {
        transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
        cursor: pointer;
    }
    .coa-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 22px -8px rgba(15, 23, 42, 0.14) !important;
        border-color: #3b82f6 !important;
    }
    .coa-card.is-selected {
        border-color: #2563eb !important;
        background-color: #eff6ff !important;
    }
    .coa-breadcrumb-item {
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .coa-breadcrumb-item:hover {
        background-color: #dbeafe !important;
        color: #1d4ed8 !important;
    }

    /* Slide-over Details Drawer (rendered outside .main-content in modals stack) */
    .coa-drawer-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.35);
        z-index: 1052;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
    }
    .coa-drawer-backdrop.show {
        opacity: 1;
        pointer-events: auto;
    }
    .coa-drawer {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: min(460px, 94vw);
        background: #ffffff;
        z-index: 1053;
        box-shadow: 8px 0 32px rgba(15, 23, 42, 0.16);
        transform: translateX(-100%);
        transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
    }
    .coa-drawer.show {
        transform: translateX(0);
    }

    /* Fullscreen Flowchart Overlay (rendered directly under <body> in modals stack) */
    .coa-fullscreen-overlay {
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        height: 100dvh;
        z-index: 1050;
        background: #f8fafc;
        display: none;
        flex-direction: column;
        overflow: hidden;
    }
    .coa-fullscreen-overlay.show {
        display: flex;
    }
    .coa-canvas-viewport {
        position: relative;
        flex: 1;
        min-height: 0;
        overflow: hidden;
        cursor: grab;
        touch-action: none;
        background-color: #f8fafc;
        background-image: radial-gradient(#cbd5e1 1.25px, transparent 1.25px);
        background-size: 22px 22px;
        user-select: none;
        -webkit-user-select: none;
    }
    .coa-canvas-viewport.is-panning {
        cursor: grabbing;
    }
    .coa-canvas-stage {
        position: absolute;
        top: 0;
        left: 0;
        transform-origin: 0 0;
        will-change: transform;
    }
    .coa-svg-layer {
        position: absolute;
        top: 0;
        left: 0;
        overflow: visible;
        pointer-events: none;
    }
    .coa-flow-node {
        position: absolute;
        width: 225px;
        min-height: 96px;
        border-radius: 14px;
        background: #ffffff;
        border: 2px solid #cbd5e1;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
        padding: 10px 12px;
        cursor: pointer;
        transition: border-color 0.16s ease, box-shadow 0.16s ease, transform 0.16s ease;
        direction: rtl;
        text-align: right;
    }
    .coa-flow-node:hover {
        border-color: #3b82f6;
        box-shadow: 0 10px 24px rgba(37, 99, 235, 0.15);
        transform: translateY(-2px);
    }
    .coa-flow-node.node-root {
        background: linear-gradient(135deg, #ede9fe 0%, #f5f3ff 100%);
        border-color: #8b5cf6;
    }
    .coa-flow-node.node-origin {
        background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
        border-color: #3b82f6;
    }
    .coa-flow-node.node-postable {
        background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
        border-color: #10b981;
    }
    .coa-flow-node.node-active-chain {
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2), 0 12px 26px rgba(37, 99, 235, 0.16);
    }
    .coa-flow-node.node-expanded {
        border-width: 2.5px;
    }
    .coa-flow-connector {
        fill: none;
        stroke: #94a3b8;
        stroke-width: 2.2;
        transition: stroke 0.18s ease, stroke-width 0.18s ease;
    }
    .coa-flow-connector.is-active {
        stroke: #2563eb;
        stroke-width: 3;
    }
    .coa-node-action-btn {
        width: 24px;
        height: 24px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 11px;
    }
    .coa-floating-controls {
        position: absolute;
        bottom: max(16px, env(safe-area-inset-bottom));
        left: 50%;
        transform: translateX(-50%);
        z-index: 30;
        max-width: 96vw;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(10px);
        border: 1px solid #cbd5e1;
        border-radius: 24px;
        padding: 6px 12px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18);
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        gap: 6px;
        direction: rtl;
    }
</style>
@endpush

@section('accounting_content')
<!-- Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 fs-3">
                <i class="fas fa-sitemap"></i>
            </div>
            <div>
                <span class="text-muted small d-block">إجمالي حسابات الدليل</span>
                <span class="fs-4 fw-bold text-dark">{{ number_format($stats['total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-success bg-opacity-10 text-success p-3 rounded-4 fs-3">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <span class="text-muted small d-block">فروع نهائية تقبل القيود</span>
                <span class="fs-4 fw-bold text-success">{{ number_format($stats['postable']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-secondary bg-opacity-10 text-secondary p-3 rounded-4 fs-3">
                <i class="fas fa-folder-tree"></i>
            </div>
            <div>
                <span class="text-muted small d-block">حسابات أصل تجميعية</span>
                <span class="fs-4 fw-bold text-secondary">{{ number_format($stats['headers']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-info bg-opacity-10 text-info p-3 rounded-4 fs-3">
                    <i class="fas fa-coins"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">العملة الأساسية والسعر</span>
                    <span class="fs-5 fw-bold text-dark">
                        {{ $defaultCurrency?->name ?? 'دينار عراقي' }}
                        @if($foreignCurrency)
                            (1{{ $foreignCurrency->symbol }}) = {{ rtrim(rtrim(number_format((float) $foreignCurrency->exchange_rate, 2), '0'), '.') }}
                        @endif
                    </span>
                </div>
            </div>
            <a href="{{ route('accounting.settings.index') }}" class="btn btn-sm btn-outline-info rounded-3" title="تعديل العملة وسعر الصرف">
                <i class="fas fa-cog"></i>
            </a>
        </div>
    </div>
</div>

<!-- Full-Width Interactive Drill-Down Workspace (Top-to-Bottom Flow) -->
<div class="bg-white rounded-4 shadow-sm border border-slate-200 p-3 p-md-4">
    <!-- Top Controls Row -->
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-3 pb-3 border-bottom">
        <div class="input-group" style="max-width: 440px;">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-search"></i></span>
            <input type="text" id="treeSearchInput" value="{{ $search ?? '' }}" class="form-control bg-light border-start-0 border-end-0" placeholder="ابحث برقم الدليل أو اسم الحساب (مثل: أراضي المشاتل)...">
            <button type="button" class="btn bg-light border border-start-0 text-muted d-none" id="btnClearSearch" title="مسح البحث">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 px-3 d-none" id="btnDrillUp">
                <i class="fas fa-arrow-up me-1"></i> رجوع للأصل الأعلى
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 px-3 d-none" id="btnResetRoot">
                <i class="fas fa-home me-1"></i> الأصول الرئيسية
            </button>
            <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 shadow-sm d-flex align-items-center gap-2" id="btnOpenFullscreen">
                <i class="fas fa-project-diagram"></i>
                <span>وضع النافذة الكاملة (المخطط الشجري)</span>
            </button>
        </div>
    </div>

    <!-- Legend Bar -->
    <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between bg-light p-2 px-3 rounded-3 mb-3 border text-muted small">
        <div class="d-flex flex-wrap gap-3 align-items-center">
            <span class="d-flex align-items-center gap-1">
                <span class="badge bg-primary rounded-pill"><i class="fas fa-folder"></i> أصل</span>
                <span>حساب أصل تجميعي (يحتوي على فروع أو مستوى 1–4 ولا يقبل القيود المباشرة)</span>
            </span>
            <span class="d-flex align-items-center gap-1">
                <span class="badge bg-success rounded-pill"><i class="fas fa-check"></i> فرع</span>
                <span>فرع تحليلي نهائي (يقبل القيود — وبمجرد إضافة فرع تحته يتحول لأصل ويُغلق عن القيود)</span>
            </span>
        </div>
        <span class="text-primary fw-semibold d-none d-md-inline">
            <i class="fas fa-arrow-down me-1"></i> اضغط على أي حساب للانتقال إلى الفروع التي تحته
        </span>
    </div>

    <!-- Breadcrumb Path Bar (Top-to-Bottom hierarchy indicator) -->
    <div class="d-flex flex-wrap align-items-center gap-2 p-2 px-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 mb-3" id="drillBreadcrumb">
        <!-- Populated dynamically by JS -->
    </div>

    <!-- Current Focused Origin Header Banner (shown when inside a node) -->
    <div id="focusedOriginBanner" class="d-none">
        <div class="rounded-4 border border-primary border-opacity-25 bg-white shadow-sm p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <span class="badge bg-primary fs-6 font-monospace px-3 py-1 rounded-pill" id="focusedOriginCode">-</span>
                        <span class="badge bg-secondary-subtle text-secondary border" id="focusedOriginLevel">-</span>
                        <span class="badge" id="focusedOriginStatusBadge">-</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-0" id="focusedOriginName">-</h5>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3" id="btnFocusedDetails">
                        <i class="fas fa-eye me-1"></i> تفاصيل ورصيد الأصل
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-dark rounded-3" id="btnFocusedFullscreen">
                        <i class="fas fa-sitemap me-1"></i> عرض المسار في المخطط الكامل
                    </button>
                    <button type="button" class="btn btn-sm btn-success rounded-3" id="btnFocusedAddBranch">
                        <i class="fas fa-plus-circle me-1"></i> + إضافة فرع
                    </button>
                </div>
            </div>
        </div>
        <div class="text-center my-2">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1">
                <i class="fas fa-arrow-down me-1"></i> الفروع المتفرعة للأسفل
            </span>
        </div>
    </div>

    <!-- Drill-Down Cards Grid (Current Level Only) -->
    <div id="drillGrid" class="row g-3">
        <!-- Populated dynamically by JS -->
    </div>

    <!-- Empty State -->
    <div id="drillEmptyState" class="d-none text-center py-5 text-muted">
        <i class="fas fa-folder-open fs-1 opacity-50 mb-3 d-block"></i>
        <h6 class="fw-bold text-dark mb-1" id="drillEmptyTitle">لا توجد نتائج مطابقة</h6>
        <p class="small mb-0" id="drillEmptySubtitle">جرّب البحث بكود أو كلمة أخرى.</p>
    </div>
</div>
@endsection

@push('modals')
<!-- Slide-Over Details Drawer -->
<div class="coa-drawer-backdrop" id="detailsDrawerBackdrop"></div>
<aside class="coa-drawer" id="detailsDrawer" dir="rtl" aria-hidden="true">
    <div class="p-3 p-md-4 border-bottom d-flex justify-content-between align-items-center bg-light">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary fs-6 font-monospace px-3 py-1 rounded-pill" id="detailCode">-</span>
            <span class="badge" id="detailPostableBadge">-</span>
        </div>
        <button type="button" class="btn-close" id="btnCloseDrawer" aria-label="إغلاق"></button>
    </div>

    <div class="p-3 p-md-4 overflow-auto flex-grow-1">
        <h5 class="fw-bold text-dark mb-2" id="detailName">اسم الحساب</h5>
        <div class="small text-muted mb-3" id="detailChainText"></div>

        <!-- Action Buttons inside Drawer -->
        <div class="d-flex flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-success rounded-3 d-flex align-items-center gap-1 shadow-sm" id="btnAddSubAccount">
                <i class="fas fa-plus-circle"></i>
                <span>+ إضافة فرع</span>
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary rounded-3 d-flex align-items-center gap-1" id="btnShowInFullscreenFromDrawer">
                <i class="fas fa-project-diagram"></i>
                <span>عرض في المخطط الكامل</span>
            </button>
        </div>

        <div id="detailBranchBlockAlert" class="alert alert-warning py-2 px-3 rounded-3 small d-none mb-3">
            <i class="fas fa-lock me-1"></i>
            <span id="detailBranchBlockText"></span>
        </div>

        <div class="bg-light p-3 rounded-3 mb-3 border">
            <div class="row g-2 small">
                <div class="col-6">
                    <span class="text-muted d-block">رتبة المستوى:</span>
                    <span class="fw-semibold text-dark" id="detailLevel">-</span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">طبيعة الحساب:</span>
                    <span class="fw-semibold text-dark" id="detailNature">-</span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">النوع / التبويب:</span>
                    <span class="fw-semibold text-dark" id="detailType">-</span>
                </div>
                <div class="col-6">
                    <span class="text-muted d-block">الحساب الأصل:</span>
                    <span class="fw-semibold text-dark text-truncate d-block" id="detailParent">-</span>
                </div>
            </div>
        </div>

        <!-- Instant Balance Box -->
        <div class="bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 p-3 mb-3 text-center">
            <span class="text-muted small d-block">الرصيد الفعلي اللحظي للحساب</span>
            <h3 class="fw-bold text-primary mb-0" id="detailBalance">0.00 د.ع</h3>
        </div>

        <!-- Recent Transactions -->
        <h6 class="fw-bold text-dark mb-2">
            <i class="fas fa-history me-1 text-muted"></i> آخر الحركات المحاسبية المسجلة:
        </h6>
        <div class="table-responsive border rounded-3 overflow-hidden">
            <table class="table table-sm table-hover mb-0 small text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th>رقم القيد</th>
                        <th>التاريخ</th>
                        <th>البيان</th>
                        <th>مدين</th>
                        <th>دائن</th>
                    </tr>
                </thead>
                <tbody id="detailTransactionsTable">
                    <tr>
                        <td colspan="5" class="text-muted py-3">لا توجد حركات مسجلة على هذا الحساب حالياً</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</aside>

<!-- Fullscreen Flowchart Mode Overlay (Top-to-Bottom Vertical Tree) -->
<div class="coa-fullscreen-overlay" id="fullscreenOverlay" dir="rtl" aria-hidden="true">
    <!-- Fullscreen Top Bar -->
    <div class="bg-white border-bottom px-3 py-2 shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-3 fw-bold">
                <i class="fas fa-project-diagram me-1"></i> المخطط الشجري التفاعلي (من الأعلى للأسفل)
            </span>

            <!-- Root Switcher (Shows 1 root at a time so canvas never gets cluttered) -->
            <div class="btn-group btn-group-sm" id="fsRootSwitcher" role="group" aria-label="الأصول الرئيسية"></div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Quick Jump Search in Fullscreen -->
            <div class="position-relative" style="width: 230px;">
                <input type="text" id="fsJumpSearch" class="form-control form-control-sm bg-light" placeholder="انتقال سريع لحساب بالمسار...">
                <div id="fsJumpResults" class="list-group position-absolute start-0 end-0 shadow-lg d-none" style="top: 100%; z-index: 1054; max-height: 280px; overflow-y: auto;"></div>
            </div>

            <!-- Single-Focus vs Free-Expand Toggle (Default: Single Focus) -->
            <button type="button" class="btn btn-sm btn-primary rounded-3 fw-semibold" id="btnToggleFocusMode" title="التبديل بين التركيز الأحادي والتوسيع الحر">
                <i class="fas fa-bullseye me-1"></i>
                <span id="focusModeLabel">النمط: التركيز الأحادي</span>
            </button>

            <button type="button" class="btn btn-sm btn-danger rounded-3 px-3" id="btnCloseFullscreen">
                <i class="fas fa-times me-1"></i> إغلاق
            </button>
        </div>
    </div>

    <!-- Active Chain Ribbon -->
    <div class="bg-light border-bottom px-3 py-1 small d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex flex-wrap align-items-center gap-1" id="fsActiveChainRibbon">
            <span class="text-muted">اضغط على أي مربع لفتح فروعه المرتبطة فقط</span>
        </div>
        <div class="d-flex align-items-center gap-3 text-muted">
            <span><span class="d-inline-block rounded-circle bg-primary me-1" style="width:8px;height:8px;"></span> أصل (له فروع)</span>
            <span><span class="d-inline-block rounded-circle bg-success me-1" style="width:8px;height:8px;"></span> فرع نهائي (يقبل القيود)</span>
            <span class="d-none d-lg-inline"><i class="fas fa-hand-paper me-1"></i> اسحب من أي مكان لتحريك الشجرة</span>
        </div>
    </div>

    <!-- Interactive Flowchart Canvas -->
    <div class="coa-canvas-viewport" id="fsCanvasViewport">
        <div class="coa-canvas-stage" id="fsCanvasStage">
            <svg class="coa-svg-layer" id="fsSvgLayer">
                <defs>
                    <marker id="arrowDefault" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
                        <path d="M 0 0 L 8 4 L 0 8 z" fill="#94a3b8"></path>
                    </marker>
                    <marker id="arrowActive" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
                        <path d="M 0 0 L 8 4 L 0 8 z" fill="#2563eb"></path>
                    </marker>
                </defs>
            </svg>
            <div id="fsNodesLayer"></div>
        </div>

        <!-- Always-Visible Floating Zoom, Pan, Center & Close Controls (iPad & Trackpad friendly) -->
        <div class="coa-floating-controls" id="fsFloatingControls">
            <div class="btn-group btn-group-sm" role="group" aria-label="تحريك الشاشة">
                <button type="button" class="btn btn-light border rounded-end-pill px-2" id="btnFsPanRight" title="تحريك لليمين">
                    <i class="fas fa-chevron-right text-secondary"></i>
                </button>
                <button type="button" class="btn btn-light border px-2" id="btnFsPanUp" title="تحريك للأعلى">
                    <i class="fas fa-chevron-up text-secondary"></i>
                </button>
                <button type="button" class="btn btn-light border px-2" id="btnFsPanDown" title="تحريك للأسفل">
                    <i class="fas fa-chevron-down text-secondary"></i>
                </button>
                <button type="button" class="btn btn-light border rounded-start-pill px-2" id="btnFsPanLeft" title="تحريك لليسار">
                    <i class="fas fa-chevron-left text-secondary"></i>
                </button>
            </div>
            <div class="vr mx-1 d-none d-sm-block"></div>
            <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold" id="btnFsZoomIn" title="تكبير">
                <i class="fas fa-search-plus text-primary me-1"></i> تكبير
            </button>
            <span class="badge bg-light text-dark border rounded-pill px-2 py-1 font-monospace" id="fsZoomPercent">100%</span>
            <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 fw-bold" id="btnFsZoomOut" title="تصغير">
                <i class="fas fa-search-minus text-primary me-1"></i> تصغير
            </button>
            <div class="vr mx-1 d-none d-sm-block"></div>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" id="btnFsZoomReset" title="توسيط الشجرة">
                <i class="fas fa-compress-arrows-alt me-1"></i> توسيط
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" id="btnFsFloatingClose" title="إغلاق النافذة الكاملة">
                <i class="fas fa-times me-1"></i> إغلاق
            </button>
        </div>
    </div>
</div>

<!-- Modal: Add Branch Account -->
<div class="modal fade" id="subAccountModal" tabindex="-1" aria-labelledby="subAccountModalLabel" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('accounting.chart.store') }}" method="POST">
                @csrf
                <input type="hidden" name="parent_id" id="modalParentId">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="subAccountModalLabel">
                        <i class="fas fa-plus-circle text-success me-1"></i> إضافة فرع جديد
                    </h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body py-3">
                    <!-- Origin Details Notice -->
                    <div class="alert alert-light border rounded-3 p-2 mb-3 small d-flex align-items-center gap-2">
                        <i class="fas fa-folder-open text-primary fs-5"></i>
                        <div>
                            <span class="text-muted">الحساب الأصل:</span>
                            <span class="fw-bold text-dark" id="modalParentLabel">-</span>
                        </div>
                    </div>

                    <!-- Suggested Code Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">كود / رقم الفرع الجديد:</label>
                        <div class="input-group">
                            <input type="text" name="code" id="modalAccountCode" class="form-control text-start font-monospace fw-bold" required>
                            <button type="button" class="btn btn-outline-secondary" id="btnAutoSuggest" title="اقتراح تلقائي للكود التالي">
                                <i class="fas fa-magic me-1"></i> اقتراح كود
                            </button>
                        </div>
                        <div class="form-text small" id="modalCodeNotice">سيتم فتح الفرع وفق تدرج الدليل الموحد.</div>
                    </div>

                    <!-- Account Name Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">اسم الفرع الجديد (عربي):</label>
                        <input type="text" name="name" id="modalAccountName" class="form-control" placeholder="مثلاً: صندوق كاشير الطوارئ المسائي" required>
                    </div>

                    <div class="alert alert-info py-2 px-3 rounded-3 small border-0 mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        <span>تنبيه محاسبي: بمجرد إضافة فرع، يتحوّل <strong>الحساب الأصل</strong> تلقائياً إلى حساب تجميعي لا يقبل القيود المباشرة، ويصبح الفرع الجديد (من المستوى 5 فأعلى) قابلاً للقيود ما لم يتفرع.</span>
                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 shadow-sm">حفظ الفرع في الدليل</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
    const ALL_ACCOUNTS = @json($treeAccounts);
    const DETAILS_URL_TEMPLATE = "{{ route('accounting.chart.details', ':id') }}";
    const SUGGEST_CODE_URL = "{{ route('accounting.chart.suggest-code') }}";

    // Build fast in-memory indexes
    const accountsById = new Map();
    const childrenByParent = new Map();
    const rootAccounts = [];

    ALL_ACCOUNTS.forEach(acc => {
        accountsById.set(acc.id, acc);
        const pid = acc.parent_id ?? 'ROOT';
        if (!childrenByParent.has(pid)) {
            childrenByParent.set(pid, []);
        }
        childrenByParent.get(pid).push(acc);
        if (acc.parent_id === null) {
            rootAccounts.push(acc);
        }
    });

    function getAncestorChain(accountId) {
        const chain = [];
        let curr = accountsById.get(accountId);
        while (curr) {
            chain.unshift(curr);
            curr = curr.parent_id ? accountsById.get(curr.parent_id) : null;
        }
        return chain;
    }

    // Normal Mode State
    let focusedParentId = null;
    let currentSelectedAccountId = null;

    // Fullscreen Flowchart State
    let fsActiveRootId = rootAccounts.length > 0 ? rootAccounts[0].id : null;
    let fsSingleFocusMode = true; // Default: التركيز الأحادي
    let fsExpandedIds = new Set();
    let fsActiveNodeId = fsActiveRootId;
    let fsScale = 1;
    let fsPanX = 40;
    let fsPanY = 40;
    let fsPointerDown = false;
    let fsDidDrag = false;
    let fsStartClientX = 0;
    let fsStartClientY = 0;
    let fsStartPanX = 0;
    let fsStartPanY = 0;
    let fsLastNodePositions = new Map();

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getAccountBadgeInfo(acc) {
        if (acc.children_count > 0) {
            return {
                cls: 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                text: `أصل (${acc.children_count} فرع) — مغلق عن القيود`
            };
        }
        if (acc.is_postable) {
            return {
                cls: 'bg-success bg-opacity-10 text-success border border-success border-opacity-25',
                text: 'فرع نهائي — يقبل القيود'
            };
        }
        return {
            cls: 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
            text: `مستوى ${acc.level} تجميعي (لا يقبل القيود)`
        };
    }

    // ==========================================
    // 1. NORMAL MODE: DRILL-DOWN TREE RENDERING
    // ==========================================
    function renderNormalView() {
        const searchInput = document.getElementById('treeSearchInput');
        const term = searchInput.value.trim().toLowerCase();
        const clearBtn = document.getElementById('btnClearSearch');
        clearBtn.classList.toggle('d-none', !term);

        renderBreadcrumb(term);
        renderFocusedBanner(term);

        const grid = document.getElementById('drillGrid');
        const emptyState = document.getElementById('drillEmptyState');
        grid.innerHTML = '';

        let itemsToShow = [];
        if (term) {
            itemsToShow = ALL_ACCOUNTS.filter(acc =>
                acc.code.toLowerCase().includes(term) || acc.name.toLowerCase().includes(term)
            );
        } else {
            itemsToShow = childrenByParent.get(focusedParentId ?? 'ROOT') || [];
        }

        if (itemsToShow.length === 0) {
            emptyState.classList.remove('d-none');
            document.getElementById('drillEmptyTitle').textContent = term
                ? 'لم يتم العثور على حسابات مطابقة'
                : 'لا توجد فروع مضافة تحت هذا الحساب بعد';
            document.getElementById('drillEmptySubtitle').textContent = term
                ? 'جرّب البحث برقم الدليل أو جزء آخر من الاسم.'
                : 'يمكنك إضافة فرع جديد تحته إذا لم تكن عليه قيود مسجلة.';
            return;
        }
        emptyState.classList.add('d-none');

        const displayItems = term ? itemsToShow.slice(0, 60) : itemsToShow;

        displayItems.forEach(acc => {
            const col = document.createElement('div');
            col.className = 'col-12 col-md-6 col-xl-4';

            const badge = getAccountBadgeInfo(acc);
            const hasChildren = acc.children_count > 0;
            const chainText = term
                ? getAncestorChain(acc.id).slice(0, -1).map(a => a.name).join(' ⬇ ')
                : '';

            const isBlockedFromBranch = acc.lines_count > 0;

            col.innerHTML = `
                <div class="card h-100 rounded-4 border shadow-sm coa-card ${currentSelectedAccountId === acc.id ? 'is-selected' : ''}" data-account-id="${acc.id}">
                    <div class="card-body d-flex flex-column justify-content-between p-3">
                        <div>
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <span class="badge ${hasChildren ? 'bg-primary' : (acc.is_postable ? 'bg-success' : 'bg-secondary')} font-monospace px-2 py-1 fs-6">
                                    ${escapeHtml(acc.code)}
                                </span>
                                <span class="badge bg-light text-muted border small">مستوى ${acc.level}</span>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">${escapeHtml(acc.name)}</h6>
                            ${chainText ? `<div class="small text-muted mb-2 text-truncate" title="${escapeHtml(chainText)}">${escapeHtml(chainText)}</div>` : ''}
                            <div class="mt-2">
                                <span class="badge ${badge.cls} small">${badge.text}</span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <span class="small fw-semibold ${hasChildren ? 'text-primary' : 'text-muted'}">
                                ${hasChildren ? `<i class="fas fa-arrow-down me-1"></i> اضغط لفتح الفروع (${acc.children_count})` : '<i class="fas fa-eye me-1"></i> اضغط لعرض التفاصيل'}
                            </span>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-3 py-1 px-2" title="التفاصيل والرصيد" onclick="event.stopPropagation(); selectAccount(${acc.id})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-dark rounded-3 py-1 px-2" title="عرض مساره في المخطط الكامل" onclick="event.stopPropagation(); openFullscreenAt(${acc.id})">
                                    <i class="fas fa-project-diagram"></i>
                                </button>
                                ${!isBlockedFromBranch ? `
                                <button type="button" class="btn btn-sm btn-outline-success rounded-3 py-1 px-2" title="إضافة فرع جديد" onclick="event.stopPropagation(); openAddBranchModal(${acc.id})">
                                    <i class="fas fa-plus"></i>
                                </button>` : `
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-3 py-1 px-2" disabled title="مغلق عن التفريع لوجود قيود مسجلة">
                                    <i class="fas fa-lock"></i>
                                </button>`}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const cardEl = col.querySelector('.coa-card');
            cardEl.addEventListener('click', () => {
                if (hasChildren) {
                    drillInto(acc.id);
                } else {
                    selectAccount(acc.id);
                }
            });

            grid.appendChild(col);
        });

        if (term && itemsToShow.length > 60) {
            const notice = document.createElement('div');
            notice.className = 'col-12';
            notice.innerHTML = `<div class="alert alert-light border text-center small mb-0">تم عرض أول 60 نتيجة من أصل ${itemsToShow.length}. اكتب كلمة أو كود أدق لتضييق النتائج.</div>`;
            grid.appendChild(notice);
        }
    }

    function drillInto(accountId) {
        const searchInput = document.getElementById('treeSearchInput');
        if (searchInput.value.trim() !== '') {
            searchInput.value = '';
        }
        focusedParentId = accountId;
        renderNormalView();
    }

    function renderBreadcrumb(searchTerm) {
        const bc = document.getElementById('drillBreadcrumb');
        const btnUp = document.getElementById('btnDrillUp');
        const btnReset = document.getElementById('btnResetRoot');

        if (searchTerm) {
            bc.innerHTML = `<span class="fw-semibold text-primary"><i class="fas fa-search me-1"></i> نتائج البحث الفوري عن: "${escapeHtml(searchTerm)}"</span>`;
            btnUp.classList.add('d-none');
            btnReset.classList.remove('d-none');
            return;
        }

        const hasFocus = focusedParentId !== null;
        btnUp.classList.toggle('d-none', !hasFocus);
        btnReset.classList.toggle('d-none', !hasFocus);

        let html = `
            <span class="badge rounded-pill px-3 py-2 coa-breadcrumb-item ${!hasFocus ? 'bg-primary text-white' : 'bg-white text-dark border'}" onclick="drillInto(null)">
                <i class="fas fa-home me-1"></i> الأصول الرئيسية (4)
            </span>
        `;

        if (hasFocus) {
            const chain = getAncestorChain(focusedParentId);
            chain.forEach((node, idx) => {
                const isLast = idx === chain.length - 1;
                html += `<i class="fas fa-arrow-down text-muted small"></i>`;
                html += `
                    <span class="badge rounded-pill px-3 py-2 coa-breadcrumb-item ${isLast ? 'bg-primary text-white' : 'bg-white text-dark border'}" onclick="drillInto(${node.id})">
                        <span class="font-monospace me-1">${escapeHtml(node.code)}</span> ${escapeHtml(node.name)}
                    </span>
                `;
            });
        }

        bc.innerHTML = html;
    }

    function renderFocusedBanner(searchTerm) {
        const banner = document.getElementById('focusedOriginBanner');
        if (searchTerm || focusedParentId === null) {
            banner.classList.add('d-none');
            return;
        }
        const acc = accountsById.get(focusedParentId);
        if (!acc) {
            banner.classList.add('d-none');
            return;
        }
        banner.classList.remove('d-none');

        const badge = getAccountBadgeInfo(acc);
        document.getElementById('focusedOriginCode').textContent = acc.code;
        document.getElementById('focusedOriginLevel').textContent = 'المستوى ' + acc.level;
        document.getElementById('focusedOriginName').textContent = acc.name;
        const statusBadge = document.getElementById('focusedOriginStatusBadge');
        statusBadge.className = 'badge ' + badge.cls;
        statusBadge.textContent = badge.text;

        const addBtn = document.getElementById('btnFocusedAddBranch');
        addBtn.disabled = acc.lines_count > 0;
        addBtn.title = acc.lines_count > 0 ? 'لا يمكن إضافة فرع لوجود قيود مسجلة' : 'إضافة فرع جديد تحت هذا الأصل';

        document.getElementById('btnFocusedDetails').onclick = () => selectAccount(acc.id);
        document.getElementById('btnFocusedFullscreen').onclick = () => openFullscreenAt(acc.id);
        addBtn.onclick = () => openAddBranchModal(acc.id);
    }

    // ==========================================
    // 2. DETAILS DRAWER & ADD BRANCH MODAL
    // ==========================================
    function openDrawer() {
        document.getElementById('detailsDrawerBackdrop').classList.add('show');
        document.getElementById('detailsDrawer').classList.add('show');
    }

    function closeDrawer() {
        document.getElementById('detailsDrawerBackdrop').classList.remove('show');
        document.getElementById('detailsDrawer').classList.remove('show');
    }

    document.getElementById('btnCloseDrawer').addEventListener('click', closeDrawer);
    document.getElementById('detailsDrawerBackdrop').addEventListener('click', closeDrawer);

    function selectAccount(accountId) {
        currentSelectedAccountId = accountId;

        document.querySelectorAll('.coa-card').forEach(el => {
            el.classList.toggle('is-selected', Number(el.dataset.accountId) === accountId);
        });

        const detailsUrl = DETAILS_URL_TEMPLATE.replace(':id', accountId);

        fetch(detailsUrl)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                const acc = data.account;

                document.getElementById('detailCode').textContent = acc.code;
                document.getElementById('detailName').textContent = acc.name;
                document.getElementById('detailLevel').textContent = 'المستوى ' + acc.level;
                document.getElementById('detailNature').textContent = acc.nature === 'debit' ? 'مدين (Debit)' : 'دائن (Credit)';

                const typeMap = {
                    'asset': 'أصول (Assets)',
                    'liability': 'التزامات (Liabilities)',
                    'equity': 'حقوق ملكية (Equity)',
                    'expense': 'استخدامات / مصروفات (Expenses)',
                    'revenue': 'موارد / إيرادات (Revenues)'
                };
                document.getElementById('detailType').textContent = typeMap[acc.account_type] || acc.account_type;
                document.getElementById('detailParent').textContent = acc.parent ? `${acc.parent.code} - ${acc.parent.name}` : 'لا يوجد (أصل رئيسي)';

                const chain = getAncestorChain(acc.id);
                document.getElementById('detailChainText').textContent = chain.map(n => n.name).join(' ⬇ ');

                const postableBadge = document.getElementById('detailPostableBadge');
                if (acc.has_children) {
                    postableBadge.className = 'badge bg-primary';
                    postableBadge.textContent = `📁 أصل (${acc.children_count} فرع) — لا يقبل القيود`;
                } else if (acc.is_postable) {
                    postableBadge.className = 'badge bg-success';
                    postableBadge.textContent = '🟢 فرع نهائي يقبل القيود';
                } else {
                    postableBadge.className = 'badge bg-secondary';
                    postableBadge.textContent = '📁 حساب أصل تجميعي (مستوى 1–4)';
                }

                const btnAddBranch = document.getElementById('btnAddSubAccount');
                const blockAlert = document.getElementById('detailBranchBlockAlert');
                if (acc.can_add_branch) {
                    btnAddBranch.disabled = false;
                    blockAlert.classList.add('d-none');
                } else {
                    btnAddBranch.disabled = true;
                    blockAlert.classList.remove('d-none');
                    document.getElementById('detailBranchBlockText').textContent = acc.add_branch_block_reason;
                }

                document.getElementById('btnShowInFullscreenFromDrawer').onclick = () => {
                    closeDrawer();
                    openFullscreenAt(acc.id);
                };

                document.getElementById('detailBalance').textContent =
                    new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(acc.balance) + ' د.ع';

                const tbody = document.getElementById('detailTransactionsTable');
                if (acc.recent_transactions && acc.recent_transactions.length > 0) {
                    tbody.innerHTML = '';
                    acc.recent_transactions.forEach(t => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><span class="badge bg-light text-dark border font-monospace">${escapeHtml(t.entry_number)}</span></td>
                            <td class="text-muted small">${escapeHtml(t.date)}</td>
                            <td class="text-start small text-truncate" style="max-width: 140px;">${escapeHtml(t.description)}</td>
                            <td class="text-success fw-semibold">${t.debit > 0 ? new Intl.NumberFormat('en-US').format(t.debit) : '-'}</td>
                            <td class="text-danger fw-semibold">${t.credit > 0 ? new Intl.NumberFormat('en-US').format(t.credit) : '-'}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-muted py-3">لا توجد حركات مسجلة على هذا الحساب</td></tr>';
                }

                openDrawer();
            });
    }

    function openAddBranchModal(accountId) {
        const acc = accountsById.get(accountId);
        if (!acc) return;

        if (acc.lines_count > 0) {
            alert('لا يمكن إضافة فرع تحت هذا الحساب لوجود قيود محاسبية مسجلة عليه مسبقاً.');
            return;
        }

        currentSelectedAccountId = accountId;
        document.getElementById('modalParentId').value = accountId;
        document.getElementById('modalParentLabel').textContent = `${acc.code} - ${acc.name}`;
        document.getElementById('modalAccountName').value = '';
        document.getElementById('modalAccountCode').value = '';

        fetch(`${SUGGEST_CODE_URL}?parent_id=${accountId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalAccountCode').value = data.suggested_code;
                    document.getElementById('modalCodeNotice').textContent =
                        `الكود المقترح يتبع المستوى ${data.level} (${data.is_postable ? 'فرع يقبل القيود' : 'أصل تجميعي'}).`;
                } else if (data.message) {
                    alert(data.message);
                }
            });

        const modal = new bootstrap.Modal(document.getElementById('subAccountModal'));
        modal.show();
    }

    document.getElementById('btnAddSubAccount').addEventListener('click', () => {
        if (currentSelectedAccountId) {
            openAddBranchModal(currentSelectedAccountId);
        }
    });

    document.getElementById('btnAutoSuggest').addEventListener('click', () => {
        const pid = document.getElementById('modalParentId').value || currentSelectedAccountId;
        if (!pid) return;
        fetch(`${SUGGEST_CODE_URL}?parent_id=${pid}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalAccountCode').value = data.suggested_code;
                }
            });
    });

    // ==========================================
    // 3. FULLSCREEN INTERACTIVE FLOWCHART MODE (TOP-TO-BOTTOM)
    // ==========================================
    function renderRootSwitcher() {
        const container = document.getElementById('fsRootSwitcher');
        container.innerHTML = '';
        rootAccounts.forEach(root => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = `btn ${root.id === fsActiveRootId ? 'btn-primary fw-bold' : 'btn-outline-secondary'}`;
            btn.textContent = `${root.code} ${root.name}`;
            btn.onclick = () => {
                fsActiveRootId = root.id;
                fsExpandedIds = new Set();
                fsActiveNodeId = root.id;
                renderRootSwitcher();
                renderFlowchart(true);
            };
            container.appendChild(btn);
        });
    }

    function openFullscreenAt(accountId = null) {
        const overlay = document.getElementById('fullscreenOverlay');
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';

        const targetId = accountId ?? focusedParentId;
        if (targetId && accountsById.has(targetId)) {
            const chain = getAncestorChain(targetId);
            fsActiveRootId = chain[0].id;
            fsExpandedIds = new Set();
            chain.forEach((node, idx) => {
                if (node.children_count > 0 && (idx < chain.length - 1 || node.id === targetId)) {
                    fsExpandedIds.add(node.id);
                }
            });
            fsActiveNodeId = targetId;
        } else {
            fsActiveRootId = rootAccounts.length > 0 ? rootAccounts[0].id : null;
            fsExpandedIds = new Set();
            fsActiveNodeId = fsActiveRootId;
        }

        renderRootSwitcher();
        requestAnimationFrame(() => renderFlowchart(true));
    }

    function closeFullscreen() {
        document.getElementById('fullscreenOverlay').classList.remove('show');
        document.body.style.overflow = '';
    }

    document.getElementById('btnOpenFullscreen').addEventListener('click', () => openFullscreenAt(focusedParentId));
    document.getElementById('btnCloseFullscreen').addEventListener('click', closeFullscreen);
    document.getElementById('btnFsFloatingClose').addEventListener('click', closeFullscreen);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.getElementById('fullscreenOverlay').classList.contains('show')) {
            const modalOpen = document.querySelector('.modal.show');
            if (!modalOpen) {
                closeFullscreen();
            }
        }
    });

    function updateFocusModeBtn() {
        const btn = document.getElementById('btnToggleFocusMode');
        const label = document.getElementById('focusModeLabel');
        if (fsSingleFocusMode) {
            btn.className = 'btn btn-sm btn-primary rounded-3 fw-semibold';
            label.textContent = 'النمط: التركيز الأحادي';
        } else {
            btn.className = 'btn btn-sm btn-outline-primary rounded-3 fw-semibold';
            label.textContent = 'النمط: التوسيع الحر';
        }
    }

    document.getElementById('btnToggleFocusMode').addEventListener('click', () => {
        fsSingleFocusMode = !fsSingleFocusMode;
        updateFocusModeBtn();
    });

    function collapseSubtree(nodeId) {
        fsExpandedIds.delete(nodeId);
        const kids = childrenByParent.get(nodeId) || [];
        kids.forEach(k => collapseSubtree(k.id));
    }

    function handleFlowNodeClick(acc) {
        fsActiveNodeId = acc.id;

        if (acc.children_count === 0) {
            renderFlowchart(false);
            selectAccount(acc.id);
            return;
        }

        const willExpand = !fsExpandedIds.has(acc.id);
        if (!willExpand) {
            collapseSubtree(acc.id);
        } else {
            if (fsSingleFocusMode && acc.parent_id !== null) {
                const siblings = childrenByParent.get(acc.parent_id) || [];
                siblings.forEach(sib => {
                    if (sib.id !== acc.id && fsExpandedIds.has(sib.id)) {
                        collapseSubtree(sib.id);
                    }
                });
            }
            fsExpandedIds.add(acc.id);
        }

        renderFlowchart(false);

        if (willExpand) {
            ensureNodeAndChildrenVisible(acc.id);
        }
    }

    // Build visible tree & compute non-overlapping Top-to-Bottom layout
    function buildVisibleTree(nodeId, depth = 0) {
        const acc = accountsById.get(nodeId);
        if (!acc) return null;

        const isExpanded = fsExpandedIds.has(nodeId);
        const rawChildren = isExpanded ? (childrenByParent.get(nodeId) || []) : [];
        const children = rawChildren.map(c => buildVisibleTree(c.id, depth + 1)).filter(Boolean);

        return { acc, depth, children, x: 0, y: 0, width: 0 };
    }

    const NODE_W = 225;
    const NODE_H = 96;
    const H_GAP = 24;
    const V_GAP = 64;
    const PAD = 70;

    function measureTree(node) {
        if (!node.children || node.children.length === 0) {
            node.width = NODE_W;
            return { width: NODE_W, maxDepth: node.depth };
        }
        let sumW = 0;
        let maxD = node.depth;
        node.children.forEach((child, idx) => {
            const res = measureTree(child);
            sumW += res.width + (idx > 0 ? H_GAP : 0);
            if (res.maxDepth > maxD) maxD = res.maxDepth;
        });
        node.width = Math.max(NODE_W, sumW);
        return { width: node.width, maxDepth: maxD };
    }

    function positionTree(node, leftX, flatNodes, edges) {
        // Top-to-Bottom: depth 0 at top, each deeper level flows downwards
        node.y = PAD + node.depth * (NODE_H + V_GAP);

        if (!node.children || node.children.length === 0) {
            node.x = leftX + (node.width - NODE_W) / 2;
        } else {
            const childrenTotalW = node.children.reduce(
                (sum, c, idx) => sum + c.width + (idx > 0 ? H_GAP : 0),
                0
            );
            // Order siblings Right-to-Left inside the row for natural Arabic reading
            let curRight = leftX + (node.width + childrenTotalW) / 2;
            node.children.forEach(child => {
                const childLeft = curRight - child.width;
                positionTree(child, childLeft, flatNodes, edges);
                curRight = childLeft - H_GAP;
            });

            const firstChild = node.children[0];
            const lastChild = node.children[node.children.length - 1];
            node.x = (firstChild.x + lastChild.x) / 2;
        }

        flatNodes.push(node);
        node.children.forEach(child => {
            edges.push({ parent: node, child });
        });
    }

    function applyCanvasTransform() {
        const stage = document.getElementById('fsCanvasStage');
        stage.style.transform = `translate(${fsPanX}px, ${fsPanY}px) scale(${fsScale})`;
        const pctEl = document.getElementById('fsZoomPercent');
        if (pctEl) {
            pctEl.textContent = Math.round(fsScale * 100) + '%';
        }
    }

    function renderFlowchart(fitToViewport = false) {
        if (!fsActiveRootId) return;

        const tree = buildVisibleTree(fsActiveRootId, 0);
        if (!tree) return;

        const metrics = measureTree(tree);
        const flatNodes = [];
        const edges = [];
        positionTree(tree, PAD, flatNodes, edges);

        const totalWidth = PAD * 2 + metrics.width;
        const totalHeight = PAD * 2 + (metrics.maxDepth + 1) * NODE_H + metrics.maxDepth * V_GAP + 60;

        const stage = document.getElementById('fsCanvasStage');
        stage.style.width = totalWidth + 'px';
        stage.style.height = totalHeight + 'px';

        fsLastNodePositions = new Map();
        flatNodes.forEach(n => fsLastNodePositions.set(n.acc.id, n));

        const activeChainNodes = getAncestorChain(fsActiveNodeId || fsActiveRootId);
        const activeChainIds = new Set(activeChainNodes.map(n => n.id));

        const ribbon = document.getElementById('fsActiveChainRibbon');
        ribbon.innerHTML = activeChainNodes.map((n, i) => `
            ${i > 0 ? '<i class="fas fa-arrow-down text-muted mx-1" style="font-size:10px;"></i>' : ''}
            <span class="badge ${i === activeChainNodes.length - 1 ? 'bg-primary' : 'bg-white text-dark border'} font-monospace">${escapeHtml(n.code)}</span>
            <span class="fw-semibold ${i === activeChainNodes.length - 1 ? 'text-primary' : 'text-dark'}">${escapeHtml(n.name)}</span>
        `).join('');

        // Render Top-to-Bottom SVG Edges
        const svg = document.getElementById('fsSvgLayer');
        svg.setAttribute('width', totalWidth);
        svg.setAttribute('height', totalHeight);
        svg.querySelectorAll('path.coa-flow-connector').forEach(p => p.remove());

        edges.forEach(({ parent, child }) => {
            const startX = parent.x + NODE_W / 2;
            const startY = parent.y + NODE_H;
            const endX = child.x + NODE_W / 2;
            const endY = child.y - 6;
            const midY = (startY + endY) / 2;

            const isActiveEdge = activeChainIds.has(parent.acc.id) && activeChainIds.has(child.acc.id);
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', `M ${startX} ${startY} C ${startX} ${midY}, ${endX} ${midY}, ${endX} ${endY}`);
            path.setAttribute('class', `coa-flow-connector ${isActiveEdge ? 'is-active' : ''}`);
            path.setAttribute('marker-end', isActiveEdge ? 'url(#arrowActive)' : 'url(#arrowDefault)');
            svg.appendChild(path);
        });

        // Render HTML Boxes
        const nodesLayer = document.getElementById('fsNodesLayer');
        nodesLayer.innerHTML = '';

        flatNodes.forEach(node => {
            const acc = node.acc;
            const isExpanded = fsExpandedIds.has(acc.id);
            const isInActiveChain = activeChainIds.has(acc.id);
            const hasChildren = acc.children_count > 0;

            let typeClass = 'node-origin';
            if (acc.parent_id === null) {
                typeClass = 'node-root';
            } else if (acc.is_postable) {
                typeClass = 'node-postable';
            }

            const el = document.createElement('div');
            el.className = `coa-flow-node ${typeClass} ${isInActiveChain ? 'node-active-chain' : ''} ${isExpanded ? 'node-expanded' : ''}`;
            el.style.left = node.x + 'px';
            el.style.top = node.y + 'px';

            el.innerHTML = `
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge ${acc.parent_id === null ? 'bg-primary' : (acc.is_postable ? 'bg-success' : 'bg-secondary')} font-monospace" style="font-size: 11px;">
                        ${escapeHtml(acc.code)}
                    </span>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-light border coa-node-action-btn text-primary" title="تفاصيل الحساب" onclick="event.stopPropagation(); selectAccount(${acc.id})">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${acc.lines_count === 0 ? `
                        <button type="button" class="btn btn-light border coa-node-action-btn text-success" title="إضافة فرع" onclick="event.stopPropagation(); openAddBranchModal(${acc.id})">
                            <i class="fas fa-plus"></i>
                        </button>` : ''}
                    </div>
                </div>
                <div class="fw-bold text-dark small text-truncate" title="${escapeHtml(acc.name)}">${escapeHtml(acc.name)}</div>
                <div class="d-flex justify-content-between align-items-center mt-2 pt-1 border-top border-light-subtle" style="font-size: 11px;">
                    ${hasChildren
                        ? `<span class="text-primary fw-semibold"><i class="fas ${isExpanded ? 'fa-chevron-up' : 'fa-chevron-down'} me-1"></i>${isExpanded ? 'طي الفروع' : `فتح (${acc.children_count} فرع)`}</span>`
                        : (acc.is_postable
                            ? '<span class="text-success fw-semibold"><i class="fas fa-check-circle me-1"></i>يقبل القيود</span>'
                            : `<span class="text-muted">مستوى ${acc.level} تجميعي</span>`)
                    }
                    <span class="text-muted">م${acc.level}</span>
                </div>
            `;

            el.addEventListener('click', () => {
                if (fsDidDrag) return;
                handleFlowNodeClick(acc);
            });
            nodesLayer.appendChild(el);
        });

        if (fitToViewport) {
            fitFlowchartToViewport(totalWidth, totalHeight);
        } else {
            applyCanvasTransform();
        }
    }

    function fitFlowchartToViewport(totalWidth, totalHeight) {
        const vp = document.getElementById('fsCanvasViewport');
        const vpW = vp.clientWidth || window.innerWidth;
        const vpH = vp.clientHeight || (window.innerHeight - 100);

        const scaleX = vpW / totalWidth;
        const scaleY = (vpH - 70) / totalHeight;
        fsScale = Math.min(1, Math.max(0.35, Math.min(scaleX, scaleY) * 0.92));

        fsPanX = (vpW - totalWidth * fsScale) / 2;
        fsPanY = Math.max(16, (vpH - 60 - totalHeight * fsScale) / 2);
        applyCanvasTransform();
    }

    function ensureNodeAndChildrenVisible(nodeId) {
        const node = fsLastNodePositions.get(nodeId);
        if (!node) return;
        const vp = document.getElementById('fsCanvasViewport');
        const vpW = vp.clientWidth || window.innerWidth;
        const vpH = vp.clientHeight || (window.innerHeight - 100);

        // Center horizontally on the expanded parent node, and keep children comfortably above the floating bar
        const nodeCenterX = (node.x + NODE_W / 2) * fsScale + fsPanX;
        const childBottomY = (node.y + NODE_H + V_GAP + NODE_H + 30) * fsScale + fsPanY;

        if (nodeCenterX < 140 || nodeCenterX > vpW - 140) {
            fsPanX = vpW / 2 - (node.x + NODE_W / 2) * fsScale;
        }
        if (childBottomY > vpH - 85) {
            fsPanY -= (childBottomY - (vpH - 95));
        }
        applyCanvasTransform();
    }

    function zoomAroundViewportCenter(delta) {
        const vp = document.getElementById('fsCanvasViewport');
        const vpW = vp.clientWidth || window.innerWidth;
        const vpH = vp.clientHeight || window.innerHeight;
        const cx = vpW / 2;
        const cy = vpH / 2;
        const newScale = Math.min(1.8, Math.max(0.25, fsScale + delta));
        fsPanX = cx - (cx - fsPanX) * (newScale / fsScale);
        fsPanY = cy - (cy - fsPanY) * (newScale / fsScale);
        fsScale = newScale;
        applyCanvasTransform();
    }

    // Multi-touch (iPad 1-finger pan + 2-finger pinch), Trackpad 2-finger pan, & Mouse Drag
    const fsViewport = document.getElementById('fsCanvasViewport');
    const fsActivePointers = new Map();
    let fsPinchStartDist = 0;
    let fsPinchStartScale = 1;
    let fsPinchLastCenter = null;

    function getPinchMetrics() {
        const pts = Array.from(fsActivePointers.values());
        if (pts.length < 2) return null;
        const dx = pts[1].clientX - pts[0].clientX;
        const dy = pts[1].clientY - pts[0].clientY;
        return {
            dist: Math.hypot(dx, dy),
            cx: (pts[0].clientX + pts[1].clientX) / 2,
            cy: (pts[0].clientY + pts[1].clientY) / 2,
        };
    }

    fsViewport.addEventListener('pointerdown', (e) => {
        if (e.target.closest('#fsFloatingControls') || e.target.closest('.coa-node-action-btn')) return;

        fsActivePointers.set(e.pointerId, { clientX: e.clientX, clientY: e.clientY });

        if (fsActivePointers.size === 1) {
            fsPointerDown = true;
            fsDidDrag = false;
            fsStartClientX = e.clientX;
            fsStartClientY = e.clientY;
            fsStartPanX = fsPanX;
            fsStartPanY = fsPanY;
        } else if (fsActivePointers.size === 2) {
            fsPointerDown = false;
            fsDidDrag = true;
            const pinch = getPinchMetrics();
            if (pinch) {
                fsPinchStartDist = pinch.dist;
                fsPinchStartScale = fsScale;
                fsPinchLastCenter = { x: pinch.cx, y: pinch.cy };
            }
        }
    });

    window.addEventListener('pointermove', (e) => {
        if (!fsActivePointers.has(e.pointerId)) return;
        fsActivePointers.set(e.pointerId, { clientX: e.clientX, clientY: e.clientY });

        // 2-Finger Pinch-to-Zoom & Pan on iPad / Touch
        if (fsActivePointers.size === 2) {
            const pinch = getPinchMetrics();
            if (!pinch || fsPinchStartDist <= 0) return;

            const rect = fsViewport.getBoundingClientRect();
            const localCx = pinch.cx - rect.left;
            const localCy = pinch.cy - rect.top;

            if (fsPinchLastCenter) {
                fsPanX += (pinch.cx - fsPinchLastCenter.x);
                fsPanY += (pinch.cy - fsPinchLastCenter.y);
            }
            fsPinchLastCenter = { x: pinch.cx, y: pinch.cy };

            const targetScale = Math.min(1.8, Math.max(0.25, fsPinchStartScale * (pinch.dist / fsPinchStartDist)));
            fsPanX = localCx - (localCx - fsPanX) * (targetScale / fsScale);
            fsPanY = localCy - (localCy - fsPanY) * (targetScale / fsScale);
            fsScale = targetScale;
            applyCanvasTransform();
            return;
        }

        // 1-Finger Touch / Mouse Drag Pan
        if (!fsPointerDown) return;
        const dx = e.clientX - fsStartClientX;
        const dy = e.clientY - fsStartClientY;
        if (!fsDidDrag && Math.hypot(dx, dy) > 5) {
            fsDidDrag = true;
            fsViewport.classList.add('is-panning');
        }
        if (fsDidDrag) {
            fsPanX = fsStartPanX + dx;
            fsPanY = fsStartPanY + dy;
            applyCanvasTransform();
        }
    });

    function handlePointerRelease(e) {
        if (!fsActivePointers.has(e.pointerId)) return;
        fsActivePointers.delete(e.pointerId);

        if (fsActivePointers.size === 1) {
            const remaining = Array.from(fsActivePointers.values())[0];
            fsPointerDown = true;
            fsStartClientX = remaining.clientX;
            fsStartClientY = remaining.clientY;
            fsStartPanX = fsPanX;
            fsStartPanY = fsPanY;
            fsPinchLastCenter = null;
        } else if (fsActivePointers.size === 0) {
            fsPointerDown = false;
            fsPinchLastCenter = null;
            fsViewport.classList.remove('is-panning');
            setTimeout(() => { fsDidDrag = false; }, 0);
        }
    }

    window.addEventListener('pointerup', handlePointerRelease);
    window.addEventListener('pointercancel', handlePointerRelease);

    // Trackpad 2-finger swipe pans in all directions (no button hold needed!); Ctrl/Pinch zooms
    fsViewport.addEventListener('wheel', (e) => {
        e.preventDefault();
        if (e.ctrlKey || e.metaKey) {
            const zoomFactor = e.deltaY < 0 ? 1.08 : 0.92;
            const newScale = Math.min(1.8, Math.max(0.25, fsScale * zoomFactor));
            const rect = fsViewport.getBoundingClientRect();
            const mx = e.clientX - rect.left;
            const my = e.clientY - rect.top;

            fsPanX = mx - (mx - fsPanX) * (newScale / fsScale);
            fsPanY = my - (my - fsPanY) * (newScale / fsScale);
            fsScale = newScale;
            applyCanvasTransform();
        } else {
            fsPanX -= e.deltaX;
            fsPanY -= e.deltaY;
            applyCanvasTransform();
        }
    }, { passive: false });

    function nudgePan(dx, dy) {
        fsPanX += dx;
        fsPanY += dy;
        applyCanvasTransform();
    }

    document.getElementById('btnFsPanRight').addEventListener('click', () => nudgePan(-180, 0));
    document.getElementById('btnFsPanLeft').addEventListener('click', () => nudgePan(180, 0));
    document.getElementById('btnFsPanUp').addEventListener('click', () => nudgePan(0, 140));
    document.getElementById('btnFsPanDown').addEventListener('click', () => nudgePan(0, -140));

    document.getElementById('btnFsZoomIn').addEventListener('click', () => zoomAroundViewportCenter(0.15));
    document.getElementById('btnFsZoomOut').addEventListener('click', () => zoomAroundViewportCenter(-0.15));
    document.getElementById('btnFsZoomReset').addEventListener('click', () => {
        const stage = document.getElementById('fsCanvasStage');
        fitFlowchartToViewport(parseFloat(stage.style.width) || 600, parseFloat(stage.style.height) || 400);
    });

    // Fullscreen Quick Jump Search
    const fsJumpInput = document.getElementById('fsJumpSearch');
    const fsJumpResults = document.getElementById('fsJumpResults');

    fsJumpInput.addEventListener('input', (e) => {
        const term = e.target.value.trim().toLowerCase();
        if (!term) {
            fsJumpResults.classList.add('d-none');
            fsJumpResults.innerHTML = '';
            return;
        }
        const matches = ALL_ACCOUNTS.filter(a =>
            a.code.toLowerCase().includes(term) || a.name.toLowerCase().includes(term)
        ).slice(0, 15);

        if (matches.length === 0) {
            fsJumpResults.innerHTML = '<div class="list-group-item small text-muted">لا توجد نتائج</div>';
            fsJumpResults.classList.remove('d-none');
            return;
        }

        fsJumpResults.innerHTML = '';
        matches.forEach(m => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action text-end small py-2';
            btn.innerHTML = `<span class="badge bg-primary font-monospace me-1">${escapeHtml(m.code)}</span> <strong>${escapeHtml(m.name)}</strong>`;
            btn.onclick = () => {
                fsJumpResults.classList.add('d-none');
                fsJumpInput.value = '';
                openFullscreenAt(m.id);
            };
            fsJumpResults.appendChild(btn);
        });
        fsJumpResults.classList.remove('d-none');
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#fsJumpSearch') && !e.target.closest('#fsJumpResults')) {
            fsJumpResults.classList.add('d-none');
        }
    });

    // Normal mode controls
    document.getElementById('treeSearchInput').addEventListener('input', renderNormalView);
    document.getElementById('btnClearSearch').addEventListener('click', () => {
        document.getElementById('treeSearchInput').value = '';
        renderNormalView();
    });
    document.getElementById('btnResetRoot').addEventListener('click', () => drillInto(null));
    document.getElementById('btnDrillUp').addEventListener('click', () => {
        if (focusedParentId === null) return;
        const curr = accountsById.get(focusedParentId);
        drillInto(curr ? curr.parent_id : null);
    });

    // Initial render
    updateFocusModeBtn();
    renderNormalView();
</script>
@endpush
