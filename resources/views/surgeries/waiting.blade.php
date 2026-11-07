@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="orScheduleApp">
    {{-- Ultra-Modern Hospital EHR Flight Board Styles --}}
    <style>
        :root {
            --or-navy: #0f172a;
            --or-slate-dark: #1e293b;
            --or-primary: #0284c7;
            --or-primary-dark: #0369a1;
            --or-primary-light: #f0f9ff;
            --or-emerald: #10b981;
            --or-amber: #f59e0b;
            --or-rose: #f43f5e;
            --or-border: #e2e8f0;
            --or-card-bg: #ffffff;
        }

        /* Canvas Card Container */
        .or-canvas-card {
            background-color: #ffffff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.04), 0 4px 6px -2px rgba(15, 23, 42, 0.02);
            border: 1px solid var(--or-border);
        }

        /* Top Executive Header */
        .or-header-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 22px;
        }
        .or-header-icon {
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

        /* ===== Compact Month Calendar Grid & Side KPI Dashboard ===== */
        .mini-calendar-card {
            background: #ffffff;
            border: 1px solid var(--or-border);
            border-radius: 16px;
        }
        .mini-cal-title {
            font-size: 0.92rem;
            font-weight: 800;
            color: #0f172a;
        }
        .calendar-weekdays-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            text-align: center;
            font-weight: 700;
            font-size: 0.72rem;
            color: #64748b;
            margin-bottom: 5px;
        }
        .calendar-weekdays-header > div {
            background: #f1f5f9;
            padding: 3px 2px;
            border-radius: 6px;
        }
        .calendar-days-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        .calendar-day-cell {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 3px 6px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            position: relative;
        }
        .calendar-day-cell:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(15, 23, 42, 0.08);
            border-color: #3b82f6;
        }
        .calendar-day-cell .day-number {
            font-size: 0.8rem;
            font-weight: 700;
            color: #1e293b;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
        }
        .calendar-day-cell .badge-surgery-count {
            font-size: 0.65rem;
            font-weight: 800;
            background: #10b981;
            color: #ffffff;
            border-radius: 5px;
            padding: 1px 5px;
            min-width: 17px;
            height: 17px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 3px rgba(16, 185, 129, 0.25);
        }
        .today-dot-mini {
            width: 5px;
            height: 5px;
            background-color: #f59e0b;
            border-radius: 50%;
            display: inline-block;
        }

        /* State 1: Has Surgeries */
        .calendar-day-cell.day-has-surgeries {
            background: #f0fdf4;
            border-color: #86efac;
        }
        .calendar-day-cell.day-has-surgeries .day-number {
            color: #15803d;
            font-weight: 800;
        }

        /* State 2: Today */
        .calendar-day-cell.day-today {
            box-shadow: 0 0 0 2px #f59e0b, 0 2px 6px rgba(245, 158, 11, 0.18) !important;
            border-color: #f59e0b !important;
        }

        /* State 3: Selected Day */
        .calendar-day-cell.day-selected {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;
            border-color: #1e3a8a !important;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.35) !important;
            z-index: 2;
        }
        .calendar-day-cell.day-selected .day-number {
            color: #ffffff !important;
        }
        .calendar-day-cell.day-selected .badge-surgery-count {
            background: #ffffff;
            color: #1e3a8a;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
        }

        /* Outside Month */
        .calendar-day-cell.day-outside-month {
            opacity: 0.35;
            background: #f8fafc;
            border-color: #f1f5f9;
        }

        /* Legend boxes */
        .legend-color-box {
            border-radius: 3px;
            display: inline-block;
        }
        .legend-selected { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); }
        .legend-today { background: #f59e0b; }
        .legend-has-ops { background: #10b981; }

        /* KPI Side Dashboard Cards */
        .kpi-side-card {
            background: #f8fafc;
            border: 1.5px solid var(--or-border);
            border-radius: 12px;
            padding: 9px 12px;
            cursor: pointer;
            transition: all 0.18s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            user-select: none;
        }
        .kpi-side-card:hover {
            transform: translateY(-1px);
            border-color: #cbd5e1;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
        .kpi-side-card.kpi-active {
            border-color: var(--or-primary);
            background: #f0f9ff;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.18);
        }
        .kpi-icon-wrap {
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Modern Table Styling */
        .or-table-shell {
            border: 1px solid var(--or-border);
            border-radius: 16px;
            overflow: hidden;
            background: #ffffff;
        }
        .or-master-table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 0;
        }
        .or-master-table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
            padding: 14px 16px;
            border-bottom: 2px solid var(--or-border);
            white-space: nowrap;
        }
        .or-master-table tbody tr {
            transition: all 0.15s ease;
            border-bottom: 1px solid #f1f5f9;
        }
        .or-master-table tbody tr:last-child {
            border-bottom: none;
        }
        .or-master-table tbody tr:hover {
            background-color: #f8fafc;
        }
        .or-master-table tbody td {
            padding: 12px 16px;
            vertical-align: middle;
        }

        /* Patient Avatar */
        .patient-avatar-box {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 15px;
            color: #ffffff;
            background: linear-gradient(135deg, #0284c7 0%, #1e40af 100%);
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.2);
            flex-shrink: 0;
        }

        /* Status & Timer Badges */
        .badge-stage-pill {
            font-size: 0.78rem;
            padding: 5px 12px;
            border-radius: 30px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .live-pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-ring 1.8s infinite;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Stopwatch Live Capsule */
        .live-stopwatch-capsule {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 0.76rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Success save highlight */
        .inline-save-success {
            animation: highlight-green 1.5s ease-out;
        }
        @keyframes highlight-green {
            0% { background-color: #dcfce7; border-color: #10b981; }
            100% { background-color: transparent; }
        }

        /* Modern Form Controls Inside Table */
        .or-inline-select {
            border: 1.5px solid var(--or-border);
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #1e293b;
            background-color: #f8fafc;
            padding: 6px 10px;
            transition: all 0.2s;
        }
        .or-inline-select:focus {
            background-color: #ffffff;
            border-color: var(--or-primary);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        }
        .or-inline-time {
            border: 1.5px solid var(--or-border);
            border-radius: 10px;
            font-size: 0.83rem;
            font-weight: 700;
            color: #0f172a;
            background-color: #f8fafc;
            padding: 5px 8px;
            text-align: center;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
            transition: all 0.2s;
        }
        .or-inline-time:focus {
            background-color: #ffffff;
            border-color: var(--or-primary);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        }

        /* Quick action buttons */
        .btn-action-stage {
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 6px 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-action-stage:hover {
            transform: translateY(-1px);
        }

        /* Fullscreen TV Flight Board Display */
        body.tv-display-active .sidebar,
        body.tv-display-active .app-header,
        body.tv-display-active .sidebar-backdrop,
        body.tv-display-active footer {
            display: none !important;
        }
        body.tv-display-active main,
        body.tv-display-active #orScheduleApp {
            margin: 0 !important;
            padding: 8px !important;
            max-width: 100vw !important;
            width: 100vw !important;
        }
        body.tv-display-active .or-canvas-card {
            border-radius: 12px;
            padding: 16px;
        }
    </style>

    {{-- Main Container Card --}}
    <div class="or-canvas-card mb-4">
        {{-- Header Section: Title & Actions --}}
        <div class="or-header-box">
            <div class="d-flex align-items-center gap-3">
                <div class="or-header-icon">
                    <i class="fas fa-procedures"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="mb-0 fw-bolder text-dark" style="font-size: 1.35rem; color: #0f172a;">جدول وقائمة انتظار العمليات الجراحية</h4>
                        <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small fw-bold">OR Flight Board</span>
                    </div>
                    <div class="text-muted small mt-1">
                        متابعة وتحديث حالات العمليات، الصالات المخصصة، والمواعيد
                        <span class="mx-1">•</span>
                        <span class="text-dark fw-semibold"><i class="far fa-clock text-primary me-0.5"></i> التوقيت المباشر للمركز</span>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                @can('create surgeries')
                <a href="{{ route('surgeries.create') }}" class="btn rounded-pill px-3 py-2 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none;">
                    <i class="fas fa-plus me-1"></i> حجز عملية جديدة
                </a>
                @endcan

                @can('view surgeries')
                <a href="{{ route('surgeries.index') }}" class="btn btn-outline-secondary bg-white rounded-pill px-3 py-2 shadow-sm fw-semibold" style="border-color: #cbd5e1; color: #334155;">
                    <i class="fas fa-archive me-1 text-secondary"></i> الأرشيف العام
                </a>
                @endcan

                <button type="button" class="btn btn-outline-primary bg-white rounded-pill px-3 py-2 shadow-sm fw-bold d-inline-flex align-items-center gap-1.5" id="btnToggleDashboard" data-bs-toggle="collapse" data-bs-target="#topDashboardCollapse" aria-expanded="true" title="طي أو إظهار التقويم والمؤشرات لتكبير مساحة الجدول">
                    <i class="fas fa-chevron-up" id="toggleDashboardIcon"></i> <span id="toggleDashboardText">طي التقويم</span>
                </button>

                <button type="button" class="btn btn-outline-dark bg-white rounded-pill px-3 py-2 shadow-sm fw-semibold" id="btnToggleTvMode" onclick="toggleTvDisplayMode()" title="عرض مخصص لشاشات الـ TV وممرات العمليات">
                    <i class="fas fa-tv me-1 text-primary"></i> <span id="tvModeText">شاشة العرض (TV)</span>
                </button>

                <button type="button" class="btn btn-light border rounded-pill px-3 py-2 shadow-sm text-dark fw-bold" onclick="location.reload();">
                    <i class="fas fa-rotate me-1 text-primary"></i> تحديث
                </button>
            </div>
        </div>

        {{-- Side-by-Side Dashboard: Compact Month Calendar Grid & Interactive KPIs --}}
        @php
            $inTheaterCount = $daySurgeries->filter(function($s) {
                $isDone = $s->status === 'completed' || ($s->operationTheaterStation && $s->operationTheaterStation->status === 'completed');
                return $s->status === 'in_progress' && ! $isDone;
            })->count();

            $completedCount = $daySurgeries->filter(function($s) {
                return $s->status === 'completed' || ($s->operationTheaterStation && $s->operationTheaterStation->status === 'completed');
            })->count();

            $waitingCount = $activeSurgeries->whereIn('status', ['waiting', 'checked_in'])->count();
        @endphp

        <div class="collapse show" id="topDashboardCollapse">
            <div class="row g-3 mb-4 align-items-stretch">
            {{-- Right Column: Compact Month Calendar Grid (التقويم الشهري المدمج) --}}
            <div class="col-12 col-lg-7 col-xl-7">
                <div class="mini-calendar-card shadow-sm h-100 d-flex flex-column justify-content-between p-3">
                    {{-- Mini Cal Header --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 mb-2 border-bottom">
                        <div class="d-flex align-items-center gap-1.5">
                            <a href="{{ route('surgeries.waiting', ['date' => $prevMonthDate]) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5" title="الشهر السابق" style="font-size: 0.76rem;">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <span class="mini-cal-title px-1">
                                <i class="far fa-calendar-alt text-primary me-1"></i> {{ $monthTitle }}
                            </span>
                            <a href="{{ route('surgeries.waiting', ['date' => $nextMonthDate]) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5" title="الشهر القادم" style="font-size: 0.76rem;">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </div>
                        <div class="d-flex align-items-center gap-1.5">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">
                                {{ $totalMonthSurgeries }} عملية بالشهر
                            </span>
                            <a href="{{ route('surgeries.waiting', ['date' => today()->format('Y-m-d')]) }}" class="btn btn-sm {{ today()->format('Y-m-d') === $selectedDate ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill px-2.5 py-0.5 fw-bold" style="font-size: 0.74rem;">
                                🌟 اليوم
                            </a>
                        </div>
                    </div>

                    {{-- 7 Weekdays Header --}}
                    <div class="calendar-weekdays-header">
                        <div>السبت</div>
                        <div>الأحد</div>
                        <div>الاثنين</div>
                        <div>الثلاثاء</div>
                        <div>الأربعاء</div>
                        <div>الخميس</div>
                        <div>الجمعة</div>
                    </div>

                    {{-- Calendar Days Grid (7 columns) --}}
                    <div class="calendar-days-grid my-auto">
                        @foreach($calendarDays as $day)
                            @php
                                $cellClass = [];
                                if ($day['is_selected']) {
                                    $cellClass[] = 'day-selected';
                                } elseif ($day['is_today']) {
                                    $cellClass[] = 'day-today';
                                } elseif ($day['count'] > 0) {
                                    $cellClass[] = 'day-has-surgeries';
                                } else {
                                    $cellClass[] = 'day-empty';
                                }

                                if (!$day['is_current_month']) {
                                    $cellClass[] = 'day-outside-month';
                                }
                            @endphp
                            <a href="{{ route('surgeries.waiting', ['date' => $day['date']]) }}" 
                               class="calendar-day-cell {{ implode(' ', $cellClass) }}"
                               title="{{ $day['day_name'] }} {{ $day['date'] }} ({{ $day['count'] }} عمليات)">
                                <span class="day-number">{{ $day['day_number'] }}</span>
                                @if($day['count'] > 0)
                                    <span class="badge-surgery-count">{{ $day['count'] }}</span>
                                @elseif($day['is_today'])
                                    <span class="today-dot-mini"></span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    {{-- Mini Cal Footer Legend --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pt-2 mt-2 border-top small text-muted" style="font-size: 0.72rem;">
                        <div class="d-flex align-items-center gap-2.5">
                            <span class="d-flex align-items-center gap-1">
                                <span class="legend-color-box legend-selected" style="width: 10px; height: 10px;"></span>
                                <span>المختار: <strong>{{ $parsedDate->format('d/m') }}</strong></span>
                            </span>
                            <span class="d-flex align-items-center gap-1">
                                <span class="legend-color-box legend-today" style="width: 10px; height: 10px;"></span>
                                <span>اليوم</span>
                            </span>
                            <span class="d-flex align-items-center gap-1">
                                <span class="legend-color-box legend-has-ops" style="width: 10px; height: 10px;"></span>
                                <span>أيام بها عمليات</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Left Column: Compact KPI Cards Dashboard (كروت المؤشرات بجانب التقويم) --}}
            <div class="col-12 col-lg-5 col-xl-5">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white d-flex flex-column justify-content-between" style="border: 1px solid var(--or-border) !important;">
                    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
                        <span class="fw-bold text-dark small d-flex align-items-center gap-1.5" style="font-size: 0.84rem;">
                            <i class="fas fa-chart-pie text-primary"></i>
                            <span>مؤشرات: {{ $parsedDate->locale('ar')->translatedFormat('l، d F') }}</span>
                        </span>
                        <span class="badge bg-light text-muted border px-2 py-0.5 font-monospace" style="font-size: 0.72rem;">
                            {{ $daySurgeries->count() }} عمليات
                        </span>
                    </div>

                    {{-- 2x2 Grid + 1 Completed Bar --}}
                    <div class="d-flex flex-column gap-2 my-auto">
                        <div class="row g-2">
                            {{-- All Surgeries --}}
                            <div class="col-6">
                                <div class="kpi-side-card kpi-active" data-status-filter="all" id="kpi-filter-all" onclick="filterTableByStatus('all')">
                                    <div>
                                        <div class="text-muted small fw-bold" style="font-size: 0.72rem;">إجمالي اليوم</div>
                                        <div class="fs-4 fw-bolder text-dark font-monospace">{{ $daySurgeries->count() }}</div>
                                    </div>
                                    <div class="kpi-icon-wrap bg-light text-primary" style="width: 34px; height: 34px; font-size: 0.9rem;">
                                        <i class="fas fa-layer-group"></i>
                                    </div>
                                </div>
                            </div>

                            {{-- Scheduled --}}
                            <div class="col-6">
                                <div class="kpi-side-card" data-status-filter="scheduled" id="kpi-filter-scheduled" onclick="filterTableByStatus('scheduled')">
                                    <div>
                                        <div class="text-muted small fw-bold" style="font-size: 0.72rem;">مجدولة</div>
                                        <div class="fs-4 fw-bolder text-primary font-monospace">{{ $scheduledSurgeries->count() }}</div>
                                    </div>
                                    <div class="kpi-icon-wrap bg-primary bg-opacity-10 text-primary" style="width: 34px; height: 34px; font-size: 0.9rem;">
                                        <i class="far fa-calendar-alt"></i>
                                    </div>
                                </div>
                            </div>

                            {{-- Waiting / Prep --}}
                            <div class="col-6">
                                <div class="kpi-side-card" data-status-filter="waiting" id="kpi-filter-waiting" onclick="filterTableByStatus('waiting')">
                                    <div>
                                        <div class="text-muted small fw-bold" style="font-size: 0.72rem;">بالانتظار (تحضير)</div>
                                        <div class="fs-4 fw-bolder text-warning font-monospace">{{ $waitingCount }}</div>
                                    </div>
                                    <div class="kpi-icon-wrap bg-warning bg-opacity-10 text-warning" style="width: 34px; height: 34px; font-size: 0.9rem;">
                                        <i class="fas fa-user-clock"></i>
                                    </div>
                                </div>
                            </div>

                            {{-- In OR (In Progress) --}}
                            <div class="col-6">
                                <div class="kpi-side-card" data-status-filter="in_progress" id="kpi-filter-in_progress" onclick="filterTableByStatus('in_progress')">
                                    <div>
                                        <div class="text-muted small fw-bold d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                            <span class="live-pulse-dot" style="width: 5px; height: 5px;"></span> داخل الصالة
                                        </div>
                                        <div class="fs-4 fw-bolder text-info font-monospace">{{ $inTheaterCount }}</div>
                                    </div>
                                    <div class="kpi-icon-wrap bg-info bg-opacity-10 text-info" style="width: 34px; height: 34px; font-size: 0.9rem;">
                                        <i class="fas fa-procedures"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Completed Full Width Bar --}}
                        <div class="kpi-side-card" data-status-filter="completed" id="kpi-filter-completed" onclick="filterTableByStatus('completed')">
                            <div class="d-flex align-items-center gap-2">
                                <div class="kpi-icon-wrap bg-success bg-opacity-10 text-success" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                    <i class="fas fa-flag-checkered"></i>
                                </div>
                                <div class="text-muted small fw-bold" style="font-size: 0.76rem;">
                                    مكتملة (خرجت من الصالة للتعافي / الطابق)
                                </div>
                            </div>
                            <div class="fs-5 fw-bolder text-success font-monospace">{{ $completedCount }}</div>
                        </div>
                    </div>

                    <div class="pt-2 border-top text-muted small text-center" style="font-size: 0.72rem;">
                        <i class="fas fa-hand-pointer me-1 text-primary"></i> انقر على أي مؤشر لتصفية جدول العمليات بالأسفل
                    </div>
                </div>
            </div>
        </div>
        </div>

        {{-- Realtime Search Bar --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="position-relative flex-grow-1" style="max-width: 520px;">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="surgerySearchInput" class="form-control rounded-pill ps-5 pe-4 py-2 border" style="background: #f8fafc; font-size: 0.88rem;" placeholder="بحث فوري بالاسم، رقم الملف، الصالة المخصصة، الطبيب، العملية...">
            </div>
            <div class="small text-muted fw-semibold">
                عرض <span id="visibleRowCount" class="text-primary font-monospace fw-bold">{{ $daySurgeries->count() }}</span> من أصل <span class="font-monospace fw-bold">{{ $daySurgeries->count() }}</span> عملية
            </div>
        </div>

        {{-- Master Flight Board Table (5 Consolidated Columns) --}}
        <div class="or-table-shell table-responsive">
            <table class="or-master-table">
                <thead>
                    <tr>
                        <th style="width: 25%;"># / المريض والملف</th>
                        <th style="width: 25%;">العملية الجراحية والجراح</th>
                        <th style="width: 24%;">الصالة والتوقيت المباشر</th>
                        <th style="width: 13%;">حالة المسار</th>
                        <th class="text-center" style="width: 13%;">الإجراء السريع</th>
                    </tr>
                </thead>
                <tbody id="surgeriesTableBody">
                    @forelse($daySurgeries as $surgery)
                    @php
                        $patientName = $surgery->patient->user->name ?? 'مريض غير مسجل';
                        $fileNumber = $surgery->patient->file_number ?? '-';
                        $doctorName = $surgery->doctor->user->name ?? $surgery->surgeon_name ?? 'غير محدد';
                        $theaterName = $surgery->operationTheater->name ?? null;
                        $rawTime = $surgery->scheduled_time ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('H:i') : '';

                        $searchData = strtolower(
                            $patientName . ' ' .
                            $fileNumber . ' ' .
                            $surgery->surgery_type . ' ' .
                            $doctorName . ' ' .
                            ($theaterName ?? '')
                        );

                        $isOtCompleted = $surgery->status === 'completed' || ($surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'completed');

                        // Normalized status for interactive KPI filter
                        $normalizedStatus = $surgery->status;
                        if (in_array($normalizedStatus, ['waiting', 'checked_in'])) {
                            $normalizedStatus = 'waiting';
                        } elseif ($isOtCompleted) {
                            $normalizedStatus = 'completed';
                        }
                    @endphp
                    <tr class="surgery-row" data-search="{{ $searchData }}" data-status="{{ $normalizedStatus }}" id="surgeryRow-{{ $surgery->id }}">
                        {{-- Col 1: ID, Patient & File --}}
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="patient-avatar-box">
                                    {{ mb_substr($patientName, 0, 1) }}
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-1.5">
                                        <span class="badge bg-light text-muted border font-monospace px-1.5 py-0.5" style="font-size: 0.72rem;">#{{ $surgery->id }}</span>
                                        <span class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $patientName }}</span>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace px-1.5 py-0.5" style="font-size: 0.72rem;">ملف #{{ $fileNumber }}</span>
                                        @if($surgery->patient && $surgery->patient->age)
                                            <span class="badge bg-light text-muted border px-1.5 py-0.5" style="font-size: 0.72rem;">{{ $surgery->patient->age }} سنة</span>
                                        @endif
                                        @if($surgery->patient && $surgery->patient->gender)
                                            <span class="badge bg-light text-muted border px-1.5 py-0.5" style="font-size: 0.72rem;">{{ $surgery->patient->gender == 'male' ? 'ذكر' : 'أنثى' }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Col 2: Surgery Type & Surgeon --}}
                        <td>
                            <div>
                                <div class="fw-bold text-dark" style="color: #0f172a !important; font-size: 0.94rem;">
                                    {{ $surgery->surgery_type }}
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-1.5 mt-1">
                                    <span class="d-inline-flex align-items-center gap-1 bg-light border rounded-pill px-2.5 py-0.5 text-dark small fw-semibold" style="font-size: 0.78rem;">
                                        <i class="fas fa-user-md text-primary"></i>
                                        <span>د. {{ $doctorName }}</span>
                                    </span>
                                    @if($surgery->additionalOperations && $surgery->additionalOperations->count() > 0)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">
                                            + {{ $surgery->additionalOperations->count() }} إضافية
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Col 3: Theater & Time (Compact Inline Configuration) --}}
                        <td>
                            <div class="d-flex flex-column gap-1.5" style="max-width: 260px;">
                                {{-- Theater Selector --}}
                                <div class="d-flex align-items-center gap-1">
                                    <select class="form-select form-select-sm or-inline-select or-theater-select py-1"
                                            data-surgery-id="{{ $surgery->id }}"
                                            onchange="inlineSaveTheater(this, {{ $surgery->id }})"
                                            style="font-size: 0.8rem;">
                                        <option value="">-- اختر الصالة المخصصة --</option>
                                        @foreach($operationTheaters as $theater)
                                            <option value="{{ $theater->id }}" {{ $surgery->operation_theater_id == $theater->id ? 'selected' : '' }}>
                                                {{ $theater->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3 shadow-none p-0 d-inline-flex align-items-center justify-content-center"
                                            title="إضافة صالة جديدة للقائمة"
                                            onclick="openNewTheaterModal({{ $surgery->id }})"
                                            style="width: 28px; height: 28px; flex-shrink: 0;">
                                        <i class="fas fa-plus small" style="font-size: 0.72rem;"></i>
                                    </button>
                                </div>

                                {{-- Time Input & Stopwatch --}}
                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm" style="max-width: 125px;">
                                        <span class="input-group-text bg-light text-muted px-1.5 py-0 border-end-0" style="font-size: 0.75rem;"><i class="far fa-clock"></i></span>
                                        <input type="time" class="form-control form-control-sm or-inline-time or-time-input ps-1 py-0.5"
                                               value="{{ $rawTime }}"
                                               data-surgery-id="{{ $surgery->id }}"
                                               onchange="inlineSaveTime(this, {{ $surgery->id }})"
                                               style="font-size: 0.8rem; height: 28px;">
                                    </div>

                                    @if($surgery->status === 'in_progress' && ! $isOtCompleted)
                                        <span class="live-stopwatch-capsule py-0.5 px-2" data-live-timer="true" data-start="{{ $surgery->started_at ? $surgery->started_at->toISOString() : ($surgery->scheduled_time ? $surgery->scheduled_time->toISOString() : '') }}" style="font-size: 0.72rem;">
                                            <i class="fas fa-stopwatch text-success"></i><span>00:00:00</span>
                                        </span>
                                    @endif
                                </div>

                                {{-- Feedback messages --}}
                                <div class="d-flex align-items-center gap-2">
                                    <div class="small text-success fw-bold d-none save-msg" id="saveTheaterMsg-{{ $surgery->id }}" style="font-size: 0.7rem;">
                                        <i class="fas fa-check-circle"></i> تم حفظ الصالة
                                    </div>
                                    <div class="small text-success fw-bold d-none save-msg" id="saveTimeMsg-{{ $surgery->id }}" style="font-size: 0.7rem;">
                                        <i class="fas fa-check-circle"></i> تم حفظ الوقت
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Col 4: Stage / Status --}}
                        <td>
                            @if($isOtCompleted)
                                <span class="badge-stage-pill bg-success text-white shadow-sm" title="تم إنهاء صالة العمليات واكتمال العملية">
                                    <i class="fas fa-check-circle me-0.5"></i> مكتملة (خرجت)
                                </span>
                            @elseif($surgery->status === 'in_progress')
                                <span class="badge-stage-pill bg-info text-white shadow-sm">
                                    <span class="live-pulse-dot"></span> داخل الصالة
                                </span>
                            @elseif($surgery->status === 'waiting' || $surgery->status === 'checked_in')
                                <span class="badge-stage-pill bg-warning bg-opacity-10 text-warning border border-warning-subtle">
                                    <i class="fas fa-user-clock"></i> بالانتظار
                                </span>
                            @elseif($surgery->status === 'scheduled')
                                <span class="badge-stage-pill bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                                    <i class="far fa-calendar-alt"></i> مجدولة
                                </span>
                            @else
                                <span class="badge bg-light text-muted">{{ $surgery->status }}</span>
                            @endif
                        </td>

                        {{-- Col 5: Actions Column --}}
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1.5 flex-wrap">
                                @if($isOtCompleted)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold" title="تم إنهاء صالة العمليات واكتمال العملية">
                                        <i class="fas fa-check-circle text-success me-1"></i> ✔️ عملية مكتملة
                                    </span>
                                @elseif($surgery->status === 'in_progress')
                                    @can('control surgeries')
                                    <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger btn-action-stage shadow-sm" onclick="return confirm('تأكيد إنهاء مرحلة صالة العمليات واكتمال العملية؟')">
                                            <i class="fas fa-stop"></i> إنهاء
                                        </button>
                                    </form>
                                    @endcan
                                @elseif($surgery->status === 'waiting' || $surgery->status === 'checked_in')
                                    @can('control surgeries')
                                    <form action="{{ route('surgeries.start', $surgery) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success btn-action-stage shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                                            <i class="fas fa-play"></i> تحويل وبدء
                                        </button>
                                    </form>
                                    @endcan
                                @elseif($surgery->status === 'scheduled')
                                    @can('manage surgery waiting list')
                                    <form action="{{ route('surgeries.check-in', $surgery) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary btn-action-stage shadow-sm">
                                            <i class="fas fa-sign-in-alt"></i> دخول
                                        </button>
                                    </form>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-2 text-muted" style="width: 70px; height: 70px;">
                                    <i class="far fa-calendar-times fs-2"></i>
                                </div>
                                <div class="fw-bold fs-6 text-dark">لا توجد عمليات مسجلة في هذا اليوم</div>
                                <div class="small text-muted mt-1">اختر يوماً آخر من التقويم في الأعلى أو قم بحجز عملية جديدة</div>
                                @can('create surgeries')
                                <a href="{{ route('surgeries.create') }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 mt-3 fw-bold">
                                    <i class="fas fa-plus me-1"></i> حجز عملية الآن
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Small Modal ONLY for adding a new Operation Theater (فقط لإضافة صالة جديدة) --}}
<div class="modal fade" id="newTheaterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-1 pt-3 px-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-1.5">
                    <i class="fas fa-door-closed text-primary"></i>
                    إضافة صالة جديدة
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1 px-3 pb-3">
                <input type="hidden" id="activeSurgeryIdForNewTheater">
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1 fw-semibold">اسم الصالة</label>
                    <input type="text" id="newTheaterNameModalInput" class="form-control form-control-sm rounded-3 py-1.5" placeholder="مثلاً: صالة 4، صالة القسطرة...">
                    <div id="newTheaterModalError" class="text-danger small mt-1 d-none"></div>
                </div>
                <div class="d-flex justify-content-end gap-1.5 pt-2 border-top">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm" id="btnSaveTheaterFromModal">
                        <i class="fas fa-check me-1"></i> حفظ الصالة
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let newTheaterModalInstance = null;
let currentActiveFilter = 'all';

// Open New Theater Modal
function openNewTheaterModal(surgeryId) {
    document.getElementById('activeSurgeryIdForNewTheater').value = surgeryId || '';
    document.getElementById('newTheaterNameModalInput').value = '';
    document.getElementById('newTheaterModalError').classList.add('d-none');

    const modalEl = document.getElementById('newTheaterModal');
    if (!newTheaterModalInstance) {
        newTheaterModalInstance = new bootstrap.Modal(modalEl);
    }
    newTheaterModalInstance.show();
    setTimeout(() => {
        document.getElementById('newTheaterNameModalInput').focus();
    }, 300);
}

// Inline Save Theater directly from select
function inlineSaveTheater(selectEl, surgeryId) {
    const theaterId = selectEl.value;
    const msgEl = document.getElementById('saveTheaterMsg-' + surgeryId);

    selectEl.classList.remove('inline-save-success');

    fetch("{{ url('/surgeries') }}/" + surgeryId + "/quick-update-schedule", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            operation_theater_id: theaterId || null
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            selectEl.classList.add('inline-save-success');
            if (msgEl) {
                msgEl.classList.remove('d-none');
                setTimeout(() => msgEl.classList.add('d-none'), 2500);
            }
        } else {
            alert(data.message || 'حدث خطأ أثناء حفظ الصالة');
        }
    })
    .catch(() => alert('تعذر الاتصال بالسيرفر'));
}

// Inline Save Time directly from input
function inlineSaveTime(inputEl, surgeryId) {
    const scheduledTime = inputEl.value;
    const msgEl = document.getElementById('saveTimeMsg-' + surgeryId);

    inputEl.classList.remove('inline-save-success');

    fetch("{{ url('/surgeries') }}/" + surgeryId + "/quick-update-schedule", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            scheduled_time: scheduledTime || null
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            inputEl.classList.add('inline-save-success');
            if (msgEl) {
                msgEl.classList.remove('d-none');
                setTimeout(() => msgEl.classList.add('d-none'), 2500);
            }
        } else {
            alert(data.message || 'حدث خطأ أثناء حفظ الوقت');
        }
    })
    .catch(() => alert('تعذر الاتصال بالسيرفر'));
}

// Interactive Status Filter via KPI Cards
function filterTableByStatus(status) {
    currentActiveFilter = status;

    // Highlight active card
    document.querySelectorAll('.kpi-side-card, .kpi-card').forEach(card => {
        if (card.getAttribute('data-status-filter') === status) {
            card.classList.add('kpi-active');
        } else {
            card.classList.remove('kpi-active');
        }
    });

    applyFilters();
}

function applyFilters() {
    const searchInput = document.getElementById('surgerySearchInput');
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const rows = document.querySelectorAll('.surgery-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const searchData = (row.getAttribute('data-search') || '').toLowerCase();
        const rowStatus = row.getAttribute('data-status') || '';

        const matchesSearch = !query || searchData.includes(query);
        const matchesStatus = (currentActiveFilter === 'all') || (rowStatus === currentActiveFilter);

        if (matchesSearch && matchesStatus) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const counterEl = document.getElementById('visibleRowCount');
    if (counterEl) {
        counterEl.textContent = visibleCount;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Dashboard Collapse/Expand state management
    const dashboardCollapseEl = document.getElementById('topDashboardCollapse');
    const toggleDashboardIcon = document.getElementById('toggleDashboardIcon');
    const toggleDashboardText = document.getElementById('toggleDashboardText');

    if (dashboardCollapseEl) {
        // Restore user's collapsed state from localStorage
        const isCollapsed = localStorage.getItem('waiting_dashboard_collapsed') === 'true';
        if (isCollapsed) {
            const bsCollapse = new bootstrap.Collapse(dashboardCollapseEl, { toggle: false });
            bsCollapse.hide();
            if (toggleDashboardIcon) toggleDashboardIcon.className = 'fas fa-chevron-down';
            if (toggleDashboardText) toggleDashboardText.textContent = 'إظهار التقويم';
        }

        dashboardCollapseEl.addEventListener('hide.bs.collapse', function () {
            if (toggleDashboardIcon) toggleDashboardIcon.className = 'fas fa-chevron-down';
            if (toggleDashboardText) toggleDashboardText.textContent = 'إظهار التقويم';
            localStorage.setItem('waiting_dashboard_collapsed', 'true');
        });

        dashboardCollapseEl.addEventListener('show.bs.collapse', function () {
            if (toggleDashboardIcon) toggleDashboardIcon.className = 'fas fa-chevron-up';
            if (toggleDashboardText) toggleDashboardText.textContent = 'طي التقويم';
            localStorage.setItem('waiting_dashboard_collapsed', 'false');
        });
    }

    // Search Filter
    const searchInput = document.getElementById('surgerySearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }

    // Save New Theater from modal
    const btnSaveTheater = document.getElementById('btnSaveTheaterFromModal');
    if (btnSaveTheater) {
        btnSaveTheater.addEventListener('click', function () {
            const nameInput = document.getElementById('newTheaterNameModalInput');
            const errDiv = document.getElementById('newTheaterModalError');
            const name = nameInput.value.trim();
            const targetSurgeryId = document.getElementById('activeSurgeryIdForNewTheater').value;

            if (!name) {
                errDiv.textContent = 'يرجى كتابة اسم الصالة';
                errDiv.classList.remove('d-none');
                return;
            }

            btnSaveTheater.disabled = true;
            btnSaveTheater.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> حفظ...';

            fetch("{{ route('surgeries.theaters.quick-store') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ name: name })
            })
            .then(res => res.json())
            .then(data => {
                btnSaveTheater.disabled = false;
                btnSaveTheater.innerHTML = '<i class="fas fa-check me-1"></i> حفظ الصالة';

                if (data.success && data.theater) {
                    // Append option to all theater selects on the page
                    document.querySelectorAll('.or-theater-select').forEach(select => {
                        const opt = document.createElement('option');
                        opt.value = data.theater.id;
                        opt.textContent = data.theater.name;
                        select.appendChild(opt);

                        // If this select belongs to the surgery where '+' was clicked, select it & save
                        if (targetSurgeryId && select.getAttribute('data-surgery-id') === targetSurgeryId) {
                            select.value = data.theater.id;
                            inlineSaveTheater(select, targetSurgeryId);
                        }
                    });

                    if (newTheaterModalInstance) newTheaterModalInstance.hide();
                } else {
                    errDiv.textContent = data.message || 'حدث خطأ أثناء إضافة الصالة';
                    errDiv.classList.remove('d-none');
                }
            })
            .catch(() => {
                btnSaveTheater.disabled = false;
                btnSaveTheater.innerHTML = '<i class="fas fa-check me-1"></i> حفظ الصالة';
                errDiv.textContent = 'تعذر الاتصال بالسيرفر';
                errDiv.classList.remove('d-none');
            });
        });
    }

    // Realtime Stopwatch for in-progress operations
    const liveTimerEls = document.querySelectorAll('[data-live-timer="true"]');
    function updateTimers() {
        const now = new Date();
        liveTimerEls.forEach(el => {
            const startAttr = el.getAttribute('data-start');
            if (!startAttr) return;
            const startTime = new Date(startAttr);
            if (isNaN(startTime.getTime())) return;

            let diffMs = now - startTime;
            if (diffMs < 0) diffMs = 0;

            const totalSec = Math.floor(diffMs / 1000);
            const hrs = String(Math.floor(totalSec / 3600)).padStart(2, '0');
            const mins = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
            const secs = String(totalSec % 60).padStart(2, '0');

            const spanText = el.querySelector('span');
            if (spanText) {
                spanText.textContent = `${hrs}:${mins}:${secs}`;
            }
        });
    }
    updateTimers();
    setInterval(updateTimers, 1000);

    // Auto-refresh every 60s for public TV Flight Board display (only when not searching or using modal)
    setInterval(function () {
        const searchInput = document.getElementById('surgerySearchInput');
        const isSearching = searchInput && searchInput.value.trim().length > 0;
        const isModalOpen = document.querySelector('.modal.show');
        if (!isSearching && !isModalOpen) {
            location.reload();
        }
    }, 60000);
});

// Toggle TV Flight Board Fullscreen Display
function toggleTvDisplayMode() {
    document.body.classList.toggle('tv-display-active');
    const isTv = document.body.classList.contains('tv-display-active');
    const tvText = document.getElementById('tvModeText');
    if (tvText) {
        tvText.textContent = isTv ? 'خروج من وضع الـ TV' : 'شاشة العرض (TV)';
    }
    if (isTv && document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else if (!isTv && document.exitFullscreen && document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
    }
}
</script>
@endsection
