@extends('layouts.app')

@section('content')
<style>
/* Beautiful Unified Table Styles - Matching Doctor's Main Station */
.unified-table {
    border-radius: 10px;
    background: #fff;
    border: 1px solid #e5e7eb;
    margin-bottom: 2rem;
    box-shadow: none;
}

.unified-table thead th {
    background: #f3f4f6;
    color: #2563eb;
    border: none;
    padding: 1rem 0.75rem;
    font-weight: 600;
    font-size: 0.95rem;
    text-transform: none;
    letter-spacing: 0.2px;
    position: relative;
    border-bottom: 1px solid #e5e7eb;
}

.unified-table thead th i {
    color: #60a5fa;
    margin-left: 0.5rem;
}

/* Row color coding based on status - Professional Medical Colors */
.unified-table tbody tr.calling-row {
    background: #fefce8 !important;
    border-right: 5px solid #eab308 !important;
    border-left: 1px solid #fde047 !important;
}

.unified-table tbody tr.inprogress-row {
    background: #e0f2fe !important;
    border-right: 5px solid #0284c7 !important;
    border-left: 1px solid #7dd3fc !important;
}

.unified-table tbody tr.completed-row {
    background: #f0fdf4 !important;
    border-right: 5px solid #16a34a !important;
    border-left: 1px solid #86efac !important;
}

.unified-table tbody tr:hover {
    transform: translateX(3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.12);
    z-index: 1;
    position: relative;
}

.unified-table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    border: none;
    font-size: 0.85rem;
}

.unified-table .type-badge {
    padding: 0.3rem 0.6rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: #e0e7ff;
    color: #2563eb;
    border: none;
}

.unified-table .status-badge {
    padding: 0.4rem 0.8rem;
    border-radius: 14px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.unified-table .status-badge.status-completed {
    background: #f0fdf4;
    color: #166534;
    border-color: #bbf7d0;
}

.unified-table .status-badge.status-calling {
    background: #fefce8;
    color: #854d0e;
    border-color: #fde047;
    animation: pulseBorder 1.5s infinite;
}

.unified-table .status-badge.status-pending {
    background: #f3f4f6;
    color: #4b5563;
    border-color: #e5e7eb;
}

@keyframes pulseBorder {
    0% { transform: scale(1); }
    50% { transform: scale(1.03); }
    100% { transform: scale(1); }
}

.unified-table .action-btn {
    padding: 0.4rem 0.8rem;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.unified-table .action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Avatar circles */
.avatar-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    font-weight: bold;
    color: #2563eb;
    background: #e0e7ff;
    box-shadow: none;
    border: 1px solid #c7d2fe;
}

/* Tabs Styling matching Doctor's Station */
#radiologyTabs .nav-link {
    font-size: 0.95rem;
    font-weight: 600;
    color: #4b5563;
    padding: 0.75rem 1.25rem;
    border: none;
    border-bottom: 3px solid transparent;
    transition: all 0.2s ease;
    background: transparent;
}
#radiologyTabs .nav-link:hover {
    color: #2563eb;
    border-bottom-color: #93c5fd;
}
#radiologyTabs .nav-link.active {
    color: #2563eb !important;
    background: transparent !important;
    border-bottom: 3px solid #2563eb !important;
}

/* Calling Station Control Bar (Matching Doctor's Station) */
.queue-control-bar {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

/* Executive KPI Metrics */
.kpi-metric-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    padding: 0.9rem 1.1rem;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}
.kpi-metric-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.06);
    border-color: #cbd5e1;
}
.kpi-metric-card.active-card {
    box-shadow: 0 0 0 2px #2563eb, 0 8px 16px rgba(37,99,235,0.12);
}
.kpi-metric-card .icon-box {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>

<div class="container-fluid px-3 px-md-4 py-3" id="radiology-hub-content">
    
    <!-- 1. Header & Live Indicator -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                    <i class="fas fa-x-ray fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-dark">
                        @if($selectedCategory === 'ultrasound')
                            محطة فحص السونار والدوبلر
                            <span class="badge bg-info-subtle text-info border border-info-subtle fs-6 ms-2"><i class="fas fa-wave-square me-1"></i> السونار</span>
                        @elseif($selectedCategory === 'mri')
                            قسم الرنين المغناطيسي والمفراس
                            <span class="badge bg-purple-subtle text-purple border border-purple-subtle fs-6 ms-2" style="background-color: #f3e8ff; color: #7e22ce;"><i class="fas fa-magnet me-1"></i> الرنين والمفراس</span>
                        @elseif($selectedCategory === 'echo')
                            قسم فحص إيكو القلب
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 ms-2"><i class="fas fa-heartbeat me-1"></i> إيكو القلب</span>
                        @elseif($selectedCategory === 'radiology')
                            قسم الأشعة والتصوير التشخيصي (X-Ray)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 ms-2"><i class="fas fa-film me-1"></i> الأشعة السينية</span>
                        @else
                            قسم الأشعة والتصوير الطبي
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 ms-2"><i class="fas fa-hospital me-1"></i> المركز العام</span>
                        @endif
                    </h3>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        @if($selectedCategory === 'ultrasound')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle py-1 px-2">
                                <i class="fas fa-circle fa-xs me-1 text-success"></i> طابور مباشر متزامن مع الغرفة 11
                            </span>
                        @else
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2">
                                <i class="fas fa-layer-group fa-xs me-1"></i> نظام الفحوصات والتقارير الطبية
                            </span>
                        @endif
                        <small class="text-muted" id="last-update-time">آخر تحديث: {{ now()->format('H:i:s') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Category Switcher (Admins & General Roles) -->
        @if(auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'receptionist']) || empty($userCategory))
            <div class="d-flex flex-wrap gap-1 bg-white p-1 rounded-pill shadow-xs border">
                <a href="{{ route('radiology-staff.index', ['category' => 'all', 'tab' => $activeTab, 'date' => $dateFilter]) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-bold {{ $selectedCategory === 'all' ? 'btn-primary shadow-xs' : 'btn-light text-dark' }}">
                    🌐 الكل
                </a>
                <a href="{{ route('radiology-staff.index', ['category' => 'ultrasound', 'tab' => $activeTab, 'date' => $dateFilter]) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-bold {{ $selectedCategory === 'ultrasound' ? 'btn-info text-white shadow-xs' : 'btn-light text-dark' }}">
                    🔊 السونار
                </a>
                <a href="{{ route('radiology-staff.index', ['category' => 'radiology', 'tab' => $activeTab, 'date' => $dateFilter]) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-bold {{ $selectedCategory === 'radiology' ? 'btn-secondary text-white shadow-xs' : 'btn-light text-dark' }}">
                    🩻 الأشعة
                </a>
                <a href="{{ route('radiology-staff.index', ['category' => 'mri', 'tab' => $activeTab, 'date' => $dateFilter]) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-bold {{ $selectedCategory === 'mri' ? 'btn-dark text-white shadow-xs' : 'btn-light text-dark' }}">
                    🧲 الرنين
                </a>
                <a href="{{ route('radiology-staff.index', ['category' => 'echo', 'tab' => $activeTab, 'date' => $dateFilter]) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-bold {{ $selectedCategory === 'echo' ? 'btn-danger text-white shadow-xs' : 'btn-light text-dark' }}">
                    ❤️ الإيكو
                </a>
            </div>
        @endif
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 2. Calling Station Control Bar (حصرياً للسونار) -->
    @if($selectedCategory === 'ultrasound')
    <div class="queue-control-bar">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <!-- Serving Status -->
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-circle fs-5" style="width: 44px; height: 44px; background: #e0f2fe; color: #0284c7; border-color: #bae6fd;">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary text-white px-2 py-1 fs-6 font-monospace" id="radiology-station-current-num">الدور: -</span>
                        <strong class="text-dark fs-6" id="radiology-station-current-name">لا يوجد مريض مستدعى</strong>
                        <span class="status-badge status-pending" id="radiology-station-status-badge">غرفة الفحص جاهزة</span>
                    </div>
                    <small class="text-muted" id="radiology-station-current-status">اضغط "استدعاء التالي" لمناداة أول مريض مسدد في طابور الانتظار</small>
                </div>
            </div>

            <!-- Actions & Screen Link -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button" class="btn btn-success fw-bold px-3 py-2" onclick="radiologyCallNext()" id="btn-call-next">
                    <i class="fas fa-bullhorn me-1"></i> استدعاء التالي
                </button>
                <button type="button" class="btn btn-warning fw-bold px-3 py-2 text-dark" onclick="radiologyRecall()" id="btn-recall" style="display: none;">
                    <i class="fas fa-redo me-1"></i> إعادة المناداة
                </button>
                <button type="button" class="btn btn-primary fw-bold px-3 py-2" onclick="radiologyStartExam()" id="btn-start-exam" style="display: none;">
                    <i class="fas fa-door-open me-1"></i> إدخال وبدء الفحص
                </button>
                <button type="button" class="btn btn-outline-secondary fw-semibold px-3 py-2" onclick="radiologySkip()" id="btn-skip" style="display: none;">
                    <i class="fas fa-forward me-1"></i> تخطي
                </button>
                <a href="{{ url('/queue/room/' . (auth()->user()->doctor?->current_room ?? 11)) }}" target="_blank" class="btn btn-outline-primary px-3 py-2">
                    <i class="fas fa-tv me-1"></i> شاشة العرض (الغرفة {{ auth()->user()->doctor?->current_room ?? 11 }})
                </a>
            </div>
        </div>
    </div>
    @endif

    <!-- 2. بطاقات الأقسام: الاستشارية، طلبات الأشعة المباشرة، الطوارئ، وكافة الطلبات (مثل واجهة المختبر تماماً) -->
    <div class="row g-2 mb-3" id="radiology-department-cards">
        <!-- أ. طلبات العيادات الاستشارية -->
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 kpi-metric-card cursor-pointer border-bottom border-primary border-4 {{ ($sourceFilter ?? '') === 'consultation' || ($activeTab ?? 'station') === 'station' ? 'shadow' : '' }}" 
                 onclick="switchRadiologyTab('station-tab')" style="background: #ffffff; cursor: pointer; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">طلبات العيادات الاستشارية</span>
                        <h4 class="mb-0 fw-bold text-primary font-monospace" id="kpi-consultation-count">{{ $counts['consultation'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.72rem;">فحوصات محولة من الأطباء</small>
                    </div>
                    <div class="bg-primary-subtle text-primary p-2 rounded-circle text-center d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-user-md fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ب. طلبات الأشعة المباشرة (الخارجية) -->
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 kpi-metric-card cursor-pointer border-bottom border-info border-4 {{ ($sourceFilter ?? '') === 'direct' ? 'shadow' : '' }}" 
                 onclick="switchRadiologyTab('direct-tab')" style="background: #ffffff; cursor: pointer; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">طلبات الأشعة المباشرة</span>
                        <h4 class="mb-0 fw-bold text-info font-monospace" id="kpi-direct-count">{{ $counts['direct'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.72rem;">مراجعي الصندوق والاستقبال</small>
                    </div>
                    <div class="bg-info-subtle text-info p-2 rounded-circle text-center d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-x-ray fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ج. أشعة وفحوصات قسم الطوارئ -->
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 kpi-metric-card cursor-pointer border-bottom border-danger border-4 {{ ($sourceFilter ?? '') === 'emergency' || ($activeTab ?? '') === 'emergency' ? 'shadow' : '' }}" 
                 onclick="switchRadiologyTab('emergency-tab')" style="background: #ffffff; cursor: pointer; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">أشعة وفحوصات قسم الطوارئ</span>
                        <h4 class="mb-0 fw-bold text-danger font-monospace" id="kpi-emergency-count">{{ $counts['emergency'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.72rem;">حالات الطوارئ والكسور الحرجة</small>
                    </div>
                    <div class="bg-danger-subtle text-danger p-2 rounded-circle text-center d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-ambulance fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- د. كافة طلبات القسم -->
        <div class="col-xl-3 col-md-6">
            <div class="card shadow-sm border-0 h-100 kpi-metric-card cursor-pointer border-bottom border-secondary border-4 {{ ($sourceFilter ?? '') === 'all' || ($activeTab ?? '') === 'all' ? 'shadow' : '' }}" 
                 onclick="switchRadiologyTab('all-tab')" style="background: #ffffff; cursor: pointer; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">كافة طلبات القسم</span>
                        <h4 class="mb-0 fw-bold text-dark font-monospace" id="kpi-all-count">{{ $counts['all'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.72rem;">مكتمل: {{ $counts['completed'] ?? 0 }} • معلق: {{ $counts['pending'] ?? 0 }}</small>
                    </div>
                    <div class="bg-secondary-subtle text-secondary p-2 rounded-circle text-center d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-layer-group fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. شريط الفلترة والبحث والتبويبات حسب كل قسم (مطابق لواجهة طلبات المختبر) -->
    <div class="card shadow-sm border-0 bg-white mb-3 rounded-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center justify-content-between">
                <!-- التبويبات حسب الأقسام والمصادر -->
                <div class="col-lg-7 col-12">
                    <ul class="nav nav-pills gap-1" id="radiologyTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ in_array($activeTab, ['station', 'waiting']) || ($sourceFilter ?? '') === 'consultation' ? 'active' : '' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2" id="station-tab" data-bs-toggle="tab" data-bs-target="#station-pane" type="button" role="tab">
                                <i class="fas fa-user-md"></i>
                                <span>الاستشارية</span>
                                <span class="badge {{ in_array($activeTab, ['station', 'waiting']) || ($sourceFilter ?? '') === 'consultation' ? 'bg-white text-primary' : 'bg-primary text-white' }} rounded-pill font-monospace" id="badge-tab-waiting-count">{{ $counts['consultation'] ?? 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'direct' || ($sourceFilter ?? '') === 'direct' ? 'active' : '' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2" id="direct-tab" data-bs-toggle="tab" data-bs-target="#direct-pane" type="button" role="tab">
                                <i class="fas fa-x-ray"></i>
                                <span>طلبات مباشرة</span>
                                <span class="badge {{ $activeTab === 'direct' || ($sourceFilter ?? '') === 'direct' ? 'bg-white text-info' : 'bg-info text-white' }} rounded-pill font-monospace" id="badge-tab-direct-count">{{ $counts['direct'] ?? 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ in_array($activeTab, ['inprogress', 'in-progress']) ? 'active' : '' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2" id="inprogress-tab" data-bs-toggle="tab" data-bs-target="#inprogress-pane" type="button" role="tab">
                                <i class="fas fa-stethoscope"></i>
                                <span>قيد الفحص</span>
                                <span class="badge {{ in_array($activeTab, ['inprogress', 'in-progress']) ? 'bg-white text-info' : 'bg-info text-white' }} rounded-pill font-monospace" id="badge-tab-inprogress-count">{{ $counts['in_progress'] ?? 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'completed' ? 'active' : '' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-pane" type="button" role="tab">
                                <i class="fas fa-check-circle"></i>
                                <span>المكتملة والطباعة</span>
                                <span class="badge {{ $activeTab === 'completed' ? 'bg-white text-success' : 'bg-success text-white' }} rounded-pill font-monospace" id="badge-tab-completed-count">{{ $counts['completed'] ?? 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'pending' || ($sourceFilter ?? '') === 'pending' ? 'active bg-warning text-dark' : 'text-dark' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-pane" type="button" role="tab">
                                <i class="fas fa-history text-warning"></i>
                                <span>الطلبات المعلقة (أيام سابقة)</span>
                                <span class="badge {{ $activeTab === 'pending' || ($sourceFilter ?? '') === 'pending' ? 'bg-white text-warning' : 'bg-warning text-dark' }} rounded-pill font-monospace" id="badge-tab-pending-count">{{ $counts['prior_pending'] ?? 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'emergency' || ($sourceFilter ?? '') === 'emergency' ? 'active bg-danger text-white' : 'text-danger' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2" id="emergency-tab" data-bs-toggle="tab" data-bs-target="#emergency-pane" type="button" role="tab">
                                <i class="fas fa-ambulance"></i>
                                <span>الطوارئ</span>
                                <span class="badge {{ $activeTab === 'emergency' || ($sourceFilter ?? '') === 'emergency' ? 'bg-white text-danger' : 'bg-danger text-white' }} rounded-pill font-monospace">{{ $counts['emergency'] ?? 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'nicu' || ($sourceFilter ?? '') === 'nicu' ? 'active bg-primary text-white' : 'text-primary' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2" id="nicu-tab" data-bs-toggle="tab" data-bs-target="#nicu-pane" type="button" role="tab">
                                <i class="fas fa-baby"></i>
                                <span>الخدج والحاضنات</span>
                                <span class="badge {{ $activeTab === 'nicu' || ($sourceFilter ?? '') === 'nicu' ? 'bg-white text-primary' : (($stats['nicu'] ?? 0) > 0 ? 'bg-danger text-white' : 'bg-primary text-white') }} rounded-pill font-monospace" id="badge-tab-nicu-count">{{ $stats['nicu'] ?? 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $activeTab === 'all' || ($sourceFilter ?? '') === 'all' ? 'active' : '' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-pane" type="button" role="tab">
                                <i class="fas fa-list"></i>
                                <span>كافة الطلبات</span>
                                <span class="badge {{ $activeTab === 'all' || ($sourceFilter ?? '') === 'all' ? 'bg-white text-dark' : 'bg-secondary text-white' }} rounded-pill font-monospace">{{ $counts['all'] ?? 0 }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- شريط البحث والفلترة الموحد والشامل لكافة التبويبات -->
                <div class="col-lg-5 col-12">
                    <form method="GET" action="{{ route('radiology-staff.index') }}" class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                        <input type="hidden" name="tab" id="radiology-active-tab-input" value="{{ $activeTab }}">
                        <input type="hidden" name="source" id="radiology-source-input" value="{{ $sourceFilter ?? ($activeTab === 'station' ? 'consultation' : $activeTab) }}">
                        <input type="hidden" name="category" value="{{ $selectedCategory }}">
                        
                        <!-- فلتر التاريخ الموحد والشامل -->
                        <div class="d-flex align-items-center gap-1">
                            <select name="date" class="form-select form-select-sm" style="width: auto; min-width: 125px;" onchange="this.form.submit()">
                                <option value="today" {{ ($dateFilter ?? '') === 'today' ? 'selected' : '' }}>📅 اليوم</option>
                                <option value="yesterday" {{ ($dateFilter ?? '') === 'yesterday' ? 'selected' : '' }}>⏮️ أمس</option>
                                <option value="week" {{ ($dateFilter ?? '') === 'week' ? 'selected' : '' }}>🗓️ هذا الأسبوع</option>
                                <option value="month" {{ ($dateFilter ?? '') === 'month' ? 'selected' : '' }}>📆 هذا الشهر</option>
                                <option value="all" {{ ($dateFilter ?? '') === 'all' ? 'selected' : '' }}>🗂️ كافة التواريخ</option>
                            </select>
                            <input type="date" name="custom_date" class="form-control form-control-sm" style="width: 130px;" 
                                   value="{{ !in_array($dateFilter ?? '', ['today', 'yesterday', 'week', 'month', 'all']) ? $dateFilter : '' }}" 
                                   title="اختر تاريخاً محدداً" onchange="this.form.submit()">
                        </div>

                        <!-- البحث السريع الموحد -->
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <input type="text" name="search" id="radiology-search-input" class="form-control" 
                                   placeholder="بحث باسم، رقم..." 
                                   value="{{ $search }}">
                            <button class="btn btn-primary" type="submit" title="بحث">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(!empty($search) || ($dateFilter ?? 'today') !== 'today')
                                <a href="{{ route('radiology-staff.index', ['category' => $selectedCategory, 'tab' => $activeTab, 'date' => 'today']) }}" class="btn btn-outline-secondary" title="إعادة ضبط الفلاتر">
                                    <i class="fas fa-undo"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Tab Content Panels -->
    <div class="tab-content" id="radiologyTabsContent">
        
        <!-- 1. LIVE STATION TAB (طلبات العيادات الاستشارية) -->
        <div class="tab-pane fade {{ in_array($activeTab, ['station', 'waiting']) || ($sourceFilter ?? '') === 'consultation' ? 'show active' : '' }}" id="station-pane" role="tabpanel">
            
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-user-md me-2 text-primary"></i>
                    طلبات العيادات الاستشارية (المحولة من الأطباء)
                </h5>
                <span class="badge bg-primary text-white" id="badge-live-waiting-count">{{ $counts['consultation'] ?? 0 }} طلب</span>
            </div>

            <div class="table-responsive">
                <table class="table unified-table mb-2">
                    <thead>
                        <tr>
                            <th style="width: 80px;"><i class="fas fa-hashtag me-1"></i>الدور</th>
                            <th style="width: 75px;"><i class="fas fa-receipt me-1"></i>#الطلب</th>
                            <th><i class="fas fa-user-injured me-2"></i>المريض</th>
                            <th><i class="fas fa-x-ray me-2"></i>الفحوصات المطلوبة</th>
                            <th><i class="fas fa-user-md me-2"></i>الطبيب المحول</th>
                            <th style="width: 80px;" class="text-center"><i class="fas fa-clock me-1"></i>الوقت</th>
                            <th style="width: 120px;" class="text-center"><i class="fas fa-money-bill-wave me-1"></i>الدفع</th>
                            <th style="width: 120px;" class="text-center"><i class="fas fa-tasks me-1"></i>الحالة</th>
                            <th class="text-end" style="width: 180px;"><i class="fas fa-cogs me-2"></i>الإجراء والنداء</th>
                        </tr>
                    </thead>
                    <tbody id="radiology-station-waiting-list">
                        @php $turnNum = 1; @endphp
                        @forelse($consultationRequests as $req)
                            @php
                                $isNicu = !empty($req->incubator_reservation_id);
                                $nicuRes = $isNicu ? $req->incubatorReservation : null;
                                $isPaid = $isNicu || $req->payment_status === 'paid';
                                $isCalling = $req->status === 'calling';
                                $p = $isNicu ? $nicuRes?->patient : $req->visit?->patient;
                                $pName = $isNicu ? ($nicuRes?->baby_title ?: ($nicuRes?->baby_name ?: ($p?->user?->name ? 'طفل ' . $p->user->name : 'طفل خديج'))) : ($p?->name ?? $p?->user?->name ?? 'مريض');
                                $qNum = $req->visit?->appointment?->queue_number ?? ($turnNum++);
                            @endphp
                            <tr class="{{ $isCalling ? 'calling-row fw-bold' : ($req->status === 'in_progress' ? 'inprogress-row' : ($req->status === 'completed' ? 'completed-row' : '')) }}" id="request-row-{{ $req->id }}">
                                <td>
                                    <span class="badge {{ $isCalling ? 'bg-warning text-dark' : 'bg-dark bg-opacity-10 text-dark border' }} px-2 py-1 font-monospace fs-6">
                                        #{{ $qNum }}
                                    </span>
                                </td>
                                <td class="font-monospace text-muted">#{{ $req->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle {{ $isNicu ? 'bg-primary-subtle text-primary' : '' }}">
                                            @if($isNicu)
                                                <i class="fas fa-baby"></i>
                                            @else
                                                {{ mb_substr($pName, 0, 1) }}
                                            @endif
                                        </div>
                                        <div class="ms-2">
                                            <strong class="text-dark">{{ $pName }}</strong>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                @if($isNicu)
                                                    <span class="badge bg-info-subtle text-dark border border-info-subtle me-1">#{{ $nicuRes?->incubator?->name ?? 'حاضنة' }}</span>
                                                    <span>ملف: #{{ $p?->national_id ?: ($p?->id ?? '-') }}</span>
                                                @else
                                                    @if($p?->gender) <span>{{ $p->gender === 'male' ? 'ذكر' : 'أنثى' }} • </span> @endif
                                                    @if($p?->age) <span>{{ $p->age }} سنة</span> @endif
                                                    @if($p?->medical_number) <span class="badge bg-light text-muted border ms-1 font-monospace">{{ $p->medical_number }}</span> @endif
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if(!empty($req->radiology_names))
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($req->radiology_names as $rName)
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-bold py-1 px-2">
                                                    <i class="fas fa-x-ray fa-xs me-1"></i> {{ $rName }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">{{ $req->description ?: 'فحص تصوير' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isNicu)
                                        <div class="small fw-semibold text-dark">د. {{ $nicuRes?->doctor?->user?->name ?? 'طبيب الخدج' }}</div>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;">
                                            <i class="fas fa-baby me-1"></i> قسم الخدج
                                        </span>
                                    @else
                                        <div class="small fw-semibold text-dark">د. {{ $req->visit?->doctor?->user?->name ?? 'الاستشارية' }}</div>
                                        <small class="text-muted" style="font-size: 0.72rem;">{{ $req->visit?->department?->name ?? 'العيادات' }}</small>
                                    @endif
                                    @if(!empty($req->doctor_notes))
                                        <div class="mt-1">
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle text-truncate d-inline-block" style="max-width: 170px; font-size: 0.68rem;" title="{{ $req->doctor_notes }}">
                                                <i class="fas fa-sticky-note text-warning me-1"></i> {{ $req->doctor_notes }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center font-monospace small">{{ $req->created_at ? $req->created_at->format('H:i') : '—' }}</td>
                                <td class="text-center">
                                    @if($isNicu)
                                        <span class="badge bg-success text-white py-1 px-2 shadow-xs fw-bold" title="يُسدد ضمن حساب الحاضنة الموحد لدى الكاشير"><i class="fas fa-file-invoice-dollar me-1"></i> حساب الحاضنة</span>
                                    @elseif($isPaid)
                                        <span class="badge bg-success text-white py-1 px-2 shadow-xs fw-bold"><i class="fas fa-check-circle me-1"></i> مدفوع</span>
                                    @else
                                        <span class="badge bg-danger text-white py-1 px-2 fw-bold"><i class="fas fa-clock me-1"></i> غير مدفوع</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($req->status === 'completed')
                                        <span class="badge bg-success text-white py-1 px-2 fw-bold"><i class="fas fa-check-circle me-1"></i> مكتمل</span>
                                    @elseif($req->status === 'in_progress')
                                        <span class="badge bg-info text-white py-1 px-2 fw-bold"><i class="fas fa-stethoscope me-1"></i> قيد الفحص</span>
                                    @elseif($isCalling)
                                        <span class="badge bg-warning text-dark border border-warning py-1 px-2 fw-bold"><i class="fas fa-bullhorn me-1"></i> قيد النداء</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary py-1 px-2">⏳ بالانتظار</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        @if($req->status === 'completed')
                                            <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-outline-primary" title="عرض وطباعة التقرير">
                                                <i class="fas fa-print me-1"></i> عرض / طباعة
                                            </a>
                                        @elseif($req->status === 'in_progress')
                                            <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-success fw-bold shadow-xs" title="كتابة التقرير">
                                                <i class="fas fa-edit me-1"></i> كتابة التقرير
                                            </a>
                                        @elseif($isCalling)
                                            <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-primary fw-bold shadow-xs" title="إدخال وبدء الفحص">
                                                <i class="fas fa-door-open me-1"></i> إدخال
                                            </a>
                                        @elseif($selectedCategory === 'ultrasound')
                                            @if($isPaid)
                                                <button type="button" class="action-btn btn-warning text-dark fw-bold shadow-xs" onclick="radiologyCallSpecific({{ $req->id }}, '{{ addslashes($pName) }}')" title="مناداة واستدعاء للغرفة">
                                                    <i class="fas fa-bullhorn"></i> نداء
                                                </button>
                                            @else
                                                <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-outline-secondary" title="معاينة الطلب">
                                                    <i class="fas fa-eye"></i> معاينة
                                                </a>
                                            @endif
                                        @else
                                            <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-primary fw-bold shadow-xs" title="بدء الفحص وكتابة التقرير">
                                                <i class="fas fa-play me-1"></i> بدء الفحص
                                            </a>
                                        @endif
                                        @if($req->isDirectExternal() && !in_array($req->status, ['completed', 'cancelled']))
                                            <button type="button" 
                                                    class="action-btn btn-outline-danger" 
                                                    title="إلغاء الطلب الخارجي"
                                                    onclick="openCancelRadiologyModal({{ $req->id }}, '{{ addslashes($pName) }}')">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle text-muted me-1"></i> لا توجد طلبات أشعة محولة من العيادات الاستشارية لهذا التاريخ
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <!-- DIRECT REQUESTS TAB (طلبات الأشعة المباشرة والاستقبال) -->
        <div class="tab-pane fade {{ ($activeTab ?? '') === 'direct' ? 'show active' : '' }}" id="direct-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-x-ray me-2 text-info"></i>
                    طلبات الأشعة المباشرة (مراجعي الصندوق وقسم الأشعة الخارجي)
                </h5>
                <span class="badge bg-info text-white" id="badge-direct-count">{{ $counts['direct'] ?? 0 }} طلب مباشر</span>
            </div>
            <div class="table-responsive">
                <table class="table unified-table mb-2">
                    <thead>
                        <tr>
                            <th style="width: 75px;"><i class="fas fa-receipt me-1"></i>#الطلب</th>
                            <th><i class="fas fa-user me-2"></i>المريض</th>
                            <th><i class="fas fa-x-ray me-2"></i>الفحوصات المطلوبة</th>
                            <th><i class="fas fa-hospital me-2"></i>المصدر</th>
                            <th style="width: 100px;" class="text-center"><i class="fas fa-clock me-1"></i>الوقت</th>
                            <th style="width: 120px;" class="text-center"><i class="fas fa-money-bill-wave me-1"></i>الدفع</th>
                            <th style="width: 120px;" class="text-center"><i class="fas fa-tasks me-1"></i>الحالة</th>
                            <th class="text-end" style="width: 180px;"><i class="fas fa-cogs me-2"></i>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($directRequests as $req)
                            @php
                                $isPaid = $req->payment_status === 'paid';
                                $p = $req->visit?->patient;
                                $pName = $p?->name ?? $p?->user?->name ?? 'مريض';
                            @endphp
                            <tr id="direct-row-{{ $req->id }}">
                                <td class="font-monospace text-muted">#{{ $req->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle" style="background: #e0f2fe; color: #0284c7; border-color: #bae6fd;">
                                            {{ mb_substr($pName, 0, 1) }}
                                        </div>
                                        <div class="ms-2">
                                            <strong class="text-dark">{{ $pName }}</strong>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                @if($p?->gender) <span>{{ $p->gender === 'male' ? 'ذكر' : 'أنثى' }} • </span> @endif
                                                @if($p?->age) <span>{{ $p->age }} سنة</span> @endif
                                                @if($p?->medical_number) <span class="badge bg-light text-muted border ms-1 font-monospace">{{ $p->medical_number }}</span> @endif
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if(!empty($req->radiology_names))
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($req->radiology_names as $rName)
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle fw-bold py-1 px-2">
                                                    <i class="fas fa-x-ray fa-xs me-1"></i> {{ $rName }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">{{ $req->description ?: 'فحص تصوير' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">استقبال الصندوق / مباشر</div>
                                    <small class="badge bg-light text-info border border-info-subtle" style="font-size: 0.68rem;">
                                        مريض خارجي
                                    </small>
                                </td>
                                <td class="text-center font-monospace small">{{ $req->created_at ? $req->created_at->format('H:i') : '—' }}</td>
                                <td class="text-center">
                                    @if($isPaid)
                                        <span class="badge bg-success text-white py-1 px-2 shadow-xs fw-bold"><i class="fas fa-check-circle me-1"></i> مدفوع</span>
                                    @else
                                        <span class="badge bg-danger text-white py-1 px-2 fw-bold"><i class="fas fa-clock me-1"></i> غير مدفوع</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($req->status === 'completed')
                                        <span class="badge bg-success text-white py-1 px-2 fw-bold"><i class="fas fa-check-circle me-1"></i> مكتمل</span>
                                    @elseif($req->status === 'in_progress')
                                        <span class="badge bg-info text-white py-1 px-2 fw-bold"><i class="fas fa-stethoscope me-1"></i> قيد الفحص</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary py-1 px-2">⏳ بالانتظار</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-outline-primary" title="معاينة وبدء الفحص">
                                            <i class="fas fa-eye"></i> عرض
                                        </a>
                                        @if($req->isDirectExternal() && !in_array($req->status, ['completed', 'cancelled']))
                                            <button type="button" 
                                                    class="action-btn btn-outline-danger" 
                                                    title="إلغاء الطلب الخارجي"
                                                    onclick="openCancelRadiologyModal({{ $req->id }}, '{{ addslashes($pName) }}')">
                                                <i class="fas fa-ban"></i> إلغاء
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle text-muted me-1"></i> لا توجد طلبات أشعة مباشرة مسجلة حالياً
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. IN PROGRESS TAB (قيد الفحص والتقرير) -->
        <div class="tab-pane fade {{ in_array($activeTab, ['inprogress', 'in-progress']) ? 'show active' : '' }}" id="inprogress-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-stethoscope me-2 text-info"></i>
                    الفحوصات الجارية الآن وكتابة التقارير والنتائج
                </h5>
                <span class="badge bg-info text-white" id="badge-tab-inprogress-inner-count">{{ $stats['in_progress'] }} قيد الفحص</span>
            </div>
            <div class="table-responsive">
                <table class="table unified-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">الدور</th>
                            <th>المريض</th>
                            <th>الفحوصات المطلوبة</th>
                            <th>الطبيب المحول</th>
                            <th style="width: 100px;" class="text-center">الوقت</th>
                            <th class="text-end" style="width: 170px;">كتابة التقرير والنتائج</th>
                        </tr>
                    </thead>
                    <tbody id="radiology-tab-inprogress-tbody">
                        @forelse($inProgressRequests as $req)
                            @php
                                $isNicu = !empty($req->incubator_reservation_id);
                                $nicuRes = $isNicu ? $req->incubatorReservation : null;
                                $p = $isNicu ? $nicuRes?->patient : $req->visit?->patient;
                                $pName = $isNicu ? ($nicuRes?->baby_title ?: ($nicuRes?->baby_name ?: ($p?->user?->name ? 'طفل ' . $p->user->name : 'طفل خديج'))) : ($p?->name ?? $p?->user?->name ?? 'مريض');
                                $docName = $isNicu ? ($nicuRes?->doctor?->user?->name ?? 'طبيب الخدج') : ($req->visit?->doctor?->user?->name ?? 'الاستشارية');
                                $qNum = $req->visit?->appointment?->queue_number ?? $req->id;
                            @endphp
                            <tr class="inprogress-row">
                                <td><span class="badge bg-info text-white font-monospace fs-6">#{{ $qNum }}</span></td>
                                <td>
                                    <strong class="text-dark">{{ $pName }}</strong>
                                    @if($isNicu)
                                        <small class="text-muted d-block" style="font-size: 0.72rem;">#{{ $nicuRes?->incubator?->name ?? 'حاضنة' }} • ملف: #{{ $p?->national_id ?: ($p?->id ?? '-') }}</small>
                                    @endif
                                </td>
                                <td>{{ implode(', ', $req->radiology_names) ?: ($req->description ?: 'فحص تصوير') }}</td>
                                <td>
                                    <span>د. {{ $docName }}</span>
                                    @if($isNicu)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-block mt-0.5" style="font-size: 0.65rem;">قسم الخدج</span>
                                    @endif
                                </td>
                                <td class="text-center font-monospace small">{{ $req->details['started_at'] ?? $req->updated_at->format('H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-success fw-bold shadow-xs">
                                        <i class="fas fa-edit"></i> كتابة التقرير والنتائج
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle text-success me-1"></i> لا توجد فحوصات جارية داخل غرفة الفحص حالياً
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. COMPLETED TAB (المكتملة والطباعة) -->
        <div class="tab-pane fade {{ $activeTab === 'completed' ? 'show active' : '' }}" id="completed-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-check-circle me-2 text-success"></i>
                    الفحوصات المكتملة الجاهزة للمعاينة والطباعة
                </h5>
                <span class="badge bg-success text-white">{{ $stats['completed'] }} مكتمل</span>
            </div>
            <div class="table-responsive">
                <table class="table unified-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">الدور</th>
                            <th>المريض</th>
                            <th>الفحوصات المنجزة</th>
                            <th>الطبيب المحول</th>
                            <th class="text-center">تاريخ الإنجاز</th>
                            <th class="text-end">الطباعة والمعاينة</th>
                        </tr>
                    </thead>
                    <tbody id="radiology-tab-completed-tbody">
                        @forelse($completedRequests as $req)
                            @php
                                $isNicu = !empty($req->incubator_reservation_id);
                                $nicuRes = $isNicu ? $req->incubatorReservation : null;
                                $p = $isNicu ? $nicuRes?->patient : $req->visit?->patient;
                                $pName = $isNicu ? ($nicuRes?->baby_title ?: ($nicuRes?->baby_name ?: ($p?->user?->name ? 'طفل ' . $p->user->name : 'طفل خديج'))) : ($p?->name ?? $p?->user?->name ?? 'مريض');
                                $docName = $isNicu ? ($nicuRes?->doctor?->user?->name ?? 'طبيب الخدج') : ($req->visit?->doctor?->user?->name ?? 'الاستشارية');
                                $qNum = $req->visit?->appointment?->queue_number ?? $req->id;
                            @endphp
                            <tr class="completed-row">
                                <td><span class="badge bg-success text-white font-monospace fs-6">#{{ $qNum }}</span></td>
                                <td>
                                    <strong class="text-dark">{{ $pName }}</strong>
                                    @if($isNicu)
                                        <small class="text-muted d-block" style="font-size: 0.72rem;">#{{ $nicuRes?->incubator?->name ?? 'حاضنة' }} • ملف: #{{ $p?->national_id ?: ($p?->id ?? '-') }}</small>
                                    @endif
                                </td>
                                <td>{{ implode(', ', $req->radiology_names) ?: ($req->description ?: 'فحص تصوير') }}</td>
                                <td>
                                    <span>د. {{ $docName }}</span>
                                    @if($isNicu)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle d-block mt-0.5" style="font-size: 0.65rem;">قسم الخدج</span>
                                    @endif
                                </td>
                                <td class="text-center font-monospace small">{{ $req->updated_at ? $req->updated_at->format('H:i') : '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-outline-primary" title="عرض وطباعة">
                                        <i class="fas fa-print"></i> عرض / طباعة
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle text-secondary me-1"></i> لا توجد فحوصات مكتملة اليوم حتى الآن
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3.5. PRIOR PENDING TAB (الطلبات المعلقة من أيام سابقة) -->
        <div class="tab-pane fade {{ ($activeTab ?? '') === 'pending' ? 'show active' : '' }}" id="pending-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded border">
                <div>
                    <h5 class="mb-1 text-dark fw-bold">
                        <i class="fas fa-history text-warning me-2"></i>
                        سجل الطلبات المعلقة وغير المكتملة من الأيام السابقة
                    </h5>
                    <small class="text-muted">
                        الفحوصات المطلوبة في تواريخ سابقة وما زالت قيد الانتظار أو الإجراء ولم يتم اعتماد تقريرها النهائي
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ ($stats['prior_pending'] ?? 0) > 0 ? 'bg-danger' : 'bg-success' }} fs-6 px-3 py-2 rounded-pill">
                        <i class="fas {{ ($stats['prior_pending'] ?? 0) > 0 ? 'fa-clock' : 'fa-check' }} me-1"></i>
                        {{ $priorPendingRequests->count() }} طلب معلق
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table unified-table mb-2">
                    <thead>
                        <tr>
                            <th style="width: 80px;"><i class="fas fa-receipt me-1"></i>#الطلب</th>
                            <th><i class="fas fa-user-injured me-2"></i>المريض</th>
                            <th><i class="fas fa-x-ray me-2"></i>الفحوصات المطلوبة</th>
                            <th><i class="fas fa-user-md me-2"></i>الطبيب المحول / العيادة</th>
                            <th style="width: 140px;" class="text-center"><i class="fas fa-calendar-alt me-1"></i>تاريخ الطلب الأصلي</th>
                            <th style="width: 100px;" class="text-center"><i class="fas fa-money-bill-wave me-1"></i>الدفع</th>
                            <th style="width: 110px;" class="text-center"><i class="fas fa-tasks me-1"></i>الحالة</th>
                            <th class="text-end" style="width: 170px;"><i class="fas fa-cogs me-2"></i>الإجراء والمتابعة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($priorPendingRequests as $req)
                            @php
                                $isPaid = $req->payment_status === 'paid';
                                $p = $req->visit?->patient;
                                $pName = $p?->name ?? $p?->user?->name ?? 'مريض';
                            @endphp
                            <tr id="prior-pending-row-{{ $req->id }}">
                                <td class="font-monospace fw-bold text-dark">#{{ $req->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle">
                                            {{ mb_substr($pName, 0, 1) }}
                                        </div>
                                        <div class="ms-2">
                                            <strong class="text-dark">{{ $pName }}</strong>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                @if($p?->gender) <span>{{ $p->gender === 'male' ? 'ذكر' : 'أنثى' }} • </span> @endif
                                                @if($p?->age) <span>{{ $p->age }} سنة</span> @endif
                                                @if($p?->medical_number) <span class="badge bg-light text-muted border ms-1 font-monospace">{{ $p->medical_number }}</span> @endif
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if(!empty($req->radiology_names))
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($req->radiology_names as $rName)
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-bold py-1 px-2">
                                                    <i class="fas fa-x-ray fa-xs me-1"></i> {{ $rName }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted">{{ $req->description ?: 'فحص تصوير' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">د. {{ $req->visit?->doctor?->user?->name ?? 'الاستشارية' }}</div>
                                    <small class="text-muted" style="font-size: 0.72rem;">{{ $req->visit?->department?->name ?? 'العيادات' }}</small>
                                    @if(!empty($req->doctor_notes))
                                        <div class="mt-1">
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle text-truncate d-inline-block" style="max-width: 170px; font-size: 0.68rem;" title="{{ $req->doctor_notes }}">
                                                <i class="fas fa-sticky-note text-warning me-1"></i> {{ $req->doctor_notes }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center font-monospace">
                                    <span class="d-block text-dark fw-bold" style="font-size: 0.85rem;">
                                        {{ $req->created_at ? $req->created_at->format('Y-m-d') : '—' }}
                                    </span>
                                    <span class="badge bg-secondary-subtle text-muted" style="font-size: 0.7rem;">
                                        {{ $req->created_at ? $req->created_at->diffForHumans() : '' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($isPaid)
                                        <span class="badge bg-success text-white py-1 px-2 shadow-xs fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> مدفوع
                                        </span>
                                    @else
                                        <span class="badge bg-danger text-white py-1 px-2 fw-bold">
                                            <i class="fas fa-clock me-1"></i> غير مدفوع
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($req->status === 'in_progress')
                                        <span class="badge bg-info text-white py-1 px-2">
                                            <i class="fas fa-stethoscope me-1"></i> قيد الفحص
                                        </span>
                                    @elseif($req->status === 'calling')
                                        <span class="badge bg-warning text-dark py-1 px-2">
                                            <i class="fas fa-bullhorn me-1"></i> قيد النداء
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary py-1 px-2">
                                            ⏳ معلق
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-primary fw-bold shadow-xs" title="بدء أو استكمال الفحص">
                                            <i class="fas fa-play me-1"></i> بدء / متابعة الفحص
                                        </a>
                                        @if($req->isDirectExternal() && !in_array($req->status, ['completed', 'cancelled']))
                                            <button type="button" 
                                                    class="action-btn btn-outline-danger" 
                                                    title="إلغاء الطلب الخارجي"
                                                    onclick="openCancelRadiologyModal({{ $req->id }}, '{{ addslashes($pName) }}')">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="py-3">
                                        <i class="fas fa-check-circle text-success fa-3x mb-3 d-block opacity-75"></i>
                                        <h6 class="fw-bold text-dark">ممتاز! لا توجد أي طلبات معلقة من أيام سابقة</h6>
                                        <p class="small text-muted mb-0">كافة فحوصات الأيام الماضية مكتملة ومعتمدة بالكامل.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. EMERGENCY TAB (If any) -->
        @if(isset($emergencyRadiologyRequests) && $emergencyRadiologyRequests->count() > 0)
        <div class="tab-pane fade {{ ($activeTab ?? '') === 'emergency' ? 'show active' : '' }}" id="emergency-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-danger bg-opacity-10 rounded border border-danger-subtle">
                <div>
                    <h5 class="mb-1 text-danger fw-bold">
                        <i class="fas fa-ambulance me-2"></i>
                        سجل طلبات فحوصات قسم الطوارئ (الأولوية العاجلة والحرجة)
                    </h5>
                    <small class="text-muted">
                        الفحوصات المطلوبة مباشرة من أطباء طوارئ المستشفى والمصنفة كحالات حرجة وعاجلة
                    </small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ ($stats['emergency'] ?? 0) > 0 ? 'bg-danger' : 'bg-secondary' }} fs-6 px-3 py-2 rounded-pill shadow-xs">
                        <i class="fas fa-bolt me-1"></i>
                        {{ $stats['emergency'] ?? 0 }} قيد الانتظار والفحص
                    </span>
                    <span class="badge bg-light text-dark border fs-6 px-3 py-2 rounded-pill">
                        إجمالي الطوارئ: {{ $emergencyRadiologyRequests->count() }}
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table unified-table mb-2">
                    <thead>
                        <tr>
                            <th style="width: 80px;"><i class="fas fa-receipt me-1"></i>#الطلب</th>
                            <th><i class="fas fa-user-injured me-2"></i>المريض</th>
                            <th><i class="fas fa-x-ray me-2"></i>الفحوصات المطلوبة</th>
                            <th><i class="fas fa-user-md me-2"></i>طبيب الطوارئ المحول</th>
                            <th style="width: 130px;" class="text-center"><i class="fas fa-exclamation-circle me-1"></i>الأولوية والوقت</th>
                            <th style="width: 100px;" class="text-center"><i class="fas fa-money-bill-wave me-1"></i>الدفع</th>
                            <th style="width: 110px;" class="text-center"><i class="fas fa-tasks me-1"></i>الحالة</th>
                            <th class="text-end" style="width: 170px;"><i class="fas fa-cogs me-2"></i>الإجراء والتقرير</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($emergencyRadiologyRequests as $emReq)
                            @php
                                $p = $emReq->patient ?? $emReq->emergency?->patient;
                                $emP = $emReq->emergency?->emergencyPatient;
                                $pName = $p?->name ?? $p?->user?->name ?? $emP?->name ?? 'مريض طوارئ';
                                $pAge = $p?->age ?? $emP?->age;
                                $pGender = $p?->gender ?? $emP?->gender;
                                $pMedNum = $p?->medical_number ?? $emP?->emergency_number;
                                $isPaid = ($emReq->payment_status === 'paid' || $emReq->payment_id);
                                $docName = $emReq->emergency?->doctor?->user?->name ?? $emReq->emergency?->consultantDoctor?->user?->name ?? 'طبيب الطوارئ';
                                $isCritical = ($emReq->priority === 'critical');
                            @endphp
                            <tr class="{{ $emReq->status === 'pending' ? 'table-warning' : ($emReq->status === 'in_progress' ? 'table-info' : '') }}" id="emergency-request-row-{{ $emReq->id }}">
                                <td>
                                    <span class="font-monospace fw-bold text-dark d-block">#{{ $emReq->id }}</span>
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle font-monospace" style="font-size: 0.68rem;">
                                        طوارئ #{{ $emReq->emergency_id }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle {{ $isCritical ? 'bg-danger text-white' : '' }}">
                                            {{ mb_substr($pName, 0, 1) }}
                                        </div>
                                        <div class="ms-2">
                                            <strong class="text-dark">{{ $pName }}</strong>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                @if($pGender) <span>{{ $pGender === 'male' ? 'ذكر' : 'أنثى' }} • </span> @endif
                                                @if($pAge) <span>{{ $pAge }} سنة</span> @endif
                                                @if($pMedNum) <span class="badge bg-light text-muted border ms-1 font-monospace">{{ $pMedNum }}</span> @endif
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($emReq->radiologyTypes as $type)
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-bold py-1 px-2">
                                                <i class="fas fa-x-ray fa-xs me-1"></i> {{ $type->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                    @if(!empty($emReq->notes))
                                        <div class="mt-1">
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle text-truncate d-inline-block" style="max-width: 170px; font-size: 0.68rem;" title="{{ $emReq->notes }}">
                                                <i class="fas fa-sticky-note text-warning me-1"></i> {{ $emReq->notes }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="small fw-semibold text-dark">د. {{ $docName }}</div>
                                    <small class="badge bg-light text-danger border border-danger-subtle" style="font-size: 0.68rem;">
                                        <i class="fas fa-ambulance me-1"></i> قسم الطوارئ
                                    </small>
                                </td>
                                <td class="text-center font-monospace">
                                    <span class="badge bg-{{ $isCritical ? 'danger' : 'warning text-dark' }} py-1 px-2 fw-bold d-inline-block">
                                        <i class="fas {{ $isCritical ? 'fa-exclamation-triangle' : 'fa-bolt' }} me-1"></i>
                                        {{ $emReq->priority_text }}
                                    </span>
                                    <small class="d-block text-muted mt-1" style="font-size: 0.72rem;">
                                        <i class="fas fa-clock me-1"></i> {{ optional($emReq->requested_at)->format('H:i') }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    @if($isPaid)
                                        <span class="badge bg-success text-white py-1 px-2 shadow-xs fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> مدفوع
                                        </span>
                                    @elseif($emReq->payment_status === 'refunded')
                                        <span class="badge bg-secondary text-white py-1 px-2 fw-bold">
                                            <i class="fas fa-undo me-1"></i> مسترجع
                                        </span>
                                    @else
                                        <span class="badge bg-danger text-white py-1 px-2 fw-bold">
                                            <i class="fas fa-clock me-1"></i> غير مدفوع
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($emReq->status === 'in_progress')
                                        <span class="badge bg-info text-white py-1 px-2 fw-bold">
                                            <i class="fas fa-stethoscope me-1"></i> قيد الفحص
                                        </span>
                                    @elseif($emReq->status === 'completed')
                                        <span class="badge bg-success text-white py-1 px-2 fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> مكتمل
                                        </span>
                                    @elseif($emReq->status === 'cancelled')
                                        <span class="badge bg-danger-subtle text-danger py-1 px-2">
                                            ملغي
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-2 fw-bold">
                                            ⏳ بالانتظار
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        @if($emReq->status === 'pending')
                                            <form action="{{ route('staff.emergency-radiology.start', $emReq) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="action-btn btn-primary fw-bold shadow-xs" title="بدء فحص الطوارئ">
                                                    <i class="fas fa-play me-1"></i> بدء الفحص
                                                </button>
                                            </form>
                                        @elseif($emReq->status === 'in_progress')
                                            <a href="{{ route('staff.emergency-radiology.show', $emReq) }}" class="action-btn btn-success fw-bold shadow-xs" title="إدخال النتائج والتقرير">
                                                <i class="fas fa-edit me-1"></i> إدخال التقرير
                                            </a>
                                        @elseif($emReq->status === 'completed')
                                            <a href="{{ route('staff.emergency-radiology.print', $emReq) }}" class="action-btn btn-outline-primary fw-bold shadow-xs" target="_blank" title="عرض وطباعة التقرير">
                                                <i class="fas fa-print me-1"></i> عرض / طباعة
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <div class="py-3">
                                        <i class="fas fa-ambulance text-secondary fa-3x mb-3 d-block opacity-50"></i>
                                        <h6 class="fw-bold text-dark">لا توجد طلبات أشعة طارئة حالياً</h6>
                                        <p class="small text-muted mb-0">جميع طلبات الطوارئ معالجة أو لا توجد حالات محولة.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- 4.2 NICU / INCUBATORS TAB -->
        <div class="tab-pane fade" id="nicu-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-baby me-2 text-primary"></i>
                    طلبات أشعة وسونار وإيكو الأطفال الخدج والحاضنات
                </h5>
                <span class="badge bg-primary text-white">{{ $nicuRadiologyRequests->count() }} طلب فحص</span>
            </div>

            @if($nicuRadiologyRequests->count() > 0)
                <div class="table-responsive">
                    <table class="table unified-table">
                        <thead>
                            <tr>
                                <th style="width: 75px;"><i class="fas fa-hashtag me-1"></i>#الطلب</th>
                                <th><i class="fas fa-baby me-2"></i>الطفل / الحاضنة</th>
                                <th><i class="fas fa-x-ray me-2"></i>الفحص والتصوير المطلوب</th>
                                <th style="width: 100px;" class="text-center"><i class="fas fa-bolt me-1"></i>الأولوية</th>
                                <th><i class="fas fa-user-md me-2"></i>الطبيب المشرف</th>
                                <th style="width: 100px;" class="text-center"><i class="fas fa-clock me-1"></i>الوقت</th>
                                <th style="width: 140px;" class="text-center"><i class="fas fa-file-invoice-dollar me-1"></i>الحساب المالي</th>
                                <th style="width: 130px;" class="text-center"><i class="fas fa-tasks me-1"></i>الحالة</th>
                                <th class="text-end" style="width: 170px;"><i class="fas fa-cogs me-2"></i>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($nicuRadiologyRequests as $nicuRad)
                                @php
                                    $isMedRequest = $nicuRad instanceof \App\Models\Request;
                                    $isCompleted = $nicuRad->status === 'completed';
                                    $isInProgress = $nicuRad->status === 'in_progress';
                                    
                                    if ($isMedRequest) {
                                        $res = $nicuRad->incubatorReservation;
                                        $babyName = $res?->baby_name ?: ($res?->baby_title ?: ('طفل ' . ($res?->patient?->name ?? 'غير محدد')));
                                        $incubatorName = '#' . ($res?->incubator?->name ?? $res?->incubator_id);
                                        $docName = $res?->doctor?->user?->name ?? 'طبيب الخدج المشرف';
                                        $reqDetails = is_string($nicuRad->details) ? json_decode($nicuRad->details, true) : ($nicuRad->details ?? []);
                                        $radNames = $nicuRad->radiology_names ?: [$nicuRad->description ?: 'فحص تصوير'];
                                        $priority = $reqDetails['priority'] ?? 'normal';
                                        $notes = $reqDetails['doctor_notes'] ?? null;
                                        $orderedAt = $nicuRad->created_at;
                                        $fileNo = $res?->patient?->national_id ?: ($res?->patient_id ?? '-');
                                    } else {
                                        $babyName = $nicuRad->reservation?->baby_name ?: 'طفل ' . ($nicuRad->reservation?->patient?->name ?? 'غير محدد');
                                        $incubatorName = $nicuRad->reservation?->incubator?->name ?? ('حاضنة #' . $nicuRad->reservation?->incubator_id);
                                        $docName = $nicuRad->reservation?->doctor?->user?->name ?? 'طبيب الخدج';
                                        $radNames = [$nicuRad->item_name];
                                        $priority = $nicuRad->priority;
                                        $notes = $nicuRad->notes;
                                        $orderedAt = $nicuRad->ordered_at ?? $nicuRad->created_at;
                                        $fileNo = $nicuRad->reservation?->patient?->national_id ?: ($nicuRad->reservation?->patient_id ?? '-');
                                    }

                                    $isStat = in_array(strtolower($priority), ['stat', 'critical', 'emergency']);
                                    $prioColor = match(strtolower($priority)) {
                                        'stat', 'critical', 'emergency' => 'danger',
                                        'urgent' => 'warning text-dark',
                                        default => 'secondary'
                                    };
                                    $prioText = match(strtolower($priority)) {
                                        'stat', 'critical', 'emergency' => '🚨 طارئ جداً (STAT)',
                                        'urgent' => '⚡ عاجل (Urgent)',
                                        default => 'عادي (Normal)'
                                    };
                                @endphp
                                <tr style="{{ $isCompleted ? 'border-right: 4px solid #16a34a; background-color: #fafdfb;' : ($isInProgress ? 'border-right: 4px solid #0284c7; background-color: #f0f9ff;' : ($isStat ? 'border-right: 4px solid #dc2626; background-color: #fef2f2;' : 'border-right: 4px solid #f59e0b; background-color: #ffffff;')) }}">
                                    <td class="font-monospace text-muted">#{{ $nicuRad->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle" style="background: #e0f2fe; color: #0284c7; border-color: #bae6fd;">
                                                <i class="fas fa-baby"></i>
                                            </div>
                                            <div class="ms-2">
                                                <strong class="text-dark d-block">
                                                    {{ $babyName }}
                                                </strong>
                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-1">
                                                        {{ $incubatorName }}
                                                    </span>
                                                    • ملف: #{{ $fileNo }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($radNames as $rn)
                                                <span class="badge bg-info-subtle text-dark border border-info-subtle font-monospace px-2 py-1">
                                                    <i class="fas fa-x-ray text-info me-1"></i> {{ $rn }}
                                                </span>
                                            @endforeach
                                        </div>
                                        @if($notes)
                                            <small class="text-muted d-block text-truncate mt-1" style="max-width: 250px;" title="{{ $notes }}">
                                                <i class="fas fa-info-circle text-info me-1"></i> {{ $notes }}
                                            </small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $prioColor }} fw-bold">
                                            {{ $prioText }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-dark">د. {{ $docName }}</div>
                                        <small class="text-muted" style="font-size: 0.72rem;">قسم العناية المركزة لحديثي الولادة</small>
                                    </td>
                                    <td class="text-center font-monospace small">
                                        {{ $orderedAt ? $orderedAt->format('H:i') : '—' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2">
                                            <i class="fas fa-file-invoice-dollar me-1"></i> حساب الإقامة (الخدج)
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if($isCompleted)
                                            <span class="badge bg-success py-1.5 px-2.5 shadow-xs fw-semibold">
                                                <i class="fas fa-check-circle me-1"></i> مكتمل ومعتمد
                                            </span>
                                        @elseif($isInProgress)
                                            <span class="badge bg-info text-white py-1.5 px-2.5 shadow-xs fw-semibold">
                                                <i class="fas fa-spinner fa-spin me-1"></i> قيد الفحص
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark py-1.5 px-2.5 shadow-xs fw-bold">
                                                <i class="fas fa-clock me-1"></i> بانتظار الفحص
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-1 justify-content-end align-items-center">
                                            @if($isMedRequest)
                                                <a href="{{ route('radiology-staff.show', $nicuRad->id) }}" class="btn btn-sm btn-info text-dark fw-bold shadow-xs px-2.5 py-1">
                                                    <i class="fas fa-edit me-1"></i> {{ $isCompleted ? 'مراجعة التقرير' : 'بدء الفحص والتقرير' }}
                                                </a>
                                                @if($isCompleted)
                                                    <a href="{{ route('radiology-staff.print', $nicuRad->id) }}" class="btn btn-sm btn-outline-dark fw-bold px-2 py-1 shadow-xs" target="_blank" title="طباعة التقرير">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                @endif
                                            @else
                                                @if($nicuRad->status === 'pending')
                                                    <form action="{{ route('radiology-staff.nicu-orders.start', $nicuRad) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary fw-bold shadow-xs px-2 py-1">
                                                            <i class="fas fa-play me-1"></i> بدء الفحص
                                                        </button>
                                                    </form>
                                                @elseif($nicuRad->status === 'in_progress')
                                                    <button type="button" class="btn btn-sm btn-success fw-bold shadow-xs px-2 py-1" data-bs-toggle="modal" data-bs-target="#completeNicuRadModal{{ $nicuRad->id }}">
                                                        <i class="fas fa-edit me-1"></i> إدخال التقرير
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-outline-primary px-2 py-1" data-bs-toggle="modal" data-bs-target="#completeNicuRadModal{{ $nicuRad->id }}" title="تعديل التقرير">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="{{ route('radiology-staff.nicu-orders.print', $nicuRad) }}" class="btn btn-sm btn-success fw-bold px-2 py-1 shadow-xs" target="_blank" title="طباعة">
                                                        <i class="fas fa-print me-1"></i> طباعة
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 bg-light rounded-3">
                    <i class="fas fa-baby fa-3x text-muted opacity-50 mb-3"></i>
                    <h6 class="text-dark fw-bold">لا توجد طلبات أشعة أو سونار مسجلة للخدج حالياً</h6>
                    <p class="text-muted small mb-0">ستظهر الفحوصات هنا فور طلبها من محطة طبيب الخدج والحاضنات.</p>
                </div>
            @endif
        </div>

        <!-- 5. ALL / ARCHIVE TAB -->
        <div class="tab-pane fade {{ $activeTab === 'all' || ($sourceFilter ?? '') === 'all' ? 'show active' : '' }}" id="all-pane" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-list text-secondary me-2"></i>
                    سجل كافة طلبات وأرشيف الأشعة والتصوير الطبي
                </h5>
                <span class="badge bg-secondary text-white">{{ $requests->total() }} طلب إجمالي</span>
            </div>

            <div class="table-responsive">
                <table class="table unified-table">
                    <thead>
                        <tr>
                            <th>#الطلب</th>
                            <th>المريض</th>
                            <th>الفحوصات المطلوبة</th>
                            <th>الطبيب المحول</th>
                            <th>التاريخ</th>
                            <th>الدفع</th>
                            <th>الحالة</th>
                            <th class="text-end">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requests as $req)
                        <tr>
                            <td class="font-monospace">#{{ $req->id }}</td>
                            <td class="fw-bold">{{ $req->visit?->patient?->name ?? 'مريض' }}</td>
                            <td>{{ implode(', ', $req->radiology_names) ?: ($req->description ?: 'فحص تصوير') }}</td>
                            <td>د. {{ $req->visit?->doctor?->user?->name ?? 'الاستشارية' }}</td>
                            <td>{{ $req->created_at ? $req->created_at->format('Y-m-d H:i') : '—' }}</td>
                            <td>
                                @if($req->payment_status === 'paid')
                                    <span class="badge bg-success">مدفوع</span>
                                @else
                                    <span class="badge bg-danger">غير مدفوع</span>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary">{{ $req->status }}</span></td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end align-items-center">
                                    <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-outline-primary">
                                        <i class="fas fa-eye"></i> عرض
                                    </a>
                                    @if($req->isDirectExternal() && !in_array($req->status, ['completed', 'cancelled']))
                                        <button type="button" 
                                                class="action-btn btn-outline-danger" 
                                                title="إلغاء الطلب الخارجي"
                                                onclick="openCancelRadiologyModal({{ $req->id }}, '{{ addslashes($req->visit?->patient?->name ?? 'المريض') }}')">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
                <div class="d-flex justify-content-center">
                    {{ $requests->appends(request()->query())->links() }}
                </div>
            @endif
        </div>

    </div>

</div>

<!-- Audio Chime Synth & Realtime Queue Engine -->
<script>
const radiologyBaseUrl = '{{ url('/') }}';
let currentSelectedCategory = '{{ $selectedCategory }}';
let currentCallingRequestId = null;
let currentCallingVisitId = null;

// Web Audio API Ding-Dong Chime Synth
function playChimeSound() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const audioCtx = new AudioContext();
        const now = audioCtx.currentTime;
        
        // Tone 1: D5
        const osc1 = audioCtx.createOscillator();
        const gain1 = audioCtx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(587.33, now);
        gain1.gain.setValueAtTime(0.35, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
        osc1.connect(gain1);
        gain1.connect(audioCtx.destination);
        osc1.start(now);
        osc1.stop(now + 0.6);

        // Tone 2: A5
        const osc2 = audioCtx.createOscillator();
        const gain2 = audioCtx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(880, now + 0.2);
        gain2.gain.setValueAtTime(0.35, now + 0.2);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.9);
        osc2.connect(gain2);
        gain2.connect(audioCtx.destination);
        osc2.start(now + 0.2);
        osc2.stop(now + 0.9);
    } catch(e) {
        console.log('Audio not allowed or supported', e);
    }
}

// 1. Realtime Sync Function (Silent JSON Polling)
async function syncRadiologyQueue() {
    try {
        const res = await fetch(`${radiologyBaseUrl}/radiology-staff/queue-status?category=${currentSelectedCategory}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!res.ok) return;
        const data = await res.json();
        if (!data.success) return;

        // 1. Update Control Bar
        const currentPatient = data.current_patient;
        const numEl = document.getElementById('radiology-station-current-num');
        const nameEl = document.getElementById('radiology-station-current-name');
        const badgeEl = document.getElementById('radiology-station-status-badge');
        const statusEl = document.getElementById('radiology-station-current-status');
        const btnCallNext = document.getElementById('btn-call-next');
        const btnRecall = document.getElementById('btn-recall');
        const btnStartExam = document.getElementById('btn-start-exam');
        const btnSkip = document.getElementById('btn-skip');

        if (currentPatient) {
            currentCallingRequestId = currentPatient.id;
            currentCallingVisitId = currentPatient.visit_id;

            numEl.textContent = `الدور: #${currentPatient.queue_number}`;
            nameEl.textContent = currentPatient.name;
            
            if (currentPatient.status === 'in_progress') {
                badgeEl.className = 'status-badge status-completed';
                badgeEl.innerHTML = '<i class="fas fa-stethoscope me-1"></i> داخل الغرفة قيد الفحص';
                btnCallNext.style.display = 'none';
                btnRecall.style.display = 'none';
                btnStartExam.style.display = 'inline-flex';
                btnStartExam.innerHTML = '<i class="fas fa-edit me-1"></i> كتابة التقرير والنتائج';
                btnSkip.style.display = 'none';
                statusEl.textContent = `الفحوصات: ${currentPatient.radiology_names.join(', ')} • د. ${currentPatient.doctor_name}`;
            } else {
                badgeEl.className = 'status-badge status-calling';
                badgeEl.innerHTML = '<i class="fas fa-bullhorn me-1"></i> قيد النداء الآن 📢';
                btnCallNext.style.display = 'none';
                btnRecall.style.display = 'inline-flex';
                btnStartExam.style.display = 'inline-flex';
                btnStartExam.innerHTML = '<i class="fas fa-door-open me-1"></i> إدخال وبدء الفحص';
                btnSkip.style.display = 'inline-flex';
                statusEl.textContent = `تم النداء: ${currentPatient.called_at} • ${currentPatient.radiology_names.join(', ')}`;
            }
        } else {
            currentCallingRequestId = null;
            currentCallingVisitId = null;
            numEl.textContent = 'الدور: -';
            nameEl.textContent = 'لا يوجد مريض مستدعى';
            badgeEl.className = 'status-badge status-pending';
            badgeEl.textContent = 'غرفة الفحص جاهزة';
            btnCallNext.style.display = 'inline-flex';
            btnRecall.style.display = 'none';
            btnStartExam.style.display = 'none';
            btnSkip.style.display = 'none';
            statusEl.textContent = 'اضغط "استدعاء التالي" لمناداة أول مريض مسدد في طابور الانتظار';
        }

        // 2. Update KPI Badges
        if (data.stats) {
            document.getElementById('badge-tab-waiting-count').textContent = data.stats.waiting;
            document.getElementById('badge-tab-inprogress-count').textContent = data.stats.in_progress;
            document.getElementById('badge-tab-completed-count').textContent = data.stats.completed;
            if (document.getElementById('badge-tab-pending-count') && data.stats.prior_pending !== undefined) {
                document.getElementById('badge-tab-pending-count').textContent = data.stats.prior_pending;
                if (data.stats.prior_pending > 0) {
                    document.getElementById('badge-tab-pending-count').className = 'badge bg-danger text-white rounded-pill px-2 py-1';
                } else {
                    document.getElementById('badge-tab-pending-count').className = 'badge bg-secondary text-white rounded-pill px-2 py-1';
                }
            }
            document.getElementById('badge-live-waiting-count').textContent = `${data.stats.waiting} منتظر`;
            document.getElementById('badge-live-inprogress-count').textContent = `${data.stats.in_progress} قيد الفحص`;

            // Update Executive KPI Strip Cards
            if (document.getElementById('kpi-waiting-count')) document.getElementById('kpi-waiting-count').textContent = data.stats.waiting;
            if (document.getElementById('kpi-inprogress-count')) document.getElementById('kpi-inprogress-count').textContent = data.stats.in_progress;
            if (document.getElementById('kpi-completed-count')) document.getElementById('kpi-completed-count').textContent = data.stats.completed;
            if (document.getElementById('kpi-pending-count') && data.stats.prior_pending !== undefined) document.getElementById('kpi-pending-count').textContent = data.stats.prior_pending;
        }

        // 3. Render Waiting List Table
        const waitingTbody = document.getElementById('radiology-station-waiting-list');
        if (waitingTbody) {
            if (data.waiting_list.length === 0) {
                waitingTbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle text-success me-1"></i> لا يوجد مرضى في طابور الانتظار حالياً
                        </td>
                    </tr>
                `;
            } else {
                waitingTbody.innerHTML = data.waiting_list.map(r => {
                    const isCalling = r.status === 'calling';
                    return `
                        <tr class="${isCalling ? 'calling-row fw-bold' : ''}" id="request-row-${r.id}">
                            <td>
                                <span class="badge ${isCalling ? 'bg-warning text-dark' : 'bg-dark bg-opacity-10 text-dark border'} px-2 py-1 font-monospace fs-6">
                                    #${r.queue_number}
                                </span>
                            </td>
                            <td class="font-monospace text-muted">#${r.id}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle">
                                        ${r.name.charAt(0)}
                                    </div>
                                    <div class="ms-2">
                                        <strong class="text-dark">${r.name}</strong>
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                                            ${r.gender ? r.gender + ' • ' : ''}${r.age ? r.age + ' سنة' : ''}
                                            ${r.medical_number ? '<span class="badge bg-light text-muted border ms-1 font-monospace">' + r.medical_number + '</span>' : ''}
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    ${r.radiology_names.map(rn => `
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-bold py-1 px-2">
                                            <i class="fas fa-x-ray fa-xs me-1"></i> ${rn}
                                        </span>
                                    `).join('')}
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark">د. ${r.doctor_name}</div>
                                <small class="text-muted" style="font-size: 0.72rem;">${r.department_name}</small>
                            </td>
                            <td class="text-center font-monospace small">${r.created_time}</td>
                            <td class="text-center">
                                ${r.is_paid 
                                    ? '<span class="badge bg-success text-white py-1 px-2 shadow-xs fw-bold"><i class="fas fa-check-circle me-1"></i> مدفوع</span>'
                                    : '<span class="badge bg-danger text-white py-1 px-2 fw-bold"><i class="fas fa-clock me-1"></i> غير مدفوع</span>'
                                }
                            </td>
                            <td class="text-center">
                                ${isCalling 
                                    ? '<span class="badge bg-warning text-dark border border-warning py-1 px-2 fw-bold"><i class="fas fa-bullhorn me-1"></i> قيد النداء</span>'
                                    : '<span class="badge bg-secondary-subtle text-secondary py-1 px-2">⏳ بالانتظار</span>'
                                }
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end align-items-center">
                                    ${currentSelectedCategory === 'ultrasound' ? (
                                        r.is_paid ? `
                                            <button type="button" class="action-btn btn-warning text-dark fw-bold shadow-xs" onclick="radiologyCallSpecific(${r.id}, '${r.name}')" title="مناداة واستدعاء للغرفة">
                                                <i class="fas fa-bullhorn"></i> نداء
                                            </button>
                                        ` : `
                                            <a href="${radiologyBaseUrl}/radiology-staff/requests/${r.id}/show" class="action-btn btn-outline-secondary" title="معاينة الطلب">
                                                <i class="fas fa-eye"></i> معاينة
                                            </a>
                                        `
                                    ) : `
                                        <a href="${radiologyBaseUrl}/radiology-staff/requests/${r.id}/show" class="action-btn btn-primary fw-bold shadow-xs" title="بدء الفحص وكتابة التقرير">
                                            <i class="fas fa-play me-1"></i> بدء الفحص
                                        </a>
                                    `}
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }

        // 4. Render In-Progress Table
        const inprogTbody = document.getElementById('radiology-station-inprogress-list');
        const inprogTabTbody = document.getElementById('radiology-tab-inprogress-tbody');
        if (inprogTbody) {
            if (data.in_progress_list.length === 0) {
                inprogTbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fas fa-check-circle text-success me-1"></i> لا يوجد مريض داخل غرفة الفحص حالياً
                        </td>
                    </tr>
                `;
            } else {
                const inprogRows = data.in_progress_list.map(r => `
                    <tr class="inprogress-row">
                        <td><span class="badge bg-info text-white font-monospace fs-6">#${r.queue_number}</span></td>
                        <td><strong class="text-dark">${r.name}</strong></td>
                        <td>${r.radiology_names.join(', ')}</td>
                        <td>د. ${r.doctor_name}</td>
                        <td class="text-center font-monospace small">${r.started_time}</td>
                        <td class="text-end">
                            <a href="${radiologyBaseUrl}/radiology-staff/requests/${r.id}/show" class="action-btn btn-success fw-bold shadow-xs">
                                <i class="fas fa-edit"></i> كتابة التقرير
                            </a>
                        </td>
                    </tr>
                `).join('');
                inprogTbody.innerHTML = inprogRows;
                if (inprogTabTbody) inprogTabTbody.innerHTML = inprogRows;
            }
        }

        // 5. Render Completed Tab
        if (inprogTabTbody && inprogTabTbody.children.length === 0 && data.in_progress_list.length === 0) {
            inprogTabTbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">لا توجد فحوصات جارية حالياً</td></tr>`;
        }

        const compTbody = document.getElementById('radiology-tab-completed-tbody');
        if (compTbody && data.completed_list) {
            if (data.completed_list.length === 0) {
                compTbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">لا توجد فحوصات مكتملة اليوم حتى الآن</td></tr>`;
            } else {
                compTbody.innerHTML = data.completed_list.map(r => `
                    <tr class="completed-row">
                        <td><span class="badge bg-success text-white font-monospace fs-6">#${r.queue_number}</span></td>
                        <td><strong class="text-dark">${r.name}</strong></td>
                        <td>${r.radiology_names.join(', ')}</td>
                        <td>د. ${r.doctor_name}</td>
                        <td class="text-center font-monospace small">${r.completed_time}</td>
                        <td class="text-end">
                            <a href="${radiologyBaseUrl}/radiology-staff/requests/${r.id}/show" class="action-btn btn-outline-primary" title="عرض وطباعة">
                                <i class="fas fa-print"></i> عرض / طباعة
                            </a>
                        </td>
                    </tr>
                `).join('');
            }
        }

        document.getElementById('last-update-time').textContent = 'آخر تحديث: ' + new Date().toLocaleTimeString('ar-IQ');

    } catch (err) {
        console.error('Radiology queue sync error:', err);
    }
}

// Speech Synthesis & Voice Announcement
let availableVoices = [];
function loadVoices() {
    if ('speechSynthesis' in window) {
        availableVoices = window.speechSynthesis.getVoices() || [];
    }
}
if ('speechSynthesis' in window) {
    loadVoices();
    window.speechSynthesis.onvoiceschanged = loadVoices;
}

async function speakAnnouncement(patientName, queueNumber) {
    if (!patientName) return;
    
    // Play chime sound first
    playChimeSound();
    
    const text = queueNumber 
        ? `المراجع ${patientName}، دورك رقم ${queueNumber}، تفضل لغرفة الفحص.`
        : `المراجع ${patientName}، تفضل لغرفة الفحص.`;

    // Wait 500ms after chime
    await new Promise(r => setTimeout(r, 500));

    try {
        const audioUrl = `/queue/tts?text=${encodeURIComponent(text)}`;
        const audio = new Audio(audioUrl);
        const playProm = audio.play();
        if (playProm !== undefined) {
            playProm.catch(() => {
                fallbackSpeech(text);
            });
        }
    } catch (e) {
        fallbackSpeech(text);
    }
}

function fallbackSpeech(text) {
    if (!('speechSynthesis' in window)) return;
    try {
        if (window.speechSynthesis.paused) window.speechSynthesis.resume();
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'ar-SA';
        utterance.rate = 0.88;
        utterance.pitch = 1.0;
        if (availableVoices.length === 0) loadVoices();
        const arVoice = availableVoices.find(v => v.lang && (v.lang.startsWith('ar') || v.name.toLowerCase().includes('arabic')));
        if (arVoice) utterance.voice = arVoice;
        window.speechSynthesis.speak(utterance);
    } catch (err) {}
}

// 2. Action Handlers
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

async function radiologyCallNext() {
    const btn = document.getElementById('btn-call-next');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الاستدعاء...';
    try {
        const res = await fetch(`${radiologyBaseUrl}/radiology-staff/call-next?category=${currentSelectedCategory}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        });
        const data = await res.json();
        if (res.ok && data.success) {
            speakAnnouncement(data.patient_name, data.queue_number);
            if (typeof toastr !== 'undefined') toastr.success(data.message || 'تم استدعاء المريض بنجاح.');
            syncRadiologyQueue();
        } else {
            if (typeof toastr !== 'undefined') toastr.warning(data.message || 'لا يوجد مرضى في طابور الانتظار');
            else alert(data.message || 'لا يوجد مرضى في طابور الانتظار');
        }
    } catch(e) {
        alert('حدث خطأ في الاتصال');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-bullhorn me-1"></i> استدعاء التالي';
    }
}

async function radiologyRecall() {
    const btn = document.getElementById('btn-recall');
    btn.disabled = true;
    try {
        const res = await fetch(`${radiologyBaseUrl}/radiology-staff/recall`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        });
        const data = await res.json();
        if (res.ok && data.success) {
            const curName = document.getElementById('radiology-station-current-name')?.textContent || data.patient_name;
            speakAnnouncement(data.patient_name || curName, data.queue_number);
            if (typeof toastr !== 'undefined') toastr.success('تمت إعادة المناداة على المريض بنجاح.');
            syncRadiologyQueue();
        }
    } catch(e) {
        alert('حدث خطأ في الاتصال');
    } finally {
        btn.disabled = false;
    }
}

async function radiologyStartExam() {
    if (!currentCallingRequestId) return;
    window.location.href = `${radiologyBaseUrl}/radiology-staff/requests/${currentCallingRequestId}/show`;
}

async function radiologySkip() {
    if (!confirm('هل تريد تأخير دور هذا المريض ونقله لآخر الطابور؟')) return;
    try {
        const res = await fetch(`${radiologyBaseUrl}/radiology-staff/skip`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        });
        const data = await res.json();
        if (res.ok && data.success) {
            if (typeof toastr !== 'undefined') toastr.info(data.message);
            syncRadiologyQueue();
        }
    } catch(e) {
        alert('حدث خطأ في الاتصال');
    }
}

async function radiologyCallSpecific(reqId, patientName) {
    try {
        const res = await fetch(`${radiologyBaseUrl}/radiology-staff/requests/${reqId}/call`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        });
        const data = await res.json();
        if (res.ok && data.success) {
            speakAnnouncement(data.patient_name || patientName, data.queue_number);
            if (typeof toastr !== 'undefined') toastr.success(data.message || `تمت المناداة على ${patientName}`);
            syncRadiologyQueue();
        } else {
            const err = data.message || 'حدث خطأ أثناء المناداة';
            if (typeof toastr !== 'undefined') toastr.error(err);
            else alert(err);
        }
    } catch(e) {
        alert('حدث خطأ في الاتصال');
    }
}

function radiologyStartSpecific(reqId) {
    window.location.href = `${radiologyBaseUrl}/radiology-staff/requests/${reqId}/show`;
}

function openCancelRadiologyModal(requestId, patientName) {
    const modalEl = document.getElementById('cancelRadiologyModal');
    const form = document.getElementById('cancelRadiologyForm');
    const patientNameEl = document.getElementById('cancelRadModalPatientName');
    
    if (patientNameEl) patientNameEl.textContent = patientName;
    if (form) form.action = `/radiology-staff/requests/${requestId}/cancel`;
    
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

@if(($dateFilter ?? 'today') === 'today')
// Auto poll every 4s (matching doctor's main station)
setInterval(syncRadiologyQueue, 4000);
syncRadiologyQueue();
@endif
</script>

<!-- مودال إلغاء الطلب الخارجي المباشر للأشعة -->
<div class="modal fade" id="cancelRadiologyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i> تأكيد إلغاء طلب الأشعة والتصوير الخارجي
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="cancelRadiologyForm" method="POST">
                @csrf
                <div class="modal-body p-4 text-end">
                    <p class="fs-6 text-dark mb-2">
                        هل أنت متأكد من رغبتك في إلغاء طلب الأشعة والتصوير الخارجي للمريض <strong id="cancelRadModalPatientName" class="text-danger"></strong>؟
                    </p>
                    <div class="alert alert-warning py-2 px-3 small border-0 mb-3">
                        <i class="fas fa-info-circle me-1"></i> سيتم إلغاء الطلب فوراً وإزالته من طابور الفحص والمناداة. في حال كان الطلب مسدداً مالياً، سيتم توجيه المريض للكاشير لاسترداد المبلغ.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">سبب الإلغاء (اختياري):</label>
                        <input type="text" name="cancel_reason" class="form-control form-control-sm" placeholder="مثال: رغبة المريض، مغادرة المستشفى..." value="إلغاء الطلب الخارجي بطلب من المريض">
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">تراجع</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4">
                        <i class="fas fa-check me-1"></i> نعم، إلغاء الطلب الآن
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@if(isset($nicuRadiologyRequests) && $nicuRadiologyRequests->count() > 0)
    @foreach($nicuRadiologyRequests as $nicuRad)
        <div class="modal fade" id="completeNicuRadModal{{ $nicuRad->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold">
                            <i class="fas fa-x-ray me-2"></i> توثيق تقرير الأشعة / السونار للطفل الخديج
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('radiology-staff.nicu-orders.complete', $nicuRad) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body p-4 text-end">
                            <div class="bg-light p-3 rounded-3 mb-3 border">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <span class="text-muted small d-block">الطفل / المريض:</span>
                                        <strong class="text-dark">{{ $nicuRad->reservation?->baby_name ?: 'طفل ' . ($nicuRad->reservation?->patient?->name ?? 'غير محدد') }}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted small d-block">الحاضنة:</span>
                                        <span class="badge bg-primary">#{{ $nicuRad->reservation?->incubator?->name ?? $nicuRad->reservation?->incubator_id }}</span>
                                    </div>
                                    <div class="col-md-3">
                                        <span class="text-muted small d-block">نوع الفحص:</span>
                                        <strong class="text-primary">{{ $nicuRad->item_name }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold text-dark small">التقرير الشعاعي / النتائج والمشاهدات التشخيصية (Findings & Impression) <span class="text-danger">*</span></label>
                                    <textarea name="result" class="form-control" rows="5" placeholder="اكتب تقرير الفحص الطبي بالتفصيل والمشاهدات السريرية..." required>{{ $nicuRad->result }}</textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold text-dark small">توصيات أخصائي الأشعة / ملاحظات فنية إضافية</label>
                                    <textarea name="technician_notes" class="form-control" rows="2" placeholder="اكتب أي توصيات للمتابعة أو إعادة الفحص...">{{ $nicuRad->technician_notes }}</textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold text-dark small">إرفاق صورة الأشعة / السونار أو ملف DICOM/PDF (اختياري)</label>
                                    <input type="file" name="attachment" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.dcm">
                                    @if($nicuRad->attachment_path)
                                        <div class="mt-2 small">
                                            <i class="fas fa-paperclip text-primary me-1"></i> مرفق حالي: 
                                            <a href="{{ asset('storage/' . $nicuRad->attachment_path) }}" target="_blank" class="fw-semibold text-primary">
                                                {{ $nicuRad->attachment_name ?: 'عرض المرفق' }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light justify-content-between">
                            <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm">
                                <i class="fas fa-check-circle me-1"></i> حفظ واعتماد تقرير الفحص
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endif

<!-- مودال المعاينة السريعة لبيانات فحص الأشعة -->
<div class="modal fade" id="quickViewRadiologyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-file-medical me-2"></i> تفاصيل طلب الفحص <span id="qv-req-id" class="font-monospace"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-end">
                <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded border">
                    <div class="avatar-circle fs-4" id="qv-avatar">م</div>
                    <div>
                        <h5 class="mb-0 fw-bold text-dark" id="qv-patient-name"></h5>
                        <small class="text-muted" id="qv-patient-info"></small>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="small text-muted fw-bold d-block">الطبيب المحول:</label>
                        <span class="fw-semibold text-dark" id="qv-doctor-name"></span>
                    </div>
                    <div class="col-6">
                        <label class="small text-muted fw-bold d-block">حالة السداد:</label>
                        <span id="qv-payment"></span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold d-block">الفحوصات المطلوبة:</label>
                    <div id="qv-tests" class="d-flex flex-wrap gap-1 mt-1"></div>
                </div>
                <div class="mb-3">
                    <label class="small text-muted fw-bold d-block">ملاحظات الطبيب المعالج:</label>
                    <div class="p-2 bg-warning-subtle rounded border border-warning-subtle text-dark small" id="qv-notes"></div>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">إغلاق</button>
                <a href="#" id="qv-action-btn" class="btn btn-primary fw-bold px-4">
                    <i class="fas fa-play me-1"></i> فتح محطة الفحص
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function switchRadiologyTab(tabId) {
    const tabEl = document.getElementById(tabId);
    if (tabEl) {
        bootstrap.Tab.getOrCreateInstance(tabEl).show();
    }
}

// Sync active tab inputs & department cards when tabs change
document.addEventListener('DOMContentLoaded', function() {
    const tabTriggers = document.querySelectorAll('#radiologyTabs button[data-bs-toggle="tab"]');
    tabTriggers.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.id;
            const tabName = target.replace('-tab', '');
            
            // مزامنة حقل التبويب المخفي في نموذج الفلترة والتاريخ
            const tabInput = document.getElementById('radiology-active-tab-input');
            const sourceInput = document.getElementById('radiology-source-input');
            if (tabInput) tabInput.value = tabName;
            if (sourceInput) {
                if (tabName === 'station') sourceInput.value = 'consultation';
                else if (tabName === 'direct') sourceInput.value = 'direct';
                else if (tabName === 'emergency') sourceInput.value = 'emergency';
                else if (tabName === 'nicu') sourceInput.value = 'nicu';
                else if (tabName === 'all') sourceInput.value = 'all';
                else sourceInput.value = tabName;
            }

            // مزامنة حالة التمييز في بطاقات الأقسام العلوية
            const kpiMap = {
                'station-tab': 0,
                'direct-tab': 1,
                'emergency-tab': 2,
                'all-tab': 3
            };
            const cards = document.querySelectorAll('#radiology-department-cards .kpi-metric-card');
            cards.forEach(c => c.classList.remove('shadow', 'border-4'));
            if (kpiMap[target] !== undefined && cards[kpiMap[target]]) {
                cards[kpiMap[target]].classList.add('shadow', 'border-4');
            }
        });
    });
});
</script>
@endsection
