@extends('layouts.app')

@section('title', 'خزينة المستشفى المركزية والحسابات العامة')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="#">الحسابات والمالية</a></li>
                    <li class="breadcrumb-item active" aria-current="page">خزينة المستشفى المركزية</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i class="fas fa-vault text-warning"></i>
                <span>خزينة المستشفى المركزية والتحليل المالي</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-7 fw-normal">لوحة قيادة تفاعلية</span>
            </h3>
            <p class="text-muted small mb-0">نظام الخزينة الموحد (Unified Hospital Treasury) - توزيع الإيرادات حسب الأقسام، التحليلات البيانية، وسجل القيود المحاسبية</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-danger d-flex align-items-center gap-2 shadow-sm px-3 rounded-3" data-bs-toggle="modal" data-bs-target="#expenseModal">
                <i class="fas fa-minus-circle"></i>
                <span>+ سند صرف مصروفات</span>
            </button>
            <button type="button" class="btn btn-success d-flex align-items-center gap-2 shadow-sm px-3 rounded-3" data-bs-toggle="modal" data-bs-target="#incomeModal">
                <i class="fas fa-plus-circle"></i>
                <span>+ سند قبض إيراد</span>
            </button>
            <button type="button" onclick="window.print()" class="btn btn-outline-dark d-flex align-items-center gap-2 shadow-sm rounded-3">
                <i class="fas fa-print"></i>
                <span>طباعة التقرير</span>
            </button>
            <a href="{{ route('treasury.export', request()->query()) }}" class="btn btn-outline-success d-flex align-items-center gap-2 shadow-sm rounded-3">
                <i class="fas fa-file-excel"></i>
                <span>تصدير Excel</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- بطاقات المؤشرات المالية العامة (KPI Cards) -->
    <div class="row g-3 mb-4">
        <!-- رصيد الخزينة الإجمالي الفعلي اللحظي -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff;">
                <div class="card-body p-3.5 position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase fw-bold text-white-50 small">الرصيد الفعلي المتوفر بالخزينة</span>
                        <div class="rounded-circle p-2 bg-white bg-opacity-10 text-warning">
                            <i class="fas fa-vault fa-lg"></i>
                        </div>
                    </div>
                    <h2 class="fw-bold mb-1 {{ $currentTreasuryBalance >= 0 ? 'text-warning' : 'text-danger' }}">
                        {{ number_format($currentTreasuryBalance) }} <small class="fs-6 text-white-50">د.ع</small>
                    </h2>
                    <div class="small text-white-50 mt-2 d-flex justify-content-between pt-2 border-top border-secondary border-opacity-25">
                        <span>إجمالي كل الوارد: <strong class="text-success">{{ number_format($totalAllTimeInflow) }}</strong></span>
                        <span>المنصرف: <strong class="text-danger">{{ number_format($totalAllTimeOutflow) }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- إجمالي المقبوضات للفترة -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3.5 border-start border-4 border-success">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase fw-bold text-muted small">إجمالي الوارد (الفترة المحددة)</span>
                        <div class="rounded-circle p-2 bg-success bg-opacity-10 text-success">
                            <i class="fas fa-arrow-down fa-lg"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-1">
                        + {{ number_format($periodInflow) }} <small class="fs-6 text-muted">د.ع</small>
                    </h3>
                    <div class="small text-muted mt-2 d-flex justify-content-between">
                        <span>كافة إيرادات الأقسام والكاشير</span>
                        <span class="badge bg-success-subtle text-success">وارد (+)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- إجمالي المصروفات للفترة -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3.5 border-start border-4 border-danger">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase fw-bold text-muted small">إجمالي المنصرف (الفترة المحددة)</span>
                        <div class="rounded-circle p-2 bg-danger bg-opacity-10 text-danger">
                            <i class="fas fa-arrow-up fa-lg"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-danger mb-1">
                        - {{ number_format($periodOutflow) }} <small class="fs-6 text-muted">د.ع</small>
                    </h3>
                    <div class="small text-muted mt-2 d-flex justify-content-between">
                        <span>مستحقات أطباء ومشتريات ونثريات</span>
                        <span class="badge bg-danger-subtle text-danger">صادر (-)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- صافي حركة الفترة (Net Period Balance) -->
        @php
            $netPeriod = $periodInflow - $periodOutflow;
        @endphp
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3.5 border-start border-4 {{ $netPeriod >= 0 ? 'border-primary' : 'border-warning' }}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-uppercase fw-bold text-muted small">صافي حركة الفترة (الفائض / العجز)</span>
                        <div class="rounded-circle p-2 {{ $netPeriod >= 0 ? 'bg-primary bg-opacity-10 text-primary' : 'bg-warning bg-opacity-10 text-warning' }}">
                            <i class="fas fa-scale-balanced fa-lg"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold {{ $netPeriod >= 0 ? 'text-primary' : 'text-warning' }} mb-1">
                        {{ $netPeriod >= 0 ? '+' : '' }}{{ number_format($netPeriod) }} <small class="fs-6 text-muted">د.ع</small>
                    </h3>
                    <div class="small text-muted mt-2 d-flex justify-content-between">
                        <span>الوارد مطروحاً منه المصروفات</span>
                        <span class="badge {{ $netPeriod >= 0 ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning' }}">
                            {{ $netPeriod >= 0 ? 'فائض نقدي' : 'عجز مؤقت' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط الفلاتر والبحث الزمني الموحد -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('treasury.index') }}" id="treasuryFilterForm">
                <!-- أزرار الاختيار السريع للفترات -->
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom">
                    <div class="d-flex flex-wrap align-items-center gap-1.5">
                        <span class="small fw-bold text-muted me-1"><i class="fas fa-calendar-day me-1"></i> فترات سريعة:</span>
                        <button type="button" onclick="setQuickDate('today')" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1">اليوم</button>
                        <button type="button" onclick="setQuickDate('yesterday')" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1">أمس</button>
                        <button type="button" onclick="setQuickDate('week')" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1">هذا الأسبوع</button>
                        <button type="button" onclick="setQuickDate('month')" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1">هذا الشهر</button>
                        <button type="button" onclick="setQuickDate('all')" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1">كل الوقت</button>
                    </div>
                    <div>
                        <span class="small text-muted font-monospace">الفترة الحالية: {{ request('from_date') ?: 'البداية' }} ⬅ {{ request('to_date') ?: 'الآن' }}</span>
                    </div>
                </div>

                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">من تاريخ</label>
                        <input type="date" name="from_date" id="from_date_input" class="form-control form-control-sm" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">إلى تاريخ</label>
                        <input type="date" name="to_date" id="to_date_input" class="form-control form-control-sm" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">نوع الحركة</label>
                        <select name="voucher_type" class="form-select form-select-sm">
                            <option value="all">كافة الحركات</option>
                            <option value="inflow" {{ request('voucher_type') === 'inflow' ? 'selected' : '' }}>وارد فقط (+)</option>
                            <option value="outflow" {{ request('voucher_type') === 'outflow' ? 'selected' : '' }}>صادر فقط (-)</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">البند / التصنيف</label>
                        <select name="category" class="form-select form-select-sm">
                            <option value="all">كافة التصنيفات والأقسام</option>
                            @foreach($categories as $key => $name)
                                <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">بحث برقم السند أو البيان</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" placeholder="رقم السند، الوصف، الملاحظات..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-primary px-3">
                                <i class="fas fa-search me-1"></i> تصفية
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-1">
                        <a href="{{ route('treasury.index') }}" class="btn btn-sm btn-outline-secondary w-100" title="إعادة ضبط الفلاتر">
                            <i class="fas fa-redo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= شريط التبويبات الثلاثة (Modern 3-Tab Architecture) ================= -->
    <ul class="nav nav-pills p-1.5 bg-white border shadow-sm rounded-4 mb-4 gap-2" id="treasuryTabs" role="tablist">
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link active w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2" 
                    id="analytics-tab-btn" data-bs-toggle="pill" data-bs-target="#analytics-tab-pane" type="button" role="tab" aria-selected="true">
                <i class="fas fa-chart-pie text-primary fs-6"></i>
                <span>1. المخططات البيانية والتحليل المالي</span>
            </button>
        </li>
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2" 
                    id="departments-tab-btn" data-bs-toggle="pill" data-bs-target="#departments-tab-pane" type="button" role="tab" aria-selected="false">
                <i class="fas fa-sitemap text-teal fs-6" style="color: #0d9488;"></i>
                <span>2. جدول إيرادات ومصروفات الأقسام</span>
                <span class="badge bg-teal-subtle text-teal border rounded-pill px-2" style="background:#ccfbf1; color:#0f766e;">{{ count($departmentStats) }} أقسام</span>
            </button>
        </li>
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2" 
                    id="ledger-tab-btn" data-bs-toggle="pill" data-bs-target="#ledger-tab-pane" type="button" role="tab" aria-selected="false">
                <i class="fas fa-receipt text-success fs-6"></i>
                <span>3. سجل القيود والوصولات التفصيلية</span>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2">{{ $transactions->total() }}</span>
            </button>
        </li>
        <li class="nav-item flex-fill" role="presentation">
            <button class="nav-link w-100 py-2.5 rounded-3 fw-bold d-flex align-items-center justify-content-center gap-2" 
                    id="doctors-tab-btn" data-bs-toggle="pill" data-bs-target="#doctors-tab-pane" type="button" role="tab" aria-selected="false">
                <i class="fas fa-user-md text-info fs-6"></i>
                <span>4. 🩺 تحليل أطباء الاستشارية والتحويلات</span>
                <span class="badge bg-info-subtle text-info border rounded-pill px-2">{{ count($doctorAnalytics) }} أطباء</span>
            </button>
        </li>
    </ul>

    <!-- محتوى التبويبات -->
    <div class="tab-content" id="treasuryTabsContent">

        <!-- ================= التبويب 1: المخططات البيانية والتحليل المالي ================= -->
        <div class="tab-pane fade show active" id="analytics-tab-pane" role="tabpanel" tabindex="0">
            <!-- شريط التحكم الشامل بأنماط المخططات البيانية لكامل الصفحة -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border-start border-4 border-primary">
                <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-sliders fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark">شريط التحكم الشامل بأنماط المخططات البيانية</h6>
                            <small class="text-muted">التحكم الفوري والموحد في طريقة تمثيل كافة الرسوم البيانية في الصفحة بضغطة زر واحدة</small>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border shadow-2xs" role="group" id="masterChartButtonGroup">
                            <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold active btn-primary" onclick="setMasterChartMode('bar')" id="btnMasterBar">
                                <i class="fas fa-chart-column me-1.5"></i>📊 أعمدة رأسية
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold btn-light text-dark" onclick="setMasterChartMode('horizontal')" id="btnMasterHorizontal">
                                <i class="fas fa-bars-staggered me-1.5 text-warning"></i>📶 أعمدة أفقية
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold btn-light text-dark" onclick="setMasterChartMode('line')" id="btnMasterLine">
                                <i class="fas fa-chart-line me-1.5 text-success"></i>📈 منحنيات مساحية
                            </button>
                            <button type="button" class="btn btn-sm rounded-pill px-3 fw-bold btn-light text-dark" onclick="setMasterChartMode('donut')" id="btnMasterDonut">
                                <i class="fas fa-chart-pie me-1.5 text-info"></i>🍩 دائري وحصص
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-4 mb-4">
                <!-- مخطط توزيع الإيرادات حسب الأقسام (Donut Chart) -->
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 px-4 border-bottom border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-chart-column text-primary me-2"></i>إيرادات الأقسام والمراكز</h6>
                                <small class="text-muted">الأرقام الدقيقة لمساهمة كل قسم في إجمالي دخل المستشفى</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="btn-group btn-group-sm" role="group" id="deptChartButtonGroup">
                                    <button type="button" class="btn btn-sm btn-primary active" id="btnShowDeptBar" onclick="switchDeptChart('bar')" title="أعمدة رأسية">
                                        <i class="fas fa-chart-column me-1"></i>أعمدة
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnShowDeptHorizontal" onclick="switchDeptChart('horizontal')" title="أعمدة أفقية">
                                        <i class="fas fa-bars-staggered me-1"></i>أفقي
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnShowDeptLine" onclick="switchDeptChart('line')" title="منحنى خطي">
                                        <i class="fas fa-chart-line me-1"></i>منحنى
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnShowDeptDonut" onclick="switchDeptChart('donut')" title="مخطط دائري">
                                        <i class="fas fa-chart-pie me-1"></i>دائري
                                    </button>
                                </div>
                                <span class="badge bg-light text-dark border font-monospace">{{ number_format($periodInflow) }} د.ع</span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            @if(count($deptChartData) > 0 && $periodInflow > 0)
                                <!-- الحاوية الأساسية: مخطط الأعمدة البيانية مع الأرقام مباشرة فوق كل عمود -->
                                <div id="deptBarChartContainer" style="position: relative; height: 300px; width: 100%;">
                                    <canvas id="deptRevenueBarChart"></canvas>
                                </div>

                                <!-- الحاوية البديلة: المخطط الدائري -->
                                <div id="deptDonutChartContainer" style="position: relative; height: 300px; width: 100%; display: none;">
                                    <div style="position: relative; height: 280px; width: 100%; max-width: 340px; margin: 0 auto;">
                                        <canvas id="deptRevenueDonutChart"></canvas>
                                    </div>
                                </div>
                                <div class="w-100 mt-4 pt-3 border-top">
                                    <h6 class="small fw-bold text-muted mb-3 d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-list-ul me-1.5 text-primary"></i>تفاصيل إيرادات الأقسام المسجلة:</span>
                                        <span class="badge bg-light text-dark border font-monospace">{{ count($deptChartData) }} أقسام</span>
                                    </h6>
                                    <div class="d-flex flex-column gap-2">
                                        @foreach($departmentStats as $dKey => $dStat)
                                            @if($dStat['gross'] > 0)
                                                <div class="p-2.5 rounded-3 border bg-light d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center gap-2.5 text-truncate">
                                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" 
                                                              style="width: 24px; height: 24px; background-color: {{ $dStat['color'] }}20; color: {{ $dStat['color'] }}; border: 1px solid {{ $dStat['color'] }}40;">
                                                            <i class="fas {{ $dStat['icon'] }} fs-7"></i>
                                                        </span>
                                                        <div class="text-truncate">
                                                            <strong class="text-dark d-block text-truncate small">{{ $dStat['name'] }}</strong>
                                                            <small class="text-muted font-monospace">{{ $dStat['count'] }} حركة / إيصال</small>
                                                        </div>
                                                    </div>
                                                    <div class="text-end flex-shrink-0 ms-2">
                                                        <div class="fw-bold font-monospace text-dark fs-6">{{ number_format($dStat['gross']) }} <small class="text-muted">د.ع</small></div>
                                                        <span class="badge rounded-pill fw-bold" style="background-color: {{ $dStat['color'] }}20; color: {{ $dStat['color'] }}; border: 1px solid {{ $dStat['color'] }}40;">{{ $dStat['percentage'] }}%</span>
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-chart-pie fa-3x opacity-25 mb-3"></i>
                                    <h6 class="fw-bold">لا توجد إيرادات مسجلة لهذه الفترة</h6>
                                    <small>قم باختيار فترة زمنية أوسع لعرض المخطط البياني</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- مخطط المنحنى الزمني لحركة النقدية (Cash Flow Trend Chart) -->
                <div class="col-12 col-xl-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 px-4 border-bottom border-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-chart-line text-success me-2"></i>منحنى حركة النقدية اليومية (الوارد مقابل المنصرف)</h6>
                                <small class="text-muted">مقارنة السيولة الداخلة والخارجة لاكتشاف أوقات الذروة</small>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <div class="btn-group btn-group-sm" role="group" id="trendChartButtonGroup">
                                    <button type="button" class="btn btn-sm btn-primary active" id="btnTrendBar" onclick="switchTrendChart('bar')">
                                        <i class="fas fa-chart-column me-1"></i>أعمدة
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnTrendHorizontal" onclick="switchTrendChart('horizontal')">
                                        <i class="fas fa-bars-staggered me-1"></i>أفقي
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnTrendLine" onclick="switchTrendChart('line')">
                                        <i class="fas fa-chart-line me-1"></i>منحنى
                                    </button>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-square me-1"></i>الوارد: {{ number_format($periodInflow) }} د.ع</span>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-square me-1"></i>المنصرف: {{ number_format($periodOutflow) }} د.ع</span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div style="position: relative; height: 320px; width: 100%;">
                                <canvas id="cashFlowTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- بطاقات ملخص طرق الدفع والمصروفات -->
            <div class="row g-4">
                <!-- توزيع طرق التحصيل (Cash vs Electronic) -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 px-4 border-bottom border-light">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-credit-card text-info me-2"></i>توزيع طرق التحصيل المالي (النقد مقابل الرقمي)</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="p-3 rounded-4 border bg-light h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small fw-bold"><i class="fas fa-money-bill-wave text-success me-1"></i> النقد في الدرج (Cash)</span>
                                            <span class="badge bg-success-subtle text-success">نقدي</span>
                                        </div>
                                        <h4 class="fw-bold text-dark mb-1 font-monospace">{{ number_format($paymentMethodStats['cash']) }} <small class="fs-7 text-muted">د.ع</small></h4>
                                        <small class="text-muted">السيولة النقدية المباشرة في صندوق الكاشير</small>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 rounded-4 border bg-light h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="text-muted small fw-bold"><i class="fas fa-qrcode text-primary me-1"></i> دفع إلكتروني وبطاقات (Card)</span>
                                            <span class="badge bg-primary-subtle text-primary">إلكتروني</span>
                                        </div>
                                        <h4 class="fw-bold text-dark mb-1 font-monospace">{{ number_format($paymentMethodStats['card']) }} <small class="fs-7 text-muted">د.ع</small></h4>
                                        <small class="text-muted">زين كاش، ماستركارد، وأجهزة الدفع الإلكتروني (POS)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- تحليل وتوزيع المصروفات التشغيلية -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white py-3 px-4 border-bottom border-light">
                            <h6 class="fw-bold mb-0 text-dark"><i class="fas fa-money-bill-transfer text-danger me-2"></i>توزيع المصروفات والمدفوعات</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-dark small fw-bold"><i class="fas fa-user-md text-primary me-2"></i> صرفيات مستحقات الأطباء (Consultant & ER Payouts)</span>
                                    <strong class="font-monospace text-dark">{{ number_format($periodDoctorPayouts) }} د.ع</strong>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $periodOutflow > 0 ? ($periodDoctorPayouts / $periodOutflow) * 100 : 0 }}%;"></div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-dark small fw-bold"><i class="fas fa-boxes text-warning me-2"></i> فواتير المشتريات والموردين (Purchases & Supplies)</span>
                                    <strong class="font-monospace text-dark">{{ number_format($periodPurchases) }} د.ع</strong>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: {{ $periodOutflow > 0 ? ($periodPurchases / $periodOutflow) * 100 : 0 }}%;"></div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-dark small fw-bold"><i class="fas fa-cogs text-info me-2"></i> نثريات، رواتب ومصاريف تشغيلية (Operational Costs)</span>
                                    <strong class="font-monospace text-dark">{{ number_format($periodOperational) }} د.ع</strong>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-info" style="width: {{ $periodOutflow > 0 ? ($periodOperational / $periodOutflow) * 100 : 0 }}%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= التبويب 2: جدول إيرادات ومصروفات الأقسام التفصيلي ================= -->
        <div class="tab-pane fade" id="departments-tab-pane" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3.5 px-4 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="fas fa-sitemap text-teal" style="color: #0d9488;"></i>
                            <span>كشف إيرادات ومساهمة الأقسام والخدمات الطبية</span>
                        </h5>
                        <small class="text-muted">جدول تحليلي معياري يقيس دخل كل قسم، عدد الوصولات، وصافي عائد المستشفى بعد خصم حصص الأطباء</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary px-3">
                            <i class="fas fa-print me-1"></i> طباعة جدول الأقسام
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 50px;">#</th>
                                <th style="width: 250px;">القسم / المركز الطبي</th>
                                <th class="text-center" style="width: 140px;">عدد الوصولات / الحالات</th>
                                <th class="text-end" style="width: 180px;">إجمالي المقبوض (Gross)</th>
                                <th class="text-end" style="width: 180px;">حصص الأطباء / التكاليف</th>
                                <th class="text-end" style="width: 180px;">صافي عائد المستشفى (Net)</th>
                                <th style="width: 180px;">النسبة من الدخل العام</th>
                                <th class="text-center pe-4" style="width: 120px;">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $totalDeptCount = 0;
                                $totalDeptGross = 0;
                                $totalDeptCosts = 0;
                                $totalDeptNet = 0;
                                $counter = 1;
                            @endphp
                            @foreach($departmentStats as $deptKey => $dept)
                                @php
                                    $totalDeptCount += $dept['count'];
                                    $totalDeptGross += $dept['gross'];
                                    $totalDeptCosts += $dept['costs'];
                                    $totalDeptNet += $dept['net'];
                                @endphp
                                <tr>
                                    <td class="ps-4 text-muted small font-monospace">{{ $counter++ }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2.5">
                                            <div class="rounded-3 p-2 d-flex align-items-center justify-content-center shadow-xs" 
                                                 style="width: 38px; height: 38px; background-color: {{ $dept['color'] }}15; color: {{ $dept['color'] }};">
                                                <i class="fas {{ $dept['icon'] }} fs-5"></i>
                                            </div>
                                            <div>
                                                <strong class="text-dark d-block">{{ $dept['name'] }}</strong>
                                                <small class="text-muted font-monospace">{{ $deptKey }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $dept['count'] > 0 ? 'bg-light text-dark border' : 'bg-secondary-subtle text-muted' }} px-3 py-1.5 font-monospace fs-7">
                                            {{ number_format($dept['count']) }} حالة
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="font-monospace fs-6 text-dark">
                                            {{ number_format($dept['gross']) }} <small class="fs-8 text-muted">د.ع</small>
                                        </strong>
                                    </td>
                                    <td class="text-end">
                                        <span class="font-monospace text-muted">
                                            {{ $dept['costs'] > 0 ? ('- ' . number_format($dept['costs'])) : '0' }} <small class="fs-8 text-muted">د.ع</small>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="font-monospace fs-6 text-success">
                                            {{ number_format($dept['net']) }} <small class="fs-8 text-muted">د.ع</small>
                                        </strong>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 7px;">
                                                <div class="progress-bar" role="progressbar" style="width: {{ $dept['percentage'] }}%; background-color: {{ $dept['color'] }};"></div>
                                            </div>
                                            <span class="font-monospace small fw-bold" style="width: 42px; text-align: left; color: {{ $dept['color'] }};">
                                                {{ $dept['percentage'] }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-center pe-4">
                                        @if($deptKey === 'consultation')
                                            <div class="d-flex justify-content-center gap-1">
                                                <button type="button" class="btn btn-xs btn-primary px-2.5 py-1 rounded-pill shadow-xs" onclick="switchToDoctorTab()" title="عرض تفاصيل كشوفات الأطباء والتحويلات">
                                                    <i class="fas fa-user-md me-1"></i> تحليل الأطباء
                                                </button>
                                                <a href="{{ route('treasury.index', array_merge(request()->query(), ['category' => $deptKey])) }}#ledger" 
                                                   class="btn btn-xs btn-outline-secondary px-2 py-1 rounded-pill" title="عرض وصولات الاستشارية">
                                                    <i class="fas fa-filter"></i>
                                                </a>
                                            </div>
                                        @else
                                            <a href="{{ route('treasury.index', array_merge(request()->query(), ['category' => $deptKey])) }}#ledger" 
                                               class="btn btn-xs btn-outline-primary px-2.5 py-1 rounded-pill" title="عرض قيود هذا القسم">
                                                <i class="fas fa-filter me-1"></i> تصفية
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light border-top border-2">
                            <tr class="fw-bold">
                                <td colspan="2" class="ps-4 py-3 fs-6 text-dark">
                                    <i class="fas fa-calculator text-primary me-2"></i> المجموع العام لإيرادات الأقسام
                                </td>
                                <td class="text-center font-monospace fs-6 py-3">
                                    <span class="badge bg-primary px-3 py-1.5 fs-7">{{ number_format($totalDeptCount) }} معاملة</span>
                                </td>
                                <td class="text-end font-monospace fs-6 py-3 text-dark">
                                    {{ number_format($totalDeptGross) }} د.ع
                                </td>
                                <td class="text-end font-monospace fs-6 py-3 text-danger">
                                    - {{ number_format($totalDeptCosts) }} د.ع
                                </td>
                                <td class="text-end font-monospace fs-6 py-3 text-success">
                                    {{ number_format($totalDeptNet) }} د.ع
                                </td>
                                <td class="py-3 font-monospace fw-bold text-dark">100%</td>
                                <td class="pe-4 text-center"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- ملاحظة هندسية توضح مرونة وإمكانية إضافة أقسام جديدة -->
            <div class="alert alert-info bg-info-subtle border-info-subtle rounded-4 p-3 d-flex align-items-center gap-3">
                <i class="fas fa-lightbulb text-info fs-3"></i>
                <div class="small text-dark">
                    <strong>💡 بنية معيارية قابلة للتوسع الفوري (Modular Architecture):</strong>
                    إذا رغبت مستقبلاً في إضافة أي قسم جديد (مثل: الصيدلية، الغسيل الكلوي، العناية المركزة، أو أي عيادة خاصة)،
                    يتم إدراجه في مصفوفة الإعدادات تلقائياً ليظهر فوراً في هذا الجدول وفي الرسوم البيانية دون الحاجة لإعادة كتابة كود الواجهة.
                </div>
            </div>
        </div>

        <!-- ================= التبويب 3: سجل القيود والوصولات التفصيلية ================= -->
        <div class="tab-pane fade" id="ledger-tab-pane" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3.5 px-4 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="fas fa-receipt text-primary"></i>
                            <span>سجل القيود وحركات الخزينة والوصولات</span>
                        </h5>
                        <small class="text-muted">دفتر النقدية المحاسبي المباشر (Audit Ledger) لكافة الإيصالات المالية وحركات الصرف والقبض</small>
                    </div>
                    <span class="badge bg-light text-dark border px-3 py-2 font-monospace">
                        إجمالي القيود المسجلة: {{ $transactions->total() }}
                    </span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">#</th>
                                <th>رقم السند</th>
                                <th>التاريخ والوقت</th>
                                <th>الاتجاه</th>
                                <th>البند / القسم</th>
                                <th>البيان والوصف</th>
                                <th>المبلغ</th>
                                <th>طريقة الدفع</th>
                                <th>المستخدم القائم</th>
                                <th class="text-center pe-4">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $index => $trx)
                            <tr>
                                <td class="ps-4 text-muted small font-monospace">{{ $transactions->firstItem() + $index }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-bold px-2 py-1 font-monospace">
                                        {{ $trx->voucher_number ?: ('#' . $trx->id) }}
                                    </span>
                                </td>
                                <td class="small text-muted font-monospace">
                                    <i class="far fa-clock me-1"></i>
                                    {{ $trx->performed_at ? $trx->performed_at->format('Y-m-d H:i') : $trx->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td>
                                    @if($trx->isInflow())
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="fas fa-arrow-down me-1"></i> وارد (+)
                                        </span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                            <i class="fas fa-arrow-up me-1"></i> صادر (-)
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">
                                        {{ $trx->category_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $trx->description }}</div>
                                    @if($trx->notes)
                                        <div class="text-muted small text-truncate" style="max-width: 320px;" title="{{ $trx->notes }}">
                                            <i class="far fa-comment-dots me-1"></i>{{ $trx->notes }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-bold fs-6 font-monospace {{ $trx->isInflow() ? 'text-success' : 'text-danger' }}">
                                        {{ $trx->isInflow() ? '+' : '-' }} {{ number_format($trx->amount) }} <small class="text-muted fs-7">د.ع</small>
                                    </span>
                                </td>
                                <td class="small">
                                    <span class="badge bg-light text-muted border font-monospace">
                                        <i class="fas fa-money-bill-wave me-1"></i>{{ $trx->payment_method_label }}
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <i class="far fa-user me-1"></i>
                                    {{ optional($trx->performer)->name ?: 'النظام' }}
                                </td>
                                <td class="text-center pe-4">
                                    @if($trx->related_type !== \App\Models\Payment::class)
                                        <form method="POST" action="{{ route('treasury.destroy', $trx) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا السند؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2" title="حذف السند اليدوي">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small" title="مرتبط بإيصال كاشير رسمي">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="fas fa-vault text-muted fa-3x mb-3 opacity-50"></i>
                                        <h6 class="text-muted fw-bold">لا توجد حركات مسجلة في الخزينة لهذه الفترة</h6>
                                        <p class="text-muted small mb-0">يمكنك تسجيل سند صرف أو قبض جديد عبر الأزرار بالأعلى.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($transactions->hasPages())
                <div class="card-footer bg-white border-0 py-3">
                    {{ $transactions->links() }}
                </div>
                @endif
            </div>
        </div>

        <!-- ================= التبويب 4: التحليل المالي والتحويلي لأطباء الاستشارية (Doctor BI & Referrals) ================= -->
        <div class="tab-pane fade" id="doctors-tab-pane" role="tabpanel" tabindex="0">
            <!-- بطاقات مؤشرات الأداء السريعة للأطباء (KPIs) -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                        <div class="card-body p-3.5 border-start border-4 border-primary">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-bold text-muted text-uppercase">إجمالي الكشوفات المسجلة</span>
                                <div class="rounded-circle p-2 bg-primary bg-opacity-10 text-primary">
                                    <i class="fas fa-stethoscope fa-lg"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold text-dark mb-1 font-monospace">
                                {{ number_format($doctorSummaryTotals['total_appts']) }} <small class="fs-6 text-muted">كشفية</small>
                            </h3>
                            <div class="small text-muted d-flex justify-content-between mt-2 pt-2 border-top">
                                <span><i class="fas fa-money-bill-wave text-success me-1"></i> كاش: <strong>{{ number_format($doctorSummaryTotals['total_cash_appts']) }}</strong></span>
                                <span><i class="fas fa-id-card text-info me-1"></i> ضمان صحي: <strong>{{ number_format($doctorSummaryTotals['total_insurance_appts']) }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                        <div class="card-body p-3.5 border-start border-4 border-success">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-bold text-muted text-uppercase">إجمالي الدخل وصافي المستشفى</span>
                                <div class="rounded-circle p-2 bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-hand-holding-dollar fa-lg"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold text-success mb-1 font-monospace">
                                {{ number_format($doctorSummaryTotals['total_hosp_share']) }} <small class="fs-6 text-muted">د.ع (صافي)</small>
                            </h3>
                            <div class="small text-muted d-flex justify-content-between mt-2 pt-2 border-top">
                                <span>نقد الدرج: <strong class="text-dark">{{ number_format($doctorSummaryTotals['total_cash_revenue'] + $doctorSummaryTotals['total_insurance_copay']) }}</strong></span>
                                <span>مطالبة الضمان: <strong class="text-info">{{ number_format($doctorSummaryTotals['total_insurance_claim']) }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                        <div class="card-body p-3.5 border-start border-4 border-info">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-bold text-muted text-uppercase">إجمالي الخدمات والإحالات الصادرة</span>
                                <div class="rounded-circle p-2 bg-info bg-opacity-10 text-info">
                                    <i class="fas fa-network-wired fa-lg"></i>
                                </div>
                            </div>
                            <h3 class="fw-bold text-info mb-1 font-monospace">
                                {{ number_format($doctorSummaryTotals['total_refs']) }} <small class="fs-6 text-muted">إحالة طبية</small>
                            </h3>
                            <div class="small text-muted d-flex justify-content-between mt-2 pt-2 border-top">
                                <span>🧪 مختبر: <strong>{{ number_format($doctorSummaryTotals['total_lab_refs']) }}</strong></span>
                                <span>🩻 أشعة/إيكو: <strong>{{ number_format($doctorSummaryTotals['total_rad_refs']) }}</strong></span>
                                <span>🏥 عمليات: <strong>{{ number_format($doctorSummaryTotals['total_surg_refs']) }}</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- جدول تحليلات الأطباء والتحويلات التفصيلي -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3.5 px-4 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="fas fa-user-md text-info"></i>
                            <span>كشف إنتاجية أطباء العيادات الاستشارية وتحويلات الأقسام (Doctor Referrals Matrix)</span>
                        </h5>
                        <small class="text-muted">تحليل رقمي شامل لحجم عمل كل طبيب: الكشوفات، مقبوض الكاش، تحمل المريض، مطالبات التأمين، وحجم الإحالات الصادرة</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary px-3">
                            <i class="fas fa-print me-1"></i> طباعة كشف الأطباء
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4" style="width: 35px;">#</th>
                                <th style="min-width: 170px;">الطبيب / الاختصاص</th>
                                <th class="text-center" style="width: 75px;">كاش 💵</th>
                                <th class="text-center" style="width: 75px;">ضمان 💳</th>
                                <th class="text-center" style="width: 85px;">الكشوفات</th>
                                <th class="text-end" style="width: 110px;">مقبوض كاش 💵</th>
                                <th class="text-end" style="width: 115px;">تحمل المريض 💳</th>
                                <th class="text-end" style="width: 125px; background-color: #ecfdf5;">نقد الدرج 💰</th>
                                <th class="text-end" style="width: 115px;">مطالبة الضمان 📑</th>
                                <th class="text-end" style="width: 125px;">إجمالي القيمة (Gross)</th>
                                <th class="text-end" style="width: 120px;">مستحقات الطبيب</th>
                                <th class="text-end" style="width: 120px;">صافي المستشفى</th>
                                <th class="text-center" style="width: 80px;">🧪 مختبر</th>
                                <th class="text-center" style="width: 80px;">🩻 أشعة</th>
                                <th class="text-center" style="width: 75px;">🏥 عمليات</th>
                                <th class="text-center pe-4" style="width: 110px;">التحويلات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($doctorAnalytics as $index => $docRow)
                            <tr>
                                <td class="ps-4 text-muted small font-monospace">{{ $index + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" 
                                             style="width: 34px; height: 34px;">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                        <div>
                                            <strong class="text-dark d-block">{{ $docRow['name'] }}</strong>
                                            <small class="text-muted">{{ $docRow['department'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $docRow['cash_cnt'] > 0 ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-muted' }} px-2 py-1 font-monospace">
                                        {{ number_format($docRow['cash_cnt']) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $docRow['insurance_cnt'] > 0 ? 'bg-info-subtle text-info border border-info-subtle' : 'bg-light text-muted' }} px-2 py-1 font-monospace">
                                        {{ number_format($docRow['insurance_cnt']) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <strong class="font-monospace text-dark">{{ number_format($docRow['total_appts']) }}</strong>
                                </td>
                                <td class="text-end font-monospace text-dark fw-bold">
                                    {{ number_format($docRow['cash_revenue']) }} <small class="text-muted fs-8">د.ع</small>
                                </td>
                                <td class="text-end font-monospace text-info fw-bold">
                                    {{ number_format($docRow['insurance_copay']) }} <small class="text-muted fs-8">د.ع</small>
                                </td>
                                <td class="text-end font-monospace fw-bold" style="background-color: #f0fdf4; color: #15803d;">
                                    {{ number_format($docRow['cash_revenue'] + $docRow['insurance_copay']) }} <small class="text-success fs-8">د.ع</small>
                                </td>
                                <td class="text-end font-monospace text-muted">
                                    {{ number_format($docRow['insurance_claim']) }} <small class="text-muted fs-8">د.ع</small>
                                </td>
                                <td class="text-end font-monospace text-dark fw-bold">
                                    {{ number_format($docRow['gross']) }} <small class="text-muted fs-8">د.ع</small>
                                </td>
                                <td class="text-end font-monospace text-danger">
                                    {{ $docRow['doc_share'] > 0 ? number_format($docRow['doc_share']) : '0' }} <small class="text-muted fs-8">د.ع</small>
                                </td>
                                <td class="text-end font-monospace text-success fw-bold">
                                    {{ number_format($docRow['hosp_share']) }} <small class="text-muted fs-8">د.ع</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $docRow['lab_refs'] > 0 ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-light text-muted' }} px-2 py-1 font-monospace">
                                        {{ number_format($docRow['lab_refs']) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $docRow['rad_refs'] > 0 ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' : 'bg-light text-muted' }} px-2 py-1 font-monospace">
                                        {{ number_format($docRow['rad_refs']) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $docRow['surg_refs'] > 0 ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-light text-muted' }} px-2 py-1 font-monospace">
                                        {{ number_format($docRow['surg_refs']) }}
                                    </span>
                                </td>
                                <td class="text-center pe-4">
                                    <span class="badge {{ $docRow['total_refs'] > 0 ? 'bg-dark text-white' : 'bg-light text-muted border' }} px-2.5 py-1 font-monospace fs-7">
                                        <i class="fas fa-share-alt me-1"></i> {{ number_format($docRow['total_refs']) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="16" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="fas fa-user-md fa-3x text-muted opacity-50 mb-3"></i>
                                        <h6 class="text-muted fw-bold">لا توجد كشوفات أو تحويلات مسجلة لأطباء الاستشارية في هذه الفترة</h6>
                                        <p class="text-muted small mb-0">يمكنك تعديل نطاق التاريخ للتحقق من بيانات فترات سابقة.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light border-top border-2">
                            <tr class="fw-bold">
                                <td colspan="2" class="ps-4 py-3 text-dark">
                                    <i class="fas fa-calculator text-primary me-2"></i> المجموع الإجمالي لكافة الأطباء
                                </td>
                                <td class="text-center font-monospace text-success py-3">
                                    {{ number_format($doctorSummaryTotals['total_cash_appts']) }}
                                </td>
                                <td class="text-center font-monospace text-info py-3">
                                    {{ number_format($doctorSummaryTotals['total_insurance_appts']) }}
                                </td>
                                <td class="text-center font-monospace text-dark py-3">
                                    {{ number_format($doctorSummaryTotals['total_appts']) }}
                                </td>
                                <td class="text-end font-monospace text-dark py-3">
                                    {{ number_format($doctorSummaryTotals['total_cash_revenue']) }} د.ع
                                </td>
                                <td class="text-end font-monospace text-info py-3">
                                    {{ number_format($doctorSummaryTotals['total_insurance_copay']) }} د.ع
                                </td>
                                <td class="text-end font-monospace py-3 fw-bold" style="background-color: #dcfce7; color: #166534;">
                                    {{ number_format($doctorSummaryTotals['total_cash_revenue'] + $doctorSummaryTotals['total_insurance_copay']) }} د.ع
                                </td>
                                <td class="text-end font-monospace text-muted py-3">
                                    {{ number_format($doctorSummaryTotals['total_insurance_claim']) }} د.ع
                                </td>
                                <td class="text-end font-monospace text-dark py-3">
                                    {{ number_format($doctorSummaryTotals['total_gross']) }} د.ع
                                </td>
                                <td class="text-end font-monospace text-danger py-3">
                                    {{ number_format($doctorSummaryTotals['total_doc_share']) }} د.ع
                                </td>
                                <td class="text-end font-monospace text-success py-3">
                                    {{ number_format($doctorSummaryTotals['total_hosp_share']) }} د.ع
                                </td>
                                <td class="text-center font-monospace text-primary py-3">
                                    {{ number_format($doctorSummaryTotals['total_lab_refs']) }}
                                </td>
                                <td class="text-center font-monospace text-warning-emphasis py-3">
                                    {{ number_format($doctorSummaryTotals['total_rad_refs']) }}
                                </td>
                                <td class="text-center font-monospace text-danger py-3">
                                    {{ number_format($doctorSummaryTotals['total_surg_refs']) }}
                                </td>
                                <td class="text-center font-monospace pe-4 py-3">
                                    <span class="badge bg-primary px-3 py-1.5 fs-7">{{ number_format($doctorSummaryTotals['total_refs']) }} إحالة</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ================= مودال: سند صرف مصروفات (Outflow) ================= -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="expenseModalLabel">
                    <i class="fas fa-minus-circle me-2"></i> تسجيل سند صرف (خروج نقدية)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('treasury.expense.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">بند الصرف / التصنيف <span class="text-danger">*</span></label>
                        <select name="category" id="expenseCategorySelect" class="form-select" required>
                            <option value="" disabled selected>-- اختر بند الصرف --</option>
                            <option value="doctor_payout">👨‍⚕️ صرف مستحقات أطباء (استشارية / طوارئ / عمليات)</option>
                            <option value="purchase">📦 فواتير مشتريات ومورّدين (أدوية ومستلزمات)</option>
                            <option value="salary">💼 رواتب ومكافآت موظفين</option>
                            <option value="maintenance">🔧 صيانة وأجهزة طبية</option>
                            <option value="utilities">⚡ كهرباء، ماء، وقود ومولدات</option>
                            <option value="medical_supplies">💉 مستلزمات ومستهلكات طبية</option>
                            <option value="operational">☕ نثريات ومصاريف تشغيلية عامة</option>
                            <option value="other">📌 أخرى</option>
                        </select>
                    </div>

                    <!-- حقل اختيار الطبيب -->
                    <div class="mb-3 d-none" id="doctorSelectDiv">
                        <label class="form-label fw-bold text-dark small">اختر الطبيب المستفيد <span class="text-danger">*</span></label>
                        <select name="doctor_id" id="doctorIdInput" class="form-select">
                            <option value="">-- اختر الطبيب --</option>
                            @foreach($doctors as $doc)
                                <option value="{{ $doc->id }}">{{ optional($doc->user)->name }} ({{ $doc->specialization ?: 'طبيب' }})</option>
                            @endforeach
                        </select>
                        <small class="text-muted">سيتم تسجيل الدفعة وخصمها من الرصيد المالي المستحق للطبيب تلقائياً.</small>
                    </div>

                    <!-- حقل اختيار المورد -->
                    <div class="mb-3 d-none" id="supplierSelectDiv">
                        <label class="form-label fw-bold text-dark small">اختر المورد / الشركة</label>
                        <select name="supplier_id" id="supplierIdInput" class="form-select">
                            <option value="">-- اختر المورد (اختياري) --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label fw-bold text-dark small">المبلغ المطلوب صرفه <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="any" min="1" name="amount" class="form-control" placeholder="0" required>
                                <span class="input-group-text bg-light">د.ع</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold text-dark small">طريقة الدفع</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash" selected>نقدي (صندوق الخزينة)</option>
                                <option value="bank_transfer">تحويل بنكي</option>
                                <option value="cheque">صك بنكي</option>
                                <option value="card">دفع إلكتروني</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">البيان / الوصف <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" placeholder="مثال: صرف مستحقات كشفيات الدكتور أحمد..." required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small">تاريخ ووقت الصرف</label>
                            <input type="datetime-local" name="performed_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small">ملاحظات إضافية</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="ملاحظات المحاسب...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger px-4 fw-bold">
                        <i class="fas fa-check me-1"></i> حفظ وتأكيد سند الصرف
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= مودال: سند قبض إيراد (Inflow) ================= -->
<div class="modal fade" id="incomeModal" tabindex="-1" aria-labelledby="incomeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-success text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="incomeModalLabel">
                    <i class="fas fa-plus-circle me-2"></i> تسجيل سند قبض (دخول نقدية)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('treasury.income.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">بند الإيراد / التصنيف <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="general_income" selected>تحصيلات وإيرادات عامة</option>
                            <option value="consultation">إيراد كشفية العيادات الاستشارية</option>
                            <option value="emergency">إيراد قسم الطوارئ</option>
                            <option value="lab">إيراد التحاليل المختبرية</option>
                            <option value="radiology">إيراد الأشعة والتصوير</option>
                            <option value="surgery">إيراد العمليات الجراحية</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-7">
                            <label class="form-label fw-bold text-dark small">المبلغ المقبوض <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="any" min="1" name="amount" class="form-control" placeholder="0" required>
                                <span class="input-group-text bg-light">د.ع</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-bold text-dark small">طريقة الدفع</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash" selected>نقدي (صندوق الخزينة)</option>
                                <option value="bank_transfer">تحويل بنكي</option>
                                <option value="cheque">صك بنكي</option>
                                <option value="card">دفع إلكتروني</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small">البيان / الوصف <span class="text-danger">*</span></label>
                        <input type="text" name="description" class="form-control" placeholder="مثال: توريد دفعة تأمين صحي / إيداع رصيد قاصة..." required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small">تاريخ ووقت القبض</label>
                            <input type="datetime-local" name="performed_at" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-bold text-dark small">ملاحظات إضافية</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="ملاحظات المحاسب...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="fas fa-check me-1"></i> حفظ وتأكيد سند القبض
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- تضمين مكتبة Chart.js للرسوم البيانية التفاعلية -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. التحكم في الحقول الشرطية لسند الصرف
    const expenseCategorySelect = document.getElementById('expenseCategorySelect');
    const doctorSelectDiv = document.getElementById('doctorSelectDiv');
    const doctorIdInput = document.getElementById('doctorIdInput');
    const supplierSelectDiv = document.getElementById('supplierSelectDiv');
    const supplierIdInput = document.getElementById('supplierIdInput');

    if (expenseCategorySelect) {
        expenseCategorySelect.addEventListener('change', function() {
            if (this.value === 'doctor_payout') {
                doctorSelectDiv.classList.remove('d-none');
                doctorIdInput.setAttribute('required', 'required');
            } else {
                doctorSelectDiv.classList.add('d-none');
                doctorIdInput.removeAttribute('required');
                doctorIdInput.value = '';
            }

            if (this.value === 'purchase') {
                supplierSelectDiv.classList.remove('d-none');
            } else {
                supplierSelectDiv.classList.add('d-none');
                if (supplierIdInput) supplierIdInput.value = '';
            }
        });
    }

    // 2. تفعيل التبويب المختار عبر الرابط (URL Hash Memory)
    if (window.location.hash) {
        const hash = window.location.hash;
        if (hash === '#departments') {
            const tabBtn = document.getElementById('departments-tab-btn');
            if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        } else if (hash === '#ledger') {
            const tabBtn = document.getElementById('ledger-tab-btn');
            if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        } else if (hash === '#doctors') {
            const tabBtn = document.getElementById('doctors-tab-btn');
            if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
        }
    }

    // تحديث الهاش عند تغيير التبويب
    const tabButtons = document.querySelectorAll('#treasuryTabs button[data-bs-toggle="pill"]');
    tabButtons.forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(e) {
            if (e.target.id === 'departments-tab-btn') window.location.hash = 'departments';
            else if (e.target.id === 'ledger-tab-btn') window.location.hash = 'ledger';
            else if (e.target.id === 'doctors-tab-btn') window.location.hash = 'doctors';
            else window.location.hash = 'analytics';
        });
    });

    // دالة الانتقال السريع لتبويب الأطباء
    window.switchToDoctorTab = function() {
        const tabBtn = document.getElementById('doctors-tab-btn');
        if (tabBtn) {
            bootstrap.Tab.getOrCreateInstance(tabBtn).show();
            window.location.hash = 'doctors';
            tabBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    // 3. محرك الرسوم البيانية الشامل (Full Master Chart.js Engine)
    let deptBarChartInstance = null;
    let deptDonutChartInstance = null;
    let trendChartInstance = null;

    // دالة التحكم الشامل بكافة مخططات الصفحة (Master Mode Switcher)
    window.setMasterChartMode = function(mode) {
        // 1. تحديث أزرار شريط التحكم العام
        const masterBtns = {
            'bar': document.getElementById('btnMasterBar'),
            'horizontal': document.getElementById('btnMasterHorizontal'),
            'line': document.getElementById('btnMasterLine'),
            'donut': document.getElementById('btnMasterDonut')
        };
        Object.entries(masterBtns).forEach(([k, btn]) => {
            if (btn) {
                if (k === mode) {
                    btn.classList.add('active', 'btn-primary');
                    btn.classList.remove('btn-light', 'text-dark');
                } else {
                    btn.classList.remove('active', 'btn-primary');
                    btn.classList.add('btn-light', 'text-dark');
                }
            }
        });

        // 2. تطبيق النمط على مخطط إيرادات الأقسام
        window.switchDeptChart(mode);

        // 3. تطبيق النمط على منحنى حركة النقدية اليومية
        const trendMode = (mode === 'donut') ? 'bar' : mode;
        window.switchTrendChart(trendMode);

        // 4. حفظ التفضيل في المتصفح للزيارات القادمة
        try {
            localStorage.setItem('treasury_chart_mode', mode);
        } catch(e) {}
    };

    // دالة التحكم الفردي بمخطط الأقسام
    window.switchDeptChart = function(type) {
        const barBox = document.getElementById('deptBarChartContainer');
        const donutBox = document.getElementById('deptDonutChartContainer');
        
        // تحديث أزرار بطاقة الأقسام
        const deptBtns = {
            'bar': document.getElementById('btnShowDeptBar'),
            'horizontal': document.getElementById('btnShowDeptHorizontal'),
            'line': document.getElementById('btnShowDeptLine'),
            'donut': document.getElementById('btnShowDeptDonut')
        };
        Object.entries(deptBtns).forEach(([k, btn]) => {
            if (btn) {
                if (k === type) {
                    btn.classList.add('active', 'btn-primary');
                    btn.classList.remove('btn-outline-primary');
                } else {
                    btn.classList.remove('active', 'btn-primary');
                    btn.classList.add('btn-outline-primary');
                }
            }
        });

        if (type === 'donut') {
            if (barBox) barBox.style.display = 'none';
            if (donutBox) donutBox.style.display = 'block';
            if (deptDonutChartInstance) deptDonutChartInstance.resize();
        } else {
            if (donutBox) donutBox.style.display = 'none';
            if (barBox) barBox.style.display = 'block';
            
            if (deptBarChartInstance) {
                const maxDeptVal = Math.max(...deptData, 1000);
                if (type === 'horizontal') {
                    deptBarChartInstance.config.type = 'bar';
                    deptBarChartInstance.config.options.indexAxis = 'y';
                    deptBarChartInstance.config.data.datasets[0].backgroundColor = deptColors;
                    deptBarChartInstance.config.data.datasets[0].borderColor = deptColors;
                    deptBarChartInstance.config.data.datasets[0].fill = false;
                    deptBarChartInstance.config.options.scales.x.beginAtZero = true;
                    deptBarChartInstance.config.options.scales.x.suggestedMax = maxDeptVal * 1.25;
                    deptBarChartInstance.config.options.scales.y.suggestedMax = undefined;
                    deptBarChartInstance.config.options.layout.padding = { top: 15, right: 65, left: 10, bottom: 10 };
                } else if (type === 'line') {
                    deptBarChartInstance.config.type = 'line';
                    deptBarChartInstance.config.options.indexAxis = 'x';
                    deptBarChartInstance.config.data.datasets[0].backgroundColor = 'rgba(2, 132, 199, 0.2)';
                    deptBarChartInstance.config.data.datasets[0].borderColor = '#0284c7';
                    deptBarChartInstance.config.data.datasets[0].pointBackgroundColor = deptColors;
                    deptBarChartInstance.config.data.datasets[0].pointRadius = 6;
                    deptBarChartInstance.config.data.datasets[0].fill = true;
                    deptBarChartInstance.config.data.datasets[0].tension = 0.35;
                    deptBarChartInstance.config.options.scales.y.beginAtZero = true;
                    deptBarChartInstance.config.options.scales.y.suggestedMax = maxDeptVal * 1.25;
                    deptBarChartInstance.config.options.scales.x.suggestedMax = undefined;
                    deptBarChartInstance.config.options.layout.padding = { top: 32, right: 15, left: 10, bottom: 10 };
                } else { // vertical bar (default)
                    deptBarChartInstance.config.type = 'bar';
                    deptBarChartInstance.config.options.indexAxis = 'x';
                    deptBarChartInstance.config.data.datasets[0].backgroundColor = deptColors;
                    deptBarChartInstance.config.data.datasets[0].borderColor = deptColors;
                    deptBarChartInstance.config.data.datasets[0].fill = false;
                    deptBarChartInstance.config.options.scales.y.beginAtZero = true;
                    deptBarChartInstance.config.options.scales.y.suggestedMax = maxDeptVal * 1.25;
                    deptBarChartInstance.config.options.scales.x.suggestedMax = undefined;
                    deptBarChartInstance.config.options.layout.padding = { top: 32, right: 15, left: 10, bottom: 10 };
                }
                deptBarChartInstance.update();
                deptBarChartInstance.resize();
            }
        }
    };

    // دالة التحكم الفردي بمخطط حركة النقدية اليومية
    window.switchTrendChart = function(type) {
        const trendBtns = {
            'bar': document.getElementById('btnTrendBar'),
            'horizontal': document.getElementById('btnTrendHorizontal'),
            'line': document.getElementById('btnTrendLine')
        };
        Object.entries(trendBtns).forEach(([k, btn]) => {
            if (btn) {
                if (k === type) {
                    btn.classList.add('active', 'btn-primary');
                    btn.classList.remove('btn-outline-primary');
                } else {
                    btn.classList.remove('active', 'btn-primary');
                    btn.classList.add('btn-outline-primary');
                }
            }
        });

        if (trendChartInstance) {
            const maxFlow = Math.max(...trendInflows, ...trendOutflows, 1000);
            if (type === 'horizontal') {
                trendChartInstance.config.type = 'bar';
                trendChartInstance.config.options.indexAxis = 'y';
                trendChartInstance.config.data.datasets[0].fill = false;
                trendChartInstance.config.data.datasets[1].fill = false;
                trendChartInstance.config.options.scales.x.beginAtZero = true;
                trendChartInstance.config.options.scales.x.suggestedMax = maxFlow * 1.25;
                trendChartInstance.config.options.scales.y.suggestedMax = undefined;
                trendChartInstance.config.options.layout.padding = { top: 15, right: 55, left: 10, bottom: 10 };
            } else if (type === 'line') {
                trendChartInstance.config.type = 'line';
                trendChartInstance.config.options.indexAxis = 'x';
                trendChartInstance.config.data.datasets[0].fill = true;
                trendChartInstance.config.data.datasets[0].tension = 0.35;
                trendChartInstance.config.data.datasets[0].backgroundColor = 'rgba(34, 197, 94, 0.15)';
                trendChartInstance.config.data.datasets[1].fill = true;
                trendChartInstance.config.data.datasets[1].tension = 0.35;
                trendChartInstance.config.data.datasets[1].backgroundColor = 'rgba(239, 68, 68, 0.15)';
                trendChartInstance.config.options.scales.y.beginAtZero = true;
                trendChartInstance.config.options.scales.y.suggestedMax = maxFlow * 1.25;
                trendChartInstance.config.options.scales.x.suggestedMax = undefined;
                trendChartInstance.config.options.layout.padding = { top: 32, right: 15, left: 10, bottom: 10 };
            } else { // vertical bar
                trendChartInstance.config.type = 'bar';
                trendChartInstance.config.options.indexAxis = 'x';
                trendChartInstance.config.data.datasets[0].fill = false;
                trendChartInstance.config.data.datasets[1].fill = false;
                trendChartInstance.config.options.scales.y.beginAtZero = true;
                trendChartInstance.config.options.scales.y.suggestedMax = maxFlow * 1.25;
                trendChartInstance.config.options.scales.x.suggestedMax = undefined;
                trendChartInstance.config.options.layout.padding = { top: 32, right: 15, left: 10, bottom: 10 };
            }
            trendChartInstance.update();
            trendChartInstance.resize();
        }
    };

    // بيانات الأقسام المشتركة
    const deptLabels = @json($deptChartLabels);
    const deptData = @json($deptChartData);
    const deptColors = @json($deptChartColors);

    // أ) تهيئة مخطط إيرادات الأقسام التفاعلي
    const deptBarCtx = document.getElementById('deptRevenueBarChart');
    if (deptBarCtx && deptData.length > 0) {
        const maxDeptVal = Math.max(...deptData);
        deptBarChartInstance = new Chart(deptBarCtx, {
            type: 'bar',
            data: {
                labels: deptLabels,
                datasets: [{
                    label: 'إيراد القسم',
                    data: deptData,
                    backgroundColor: deptColors,
                    borderColor: deptColors,
                    borderWidth: 1.5,
                    borderRadius: 8,
                    barPercentage: 0.5,
                    categoryPercentage: 0.7
                }]
            },
            plugins: [{
                id: 'deptBarDataLabels',
                afterDatasetsDraw(chart) {
                    const ctx = chart.ctx;
                    const isHoriz = chart.options.indexAxis === 'y';
                    const meta = chart.getDatasetMeta(0);
                    if (!meta.hidden) {
                        meta.data.forEach((element, index) => {
                            const val = chart.data.datasets[0].data[index];
                            if (val !== null && val !== undefined && val > 0) {
                                ctx.save();
                                ctx.font = 'bold 11px "Segoe UI", Cairo, sans-serif';
                                ctx.fillStyle = '#0f172a';
                                const labelText = Number(val).toLocaleString() + ' د.ع';
                                if (isHoriz) {
                                    ctx.textAlign = 'left';
                                    ctx.textBaseline = 'middle';
                                    ctx.fillText(labelText, element.x + 8, element.y);
                                } else {
                                    ctx.textAlign = 'center';
                                    ctx.textBaseline = 'bottom';
                                    ctx.fillText(labelText, element.x, element.y - 6);
                                }
                                ctx.restore();
                            }
                        });
                    }
                }
            }],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { top: 32, right: 15, left: 10, bottom: 10 }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        rtl: true,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.label + ': ' + Number(context.raw).toLocaleString() + ' د.ع';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { family: 'Cairo, sans-serif', size: 12, weight: 'bold' },
                            color: '#334155'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: maxDeptVal * 1.25,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + 'M';
                                if (value >= 1000) return (value / 1000) + 'K';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    // ب) تهيئة مخطط توزيع الإيرادات الدائري البديل (Donut Chart)
    const donutCenterPlugin = {
        id: 'donutCenterText',
        beforeDraw(chart) {
            const { width, height, ctx } = chart;
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            const centerX = width / 2;
            const centerY = height / 2;
            
            ctx.font = '600 12px Cairo, sans-serif';
            ctx.fillStyle = '#64748b';
            ctx.fillText('إجمالي الوارد', centerX, centerY - 14);
            
            ctx.font = 'bold 15px "Segoe UI", Tahoma, sans-serif';
            ctx.fillStyle = '#0f172a';
            ctx.fillText(Number({{ (float)$periodInflow }}).toLocaleString() + ' د.ع', centerX, centerY + 12);
            
            ctx.restore();
        }
    };

    const donutCtx = document.getElementById('deptRevenueDonutChart');
    if (donutCtx && deptData.length > 0) {
        deptDonutChartInstance = new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: deptLabels,
                datasets: [{
                    data: deptData,
                    backgroundColor: deptColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            plugins: [donutCenterPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        rtl: true,
                        labels: {
                            font: { family: 'Cairo, sans-serif', size: 11, weight: 'bold' },
                            boxWidth: 12,
                            boxHeight: 12,
                            padding: 10,
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    const total = {{ (float) max(1, $periodInflow) }};
                                    return data.labels.map(function(label, i) {
                                        const val = data.datasets[0].data[i];
                                        const color = data.datasets[0].backgroundColor[i];
                                        const pct = ((val / total) * 100).toFixed(1);
                                        return {
                                            text: label + ': ' + Number(val).toLocaleString() + ' د.ع (' + pct + '%)',
                                            fillStyle: color,
                                            strokeStyle: '#ffffff',
                                            lineWidth: 1.5,
                                            hidden: false,
                                            index: i
                                        };
                                    });
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        rtl: true,
                        callbacks: {
                            label: function(context) {
                                const val = context.raw || 0;
                                const total = {{ (float) max(1, $periodInflow) }};
                                const pct = ((val / total) * 100).toFixed(1);
                                return ' ' + context.label + ': ' + Number(val).toLocaleString() + ' د.ع (' + pct + '%)';
                            }
                        }
                    }
                },
                cutout: '72%'
            }
        });
    }

    // ج) تهيئة مخطط حركة النقدية اليومية (Daily Cash Flow Trend)
    const barLabelsPlugin = {
        id: 'barLabels',
        afterDatasetsDraw(chart) {
            const ctx = chart.ctx;
            const isHoriz = chart.options.indexAxis === 'y';
            chart.data.datasets.forEach((dataset, datasetIndex) => {
                const meta = chart.getDatasetMeta(datasetIndex);
                if (!meta.hidden) {
                    meta.data.forEach((element, index) => {
                        const dataVal = dataset.data[index];
                        if (dataVal !== null && dataVal !== undefined && dataVal > 0) {
                            ctx.save();
                            ctx.font = 'bold 11px "Segoe UI", Tahoma, sans-serif';
                            ctx.fillStyle = datasetIndex === 0 ? '#15803d' : '#b91c1c';
                            
                            let formatted = Number(dataVal).toLocaleString();
                            if (dataVal >= 1000000) {
                                formatted = (dataVal / 1000000).toFixed(1) + 'M';
                            } else if (dataVal >= 1000) {
                                formatted = Math.round(dataVal / 1000) + 'K';
                            }

                            if (isHoriz) {
                                ctx.textAlign = 'left';
                                ctx.textBaseline = 'middle';
                                ctx.fillText(formatted, element.x + 6, element.y);
                            } else {
                                ctx.textAlign = 'center';
                                ctx.textBaseline = 'bottom';
                                ctx.fillText(formatted, element.x, element.y - 4);
                            }
                            ctx.restore();
                        }
                    });
                }
            });
        }
    };

    const trendLabels = @json($trendLabels);
    const trendInflows = @json($trendInflows);
    const trendOutflows = @json($trendOutflows);

    const trendCtx = document.getElementById('cashFlowTrendChart');
    if (trendCtx) {
        const maxFlow = Math.max(...trendInflows, ...trendOutflows, 1000);
        trendChartInstance = new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: trendLabels,
                datasets: [
                    {
                        label: 'الوارد (+)',
                        data: trendInflows,
                        backgroundColor: 'rgba(34, 197, 94, 0.75)',
                        borderColor: '#16a34a',
                        borderWidth: 1.5,
                        borderRadius: 6,
                    },
                    {
                        label: 'المنصرف (-)',
                        data: trendOutflows,
                        backgroundColor: 'rgba(239, 68, 68, 0.75)',
                        borderColor: '#dc2626',
                        borderWidth: 1.5,
                        borderRadius: 6,
                    }
                ]
            },
            plugins: [barLabelsPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { top: 28, right: 15, left: 10, bottom: 10 }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        rtl: true,
                        labels: {
                            font: { family: 'Cairo, sans-serif', size: 12, weight: 'bold' }
                        }
                    },
                    tooltip: {
                        rtl: true,
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + Number(context.raw).toLocaleString() + ' د.ع';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: maxFlow * 1.2,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + 'M';
                                if (value >= 1000) return (value / 1000) + 'K';
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    // استعادة نمط المخططات المفضل المحفوظ في المتصفح تلقائياً
    try {
        const savedMode = localStorage.getItem('treasury_chart_mode');
        if (savedMode && savedMode !== 'bar') {
            window.setMasterChartMode(savedMode);
        }
    } catch(e) {}
});
// دالة الفلاتر الزمنية السريعة
function setQuickDate(period) {
    const fromInput = document.getElementById('from_date_input');
    const toInput = document.getElementById('to_date_input');
    const now = new Date();

    const formatDate = (d) => {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    if (period === 'today') {
        const todayStr = formatDate(now);
        fromInput.value = todayStr;
        toInput.value = todayStr;
    } else if (period === 'yesterday') {
        const yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        const yStr = formatDate(yesterday);
        fromInput.value = yStr;
        toInput.value = yStr;
    } else if (period === 'week') {
        const weekAgo = new Date(now);
        weekAgo.setDate(now.getDate() - 6);
        fromInput.value = formatDate(weekAgo);
        toInput.value = formatDate(now);
    } else if (period === 'month') {
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        fromInput.value = formatDate(firstDay);
        toInput.value = formatDate(now);
    } else if (period === 'all') {
        fromInput.value = '';
        toInput.value = '';
    }

    document.getElementById('treasuryFilterForm').submit();
}
</script>
@endpush

<style>
@media print {
    .btn, nav, #treasuryTabs, #treasuryFilterForm, .modal, .alert {
        display: none !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    .tab-content > .tab-pane {
        display: block !important;
        opacity: 1 !important;
    }
    body {
        background: #fff !important;
    }
}
</style>
@endsection
