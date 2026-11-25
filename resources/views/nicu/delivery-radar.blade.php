@extends('layouts.app')

@section('title', 'رادار صالات العمليات والولادة - قسم الخدج')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="nicuDeliveryRadarApp">
    {{-- CSS Styles --}}
    <style>
        :root {
            --radar-crimson: #c81e3a;
            --radar-navy: #1b4958;
            --radar-bg-soft: #fbfcfe;
        }

        .radar-canvas-card {
            background-color: #ffffff;
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 4px 20px -2px rgba(27, 73, 88, 0.07);
            border: 1px solid #e2e8f0;
        }

        /* Weekday navigation tabs */
        .week-nav-scroll {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 6px;
            scrollbar-width: thin;
        }
        .week-day-pill {
            flex: 1;
            min-width: 125px;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 10px 14px;
            text-align: center;
            text-decoration: none;
            color: #475569;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .week-day-pill:hover {
            transform: translateY(-2px);
            border-color: var(--radar-navy);
            color: var(--radar-navy);
            box-shadow: 0 4px 12px rgba(27, 73, 88, 0.08);
        }
        .week-day-pill.active {
            background: linear-gradient(135deg, #1b4958, #2a738a);
            border-color: #1b4958;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(27, 73, 88, 0.25);
        }
        .week-day-pill.active .badge-count {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }
        .week-day-pill .badge-count {
            background: #f1f5f9;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            margin-top: 4px;
        }

        /* Pulse animation for active surgery */
        @keyframes radarPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.6);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }
        .animate-radar-pulse {
            animation: radarPulse 1.8s infinite;
        }

        /* Table styling */
        .radar-master-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .radar-master-table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 14px 16px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        .radar-master-table tbody tr {
            transition: all 0.15s ease;
        }
        .radar-master-table tbody tr:hover {
            background-color: #f8fafc;
        }
        .radar-master-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .theater-highlight-box {
            background: #f0fdf4;
            border: 1.5px solid #86efac;
            color: #166534;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.92rem;
        }
        .theater-pending-box {
            background: #fefce8;
            border: 1.5px dashed #facc15;
            color: #854d0e;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.88rem;
        }
    </style>

    {{-- رأس الصفحة وأزرار التحكم --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="p-2 rounded-3 bg-danger bg-opacity-10 text-danger d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fas fa-satellite-dish fa-lg animate-radar-pulse"></i>
                </span>
                <div>
                    <h3 class="mb-0 fw-bold text-dark">
                        رادار صالات العمليات والولادة (NICU Deliveries Radar)
                    </h3>
                    <p class="text-muted small mb-0">المتابعة اللحظية المباشرة لعمليات الولادة والقيصريات في الصالات لتجهيز الحواضن والاستعداد السريري</p>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            @if($canManageObservations ?? false)
                <button type="button" class="btn btn-danger shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#manualObservationModal">
                    <i class="fas fa-baby-carriage me-1"></i>
                    بدء معاينة ولادة طبيعية / خارج الرادار
                </button>
            @endif
            @if($canManageSettings)
                <a href="{{ route('nicu.delivery-radar.settings') }}" class="btn btn-outline-dark shadow-sm rounded-3">
                    <i class="fas fa-sliders-h me-1 text-primary"></i>
                    ضبط أنواع العمليات وإعدادات المعاينة
                </a>
            @endif
            <a href="{{ route('nicu.index') }}" class="btn btn-outline-primary shadow-sm rounded-3">
                <i class="fas fa-baby me-1"></i>
                لوحة إشغال الخدج
            </a>
            <a href="{{ route('surgeries.waiting') }}" class="btn btn-outline-secondary shadow-sm rounded-3">
                <i class="fas fa-calendar-check me-1"></i>
                جدول العمليات العام
            </a>
        </div>
    </div>

    {{-- التنبيهات --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- كروت الملخص السريع (KPI Cards) --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">إجمالي العمليات المشمولة</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $stats['total'] }}</h3>
                        <small class="text-muted">ليوم {{ $parsedDate->format('Y-m-d') }}</small>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3">
                        <i class="fas fa-baby-carriage fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-danger border-4">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-danger small fw-bold d-block mb-1">
                            <i class="fas fa-circle text-danger me-1 small animate-radar-pulse"></i> جارية الآن بالصالات
                        </span>
                        <h3 class="fw-bold mb-0 text-danger">{{ $stats['in_progress'] }}</h3>
                        <small class="text-danger fw-semibold">تحضير الحاضنة مطلوب فوراً!</small>
                    </div>
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3">
                        <i class="fas fa-heartbeat fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-warning small fw-bold d-block mb-1">بالانتظار والتحضير</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $stats['waiting'] }}</h3>
                        <small class="text-muted">المريضة دخلت قسم العمليات</small>
                    </div>
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3">
                        <i class="fas fa-user-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border-start border-success border-4">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-success small fw-bold d-block mb-1">معاينات الساعتين النشطة</span>
                        <h3 class="fw-bold mb-0 text-success">{{ $stats['observing'] ?? 0 }}</h3>
                        <small class="text-muted">مجدولة: {{ $stats['scheduled'] }} | منتهية: {{ $stats['completed'] }}</small>
                    </div>
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-3">
                        <i class="fas fa-stopwatch fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ألسنة التبويب الرئيسية: 1- رادار العمليات | 2- المعاينات الحية (ساعتان) | 3- الأرشيف والتسليم --}}
    @php
        $initialTab = request('tab', ($archiveSearch ?? '') !== '' ? 'archive' : 'radar');
    @endphp
    <ul class="nav nav-pills mb-3 gap-2" id="radarHubTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-bold px-4 {{ $initialTab === 'radar' ? 'active' : '' }}" id="hub-radar-tab" data-bs-toggle="pill" data-bs-target="#hub-radar-pane" type="button" role="tab">
                <i class="fas fa-satellite-dish me-1"></i>
                رادار عمليات اليوم ({{ $surgeries->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-bold px-4 {{ $initialTab === 'observations' ? 'active' : '' }}" id="hub-obs-tab" data-bs-toggle="pill" data-bs-target="#hub-obs-pane" type="button" role="tab">
                <i class="fas fa-stopwatch me-1"></i>
                معاينات الوليد الحية ({{ ($observationSettings['duration_minutes'] ?? 120) }} دقيقة)
                <span class="badge bg-danger ms-1">{{ $activeObservations->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-3 fw-bold px-4 {{ $initialTab === 'archive' ? 'active' : '' }}" id="hub-archive-tab" data-bs-toggle="pill" data-bs-target="#hub-archive-pane" type="button" role="tab">
                <i class="fas fa-archive me-1"></i>
                أرشيف التسليم والتحويل ({{ $archiveObservations->count() }})
            </button>
        </li>
    </ul>

    <div class="tab-content" id="radarHubTabContent">
        {{-- التبويب 1: رادار العمليات --}}
        <div class="tab-pane fade {{ $initialTab === 'radar' ? 'show active' : '' }}" id="hub-radar-pane" role="tabpanel">
    {{-- كارت الحاوية الرئيسي للرادار --}}
    <div class="radar-canvas-card">
        {{-- شريط اختيار أيام الأسبوع وفلتر التاريخ --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom">
            <div class="week-nav-scroll flex-grow-1">
                @foreach($weekDays as $day)
                    <a href="{{ route('nicu.delivery-radar.index', ['date' => $day['date']]) }}" 
                       class="week-day-pill {{ $day['is_selected'] ? 'active' : '' }}">
                        <span class="fw-bold" style="font-size: 0.95rem;">{{ $day['day_name'] }}</span>
                        <span class="small opacity-75">{{ $day['formatted_date'] }}</span>
                        <span class="badge-count">
                            {{ $day['count'] }} {{ $day['count'] == 1 ? 'عملية' : ($day['count'] == 2 ? 'عمليتان' : 'عمليات') }}
                        </span>
                    </a>
                @endforeach
            </div>

            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <form action="{{ route('nicu.delivery-radar.index') }}" method="GET" class="d-flex align-items-center gap-2">
                    <input type="date" 
                           name="date" 
                           class="form-control form-control-sm rounded-3 shadow-none" 
                           value="{{ $selectedDate }}" 
                           onchange="this.form.submit()"
                           style="min-width: 140px;">
                    <button type="submit" class="btn btn-sm btn-light border rounded-3" title="الانتقال للتاريخ">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- شريط البحث والفلترة السريعة بالجافاسكريبت --}}
        <div class="row g-2 align-items-center mb-3">
            <div class="col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0 text-muted rounded-start-3">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           id="radarLiveSearch" 
                           class="form-control border-start-0 rounded-end-3 shadow-none" 
                           placeholder="بحث فوري باسم الأم، الصالة، الطبيبة، نوع العملية...">
                </div>
            </div>
            <div class="col-md-7 text-md-end text-muted small">
                <span class="me-3">
                    <i class="fas fa-info-circle text-info me-1"></i>
                    العمليات تظهر هنا تلقائياً وفق الأنواع المعتمدة في إعدادات الرادار
                </span>
                <span class="badge bg-light text-dark border px-2 py-1">
                    المعروض: <span id="visibleSurgeriesCount" class="fw-bold text-primary">{{ $surgeries->count() }}</span> حالة
                </span>
            </div>
        </div>

        {{-- جدول الرادار الرئيسي --}}
        <div class="table-responsive">
            <table class="radar-master-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="min-width: 160px;"><i class="fas fa-door-open me-1 text-primary"></i> صالة العمليات (OR Room)</th>
                        <th style="min-width: 130px;"><i class="fas fa-heartbeat me-1 text-danger"></i> حالة وسير العملية</th>
                        <th style="min-width: 110px;"><i class="fas fa-clock me-1 text-muted"></i> التوقيت</th>
                        <th style="min-width: 220px;"><i class="fas fa-female me-1 text-info"></i> بيانات الأم (المريضة)</th>
                        <th style="min-width: 200px;"><i class="fas fa-procedures me-1 text-secondary"></i> نوع العملية المجدولة</th>
                        <th style="min-width: 180px;"><i class="fas fa-user-md me-1 text-success"></i> الكادر المشرف (توليد / تخدير)</th>
                        <th style="min-width: 110px;"><i class="fas fa-bed me-1 text-muted"></i> الغرفة / الردهة</th>
                        <th style="min-width: 170px;"><i class="fas fa-baby me-1 text-danger"></i> معاينة الوليد (ساعتان)</th>
                    </tr>
                </thead>
                <tbody id="radarSurgeriesTableBody">
                    @forelse($surgeries as $surgery)
                        @php
                            $patientName = $surgery->patient->user->name ?? $surgery->patient->name ?? 'مريضة غير مسجلة';
                            $fileNumber = $surgery->patient->file_number ?? '-';
                            $phone = $surgery->patient->user->phone ?? $surgery->patient->phone ?? '-';
                            $age = $surgery->patient->age ?? ($surgery->patient->date_of_birth ? \Carbon\Carbon::parse($surgery->patient->date_of_birth)->age . ' سنة' : '-');
                            $doctorName = $surgery->doctor->user->name ?? $surgery->surgeon_name ?? 'غير محدد';
                            $anesthesiaName = $surgery->anesthesiologist->user->name ?? null;
                            $theaterName = $surgery->operationTheater->name ?? null;
                            $rawTime = $surgery->scheduled_time ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('h:i A') : 'غير محدد';
                            $operationType = $surgery->surgicalOperation->name ?? $surgery->surgery_type ?? 'عملية نسائية وتوليد';

                            // Search data index
                            $searchIndex = mb_strtolower(
                                $patientName . ' ' .
                                $fileNumber . ' ' .
                                $phone . ' ' .
                                $operationType . ' ' .
                                $doctorName . ' ' .
                                ($theaterName ?? '') . ' ' .
                                ($surgery->room->room_number ?? '')
                            );
                        @endphp
                        <tr class="radar-row {{ $surgery->status === 'in_progress' ? 'table-danger bg-opacity-25' : '' }}" 
                            data-search="{{ $searchIndex }}"
                            id="surgeryRow-{{ $surgery->id }}">
                            
                            {{-- ID --}}
                            <td>
                                <span class="badge bg-light text-muted border">#{{ $surgery->id }}</span>
                            </td>

                            {{-- صالة العمليات --}}
                            <td>
                                @if($theaterName)
                                    <div class="theater-highlight-box shadow-sm">
                                        <i class="fas fa-door-open fs-6"></i>
                                        <span>{{ $theaterName }}</span>
                                    </div>
                                @else
                                    <div class="theater-pending-box">
                                        <i class="fas fa-clock text-warning"></i>
                                        <span>بانتظار تحديد الصالة</span>
                                    </div>
                                @endif
                            </td>

                            {{-- حالة العملية --}}
                            <td>
                                @if($surgery->status === 'in_progress')
                                    <span class="badge bg-danger text-white px-3 py-2 rounded-pill shadow-sm animate-radar-pulse d-inline-flex align-items-center gap-1">
                                        <i class="fas fa-spinner fa-spin"></i>
                                        <span>جارية الآن داخل الصالة</span>
                                    </span>
                                @elseif($surgery->status === 'waiting')
                                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm d-inline-flex align-items-center gap-1">
                                        <i class="fas fa-user-clock"></i>
                                        <span>بالانتظار والتحضير</span>
                                    </span>
                                @elseif($surgery->status === 'scheduled')
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill d-inline-flex align-items-center gap-1">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>مجدولة</span>
                                    </span>
                                @elseif(in_array($surgery->status, ['completed', 'checked_in']))
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill d-inline-flex align-items-center gap-1">
                                        <i class="fas fa-check-circle"></i>
                                        <span>مكتملة / بالإفاقة</span>
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-25 text-dark px-2 py-1 rounded-pill">
                                        {{ $surgery->status }}
                                    </span>
                                @endif
                            </td>

                            {{-- التوقيت --}}
                            <td>
                                <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                                    <i class="far fa-clock text-muted me-1 small"></i>
                                    {{ $rawTime }}
                                </div>
                                <small class="text-muted d-block">{{ \Carbon\Carbon::parse($surgery->scheduled_date)->format('Y-m-d') }}</small>
                            </td>

                            {{-- بيانات الأم --}}
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="p-2 bg-info bg-opacity-10 text-info rounded-circle me-2 flex-shrink-0" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-female fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                                            {{ $patientName }}
                                        </div>
                                        <div class="small text-muted">
                                            <span>إضبارة: <strong>{{ $fileNumber }}</strong></span>
                                            @if($age && $age !== '-')
                                                <span class="mx-1">•</span>
                                                <span>{{ $age }}</span>
                                            @endif
                                            @if($phone && $phone !== '-')
                                                <span class="mx-1">•</span>
                                                <span><i class="fas fa-phone-alt small text-secondary"></i> {{ $phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- نوع العملية --}}
                            <td>
                                <div class="fw-bold text-primary mb-1">
                                    <i class="fas fa-baby text-danger me-1"></i>
                                    {{ $operationType }}
                                </div>
                                @if($surgery->additionalOperations->isNotEmpty())
                                    <div class="small text-muted">
                                        <span class="badge bg-light text-secondary border">عمليات مرافقة:</span>
                                        @foreach($surgery->additionalOperations as $addOp)
                                            <span class="text-dark">{{ $addOp->surgicalOperation->name ?? '' }}</span>{{ !$loop->last ? '، ' : '' }}
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            {{-- الكادر المشرف --}}
                            <td>
                                <div class="fw-bold text-dark small mb-1">
                                    <i class="fas fa-user-md text-success me-1"></i>
                                    طبيبة التوليد: {{ $doctorName }}
                                </div>
                                @if($anesthesiaName)
                                    <div class="text-muted small">
                                        <i class="fas fa-syringe text-secondary me-1"></i>
                                        تخدير: {{ $anesthesiaName }}
                                    </div>
                                @endif
                            </td>

                            {{-- الغرفة / الردهة --}}
                            <td>
                                @if($surgery->room)
                                    <span class="badge bg-light text-dark border px-2 py-1 rounded-2">
                                        <i class="fas fa-bed text-muted me-1"></i>
                                        غرفة {{ $surgery->room->room_number }}
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>

                            {{-- معاينة المولود (ساعتان) --}}
                            <td>
                                @if($surgery->nicuDeliveryObservation)
                                    @php $obs = $surgery->nicuDeliveryObservation; @endphp
                                    <div class="d-flex flex-column gap-1">
                                        <span class="badge {{ $obs->status_badge_class }} rounded-pill">{{ $obs->status_label }}</span>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('nicu.observations.show', $obs) }}" class="btn btn-xs btn-outline-primary rounded-2 px-2 py-1 small fw-bold">
                                                <i class="fas fa-folder-open me-1"></i> ملف المعاينة
                                            </a>
                                            <a href="{{ route('nicu.observations.print', $obs) }}" target="_blank" class="btn btn-xs btn-outline-dark rounded-2 px-2 py-1 small" title="طباعة استمارة A4">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </div>
                                @elseif($canManageObservations ?? false)
                                    <form action="{{ route('nicu.observations.from-surgery', $surgery) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger rounded-3 shadow-sm fw-bold">
                                            <i class="fas fa-stopwatch me-1"></i>
                                            بدء معاينة ساعتين
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyTableRow">
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="fas fa-baby-carriage fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h5 class="fw-bold text-dark mb-1">لا توجد عمليات قيصرية أو ولادات مجدولة لهذا اليوم</h5>
                                    <p class="text-muted small mb-0">يمكنك استخدام شريط الأيام بالأعلى للتنقل واستعراض العمليات المجدولة في الأيام القادمة.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    <tr id="noResultsRow" style="display: none;">
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-search fa-3x mb-3 d-block text-secondary opacity-50"></i>
                            <h6 class="fw-bold text-dark">لا توجد عمليات تطابق نص البحث المُدخل</h6>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
        </div>

        {{-- التبويب 2: لوحة المعاينات الحية (ساعتان) --}}
        <div class="tab-pane fade {{ $initialTab === 'observations' ? 'show active' : '' }}" id="hub-obs-pane" role="tabpanel">
            <div class="radar-canvas-card">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-2 border-bottom">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="fas fa-stopwatch text-danger me-2"></i>
                            المواليد تحت المعاينة السريرية الحالية ({{ $observationSettings['duration_minutes'] ?? 120 }} دقيقة)
                        </h5>
                        <small class="text-muted">يتحول المؤقت إلى اللون الأخضر تلقائياً عند إتمام المدة المحددة لتوثيق التسليم للأهل أو التحويل لحاضنة NICU</small>
                    </div>
                    @if($canManageObservations ?? false)
                        <button type="button" class="btn btn-sm btn-danger rounded-3 fw-bold" data-bs-toggle="modal" data-bs-target="#manualObservationModal">
                            <i class="fas fa-plus me-1"></i> إضافة ولادة طبيعية / خارج الصالة
                        </button>
                    @endif
                </div>

                @if($activeObservations->isEmpty())
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-baby fa-3x mb-3 opacity-50"></i>
                        <h6 class="fw-bold text-dark">لا توجد حالات تحت معاينة الساعتين حالياً</h6>
                        <p class="small mb-0">يمكنك بدء المعاينة مباشرة من تبويب «رادار عمليات اليوم» أو إضافة حالة ولادة طبيعية يدوياً.</p>
                    </div>
                @else
                    <div class="row g-3">
                        @foreach($activeObservations as $obs)
                            <div class="col-md-6 col-xl-4">
                                <div class="card h-100 border shadow-sm rounded-4 overflow-hidden">
                                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2 px-3">
                                        <span class="badge {{ $obs->status_badge_class }} rounded-pill">{{ $obs->status_label }}</span>
                                        <div class="live-obs-timer badge px-3 py-2 font-monospace {{ $obs->isDurationCompleted() ? 'bg-success' : 'bg-warning text-dark' }}"
                                             data-started-at="{{ $obs->started_at?->toIso8601String() }}"
                                             data-target-minutes="{{ $obs->target_duration_minutes }}">
                                            {{ str_pad(intdiv($obs->elapsed_minutes, 60), 2, '0', STR_PAD_LEFT) }}:{{ str_pad($obs->elapsed_minutes % 60, 2, '0', STR_PAD_LEFT) }} / {{ $obs->target_duration_minutes }}m
                                        </div>
                                    </div>
                                    <div class="card-body p-3">
                                        <h6 class="fw-bold text-dark mb-1">{{ $obs->mother_name }}</h6>
                                        <div class="small text-muted mb-2">
                                            <span>{{ $obs->operative_description ?? '-' }}</span>
                                            <span class="mx-1">•</span>
                                            <span>{{ $obs->case_of_delivery }} ({{ $obs->no_of_borns }})</span>
                                        </div>
                                        <div class="d-flex flex-column gap-1 mb-3">
                                            @foreach($obs->babies as $b)
                                                <div class="p-2 rounded-3 bg-light border small d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong>{{ $b->born_name }}</strong>
                                                        <span class="text-muted">({{ $b->gender ?? '-' }}{{ $b->weight_kg ? ' | ' . $b->weight_kg . ' Kg' : '' }})</span>
                                                    </div>
                                                    <span class="badge bg-white text-dark border">{{ $b->disposition_label }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('nicu.observations.show', $obs) }}" class="btn btn-sm btn-primary flex-grow-1 rounded-3 fw-bold">
                                                <i class="fas fa-folder-open me-1"></i> فتح الملف والتسليم
                                            </a>
                                            <a href="{{ route('nicu.observations.print', $obs) }}" target="_blank" class="btn btn-sm btn-outline-dark rounded-3" title="طباعة A4">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- التبويب 3: أرشيف التسليم والتحويل والبحث --}}
        <div class="tab-pane fade {{ $initialTab === 'archive' ? 'show active' : '' }}" id="hub-archive-pane" role="tabpanel">
            <div class="radar-canvas-card">
                <form action="{{ route('nicu.delivery-radar.index') }}" method="GET" class="row g-2 align-items-center mb-3">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">
                    <input type="hidden" name="tab" value="archive">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="archive_search" class="form-control" value="{{ $archiveSearch ?? '' }}" placeholder="ابحث باسم الأم، المولود، المستلم (الأب)، رقم الهوية، أو الهاتف...">
                            <button type="submit" class="btn btn-primary px-3">بحث بالأرشيف</button>
                        </div>
                    </div>
                    @if(($archiveSearch ?? '') !== '')
                        <div class="col-auto">
                            <a href="{{ route('nicu.delivery-radar.index', ['date' => $selectedDate, 'tab' => 'archive']) }}" class="btn btn-sm btn-outline-secondary">مسح البحث</a>
                        </div>
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>#</th>
                                <th>تاريخ الولادة</th>
                                <th>الأم والمصدر</th>
                                <th>المواليد والحسم</th>
                                <th>بيانات المستلم (الأهل)</th>
                                <th>الورقة المؤرشفة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($archiveObservations as $arch)
                                <tr>
                                    <td><span class="badge bg-light text-muted border">#{{ $arch->id }}</span></td>
                                    <td class="small">{{ $arch->delivery_date?->format('Y-m-d') ?? $arch->started_at?->format('Y-m-d') }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $arch->mother_name }}</div>
                                        <small class="text-muted">{{ $arch->operative_description ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge {{ $arch->status_badge_class }} rounded-pill mb-1">{{ $arch->status_label }}</span>
                                        <div class="small text-muted">
                                            {{ $arch->babies->pluck('born_name')->implode('، ') }}
                                        </div>
                                    </td>
                                    <td class="small">
                                        @if($arch->recipient_name)
                                            <div class="fw-bold">{{ $arch->recipient_name }} ({{ $arch->recipient_relation ?? 'الأب' }})</div>
                                            <div class="text-muted">{{ $arch->recipient_phone }} | هوية: {{ $arch->recipient_id_number }}</div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($arch->signed_form_path)
                                            <a href="{{ route('nicu.observations.signed-form.download', $arch) }}" target="_blank" class="badge bg-success text-decoration-none">
                                                <i class="fas fa-file-check me-1"></i> محفوظة
                                            </a>
                                        @else
                                            <span class="badge bg-light text-muted border">غير مرفوعة</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="{{ route('nicu.observations.show', $arch) }}" class="btn btn-xs btn-outline-primary rounded-2 px-2 py-1 small">فتح</a>
                                            <a href="{{ route('nicu.observations.print', $arch) }}" target="_blank" class="btn btn-xs btn-outline-dark rounded-2 px-2 py-1 small" title="طباعة A4">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">لا توجد سجلات معاينة مطابقة في الأرشيف.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- مودال فتح معاينة ولادة طبيعية / حالة خارج رادار العمليات --}}
@if($canManageObservations ?? false)
<div class="modal fade" id="manualObservationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-danger text-white rounded-top-4">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-baby-carriage me-2"></i>
                    بدء معاينة (ساعتان) لولادة طبيعية / حالة غير مدرجة بالعمليات
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('nicu.observations.store-manual') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">اسم الأم الكامل <span class="text-danger">*</span></label>
                            <input type="text" name="mother_name" class="form-control" required placeholder="مثال: رسل باسل أحمد">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">نوع الولادة / الوصف</label>
                            <input type="text" name="operative_description" class="form-control" value="ولادة طبيعية" placeholder="ولادة طبيعية / قيصرية طارئة">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">تاريخ الولادة</label>
                            <input type="date" name="delivery_date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">وقت الولادة</label>
                            <input type="text" name="delivery_time" class="form-control" value="{{ now()->format('h:i a') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">عدد المواليد</label>
                            <input type="number" name="no_of_borns" min="1" max="6" class="form-control" value="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">جنس المولود الأول</label>
                            <select name="baby_gender" class="form-select">
                                <option value="">-- يحدد لاحقاً --</option>
                                <option value="Male">Male (ذكر)</option>
                                <option value="Female">Female (أنثى)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الوزن (Kg)</label>
                            <input type="number" step="0.01" min="0.2" max="10" name="baby_weight_kg" class="form-control" placeholder="3.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الطبيبة المشرفة / القابلة</label>
                            <input type="text" name="requesting_doctor_name" class="form-control" placeholder="اسم الطبيبة">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">ملاحظة الولادة (Delivery Note)</label>
                            <textarea name="delivery_note" rows="2" class="form-control" placeholder="ملاحظات أولية عن الولادة وحالة الوليد..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger rounded-3 fw-bold px-4">
                        <i class="fas fa-stopwatch me-1"></i> فتح ملف المعاينة وبدء المؤقت
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- سكريبت البحث المباشر ومؤقتات المعاينة الحية --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('radarLiveSearch');
    const tableBody = document.getElementById('radarSurgeriesTableBody');
    const rows = tableBody ? tableBody.querySelectorAll('.radar-row') : [];
    const countSpan = document.getElementById('visibleSurgeriesCount');
    const noResultsRow = document.getElementById('noResultsRow');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                if (!query || searchData.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countSpan) {
                countSpan.textContent = visibleCount;
            }

            if (noResultsRow) {
                if (visibleCount === 0 && rows.length > 0) {
                    noResultsRow.style.display = '';
                } else {
                    noResultsRow.style.display = 'none';
                }
            }
        });
    }

    // تحديث مؤقتات لوحة المعاينات الحية كل ثانية
    const liveTimers = document.querySelectorAll('.live-obs-timer');
    if (liveTimers.length > 0) {
        const refreshTimers = () => {
            const now = new Date();
            liveTimers.forEach(el => {
                const startedAt = new Date(el.dataset.startedAt);
                const targetMins = parseInt(el.dataset.targetMinutes || '120', 10);
                const elapsedMins = Math.max(0, Math.floor((now - startedAt) / 60000));
                const hrs = String(Math.floor(elapsedMins / 60)).padStart(2, '0');
                const mins = String(elapsedMins % 60).padStart(2, '0');
                el.textContent = `${hrs}:${mins} / ${targetMins}m`;
                if (elapsedMins >= targetMins) {
                    el.classList.remove('bg-warning', 'text-dark');
                    el.classList.add('bg-success', 'text-white');
                }
            });
        };
        refreshTimers();
        setInterval(refreshTimers, 15000);
    }
});
</script>
@endsection
