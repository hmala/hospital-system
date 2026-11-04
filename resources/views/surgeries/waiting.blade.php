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

        /* Weekday navigation strip */
        .week-nav-container {
            background: #f8fafc;
            border: 1px solid var(--or-border);
            border-radius: 18px;
            padding: 8px;
            margin-bottom: 20px;
        }
        .week-nav-scroll {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            scrollbar-width: thin;
            padding-bottom: 2px;
        }
        .week-day-pill {
            flex: 1;
            min-width: 125px;
            background: #ffffff;
            border: 1.5px solid var(--or-border);
            border-radius: 14px;
            padding: 10px 12px;
            text-align: center;
            text-decoration: none;
            color: #475569;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .week-day-pill:hover {
            transform: translateY(-2px);
            border-color: var(--or-primary);
            color: var(--or-primary);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        }
        .week-day-pill.active {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-color: #0f172a;
            color: #ffffff;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.2);
        }
        .week-day-pill.active .badge-count {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.25);
        }
        .week-day-pill.active .day-date-label {
            color: rgba(255, 255, 255, 0.75);
        }
        .week-day-pill .day-title-label {
            font-weight: 800;
            font-size: 0.95rem;
        }
        .week-day-pill .day-date-label {
            font-size: 0.78rem;
            color: #64748b;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
            margin-top: 2px;
        }
        .week-day-pill .badge-count {
            background: #f1f5f9;
            color: #475569;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            margin-top: 6px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .today-glow-dot {
            width: 7px;
            height: 7px;
            background-color: #f59e0b;
            border-radius: 50%;
            display: inline-block;
            margin-inline-start: 4px;
            box-shadow: 0 0 6px #f59e0b;
        }

        /* KPI Quick Stat Cards */
        .kpi-card {
            background: #ffffff;
            border: 1.5px solid var(--or-border);
            border-radius: 14px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.2s ease;
            user-select: none;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
            border-color: #cbd5e1;
        }
        .kpi-card.kpi-active {
            border-color: var(--or-primary);
            background: #f8fafc;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.15);
        }
        .kpi-icon-wrap {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
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

                <a href="{{ route('surgeries.index') }}" class="btn btn-outline-secondary bg-white rounded-pill px-3 py-2 shadow-sm fw-semibold" style="border-color: #cbd5e1; color: #334155;">
                    <i class="fas fa-archive me-1 text-secondary"></i> الأرشيف العام
                </a>

                <button type="button" class="btn btn-light border rounded-pill px-3 py-2 shadow-sm text-dark fw-bold" onclick="location.reload();">
                    <i class="fas fa-rotate me-1 text-primary"></i> تحديث
                </button>
            </div>
        </div>

        {{-- Weekdays Interactive Slider (شريط أيام الأسبوع التفاعلي) --}}
        <div class="week-nav-container">
            <div class="d-flex align-items-center justify-content-between mb-2 px-2 pt-1">
                <span class="fw-bold text-dark small d-flex align-items-center gap-1.5">
                    <i class="far fa-calendar-alt text-primary"></i>
                    <span>اختر اليوم من الأسبوع:</span>
                </span>
                <span class="small text-muted">
                    اليوم المعروض: <strong class="text-primary font-monospace">{{ $parsedDate->format('Y-m-d') }}</strong>
                </span>
            </div>

            <div class="week-nav-scroll">
                @foreach($weekDays as $day)
                <a href="{{ route('surgeries.waiting', ['date' => $day['date']]) }}" class="week-day-pill {{ $day['is_selected'] ? 'active' : '' }}">
                    <div class="day-title-label">
                        {{ $day['day_name'] }}
                        @if($day['is_today'])
                            <span class="today-glow-dot" title="اليوم الحالي"></span>
                        @endif
                    </div>
                    <div class="day-date-label">
                        {{ $day['formatted_date'] }}
                    </div>
                    <span class="badge-count">
                        {{ $day['count'] }} {{ $day['count'] == 1 ? 'عملية' : ($day['count'] == 2 ? 'عمليتان' : 'عمليات') }}
                    </span>
                </a>
                @endforeach
            </div>
        </div>

        {{-- Interactive KPI Stat Cards (فلترة فورية بنقرة واحدة) --}}
        <div class="row g-2 mb-3">
            {{-- All surgeries --}}
            <div class="col-6 col-md-3">
                <div class="kpi-card kpi-active" data-status-filter="all" id="kpi-filter-all" onclick="filterTableByStatus('all')">
                    <div>
                        <div class="text-muted small fw-bold">إجمالي عمليات اليوم</div>
                        <div class="fs-4 fw-bolder text-dark font-monospace mt-0.5">{{ $daySurgeries->count() }}</div>
                    </div>
                    <div class="kpi-icon-wrap bg-light text-primary">
                        <i class="fas fa-layer-group"></i>
                    </div>
                </div>
            </div>

            {{-- Scheduled --}}
            <div class="col-6 col-md-3">
                <div class="kpi-card" data-status-filter="scheduled" id="kpi-filter-scheduled" onclick="filterTableByStatus('scheduled')">
                    <div>
                        <div class="text-muted small fw-bold">مجدولة</div>
                        <div class="fs-4 fw-bolder text-primary font-monospace mt-0.5">{{ $scheduledSurgeries->count() }}</div>
                    </div>
                    <div class="kpi-icon-wrap bg-primary bg-opacity-10 text-primary">
                        <i class="far fa-calendar-alt"></i>
                    </div>
                </div>
            </div>

            {{-- Waiting / Preparation --}}
            <div class="col-6 col-md-3">
                <div class="kpi-card" data-status-filter="waiting" id="kpi-filter-waiting" onclick="filterTableByStatus('waiting')">
                    <div>
                        <div class="text-muted small fw-bold">بالانتظار (تحضير)</div>
                        <div class="fs-4 fw-bolder text-warning font-monospace mt-0.5">{{ $activeSurgeries->whereIn('status', ['waiting', 'checked_in'])->count() }}</div>
                    </div>
                    <div class="kpi-icon-wrap bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-user-clock"></i>
                    </div>
                </div>
            </div>

            {{-- Inside OR (In Progress) --}}
            <div class="col-6 col-md-3">
                <div class="kpi-card" data-status-filter="in_progress" id="kpi-filter-in_progress" onclick="filterTableByStatus('in_progress')">
                    <div>
                        <div class="text-muted small fw-bold d-flex align-items-center gap-1">
                            <span class="live-pulse-dot"></span> داخل الصالة (جارية)
                        </div>
                        <div class="fs-4 fw-bolder text-success font-monospace mt-0.5">{{ $activeSurgeries->where('status', 'in_progress')->count() }}</div>
                    </div>
                    <div class="kpi-icon-wrap bg-success bg-opacity-10 text-success">
                        <i class="fas fa-procedures"></i>
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

        {{-- Master Flight Board Table --}}
        <div class="or-table-shell table-responsive">
            <table class="or-master-table">
                <thead>
                    <tr>
                        <th style="width: 70px;"># الرقم</th>
                        <th>المريض</th>
                        <th>العملية الجراحية</th>
                        <th>الطبيب الجراح</th>
                        <th style="min-width: 195px;">الصالة المخصصة (تحديد مباشر)</th>
                        <th style="min-width: 155px;">الوقت (تحديد مباشر)</th>
                        <th>حالة المسار</th>
                        <th class="text-center" style="width: 170px;">الإجراءات السريعة</th>
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

                        $isOtCompleted = $surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'completed';

                        // Normalized status for interactive KPI filter
                        $normalizedStatus = $surgery->status;
                        if (in_array($normalizedStatus, ['waiting', 'checked_in'])) {
                            $normalizedStatus = 'waiting';
                        }
                    @endphp
                    <tr class="surgery-row" data-search="{{ $searchData }}" data-status="{{ $normalizedStatus }}" id="surgeryRow-{{ $surgery->id }}">
                        {{-- ID --}}
                        <td>
                            <span class="fw-bold font-monospace text-muted small">#{{ $surgery->id }}</span>
                        </td>

                        {{-- Patient Details --}}
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="patient-avatar-box">
                                    {{ mb_substr($patientName, 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $patientName }}</div>
                                    <div class="d-flex align-items-center gap-1.5 mt-0.5">
                                        <span class="badge bg-light text-dark border font-monospace px-1.5 py-0.5" style="font-size: 0.72rem;">#{{ $fileNumber }}</span>
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

                        {{-- Surgery Type --}}
                        <td>
                            <div class="fw-bold text-dark" style="color: #0f172a !important; font-size: 0.92rem;">
                                {{ $surgery->surgery_type }}
                            </div>
                            @if($surgery->additionalOperations && $surgery->additionalOperations->count() > 0)
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-2 py-0.5 small mt-1">
                                    + {{ $surgery->additionalOperations->count() }} عمليات إضافية
                                </span>
                            @endif
                        </td>

                        {{-- Surgeon --}}
                        <td>
                            <div class="d-inline-flex align-items-center gap-1.5 bg-light border rounded-pill px-2.5 py-1 text-dark small fw-semibold">
                                <i class="fas fa-user-md text-primary"></i>
                                <span>د. {{ $doctorName }}</span>
                            </div>
                        </td>

                        {{-- Operation Theater (Inline Select + Quick Add) --}}
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <select class="form-select form-select-sm or-inline-select or-theater-select"
                                        data-surgery-id="{{ $surgery->id }}"
                                        onchange="inlineSaveTheater(this, {{ $surgery->id }})">
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
                                        style="width: 32px; height: 32px; flex-shrink: 0;">
                                    <i class="fas fa-plus small"></i>
                                </button>
                            </div>
                            <div class="small text-success fw-bold mt-1 d-none save-msg" id="saveTheaterMsg-{{ $surgery->id }}" style="font-size: 0.72rem;">
                                <i class="fas fa-check-circle"></i> تم حفظ الصالة
                            </div>
                        </td>

                        {{-- Time (Inline Time Input + Live Stopwatch) --}}
                        <td>
                            <div>
                                <input type="time" class="form-control form-control-sm or-inline-time or-time-input"
                                       value="{{ $rawTime }}"
                                       data-surgery-id="{{ $surgery->id }}"
                                       onchange="inlineSaveTime(this, {{ $surgery->id }})">
                            </div>
                            <div class="small text-success fw-bold mt-1 d-none save-msg" id="saveTimeMsg-{{ $surgery->id }}" style="font-size: 0.72rem;">
                                <i class="fas fa-check-circle"></i> تم حفظ الوقت
                            </div>

                            @if($surgery->status === 'in_progress' && ! $isOtCompleted)
                                <div class="mt-1">
                                    <span class="live-stopwatch-capsule" data-live-timer="true" data-start="{{ $surgery->started_at ? $surgery->started_at->toISOString() : ($surgery->scheduled_time ? $surgery->scheduled_time->toISOString() : '') }}">
                                        <i class="fas fa-stopwatch text-success"></i><span>00:00:00</span>
                                    </span>
                                </div>
                            @endif
                        </td>

                        {{-- Stage / Status --}}
                        <td>
                            @if($surgery->status === 'scheduled')
                                <span class="badge-stage-pill bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                                    <i class="far fa-calendar-alt"></i> مجدولة
                                </span>
                            @elseif($surgery->status === 'waiting' || $surgery->status === 'checked_in')
                                <span class="badge-stage-pill bg-warning bg-opacity-10 text-warning border border-warning-subtle">
                                    <i class="fas fa-user-clock"></i> بالانتظار (تحضير)
                                </span>
                            @elseif($surgery->status === 'in_progress')
                                @if($isOtCompleted)
                                    <span class="badge-stage-pill bg-info text-white shadow-sm" title="خرج المريض من صالة العمليات وانتقل لمرحلة المتابعة والرعاية">
                                        <i class="fas fa-flag-checkered me-0.5"></i> 🏁 خرج من الصالة (متابعة)
                                    </span>
                                @else
                                    <span class="badge-stage-pill bg-success text-white shadow-sm">
                                        <span class="live-pulse-dot"></span> داخل الصالة (جارية)
                                    </span>
                                @endif
                            @elseif($surgery->status === 'completed')
                                <span class="badge-stage-pill bg-secondary text-white">
                                    <i class="fas fa-check-circle"></i> مكتملة / خرج
                                </span>
                            @else
                                <span class="badge bg-light text-muted">{{ $surgery->status }}</span>
                            @endif
                        </td>

                        {{-- Actions Column: Strictly stage progress buttons, no irrelevant buttons --}}
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1.5 flex-wrap">
                                @if($surgery->status === 'scheduled')
                                    @can('manage surgery waiting list')
                                    <form action="{{ route('surgeries.check-in', $surgery) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary btn-action-stage shadow-sm">
                                            <i class="fas fa-sign-in-alt"></i> دخول
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
                                @elseif($surgery->status === 'in_progress')
                                    @if($isOtCompleted)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-bold" title="تم إنهاء صالة العمليات وانتقل المريض للمتابعة">
                                            <i class="fas fa-check-circle text-success me-1"></i> ✔️ تمت الصالة
                                        </span>
                                    @else
                                        @can('control surgeries')
                                        <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger btn-action-stage shadow-sm" onclick="return confirm('تأكيد إنهاء مرحلة صالة العمليات ونقل المريض للمتابعة؟')">
                                                <i class="fas fa-stop"></i> إنهاء
                                            </button>
                                        </form>
                                        @endcan
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-2 text-muted" style="width: 70px; height: 70px;">
                                    <i class="far fa-calendar-times fs-2"></i>
                                </div>
                                <div class="fw-bold fs-6 text-dark">لا توجد عمليات مسجلة في هذا اليوم</div>
                                <div class="small text-muted mt-1">اختر يوماً آخر من شريط الأسبوع في الأعلى أو قم بحجز عملية جديدة</div>
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
    document.querySelectorAll('.kpi-card').forEach(card => {
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
});
</script>
@endsection
