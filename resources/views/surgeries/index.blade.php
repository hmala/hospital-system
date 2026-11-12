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
            padding: 12px 14px;
            border-bottom: 1px solid var(--sg-border);
            white-space: nowrap;
            text-align: right;
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
                                <th style="width: 50px;">#</th>
                                <th>المريض</th>
                                <th>نوع العملية</th>
                                <th>الطبيب الجراح</th>
                                <th>تاريخ وموعد العملية</th>
                                <th>الغرفة</th>
                                <th>حالة العملية</th>
                                @if(!auth()->user()->hasRole(['receptionist', 'inquiry_staff']))
                                    <th>تشخيص / علاج</th>
                                @endif
                                <th class="text-center" style="width: 140px;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeSurgeries as $surgery)
                            @php
                                $patientName = optional(optional($surgery->patient)->user)->name ?? 'غير محدد';
                                $phone = optional(optional($surgery->patient)->user)->phone;
                                $room = $surgery->room;
                                $rtype = $room ? $room->room_type : null;
                                $canManageSurgery = auth()->check() && (auth()->user()->hasRole(['surgery_staff', 'admin']) || 
                                                   (auth()->user()->isDoctor() && auth()->user()->doctor && auth()->user()->doctor->id == $surgery->doctor_id));
                            @endphp
                            <tr>
                                <td class="text-muted fw-bold">#{{ $surgery->id }}</td>
                                <td>
                                    <button type="button" class="patient-title-btn" data-bs-toggle="collapse" data-bs-target="#detailsRow{{ $surgery->id }}" aria-expanded="false" aria-controls="detailsRow{{ $surgery->id }}">
                                        <i class="fas fa-user-circle text-primary fs-5"></i>
                                        <span>{{ $patientName }}</span>
                                    </button>
                                    @if($phone)
                                        <div class="small text-muted ps-4 mt-1" style="font-size:0.75rem;">
                                            <i class="fas fa-phone-alt me-1"></i>{{ $phone }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $surgery->surgery_type }}</div>
                                    <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                        @if($surgery->surgicalOperation && $surgery->surgicalOperation->category)
                                            <small class="text-muted">{{ $surgery->surgicalOperation->category }}</small>
                                        @endif
                                        @if($surgery->insurance_type && $surgery->insurance_type !== 'none')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-2 rounded-pill" style="font-size: 0.72rem;">
                                                <i class="fas fa-shield-alt me-1"></i>{{ $surgery->insurance_type === 'hi' ? 'ضمان صحي' : 'ضمان قوى الأمن' }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border py-0 px-2 rounded-pill" style="font-size: 0.72rem;">
                                                <i class="fas fa-money-bill-wave me-1"></i>نقدي (كاش)
                                            </span>
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
                                    <div class="fw-bold text-dark font-monospace" style="font-size: 0.85rem;">
                                        <i class="fas fa-calendar-day text-secondary me-1"></i>
                                        {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}
                                    </div>
                                    <small class="text-muted font-monospace">
                                        <i class="fas fa-clock text-secondary me-1"></i>
                                        {{ is_object($surgery->scheduled_time) ? $surgery->scheduled_time->format('H:i') : substr($surgery->scheduled_time ?? '', 0, 5) }}
                                    </small>
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
                                <td>
                                    @if($surgery->status == 'scheduled')
                                        <span class="status-badge-pill status-scheduled"><i class="fas fa-calendar-check me-1"></i>مجدولة</span>
                                    @elseif($surgery->status == 'waiting')
                                        <span class="status-badge-pill status-waiting"><i class="fas fa-clock me-1"></i>في الانتظار</span>
                                    @elseif($surgery->status == 'in_progress')
                                        <span class="status-badge-pill status-in_progress"><span class="sg-pulse-dot me-1"></span>جارية</span>
                                    @elseif($surgery->status == 'completed')
                                        <span class="status-badge-pill status-completed"><i class="fas fa-check me-1"></i>مكتملة</span>
                                    @else
                                        <span class="status-badge-pill status-cancelled"><i class="fas fa-times me-1"></i>ملغاة</span>
                                    @endif
                                </td>
                                @if(!auth()->user()->hasRole(['receptionist', 'inquiry_staff']))
                                <td>
                                    <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-primary shadow-sm rounded-pill px-3" title="تفاصيل العملية">
                                        <i class="fas fa-eye me-1"></i> تفاصيل
                                    </a>
                                </td>
                                @endif
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center align-items-center">
                                        <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-primary px-2 rounded-2" title="تفاصيل العملية">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @can('edit surgeries')
                                            <a href="{{ route('surgeries.edit', $surgery) }}" class="btn btn-sm btn-outline-warning text-dark px-2 rounded-2" title="تعديل الحجز">
                                                <i class="fas fa-edit text-warning"></i>
                                            </a>
                                        @endcan

                                        <a href="{{ route('surgeries.print', $surgery) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2 rounded-2" title="طباعة الوصل">
                                            <i class="fas fa-print"></i>
                                        </a>

                                        @can('delete surgeries')
                                            <form action="{{ route('surgeries.destroy', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف وإلغاء حجز العملية نهائياً للمريض وتحرير الغرفة؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 rounded-2" title="حذف / إلغاء الحجز">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>

                            {{-- Expandable Results Row --}}
                            <tr class="bg-light">
                                <td colspan="{{ auth()->user()->hasRole(['receptionist', 'inquiry_staff']) ? '8' : '9' }}" class="p-0 border-0">
                                    <div class="collapse" id="detailsRow{{ $surgery->id }}">
                                        <div class="p-3 m-2 sg-collapse-box shadow-sm">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0 fw-bold text-dark">
                                                    <i class="fas fa-clipboard-list text-primary me-1"></i>
                                                    تفاصيل ونتائج العملية (#{{ $surgery->id }})
                                                </h6>
                                                <div class="d-flex gap-2">
                                                    @if($canManageSurgery && $surgery->status != 'cancelled' && $surgery->status != 'completed')
                                                        <button type="button" class="btn btn-sm btn-danger rounded-pill px-3" 
                                                                onclick="confirmCancelSurgery(event, '{{ route('surgeries.cancel', $surgery) }}', '{{ addslashes($surgery->surgery_type) }}')">
                                                            <i class="fas fa-times me-1"></i>إلغاء العملية
                                                        </button>
                                                    @endif
                                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" type="button" 
                                                            data-bs-toggle="collapse" data-bs-target="#detailsRow{{ $surgery->id }}">
                                                        إخفاء التفاصيل
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="row row-cols-1 row-cols-md-3 g-3">
                                                {{-- Radiology --}}
                                                <div class="col">
                                                    <div class="card h-100 shadow-sm border-0">
                                                        <div class="card-header bg-primary text-white py-2 fw-bold d-flex justify-content-between align-items-center">
                                                            <span><i class="fas fa-x-ray me-1"></i> الأشعة والسونار</span>
                                                            <span class="badge bg-light text-dark">{{ $surgery->radiologyTests->count() }}</span>
                                                        </div>
                                                        <div class="card-body p-2" style="font-size: 0.82rem;">
                                                            @if($surgery->radiologyTests->count())
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm table-bordered mb-0">
                                                                        <thead>
                                                                            <tr class="bg-light">
                                                                                <th>نوع الفحص</th>
                                                                                <th>الحالة</th>
                                                                                <th>النتيجة</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($surgery->radiologyTests as $test)
                                                                            <tr>
                                                                                <td>{{ $test->radiologyType->name ?? '-' }}</td>
                                                                                <td><span class="badge bg-light text-dark border">{{ $test->statusText ?? '-' }}</span></td>
                                                                                <td>
                                                                                    @if($test->result)
                                                                                        <div>{{ $test->result }}</div>
                                                                                    @endif
                                                                                    @if($test->result_file)
                                                                                        <a href="{{ asset('storage/' . $test->result_file) }}" target="_blank" class="btn btn-xs btn-outline-primary mt-1">
                                                                                            <i class="fas fa-paperclip"></i> مرفق
                                                                                        </a>
                                                                                    @elseif(!$test->result)
                                                                                        -
                                                                                    @endif
                                                                                </td>
                                                                            </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @else
                                                                <p class="mb-0 text-muted p-2">لا توجد طلبات أشعة مسجلة</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Lab Tests --}}
                                                <div class="col">
                                                    <div class="card h-100 shadow-sm border-0">
                                                        <div class="card-header bg-info text-white py-2 fw-bold d-flex justify-content-between align-items-center">
                                                            <span><i class="fas fa-flask me-1"></i> التحاليل المختبرية</span>
                                                            <span class="badge bg-light text-dark">{{ $surgery->labTests->count() }}</span>
                                                        </div>
                                                        <div class="card-body p-2" style="font-size: 0.82rem;">
                                                            @if($surgery->labTests->count())
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm table-bordered mb-0">
                                                                        <thead>
                                                                            <tr class="bg-light">
                                                                                <th>التحليل</th>
                                                                                <th>الحالة</th>
                                                                                <th>النتيجة</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($surgery->labTests as $test)
                                                                            <tr>
                                                                                <td>{{ $test->labTest->name ?? '-' }}</td>
                                                                                <td><span class="badge bg-light text-dark border">{{ $test->statusText ?? '-' }}</span></td>
                                                                                <td>
                                                                                    @if($test->result)
                                                                                        <div>{{ $test->result }}</div>
                                                                                    @endif
                                                                                    @if($test->result_file)
                                                                                        <a href="{{ asset('storage/' . $test->result_file) }}" target="_blank" class="btn btn-xs btn-outline-primary mt-1">
                                                                                            <i class="fas fa-paperclip"></i> مرفق
                                                                                        </a>
                                                                                    @elseif(!$test->result)
                                                                                        -
                                                                                    @endif
                                                                                </td>
                                                                            </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @else
                                                                <p class="mb-0 text-muted p-2">لا توجد تحاليل مختبرية</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Treatments --}}
                                                <div class="col">
                                                    <div class="card h-100 shadow-sm border-0">
                                                        <div class="card-header bg-secondary text-white py-2 fw-bold d-flex justify-content-between align-items-center">
                                                            <span><i class="fas fa-pills me-1"></i> العلاجات والأدوية</span>
                                                            <span class="badge bg-light text-dark">{{ $surgery->surgeryTreatments->count() }}</span>
                                                        </div>
                                                        <div class="card-body p-2" style="font-size: 0.82rem;">
                                                            @if($surgery->surgeryTreatments->count())
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm table-bordered mb-0">
                                                                        <thead>
                                                                            <tr class="bg-light">
                                                                                <th>العلاج</th>
                                                                                <th>الجرعة والتكرار</th>
                                                                                <th>الحالة</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($surgery->surgeryTreatments as $tr)
                                                                            <tr>
                                                                                <td class="fw-bold">{{ $tr->treatment_name }}</td>
                                                                                <td>{{ $tr->dosage ?? '-' }} | {{ $tr->frequency ?? '-' }}</td>
                                                                                <td>
                                                                                    @if($tr->is_administered)
                                                                                        <span class="badge bg-success-subtle text-success">تم الإعطاء</span>
                                                                                    @else
                                                                                        <span class="badge bg-warning-subtle text-warning">قيد الانتظار</span>
                                                                                    @endif
                                                                                </td>
                                                                            </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @else
                                                                <p class="mb-0 text-muted p-2">لا توجد علاجات موصوفة</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ auth()->user()->hasRole(['receptionist', 'inquiry_staff']) ? '8' : '9' }}">
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
                                <th style="width: 50px;">#</th>
                                <th>المريض</th>
                                <th>نوع العملية</th>
                                <th>الطبيب الجراح</th>
                                <th>تاريخ وموعد العملية</th>
                                <th>الغرفة</th>
                                <th>حالة العملية</th>
                                <th>تاريخ الخروج</th>
                                <th class="text-center" style="width: 140px;">الإجراءات</th>
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
                            <tr>
                                <td class="text-muted fw-bold">#{{ $surgery->id }}</td>
                                <td>
                                    <button type="button" class="patient-title-btn" data-bs-toggle="collapse" data-bs-target="#detailsRowCompleted{{ $surgery->id }}" aria-expanded="false" aria-controls="detailsRowCompleted{{ $surgery->id }}">
                                        <i class="fas fa-user-circle text-secondary fs-5"></i>
                                        <span>{{ $patientName }}</span>
                                    </button>
                                    @if($phone)
                                        <div class="small text-muted ps-4 mt-1" style="font-size:0.75rem;">
                                            <i class="fas fa-phone-alt me-1"></i>{{ $phone }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $surgery->surgery_type }}</div>
                                    <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                        @if($surgery->insurance_type && $surgery->insurance_type !== 'none')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-2 rounded-pill" style="font-size: 0.72rem;">
                                                <i class="fas fa-shield-alt me-1"></i>{{ $surgery->insurance_type === 'hi' ? 'ضمان صحي' : 'ضمان قوى الأمن' }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary border py-0 px-2 rounded-pill" style="font-size: 0.72rem;">
                                                <i class="fas fa-money-bill-wave me-1"></i>نقدي (كاش)
                                            </span>
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
                                    <div class="fw-bold text-dark font-monospace" style="font-size: 0.85rem;">
                                        <i class="fas fa-calendar-day text-secondary me-1"></i>
                                        {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}
                                    </div>
                                    <small class="text-muted font-monospace">
                                        <i class="fas fa-clock text-secondary me-1"></i>
                                        {{ is_object($surgery->scheduled_time) ? $surgery->scheduled_time->format('H:i') : substr($surgery->scheduled_time ?? '', 0, 5) }}
                                    </small>
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
                                <td>
                                    @if($surgery->status == 'completed' || ($surgery->operationTheaterStation && $surgery->operationTheaterStation->status === 'completed'))
                                        <span class="status-badge-pill status-completed"><i class="fas fa-check-circle me-1"></i>مكتملة (خرجت من الصالة)</span>
                                    @else
                                        <span class="status-badge-pill status-cancelled"><i class="fas fa-times-circle me-1"></i>ملغاة</span>
                                    @endif
                                </td>
                                <td>
                                    @if($surgery->discharged_at)
                                        <span class="text-success fw-bold d-block font-monospace" style="font-size: 0.82rem;">
                                            <i class="fas fa-calendar-check me-1"></i>
                                            {{ $surgery->discharged_at->format('Y-m-d H:i') }}
                                        </span>
                                        @if($surgery->discharge_notes)
                                            <small class="text-info" title="{{ $surgery->discharge_notes }}">
                                                <i class="fas fa-sticky-note me-1"></i>ملاحظات
                                            </small>
                                        @endif
                                    @else
                                        <span class="badge bg-light text-secondary border">لم يخرج بعد</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center align-items-center">
                                        <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-primary px-2 rounded-2" title="تفاصيل العملية">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        @can('edit surgeries')
                                            <a href="{{ route('surgeries.edit', $surgery) }}" class="btn btn-sm btn-outline-warning text-dark px-2 rounded-2" title="تعديل الحجز">
                                                <i class="fas fa-edit text-warning"></i>
                                            </a>
                                        @endcan

                                        <a href="{{ route('surgeries.print', $surgery) }}" target="_blank" class="btn btn-sm btn-outline-secondary px-2 rounded-2" title="طباعة الوصل">
                                            <i class="fas fa-print"></i>
                                        </a>

                                        @can('delete surgeries')
                                            <form action="{{ route('surgeries.destroy', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف سجل هذه العملية نهائياً؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger px-2 rounded-2" title="حذف السجل">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>

                            {{-- Expandable Details for Completed --}}
                            <tr class="bg-light">
                                <td colspan="9" class="p-0 border-0">
                                    <div class="collapse" id="detailsRowCompleted{{ $surgery->id }}">
                                        <div class="p-3 m-2 sg-collapse-box shadow-sm">
                                            <h6 class="mb-2 fw-bold text-dark">
                                                <i class="fas fa-archive text-secondary me-1"></i>
                                                أرشيف تفاصيل العملية (#{{ $surgery->id }})
                                            </h6>
                                            <p class="small text-muted mb-0">
                                                الحالة النهائية: {{ $surgery->status === 'completed' ? 'مكتملة وخرجت من صالة العمليات' : 'ملغاة' }} 
                                                @if($surgery->cancellation_reason)
                                                    - سبب الإلغاء: <strong>{{ $surgery->cancellation_reason }}</strong>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9">
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

{{-- Hidden Form for Cancellation --}}
<form id="cancelSurgeryConfirmForm" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="cancellation_reason" id="cancel_reason_input" value="">
</form>

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
    });

    window.confirmCancelSurgery = function(event, route, title) {
        event.preventDefault();
        const message = 'هل أنت متأكد من إلغاء العملية: ' + title + '؟';
        if (!window.confirm(message)) {
            return false;
        }

        const reason = window.prompt('أدخل سبب الإلغاء (اختياري):', '');
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
