@extends('layouts.app')

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
    body {
        font-family: 'Tajawal', sans-serif !important;
    }
    
    .pulse-dot {
        width: 10px;
        height: 10px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: pulseAnimation 1.6s infinite;
    }
    @keyframes pulseAnimation {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }

    .pulse-dot-danger {
        background-color: #ef4444;
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
        animation: pulseDanger 1.6s infinite;
    }
    @keyframes pulseDanger {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    .glass-card-pulse {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 10px 30px rgba(30, 58, 138, 0.05);
        border-radius: 20px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .glass-card-pulse:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 36px rgba(30, 58, 138, 0.09);
    }

    .timeline-item {
        position: relative;
        padding-right: 48px;
        padding-bottom: 24px;
        border-right: 2px dashed #e2e8f0;
    }
    .timeline-item:last-child {
        border-right: 2px solid transparent;
        padding-bottom: 0;
    }
    .timeline-icon-box {
        position: absolute;
        right: -19px;
        top: 0;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        z-index: 2;
    }

    .user-avatar-circle {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1e40af;
    }

    .nav-pills-custom .nav-link {
        color: #475569;
        font-weight: 700;
        border-radius: 14px;
        padding: 10px 20px;
        transition: all 0.2s ease;
    }
    .nav-pills-custom .nav-link.active {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        box-shadow: 0 6px 20px rgba(37, 99, 235, 0.25);
    }
</style>
@endsection

@section('content')
<div class="container-fluid px-4 py-3">

    <!-- بنر الرأس والإحصائيات اللحظية -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%); border-radius: 24px; overflow: hidden;">
                <div class="card-body p-4 position-relative">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 position-relative" style="z-index: 2;">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-3 py-1 rounded-pill fw-bold">
                                    <span class="pulse-dot me-1"></span> رادار المستشفى اللحظي (Staff Pulse)
                                </span>
                                <span class="badge bg-white bg-opacity-10 text-white rounded-pill px-3 py-1" id="lastRefreshTimestamp">
                                    <i class="fas fa-clock me-1"></i> {{ now()->translatedFormat('l d F Y - h:i A') }}
                                </span>
                            </div>
                            <h2 class="fw-black mb-1 text-white" style="font-weight: 900; letter-spacing: -0.02em;">
                                <i class="fas fa-satellite-dish text-primary me-2"></i> سجل حركات ونشاط الموظفين والرقابة الإدارية
                            </h2>
                            <p class="text-white-50 mb-0 small">
                                مراقبة لحظية لحركات كافة موظفي الاستقبال، الكاشير، الأطباء، المختبر، الأشعة، الخزينة، وتتبع العمليات الحساسة.
                            </p>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <!-- أداة التحديث التلقائي اللحظي ومؤشر النبض -->
                            <div class="d-inline-flex align-items-center bg-white bg-opacity-10 border border-white border-opacity-20 rounded-pill px-3 py-1 shadow-sm">
                                <span class="pulse-dot me-2" id="autoRefreshDot"></span>
                                <span class="small fw-bold text-white me-2" id="autoRefreshStatusText" style="font-size: 0.8rem;">بث حي:</span>
                                <select id="autoRefreshIntervalSelect" class="form-select form-select-sm bg-transparent text-white border-0 py-0 pe-4 ps-1 fw-bold shadow-none" style="font-size: 0.82rem; width: auto; cursor: pointer;">
                                    <option value="15" class="text-dark" selected>كل 15 ثانية</option>
                                    <option value="30" class="text-dark">كل 30 ثانية</option>
                                    <option value="60" class="text-dark">كل دقيقة</option>
                                    <option value="0" class="text-dark">إيقاف المؤقت ⏸️</option>
                                </select>
                                <div class="vr bg-white bg-opacity-25 mx-2" style="height: 18px;"></div>
                                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 font-monospace rounded-pill px-2 py-1" id="countdownBadge" style="font-size: 0.75rem;">
                                    <i class="fas fa-stopwatch me-1"></i><span id="countdownSeconds">15</span>ث
                                </span>
                            </div>

                            <button id="refreshFeedBtn" class="btn btn-outline-light btn-sm fw-bold px-3 py-2 rounded-3 shadow-sm d-flex align-items-center gap-1" onclick="performLiveRefresh(true);">
                                <i class="fas fa-sync-alt" id="refreshIcon"></i>
                                <span>تحديث الآن</span>
                            </button>
                            <a href="{{ route('activity-logs.export', request()->query()) }}" class="btn btn-success btn-sm fw-bold px-3 py-2 rounded-3 shadow-sm">
                                <i class="fas fa-file-excel me-1"></i> تصدير الكشف (Excel/CSV)
                            </a>
                            @can('delete activity logs')
                            <button class="btn btn-outline-danger btn-sm fw-bold px-3 py-2 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#purgeLogsModal">
                                <i class="fas fa-archive me-1"></i> تنظيف وأرشفة
                            </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بطاقات المؤشرات اللحظية الأربعة (KPIs) -->
    <div class="row g-3 mb-4" id="kpiCardsSection">
        <!-- 1. المتواجدون الآن -->
        <div class="col-xl-3 col-sm-6">
            <div class="card glass-card-pulse h-100 border-start border-success border-4 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">
                            <span class="pulse-dot me-1"></span> المتواجدون الآن بالسيستم
                        </span>
                        <h2 class="fw-black text-success mb-0" style="font-family: 'Outfit', sans-serif;">
                            {{ $activeNowCount }} <small class="fs-6 fw-normal text-muted">موظف</small>
                        </h2>
                        <span class="text-success small fw-bold">
                            نشطون خلال الـ 5 دقائق الماضية
                        </span>
                    </div>
                    <div class="rounded-4 p-3 bg-success bg-opacity-10 text-success fs-3 shadow-sm">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. موظفون تفاعلوا اليوم -->
        <div class="col-xl-3 col-sm-6">
            <div class="card glass-card-pulse h-100 border-start border-primary border-4 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">
                            <i class="fas fa-id-badge text-primary me-1"></i> كادر تفاعل وسجل دخولاً اليوم
                        </span>
                        <h2 class="fw-black text-primary mb-0" style="font-family: 'Outfit', sans-serif;">
                            {{ $staffLoggedInTodayCount }} <small class="fs-6 fw-normal text-muted">كادر</small>
                        </h2>
                        <span class="text-muted small">
                            أجروا حركات أو سجلوا دخول اليوم
                        </span>
                    </div>
                    <div class="rounded-4 p-3 bg-primary bg-opacity-10 text-primary fs-3 shadow-sm">
                        <i class="fas fa-users-cog"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. إجمالي الحركات اليوم -->
        <div class="col-xl-3 col-sm-6">
            <div class="card glass-card-pulse h-100 border-start border-info border-4 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">
                            <i class="fas fa-bolt text-info me-1"></i> إجمالي العمليات والحركات اليوم
                        </span>
                        <h2 class="fw-black text-dark mb-0" style="font-family: 'Outfit', sans-serif;">
                            {{ number_format($totalToday) }} <small class="fs-6 fw-normal text-muted">عملية</small>
                        </h2>
                        <span class="text-info small fw-bold">
                            كشفيات، دفعات، تحاليل، أشعة، خزينة
                        </span>
                    </div>
                    <div class="rounded-4 p-3 bg-info bg-opacity-10 text-info fs-3 shadow-sm">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. العمليات الحساسة والرادار -->
        <div class="col-xl-3 col-sm-6">
            <div class="card glass-card-pulse h-100 border-start border-danger border-4 shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">
                            <span class="pulse-dot pulse-dot-danger me-1"></span> حركات حساسة / رادار الرقابة
                        </span>
                        <h2 class="fw-black text-danger mb-0" style="font-family: 'Outfit', sans-serif;">
                            {{ $sensitiveToday }} <small class="fs-6 fw-normal text-muted">حركة</small>
                        </h2>
                        <span class="text-danger small fw-bold">
                            حذف، استرجاع مبالغ، إلغاء حجوزات
                        </span>
                    </div>
                    <div class="rounded-4 p-3 bg-danger bg-opacity-10 text-danger fs-3 shadow-sm">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط التبويبات الرئيسية الأربعة -->
    <div class="card glass-card-pulse border-0 shadow-sm mb-4">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-pills-custom gap-2" id="activityTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active d-flex align-items-center gap-2" id="timeline-tab" data-bs-toggle="pill" data-bs-target="#timelinePane" type="button" role="tab">
                        <i class="fas fa-stream"></i>
                        <span>التايم لاين المباشر للحركات (Live Feed)</span>
                        <span class="badge bg-white text-primary rounded-pill" id="timelineCountBadge">{{ $activities->total() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2" id="online-tab" data-bs-toggle="pill" data-bs-target="#onlinePane" type="button" role="tab">
                        <i class="fas fa-wifi text-success"></i>
                        <span>المتواجدون الآن وسجل الدخول</span>
                        <span class="badge bg-success text-white rounded-pill" id="onlineCountBadge">{{ $onlineUsers->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link d-flex align-items-center gap-2" id="productivity-tab" data-bs-toggle="pill" data-bs-target="#productivityPane" type="button" role="tab">
                        <i class="fas fa-medal text-warning"></i>
                        <span>إنتاجية الكادر والموظفين اليوم</span>
                        <span class="badge bg-warning text-dark rounded-pill" id="productivityCountBadge">{{ $staffProductivity->count() }}</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- محتوى التبويبات -->
    <div class="tab-content" id="activityTabsContent">

        <!-- ======================= التبويب 1: التايم لاين وسجل الحركات ======================= -->
        <div class="tab-pane fade show active" id="timelinePane" role="tabpanel">

            <!-- شريط الفلاتر والبحث المتقدم -->
            <div class="card glass-card-pulse border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-2 align-items-end">
                        
                        <!-- بحث بالكلمة -->
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label small fw-bold text-muted mb-1">
                                <i class="fas fa-search me-1"></i> بحث بالوصف أو الاسم:
                            </label>
                            <input type="text" name="search" class="form-control form-control-sm rounded-3 shadow-none" placeholder="ابحث عن مريض، موصل، إجراء..." value="{{ request('search') }}">
                        </div>

                        <!-- فلتر القسم / التصنيف -->
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1">
                                <i class="fas fa-layer-group me-1"></i> القسم / التصنيف:
                            </label>
                            <select name="category" class="form-select form-select-sm rounded-3 shadow-none">
                                @foreach($categoriesList as $key => $lbl)
                                    <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- فلتر الموظف -->
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1">
                                <i class="fas fa-user me-1"></i> الموظف:
                            </label>
                            <select name="user_id" class="form-select form-select-sm rounded-3 shadow-none">
                                <option value="all">كافة الموظفين</option>
                                @foreach($usersList as $u)
                                    <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- فلتر التاريخ -->
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label small fw-bold text-muted mb-1">
                                <i class="fas fa-calendar-alt me-1"></i> الفترة الزمنية:
                            </label>
                            <select name="date_filter" class="form-select form-select-sm rounded-3 shadow-none" onchange="toggleCustomDates(this.value)">
                                <option value="today" {{ request('date_filter', 'today') == 'today' ? 'selected' : '' }}>اليوم فقط</option>
                                <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>أمس</option>
                                <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>هذا الأسبوع</option>
                                <option value="all" {{ request('date_filter') == 'all' ? 'selected' : '' }}>الكل بدون تحديد</option>
                                <option value="custom" {{ request('date_filter') == 'custom' ? 'selected' : '' }}>تاريخ مخصص...</option>
                            </select>
                        </div>

                        <!-- مفتاح الحركات الحساسة فقط -->
                        <div class="col-lg-2 col-md-4">
                            <div class="form-check form-switch pt-3">
                                <input class="form-check-input" type="checkbox" name="sensitive_only" value="1" id="sensitiveOnlySwitch" {{ request('sensitive_only') ? 'checked' : '' }}>
                                <label class="form-check-label small fw-bold text-danger" for="sensitiveOnlySwitch">
                                    <i class="fas fa-exclamation-triangle me-1"></i> حركات حساسة فقط
                                </label>
                            </div>
                        </div>

                        <!-- أزرار الإجراء -->
                        <div class="col-lg-1 col-md-4 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold rounded-3">
                                <i class="fas fa-filter"></i> فلترة
                            </button>
                            <a href="{{ route('activity-logs.index') }}" class="btn btn-light btn-sm fw-bold rounded-3 text-muted" title="إعادة تعيين">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- عرض التايم لاين المباشر -->
            <div class="card glass-card-pulse border-0 shadow-sm" id="timelineCardWrapper">
                <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between p-3 pb-0">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-history text-primary me-2"></i> شريط الأحداث والحركات التشغيلية
                    </h5>
                    <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">
                        صفحة {{ $activities->currentPage() }} من {{ $activities->lastPage() }}
                    </span>
                </div>
                <div class="card-body p-4">
                    @if($activities->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-check fa-3x mb-3 text-secondary opacity-50"></i>
                            <h5 class="fw-bold">لا توجد حركات مسجلة مطابقة لمعايير البحث الحالية</h5>
                            <p class="small text-muted mb-0">ستظهر أي عملية تسجيل دخول، حجز، كشفية، تسديد، أو فحص تجري بالمستشفى هنا فورياً.</p>
                        </div>
                    @else
                        <div class="timeline-wrapper pe-2">
                            @foreach($activities as $act)
                                <div class="timeline-item">
                                    <!-- أيقونة التصنيف -->
                                    <div class="timeline-icon-box bg-{{ $act->category_color }} text-white">
                                        <i class="{{ $act->category_icon }}"></i>
                                    </div>

                                    <!-- بطاقة الحركة -->
                                    <div class="card border-0 shadow-sm p-3 rounded-4 {{ $act->is_sensitive ? 'border-start border-danger border-4 bg-danger bg-opacity-10' : 'bg-light' }}">
                                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-2">
                                            <!-- بيانات المستخدم -->
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="user-avatar-circle" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                                    {{ mb_substr($act->user_name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-dark">{{ $act->user_name }}</span>
                                                    <span class="badge bg-white text-secondary border rounded-pill ms-1 small">
                                                        {{ $act->role_name }}
                                                    </span>
                                                    <span class="badge bg-{{ $act->category_color }}-subtle text-{{ $act->category_color }} border border-{{ $act->category_color }}-subtle rounded-pill ms-1 small">
                                                        {{ $act->category_label }}
                                                    </span>
                                                    @if($act->is_sensitive)
                                                        <span class="badge bg-danger text-white rounded-pill ms-1 small fw-bold">
                                                            <i class="fas fa-shield-alt me-1"></i> حركة حساسة
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- التوقيت و IP -->
                                            <div class="text-md-end text-muted small">
                                                <span class="fw-bold text-dark">{{ $act->created_at->format('h:i:s A') }}</span>
                                                <span class="mx-1">•</span>
                                                <span>{{ $act->created_at->diffForHumans() }}</span>
                                                @if($act->ip_address)
                                                    <span class="badge bg-light text-muted border ms-1 font-monospace" style="font-size: 0.72rem;">
                                                        <i class="fas fa-network-wired me-1"></i>{{ $act->ip_address }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- نص ووصف الحركة -->
                                        <div class="fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.6;">
                                            {{ $act->description }}
                                        </div>

                                        <!-- التفاصيل الإضافية إن وجدت -->
                                        @if(!empty($act->properties))
                                            <div class="mt-2 pt-2 border-top border-secondary border-opacity-10 small text-muted font-monospace d-flex flex-wrap gap-2">
                                                @foreach($act->properties as $pk => $pv)
                                                    <span class="badge bg-white text-dark border">
                                                        <strong>{{ $pk }}:</strong> {{ is_array($pv) ? json_encode($pv) : $pv }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- الترقيم والصفحات -->
                        @if($activities->hasPages())
                            <div class="p-3 border-top d-flex justify-content-center">
                                {{ $activities->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>

        </div>

        <!-- ======================= التبويب 2: المتواجدون الآن وسجل الدخول ======================= -->
        <div class="tab-pane fade" id="onlinePane" role="tabpanel">
            <div class="card glass-card-pulse border-0 shadow-sm" id="onlineCardWrapper">
                <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between p-3 pb-0">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">
                            <span class="pulse-dot me-2"></span> الكادر المتصل وسجل تسجيلات الدخول لليوم
                        </h5>
                        <p class="small text-muted mb-0">يتم تحديث حالة الاتصال تلقائياً بمجرد تنقل الموظف بين صفحات النظام.</p>
                    </div>
                    <span class="badge bg-primary text-white px-3 py-2 rounded-pill fw-bold">
                        {{ $onlineUsers->count() }} مسجل دخول اليوم
                    </span>
                </div>
                <div class="card-body p-3">
                    @if($onlineUsers->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-user-slash fa-3x mb-3 text-secondary opacity-50"></i>
                            <h6 class="fw-bold">لا يوجد مستخدمون متصلون حالياً</h6>
                        </div>
                    @else
                        <div class="row g-3">
                            @foreach($onlineUsers as $ou)
                                @php
                                    $lastAct = $ou->last_activity_at ? \Carbon\Carbon::parse($ou->last_activity_at) : null;
                                    $lastLog = $ou->last_login_at ? \Carbon\Carbon::parse($ou->last_login_at) : null;
                                    $isOnlineNow = $lastAct && $lastAct->diffInMinutes(now()) <= 5;
                                    $wasRecentlyActive = $lastAct && $lastAct->diffInMinutes(now()) <= 15;
                                @endphp
                                <div class="col-xl-4 col-md-6">
                                    <div class="card border rounded-4 p-3 shadow-sm h-100 {{ $isOnlineNow ? 'bg-success bg-opacity-10 border-success' : ($wasRecentlyActive ? 'bg-warning bg-opacity-10 border-warning' : 'bg-light border-light') }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="user-avatar-circle" style="width: 42px; height: 42px; font-size: 1.1rem; background: {{ $isOnlineNow ? '#dcfce7' : '#f1f5f9' }}; color: {{ $isOnlineNow ? '#15803d' : '#475569' }};">
                                                    {{ mb_substr($ou->name, 0, 1) }}
                                                </div>
                                                <div>
                                                    <h6 class="fw-bold mb-0 text-dark">{{ $ou->name }}</h6>
                                                    <small class="text-muted">{{ $ou->roles->pluck('name')->join(', ') ?: 'مستخدم' }}</small>
                                                </div>
                                            </div>
                                            <div>
                                                @if($isOnlineNow)
                                                    <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold">
                                                        <span class="pulse-dot me-1"></span> متصل الآن
                                                    </span>
                                                @elseif($wasRecentlyActive)
                                                    <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold">
                                                        منذ {{ $lastAct->diffInMinutes(now()) }} دقيقة
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1">
                                                        غير متصل
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="border-top pt-2 mt-2 small text-muted">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span><i class="fas fa-sign-in-alt text-primary me-1"></i> أول دخول اليوم:</span>
                                                <span class="fw-bold text-dark">{{ $lastLog ? $lastLog->format('h:i A') : 'غير متوفر' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span><i class="fas fa-wave-square text-info me-1"></i> آخر نشاط:</span>
                                                <span class="fw-bold text-dark">{{ $lastAct ? $lastAct->diffForHumans() : '-' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span><i class="fas fa-network-wired text-muted me-1"></i> الـ IP:</span>
                                                <span class="font-monospace text-muted">{{ $ou->last_login_ip ?: '-' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- ======================= التبويب 3: إنتاجية الموظفين لليوم ======================= -->
        <div class="tab-pane fade" id="productivityPane" role="tabpanel">
            <div class="card glass-card-pulse border-0 shadow-sm" id="productivityCardWrapper">
                <div class="card-header bg-transparent border-0 d-flex align-items-center justify-content-between p-3 pb-0">
                    <div>
                        <h5 class="fw-bold mb-1 text-dark">
                            <i class="fas fa-medal text-warning me-2"></i> جدول إنتاجية وإنجاز الكادر اليومي
                        </h5>
                        <p class="small text-muted mb-0">إحصائيات فورية توضح عدد العمليات المنجزة من قبل كل موظف حسب القسم لليوم الحالي.</p>
                    </div>
                </div>
                <div class="card-body p-0 mt-3">
                    @if($staffProductivity->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-chart-bar fa-3x mb-3 text-secondary opacity-50"></i>
                            <h6 class="fw-bold">لم تسجل أي حركات للموظفين لليوم بعد</h6>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>الموظف</th>
                                        <th>الصفة / الدور</th>
                                        <th class="text-center">إجمالي الإنجاز اليوم</th>
                                        <th class="text-center">الاستقبال</th>
                                        <th class="text-center">الكاشير</th>
                                        <th class="text-center">العيادات</th>
                                        <th class="text-center">المختبر</th>
                                        <th class="text-center">الأشعة</th>
                                        <th class="text-center">الخزينة</th>
                                        <th class="text-center">حساسة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($staffProductivity as $sp)
                                        <tr>
                                            <td class="fw-bold text-muted">{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="user-avatar-circle" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                                        {{ mb_substr($sp->user_name, 0, 1) }}
                                                    </div>
                                                    <span class="fw-bold text-dark">{{ $sp->user_name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border rounded-pill">{{ $sp->role_name }}</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary text-white rounded-pill px-3 py-1 fw-bold fs-6">
                                                    {{ $sp->total_actions }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $sp->reception_count > 0 ? 'info' : 'light' }} text-{{ $sp->reception_count > 0 ? 'white' : 'muted' }} rounded-pill">
                                                    {{ $sp->reception_count }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $sp->cashier_count > 0 ? 'success' : 'light' }} text-{{ $sp->cashier_count > 0 ? 'white' : 'muted' }} rounded-pill">
                                                    {{ $sp->cashier_count }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $sp->doctor_count > 0 ? 'primary' : 'light' }} text-{{ $sp->doctor_count > 0 ? 'white' : 'muted' }} rounded-pill">
                                                    {{ $sp->doctor_count }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $sp->lab_count > 0 ? 'warning' : 'light' }} text-{{ $sp->lab_count > 0 ? 'dark' : 'muted' }} rounded-pill">
                                                    {{ $sp->lab_count }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $sp->radiology_count > 0 ? 'teal' : 'light' }} text-{{ $sp->radiology_count > 0 ? 'white' : 'muted' }} rounded-pill" style="background-color: {{ $sp->radiology_count > 0 ? '#0d9488' : '' }};">
                                                    {{ $sp->radiology_count }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-{{ $sp->treasury_count > 0 ? 'dark' : 'light' }} text-{{ $sp->treasury_count > 0 ? 'white' : 'muted' }} rounded-pill">
                                                    {{ $sp->treasury_count }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                @if($sp->sensitive_count > 0)
                                                    <span class="badge bg-danger text-white rounded-pill fw-bold">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>{{ $sp->sensitive_count }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

</div>

<!-- مودال تنظيف وأرشفة السجلات القديمة -->
@can('delete activity logs')
<div class="modal fade" id="purgeLogsModal" tabindex="-1" aria-labelledby="purgeLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-danger text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold" id="purgeLogsModalLabel">
                    <i class="fas fa-archive me-2"></i> تنظيف وأرشفة سجل الحركات
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('activity-logs.purge') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted mb-3">
                        سيتم حذف حركات النشاط القديمة لتخفيف حجم قاعدة البيانات. للحفاظ على الأمان، لا يمكن حذف الحركات الأحدث من 30 يوماً.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">اختر الحد الأدنى للأرشفة:</label>
                        <select name="days" class="form-select rounded-3">
                            <option value="90" selected>حذف الحركات الأقدم من 90 يوماً (3 أشهر) - موصى به</option>
                            <option value="180">حذف الحركات الأقدم من 180 يوماً (6 أشهر)</option>
                            <option value="365">حذف الحركات الأقدم من سنة كاملة</option>
                            <option value="30">حذف الحركات الأقدم من 30 يوماً (شهر)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger fw-bold rounded-3" onclick="return confirm('هل أنت متأكد من رغبتك في أرشفة وتنظيف السجلات القديمة؟');">
                        تأكيد التنظيف والأرشفة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection

@section('scripts')
<script>
    function toggleCustomDates(val) {
        if (val === 'custom') {
            // يمكن إضافة مودال أو حقول مخصصة
        }
    }

    // ==================== محرك التحديث التلقائي والنبض الحي (Auto-Refresh Engine) ====================
    let countdownTimer = null;
    let refreshInterval = 15;
    let currentSeconds = 15;
    let isRefreshing = false;

    document.addEventListener('DOMContentLoaded', function() {
        initAutoRefresh();
    });

    function initAutoRefresh() {
        const select = document.getElementById('autoRefreshIntervalSelect');
        const saved = localStorage.getItem('hospital_activity_refresh_rate');
        if (saved !== null && select) {
            select.value = saved;
            refreshInterval = parseInt(saved, 10);
        } else if (select) {
            refreshInterval = parseInt(select.value, 10);
        }

        if (select) {
            select.addEventListener('change', function() {
                refreshInterval = parseInt(this.value, 10);
                localStorage.setItem('hospital_activity_refresh_rate', refreshInterval);
                resetCountdown();
            });
        }

        resetCountdown();
        startCountdownTimer();
    }

    function resetCountdown() {
        currentSeconds = refreshInterval;
        updateCountdownUI();
    }

    function updateCountdownUI() {
        const badge = document.getElementById('countdownBadge');
        const dot = document.getElementById('autoRefreshDot');
        const txt = document.getElementById('autoRefreshStatusText');

        if (!badge || !dot || !txt) return;

        if (refreshInterval === 0) {
            dot.style.display = 'none';
            badge.className = 'badge bg-secondary bg-opacity-25 text-white-50 border border-white border-opacity-25 font-monospace rounded-pill px-2 py-1';
            badge.innerHTML = '<i class="fas fa-pause me-1"></i>متوقف';
            txt.textContent = 'المؤقت:';
        } else {
            dot.style.display = 'inline-block';
            badge.className = 'badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 font-monospace rounded-pill px-2 py-1';
            badge.innerHTML = '<i class="fas fa-stopwatch me-1"></i><span id="countdownSeconds">' + currentSeconds + '</span>ث';
            txt.textContent = 'بث حي:';
        }
    }

    function startCountdownTimer() {
        if (countdownTimer) clearInterval(countdownTimer);

        countdownTimer = setInterval(() => {
            if (refreshInterval === 0 || isRefreshing) return;

            // عدم التحديث إذا كان المستخدم يكتب في حقول الفلترة أو يبحث
            const activeElem = document.activeElement;
            const isUserInteracting = activeElem && (
                activeElem.tagName === 'INPUT' || 
                activeElem.tagName === 'TEXTAREA' || 
                (activeElem.tagName === 'SELECT' && activeElem.id !== 'autoRefreshIntervalSelect')
            );
            
            // عدم التحديث إذا كان أي مودال مفتوحاً
            const isModalOpen = document.querySelector('.modal.show') !== null;

            if (isUserInteracting || isModalOpen) {
                return;
            }

            currentSeconds--;
            const secSpan = document.getElementById('countdownSeconds');
            if (secSpan) secSpan.textContent = currentSeconds;

            if (currentSeconds <= 0) {
                performLiveRefresh(false);
            }
        }, 1000);
    }

    async function performLiveRefresh(manual = false) {
        if (isRefreshing) return;
        isRefreshing = true;

        const icon = document.getElementById('refreshIcon');
        if (icon) icon.classList.add('fa-spin');

        try {
            const response = await fetch(window.location.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (response.ok) {
                const htmlText = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(htmlText, 'text/html');

                // 1. تحديث بطاقات الـ KPI العلوية
                const newKpis = doc.getElementById('kpiCardsSection');
                const curKpis = document.getElementById('kpiCardsSection');
                if (newKpis && curKpis) {
                    curKpis.innerHTML = newKpis.innerHTML;
                }

                // 2. تحديث شارات العدادات في التبويبات
                ['timelineCountBadge', 'onlineCountBadge', 'productivityCountBadge'].forEach(id => {
                    const newBadge = doc.getElementById(id);
                    const curBadge = document.getElementById(id);
                    if (newBadge && curBadge) {
                        curBadge.innerHTML = newBadge.innerHTML;
                    }
                });

                // 3. تحديث شريط التايم لاين (Live Feed)
                const newTL = doc.getElementById('timelineCardWrapper');
                const curTL = document.getElementById('timelineCardWrapper');
                if (newTL && curTL) {
                    curTL.innerHTML = newTL.innerHTML;
                }

                // 4. تحديث بطاقات المتواجدين الآن
                const newOnline = doc.getElementById('onlineCardWrapper');
                const curOnline = document.getElementById('onlineCardWrapper');
                if (newOnline && curOnline) {
                    curOnline.innerHTML = newOnline.innerHTML;
                }

                // 5. تحديث جدول الإنتاجية
                const newProd = doc.getElementById('productivityCardWrapper');
                const curProd = document.getElementById('productivityCardWrapper');
                if (newProd && curProd) {
                    curProd.innerHTML = newProd.innerHTML;
                }

                // 6. تحديث توقيت آخر تحديث
                const now = new Date();
                const timeStr = now.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                const lastUpdatedElem = document.getElementById('lastRefreshTimestamp');
                if (lastUpdatedElem) {
                    lastUpdatedElem.innerHTML = '<i class="fas fa-clock me-1"></i> آخر تحديث: ' + timeStr;
                }
            }
        } catch (err) {
            console.error('Live refresh error:', err);
        } finally {
            if (icon) icon.classList.remove('fa-spin');
            isRefreshing = false;
            resetCountdown();
        }
    }
</script>
@endsection
