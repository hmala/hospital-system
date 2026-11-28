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
        .sg-kpi-card.kpi-discharged::before { background: #64748b; }
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
        .kpi-discharged .sg-kpi-icon { background: #f1f5f9; color: #475569; }
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
        .sg-tab-btn.active .badge-discharged-color {
            background: #f1f5f9;
            color: #475569;
        }
        .sg-tab-btn.active .badge-cancelled-color {
            background: #ffe4e6;
            color: #e11d48;
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
                        {{ __('سجل العمليات الجراحية') }}
                        <span class="sg-badge-registry">Surgery Registry</span>
                    </h1>
                    <p class="sg-subtitle">{{ __('متابعة كافة العمليات الجراحية، الحالات النشطة، الأرشيف السريري وتفاصيل المرضى') }}</p>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                @can('create surgeries')
                <a href="{{ route('surgeries.create') }}" class="sg-btn-create">
                    <i class="fas fa-plus me-1"></i> {{ __('حجز عملية جديدة') }}
                </a>
                @endcan

                @canany(['manage surgery waiting list', 'view surgeries'])
                <a href="{{ route('surgeries.waiting') }}" class="sg-btn-link-action">
                    <i class="fas fa-clock text-warning"></i>
                    <span>{{ __('قائمة الانتظار والصالات') }}</span>
                </a>
                @endcanany

                @canany(['manage rooms', 'view occupancy'])
                <a href="{{ route('rooms.index') }}" class="sg-btn-link-action">
                    <i class="fas fa-bed text-danger"></i>
                    <span>{{ __('إدارة الغرف') }}</span>
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
                    <div class="sg-kpi-title">{{ __('إجمالي العمليات المسجلة') }}</div>
                    <div class="sg-kpi-num">{{ $stats->total ?? ($activeSurgeries->total() + $completedSurgeries->total() + $dischargedSurgeries->total() + $cancelledSurgeries->total()) }}</div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>

            <div class="sg-kpi-card kpi-active">
                <div>
                    <div class="sg-kpi-title d-flex align-items-center">
                        <span>{{ __('العمليات النشطة (داخل المسار)') }}</span>
                        <span class="sg-pulse-dot" title="{{ __('حالات نشطة جارية') }}"></span>
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
                    <div class="sg-kpi-title">{{ __('بالانتظار والتحضير') }}</div>
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
                    <div class="sg-kpi-title">{{ __('المكتملة (خرجت من الصالة)') }}</div>
                    <div class="sg-kpi-num text-primary">
                        {{ $stats->completed ?? $completedSurgeries->total() }}
                    </div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>

            <div class="sg-kpi-card kpi-discharged">
                <div>
                    <div class="sg-kpi-title">{{ __('المرضى المخرّجين') }}</div>
                    <div class="sg-kpi-num text-secondary">
                        {{ $stats->discharged ?? $dischargedSurgeries->total() }}
                    </div>
                </div>
                <div class="sg-kpi-icon">
                    <i class="fas fa-door-open"></i>
                </div>
            </div>

            <div class="sg-kpi-card kpi-cancelled">
                <div>
                    <div class="sg-kpi-title">{{ __('العمليات الملغاة') }}</div>
                    <div class="sg-kpi-num text-danger">
                        {{ $stats->cancelled ?? $cancelledSurgeries->total() }}
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
                        <i class="fas fa-search me-1 text-primary"></i> {{ __('البحث السريع') }}
                    </label>
                    <input type="text" class="form-control sg-input" id="search" name="search" 
                           value="{{ request('search') }}" 
                           placeholder="{{ __('ابحث بالمريض أو الطبيب أو نوع العملية...') }}">
                </div>

                <div class="col-lg-3 col-md-3">
                    <label for="status_filter" class="sg-form-label">
                        <i class="fas fa-filter me-1 text-primary"></i> {{ __('الحالة') }}
                    </label>
                    <select class="form-select sg-input" id="status_filter" name="status">
                        <option value="">{{ __('جميع الحالات') }}</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>{{ __('مجدولة') }}</option>
                        <option value="waiting" {{ request('status') == 'waiting' ? 'selected' : '' }}>{{ __('في الانتظار') }}</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>{{ __('جارية') }}</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __('مكتملة') }}</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('ملغاة') }}</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-3">
                    <label for="date_filter" class="sg-form-label">
                        <i class="fas fa-calendar-day me-1 text-primary"></i> {{ __('التاريخ') }}
                    </label>
                    <input type="date" class="form-control sg-input" id="date_filter" name="date" value="{{ request('date') }}">
                </div>

                <div class="col-lg-2 col-md-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold" style="border-radius: 10px; padding: 8px 12px;">
                            <i class="fas fa-search me-1"></i> {{ __('بحث') }}
                        </button>
                        @if(request('search') || request('status') || request('date'))
                        <a href="{{ route('surgeries.index') }}" class="btn btn-outline-secondary" style="border-radius: 10px; padding: 8px 14px;" title="{{ __('إعادة تعيين') }}">
                            <i class="fas fa-times"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        {{-- 4. Segmented Tabs --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="sg-tabs-strip flex-wrap">
                <button type="button" class="sg-tab-btn {{ $activeIndexTab === 'active' ? 'active' : '' }}" id="btnTabActive" onclick="switchSurgeryTab('active')">
                    <i class="fas fa-heartbeat text-success"></i>
                    <span>{{ __('العمليات النشطة (داخل المسار)') }}</span>
                    <span class="sg-tab-pill-badge badge-active-color">{{ $activeSurgeries->total() }}</span>
                </button>
                <button type="button" class="sg-tab-btn {{ $activeIndexTab === 'completed' ? 'active' : '' }}" id="btnTabCompleted" onclick="switchSurgeryTab('completed')">
                    <i class="fas fa-check-circle text-primary"></i>
                    <span>{{ __('المكتملة بالصالة (بالردهة)') }}</span>
                    <span class="sg-tab-pill-badge badge-completed-color">{{ $completedSurgeries->total() }}</span>
                </button>
                <button type="button" class="sg-tab-btn {{ $activeIndexTab === 'discharged' ? 'active' : '' }}" id="btnTabDischarged" onclick="switchSurgeryTab('discharged')">
                    <i class="fas fa-door-open text-secondary"></i>
                    <span>{{ __('المرضى المخرّجين') }}</span>
                    <span class="sg-tab-pill-badge badge-discharged-color">{{ $dischargedSurgeries->total() }}</span>
                </button>
                <button type="button" class="sg-tab-btn {{ $activeIndexTab === 'cancelled' ? 'active' : '' }}" id="btnTabCancelled" onclick="switchSurgeryTab('cancelled')">
                    <i class="fas fa-ban text-danger"></i>
                    <span>{{ __('العمليات الملغاة') }}</span>
                    <span class="sg-tab-pill-badge badge-cancelled-color">{{ $cancelledSurgeries->total() }}</span>
                </button>
                @if($canViewOrStoreTab)
                    <button type="button" class="sg-tab-btn {{ $activeIndexTab === 'or_store' ? 'active' : '' }}" id="btnTabOrStore" onclick="switchSurgeryTab('or_store')">
                        <i class="fas fa-warehouse text-warning"></i>
                        <span>{{ __('مخزن وعُهدة العمليات') }}</span>
                        <span class="sg-tab-pill-badge badge-or-color">{{ $orStockBatches->count() }}</span>
                    </button>
                @endif
            </div>

            <div class="text-muted small fw-bold">
                <i class="fas fa-info-circle me-1 text-primary"></i>
                <span id="tabCounterLabel">
                    @if($activeIndexTab === 'completed')
                        {{ __('عرض العمليات المكتملة في صالة العمليات (نزلاء الردهة قيد المتابعة)') }}
                    @elseif($activeIndexTab === 'discharged')
                        {{ __('أرشيف وسجل المرضى الذين تم تخريجهم نهائياً من المستشفى') }}
                    @elseif($activeIndexTab === 'cancelled')
                        {{ __('سجل العمليات الملغاة مع إمكانية التراجع والاستعادة') }}
                    @elseif($activeIndexTab === 'or_store')
                        {{ __('جرد ومراقبة عهدة مخزن العمليات وحركات الصرف المباشر للعمليات') }}
                    @else
                        {{ __('عرض العمليات النشطة حالياً داخل المستشفى') }}
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
                                <th class="text-center" style="width: 10%;" title="{{ __('حالة المريض في الصالة') }}">{{ __('الحالة') }}</th>
                                <th>{{ __('المريض') }}</th>
                                <th>{{ __('نوع العملية') }}</th>
                                <th>{{ __('الطبيب الجراح') }}</th>
                                <th>{{ __('رقم الغرفة') }}</th>
                                <th class="text-center" style="width: 90px;">{{ __('الإجراءات') }}</th>
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
                                            <span class="badge bg-white text-muted border px-1.5 py-0.5" style="font-size: 0.72rem;">{{ $surgery->patient->age }} {{ __('سنة') }}</span>
                                        @endif
                                        @if($surgery->patient && $surgery->patient->gender)
                                            <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.72rem;">
                                                {{ in_array($surgery->patient->gender, ['male', 'ذكر']) ? __('ذكر') : __('أنثى') }}
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
                                            <span class="badge bg-success-subtle text-success border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="{{ $surgery->insurance_type === 'hi' ? __('ضمان صحي') : __('ضمان قوى الأمن') }}">{{ __('ضمان') }}</span>
                                        @else
                                            <span class="badge bg-light text-secondary border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="{{ __('نقدي (كاش)') }}">{{ __('كاش') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="fas fa-user-md text-success"></i>
                                        <span class="fw-semibold">
                                            @if($surgery->doctor && $surgery->doctor->user)
                                                {{ __('د.') }} {{ $surgery->doctor->user->name }}
                                            @elseif($surgery->surgeon_name)
                                                {{ $surgery->surgeon_name }} <span class="badge bg-light text-secondary border">{{ __('خارجي') }}</span>
                                            @else
                                                <span class="text-muted">{{ __('غير محدد') }}</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if($room)
                                        <div class="d-flex flex-column gap-1 align-items-start">
                                            <span class="room-badge-pill {{ $rtype === 'vvip' ? 'room-badge-vvip' : ($rtype === 'vip' ? 'room-badge-vip' : 'room-badge-regular') }}">
                                                @if($rtype === 'vvip')
                                                    <i class="fas fa-gem text-warning"></i> VVIP {{ $room->room_number }}
                                                @elseif($rtype === 'vip')
                                                    <i class="fas fa-crown text-warning"></i> VIP {{ $room->room_number }}
                                                @else
                                                    <i class="fas fa-bed text-primary"></i> {{ $room->room_number }}
                                                @endif
                                            </span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0" style="font-size: 0.68rem; font-weight: 600;">
                                                <i class="fas fa-user-check me-1 text-success"></i>{{ __('إشغال حالي') }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="d-flex flex-column gap-1 align-items-start">
                                            <span class="badge bg-warning-subtle text-dark border border-warning"><i class="fas fa-hourglass-half me-1 text-warning"></i>{{ __('وضع انتظار الغرفة') }}</span>
                                            @if(auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || auth()->user()->can('assign surgery room'))
                                                <a href="{{ route('inquiry.occupancy', ['tab' => 'waiting_room']) }}" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0" style="font-size: 0.68rem;" title="{{ __('تخصيص غرفة من وضع الانتظار') }}">
                                                    <i class="fas fa-bed"></i> {{ __('تخصيص') }}
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            @can('edit surgeries')
                                                <a href="{{ route('surgeries.edit', $surgery) }}" class="btn btn-sm btn-outline-warning text-dark px-2 py-1 rounded-2 shadow-xs" title="{{ __('تعديل الحجز') }}">
                                                    <i class="fas fa-edit text-warning"></i>
                                                </a>
                                            @endcan

                                            @if($surgery->status !== 'cancelled' && $surgery->status !== 'completed' && !$isOtCompleted)
                                                @canany(['delete surgeries', 'cancel surgeries'])
                                                    <button type="button" class="btn btn-sm btn-outline-danger px-2 py-1 rounded-2 shadow-xs" title="{{ __('إلغاء العملية') }}" onclick="confirmCancelSurgery(event, '{{ route('surgeries.cancel', $surgery) }}', '{{ addslashes($patientName) }}')">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                @endcanany
                                            @endif
                                        </div>

                                        @if($room && (auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']) || auth()->user()->can('assign surgery room') || auth()->user()->can('edit surgeries')))
                                            <div class="d-inline-flex gap-1 align-items-center mt-1">
                                                <button type="button" 
                                                        class="btn btn-xs btn-outline-info rounded-pill px-2 py-0 fw-semibold" 
                                                        style="font-size: 0.68rem;" 
                                                        onclick="openSwapRoomModal({{ $surgery->id }}, '{{ addslashes($patientName) }}', '{{ $room->room_number }}')" 
                                                        title="{{ __('تبديل الغرفة مع مريض آخر') }}">
                                                    <i class="fas fa-exchange-alt"></i> {{ __('تبديل') }}
                                                </button>
                                                <form action="{{ route('surgeries.move-to-waiting-zone', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد من إخلاء الغرفة (:room) ونقل المريض (:name) إلى وضع انتظار الغرفة؟', ['room' => $room->room_number, 'name' => $patientName]) }}');">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-xs btn-outline-warning rounded-pill px-2 py-0 fw-semibold text-dark" 
                                                            style="font-size: 0.68rem;" 
                                                            title="{{ __('إخلاء الغرفة ونقل المريض إلى وضع الانتظار') }}">
                                                        <i class="fas fa-hourglass-half"></i> {{ __('للانتظار') }}
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-procedures fa-3x mb-3 text-secondary"></i>
                                        <div class="fw-bold fs-5">{{ __('لا توجد عمليات نشطة حالياً مطابقة للبحث') }}</div>
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
                        {{ __('عرض') }} {{ $activeSurgeries->firstItem() }} {{ __('إلى') }} {{ $activeSurgeries->lastItem() }} {{ __('من أصل') }} {{ $activeSurgeries->total() }} {{ __('عملية نشطة') }}
                    </div>
                    <div>
                        {{ $activeSurgeries->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- 6. Completed in OT Surgeries Table (خرجت من الصالة للمتابعة بالردهة) --}}
        <div id="paneCompletedSurgeries" style="{{ $activeIndexTab === 'completed' ? '' : 'display: none;' }}">
            <div class="sg-table-container">
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 10%;">{{ __('الحالة السريرية') }}</th>
                                <th>{{ __('المريض') }}</th>
                                <th>{{ __('نوع العملية') }}</th>
                                <th>{{ __('الطبيب الجراح') }}</th>
                                <th>{{ __('رقم الغرفة') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('الإجراءات') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($completedSurgeries as $surgery)
                            @php
                                $patientName = optional(optional($surgery->patient)->user)->name ?? 'غير محدد';
                                $phone = optional(optional($surgery->patient)->user)->phone;
                                $room = $surgery->room;
                                $rtype = $room ? $room->room_type : null;
                            @endphp
                            <tr style="background-color: rgba(16, 185, 129, 0.05); border-right: 4px solid #10b981;">
                                <td class="text-center">
                                    <span class="badge py-1 px-2.5 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.78rem;" title="{{ __('خرج من صالة العمليات ويقيم في الردهة للمتابعة') }}">
                                        <i class="fas fa-procedures me-1 text-primary"></i>Post-Op (بالردهة)
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('surgeries.show', $surgery) }}" class="patient-title-btn" title="{{ __('عرض تفاصيل العملية') }}">
                                        <i class="fas fa-check-circle text-success fs-5"></i>
                                        <span class="text-success">{{ $patientName }}</span>
                                    </a>
                                    <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                        @if($surgery->patient && $surgery->patient->age)
                                            <span class="badge bg-white text-muted border px-1.5 py-0.5" style="font-size: 0.72rem;">{{ $surgery->patient->age }} {{ __('سنة') }}</span>
                                        @endif
                                        @if($surgery->patient && $surgery->patient->gender)
                                            <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.72rem;">
                                                {{ in_array($surgery->patient->gender, ['male', 'ذكر']) ? __('ذكر') : __('أنثى') }}
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
                                            <span class="badge bg-success-subtle text-success border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="{{ $surgery->insurance_type === 'hi' ? __('ضمان صحي') : __('ضمان قوى الأمن') }}">{{ __('ضمان') }}</span>
                                        @else
                                            <span class="badge bg-light text-secondary border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="{{ __('نقدي (كاش)') }}">{{ __('كاش') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="fas fa-user-md text-success"></i>
                                        <span class="fw-semibold">
                                            @if($surgery->doctor && $surgery->doctor->user)
                                                {{ __('د.') }} {{ $surgery->doctor->user->name }}
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
                                        <div class="d-flex flex-column gap-1 align-items-start">
                                            <span class="room-badge-pill {{ $rtype === 'vvip' ? 'room-badge-vvip' : ($rtype === 'vip' ? 'room-badge-vip' : 'room-badge-regular') }}">
                                                @if($rtype === 'vvip')
                                                    <i class="fas fa-gem text-warning"></i> VVIP {{ $room->room_number }}
                                                @elseif($rtype === 'vip')
                                                    <i class="fas fa-crown text-warning"></i> VIP {{ $room->room_number }}
                                                @else
                                                    <i class="fas fa-bed text-primary"></i> {{ $room->room_number }}
                                                @endif
                                            </span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-1.5 py-0" style="font-size: 0.68rem; font-weight: 600;">
                                                <i class="fas fa-user-check me-1 text-success"></i>{{ __('إشغال حالي') }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="d-flex flex-column gap-1 align-items-start">
                                            <span class="badge bg-warning-subtle text-dark border border-warning"><i class="fas fa-hourglass-half me-1 text-warning"></i>{{ __('وضع انتظار الغرفة') }}</span>
                                            @if(auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || auth()->user()->can('assign surgery room'))
                                                <a href="{{ route('inquiry.occupancy', ['tab' => 'waiting_room']) }}" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0" style="font-size: 0.68rem;" title="{{ __('تخصيص غرفة من وضع الانتظار') }}">
                                                    <i class="fas fa-bed"></i> {{ __('تخصيص') }}
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <div class="d-inline-flex gap-1 align-items-center">
                                            <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-primary px-2 py-1 rounded-2 shadow-xs" title="{{ __('عرض تفاصيل العملية') }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('surgeries.print', $surgery) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2 py-1 rounded-2 shadow-xs" title="{{ __('طباعة تقرير العملية') }}">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            @can('edit surgeries')
                                                <a href="{{ route('surgeries.edit', $surgery) }}" class="btn btn-sm btn-outline-warning text-dark px-2 py-1 rounded-2 shadow-xs" title="{{ __('تعديل الحجز') }}">
                                                    <i class="fas fa-edit text-warning"></i>
                                                </a>
                                            @endcan
                                        </div>

                                        @if($room && (auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']) || auth()->user()->can('assign surgery room') || auth()->user()->can('edit surgeries')))
                                            <div class="d-inline-flex gap-1 align-items-center mt-1">
                                                <button type="button" 
                                                        class="btn btn-xs btn-outline-info rounded-pill px-2 py-0 fw-semibold" 
                                                        style="font-size: 0.68rem;" 
                                                        onclick="openSwapRoomModal({{ $surgery->id }}, '{{ addslashes($patientName) }}', '{{ $room->room_number }}')" 
                                                        title="{{ __('تبديل الغرفة مع مريض آخر') }}">
                                                    <i class="fas fa-exchange-alt"></i> {{ __('تبديل') }}
                                                </button>
                                                <form action="{{ route('surgeries.move-to-waiting-zone', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد من إخلاء الغرفة (:room) ونقل المريض (:name) إلى وضع انتظار الغرفة؟', ['room' => $room->room_number, 'name' => $patientName]) }}');">
                                                    @csrf
                                                    <button type="submit" 
                                                            class="btn btn-xs btn-outline-warning rounded-pill px-2 py-0 fw-semibold text-dark" 
                                                            style="font-size: 0.68rem;" 
                                                            title="{{ __('إخلاء الغرفة ونقل المريض إلى وضع الانتظار') }}">
                                                        <i class="fas fa-hourglass-half"></i> {{ __('للانتظار') }}
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-procedures fa-3x mb-3 text-secondary"></i>
                                        <div class="fw-bold fs-5">{{ __('لا توجد عمليات مكتملة بالصالة حالياً مطابقة للبحث') }}</div>
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
                        {{ __('عرض') }} {{ $completedSurgeries->firstItem() }} {{ __('إلى') }} {{ $completedSurgeries->lastItem() }} {{ __('من أصل') }} {{ $completedSurgeries->total() }} {{ __('عملية مكتملة بالصالة') }}
                    </div>
                    <div>
                        {{ $completedSurgeries->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- 7. Discharged Patients Table (المرضى المخرّجون نهائياً من المستشفى) --}}
        <div id="paneDischargedSurgeries" style="{{ $activeIndexTab === 'discharged' ? '' : 'display: none;' }}">
            <div class="sg-table-container">
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 12%;">{{ __('تاريخ وساعة التخريج') }}</th>
                                <th>{{ __('المريض') }}</th>
                                <th>{{ __('نوع العملية') }}</th>
                                <th>{{ __('الطبيب الجراح') }}</th>
                                <th>{{ __('الغرفة السابقة') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('الإجراءات') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dischargedSurgeries as $surgery)
                            @php
                                $patientName = optional(optional($surgery->patient)->user)->name ?? 'غير محدد';
                                $phone = optional(optional($surgery->patient)->user)->phone;
                                $room = $surgery->room;
                                $rtype = $room ? $room->room_type : null;
                            @endphp
                            <tr style="background-color: rgba(100, 116, 139, 0.04); border-right: 4px solid #64748b;">
                                <td class="text-center">
                                    <span class="badge py-1 px-2.5 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 0.76rem;" title="{{ __('تاريخ التخريج:') }} {{ $surgery->discharged_at ? $surgery->discharged_at->format('Y-m-d H:i') : '-' }}">
                                        <i class="fas fa-door-open me-1 text-secondary"></i>
                                        {{ $surgery->discharged_at ? $surgery->discharged_at->format('Y-m-d H:i') : __('مُخرّج') }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('surgeries.show', $surgery) }}" class="patient-title-btn" title="{{ __('عرض تفاصيل وسجل العملية') }}">
                                        <i class="fas fa-user-check text-secondary fs-5"></i>
                                        <span class="text-dark">{{ $patientName }}</span>
                                    </a>
                                    <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                        @if($surgery->patient && $surgery->patient->age)
                                            <span class="badge bg-white text-muted border px-1.5 py-0.5" style="font-size: 0.72rem;">{{ $surgery->patient->age }} {{ __('سنة') }}</span>
                                        @endif
                                        @if($surgery->patient && $surgery->patient->gender)
                                            <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.72rem;">
                                                {{ in_array($surgery->patient->gender, ['male', 'ذكر']) ? __('ذكر') : __('أنثى') }}
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
                                            <span class="badge bg-success-subtle text-success border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="{{ $surgery->insurance_type === 'hi' ? __('ضمان صحي') : __('ضمان قوى الأمن') }}">{{ __('ضمان') }}</span>
                                        @else
                                            <span class="badge bg-light text-secondary border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="{{ __('نقدي (كاش)') }}">{{ __('كاش') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="fas fa-user-md text-secondary"></i>
                                        <span class="fw-semibold">
                                            @if($surgery->doctor && $surgery->doctor->user)
                                                {{ __('د.') }} {{ $surgery->doctor->user->name }}
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
                                        <div class="d-flex flex-column gap-1 align-items-start">
                                            <span class="room-badge-pill {{ $rtype === 'vvip' ? 'room-badge-vvip' : ($rtype === 'vip' ? 'room-badge-vip' : 'room-badge-regular') }}">
                                                @if($rtype === 'vvip')
                                                    <i class="fas fa-gem text-warning"></i> VVIP {{ $room->room_number }}
                                                @elseif($rtype === 'vip')
                                                    <i class="fas fa-crown text-warning"></i> VIP {{ $room->room_number }}
                                                @else
                                                    <i class="fas fa-bed text-primary"></i> {{ $room->room_number }}
                                                @endif
                                            </span>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-1.5 py-0" style="font-size: 0.68rem; font-weight: 600;">
                                                <i class="fas fa-door-open me-1 text-secondary"></i>{{ __('أُخلي السرير') }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        @if(auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']) || auth()->user()->can('control surgeries') || auth()->user()->can('edit surgeries'))
                                            <form action="{{ route('surgeries.undo-discharge', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('تأكيد التراجع عن تخريج المريض (:name) وإعادته إلى قائمة العمليات النشطة؟', ['name' => $patientName]) }}');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success px-2 py-1 rounded-2 shadow-xs fw-semibold" title="{{ __('التراجع عن التخريج واستعادة المريض') }}">
                                                    <i class="fas fa-undo me-1"></i>{{ __('استعادة') }}
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-primary px-2 py-1 rounded-2 shadow-xs" title="{{ __('عرض ملف العملية') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('surgeries.print', $surgery) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2 py-1 rounded-2 shadow-xs" title="{{ __('طباعة تقرير العملية') }}">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        @can('delete surgeries')
                                            <form action="{{ route('surgeries.destroy', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد من حذف سجل هذه العملية نهائياً؟') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1 rounded-2 shadow-xs" title="{{ __('حذف السجل') }}">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-door-open fa-3x mb-3 text-secondary"></i>
                                        <div class="fw-bold fs-5">{{ __('لا يوجد مرضى مخرّجون مطابقون للبحث') }}</div>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($dischargedSurgeries->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        {{ __('عرض') }} {{ $dischargedSurgeries->firstItem() }} {{ __('إلى') }} {{ $dischargedSurgeries->lastItem() }} {{ __('من أصل') }} {{ $dischargedSurgeries->total() }} {{ __('مريض مخرّج') }}
                    </div>
                    <div>
                        {{ $dischargedSurgeries->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- 8. Cancelled Surgeries Table (العمليات الملغاة) --}}
        <div id="paneCancelledSurgeries" style="{{ $activeIndexTab === 'cancelled' ? '' : 'display: none;' }}">
            <div class="sg-table-container">
                <div class="table-responsive">
                    <table class="sg-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 10%;">{{ __('الحالة') }}</th>
                                <th>{{ __('المريض') }}</th>
                                <th>{{ __('نوع العملية') }}</th>
                                <th>{{ __('الطبيب الجراح') }}</th>
                                <th>{{ __('سبب الإلغاء') }}</th>
                                <th class="text-center" style="width: 120px;">{{ __('الإجراءات') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cancelledSurgeries as $surgery)
                            @php
                                $patientName = optional(optional($surgery->patient)->user)->name ?? 'غير محدد';
                                $phone = optional(optional($surgery->patient)->user)->phone;
                            @endphp
                            <tr style="background-color: rgba(239, 68, 68, 0.04); border-right: 4px solid #ef4444;">
                                <td class="text-center">
                                    <span class="badge py-1 px-2.5 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-size: 0.78rem;" title="{{ __('عملية ملغاة') }}">
                                        <i class="fas fa-times me-1 text-danger"></i>Cancelled
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('surgeries.show', $surgery) }}" class="patient-title-btn" title="{{ __('عرض تفاصيل العملية') }}">
                                        <i class="fas fa-user-times text-danger fs-5"></i>
                                        <span class="text-danger">{{ $patientName }}</span>
                                    </a>
                                    <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                        @if($surgery->patient && $surgery->patient->age)
                                            <span class="badge bg-white text-muted border px-1.5 py-0.5" style="font-size: 0.72rem;">{{ $surgery->patient->age }} {{ __('سنة') }}</span>
                                        @endif
                                        @if($surgery->patient && $surgery->patient->gender)
                                            <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.72rem;">
                                                {{ in_array($surgery->patient->gender, ['male', 'ذكر']) ? __('ذكر') : __('أنثى') }}
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
                                            <span class="badge bg-success-subtle text-success border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="{{ $surgery->insurance_type === 'hi' ? __('ضمان صحي') : __('ضمان قوى الأمن') }}">{{ __('ضمان') }}</span>
                                        @else
                                            <span class="badge bg-light text-secondary border py-0 px-1.5 rounded-pill text-nowrap" style="font-size: 0.65rem;" title="{{ __('نقدي (كاش)') }}">{{ __('كاش') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <i class="fas fa-user-md text-secondary"></i>
                                        <span class="fw-semibold">
                                            @if($surgery->doctor && $surgery->doctor->user)
                                                {{ __('د.') }} {{ $surgery->doctor->user->name }}
                                            @elseif($surgery->surgeon_name)
                                                {{ $surgery->surgeon_name }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small fw-semibold" title="{{ $surgery->cancellation_reason }}">
                                        {{ $surgery->cancellation_reason ? \Illuminate\Support\Str::limit($surgery->cancellation_reason, 40) : __('لم يحدد سبب الإلغاء') }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        @if(auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || auth()->user()->can('control surgeries'))
                                            <form action="{{ route('surgeries.reactivate', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('تأكيد التراجع عن إلغاء هذه العملية وإعادة تفعيلها؟') }}');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success px-2 py-1 rounded-2 shadow-xs fw-semibold" title="{{ __('التراجع عن الإلغاء وإعادة التفعيل') }}">
                                                    <i class="fas fa-undo me-1"></i>{{ __('استعادة') }}
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-primary px-2 py-1 rounded-2 shadow-xs" title="{{ __('عرض التفاصيل') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @can('delete surgeries')
                                            <form action="{{ route('surgeries.destroy', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد من حذف سجل هذه العملية نهائياً؟') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1 rounded-2 shadow-xs" title="{{ __('حذف السجل') }}">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-ban fa-3x mb-3 text-secondary"></i>
                                        <div class="fw-bold fs-5">{{ __('لا توجد عمليات ملغاة مطابقة للبحث') }}</div>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($cancelledSurgeries->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="text-muted small">
                        {{ __('عرض') }} {{ $cancelledSurgeries->firstItem() }} {{ __('إلى') }} {{ $cancelledSurgeries->lastItem() }} {{ __('من أصل') }} {{ $cancelledSurgeries->total() }} {{ __('عملية ملغاة') }}
                    </div>
                    <div>
                        {{ $cancelledSurgeries->links() }}
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
                                    <span>{{ __('المسؤول عن العهدة:') }}</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5">
                                        <i class="fas fa-check-circle me-1"></i> {{ __('حسابك مخول بالطلب والصرف') }}
                                    </span>
                                    <span>{{ __('• إجمالي المواد المتوفرة:') }} <strong class="text-primary">{{ $orStockBatches->sum('current_qty') }}</strong> {{ __('وحدة') }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <a href="{{ route('stock-transfers.create', ['to_location_id' => $orCurrentLocation->id]) }}" class="btn btn-warning rounded-pill px-3 py-2 fw-bold shadow-sm text-dark">
                                <i class="fas fa-plus-circle me-1"></i> {{ __('+ طلب تعزيز مواد') }}
                            </a>
                            <a href="{{ route('stock-transfers.returns.create', ['from_location_id' => $orCurrentLocation->id, 'from_surgery' => 1]) }}" class="btn btn-outline-danger rounded-pill px-3 py-2 fw-bold shadow-xs">
                                <i class="fas fa-undo-alt me-1"></i> {{ __('إرجاع عهدة للمخزن الرئيسي') }}
                            </a>
                            <a href="{{ route('stock-transfers.requests.index') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 shadow-xs">
                                <i class="fas fa-history me-1"></i> {{ __('سجل الطلبيات') }}
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
                                    <h6 class="mb-0 fw-bold text-dark">{{ __('المستلزمات والأدوية المتوفرة في عهدة العمليات') }}</h6>
                                </div>
                                <span class="badge bg-primary text-white rounded-pill px-3 py-1" id="orStockItemsCountBadge">{{ $orStockBatches->count() }} مادة</span>
                            </div>

                            {{-- Live Search & Category Filters --}}
                            <div class="p-3 bg-light border-bottom">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-5">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                            <input type="text" id="orStockSearchInput" class="form-control bg-white border-start-0 ps-0" placeholder="{{ __('بحث باسم المادة، الباركود، أو رقم الوجبة...') }}">
                                            <button class="btn btn-outline-secondary border-start-0 bg-white" type="button" id="clearOrStockSearch" style="display:none;">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-7 d-flex align-items-center gap-1 flex-wrap justify-content-md-end">
                                        <span class="text-muted small me-1">{{ __('تصفية:') }}</span>
                                        <button type="button" class="btn btn-xs btn-dark rounded-pill px-2 py-1 or-stock-filter-btn active" data-filter="all">{{ __('الكل') }}</button>
                                        <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-2 py-1 or-stock-filter-btn" data-filter="supplies">{{ __('🩹 مستلزمات') }}</button>
                                        <button type="button" class="btn btn-xs btn-outline-info rounded-pill px-2 py-1 or-stock-filter-btn" data-filter="medicines">{{ __('💊 أدوية/أخرى') }}</button>
                                        <button type="button" class="btn btn-xs btn-outline-warning rounded-pill px-2 py-1 or-stock-filter-btn" data-filter="low">{{ __('⚠️ نفاد') }}</button>
                                        <button type="button" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-1 or-stock-filter-btn" data-filter="expiring">{{ __('⏳ قريبة الانتهاء') }}</button>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="orStockTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="py-2 px-3">{{ __('المادة / المستلزم') }}</th>
                                                <th class="py-2 text-center">{{ __('الرصيد الحالي') }}</th>
                                                <th class="py-2 text-center">{{ __('تاريخ الصلاحية') }}</th>
                                                <th class="py-2 text-center">{{ __('رقم الوجبة') }}</th>
                                                <th class="py-2 text-center">{{ __('الحالة') }}</th>
                                                <th class="py-2 text-center" style="width: 100px;">{{ __('إجراء') }}</th>
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
                                                            <span class="badge bg-warning text-dark rounded-pill px-2 py-1">{{ __('شارف على النفاد') }}</span>
                                                        @else
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">{{ __('متوفر') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if($isOperationCustodian)
                                                            <a href="{{ route('stock-transfers.returns.create', ['from_location_id' => $orCurrentLocation->id, 'batch_id' => $batch->id, 'from_surgery' => 1]) }}"
                                                               class="btn btn-sm btn-outline-warning rounded-pill px-2 py-1 fw-bold text-nowrap shadow-xs"
                                                               title="{{ __('إرجاع هذه الوجبة للمخزن الرئيسي') }}">
                                                                <i class="fas fa-undo-alt me-1"></i> {{ __('إرجاع') }}
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
                                                                <i class="fas fa-plus-circle me-1"></i> {{ __('اطلب أول شحنة تعزيز من المخزن الرئيسي') }}
                                                            </a>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforelse
                                            <tr id="noMatchingOrStockRow" style="display: none;">
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    <i class="fas fa-search fa-2x mb-2 text-secondary opacity-50"></i>
                                                    <p class="mb-0 fw-semibold">{{ __('لا توجد مواد تطابق معايير البحث والتصفية المحددة.') }}</p>
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
                                    <i class="fas fa-history text-info me-2"></i> {{ __('سجل حركات مخزن العمليات') }}
                                </h6>
                                <span class="badge bg-info text-white rounded-pill px-2 py-1 small">{{ __('مباشر') }}</span>
                            </div>
                            <div class="card-body p-3">
                                @if($orRecentMovements->count())
                                    <div class="list-group list-group-flush">
                                        @foreach($orRecentMovements as $mv)
                                            <div class="list-group-item px-0 py-2 border-bottom">
                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                    <div>
                                                        <div class="fw-bold text-dark small">{{ $mv->batch?->product?->name ?? __('مادة مخزنية') }}</div>
                                                        <small class="text-muted d-block">
                                                            <i class="fas fa-user text-primary me-1"></i>
                                                            {{ __('بواسطة:') }} <strong>{{ $mv->user?->full_name ?? $mv->user?->name ?? __('كادر العمليات') }}</strong>
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



{{-- Hidden Form for Cancellation --}}
<form id="cancelSurgeryConfirmForm" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="cancellation_reason" id="cancel_reason_input" value="">
</form>

{{-- نافذة تبديل الغرفة الذكي بين مريضين --}}
<div class="modal fade" id="swapRoomModal" tabindex="-1" aria-labelledby="swapRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form id="swapRoomForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-light border-0 py-3">
                    <h5 class="modal-title fs-6 fw-bold text-dark d-flex align-items-center gap-2" id="swapRoomModalLabel">
                        <i class="fas fa-exchange-alt text-primary"></i>
                        <span>{{ __('تبديل الغرفة مع مريض آخر') }}</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="small text-muted mb-1">{{ __('المريض الحالي:') }}</div>
                        <div class="fw-bold text-dark fs-6" id="swapCurrentPatientName">-</div>
                        <div class="small text-muted mt-1">
                            {{ __('الغرفة الحالية:') }}
                            <span class="badge bg-primary text-white font-monospace px-2 py-1 ms-1" id="swapCurrentRoomNumber">-</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="swapTargetSurgerySelect" class="form-label fw-bold text-dark small">
                            <i class="fas fa-bed text-primary me-1"></i>{{ __('اختر المريض / الغرفة المراد التبديل معها:') }}
                        </label>
                        <select name="target_surgery_id" id="swapTargetSurgerySelect" class="form-select form-select-sm" required>
                            <option value="">{{ __('-- اختر المريض أو الغرفة --') }}</option>
                        </select>
                        <div class="form-text text-muted small mt-1">
                            <i class="fas fa-info-circle me-1 text-info"></i>
                            {{ __('سيتم نقل المريض الحالي إلى الغرفة المختارة، ونقل المريض الآخر إلى هذه الغرفة آلياً وبشكل فوري.') }}
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-2.5">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">
                        {{ __('إلغاء') }}
                    </button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold">
                        <i class="fas fa-exchange-alt me-1"></i> {{ __('تأكيد التبديل المباشر') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.confirmCancelSurgery = function(event, route, title) {
        if (event) event.preventDefault();
        const message = @js(__('هل أنت متأكد من إلغاء العملية الجراحية للمريض:')) + ' ' + title + '؟';
        if (!window.confirm(message)) {
            return false;
        }

        const reason = window.prompt(@js(__('يرجى إدخال سبب إلغاء العملية (اختياري):')), '');
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

    function switchSurgeryTab(tabName) {
        const btnActive = document.getElementById('btnTabActive');
        const btnCompleted = document.getElementById('btnTabCompleted');
        const btnDischarged = document.getElementById('btnTabDischarged');
        const btnCancelled = document.getElementById('btnTabCancelled');
        const btnOrStore = document.getElementById('btnTabOrStore');

        const paneActive = document.getElementById('paneActiveSurgeries');
        const paneCompleted = document.getElementById('paneCompletedSurgeries');
        const paneDischarged = document.getElementById('paneDischargedSurgeries');
        const paneCancelled = document.getElementById('paneCancelledSurgeries');
        const paneOrStore = document.getElementById('paneOrStore');
        const counterLabel = document.getElementById('tabCounterLabel');

        [btnActive, btnCompleted, btnDischarged, btnCancelled, btnOrStore].forEach(btn => btn && btn.classList.remove('active'));
        [paneActive, paneCompleted, paneDischarged, paneCancelled, paneOrStore].forEach(pane => {
            if (pane) pane.style.display = 'none';
        });

        if (tabName === 'completed') {
            btnCompleted && btnCompleted.classList.add('active');
            if (paneCompleted) paneCompleted.style.display = 'block';
            if (counterLabel) counterLabel.textContent = 'عرض العمليات المكتملة في صالة العمليات (نزلاء الردهة قيد المتابعة)';
        } else if (tabName === 'discharged') {
            btnDischarged && btnDischarged.classList.add('active');
            if (paneDischarged) paneDischarged.style.display = 'block';
            if (counterLabel) counterLabel.textContent = 'أرشيف وسجل المرضى الذين تم تخريجهم نهائياً من المستشفى';
        } else if (tabName === 'cancelled') {
            btnCancelled && btnCancelled.classList.add('active');
            if (paneCancelled) paneCancelled.style.display = 'block';
            if (counterLabel) counterLabel.textContent = 'سجل العمليات الملغاة مع إمكانية التراجع والاستعادة';
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
                countBadge.textContent = visibleCount + ' ' + @json(__('مادة'));
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

                    const newDischarged = doc.getElementById('paneDischargedSurgeries');
                    const currDischarged = document.getElementById('paneDischargedSurgeries');
                    if (newDischarged && currDischarged && newDischarged.innerHTML.trim() !== currDischarged.innerHTML.trim()) {
                        currDischarged.innerHTML = newDischarged.innerHTML;
                    }

                    const newCancelled = doc.getElementById('paneCancelledSurgeries');
                    const currCancelled = document.getElementById('paneCancelledSurgeries');
                    if (newCancelled && currCancelled && newCancelled.innerHTML.trim() !== currCancelled.innerHTML.trim()) {
                        currCancelled.innerHTML = newCancelled.innerHTML;
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

                    const newTabBtnDisch = doc.getElementById('btnTabDischarged');
                    const currTabBtnDisch = document.getElementById('btnTabDischarged');
                    if (newTabBtnDisch && currTabBtnDisch) {
                        currTabBtnDisch.innerHTML = newTabBtnDisch.innerHTML;
                    }

                    const newTabBtnCanc = doc.getElementById('btnTabCancelled');
                    const currTabBtnCanc = document.getElementById('btnTabCancelled');
                    if (newTabBtnCanc && currTabBtnCanc) {
                        currTabBtnCanc.innerHTML = newTabBtnCanc.innerHTML;
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

    window.occupiedSurgeriesForSwap = {!! json_encode(collect($occupiedSurgeriesForSwap ?? [])->map(function($s) {
        return [
            'id' => $s->id,
            'patient_name' => $s->patient?->user?->name ?? $s->patient?->name ?? __('غير محدد'),
            'room_number' => $s->room?->room_number ?? '',
            'room_type' => strtoupper($s->room?->room_type ?? 'عادية'),
            'surgery_type' => $s->surgery_type ?? '',
        ];
    })->values(), JSON_UNESCAPED_UNICODE) !!};

    function openSwapRoomModal(currentSurgeryId, currentPatientName, currentRoomNumber) {
        const form = document.getElementById('swapRoomForm');
        const pNameEl = document.getElementById('swapCurrentPatientName');
        const rNumEl = document.getElementById('swapCurrentRoomNumber');
        const selectEl = document.getElementById('swapTargetSurgerySelect');

        if (!form || !selectEl) return;

        form.action = '/surgeries/' + currentSurgeryId + '/swap-room';
        if (pNameEl) pNameEl.textContent = currentPatientName;
        if (rNumEl) rNumEl.textContent = currentRoomNumber;

        selectEl.innerHTML = '<option value="">-- ' + '{{ __("اختر المريض أو الغرفة للتبديل") }}' + ' --</option>';

        const list = window.occupiedSurgeriesForSwap || [];
        let count = 0;
        list.forEach(item => {
            if (Number(item.id) !== Number(currentSurgeryId)) {
                count++;
                const opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = 'غرفة ' + item.room_number + ' (' + item.room_type + ') — ' + item.patient_name + (item.surgery_type ? ' [' + item.surgery_type + ']' : '');
                selectEl.appendChild(opt);
            }
        });

        if (count === 0) {
            selectEl.innerHTML = '<option value="">' + '{{ __("لا يوجد مرضى آخرون في غرف حالياً للتبديل معهم") }}' + '</option>';
        }

        const modal = new bootstrap.Modal(document.getElementById('swapRoomModal'));
        modal.show();
    }
    window.openSwapRoomModal = openSwapRoomModal;
</script>
@endsection
