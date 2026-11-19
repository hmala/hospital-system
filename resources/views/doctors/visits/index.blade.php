@extends('layouts.app')

@section('content')
<style>
/* Beautiful Unified Table Styles - Original Hospital System Design */
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

/* Row color coding based on type and status - Professional Medical Colors */
.unified-table tbody tr.today-visit {
    background: #f0f9ff;
    border-left: 3px solid #60a5fa;
}
.unified-table tbody tr.medical-request {
    background: #f3f4f6;
    border-left: 3px solid #a3a3a3;
}
.unified-table tbody tr.completed-visit {
    background: #f0fdf4;
    border-left: 3px solid #34d399;
}
.unified-table tbody tr.incomplete-visit {
    background: #fff7ed;
    border-left: 3px solid #fbbf24;
}

.unified-table tbody tr.scheduled-appointment {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, rgba(37, 99, 235, 0.08) 100%);
    border-left: 4px solid #3b82f6;
    border-right: 4px solid #3b82f6;
}

/* Distinct highlights for Test Results Queue */
.unified-table tbody tr.ready-test-visit {
    background: #dcfce7 !important;
    border-right: 5px solid #16a34a !important;
    border-left: 1px solid #86efac !important;
}
.unified-table tbody tr.ready-test-visit td {
    background: #dcfce7 !important;
}

.unified-table tbody tr.pending-test-visit {
    background: #fefce8 !important;
    border-right: 5px solid #eab308 !important;
    border-left: 1px solid #fde047 !important;
}
.unified-table tbody tr.pending-test-visit td {
    background: #fefce8 !important;
}

.unified-table tbody tr:hover {
    transform: translateX(3px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
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
    text-transform: none;
    letter-spacing: 0.1px;
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

/* Status badge variations - calm colors */
.unified-table .status-badge.status-completed {
    background: #f0fdf4;
    color: #166534;
    border-color: #bbf7d0;
}

.unified-table .status-badge.status-pending {
    background: #fefce8;
    color: #92400e;
    border-color: #fde68a;
}

.unified-table .status-badge.status-cancelled {
    background: #fef2f2;
    color: #991b1b;
    border-color: #fecaca;
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
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: bold;
    color: #2563eb;
    background: #e0e7ff;
    box-shadow: none;
    border: 1px solid #c7d2fe;
}

/* Beautiful type badges with professional medical colors */
.unified-table .type-badge.today-visit {
    background: #e0f2fe;
    color: #2563eb;
}
.unified-table .type-badge.medical-request {
    background: #f3f4f6;
    color: #64748b;
}
.unified-table .type-badge.completed-visit {
    background: #f0fdf4;
    color: #059669;
}
.unified-table .type-badge.incomplete-visit {
    background: #fff7ed;
    color: #d97706;
}
.unified-table .type-badge.scheduled-appointment {
    background: #e0f2fe;
    color: #0ea5e9;
}

/* Responsive design */
@media (max-width: 768px) {
    .unified-table {
        font-size: 0.75rem;
    }

    .unified-table thead th,
    .unified-table tbody td {
        padding: 0.5rem 0.3rem;
    }

    .unified-table tbody tr:hover {
        transform: none;
    }
}

/* Tabs Styling */
#doctorTabs .nav-link {
    font-size: 0.95rem;
    font-weight: 600;
    color: #4b5563;
    padding: 0.75rem 1.25rem;
    border: none;
    border-bottom: 3px solid transparent;
    transition: all 0.2s ease;
    background: transparent;
}
#doctorTabs .nav-link:hover {
    color: #2563eb;
    border-bottom-color: #93c5fd;
}
#doctorTabs .nav-link.active {
    color: #2563eb !important;
    background: transparent !important;
    border-bottom: 3px solid #2563eb !important;
}

/* Calling Station Control Bar (No Cards, Pure Unified System) */
.queue-control-bar {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
}
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-stethoscope me-2 text-primary"></i>
                    {{ __("لوحة تحكم الطبيب") }}
                </h2>
                <small class="text-muted">{{ __("مرحباً") }} {{ __("د.") }} {{ auth()->user()->name }}</small>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(isset($error))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(auth()->user()->isDoctor() && isset($doctor))
    @php
        $pendingInpatientCount = isset($inpatientConsultations) && is_countable($inpatientConsultations) 
            ? $inpatientConsultations->where('status', 'pending')->count() 
            : 0;
    @endphp

    @if($pendingInpatientCount > 0)
        <div class="alert alert-warning border-2 border-warning d-flex align-items-center justify-content-between p-3 mb-4 rounded-3 shadow-sm">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning text-dark p-2 d-flex align-items-center justify-content-center shadow-xs" style="width: 44px; height: 44px;">
                    <i class="fas fa-procedures fa-lg"></i>
                </div>
                <div>
                    <h6 class="mb-1 fw-bold text-dark">{{ __("يوجد طلبات استشارة لمرضى الرقود / العمليات بانتظار ردك") }}</h6>
                    <p class="mb-0 text-muted small">
                        {{ __("لديك") }} <strong class="text-danger">{{ $pendingInpatientCount }}</strong> {{ __("طلب استشارة طبية سريرية موجهة إليك من الأطباء المقيمين لمرضى العمليات.") }}
                    </p>
                </div>
            </div>
            <button type="button" class="btn btn-warning fw-bold text-dark px-3 py-2 shadow-xs" onclick="document.getElementById('inpatient-tab').click();">
                <i class="fas fa-arrow-down me-1"></i> {{ __("عرض الاستشارات السريرية") }}
            </button>
        </div>
    @endif

    <!-- Doctor Tabs Navigation -->
    <ul class="nav nav-tabs mb-4 border-bottom" id="doctorTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center gap-2" id="station-tab" data-bs-toggle="tab" data-bs-target="#station-pane" type="button" role="tab" aria-controls="station-pane" aria-selected="true">
                <i class="fas fa-bullhorn text-primary"></i>
                <span>{{ __("محطة العيادة والمناداة الحية") }}</span>
                <span class="badge bg-primary text-white rounded-pill px-2 py-1" id="badge-tab-waiting-count">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="today-tab" data-bs-toggle="tab" data-bs-target="#today-pane" type="button" role="tab" aria-controls="today-pane" aria-selected="false">
                <i class="fas fa-calendar-day text-info"></i>
                <span>{{ __("زيارات اليوم") }}</span>
                <span class="badge bg-info text-white rounded-pill px-2 py-1">{{ isset($todayVisits) && is_countable($todayVisits) ? $todayVisits->count() : 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="inpatient-tab" data-bs-toggle="tab" data-bs-target="#inpatient-pane" type="button" role="tab" aria-controls="inpatient-pane" aria-selected="false">
                <i class="fas fa-procedures text-warning"></i>
                <span>{{ __("استشارات الرقود والعمليات") }}</span>
                @if($pendingInpatientCount > 0)
                    <span class="badge bg-danger rounded-pill px-2 py-1 animate-pulse">{{ $pendingInpatientCount }}</span>
                @elseif(isset($inpatientConsultations) && is_countable($inpatientConsultations) && $inpatientConsultations->count() > 0)
                    <span class="badge bg-secondary rounded-pill px-2 py-1">{{ $inpatientConsultations->count() }}</span>
                @else
                    <span class="badge bg-light text-muted rounded-pill px-2 py-1">0</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="incomplete-tab" data-bs-toggle="tab" data-bs-target="#incomplete-pane" type="button" role="tab" aria-controls="incomplete-pane" aria-selected="false">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <span>{{ __("زيارات معلقة") }}</span>
                @if(isset($incompleteVisits) && is_countable($incompleteVisits) && $incompleteVisits->count() > 0)
                    <span class="badge bg-danger rounded-pill px-2 py-1">{{ $incompleteVisits->count() }}</span>
                @else
                    <span class="badge bg-light text-muted rounded-pill px-2 py-1">0</span>
                @endif
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive-pane" type="button" role="tab" aria-controls="archive-pane" aria-selected="false">
                <i class="fas fa-archive text-secondary"></i>
                <span>{{ __("أرشيف جميع الزيارات") }}</span>
                <span class="badge bg-secondary rounded-pill px-2 py-1">{{ isset($allVisits) && is_countable($allVisits) ? $allVisits->count() : 0 }}</span>
            </button>
        </li>
    </ul>

    <!-- Tab Content Panels -->
    <div class="tab-content" id="doctorTabsContent">
        <!-- 1. LIVE STATION TAB (جدول موحد وبدون كارتات) -->
        <div class="tab-pane fade show active" id="station-pane" role="tabpanel" aria-labelledby="station-tab">
            <!-- Calling Control Bar (شريط تحكم موحد خفيف) -->
            <div class="queue-control-bar">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <!-- Serving Status -->
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-circle fs-6" style="width: 42px; height: 42px;">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white px-2 py-1" id="doctor-station-current-num">{{ __("الدور: -") }}</span>
                                <strong class="text-dark fs-6" id="doctor-station-current-name">{{ __("لا يوجد مريض مستدعى") }}</strong>
                                <span class="status-badge status-pending" id="doctor-station-status-badge">{{ __("العيادة جاهزة") }}</span>
                            </div>
                            <small class="text-muted" id="doctor-station-current-status">{{ __("اضغط \"استدعاء التالي\" لمناداة أول مريض في الانتظار") }}</small>
                        </div>
                    </div>

                    <!-- Actions & Screen Link -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn btn-success fw-bold px-3 py-2" onclick="doctorCallNext()" id="btn-call-next">
                            <i class="fas fa-bullhorn me-1"></i> {{ __("استدعاء التالي") }}
                        </button>
                        <button type="button" class="btn btn-warning fw-bold px-3 py-2" onclick="doctorRecall()" id="btn-recall" style="display: none;">
                            <i class="fas fa-redo me-1"></i> {{ __("إعادة المناداة") }}
                        </button>
                        <button type="button" class="btn btn-info text-white fw-bold px-3 py-2 shadow-sm" id="btn-emergency-consult" style="display: none;" onclick="openEmergencyConsultModal()">
                            <i class="fas fa-file-medical me-1"></i> {{ __("تسجيل رأي الاستشارة للطوارئ") }}
                        </button>
                        <button type="button" class="btn btn-primary fw-bold px-3 py-2" onclick="doctorStartConsultation()" id="btn-start-consult" style="display: none;">
                            <i class="fas fa-sign-in-alt me-1"></i> {{ __("بدء الكشف") }}
                        </button>
                        <button type="button" class="btn btn-outline-secondary fw-semibold px-3 py-2" onclick="doctorSkip()" id="btn-skip" style="display: none;">
                            <i class="fas fa-forward me-1"></i> {{ __("تخطي") }}
                        </button>
                        <a href="{{ route('queue.doctor.display', $doctor->id) }}" target="_blank" class="btn btn-outline-primary px-3 py-2">
                            <i class="fas fa-tv me-1"></i> {{ __("شاشة العرض") }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- 1. جدول {{ __("طابور الانتظار الأولي (المرضى الجدد)") }} -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-users-line me-2 text-primary"></i>
                    {{ __("طابور الانتظار الأولي (المرضى الجدد)") }}
                </h5>
                <span class="badge bg-primary text-white" id="badge-live-waiting-count">0 منتظر</span>
            </div>
            <div class="table-responsive">
                <table class="table unified-table mb-4">
                    <thead>
                        <tr>
                            <th style="width: 90px;"><i class="fas fa-hashtag me-1"></i>{{ __("الدور") }}</th>
                            <th><i class="fas fa-user-injured me-2"></i>{{ __("المريض") }}</th>
                            <th><i class="fas fa-tag me-2"></i>{{ __("نوع الحجز") }}</th>
                            <th><i class="fas fa-tasks me-2"></i>{{ __("الحالة") }}</th>
                            <th class="text-end" style="width: 210px;"><i class="fas fa-cogs me-2"></i>{{ __("الإجراء") }}</th>
                        </tr>
                    </thead>
                    <tbody id="doctor-station-waiting-list">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle text-success me-1"></i> {{ __("لا يوجد مرضى في طابور الانتظار حالياً") }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 2. جدول مراجعي الفحوصات الطبية وصرف الأدوية (الأشعة والمختبر والصيدلية) -->
            <div id="live-pharmacy-substitutions-banner" class="mb-3" style="display: none;"></div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-notes-medical me-2 text-info"></i>
                    {{ __("مراجعو الفحوصات الطبية وصرف الأدوية (الأشعة، المختبر، والصيدلية)") }}
                </h5>
                <span class="badge bg-info text-white" id="badge-pending-tests-count">0 مراجع</span>
            </div>
            <div class="table-responsive">
                <table class="table unified-table mb-4">
                    <thead>
                        <tr>
                            <th style="width: 90px;"><i class="fas fa-hashtag me-1"></i>{{ __("الدور") }}</th>
                            <th><i class="fas fa-user-injured me-2"></i>{{ __("المريض") }}</th>
                            <th><i class="fas fa-clipboard-check me-2"></i>{{ __("الفحوصات والأدوية المطلوبة") }}</th>
                            <th><i class="fas fa-tasks me-2"></i>{{ __("حالة الإنجاز والصرف") }}</th>
                            <th class="text-end" style="width: 170px;"><i class="fas fa-cogs me-2"></i>{{ __("الإجراء") }}</th>
                        </tr>
                    </thead>
                    <tbody id="doctor-station-pending-tests">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle text-success me-1"></i> {{ __("لا يوجد مراجعون بانتظار نتائج فحوصات أو صرف أدوية حالياً") }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. TODAY'S VISITS TAB (التصميم الأصلي المعتمد) -->
        <div class="tab-pane fade" id="today-pane" role="tabpanel" aria-labelledby="today-tab">
            <div class="table-responsive">
                <table class="table unified-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user-injured me-2"></i>{{ __("المريض") }}</th>
                            <th><i class="fas fa-clock me-2"></i>{{ __("التوقيت") }}</th>
                            <th><i class="fas fa-tag me-2"></i>{{ __("نوع الزيارة") }}</th>
                            <th><i class="fas fa-tasks me-2"></i>{{ __("الحالة") }}</th>
                            <th><i class="fas fa-cogs me-2"></i>{{ __("الإجراءات") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($todayVisits) && is_countable($todayVisits) && $todayVisits->count() > 0)
                            @foreach($todayVisits as $visit)
                            @php
                                $isCompleted = $visit->status == 'completed';
                                $isCancelled = $visit->status == 'cancelled';
                                $isInProgress = $visit->status == 'in_progress';
                            @endphp
                            <tr class="today-visit">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle">
                                            {{ substr(optional($visit->patient)->user->name ?? 'غ', 0, 1) }}
                                        </div>
                                        <div class="ms-2">
                                            <strong>{{ optional($visit->patient)->user->name ?? 'غير محدد' }}</strong>
                                            @if($visit->appointment && $visit->appointment->emergency_id)
                                                <span class="badge bg-danger ms-2"><i class="fas fa-ambulance"></i> {{ __("طوارئ") }}</span>
                                            @endif
                                            <br>
                                            <small class="text-muted">{{ $visit->visit_type_text ?? 'زيارة عامة' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        <i class="fas fa-clock me-1 text-primary"></i>
                                        {{ $visit->visit_time ? \Carbon\Carbon::parse($visit->visit_time)->format('h:i A') : '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="type-badge today-visit">{{ $visit->visit_type_text ?? 'زيارة عامة' }}</span>
                                </td>
                                <td>
                                    @if($isCompleted)
                                        <span class="status-badge status-completed"><i class="fas fa-check-circle"></i> {{ __("مكتملة") }}</span>
                                    @elseif($isCancelled)
                                        <span class="status-badge status-cancelled"><i class="fas fa-times-circle"></i> {{ __("ملغية") }}</span>
                                    @elseif($isInProgress)
                                        <span class="status-badge" style="background: #e0f2fe; color: #0369a1; border-color: #7dd3fc;"><i class="fas fa-spinner fa-spin"></i> {{ __("قيد الفحص") }}</span>
                                    @else
                                        <span class="status-badge status-pending"><i class="fas fa-hourglass-half"></i> {{ __("في الانتظار") }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isCompleted)
                                        <a href="{{ route('doctor.visits.show', $visit) }}" class="action-btn btn-outline-primary">
                                            <i class="fas fa-eye"></i> {{ __("عرض الملف") }}
                                        </a>
                                    @elseif($visit->appointment && $visit->appointment->emergency_id)
                                        <button type="button" class="action-btn btn-danger fw-bold shadow-sm" 
                                                onclick="openEmergencyConsultModal({{ $visit->appointment->id }}, '{{ addslashes(optional($visit->patient)->user->name ?? 'مريض طوارئ') }}', '{{ addslashes($visit->appointment->emergency->notes ?? $visit->chief_complaint ?? '') }}')">
                                            <i class="fas fa-file-medical"></i> {{ __("رأي الاستشارة") }}
                                        </button>
                                    @else
                                        <a href="{{ route('doctor.visits.show', $visit) }}" class="action-btn btn-warning">
                                            <i class="fas fa-clipboard-check"></i> {{ __("متابعة الكشف") }}
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        @else
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fas fa-calendar-day fa-3x text-muted mb-3 d-block"></i>
                                <h5 class="text-muted">{{ __("لا توجد زيارات مسجلة لليوم حتى الآن") }}</h5>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3. INCOMPLETE VISITS TAB (الزيارات المعلقة بالتصميم الأصلي) -->
        <div class="tab-pane fade" id="incomplete-pane" role="tabpanel" aria-labelledby="incomplete-tab">
            @if(isset($incompleteVisits) && is_countable($incompleteVisits) && $incompleteVisits->count() > 0)
            <div class="alert alert-warning border-warning shadow-sm mb-4" role="alert" style="border-left: 4px solid #f59e0b;">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="alert-heading mb-1 fw-bold">
                            لديك {{ $incompleteVisits->count() }} زيارة غير مكتملة من أيام سابقة
                        </h5>
                        <p class="mb-0 small">{{ __("يرجى المتابعة وإنهاء الفحوصات والوصفات الطبية لغلق ملف الزيارة.") }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="table-responsive">
                <table class="table unified-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user-injured me-2"></i>{{ __("المريض") }}</th>
                            <th><i class="fas fa-calendar me-2"></i>{{ __("تاريخ الزيارة") }}</th>
                            <th><i class="fas fa-tag me-2"></i>{{ __("نوع الزيارة") }}</th>
                            <th><i class="fas fa-tasks me-2"></i>{{ __("الحالة") }}</th>
                            <th><i class="fas fa-cogs me-2"></i>{{ __("الإجراءات") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($incompleteVisits) && is_countable($incompleteVisits) && $incompleteVisits->count() > 0)
                            @foreach($incompleteVisits as $visit)
                            <tr class="incomplete-visit" style="background: #fff7ed; border-left: 4px solid #fbbf24;">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle">
                                            {{ substr(optional($visit->patient)->user->name ?? 'غ', 0, 1) }}
                                        </div>
                                        <div class="ms-2">
                                            <strong>{{ optional($visit->patient)->user->name ?? 'غير محدد' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $visit->visit_type_text ?? 'زيارة عامة' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        <i class="fas fa-calendar-alt me-1 text-warning"></i>
                                        {{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="type-badge incomplete-visit">{{ $visit->visit_type_text ?? 'زيارة عامة' }}</span>
                                </td>
                                <td>
                                    <span class="status-badge status-pending"><i class="fas fa-clock"></i> {{ __("غير مكتملة") }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('doctor.visits.show', $visit) }}" class="action-btn btn-warning">
                                        <i class="fas fa-clipboard-check"></i> {{ __("إكمال الفحص") }}
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                                <h5 class="text-muted">{{ __("ممتاز! لا توجد أي زيارات معلقة من أيام سابقة") }}</h5>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. ALL VISITS ARCHIVE TAB (أرشيف الزيارات بالتصميم الأصلي) -->
        <div class="tab-pane fade" id="archive-pane" role="tabpanel" aria-labelledby="archive-tab">
            <div class="table-responsive">
                <table class="table unified-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user-injured me-2"></i>{{ __("المريض") }}</th>
                            <th><i class="fas fa-calendar-alt me-2"></i>{{ __("التاريخ") }}</th>
                            <th><i class="fas fa-tag me-2"></i>{{ __("نوع الزيارة") }}</th>
                            <th><i class="fas fa-tasks me-2"></i>{{ __("الحالة") }}</th>
                            <th><i class="fas fa-cogs me-2"></i>{{ __("الإجراءات") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($allVisits) && is_countable($allVisits) && $allVisits->count() > 0)
                            @foreach($allVisits as $visit)
                            @php
                                $isCompleted = $visit->status == 'completed';
                                $isCancelled = $visit->status == 'cancelled';
                                $isIncomplete = in_array($visit->status, ['in_progress', 'waiting']);
                                $rowClass = $isCompleted ? 'completed-visit' : ($isCancelled ? '' : 'incomplete-visit');
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle">
                                            {{ substr(optional($visit->patient)->user->name ?? 'غ', 0, 1) }}
                                        </div>
                                        <div class="ms-2">
                                            <strong>{{ optional($visit->patient)->user->name ?? 'غير محدد' }}</strong>
                                            @if($visit->appointment && $visit->appointment->emergency_id)
                                                <span class="badge bg-danger ms-2"><i class="fas fa-ambulance"></i> {{ __("طوارئ") }}</span>
                                            @endif
                                            <br>
                                            <small class="text-muted">{{ $visit->visit_type_text ?? 'زيارة عامة' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-muted small">
                                        <i class="fas fa-calendar-alt me-1 text-primary"></i>
                                        {{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="type-badge">{{ $visit->visit_type_text ?? 'زيارة عامة' }}</span>
                                </td>
                                <td>
                                    @if($isCompleted)
                                        <span class="status-badge status-completed"><i class="fas fa-check-circle"></i> {{ __("مكتملة") }}</span>
                                    @elseif($isCancelled)
                                        <span class="status-badge status-cancelled"><i class="fas fa-times-circle"></i> {{ __("ملغية") }}</span>
                                    @else
                                        <span class="status-badge status-pending"><i class="fas fa-clock"></i> {{ $visit->status_text ?? $visit->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('doctor.visits.show', $visit) }}" class="action-btn btn-outline-primary">
                                        <i class="fas fa-eye"></i> {{ __("عرض الملف") }}
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fas fa-archive fa-3x text-muted mb-3 d-block"></i>
                                <h5 class="text-muted">{{ __("لا توجد زيارات مسجلة في الأرشيف") }}</h5>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5. INPATIENT & SURGERY CONSULTATIONS TAB (استشارات الرقود والعمليات) -->
        <div class="tab-pane fade" id="inpatient-pane" role="tabpanel" aria-labelledby="inpatient-tab">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-procedures text-warning me-2"></i>
                        {{ __("طلبات الاستشارة الطبية لمرضى الرقود والعمليات الجراحية") }}
                    </h5>
                    <span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle px-3 py-2">
                        <i class="fas fa-user-md me-1"></i>
                        {{ __("مطلوبة من الأطباء المقيمين") }}
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table unified-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user-injured me-2"></i>{{ __("المريض والملف") }}</th>
                                    <th><i class="fas fa-hospital me-2"></i>{{ __("العملية / الجناح") }}</th>
                                    <th><i class="fas fa-user-nurse me-2"></i>{{ __("الطلب والسبب السريري") }}</th>
                                    <th><i class="fas fa-calendar-alt me-2"></i>{{ __("الأولوية والتاريخ") }}</th>
                                    <th><i class="fas fa-file-invoice-dollar me-2"></i>{{ __("حالة الدفع") }}</th>
                                    <th class="text-center"><i class="fas fa-cogs me-2"></i>{{ __("الحالة والإجراء") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($inpatientConsultations) && is_countable($inpatientConsultations) && $inpatientConsultations->count() > 0)
                                    @foreach($inpatientConsultations as $consultation)
                                        @if($consultation->status === 'cancelled')
                                            @continue
                                        @endif
                                        @php
                                            $cSurgery = $consultation->surgery;
                                            $cPatient = $cSurgery?->patient;
                                            $isHI = ($cSurgery?->insurance_type === 'hi') || ($cPatient?->insurance_type === 'hi');
                                            $isMOI = ($cSurgery?->insurance_type === 'moi') || ($cPatient?->insurance_type === 'moi');
                                        @endphp
                                        <tr class="{{ $consultation->isPending() ? 'table-warning bg-opacity-25' : '' }}">
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-circle fs-6" style="width: 36px; height: 36px; background: #e0f2fe; color: #0284c7;">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <strong class="text-dark d-block">{{ $cPatient?->user?->name ?? __('مريض رقود') }}</strong>
                                                        <div class="small text-muted">
                                                            <span>#{{ $cPatient?->file_number ?? '-' }}</span>
                                                            @if($cPatient?->gender)
                                                                <span class="mx-1">•</span>
                                                                <span>{{ $cPatient->gender == 'male' ? __('ذكر') : __('أنثى') }}</span>
                                                            @endif
                                                            @if($isHI)
                                                                <span class="badge bg-info-subtle text-info border border-info ms-1">{{ __('ضمان صحي') }}</span>
                                                            @elseif($isMOI)
                                                                <span class="badge bg-primary-subtle text-primary border border-primary ms-1">{{ __('قوى الأمن') }}</span>
                                                            @else
                                                                <span class="badge bg-secondary-subtle text-secondary ms-1">{{ __('نقدي') }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <strong class="text-primary d-block">
                                                    <i class="fas fa-procedures me-1"></i>{{ $cSurgery?->surgery_type ?? __('عملية جراحية') }}
                                                </strong>
                                                <small class="text-muted d-block">
                                                    {{ __('غرفة:') }} {{ $cSurgery?->room?->room_number ?? __('غير محدد') }}
                                                    @if($cSurgery?->doctor?->user)
                                                        <span class="mx-1">•</span>{{ __('الجراح:') }} د. {{ $cSurgery->doctor->user->name }}
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    <span class="text-muted">{{ __('المقيم:') }}</span>
                                                    <strong class="text-dark">{{ $consultation->requestedBy?->name ?? __('طبيب مقيم') }}</strong>
                                                </div>
                                                <div class="small text-dark mt-1 p-1 bg-light rounded border border-light-subtle">
                                                    <i class="fas fa-comment-medical text-primary me-1"></i>
                                                    {{ Str::limit($consultation->reason, 80) }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge {{ $consultation->priorityBadgeClass() }} mb-1">
                                                    {{ $consultation->priorityLabel() }}
                                                </span>
                                                <small class="text-muted d-block">
                                                    {{ $consultation->created_at?->diffForHumans() }}
                                                </small>
                                            </td>
                                            <td>
                                                @if($consultation->isPaid())
                                                    <span class="badge bg-success-subtle text-success border border-success">
                                                        <i class="fas fa-check-circle me-1"></i>{{ __("مسدد بالكاشير") }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning">
                                                        <i class="fas fa-clock me-1"></i>{{ __("معلق بالكاشير") }}
                                                    </span>
                                                @endif
                                                <div class="small text-muted mt-1">{{ number_format($consultation->fee, 0) }} د.ع</div>
                                            </td>
                                            <td class="text-center">
                                                @if($consultation->isCompleted())
                                                    <span class="badge bg-success text-white mb-2 d-inline-block">
                                                        <i class="fas fa-check-double me-1"></i>{{ __("تم الرد والتوثيق") }}
                                                    </span>
                                                    <button type="button" 
                                                            class="btn btn-outline-info btn-sm d-block w-100 py-1"
                                                            onclick="viewInpatientConsultation(this)"
                                                            data-patient="{{ $cPatient?->user?->name ?? '-' }}"
                                                            data-operation="{{ $cSurgery?->surgery_type ?? '-' }}"
                                                            data-room="غرفة {{ $cSurgery?->room?->room_number ?? '-' }}"
                                                            data-reason="{{ $consultation->reason }}"
                                                            data-response="{{ $consultation->consultant_response }}"
                                                            data-recommendations="{{ $consultation->consultant_recommendations }}"
                                                            data-responded-at="{{ $consultation->responded_at?->translatedFormat('Y/m/d h:i A') ?? '-' }}"
                                                            data-doctor="{{ $consultation->respondedBy?->name ?? $consultation->doctor?->user?->name ?? '-' }}">
                                                        <i class="fas fa-eye me-1"></i>{{ __("عرض الرد") }}
                                                    </button>
                                                @else
                                                    <button type="button" 
                                                            class="btn btn-warning btn-sm fw-bold px-3 shadow-xs text-dark"
                                                            onclick="openInpatientConsultModal(this)"
                                                            data-surgery-id="{{ $consultation->surgery_id }}"
                                                            data-consultation-id="{{ $consultation->id }}"
                                                            data-patient-name="{{ $cPatient?->user?->name ?? '-' }}"
                                                            data-room="غرفة {{ $cSurgery?->room?->room_number ?? '-' }}"
                                                            data-operation="{{ $cSurgery?->surgery_type ?? 'عملية جراحية' }}"
                                                            data-priority="{{ $consultation->priorityLabel() }}"
                                                            data-reason="{{ $consultation->reason }}">
                                                        <i class="fas fa-pen-alt me-1"></i>
                                                        {{ __("توثيق الرأي والتوصيات") }}
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fas fa-procedures fa-3x text-muted mb-3 d-block"></i>
                                            <h5 class="text-muted">{{ __("لا توجد طلبات استشارة لمرضى الرقود أو العمليات حالياً") }}</h5>
                                            <p class="text-muted small mb-0">{{ __("عند قيام الأطباء المقيمين بطلب استشارتك لمريض في العمليات أو الردهة ستظهر هنا فوراً.") }}</p>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal تسجيل رأي الاستشارة لحالة الطوارئ -->
<div class="modal fade" id="emergencyConsultResponseModal" tabindex="-1" aria-labelledby="emergencyConsultResponseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0">
            <form id="emergencyConsultResponseForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="emergencyConsultResponseModalLabel">
                        <i class="fas fa-ambulance me-2"></i>{{ __("تسجيل رأي الاستشارة الطبية لحالة الطوارئ") }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- معلومات الحالة والقادمة من الطوارئ -->
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">{{ __("اسم المريض:") }}</small>
                                    <strong class="fs-6 text-dark" id="modal-patient-name">-</strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">{{ __("رقم الحالة / الدور:") }}</small>
                                    <span class="badge bg-danger" id="modal-queue-num">{{ __("طوارئ") }}</span>
                                </div>
                                <div class="col-12 mt-2" id="modal-emergency-details-box">
                                    <div class="p-2 bg-white rounded border border-warning">
                                        <small class="fw-bold text-danger d-block mb-1">
                                            <i class="fas fa-notes-medical me-1"></i>{{ __("طلب وملاحظات طبيب الطوارئ:") }}
                                        </small>
                                        <div class="text-muted small" id="modal-emergency-notes">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- رأي الاستشاري والتوصيات -->
                    <div class="mb-3">
                        <label for="consultant_notes_input" class="form-label fw-bold text-dark">
                            <i class="fas fa-user-md text-primary me-1"></i>
                            {{ __("رأي الاستشاري / الإجراء المتبع والتوصيات الطبية") }} <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="consultant_notes_input" name="consultant_notes" rows="4" 
                                  placeholder="{{ __("اكتب التقييم التخصصي، خطة التدخل، الأدوية أو الإجراءات الفورية لحالة المريض...") }}" required></textarea>
                    </div>

                    <div class="mb-2">
                        <label for="consultant_recommendation_select" class="form-label fw-bold text-dark">
                            <i class="fas fa-directions text-secondary me-1"></i>
                            {{ __("التوصية بالمسار العلاجي (اختياري)") }}
                        </label>
                        <select class="form-select" id="consultant_recommendation_select" name="consultant_recommendation">
                            <option value="emergency_treatment" selected>{{ __("علاج تحفظي ومتابعة بقسم الطوارئ") }}</option>
                            <option value="requires_surgery">{{ __("تحويل عاجل إلى صالة العمليات (Requires Surgery)") }}</option>
                            <option value="requires_admission">{{ __("تحويل ودخول للرقود (Requires Admission)") }}</option>
                            <option value="discharge_safe">{{ __("مستقر ويمكن تخريجه للمنزل (Safe for Discharge)") }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>إلغاء
                    </button>
                    <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm">
                        <i class="fas fa-save me-1"></i>{{ __("حفظ وإرسال التقرير للطوارئ") }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تسجيل رأي الاستشارة لمرضى الرقود والعمليات -->
<div class="modal fade" id="inpatientConsultResponseModal" tabindex="-1" aria-labelledby="inpatientConsultResponseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0">
            <form id="inpatientConsultResponseForm" method="POST" action="">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}?tab=inpatient">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title fw-bold" id="inpatientConsultResponseModalLabel">
                        <i class="fas fa-user-md me-2"></i>{{ __("توثيق رد وتوصيات الاستشارة الطبية (مرضى الرقود / العمليات)") }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- معلومات الحالة والقادمة من المحطة السريرية -->
                    <div class="card bg-light border-0 mb-3">
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">{{ __("اسم المريض:") }}</small>
                                    <strong class="fs-6 text-dark" id="inpatient-modal-patient-name">-</strong>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block">{{ __("الغرفة والجناح:") }}</small>
                                    <span class="badge bg-primary px-2 py-1" id="inpatient-modal-room">-</span>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <small class="text-muted d-block">{{ __("العملية الجراحية:") }}</small>
                                    <strong class="text-dark" id="inpatient-modal-operation">-</strong>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <small class="text-muted d-block">{{ __("درجة الأولوية:") }}</small>
                                    <span class="badge bg-warning text-dark" id="inpatient-modal-priority">-</span>
                                </div>
                                <div class="col-12 mt-2">
                                    <div class="p-2 bg-white rounded border border-warning">
                                        <small class="fw-bold text-primary d-block mb-1">
                                            <i class="fas fa-comment-medical me-1"></i>{{ __("سبب الاستشارة وطلب المقيم:") }}
                                        </small>
                                        <div class="text-dark small" id="inpatient-modal-reason">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- رأي الاستشاري والتوصيات -->
                    <div class="mb-3">
                        <label for="consultant_response_input" class="form-label fw-bold text-dark">
                            <i class="fas fa-stethoscope text-primary me-1"></i>
                            {{ __("الرأي السريري والتقييم الطبي للاستشاري") }} <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="consultant_response_input" name="consultant_response" rows="4" 
                                  placeholder="{{ __("اكتب التقييم الطبي الدقيق، التشخيص التخصصي، والتقييم السريري لحالة المريض...") }}" required></textarea>
                    </div>

                    <div class="mb-2">
                        <label for="consultant_recommendations_input" class="form-label fw-bold text-dark">
                            <i class="fas fa-clipboard-list text-success me-1"></i>
                            {{ __("التوصيات الطبية والعلاجية الموجهة للفريق الجراحي والمقيم (اختياري)") }}
                        </label>
                        <textarea class="form-control" id="consultant_recommendations_input" name="consultant_recommendations" rows="3" 
                                  placeholder="{{ __("توصيات الأدوية، الجرعات، خطة المتابعة، أو قيود ما بعد العملية...") }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>{{ __("إلغاء") }}
                    </button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark px-4 shadow-sm">
                        <i class="fas fa-check-circle me-1"></i>{{ __("اعتماد وتوثيق الرد السريري") }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal عرض رد الاستشارة الموثقة -->
<div class="modal fade" id="viewInpatientConsultModal" tabindex="-1" aria-labelledby="viewInpatientConsultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="viewInpatientConsultModalLabel">
                    <i class="fas fa-clipboard-check me-2"></i>{{ __("تفاصيل رد الاستشارة الطبية المعتمدة") }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <small class="text-muted d-block">{{ __("المريض:") }}</small>
                                <strong class="fs-6 text-dark" id="view-modal-patient">-</strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">{{ __("العملية والغرفة:") }}</small>
                                <span class="badge bg-secondary" id="view-modal-operation">-</span>
                            </div>
                            <div class="col-12 mt-2">
                                <small class="text-muted d-block">{{ __("سبب الاستشارة:") }}</small>
                                <div class="text-dark small p-2 bg-white rounded border" id="view-modal-reason">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-primary">
                        <i class="fas fa-stethoscope me-1"></i>{{ __("رد وتقييم الاستشاري:") }}
                    </label>
                    <div class="p-3 bg-white rounded border border-primary border-opacity-25" id="view-modal-response" style="white-space: pre-wrap;">-</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold text-success">
                        <i class="fas fa-clipboard-list me-1"></i>{{ __("التوصيات السريرية:") }}
                    </label>
                    <div class="p-3 bg-white rounded border border-success border-opacity-25" id="view-modal-recommendations" style="white-space: pre-wrap;">-</div>
                </div>

                <div class="d-flex justify-content-between align-items-center text-muted small">
                    <div>
                        <span>{{ __("الموثق:") }}</span> <strong id="view-modal-doctor">-</strong>
                    </div>
                    <div>
                        <span>{{ __("تاريخ الرد:") }}</span> <span id="view-modal-date">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">{{ __("إغلاق") }}</button>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()->isDoctor() && isset($doctor))
<script>
    const currentDoctorId = {{ $doctor->id }};
    const baseUrl = window.location.origin + (window.location.pathname.startsWith('/hearmz') ? '/hearmz' : '');
    let currentAppointmentId = null;

    async function syncDoctorQueue() {
        try {
            const res = await fetch(`${baseUrl}/queue/doctor/${currentDoctorId}/data`);
            if (!res.ok) return;
            const data = await res.json();

            if (data.success) {
                const cur = data.current_patient;
                const stats = data.stats || {};
                
                // 1. Current Station State
                const waitCount = stats.waiting_count || 0;
                const badgeTabEl = document.getElementById('badge-tab-waiting-count');
                if (badgeTabEl) badgeTabEl.textContent = `${waitCount}`;

                const btnRecall = document.getElementById('btn-recall');
                const btnStartConsult = document.getElementById('btn-start-consult');
                const btnEmergencyConsult = document.getElementById('btn-emergency-consult');
                const btnSkip = document.getElementById('btn-skip');

                window.currentPatientData = cur;

                if (cur) {
                    currentAppointmentId = cur.id;
                    document.getElementById('doctor-station-current-num').textContent = `الدور: #${cur.queue_number}`;
                    document.getElementById('doctor-station-current-name').textContent = cur.name;
                    document.getElementById('doctor-station-current-status').textContent = cur.status_text;

                    const statusBadge = document.getElementById('doctor-station-status-badge');
                    if (cur.status === 'calling') {
                        statusBadge.className = 'status-badge status-cancelled';
                        statusBadge.innerHTML = '<i class="fas fa-bullhorn"></i> {{ __('يتم الاستدعاء الآن') }}';
                    } else {
                        statusBadge.className = 'status-badge status-completed';
                        statusBadge.innerHTML = '<i class="fas fa-user-check"></i> {{ __('المريض بالداخل') }}';
                    }

                    btnRecall.style.display = 'inline-block';
                    btnSkip.style.display = 'inline-block';

                    if (cur.is_emergency) {
                        btnEmergencyConsult.style.display = 'inline-block';
                        btnStartConsult.style.display = 'none';
                    } else {
                        btnEmergencyConsult.style.display = 'none';
                        btnStartConsult.style.display = 'inline-block';
                    }
                } else {
                    currentAppointmentId = null;
                    document.getElementById('doctor-station-current-num').textContent = 'الدور: -';
                    document.getElementById('doctor-station-current-name').textContent = '{{ __('لا يوجد مريض مستدعى') }}';
                    document.getElementById('doctor-station-current-status').textContent = '{{ __('اضغط "استدعاء التالي" لمناداة أول مريض في الانتظار') }}';
                    
                    const statusBadge = document.getElementById('doctor-station-status-badge');
                    statusBadge.className = 'status-badge status-pending';
                    statusBadge.innerHTML = '<i class="fas fa-clock"></i> {{ __('العيادة جاهزة') }}';

                    btnRecall.style.display = 'none';
                    btnStartConsult.style.display = 'none';
                    btnEmergencyConsult.style.display = 'none';
                    btnSkip.style.display = 'none';
                }

                // 2. Render Waiting Queue Table Rows (طابور الانتظار الأولي)
                const waitingList = data.waiting_list || [];
                const badgeWaitCount = document.getElementById('badge-live-waiting-count');
                if (badgeWaitCount) badgeWaitCount.textContent = `${waitCount} منتظر`;

                const waitingTbody = document.getElementById('doctor-station-waiting-list');
                if (waitingTbody) {
                    if (waitingList.length === 0) {
                        waitingTbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle text-success me-1"></i> {{ __("لا يوجد مرضى في طابور الانتظار حالياً") }}
                                </td>
                            </tr>
                        `;
                    } else {
                        waitingTbody.innerHTML = waitingList.map((item, idx) => {
                            const isEmergency = item.is_emergency;
                            return `
                                <tr class="scheduled-appointment">
                                    <td>
                                        <span class="badge bg-primary text-white fw-bold fs-6">#${item.queue_number}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle">
                                                ${item.name.charAt(0)}
                                            </div>
                                            <div class="ms-2">
                                                <strong>${item.name}</strong>
                                                ${isEmergency ? '<span class="badge bg-danger ms-1"><i class="fas fa-ambulance"></i> طوارئ</span>' : ''}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="type-badge scheduled-appointment">${isEmergency ? 'طوارئ' : 'كشف استشاري'}</span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-pending"><i class="fas fa-clock"></i> ${item.status_text || 'في الانتظار'}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            ${idx === 0 ? `
                                                <button type="button" class="action-btn btn-success fw-bold shadow-sm" onclick="doctorCallSpecific(${item.id})" title="استدعاء صاحب الدور الحالي">
                                                    <i class="fas fa-bullhorn"></i> استدعاء
                                                </button>
                                            ` : `
                                                <button type="button" class="action-btn btn-outline-success fw-bold shadow-sm" onclick="doctorCallSpecific(${item.id})" title="تقديم دور هذا المريض واستدعاؤه للعيادة الآن">
                                                    <i class="fas fa-bullhorn"></i> تقديم واستدعاء
                                                </button>
                                                <span class="badge bg-light text-secondary border px-2 py-1 small">دور #${idx + 1}</span>
                                            `}
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    }
                }

                // 3. Render Pending / Ready Test Results Table Rows (مراجعو الفحوصات والنتائج)
                // 3. Render Pending / Ready Test Results & Pharmacy Table Rows (مراجعو الفحوصات والنتائج والصيدلية)
                const pendingTestsList = data.pending_tests_list || [];
                const pendingTestsCount = (stats.pending_tests_count !== undefined) ? stats.pending_tests_count : pendingTestsList.length;
                const badgePendingCount = document.getElementById('badge-pending-tests-count');
                if (badgePendingCount) badgePendingCount.textContent = `${pendingTestsCount} مراجع`;

                // Top Pharmacy Substitutions Urgent Alert Banner
                const subBanner = document.getElementById('live-pharmacy-substitutions-banner');
                if (subBanner) {
                    const subVisits = pendingTestsList.filter(item => item.has_substitution_alert);
                    if (subVisits.length > 0) {
                        subBanner.style.display = 'block';
                        subBanner.innerHTML = `
                            <div class="alert alert-danger border-2 border-danger shadow-sm rounded-4 p-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-danger text-white p-2 rounded-circle fs-4 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                        <i class="fas fa-prescription-bottle-alt fa-bounce"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block fs-6 text-danger">🔔 إشعار عاجل من الصيدلية: (${subVisits.length}) مريض بانتظار موافقتك على بدائل دوائية</strong>
                                        <small class="text-dark">اقترحت الصيدلية بدائل لبعض الأدوية غير المتوفرة ويرجى اتخاذ القرار الطبي.</small>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    ${subVisits.map(sv => `
                                        <a href="{{ url('/doctor/visits') }}/${sv.visit_id}" class="btn btn-danger btn-sm fw-bold shadow-sm d-inline-flex align-items-center gap-1">
                                            <i class="fas fa-exchange-alt"></i>
                                            <span>#${sv.queue_number} ${sv.patient_name}</span>
                                        </a>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    } else {
                        subBanner.style.display = 'none';
                    }
                }

                const pendingTbody = document.getElementById('doctor-station-pending-tests');
                if (pendingTbody) {
                    if (pendingTestsList.length === 0) {
                        pendingTbody.innerHTML = `
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-check-circle text-success me-1"></i> {{ __("لا يوجد مراجعون بانتظار نتائج فحوصات أو صرف أدوية حالياً") }}
                                </td>
                            </tr>
                        `;
                    } else {
                        pendingTbody.innerHTML = pendingTestsList.map(item => {
                            const isReady = item.all_ready;
                            const isUnpaid = item.has_unpaid_tests;

                            let readyBadge = isReady 
                                ? '<span class="status-badge status-completed fw-bold" style="background: #bbf7d0; color: #14532d; border-color: #86efac;"><i class="fas fa-check-double text-success me-1"></i> جاهز للمراجعة</span>'
                                : `<span class="status-badge status-pending fw-semibold" style="background: #fef08a; color: #854d0e; border-color: #fde047;"><i class="fas fa-hourglass-half me-1"></i> قيد الإجراء (${item.completed_tests}/${item.total_tests})</span>`;
                            
                            if (isUnpaid) {
                                readyBadge += ' <span class="badge bg-warning text-dark border border-warning ms-1" title="توجد رسوم غير مسددة في الكاشير"><i class="fas fa-exclamation-triangle text-danger me-1"></i>غير مقبوض</span>';
                            }

                            if (item.has_substitution_alert) {
                                readyBadge = '<span class="status-badge bg-danger text-white fw-bold shadow-sm"><i class="fas fa-exchange-alt fa-spin me-1"></i> بديل بانتظار موافقتك</span>';
                            }

                            const testsBadges = (item.tests || []).map(t => {
                                let icon = 'fa-vial';
                                let badgeClass = t.is_ready ? 'bg-success text-white' : 'bg-white text-dark border border-warning';
                                if (t.type === 'radiology') {
                                    icon = 'fa-x-ray';
                                } else if (t.type === 'pharmacy') {
                                    icon = 'fa-prescription';
                                    if (t.has_sub) {
                                        badgeClass = 'bg-danger text-white border border-danger';
                                    } else if (t.status === 'partially_dispensed') {
                                        badgeClass = 'bg-warning text-dark border border-warning';
                                    } else if (t.is_ready) {
                                        badgeClass = 'bg-success text-white';
                                    } else {
                                        badgeClass = 'bg-info text-white';
                                    }
                                }
                                return `<span class="badge ${badgeClass} small me-1 mb-1 px-2 py-1"><i class="fas ${icon} me-1"></i>${t.name}</span>`;
                            }).join(' ');

                            return `
                                <tr class="${item.has_substitution_alert ? 'border border-danger' : (isReady ? 'ready-test-visit' : 'pending-test-visit')}">
                                    <td>
                                        <span class="badge ${isReady ? 'bg-success' : (item.has_substitution_alert ? 'bg-danger' : 'bg-secondary')} text-white fw-bold fs-6">#${item.queue_number}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle" style="${isReady ? 'background: #bbf7d0; color: #166534; border-color: #86efac;' : (item.has_substitution_alert ? 'background: #fee2e2; color: #991b1b;' : '')}">
                                                ${item.patient_name.charAt(0)}
                                            </div>
                                            <div class="ms-2">
                                                <strong class="text-dark fs-6">${item.patient_name}</strong>
                                                ${isReady ? '<span class="badge bg-success ms-1 small">جاهز</span>' : ''}
                                                ${isUnpaid ? '<span class="badge bg-danger ms-1 small">غير مسدد</span>' : ''}
                                                ${item.has_substitution_alert ? '<span class="badge bg-danger ms-1 small">بديل دوائي</span>' : ''}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>${testsBadges}</div>
                                    </td>
                                    <td>
                                        ${readyBadge}
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex gap-1 justify-content-end align-items-center">
                                            ${item.has_substitution_alert ? `
                                                <a href="{{ url('/doctor/visits') }}/${item.visit_id}" class="action-btn btn-danger fw-bold shadow-sm" title="البت في بديل الدواء">
                                                    <i class="fas fa-exchange-alt"></i> مراجعة البديل
                                                </a>
                                            ` : (isReady ? (isUnpaid ? `
                                                <button type="button" class="action-btn btn-warning text-dark fw-bold shadow-sm" onclick="doctorCallResults(${item.visit_id})" title="تنبيه: غير مسدد بالكاشير">
                                                    <i class="fas fa-exclamation-triangle text-danger"></i> غير مسدد
                                                </button>
                                            ` : `
                                                <button type="button" class="action-btn btn-success fw-bold shadow-sm" onclick="doctorCallResults(${item.visit_id})">
                                                    <i class="fas fa-bullhorn"></i> استدعاء
                                                </button>
                                            `) : `
                                                <button type="button" class="action-btn btn-secondary" disabled title="لا يمكن الاستدعاء حتى تكتمل جميع الفحوصات أو الصرف" style="cursor: not-allowed; opacity: 0.65;">
                                                    <i class="fas fa-hourglass-half"></i> بالانتظار
                                                </button>
                                            `)}
                                            <a href="{{ url('/doctor/visits') }}/${item.visit_id}" class="action-btn btn-outline-primary" title="عرض ملف الزيارة">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        }).join('');
                    }
                }
            }
        } catch (e) {
            console.error('Queue sync error:', e);
        }
    }

    async function doctorCallResults(visitId) {
        try {
            const res = await fetch(`${baseUrl}/queue/visit/${visitId}/call-results`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                syncDoctorQueue();
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                }
            } else {
                alert(data.message || 'حدث خطأ أثناء استدعاء المراجع');
            }
        } catch (e) {
            alert('حدث خطأ في الاتصال');
        }
    }

    async function doctorCallNext() {
        try {
            const res = await fetch(`${baseUrl}/queue/doctor/${currentDoctorId}/call-next`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                syncDoctorQueue();
            } else {
                alert(data.message || 'لا يوجد مرضى في الانتظار');
            }
        } catch (e) {
            alert('حدث خطأ في الاتصال');
        }
    }

    async function doctorCallSpecific(appointmentId) {
        try {
            const res = await fetch(`${baseUrl}/queue/appointment/${appointmentId}/call-specific`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                syncDoctorQueue();
            } else {
                alert(data.message || 'حدث خطأ أثناء استدعاء المريض');
            }
        } catch (e) {
            alert('حدث خطأ في الاتصال');
        }
    }

    async function doctorRecall() {
        if (!currentAppointmentId) return;
        try {
            const res = await fetch(`${baseUrl}/queue/appointment/${currentAppointmentId}/recall`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                syncDoctorQueue();
            }
        } catch (e) {
            alert('حدث خطأ في الاتصال');
        }
    }

    async function doctorStartConsultation() {
        if (!currentAppointmentId) return;
        try {
            const res = await fetch(`${baseUrl}/queue/appointment/${currentAppointmentId}/start-consultation`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success && data.redirect_url) {
                window.location.href = data.redirect_url;
            } else {
                alert(data.message || 'حدث خطأ أثناء بدء الكشف');
            }
        } catch (e) {
            alert('حدث خطأ في الاتصال');
        }
    }

    async function doctorSkip() {
        if (!currentAppointmentId) return;
        if (!confirm('هل تريد تأخير دور هذا المريض ونقله لآخر الطابور؟')) return;
        try {
            const res = await fetch(`${baseUrl}/queue/appointment/${currentAppointmentId}/skip`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });
        } catch (e) {
            alert('حدث خطأ في الاتصال');
        }
    }

    function openEmergencyConsultModal(appointmentId, name, notes) {
        const aptId = appointmentId || currentAppointmentId;
        if (!aptId) {
            alert('يرجى استدعاء المريض أولاً');
            return;
        }

        const patientData = window.currentPatientData || {};
        const patientName = name || patientData.name || 'مريض طوارئ';
        const emNotes = notes || patientData.emergency_notes || patientData.appointment_notes || 'لا توجد ملاحظات إضافية مرفقة';

        document.getElementById('modal-patient-name').textContent = patientName;
        document.getElementById('modal-queue-num').textContent = patientData.queue_number ? `#${patientData.queue_number}` : 'طوارئ';
        document.getElementById('modal-emergency-notes').textContent = emNotes;

        const form = document.getElementById('emergencyConsultResponseForm');
        form.action = `${baseUrl}/doctor/consultation/${aptId}/respond`;

        const modalEl = document.getElementById('emergencyConsultResponseModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else if (typeof $ !== 'undefined') {
            $('#emergencyConsultResponseModal').modal('show');
        }
    }

    function openInpatientConsultModal(btn) {
        const surgeryId = btn.getAttribute('data-surgery-id');
        const consultationId = btn.getAttribute('data-consultation-id');
        const patientName = btn.getAttribute('data-patient-name') || '-';
        const room = btn.getAttribute('data-room') || '-';
        const operation = btn.getAttribute('data-operation') || '-';
        const priority = btn.getAttribute('data-priority') || '-';
        const reason = btn.getAttribute('data-reason') || '-';

        document.getElementById('inpatient-modal-patient-name').textContent = patientName;
        document.getElementById('inpatient-modal-room').textContent = room;
        document.getElementById('inpatient-modal-operation').textContent = operation;
        document.getElementById('inpatient-modal-priority').textContent = priority;
        document.getElementById('inpatient-modal-reason').textContent = reason;

        const form = document.getElementById('inpatientConsultResponseForm');
        const respondUrlTemplate = "{{ route('clinical-station.consultations.respond', ['surgery' => '__SURGERY__', 'consultation' => '__CONSULTATION__']) }}";
        form.action = respondUrlTemplate.replace('__SURGERY__', surgeryId).replace('__CONSULTATION__', consultationId);

        const modalEl = document.getElementById('inpatientConsultResponseModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else if (typeof $ !== 'undefined') {
            $('#inpatientConsultResponseModal').modal('show');
        }
    }

    function viewInpatientConsultation(btn) {
        document.getElementById('view-modal-patient').textContent = btn.getAttribute('data-patient') || '-';
        document.getElementById('view-modal-operation').textContent = (btn.getAttribute('data-operation') || '-') + ' • ' + (btn.getAttribute('data-room') || '-');
        document.getElementById('view-modal-reason').textContent = btn.getAttribute('data-reason') || '-';
        document.getElementById('view-modal-response').textContent = btn.getAttribute('data-response') || 'لا يوجد رد مسجل';
        document.getElementById('view-modal-recommendations').textContent = btn.getAttribute('data-recommendations') || 'لا توجد توصيات إضافية';
        document.getElementById('view-modal-doctor').textContent = btn.getAttribute('data-doctor') || '-';
        document.getElementById('view-modal-date').textContent = btn.getAttribute('data-responded-at') || '-';

        const modalEl = document.getElementById('viewInpatientConsultModal');
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else if (typeof $ !== 'undefined') {
            $('#viewInpatientConsultModal').modal('show');
        }
    }

    // Auto poll doctor station every 4s
    setInterval(syncDoctorQueue, 4000);
    syncDoctorQueue();

    if (window.location.hash === '#inpatient-pane' || window.location.search.includes('tab=inpatient')) {
        const inpTab = document.getElementById('inpatient-tab');
        if (inpTab) inpTab.click();
    }
</script>
@endif
@endsection