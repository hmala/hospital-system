@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="surgeriesIndexApp">
    {{-- Modern Surgery Master Registry Styles --}}
    <style>
        :root {
            --sg-navy: #0f172a;
            --sg-slate: #1e293b;
            --sg-primary: #0284c7;
            --sg-primary-dark: #0369a1;
            --sg-primary-light: #f0f9ff;
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

        /* Top Executive Header */
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

        /* Executive Header Action Buttons */
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

        /* KPI Stat Cards Grid */
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

        /* Glowing Live Dot */
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

        /* Smart Search & Filter Box */
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

        /* Segmented Tabs Strip (Apple Style) */
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

        /* Modern Table Styles */
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
            padding: 12px 14px;
            border-bottom: 1px solid var(--sg-border);
            white-space: nowrap;
            text-align: right;
            letter-spacing: 0.01em;
        }
        .sg-table tbody td {
            padding: 12px 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.86rem;
            color: #1e293b;
            background: #ffffff;
            transition: background 0.15s ease;
        }
        .sg-table tbody tr:hover td {
            background: #f8fafc;
        }
        .sg-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Patient Cell Styles */
        .sg-patient-box {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sg-avatar {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            font-weight: 800;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.2);
            flex-shrink: 0;
        }
        .sg-patient-name {
            font-weight: 800;
            font-size: 0.92rem;
            color: var(--sg-navy);
            text-decoration: none;
            display: block;
            line-height: 1.25;
            transition: color 0.15s ease;
        }
        .sg-patient-name:hover {
            color: #0284c7;
        }
        .sg-meta-pills {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 3px;
            flex-wrap: wrap;
        }
        .sg-meta-chip {
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748b;
            background: #f1f5f9;
            padding: 1px 6px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
        }

        /* Procedure Box */
        .sg-proc-title {
            font-weight: 800;
            font-size: 0.88rem;
            color: var(--sg-navy);
            line-height: 1.2;
        }
        .sg-proc-meta {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 3px;
            flex-wrap: wrap;
        }
        .sg-badge-ins-hi {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 20px;
        }
        .sg-badge-ins-cash {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #cbd5e1;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 20px;
        }

        /* Doctor Pill */
        .sg-doc-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid var(--sg-border);
            padding: 4px 10px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.82rem;
            color: #334155;
            white-space: nowrap;
        }

        /* Room Pill Badges */
        .sg-room-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 800;
            font-size: 0.78rem;
            padding: 3px 9px;
            border-radius: 8px;
            white-space: nowrap;
        }
        .sg-room-regular {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .sg-room-vip {
            background: #fefce8;
            color: #854d0e;
            border: 1px solid #fde047;
        }
        .sg-room-vvip {
            background: #0f172a;
            color: #fef08a;
            border: 1px solid #334155;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
        }

        /* Status Badges */
        .sg-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.76rem;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }
        .sg-status-scheduled {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }
        .sg-status-waiting {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }
        .sg-status-in_progress {
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .sg-status-completed {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .sg-status-cancelled {
            background: #ffe4e6;
            color: #e11d48;
            border: 1px solid #fecdd3;
        }

        /* Quick Diagnostic Mini Chips */
        .sg-diag-chip {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: 1px solid transparent;
            white-space: nowrap;
        }
        .sg-diag-lab {
            background: #f5f3ff;
            color: #6d28d9;
            border-color: #ddd6fe;
        }
        .sg-diag-rad {
            background: #ecfeff;
            color: #0e7490;
            border-color: #a5f3fc;
        }
        .sg-diag-med {
            background: #fdf2f8;
            color: #be185d;
            border-color: #fbcfe8;
        }

        /* Action Buttons */
        .sg-btn-clinical {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%);
            color: #ffffff !important;
            font-weight: 800;
            font-size: 0.78rem;
            padding: 6px 12px;
            border-radius: 8px;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25);
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .sg-btn-clinical:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.35);
        }
        .sg-btn-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            border: 1px solid var(--sg-border);
            background: #ffffff;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.15s ease;
            text-decoration: none;
        }
        .sg-btn-icon:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        .sg-btn-icon.btn-danger-subtle:hover {
            background: #ffe4e6;
            color: #e11d48;
            border-color: #fecdd3;
        }

        /* Empty State */
        .sg-empty-state {
            padding: 48px 24px;
            text-align: center;
            color: #94a3b8;
        }
        .sg-empty-icon {
            font-size: 42px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }
        .sg-empty-title {
            font-weight: 800;
            font-size: 1.05rem;
            color: #475569;
            margin-bottom: 4px;
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
                @can('create surgeries')
                <a href="{{ route('surgeries.create') }}" class="sg-btn-create">
                    <i class="fas fa-plus"></i>
                    <span>حجز عملية جديدة</span>
                </a>
                @endcan

                @canany(['manage surgery waiting list', 'view surgeries'])
                <a href="{{ route('surgeries.waiting') }}" class="sg-btn-link-action">
                    <i class="fas fa-clock text-amber-500" style="color: #f59e0b;"></i>
                    <span>قائمة الانتظار والصالات</span>
                </a>
                @endcanany

                @canany(['manage rooms', 'view occupancy'])
                <a href="{{ route('rooms.index') }}" class="sg-btn-link-action">
                    <i class="fas fa-bed text-rose-500" style="color: #f43f5e;"></i>
                    <span>إدارة الغرف</span>
                </a>
                @endcanany
            </div>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 border-0 shadow-sm d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-check-circle fa-lg me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 border-0 shadow-sm d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-exclamation-circle fa-lg me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- 2. Interactive KPI Stat Cards --}}
        <div class="sg-kpi-grid">
            {{-- Total Surgeries --}}
            <div class="sg-kpi-card kpi-total">
                <div>
                    <div class="sg-kpi-title">إجمالي العمليات المسجلة</div>
                    <div class="sg-kpi-num">{{ $stats->total ?? ($activeSurgeries->total() + $completedSurgeries->total()) }}</div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>

            {{-- Active Surgeries (Live Pulse) --}}
            <div class="sg-kpi-card kpi-active">
                <div>
                    <div class="sg-kpi-title d-flex align-items-center">
                        <span>العمليات النشطة</span>
                        <span class="sg-pulse-dot" title="حالات نشطة جارية"></span>
                    </div>
                    <div class="sg-kpi-num text-emerald-600" style="color: #10b981;">
                        {{ $stats->active ?? $activeSurgeries->total() }}
                    </div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
            </div>

            {{-- Waiting / Preparation --}}
            <div class="sg-kpi-card kpi-waiting">
                <div>
                    <div class="sg-kpi-title">بالانتظار والتحضير</div>
                    <div class="sg-kpi-num text-sky-600" style="color: #0284c7;">
                        {{ $stats->waiting ?? 0 }}
                    </div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-clock"></i>
                </div>
            </div>

            {{-- Completed --}}
            <div class="sg-kpi-card kpi-completed">
                <div>
                    <div class="sg-kpi-title">المكتملة بنجاح</div>
                    <div class="sg-kpi-num text-blue-600" style="color: #2563eb;">
                        {{ $stats->completed ?? 0 }}
                    </div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>

            {{-- Cancelled --}}
            <div class="sg-kpi-card kpi-cancelled">
                <div>
                    <div class="sg-kpi-title">العمليات الملغاة</div>
                    <div class="sg-kpi-num text-rose-600" style="color: #e11d48;">
                        {{ $stats->cancelled ?? 0 }}
                    </div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-ban"></i>
                </div>
            </div>
        </div>

        {{-- 3. Smart Search & Filter Box --}}
        <div class="sg-filter-card">
            <form method="GET" action="{{ route('surgeries.index') }}" class="row g-2 align-items-end">
                <div class="col-lg-5 col-md-6">
                    <label for="search" class="sg-form-label">
                        <i class="fas fa-search me-1 text-primary"></i> البحث السريع
                    </label>
                    <input type="text" class="form-control sg-input" id="search" name="search" 
                           value="{{ request('search') }}" 
                           placeholder="ابحث باسم المريض، رقم الملف، الطبيب، أو نوع العملية...">
                </div>

                <div class="col-lg-3 col-md-3">
                    <label for="status_filter" class="sg-form-label">
                        <i class="fas fa-filter me-1 text-primary"></i> تصفية حسب الحالة
                    </label>
                    <select class="form-select sg-input" id="status_filter" name="status">
                        <option value="">جميع الحالات</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>📅 مجدولة</option>
                        <option value="waiting" {{ request('status') == 'waiting' ? 'selected' : '' }}>⏳ في الانتظار</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>⚡ داخل الصالة (جارية)</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>✔️ مكتملة</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>✕ ملغاة</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3">
                    <label for="date_filter" class="sg-form-label">
                        <i class="fas fa-calendar-day me-1 text-primary"></i> تاريخ الموعد
                    </label>
                    <input type="date" class="form-control sg-input" id="date_filter" name="date" value="{{ request('date') }}">
                </div>

                <div class="col-lg-2 col-md-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold" style="border-radius: 10px; padding: 8px 12px;">
                            <i class="fas fa-search me-1"></i> تصفية
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

        {{-- 4. Segmented Tabs Navigation --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="sg-tabs-strip">
                <button type="button" class="sg-tab-btn active" id="btnTabActive" onclick="switchSurgeryTab('active')">
                    <i class="fas fa-bolt text-success"></i>
                    <span>العمليات النشطة (المجدولة والجارية)</span>
                    <span class="sg-tab-pill-badge badge-active-color">{{ $activeSurgeries->total() }}</span>
                </button>
                <button type="button" class="sg-tab-btn" id="btnTabCompleted" onclick="switchSurgeryTab('completed')">
                    <i class="fas fa-archive text-secondary"></i>
                    <span>سجل العمليات المكتملة والملغاة</span>
                    <span class="sg-tab-pill-badge badge-completed-color">{{ $completedSurgeries->total() }}</span>
                </button>
            </div>

            <div class="text-muted small fw-bold">
                <i class="fas fa-info-circle me-1 text-primary"></i>
                <span id="tabCounterLabel">عرض العمليات الجراحية المفتوحة حالياً في المستشفى</span>
            </div>
        </div>

        {{-- 5. Active Surgeries Table Tab Pane --}}
        <div id="paneActiveSurgeries">
            <div class="sg-table-container">
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">#</th>
                                <th>بيانات المريض</th>
                                <th>نوع العملية والتغطية</th>
                                <th>الطبيب الجراح</th>
                                <th>الموعد والغرفة</th>
                                <th>الحالة السريرية</th>
                                <th>الفحوصات والعلاجات</th>
                                <th style="text-align: center; width: 170px;">الإجراءات السريعة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeSurgeries as $surgery)
                            @php
                                $patientName = optional(optional($surgery->patient)->user)->name ?? 'غير محدد';
                                $initial = mb_substr($patientName, 0, 1, 'UTF-8');
                                $phone = optional(optional($surgery->patient)->user)->phone;
                                $age = optional($surgery->patient)->age;
                                $gender = optional($surgery->patient)->gender;
                                $room = $surgery->room;
                                $roomType = $room ? $room->room_type : null;
                            @endphp
                            <tr>
                                {{-- # ID --}}
                                <td>
                                    <span class="fw-bold font-monospace text-muted" style="font-size: 0.8rem;">
                                        #{{ $surgery->id }}
                                    </span>
                                </td>

                                {{-- Patient Info --}}
                                <td>
                                    <div class="sg-patient-box">
                                        <div class="sg-avatar">
                                            {{ $initial }}
                                        </div>
                                        <div>
                                            <a href="{{ route('surgeries.show', $surgery) }}" class="sg-patient-name">
                                                {{ $patientName }}
                                            </a>
                                            <div class="sg-meta-pills">
                                                @if(optional($surgery->patient)->file_number)
                                                    <span class="sg-meta-chip" title="رقم الملف السريري">
                                                        <i class="fas fa-folder-open text-primary"></i>
                                                        MRN: {{ $surgery->patient->file_number }}
                                                    </span>
                                                @endif
                                                @if($age)
                                                    <span class="sg-meta-chip">
                                                        {{ $age }} سنة
                                                    </span>
                                                @endif
                                                @if($gender)
                                                    <span class="sg-meta-chip">
                                                        {{ $gender === 'male' || $gender === 'ذكر' ? 'ذكر' : 'أنثى' }}
                                                    </span>
                                                @endif
                                                @if($phone)
                                                    <span class="sg-meta-chip text-muted" dir="ltr">
                                                        <i class="fas fa-phone-alt"></i> {{ $phone }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Procedure & Insurance --}}
                                <td>
                                    <div class="sg-proc-title">{{ $surgery->surgery_type }}</div>
                                    <div class="sg-proc-meta">
                                        @if($surgery->surgicalOperation && $surgery->surgicalOperation->category)
                                            <span class="badge bg-light text-dark border px-2 py-0" style="font-size: 0.72rem;">
                                                {{ $surgery->surgicalOperation->category }}
                                            </span>
                                        @endif

                                        @if($surgery->insurance_type && $surgery->insurance_type !== 'none')
                                            <span class="sg-badge-ins-hi">
                                                <i class="fas fa-shield-alt me-1"></i>{{ $surgery->insurance_type === 'hi' ? 'ضمان صحي' : 'ضمان قوى الأمن' }}
                                            </span>
                                        @else
                                            <span class="sg-badge-ins-cash">
                                                <i class="fas fa-money-bill-wave me-1"></i>نقدي (كاش)
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Doctor --}}
                                <td>
                                    <div class="sg-doc-pill">
                                        <i class="fas fa-user-md text-primary"></i>
                                        <span>
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

                                {{-- Date & Room --}}
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <div class="d-flex align-items-center gap-1 font-monospace" style="font-size: 0.8rem; font-weight: 700; color: #334155;">
                                            <i class="fas fa-calendar-day text-muted me-1"></i>
                                            {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}
                                            <span class="text-muted ms-1">
                                                ({{ is_object($surgery->scheduled_time) ? $surgery->scheduled_time->format('H:i') : substr($surgery->scheduled_time ?? '', 0, 5) }})
                                            </span>
                                        </div>

                                        <div>
                                            @if($room)
                                                <span class="sg-room-pill {{ $roomType === 'vvip' ? 'sg-room-vvip' : ($roomType === 'vip' ? 'sg-room-vip' : 'sg-room-regular') }}">
                                                    @if($roomType === 'vvip')
                                                        <i class="fas fa-gem text-warning"></i> VVIP {{ $room->room_number }}
                                                    @elseif($roomType === 'vip')
                                                        <i class="fas fa-crown text-warning"></i> VIP {{ $room->room_number }}
                                                    @else
                                                        <i class="fas fa-bed text-primary"></i> غرفة {{ $room->room_number }}
                                                    @endif
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted border" style="font-size: 0.72rem;">بدون غرفة</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Status --}}
                                <td>
                                    @if($surgery->status === 'scheduled')
                                        <span class="sg-status-badge sg-status-scheduled">
                                            <i class="fas fa-calendar-check"></i> مجدولة
                                        </span>
                                    @elseif($surgery->status === 'waiting')
                                        <span class="sg-status-badge sg-status-waiting">
                                            <i class="fas fa-clock"></i> في الانتظار
                                        </span>
                                    @elseif($surgery->status === 'in_progress')
                                        <span class="sg-status-badge sg-status-in_progress">
                                            <span class="sg-pulse-dot me-1"></span> داخل الصالة (جارية)
                                        </span>
                                    @elseif($surgery->status === 'completed')
                                        <span class="sg-status-badge sg-status-completed">
                                            <i class="fas fa-check-circle"></i> مكتملة
                                        </span>
                                    @else
                                        <span class="sg-status-badge sg-status-cancelled">
                                            <i class="fas fa-times-circle"></i> ملغاة
                                        </span>
                                    @endif
                                </td>

                                {{-- Quick Diagnostics --}}
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @if($surgery->labTests && $surgery->labTests->count())
                                            <span class="sg-diag-chip sg-diag-lab" title="فحوصات مختبرية مرتبطة">
                                                <i class="fas fa-flask"></i> {{ $surgery->labTests->count() }} تحاليل
                                            </span>
                                        @endif
                                        @if($surgery->radiologyTests && $surgery->radiologyTests->count())
                                            <span class="sg-diag-chip sg-diag-rad" title="فحوصات أشعة وسونار">
                                                <i class="fas fa-x-ray"></i> {{ $surgery->radiologyTests->count() }} أشعة
                                            </span>
                                        @endif
                                        @if($surgery->surgeryTreatments && $surgery->surgeryTreatments->count())
                                            <span class="sg-diag-chip sg-diag-med" title="علاجات موصوفة">
                                                <i class="fas fa-pills"></i> {{ $surgery->surgeryTreatments->count() }} علاج
                                            </span>
                                        @endif
                                        @if((!$surgery->labTests || !$surgery->labTests->count()) && (!$surgery->radiologyTests || !$surgery->radiologyTests->count()) && (!$surgery->surgeryTreatments || !$surgery->surgeryTreatments->count()))
                                            <span class="text-muted" style="font-size: 0.76rem;">-</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Action Buttons --}}
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-1 justify-content-center">
                                        {{-- 1. Direct Clinical Station Button --}}
                                        <a href="{{ route('clinical-station.show', $surgery) }}" class="sg-btn-clinical" title="المحطة السريرية الموحدة">
                                            <i class="fas fa-stethoscope"></i>
                                            <span>المحطة</span>
                                        </a>

                                        {{-- 2. Surgery Show --}}
                                        <a href="{{ route('surgeries.show', $surgery) }}" class="sg-btn-icon" title="عرض تفاصيل العملية">
                                            <i class="fas fa-eye text-primary"></i>
                                        </a>

                                        {{-- 3. More Dropdown --}}
                                        <div class="dropdown">
                                            <button class="sg-btn-icon dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="font-size: 0.84rem; border-radius: 12px;">
                                                @can('edit surgeries')
                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('surgeries.edit', $surgery) }}">
                                                        <i class="fas fa-edit text-warning"></i>
                                                        <span>تعديل بيانات الحجز</span>
                                                    </a>
                                                </li>
                                                @endcan

                                                <li>
                                                    <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="{{ route('surgeries.print', $surgery) }}" target="_blank">
                                                        <i class="fas fa-print text-secondary"></i>
                                                        <span>طباعة سند / وصل العملية</span>
                                                    </a>
                                                </li>

                                                @if($surgery->status !== 'cancelled' && $surgery->status !== 'completed')
                                                <li>
                                                    <button type="button" class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" 
                                                            onclick="confirmCancelSurgery(event, '{{ route('surgeries.cancel', $surgery) }}', '{{ addslashes($surgery->surgery_type) }}')">
                                                        <i class="fas fa-ban text-danger"></i>
                                                        <span>إلغاء العملية</span>
                                                    </button>
                                                </li>
                                                @endif

                                                @can('delete surgeries')
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <form action="{{ route('surgeries.destroy', $surgery) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا السجل نهائياً؟');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger">
                                                            <i class="fas fa-trash-alt text-danger"></i>
                                                            <span>حذف السجل</span>
                                                        </button>
                                                    </form>
                                                </li>
                                                @endcan
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8">
                                    <div class="sg-empty-state">
                                        <div class="sg-empty-icon"><i class="fas fa-procedures"></i></div>
                                        <div class="sg-empty-title">لا توجد عمليات نشطة حالياً مطابقة لخيارات البحث</div>
                                        <p class="small text-muted mb-0">يمكنك حجز عملية جديدة أو تعديل خيارات التصفية والبحث في الأعلى</p>
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

        {{-- 6. Completed Surgeries Table Tab Pane --}}
        <div id="paneCompletedSurgeries" style="display: none;">
            <div class="sg-table-container">
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">#</th>
                                <th>بيانات المريض</th>
                                <th>نوع العملية والتغطية</th>
                                <th>الطبيب الجراح</th>
                                <th>تاريخ الإجراء</th>
                                <th>الغرفة السابقة</th>
                                <th>الحالة النهائية</th>
                                <th style="text-align: center; width: 150px;">الإجراءات السريعة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($completedSurgeries as $surgery)
                            @php
                                $patientName = optional(optional($surgery->patient)->user)->name ?? 'غير محدد';
                                $initial = mb_substr($patientName, 0, 1, 'UTF-8');
                                $phone = optional(optional($surgery->patient)->user)->phone;
                                $age = optional($surgery->patient)->age;
                                $gender = optional($surgery->patient)->gender;
                                $room = $surgery->room;
                            @endphp
                            <tr>
                                <td>
                                    <span class="fw-bold font-monospace text-muted" style="font-size: 0.8rem;">
                                        #{{ $surgery->id }}
                                    </span>
                                </td>

                                <td>
                                    <div class="sg-patient-box">
                                        <div class="sg-avatar" style="background: linear-gradient(135deg, #475569 0%, #334155 100%);">
                                            {{ $initial }}
                                        </div>
                                        <div>
                                            <a href="{{ route('surgeries.show', $surgery) }}" class="sg-patient-name">
                                                {{ $patientName }}
                                            </a>
                                            <div class="sg-meta-pills">
                                                @if(optional($surgery->patient)->file_number)
                                                    <span class="sg-meta-chip">MRN: {{ $surgery->patient->file_number }}</span>
                                                @endif
                                                @if($age)
                                                    <span class="sg-meta-chip">{{ $age }} سنة</span>
                                                @endif
                                                @if($phone)
                                                    <span class="sg-meta-chip text-muted" dir="ltr"><i class="fas fa-phone-alt"></i> {{ $phone }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="sg-proc-title">{{ $surgery->surgery_type }}</div>
                                    <div class="sg-proc-meta">
                                        @if($surgery->insurance_type && $surgery->insurance_type !== 'none')
                                            <span class="sg-badge-ins-hi">
                                                <i class="fas fa-shield-alt me-1"></i>{{ $surgery->insurance_type === 'hi' ? 'ضمان صحي' : 'ضمان قوى الأمن' }}
                                            </span>
                                        @else
                                            <span class="sg-badge-ins-cash">نقدي (كاش)</span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="sg-doc-pill">
                                        <i class="fas fa-user-md text-secondary"></i>
                                        <span>
                                            @if($surgery->doctor && $surgery->doctor->user)
                                                د. {{ $surgery->doctor->user->name }}
                                            @elseif($surgery->surgeon_name)
                                                {{ $surgery->surgeon_name }}
                                            @else
                                                -
                                            @endif
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <div class="font-monospace text-muted fw-bold" style="font-size: 0.82rem;">
                                        <i class="fas fa-calendar-day me-1"></i>
                                        {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}
                                    </div>
                                </td>

                                <td>
                                    @if($room)
                                        <span class="badge bg-light text-secondary border">غرفة {{ $room->room_number }}</span>
                                    @else
                                        <span class="badge bg-light text-muted border">-</span>
                                    @endif
                                </td>

                                <td>
                                    @if($surgery->status === 'completed')
                                        <span class="sg-status-badge sg-status-completed">
                                            <i class="fas fa-check-circle"></i> مكتملة بنجاح
                                        </span>
                                    @else
                                        <span class="sg-status-badge sg-status-cancelled" title="{{ $surgery->cancellation_reason }}">
                                            <i class="fas fa-times-circle"></i> ملغاة
                                        </span>
                                    @endif
                                </td>

                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-1 justify-content-center">
                                        <a href="{{ route('clinical-station.show', $surgery) }}" class="sg-btn-clinical" style="background: linear-gradient(135deg, #475569 0%, #334155 100%);" title="الأرشيف السريري">
                                            <i class="fas fa-folder-open"></i>
                                            <span>السجل</span>
                                        </a>

                                        <a href="{{ route('surgeries.show', $surgery) }}" class="sg-btn-icon" title="عرض الملف">
                                            <i class="fas fa-eye text-primary"></i>
                                        </a>

                                        <a href="{{ route('surgeries.print', $surgery) }}" target="_blank" class="sg-btn-icon" title="طباعة السند">
                                            <i class="fas fa-print text-secondary"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8">
                                    <div class="sg-empty-state">
                                        <div class="sg-empty-icon"><i class="fas fa-archive"></i></div>
                                        <div class="sg-empty-title">لا توجد عمليات مكتملة أو مؤرشفة مطابقة للبحث</div>
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
    </div>
</div>

{{-- Shared Hidden Cancellation Form --}}
<form id="cancelSurgeryConfirmForm" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="cancellation_reason" id="cancel_reason_input" value="">
</form>

<script>
    // Tab Switcher
    function switchSurgeryTab(tabName) {
        const btnActive = document.getElementById('btnTabActive');
        const btnCompleted = document.getElementById('btnTabCompleted');
        const paneActive = document.getElementById('paneActiveSurgeries');
        const paneCompleted = document.getElementById('paneCompletedSurgeries');
        const counterLabel = document.getElementById('tabCounterLabel');

        if (tabName === 'active') {
            btnActive.classList.add('active');
            btnCompleted.classList.remove('active');
            paneActive.style.display = 'block';
            paneCompleted.style.display = 'none';
            counterLabel.textContent = 'عرض العمليات الجراحية المفتوحة حالياً في المستشفى';
        } else {
            btnCompleted.classList.add('active');
            btnActive.classList.remove('active');
            paneActive.style.display = 'none';
            paneCompleted.style.display = 'block';
            counterLabel.textContent = 'عرض أرشيف وسجل العمليات الجراحية المكتملة والملغاة';
        }
    }

    // Cancellation Handler
    window.confirmCancelSurgery = function(event, route, title) {
        event.preventDefault();
        const message = 'هل أنت متأكد من إلغاء العملية الجراحية: ' + title + '؟';
        if (!window.confirm(message)) {
            return false;
        }

        const reason = window.prompt('يرجى إدخال سبب إلغاء العملية (اختياري):', '');
        if (reason === null) {
            return false;
        }

        const form = document.getElementById('cancelSurgeryConfirmForm');
        const reasonInput = document.getElementById('cancel_reason_input');
        if (form && route) {
            form.action = route;
            if (reasonInput) {
                reasonInput.value = reason.trim();
            }
            form.submit();
        }
        return true;
    };
</script>
@endsection
