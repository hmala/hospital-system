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
                        قسم الأشعة والتصوير الطبي ومحطة السونار
                        @if($selectedCategory === 'ultrasound')
                            <span class="badge bg-info text-white fs-6 ms-2"><i class="fas fa-wave-square me-1"></i> السونار والدوبلر</span>
                        @elseif($selectedCategory === 'mri')
                            <span class="badge bg-purple text-white fs-6 ms-2" style="background-color: #6f42c1;"><i class="fas fa-magnet me-1"></i> الرنين والمفراس</span>
                        @elseif($selectedCategory === 'echo')
                            <span class="badge bg-danger text-white fs-6 ms-2"><i class="fas fa-heartbeat me-1"></i> إيكو القلب</span>
                        @elseif($selectedCategory === 'radiology')
                            <span class="badge bg-secondary text-white fs-6 ms-2"><i class="fas fa-film me-1"></i> الأشعة السينية</span>
                        @else
                            <span class="badge bg-primary text-white fs-6 ms-2"><i class="fas fa-hospital me-1"></i> المركز العام</span>
                        @endif
                    </h3>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle py-1 px-2">
                            <i class="fas fa-circle fa-xs me-1 text-success"></i> طابور مباشر متزامن
                        </span>
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

    <!-- 2. Calling Station Control Bar (مطابق لمحطة الطبيب الرئيسية) -->
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

    <!-- 3. Navigation Tabs (مطابق لمحطة الطبيب) -->
    <ul class="nav nav-tabs mb-3 border-bottom" id="radiologyTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center gap-2" id="station-tab" data-bs-toggle="tab" data-bs-target="#station-pane" type="button" role="tab">
                <i class="fas fa-bullhorn text-primary"></i>
                <span>محطة الفحص والمناداة الحية</span>
                <span class="badge bg-primary text-white rounded-pill px-2 py-1" id="badge-tab-waiting-count">{{ $stats['waiting'] }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="inprogress-tab" data-bs-toggle="tab" data-bs-target="#inprogress-pane" type="button" role="tab">
                <i class="fas fa-stethoscope text-info"></i>
                <span>قيد الفحص والتقرير</span>
                <span class="badge bg-info text-white rounded-pill px-2 py-1" id="badge-tab-inprogress-count">{{ $stats['in_progress'] }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-pane" type="button" role="tab">
                <i class="fas fa-check-circle text-success"></i>
                <span>المكتملة والطباعة</span>
                <span class="badge bg-success text-white rounded-pill px-2 py-1" id="badge-tab-completed-count">{{ $stats['completed'] }}</span>
            </button>
        </li>
        @if(($stats['emergency'] ?? 0) > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="emergency-tab" data-bs-toggle="tab" data-bs-target="#emergency-pane" type="button" role="tab">
                <i class="fas fa-ambulance text-danger"></i>
                <span>طوارئ</span>
                <span class="badge bg-danger text-white rounded-pill px-2 py-1">{{ $stats['emergency'] }}</span>
            </button>
        </li>
        @endif
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-pane" type="button" role="tab">
                <i class="fas fa-list text-secondary"></i>
                <span>جميع الطلبات والأرشيف</span>
                <span class="badge bg-secondary rounded-pill px-2 py-1">{{ $stats['total'] }}</span>
            </button>
        </li>
    </ul>

    <!-- 4. Tab Content Panels -->
    <div class="tab-content" id="radiologyTabsContent">
        
        <!-- 1. LIVE STATION TAB (طابور الانتظار الأولي والمناداة) -->
        <div class="tab-pane fade show active" id="station-pane" role="tabpanel">
            
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-users-line me-2 text-primary"></i>
                    طابور الانتظار الأولي (المرضى الجاهزون للفحص)
                </h5>
                <span class="badge bg-primary text-white" id="badge-live-waiting-count">{{ $stats['waiting'] }} منتظر</span>
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
                        @forelse($waitingRequests as $req)
                            @php
                                $isPaid = $req->payment_status === 'paid';
                                $isCalling = $req->status === 'calling';
                                $p = $req->visit?->patient;
                                $pName = $p?->name ?? $p?->user?->name ?? 'مريض';
                                $qNum = $req->visit?->appointment?->queue_number ?? ($turnNum++);
                            @endphp
                            <tr class="{{ $isCalling ? 'calling-row fw-bold' : '' }}" id="request-row-{{ $req->id }}">
                                <td>
                                    <span class="badge {{ $isCalling ? 'bg-warning text-dark' : 'bg-dark bg-opacity-10 text-dark border' }} px-2 py-1 font-monospace fs-6">
                                        #{{ $qNum }}
                                    </span>
                                </td>
                                <td class="font-monospace text-muted">#{{ $req->id }}</td>
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
                                    @if($isCalling)
                                        <span class="badge bg-warning text-dark border border-warning py-1 px-2 fw-bold"><i class="fas fa-bullhorn me-1"></i> قيد النداء</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary py-1 px-2">⏳ بالانتظار</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end align-items-center">
                                        @if($isPaid)
                                            <button type="button" class="action-btn btn-warning text-dark fw-bold shadow-xs" onclick="radiologyCallSpecific({{ $req->id }}, '{{ addslashes($pName) }}')" title="مناداة واستدعاء للغرفة">
                                                <i class="fas fa-bullhorn"></i> نداء
                                            </button>
                                            <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-primary fw-bold shadow-xs" title="إدخال للغرفة وبدء الفحص">
                                                <i class="fas fa-door-open"></i> إدخال
                                            </a>
                                        @else
                                            <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-outline-secondary" title="معاينة الطلب">
                                                <i class="fas fa-eye"></i> معاينة
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle text-success me-1"></i> لا يوجد مرضى في طابور الانتظار حالياً
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

        <!-- 2. IN PROGRESS TAB (قيد الفحص والتقرير) -->
        <div class="tab-pane fade" id="inprogress-pane" role="tabpanel">
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
                                $p = $req->visit?->patient;
                                $pName = $p?->name ?? $p?->user?->name ?? 'مريض';
                                $qNum = $req->visit?->appointment?->queue_number ?? $req->id;
                            @endphp
                            <tr class="inprogress-row">
                                <td><span class="badge bg-info text-white font-monospace fs-6">#{{ $qNum }}</span></td>
                                <td><strong class="text-dark">{{ $pName }}</strong></td>
                                <td>{{ implode(', ', $req->radiology_names) ?: ($req->description ?: 'فحص تصوير') }}</td>
                                <td>د. {{ $req->visit?->doctor?->user?->name ?? 'الاستشارية' }}</td>
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
        <div class="tab-pane fade" id="completed-pane" role="tabpanel">
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
                                $p = $req->visit?->patient;
                                $pName = $p?->name ?? $p?->user?->name ?? 'مريض';
                                $qNum = $req->visit?->appointment?->queue_number ?? $req->id;
                            @endphp
                            <tr class="completed-row">
                                <td><span class="badge bg-success text-white font-monospace fs-6">#{{ $qNum }}</span></td>
                                <td><strong class="text-dark">{{ $pName }}</strong></td>
                                <td>{{ implode(', ', $req->radiology_names) ?: ($req->description ?: 'فحص تصوير') }}</td>
                                <td>د. {{ $req->visit?->doctor?->user?->name ?? 'الاستشارية' }}</td>
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

        <!-- 4. EMERGENCY TAB (If any) -->
        @if(isset($emergencyRadiologyRequests) && $emergencyRadiologyRequests->count() > 0)
        <div class="tab-pane fade" id="emergency-pane" role="tabpanel">
            <div class="table-responsive">
                <table class="table unified-table">
                    <thead>
                        <tr>
                            <th style="width: 90px;" class="text-center">رقم طوارئ</th>
                            <th>المريض</th>
                            <th>الفحوصات المطلوبة</th>
                            <th style="width: 90px;" class="text-center">الأولوية</th>
                            <th style="width: 80px;" class="text-center">الوقت</th>
                            <th style="width: 100px;" class="text-center">الحالة</th>
                            <th style="width: 120px;" class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($emergencyRadiologyRequests as $emReq)
                        <tr class="{{ $emReq->status === 'pending' ? 'table-warning' : ($emReq->status === 'in_progress' ? 'table-info' : '') }}">
                            <td class="text-center fw-bold text-danger">#{{ $emReq->emergency_id }}</td>
                            <td class="fw-bold">{{ $emReq->patient->user->name ?? $emReq->patient->name ?? 'مريض' }}</td>
                            <td>
                                @foreach($emReq->radiologyTypes as $type)
                                    <span class="badge bg-info text-white me-1">{{ $type->name }}</span>
                                @endforeach
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $emReq->priority === 'critical' ? 'danger' : 'warning text-dark' }}">
                                    {{ $emReq->priority_text }}
                                </span>
                            </td>
                            <td class="text-center small font-monospace">{{ optional($emReq->requested_at)->format('H:i') }}</td>
                            <td class="text-center">
                                <span class="{{ $emReq->status_badge_class }}">{{ $emReq->status_text }}</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    @if($emReq->status === 'pending')
                                        <form action="{{ route('staff.emergency-radiology.start', $emReq) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm" title="بدء الفحص">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                    @elseif($emReq->status === 'in_progress')
                                        <a href="{{ route('staff.emergency-radiology.show', $emReq) }}" class="btn btn-success btn-sm" title="إدخال النتائج">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @elseif($emReq->status === 'completed')
                                        <a href="{{ route('staff.emergency-radiology.print', $emReq) }}" class="btn btn-outline-secondary btn-sm" target="_blank" title="طباعة">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <!-- 5. ALL / ARCHIVE TAB -->
        <div class="tab-pane fade" id="all-pane" role="tabpanel">
            <div class="card border-0 shadow-xs mb-3 bg-light p-3">
                <form action="{{ route('radiology-staff.index') }}" method="GET" class="row g-2 align-items-center">
                    <input type="hidden" name="tab" value="all">
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                    <div class="col-auto">
                        <select name="date" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>📅 اليوم</option>
                            <option value="all" {{ $dateFilter === 'all' ? 'selected' : '' }}>🗂️ كل التواريخ</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="بحث بالاسم أو الفحص...">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">بحث</button>
                    </div>
                </form>
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
                                <a href="{{ route('radiology-staff.show', $req) }}" class="action-btn btn-outline-primary">
                                    <i class="fas fa-eye"></i> عرض
                                </a>
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
        const res = await fetch(`/radiology-staff/queue-status?category=${currentSelectedCategory}`, {
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
            document.getElementById('badge-live-waiting-count').textContent = `${data.stats.waiting} منتظر`;
            document.getElementById('badge-live-inprogress-count').textContent = `${data.stats.in_progress} قيد الفحص`;
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
                                    ${r.is_paid ? `
                                        <button type="button" class="action-btn btn-warning text-dark fw-bold shadow-xs" onclick="radiologyCallSpecific(${r.id}, '${r.name}')" title="مناداة واستدعاء للغرفة">
                                            <i class="fas fa-bullhorn"></i> نداء
                                        </button>
                                        <button type="button" class="action-btn btn-primary fw-bold shadow-xs" onclick="radiologyStartSpecific(${r.id})" title="إدخال للغرفة وبدء الفحص">
                                            <i class="fas fa-door-open"></i> إدخال
                                        </button>
                                    ` : `
                                        <a href="/radiology-staff/requests/${r.id}/show" class="action-btn btn-outline-secondary" title="معاينة الطلب">
                                            <i class="fas fa-eye"></i> معاينة
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
                            <a href="/radiology-staff/requests/${r.id}/show" class="action-btn btn-success fw-bold shadow-xs">
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
                            <a href="/radiology-staff/requests/${r.id}/show" class="action-btn btn-outline-primary" title="عرض وطباعة">
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
        const res = await fetch(`/radiology-staff/call-next?category=${currentSelectedCategory}`, {
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
        const res = await fetch(`/radiology-staff/recall`, {
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
    window.location.href = `/radiology-staff/requests/${currentCallingRequestId}/show`;
}

async function radiologySkip() {
    if (!confirm('هل تريد تأخير دور هذا المريض ونقله لآخر الطابور؟')) return;
    try {
        const res = await fetch(`/radiology-staff/skip`, {
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
        const res = await fetch(`/radiology-staff/requests/${reqId}/call`, {
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
    window.location.href = `/radiology-staff/requests/${reqId}/show`;
}

// Auto poll every 4s (matching doctor's main station)
setInterval(syncRadiologyQueue, 4000);
syncRadiologyQueue();
</script>
@endsection
