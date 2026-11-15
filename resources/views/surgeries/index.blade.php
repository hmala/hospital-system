@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="surgeriesIndexApp">
    {{-- Modern Executive Surgery Logbook Styles --}}
    <style>
        :root {
            --sg-navy: #0f172a;
            --sg-slate: #1e293b;
            --sg-primary: #0284c7;
            --sg-primary-dark: #0369a1;
            --sg-emerald: #10b981;
            --sg-amber: #f59e0b;
            --sg-rose: #f43f5e;
            --sg-border: #e2e8f0;
            --sg-card-bg: #ffffff;
        }

        /* Canvas Card */
        .sg-canvas-card {
            background-color: #ffffff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.04), 0 4px 6px -2px rgba(15, 23, 42, 0.02);
            border: 1px solid var(--sg-border);
        }

        /* Header */
        .sg-header-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 22px;
        }
        .sg-header-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 20px;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
            flex-shrink: 0;
        }
        .sg-title-main {
            font-weight: 800;
            font-size: 1.35rem;
            color: var(--sg-navy);
            margin: 0;
            line-height: 1.25;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sg-badge-registry {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            padding: 4px 10px;
            border-radius: 20px;
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            text-transform: uppercase;
        }
        .sg-subtitle {
            font-size: 0.84rem;
            color: #64748b;
            margin: 3px 0 0 0;
        }

        /* Buttons */
        .sg-btn-create {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff !important;
            font-weight: 700;
            font-size: 0.86rem;
            padding: 9px 18px;
            border-radius: 12px;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.28);
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .sg-btn-create:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.38);
            color: #ffffff !important;
        }
        .sg-btn-link-action {
            background: #ffffff;
            color: #334155;
            font-weight: 700;
            font-size: 0.84rem;
            padding: 8px 15px;
            border-radius: 12px;
            border: 1px solid var(--sg-border);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .sg-btn-link-action:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
            transform: translateY(-1px);
        }

        /* KPI Cards */
        .sg-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 22px;
        }
        .sg-kpi-card {
            background: #ffffff;
            border: 1px solid var(--sg-border);
            border-radius: 14px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
            position: relative;
            overflow: hidden;
        }
        .sg-kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
            border-color: #cbd5e1;
        }
        .sg-kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            border-radius: 4px 0 0 4px;
        }
        .sg-kpi-card.kpi-total::before { background: #64748b; }
        .sg-kpi-card.kpi-active::before { background: #10b981; }
        .sg-kpi-card.kpi-waiting::before { background: #0284c7; }
        .sg-kpi-card.kpi-completed::before { background: #3b82f6; }
        .sg-kpi-card.kpi-cancelled::before { background: #f43f5e; }

        .sg-kpi-title {
            font-size: 0.78rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
        }
        .sg-kpi-num {
            font-size: 1.55rem;
            font-weight: 900;
            color: var(--sg-navy);
            line-height: 1.1;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
        }
        .sg-kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }
        .kpi-total .sg-kpi-icon { background: #f1f5f9; color: #475569; }
        .kpi-active .sg-kpi-icon { background: #dcfce7; color: #15803d; }
        .kpi-waiting .sg-kpi-icon { background: #e0f2fe; color: #0369a1; }
        .kpi-completed .sg-kpi-icon { background: #eff6ff; color: #2563eb; }
        .kpi-cancelled .sg-kpi-icon { background: #ffe4e6; color: #e11d48; }

        .sg-pulse-dot {
            width: 7px;
            height: 7px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.5);
            animation: sgPulse 1.8s infinite;
            margin-left: 4px;
        }
        @keyframes sgPulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
            70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Filter Box */
        .sg-filter-card {
            background: #f8fafc;
            border: 1px solid var(--sg-border);
            border-radius: 14px;
            padding: 14px 18px;
            margin-bottom: 20px;
        }
        .sg-form-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #475569;
            margin-bottom: 4px;
        }
        .sg-input {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 0.86rem;
            color: #0f172a;
            background: #ffffff;
            transition: all 0.15s ease;
        }
        .sg-input:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
            outline: none;
        }

        /* Segmented Tabs */
        .sg-tabs-strip {
            display: inline-flex;
            background: #f1f5f9;
            padding: 5px;
            border-radius: 12px;
            gap: 4px;
            margin-bottom: 18px;
            border: 1px solid #e2e8f0;
        }
        .sg-tab-btn {
            border: none;
            background: transparent;
            padding: 8px 18px;
            border-radius: 8px;
            font-size: 0.86rem;
            font-weight: 700;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
            cursor: pointer;
        }
        .sg-tab-btn:hover {
            color: #0f172a;
        }
        .sg-tab-btn.active {
            background: #ffffff;
            color: var(--sg-navy);
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
        }
        .sg-tab-pill-badge {
            font-size: 0.72rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 20px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
        }
        .sg-tab-btn.active .badge-active-color {
            background: #dcfce7;
            color: #15803d;
        }
        .sg-tab-btn.active .badge-completed-color {
            background: #eff6ff;
            color: #2563eb;
        }
        .sg-tab-btn.active .badge-or-color {
            background: #fef3c7;
            color: #b45309;
        }

        /* Tables */
        .sg-table-container {
            border: 1px solid var(--sg-border);
            border-radius: 14px;
            overflow: hidden;
            background: #ffffff;
        }
        .sg-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }
        .sg-table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 800;
            font-size: 0.8rem;
            padding: 10px 12px;
            border-bottom: 1px solid var(--sg-border);
            white-space: nowrap;
            text-align: right;
        }
        .sg-table tbody td {
            padding: 9px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.86rem;
            color: #1e293b;
            background: #ffffff;
            white-space: nowrap;
            line-height: 1.2;
            transition: background 0.15s ease;
        }
        .sg-table tbody tr:hover td {
            background: #f8fafc;
        }

        /* Patient clickable button */
        .patient-title-btn {
            font-weight: 800;
            color: #0f172a;
            text-decoration: none;
            font-size: 0.92rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            background: transparent;
            padding: 0;
            cursor: pointer;
            transition: color 0.15s;
        }
        .patient-title-btn:hover {
            color: #0284c7;
        }

        /* Room badges */
        .room-badge-pill {
            padding: 3px 9px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 0.78rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        .room-badge-regular {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .room-badge-vip {
            background: #fefce8;
            color: #854d0e;
            border: 1px solid #fde047;
        }
        .room-badge-vvip {
            background: #0f172a;
            color: #fef08a;
            border: 1px solid #334155;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
        }

        /* Status badges */
        .status-badge-pill {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.76rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        .status-scheduled { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .status-waiting { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .status-in_progress { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
        .status-completed { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .status-cancelled { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Collapse details box */
        .sg-collapse-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }
    </style>

    <div class="sg-canvas-card">
        {{-- 1. Executive Top Header --}}
        <div class="sg-header-box">
            <div class="d-flex align-items-center gap-3">
                <div class="sg-header-icon">
                    <i class="fas fa-procedures"></i>
                </div>
                <div>
                    <h1 class="sg-title-main">
                        سجل العمليات الجراحية
                        <span class="sg-badge-registry">Surgery Registry</span>
                    </h1>
                    <p class="sg-subtitle">متابعة كافة العمليات الجراحية، الحالات النشطة، الأرشيف السريري وتفاصيل المرضى</p>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                @canany(['manage surgery waiting list', 'view surgeries'])
                <a href="{{ route('surgeries.waiting') }}" class="sg-btn-link-action">
                    <i class="fas fa-clock text-warning"></i>
                    <span>قائمة الانتظار والصالات</span>
                </a>
                @endcanany

                @canany(['manage rooms', 'view occupancy'])
                <a href="{{ route('rooms.index') }}" class="sg-btn-link-action">
                    <i class="fas fa-bed text-danger"></i>
                    <span>إدارة الغرف</span>
                </a>
                @endcanany
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm d-flex align-items-center justify-content-center text-center px-5 position-relative mb-3" role="alert">
            <i class="fas fa-check-circle fa-lg me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" style="left: 1rem; right: auto;" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm d-flex align-items-center justify-content-center text-center px-5 position-relative mb-3" role="alert">
            <i class="fas fa-exclamation-circle fa-lg me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" style="left: 1rem; right: auto;" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- 2. KPI Stat Cards --}}
        <div class="sg-kpi-grid">
            <div class="sg-kpi-card kpi-total">
                <div>
                    <div class="sg-kpi-title">إجمالي العمليات المسجلة</div>
                    <div class="sg-kpi-num">{{ $stats->total ?? ($activeSurgeries->total() + $completedSurgeries->total()) }}</div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>

            <div class="sg-kpi-card kpi-active">
                <div>
                    <div class="sg-kpi-title d-flex align-items-center">
                        <span>العمليات النشطة (داخل المسار)</span>
                        <span class="sg-pulse-dot" title="حالات نشطة جارية"></span>
                    </div>
                    <div class="sg-kpi-num text-success">
                        {{ $stats->active ?? $activeSurgeries->total() }}
                    </div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
            </div>

            <div class="sg-kpi-card kpi-waiting">
                <div>
                    <div class="sg-kpi-title">بالانتظار والتحضير</div>
                    <div class="sg-kpi-num text-primary">
                        {{ $stats->waiting ?? 0 }}
                    </div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>

            <div class="sg-kpi-card kpi-completed">
                <div>
                    <div class="sg-kpi-title">المكتملة (خرجت من الصالة)</div>
                    <div class="sg-kpi-num text-primary">
                        {{ $stats->completed ?? $completedSurgeries->total() }}
                    </div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>

            <div class="sg-kpi-card kpi-cancelled">
                <div>
                    <div class="sg-kpi-title">العمليات الملغاة</div>
                    <div class="sg-kpi-num text-danger">
                        {{ $stats->cancelled ?? 0 }}
                    </div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-ban"></i>
                </div>
            </div>
        </div>

        {{-- 3. Search & Filter Bar --}}
        <div class="sg-filter-card">
            <form method="GET" action="{{ route('surgeries.index') }}" class="row g-2 align-items-end">
                <div class="col-lg-5 col-md-6">
                    <label for="search" class="sg-form-label">
                        <i class="fas fa-search me-1 text-primary"></i> البحث السريع
                    </label>
                    <input type="text" class="form-control sg-input" id="search" name="search" 
                           value="{{ request('search') }}" 
                           placeholder="ابحث بالمريض أو الطبيب أو نوع العملية...">
                </div>

                <div class="col-lg-3 col-md-3">
                    <label for="status_filter" class="sg-form-label">
                        <i class="fas fa-filter me-1 text-primary"></i> الحالة
                    </label>
                    <select class="form-select sg-input" id="status_filter" name="status">
                        <option value="">جميع الحالات</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>مجدولة</option>
                        <option value="waiting" {{ request('status') == 'waiting' ? 'selected' : '' }}>في الانتظار</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>جارية</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>مكتملة</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3">
                    <label for="date_filter" class="sg-form-label">
                        <i class="fas fa-calendar-day me-1 text-primary"></i> التاريخ
                    </label>
                    <input type="date" class="form-control sg-input" id="date_filter" name="date" value="{{ request('date') }}">
                </div>

                <div class="col-lg-2 col-md-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold" style="border-radius: 10px; padding: 8px 12px;">
                            <i class="fas fa-search me-1"></i> بحث
                        </button>
                        @if(request('search') || request('status') || request('date'))
                        <a href="{{ route('surgeries.index') }}" class="btn btn-outline-secondary" style="border-radius: 10px; padding: 8px 14px;" title="إعادة تعيين">
                            <i class="fas fa-times"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- 4. Segmented Tabs --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="sg-tabs-strip">
                <button type="button" class="sg-tab-btn {{ $activeIndexTab === 'active' ? 'active' : '' }}" id="btnTabActive" onclick="switchSurgeryTab('active')">
                    <i class="fas fa-clock text-success"></i>
                    <span>العمليات النشطة (المجدولة والمنتظرة والجارية)</span>
                    <span class="sg-tab-pill-badge badge-active-color">{{ $activeSurgeries->total() }}</span>
                </button>
                <button type="button" class="sg-tab-btn {{ $activeIndexTab === 'completed' ? 'active' : '' }}" id="btnTabCompleted" onclick="switchSurgeryTab('completed')">
                    <i class="fas fa-check-circle text-primary"></i>
                    <span>العمليات المكتملة والملغاة (خرجت من الصالة)</span>
                    <span class="sg-tab-pill-badge badge-completed-color">{{ $completedSurgeries->total() }}</span>
                </button>
                @if($canViewOrStoreTab)
                    <button type="button" class="sg-tab-btn {{ $activeIndexTab === 'or_store' ? 'active' : '' }}" id="btnTabOrStore" onclick="switchSurgeryTab('or_store')">
                        <i class="fas fa-warehouse text-warning"></i>
                        <span>مخزن وعُهدة العمليات</span>
                        <span class="sg-tab-pill-badge badge-or-color">{{ $orStockBatches->count() }}</span>
                    </button>
                @endif
            </div>

            <div class="text-muted small fw-bold">
                <i class="fas fa-info-circle me-1 text-primary"></i>
                <span id="tabCounterLabel">
                    @if($activeIndexTab === 'completed')
                        عرض أرشيف وسجل العمليات الجراحية المكتملة والملغاة
                    @elseif($activeIndexTab === 'or_store')
                        جرد ومراقبة عهدة مخزن العمليات وحركات الصرف المباشر للعمليات
                    @else
                        عرض العمليات النشطة حالياً داخل المستشفى
                    @endif
                </span>
            </div>
        </div>

        {{-- 5. Active Surgeries Table --}}
        <div id="paneActiveSurgeries" style="{{ $activeIndexTab === 'active' ? '' : 'display: none;' }}">
            <div class="sg-table-container">
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 10%;" title="حالة المريض في الصالة">الحالة</th>
                                <th>المريض</th>
                                <th>نوع العملية</th>
                                <th>الطبيب الجراح</th>
                                <th>رقم الغرفة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeSurgeries as $surgery)
                            @php
                                $patientName = optional(optional($surgery->patient)->user)->name ?? 'غير محدد';
                                $phone = optional(optional($surgery->patient)->user)->phone;
                                $room = $surgery->room;
                                $rtype = $room ? $room->room_type : null;
                                $otStation = $surgery->operationTheaterStation;
                                $isOtCompleted = ($surgery->status === 'completed') || ($otStation && $otStation->status === 'completed');
                                $isOtInProgress = ($surgery->status === 'in_progress') || ($otStation && $otStation->status === 'in_progress');

                                if ($isOtCompleted) {
                                    $rowStyle = 'background-color: rgba(16, 185, 129, 0.05); border-right: 4px solid #10b981;';
                                    $patientIcon = 'fas fa-check-circle text-success';
                                    $patientColor = 'text-success';
                                    $badgeHtml = '<span class="badge py-0.5 px-2 rounded-pill fw-bold d-inline-flex align-items-center" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; font-size: 0.72rem; letter-spacing: 0.2px;" title="Post-Op (خرج من الصالة)"><i class="fas fa-check-double me-1 text-success" style="font-size: 0.65rem;"></i>Post-Op</span>';
                                } elseif ($isOtInProgress) {
                                    $rowStyle = 'background-color: rgba(2, 132, 199, 0.05); border-right: 4px solid #0284c7;';
                                    $patientIcon = 'fas fa-procedures text-primary';
                                    $patientColor = 'text-primary';
                                    $badgeHtml = '<span class="badge py-0.5 px-2 rounded-pill fw-bold d-inline-flex align-items-center" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 0.72rem; letter-spacing: 0.2px;" title="Intra-Op (داخل الصالة)"><span class="sg-pulse-dot me-1" style="width: 5px; height: 5px;"></span>Intra-Op</span>';
                                } elseif ($surgery->status === 'cancelled') {
                                    $rowStyle = 'background-color: rgba(239, 68, 68, 0.04); border-right: 4px solid #ef4444;';
                                    $patientIcon = 'fas fa-user-times text-danger';
                                    $patientColor = 'text-danger';
                                    $badgeHtml = '<span class="badge py-0.5 px-2 rounded-pill fw-bold d-inline-flex align-items-center" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-size: 0.72rem; letter-spacing: 0.2px;" title="Cancelled (ملغاة)"><i class="fas fa-ban me-1 text-danger" style="font-size: 0.65rem;"></i>Cancelled</span>';
                                } else {
                                    $rowStyle = 'background-color: rgba(245, 158, 11, 0.05); border-right: 4px solid #f59e0b;';
                                    $patientIcon = 'fas fa-user-clock text-warning';
                                    $patientColor = 'text-dark';
                                    $badgeHtml = '<span class="badge py-0.5 px-2 rounded-pill fw-bold d-inline-flex align-items-center" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-size: 0.72rem; letter-spacing: 0.2px;" title="Pre-Op (بالانتظار)"><i class="fas fa-clock me-1 text-warning" style="font-size: 0.65rem;"></i>Pre-Op</span>';
                                }
                            @endphp
                            <tr style="{{ $rowStyle }}">
                                <td>
                                    {!! $badgeHtml !!}
                                </td>
                                <td>
                                    <a href="{{ route('surgeries.show', $surgery) }}" class="patient-title-btn" title="عرض وتعديل تفاصيل العملية">
                                        <i class="{{ $patientIcon }} fs-5"></i>
                                        <span class="{{ $patientColor }}">{{ $patientName }}</span>
                                    </a>
                                    <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                        @if($surgery->patient && $surgery->patient->age)
                                            <span class="badge bg-white text-muted border px-1.5 py-0.5" style="font-size: 0.72rem;">{{ $surgery->patient->age }} سنة</span>
                                        @endif
                                        @if($surgery->patient && $surgery->patient->gender)
                                            <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.72rem;">
                                                {{ in_array($surgery->patient->gender, ['male', 'ذكر']) ? 'ذكر' : 'أنثى' }}
                                            </span>
                                        @endif
                                        @if($phone)
                                            <span class="small text-muted ms-1" style="font-size:0.75rem;">
                                                <i class="fas fa-phone-alt me-1"></i>{{ $phone }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-1.5 text-nowrap">
                                        <span class="fw-bold text-dark text-truncate" style="max-width: 170px;" title="{{ $surgery->surgery_type }}">
                                            {{ $surgery->surgery_type }}
                                        </span>
                                        @if($surgery->insurance_type && $surgery->insurance_type !== 'none')
                                            <span class="badge bg-success-subtle text-success border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="{{ $surgery->insurance_type === 'hi' ? 'ضمان صحي' : 'ضمان قوى الأمن' }}">ضمان</span>
                                        @else
                                            <span class="badge bg-light text-secondary border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="نقدي (كاش)">كاش</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="fas fa-user-md text-success"></i>
                                        <span class="fw-semibold">
                                            @if($surgery->doctor && $surgery->doctor->user)
                                                د. {{ $surgery->doctor->user->name }}
                                            @elseif($surgery->surgeon_name)
                                                {{ $surgery->surgeon_name }} <span class="badge bg-light text-secondary border">خارجي</span>
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if($room)
                                        <span class="room-badge-pill {{ $rtype === 'vvip' ? 'room-badge-vvip' : ($rtype === 'vip' ? 'room-badge-vip' : 'room-badge-regular') }}">
                                            @if($rtype === 'vvip')
                                                <i class="fas fa-gem text-warning"></i> VVIP {{ $room->room_number }}
                                            @elseif($rtype === 'vip')
                                                <i class="fas fa-crown text-warning"></i> VIP {{ $room->room_number }}
                                            @else
                                                <i class="fas fa-bed text-primary"></i> {{ $room->room_number }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border">بدون غرفة</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-procedures fa-3x mb-3 text-secondary"></i>
                                        <div class="fw-bold fs-5">لا توجد عمليات نشطة حالياً مطابقة للبحث</div>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($activeSurgeries->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        عرض {{ $activeSurgeries->firstItem() }} إلى {{ $activeSurgeries->lastItem() }} من أصل {{ $activeSurgeries->total() }} عملية نشطة
                    </div>
                    <div>
                        {{ $activeSurgeries->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- 6. Completed Surgeries Table --}}
        <div id="paneCompletedSurgeries" style="{{ $activeIndexTab === 'completed' ? '' : 'display: none;' }}">
            <div class="sg-table-container">
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th>حالة المريض في الصالة</th>
                                <th>المريض</th>
                                <th>نوع العملية</th>
                                <th>الطبيب الجراح</th>
                                <th>رقم الغرفة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($completedSurgeries as $surgery)
                            @php
                                $patientName = optional(optional($surgery->patient)->user)->name ?? 'غير محدد';
                                $phone = optional(optional($surgery->patient)->user)->phone;
                                $room = $surgery->room;
                                $rtype = $room ? $room->room_type : null;
                                $otStation = $surgery->operationTheaterStation;
                                $isOtCompleted = ($surgery->status === 'completed') || ($otStation && $otStation->status === 'completed');
                                $isOtInProgress = ($surgery->status === 'in_progress') || ($otStation && $otStation->status === 'in_progress');

                                if ($isOtCompleted) {
                                    $rowStyle = 'background-color: rgba(16, 185, 129, 0.05); border-right: 4px solid #10b981;';
                                    $patientIcon = 'fas fa-check-circle text-success';
                                    $patientColor = 'text-success';
                                    $badgeHtml = '<span class="badge py-1 px-2.5 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; font-size: 0.8rem; letter-spacing: 0.3px;" title="خرج من الصالة (مكتملة)"><i class="fas fa-check-double me-1 text-success"></i>Post-Op</span>';
                                } elseif ($isOtInProgress) {
                                    $rowStyle = 'background-color: rgba(2, 132, 199, 0.05); border-right: 4px solid #0284c7;';
                                    $patientIcon = 'fas fa-procedures text-primary';
                                    $patientColor = 'text-primary';
                                    $badgeHtml = '<span class="badge py-1 px-2.5 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 0.8rem; letter-spacing: 0.3px;" title="داخل صالة العمليات (جارية)"><span class="sg-pulse-dot me-1"></span>Intra-Op</span>';
                                } elseif ($surgery->status === 'cancelled') {
                                    $rowStyle = 'background-color: rgba(239, 68, 68, 0.04); border-right: 4px solid #ef4444;';
                                    $patientIcon = 'fas fa-user-times text-danger';
                                    $patientColor = 'text-danger';
                                    $badgeHtml = '<span class="badge py-1 px-2.5 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-size: 0.8rem; letter-spacing: 0.3px;" title="ملغاة"><i class="fas fa-times me-1 text-danger"></i>Cancelled</span>';
                                } else {
                                    $rowStyle = 'background-color: rgba(245, 158, 11, 0.05); border-right: 4px solid #f59e0b;';
                                    $patientIcon = 'fas fa-user-clock text-warning';
                                    $patientColor = 'text-dark';
                                    $badgeHtml = '<span class="badge py-1 px-2.5 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-size: 0.8rem; letter-spacing: 0.3px;" title="قبل دخول الصالة (بالانتظار)"><i class="fas fa-clock me-1 text-warning"></i>Pre-Op</span>';
                                }
                            @endphp
                            <tr style="{{ $rowStyle }}">
                                <td>
                                    {!! $badgeHtml !!}
                                </td>
                                <td>
                                    <a href="{{ route('surgeries.show', $surgery) }}" class="patient-title-btn" title="عرض تفاصيل العملية">
                                        <i class="{{ $patientIcon }} fs-5"></i>
                                        <span class="{{ $patientColor }}">{{ $patientName }}</span>
                                    </a>
                                    <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                        @if($surgery->patient && $surgery->patient->age)
                                            <span class="badge bg-white text-muted border px-1.5 py-0.5" style="font-size: 0.72rem;">{{ $surgery->patient->age }} سنة</span>
                                        @endif
                                        @if($surgery->patient && $surgery->patient->gender)
                                            <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.72rem;">
                                                {{ in_array($surgery->patient->gender, ['male', 'ذكر']) ? 'ذكر' : 'أنثى' }}
                                            </span>
                                        @endif
                                        @if($phone)
                                            <span class="small text-muted ms-1" style="font-size:0.75rem;">
                                                <i class="fas fa-phone-alt me-1"></i>{{ $phone }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-inline-flex align-items-center gap-1.5 text-nowrap">
                                        <span class="fw-bold text-dark text-truncate" style="max-width: 170px;" title="{{ $surgery->surgery_type }}">
                                            {{ $surgery->surgery_type }}
                                        </span>
                                        @if($surgery->insurance_type && $surgery->insurance_type !== 'none')
                                            <span class="badge bg-success-subtle text-success border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="{{ $surgery->insurance_type === 'hi' ? 'ضمان صحي' : 'ضمان قوى الأمن' }}">ضمان</span>
                                        @else
                                            <span class="badge bg-light text-secondary border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="نقدي (كاش)">كاش</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="fas fa-user-md text-secondary"></i>
                                        <span class="fw-semibold">
                                            @if($surgery->doctor && $surgery->doctor->user)
                                                د. {{ $surgery->doctor->user->name }}
                                            @elseif($surgery->surgeon_name)
                                                {{ $surgery->surgeon_name }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if($room)
                                        <span class="room-badge-pill {{ $rtype === 'vvip' ? 'room-badge-vvip' : ($rtype === 'vip' ? 'room-badge-vip' : 'room-badge-regular') }}">
                                            @if($rtype === 'vvip')
                                                <i class="fas fa-gem text-warning"></i> VVIP {{ $room->room_number }}
                                            @elseif($rtype === 'vip')
                                                <i class="fas fa-crown text-warning"></i> VIP {{ $room->room_number }}
                                            @else
                                                <i class="fas fa-bed text-primary"></i> {{ $room->room_number }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="badge bg-light text-muted border">بدون غرفة</span>
                                    @endif
                                </td>
                            </tr>


                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-archive fa-3x mb-3 text-secondary"></i>
                                        <div class="fw-bold fs-5">لا توجد عمليات مكتملة أو مؤرشفة مطابقة للبحث</div>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($completedSurgeries->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        عرض {{ $completedSurgeries->firstItem() }} إلى {{ $completedSurgeries->lastItem() }} من أصل {{ $completedSurgeries->total() }} عملية مؤرشفة
                    </div>
                    <div>
                        {{ $completedSurgeries->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- 7. Operations Sub-Store Custody Tab (مخزن وعُهدة العمليات — للحساب المعين له المخزن فقط) --}}
        @if($canViewOrStoreTab && $orCurrentLocation)
        <div id="paneOrStore" style="{{ $activeIndexTab === 'or_store' ? '' : 'display: none;' }}">
                {{-- OR Store Top Action Header --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);">
                    <div class="card-body p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="rounded-circle bg-warning bg-opacity-25 text-warning-emphasis d-flex align-items-center justify-content-center shadow-xs" style="width: 48px; height: 48px;">
                                <i class="fas fa-boxes fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="mb-1 fw-bold text-dark">دولاب ومخزن: {{ $orCurrentLocation->name }}</h6>
                                <p class="text-muted small mb-0 d-flex flex-wrap align-items-center gap-2">
                                    <span>المسؤول عن العهدة:</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5">
                                        <i class="fas fa-check-circle me-1"></i> حسابك مخول بالطلب والصرف
                                    </span>
                                    <span>• إجمالي المواد المتوفرة: <strong class="text-primary">{{ $orStockBatches->sum('current_qty') }}</strong> وحدة</span>
                                </p>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <a href="{{ route('stock-transfers.create', ['to_location_id' => $orCurrentLocation->id]) }}" class="btn btn-warning rounded-pill px-3 py-2 fw-bold shadow-sm text-dark">
                                <i class="fas fa-plus-circle me-1"></i> + طلب تعزيز مواد
                            </a>
                            <a href="{{ route('stock-transfers.returns.create', ['from_location_id' => $orCurrentLocation->id, 'from_surgery' => 1]) }}" class="btn btn-outline-danger rounded-pill px-3 py-2 fw-bold shadow-xs">
                                <i class="fas fa-undo-alt me-1"></i> إرجاع عهدة للمخزن الرئيسي
                            </a>
                            <a href="{{ route('stock-transfers.requests.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 shadow-xs">
                                <i class="fas fa-history me-1"></i> سجل الطلبيات
                            </a>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    {{-- OR Stock Inventory Table --}}
                    <div class="col-lg-8">
                        <div class="card border-0 rounded-4 shadow-sm h-100 bg-white">
                            <div class="card-header bg-white py-3 px-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-boxes text-primary fs-5"></i>
                                    <h6 class="mb-0 fw-bold text-dark">المستلزمات والأدوية المتوفرة في عهدة العمليات</h6>
                                </div>
                                <span class="badge bg-primary text-white rounded-pill px-3 py-1" id="orStockItemsCountBadge">{{ $orStockBatches->count() }} مادة</span>
                            </div>

                            {{-- Live Search & Category Filters --}}
                            <div class="p-3 bg-light border-bottom">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-5">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                            <input type="text" id="orStockSearchInput" class="form-control bg-white border-start-0 ps-0" placeholder="بحث باسم المادة، الباركود، أو رقم الوجبة...">
                                            <button class="btn btn-outline-secondary border-start-0 bg-white" type="button" id="clearOrStockSearch" style="display:none;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-7 d-flex align-items-center gap-1 flex-wrap justify-content-md-end">
                                        <span class="text-muted small me-1">تصفية:</span>
                                        <button type="button" class="btn btn-xs btn-dark rounded-pill px-2 py-1 or-stock-filter-btn active" data-filter="all">الكل</button>
                                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1 or-stock-filter-btn" data-filter="supplies">🩹 مستلزمات</button>
                                        <button type="button" class="btn btn-xs btn-outline-info rounded-pill px-2 py-1 or-stock-filter-btn" data-filter="medicines">💊 أدوية/أخرى</button>
                                        <button type="button" class="btn btn-xs btn-outline-warning rounded-pill px-2 py-1 or-stock-filter-btn" data-filter="low">⚠️ نفاد</button>
                                        <button type="button" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-1 or-stock-filter-btn" data-filter="expiring">⏳ قريبة الانتهاء</button>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="orStockTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="py-2 px-3">المادة / المستلزم</th>
                                                <th class="py-2 text-center">الرصيد الحالي</th>
                                                <th class="py-2 text-center">تاريخ الصلاحية</th>
                                                <th class="py-2 text-center">رقم الوجبة</th>
                                                <th class="py-2 text-center">الحالة</th>
                                                <th class="py-2 text-center" style="width: 100px;">إجراء</th>
                                            </tr>
                                        </thead>
                                        <tbody id="orStockTableBody">
                                            @forelse($orStockBatches as $batch)
                                                @php
                                                    $isExpiringSoon = $batch->expiry_date && $batch->expiry_date->diffInDays(now()) <= 60;
                                                    $isLowStock = $batch->current_qty <= ($batch->product?->alert_quantity ?? 5);
                                                    $isSupply = $batch->product?->isSupply() ?? false;
                                                    $isMedicine = ! $isSupply;
                                                @endphp
                                                <tr class="or-stock-row"
                                                    data-name="{{ mb_strtolower($batch->product?->name ?? '') }}"
                                                    data-barcode="{{ mb_strtolower($batch->internal_barcode ?: ($batch->manufacturer_lot_number ?: '#'.$batch->id)) }}"
                                                    data-is-supply="{{ $isSupply ? '1' : '0' }}"
                                                    data-is-medicine="{{ $isMedicine ? '1' : '0' }}"
                                                    data-is-low="{{ $isLowStock ? '1' : '0' }}"
                                                    data-is-expiring="{{ $isExpiringSoon ? '1' : '0' }}">
                                                    <td class="px-3">
                                                        <div class="fw-bold text-dark">{{ $batch->product?->name ?? 'مادة غير معروفة' }}</div>
                                                        <small class="text-muted">{{ $batch->product?->category ?? 'عام' }} • وحدة: {{ $batch->product?->unit ?? 'قطعة' }}</small>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="fw-bolder fs-6 {{ $isLowStock ? 'text-danger' : 'text-success' }}">
                                                            {{ $batch->current_qty }}
                                                        </span>
                                                        <small class="text-muted d-block">{{ $batch->product?->unit ?? 'وحدة' }}</small>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($batch->expiry_date)
                                                            <span class="badge {{ $isExpiringSoon ? 'bg-danger text-white' : 'bg-light text-dark border' }} rounded-pill px-2 py-1">
                                                                {{ $batch->expiry_date->format('Y-m-d') }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">
                                                            {{ $batch->internal_barcode ?: ($batch->manufacturer_lot_number ?: '#'.$batch->id) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        @if($isLowStock)
                                                            <span class="badge bg-warning text-dark rounded-pill px-2 py-1">شارف على النفاد</span>
                                                        @else
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">متوفر</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if($isOperationCustodian)
                                                            <a href="{{ route('stock-transfers.returns.create', ['from_location_id' => $orCurrentLocation->id, 'batch_id' => $batch->id, 'from_surgery' => 1]) }}"
                                                               class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1 fw-bold text-nowrap shadow-xs"
                                                               title="إرجاع هذه الوجبة للمخزن الرئيسي">
                                                                <i class="fas fa-undo-alt me-1"></i> إرجاع
                                                            </a>
                                                        @else
                                                            <span class="text-muted small">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-5 text-muted">
                                                        <i class="fas fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                                                        <p class="mb-0 fw-semibold">لا يوجد رصيد مواد مسجل في {{ $orCurrentLocation->name }} حالياً</p>
                                                        @if($isOperationCustodian)
                                                            <a href="{{ route('stock-transfers.create', ['to_location_id' => $orCurrentLocation->id]) }}" class="btn btn-sm btn-warning rounded-pill mt-3 fw-bold text-dark">
                                                                <i class="fas fa-plus-circle me-1"></i> اطلب أول شحنة تعزيز من المخزن الرئيسي
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforelse
                                            <tr id="noMatchingOrStockRow" style="display: none;">
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    <i class="fas fa-search fa-2x mb-2 text-secondary opacity-50"></i>
                                                    <p class="mb-0 fw-semibold">لا توجد مواد تطابق معايير البحث والتصفية المحددة.</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Recent OR Stock Movements Feed --}}
                    <div class="col-lg-4">
                        <div class="card border-0 rounded-4 shadow-sm h-100 bg-white">
                            <div class="card-header bg-white py-3 px-3 border-bottom d-flex align-items-center justify-content-between">
                                <h6 class="mb-0 fw-bold text-dark">
                                    <i class="fas fa-history text-info me-2"></i> سجل حركات مخزن العمليات
                                </h6>
                                <span class="badge bg-info text-white rounded-pill px-2 py-1 small">مباشر</span>
                            </div>
                            <div class="card-body p-3">
                                @if($orRecentMovements->count())
                                    <div class="list-group list-group-flush">
                                        @foreach($orRecentMovements as $mv)
                                            <div class="list-group-item px-0 py-2 border-bottom">
                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                    <div>
                                                        <div class="fw-bold text-dark small">{{ $mv->batch?->product?->name ?? 'مادة مخزنية' }}</div>
                                                        <small class="text-muted d-block">
                                                            <i class="fas fa-user text-primary me-1"></i>
                                                            بواسطة: <strong>{{ $mv->user?->full_name ?? $mv->user?->name ?? 'كادر العمليات' }}</strong>
                                                        </small>
                                                        @if($mv->surgery)
                                                            <small class="text-primary d-block">
                                                                <i class="fas fa-procedures me-1"></i>
                                                                <a href="{{ route('surgeries.show', ['surgery' => $mv->surgery_id, 'tab' => 'detail_supplies']) }}" class="text-decoration-none">
                                                                    عملية #{{ $mv->surgery_id }} — {{ $mv->surgery->patient?->user?->name ?? 'مريض' }}
                                                                </a>
                                                            </small>
                                                        @endif
                                                    </div>
                                                    @if($mv->type === 'return')
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">
                                                            +{{ $mv->quantity }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill px-2 py-1">
                                                            -{{ $mv->quantity }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">
                                                    <i class="far fa-clock me-1"></i> {{ $mv->created_at ? $mv->created_at->diffForHumans() : '-' }}
                                                </small>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-5 text-muted small">
                                        <i class="fas fa-clipboard-check fa-2x mb-2 text-secondary opacity-50"></i>
                                        <p class="mb-0">لا توجد حركات مسجلة حديثاً في {{ $orCurrentLocation->name }}.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        @endif
    </div>
</div>



<script>
    function switchSurgeryTab(tabName) {
        const btnActive = document.getElementById('btnTabActive');
        const btnCompleted = document.getElementById('btnTabCompleted');
        const btnOrStore = document.getElementById('btnTabOrStore');
        const paneActive = document.getElementById('paneActiveSurgeries');
        const paneCompleted = document.getElementById('paneCompletedSurgeries');
        const paneOrStore = document.getElementById('paneOrStore');
        const counterLabel = document.getElementById('tabCounterLabel');

        [btnActive, btnCompleted, btnOrStore].forEach(btn => btn && btn.classList.remove('active'));
        [paneActive, paneCompleted, paneOrStore].forEach(pane => {
            if (pane) pane.style.display = 'none';
        });

        if (tabName === 'completed') {
            btnCompleted && btnCompleted.classList.add('active');
            if (paneCompleted) paneCompleted.style.display = 'block';
            if (counterLabel) counterLabel.textContent = 'عرض أرشيف وسجل العمليات الجراحية المكتملة والملغاة';
        } else if (tabName === 'or_store' && paneOrStore) {
            btnOrStore && btnOrStore.classList.add('active');
            paneOrStore.style.display = 'block';
            if (counterLabel) counterLabel.textContent = 'جرد ومراقبة عهدة مخزن العمليات وحركات الصرف المباشر للعمليات';
        } else {
            btnActive && btnActive.classList.add('active');
            if (paneActive) paneActive.style.display = 'block';
            if (counterLabel) counterLabel.textContent = 'عرض العمليات النشطة حالياً داخل المستشفى';
        }

        if (window.history && window.history.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url.toString());
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('orStockSearchInput');
        const clearBtn = document.getElementById('clearOrStockSearch');
        const filterBtns = document.querySelectorAll('.or-stock-filter-btn');
        const rows = document.querySelectorAll('#orStockTableBody .or-stock-row');
        const noMatchRow = document.getElementById('noMatchingOrStockRow');
        const countBadge = document.getElementById('orStockItemsCountBadge');

        let currentOrFilter = 'all';

        function filterOrStockTable() {
            const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
            let visibleCount = 0;

            if (clearBtn) {
                clearBtn.style.display = query.length > 0 ? 'inline-block' : 'none';
            }

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const barcode = row.getAttribute('data-barcode') || '';
                const isSupply = row.getAttribute('data-is-supply') === '1';
                const isMedicine = row.getAttribute('data-is-medicine') === '1';
                const isLow = row.getAttribute('data-is-low') === '1';
                const isExpiring = row.getAttribute('data-is-expiring') === '1';

                const matchesText = !query || name.includes(query) || barcode.includes(query);

                let matchesFilter = true;
                if (currentOrFilter === 'supplies') {
                    matchesFilter = isSupply;
                } else if (currentOrFilter === 'medicines') {
                    matchesFilter = isMedicine;
                } else if (currentOrFilter === 'low') {
                    matchesFilter = isLow;
                } else if (currentOrFilter === 'expiring') {
                    matchesFilter = isExpiring;
                }

                if (matchesText && matchesFilter) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noMatchRow) {
                noMatchRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
            }

            if (countBadge) {
                countBadge.textContent = visibleCount + ' مادة';
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterOrStockTable);
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                searchInput.value = '';
                filterOrStockTable();
                searchInput.focus();
            });
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                filterBtns.forEach(b => {
                    b.classList.remove('active', 'btn-dark', 'btn-info', 'btn-warning', 'btn-danger');
                    const f = b.getAttribute('data-filter');
                    if (f === 'medicines') {
                        b.classList.add('btn-outline-info');
                    } else if (f === 'low') {
                        b.classList.add('btn-outline-warning');
                    } else if (f === 'expiring') {
                        b.classList.add('btn-outline-danger');
                    } else {
                        b.classList.add('btn-outline-secondary');
                    }
                });

                this.classList.add('active');
                this.classList.remove('btn-outline-secondary', 'btn-outline-info', 'btn-outline-warning', 'btn-outline-danger');
                const filter = this.getAttribute('data-filter');
                if (filter === 'medicines') {
                    this.classList.add('btn-info');
                } else if (filter === 'low') {
                    this.classList.add('btn-warning');
                } else if (filter === 'expiring') {
                    this.classList.add('btn-danger');
                } else {
                    this.classList.add('btn-dark');
                }

                currentOrFilter = filter;
                filterOrStockTable();
            });
        });

        // ==========================================
        // Realtime Live Sync for Surgeries Index
        // ==========================================
        let isRefreshingSurgeriesIndex = false;
        async function refreshSurgeriesIndexLive() {
            if (isRefreshingSurgeriesIndex) return;
            isRefreshingSurgeriesIndex = true;
            try {
                const response = await fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    }
                });
                if (response.ok) {
                    const htmlText = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(htmlText, 'text/html');

                    const newPane = doc.getElementById('paneActiveSurgeries');
                    const currPane = document.getElementById('paneActiveSurgeries');
                    if (newPane && currPane && newPane.innerHTML.trim() !== currPane.innerHTML.trim()) {
                        currPane.innerHTML = newPane.innerHTML;
                    }

                    const newCompleted = doc.getElementById('paneCompletedSurgeries');
                    const currCompleted = document.getElementById('paneCompletedSurgeries');
                    if (newCompleted && currCompleted && newCompleted.innerHTML.trim() !== currCompleted.innerHTML.trim()) {
                        currCompleted.innerHTML = newCompleted.innerHTML;
                    }

                    const newTabBtnActive = doc.getElementById('btnTabActive');
                    const currTabBtnActive = document.getElementById('btnTabActive');
                    if (newTabBtnActive && currTabBtnActive) {
                        currTabBtnActive.innerHTML = newTabBtnActive.innerHTML;
                    }

                    const newTabBtnComp = doc.getElementById('btnTabCompleted');
                    const currTabBtnComp = document.getElementById('btnTabCompleted');
                    if (newTabBtnComp && currTabBtnComp) {
                        currTabBtnComp.innerHTML = newTabBtnComp.innerHTML;
                    }
                }
            } catch (e) {
                console.warn('Index live sync error:', e);
            } finally {
                isRefreshingSurgeriesIndex = false;
            }
        }

        window.refreshSurgeriesIndexLive = refreshSurgeriesIndexLive;

        if (typeof BroadcastChannel !== 'undefined') {
            try {
                const liveChannel = new BroadcastChannel('hospital_surgery_live_channel');
                liveChannel.onmessage = function (event) {
                    if (event.data && event.data.action === 'surgery_updated') {
                        refreshSurgeriesIndexLive();
                    }
                };
            } catch (err) {}
        }

        window.addEventListener('storage', function(e) {
            if (e.key === 'hospital_surgery_live_trigger') {
                refreshSurgeriesIndexLive();
            }
        });

        if (window.Echo) {
            try {
                window.Echo.channel('surgeries').listen('.surgery.updated', function() {
                    refreshSurgeriesIndexLive();
                });
            } catch (e) {}
        }
    });


</script>
@endsection
