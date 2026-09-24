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
                    لوحة تحكم الطبيب
                </h2>
                <small class="text-muted">مرحباً د. {{ auth()->user()->name }}</small>
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
    <!-- Doctor Tabs Navigation -->
    <ul class="nav nav-tabs mb-4 border-bottom" id="doctorTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active d-flex align-items-center gap-2" id="station-tab" data-bs-toggle="tab" data-bs-target="#station-pane" type="button" role="tab" aria-controls="station-pane" aria-selected="true">
                <i class="fas fa-bullhorn text-primary"></i>
                <span>محطة العيادة والمناداة الحية</span>
                <span class="badge bg-primary text-white rounded-pill px-2 py-1" id="badge-tab-waiting-count">0</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="today-tab" data-bs-toggle="tab" data-bs-target="#today-pane" type="button" role="tab" aria-controls="today-pane" aria-selected="false">
                <i class="fas fa-calendar-day text-info"></i>
                <span>زيارات اليوم</span>
                <span class="badge bg-info text-white rounded-pill px-2 py-1">{{ isset($todayVisits) && is_countable($todayVisits) ? $todayVisits->count() : 0 }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link d-flex align-items-center gap-2" id="incomplete-tab" data-bs-toggle="tab" data-bs-target="#incomplete-pane" type="button" role="tab" aria-controls="incomplete-pane" aria-selected="false">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <span>زيارات معلقة</span>
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
                <span>أرشيف جميع الزيارات</span>
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
                                <span class="badge bg-primary text-white px-2 py-1" id="doctor-station-current-num">الدور: -</span>
                                <strong class="text-dark fs-6" id="doctor-station-current-name">لا يوجد مريض مستدعى</strong>
                                <span class="status-badge status-pending" id="doctor-station-status-badge">العيادة جاهزة</span>
                            </div>
                            <small class="text-muted" id="doctor-station-current-status">اضغط "استدعاء التالي" لمناداة أول مريض في الانتظار</small>
                        </div>
                    </div>

                    <!-- Actions & Screen Link -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn btn-success fw-bold px-3 py-2" onclick="doctorCallNext()" id="btn-call-next">
                            <i class="fas fa-bullhorn me-1"></i> استدعاء التالي
                        </button>
                        <button type="button" class="btn btn-warning fw-bold px-3 py-2" onclick="doctorRecall()" id="btn-recall" style="display: none;">
                            <i class="fas fa-redo me-1"></i> إعادة المناداة
                        </button>
                        <button type="button" class="btn btn-primary fw-bold px-3 py-2" onclick="doctorStartConsultation()" id="btn-start-consult" style="display: none;">
                            <i class="fas fa-sign-in-alt me-1"></i> بدء الكشف
                        </button>
                        <button type="button" class="btn btn-outline-secondary fw-semibold px-3 py-2" onclick="doctorSkip()" id="btn-skip" style="display: none;">
                            <i class="fas fa-forward me-1"></i> تخطي
                        </button>
                        <a href="{{ route('queue.doctor.display', $doctor->id) }}" target="_blank" class="btn btn-outline-primary px-3 py-2">
                            <i class="fas fa-tv me-1"></i> شاشة العرض
                        </a>
                    </div>
                </div>
            </div>

            <!-- 1. جدول طابور الانتظار الأولي (المرضى الجدد) -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0 text-dark fw-bold">
                    <i class="fas fa-users-line me-2 text-primary"></i>
                    طابور الانتظار الأولي (المرضى الجدد)
                </h5>
                <span class="badge bg-primary text-white" id="badge-live-waiting-count">0 منتظر</span>
            </div>
            <div class="table-responsive">
                <table class="table unified-table mb-4">
                    <thead>
                        <tr>
                            <th style="width: 90px;"><i class="fas fa-hashtag me-1"></i>الدور</th>
                            <th><i class="fas fa-user-injured me-2"></i>المريض</th>
                            <th><i class="fas fa-tag me-2"></i>نوع الحجز</th>
                            <th><i class="fas fa-tasks me-2"></i>الحالة</th>
                            <th class="text-end" style="width: 140px;"><i class="fas fa-cogs me-2"></i>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody id="doctor-station-waiting-list">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle text-success me-1"></i> لا يوجد مرضى في طابور الانتظار حالياً
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
                    مراجعو الفحوصات الطبية وصرف الأدوية (الأشعة، المختبر، والصيدلية)
                </h5>
                <span class="badge bg-info text-white" id="badge-pending-tests-count">0 مراجع</span>
            </div>
            <div class="table-responsive">
                <table class="table unified-table mb-4">
                    <thead>
                        <tr>
                            <th style="width: 90px;"><i class="fas fa-hashtag me-1"></i>الدور</th>
                            <th><i class="fas fa-user-injured me-2"></i>المريض</th>
                            <th><i class="fas fa-clipboard-check me-2"></i>الفحوصات والأدوية المطلوبة</th>
                            <th><i class="fas fa-tasks me-2"></i>حالة الإنجاز والصرف</th>
                            <th class="text-end" style="width: 170px;"><i class="fas fa-cogs me-2"></i>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody id="doctor-station-pending-tests">
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle text-success me-1"></i> لا يوجد مراجعون بانتظار نتائج فحوصات أو صرف أدوية حالياً
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
                            <th><i class="fas fa-user-injured me-2"></i>المريض</th>
                            <th><i class="fas fa-clock me-2"></i>التوقيت</th>
                            <th><i class="fas fa-tag me-2"></i>نوع الزيارة</th>
                            <th><i class="fas fa-tasks me-2"></i>الحالة</th>
                            <th><i class="fas fa-cogs me-2"></i>الإجراءات</th>
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
                                                <span class="badge bg-danger ms-2"><i class="fas fa-ambulance"></i> طوارئ</span>
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
                                        <span class="status-badge status-completed"><i class="fas fa-check-circle"></i> مكتملة</span>
                                    @elseif($isCancelled)
                                        <span class="status-badge status-cancelled"><i class="fas fa-times-circle"></i> ملغية</span>
                                    @elseif($isInProgress)
                                        <span class="status-badge" style="background: #e0f2fe; color: #0369a1; border-color: #7dd3fc;"><i class="fas fa-spinner fa-spin"></i> قيد الفحص</span>
                                    @else
                                        <span class="status-badge status-pending"><i class="fas fa-hourglass-half"></i> في الانتظار</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isCompleted)
                                        <a href="{{ route('doctor.visits.show', $visit) }}" class="action-btn btn-outline-primary">
                                            <i class="fas fa-eye"></i> عرض الملف
                                        </a>
                                    @else
                                        <a href="{{ route('doctor.visits.show', $visit) }}" class="action-btn btn-warning">
                                            <i class="fas fa-clipboard-check"></i> متابعة الكشف
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        @else
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fas fa-calendar-day fa-3x text-muted mb-3 d-block"></i>
                                <h5 class="text-muted">لا توجد زيارات مسجلة لليوم حتى الآن</h5>
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
                        <p class="mb-0 small">يرجى المتابعة وإنهاء الفحوصات والوصفات الطبية لغلق ملف الزيارة.</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="table-responsive">
                <table class="table unified-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user-injured me-2"></i>المريض</th>
                            <th><i class="fas fa-calendar me-2"></i>تاريخ الزيارة</th>
                            <th><i class="fas fa-tag me-2"></i>نوع الزيارة</th>
                            <th><i class="fas fa-tasks me-2"></i>الحالة</th>
                            <th><i class="fas fa-cogs me-2"></i>الإجراءات</th>
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
                                    <span class="status-badge status-pending"><i class="fas fa-clock"></i> غير مكتملة</span>
                                </td>
                                <td>
                                    <a href="{{ route('doctor.visits.show', $visit) }}" class="action-btn btn-warning">
                                        <i class="fas fa-clipboard-check"></i> إكمال الفحص
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                                <h5 class="text-muted">ممتاز! لا توجد أي زيارات معلقة من أيام سابقة</h5>
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
                            <th><i class="fas fa-user-injured me-2"></i>المريض</th>
                            <th><i class="fas fa-calendar-alt me-2"></i>التاريخ</th>
                            <th><i class="fas fa-tag me-2"></i>نوع الزيارة</th>
                            <th><i class="fas fa-tasks me-2"></i>الحالة</th>
                            <th><i class="fas fa-cogs me-2"></i>الإجراءات</th>
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
                                                <span class="badge bg-danger ms-2"><i class="fas fa-ambulance"></i> طوارئ</span>
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
                                        <span class="status-badge status-completed"><i class="fas fa-check-circle"></i> مكتملة</span>
                                    @elseif($isCancelled)
                                        <span class="status-badge status-cancelled"><i class="fas fa-times-circle"></i> ملغية</span>
                                    @else
                                        <span class="status-badge status-pending"><i class="fas fa-clock"></i> {{ $visit->status_text ?? $visit->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('doctor.visits.show', $visit) }}" class="action-btn btn-outline-primary">
                                        <i class="fas fa-eye"></i> عرض الملف
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        @else
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <i class="fas fa-archive fa-3x text-muted mb-3 d-block"></i>
                                <h5 class="text-muted">لا توجد زيارات مسجلة في الأرشيف</h5>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

@if(auth()->user()->isDoctor() && isset($doctor))
<script>
    const currentDoctorId = {{ $doctor->id }};
    const baseUrl = "{{ url('/') }}";
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
                const btnSkip = document.getElementById('btn-skip');

                if (cur) {
                    currentAppointmentId = cur.id;
                    document.getElementById('doctor-station-current-num').textContent = `الدور: #${cur.queue_number}`;
                    document.getElementById('doctor-station-current-name').textContent = cur.name;
                    document.getElementById('doctor-station-current-status').textContent = cur.status_text;

                    const statusBadge = document.getElementById('doctor-station-status-badge');
                    if (cur.status === 'calling') {
                        statusBadge.className = 'status-badge status-cancelled';
                        statusBadge.innerHTML = '<i class="fas fa-bullhorn"></i> يتم الاستدعاء الآن';
                    } else {
                        statusBadge.className = 'status-badge status-completed';
                        statusBadge.innerHTML = '<i class="fas fa-user-check"></i> المريض بالداخل';
                    }

                    btnRecall.style.display = 'inline-block';
                    btnStartConsult.style.display = 'inline-block';
                    btnSkip.style.display = 'inline-block';
                } else {
                    currentAppointmentId = null;
                    document.getElementById('doctor-station-current-num').textContent = 'الدور: -';
                    document.getElementById('doctor-station-current-name').textContent = 'لا يوجد مريض مستدعى';
                    document.getElementById('doctor-station-current-status').textContent = 'اضغط "استدعاء التالي" لمناداة أول مريض في الانتظار';
                    
                    const statusBadge = document.getElementById('doctor-station-status-badge');
                    statusBadge.className = 'status-badge status-pending';
                    statusBadge.innerHTML = '<i class="fas fa-clock"></i> العيادة جاهزة';

                    btnRecall.style.display = 'none';
                    btnStartConsult.style.display = 'none';
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
                                    <i class="fas fa-check-circle text-success me-1"></i> لا يوجد مرضى في طابور الانتظار حالياً
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
                                        ${idx === 0 ? `
                                            <button type="button" class="action-btn btn-success" onclick="doctorCallNext()">
                                                <i class="fas fa-bullhorn"></i> استدعاء
                                            </button>
                                        ` : `
                                            <span class="badge bg-light text-secondary border px-2 py-1">دور #${idx + 1}</span>
                                        `}
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
                                    <i class="fas fa-check-circle text-success me-1"></i> لا يوجد مراجعون بانتظار نتائج فحوصات أو صرف أدوية حالياً
                                </td>
                            </tr>
                        `;
                    } else {
                        pendingTbody.innerHTML = pendingTestsList.map(item => {
                            const isReady = item.all_ready;
                            let readyBadge = isReady 
                                ? '<span class="status-badge status-completed fw-bold" style="background: #bbf7d0; color: #14532d; border-color: #86efac;"><i class="fas fa-check-double text-success me-1"></i> جاهز للمراجعة</span>'
                                : `<span class="status-badge status-pending fw-semibold" style="background: #fef08a; color: #854d0e; border-color: #fde047;"><i class="fas fa-hourglass-half me-1"></i> قيد الإجراء (${item.completed_tests}/${item.total_tests})</span>`;
                            
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
                                            ` : (isReady ? `
                                                <button type="button" class="action-btn btn-success fw-bold shadow-sm" onclick="doctorCallResults(${item.visit_id})">
                                                    <i class="fas fa-bullhorn"></i> استدعاء
                                                </button>
                                            ` : `
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
            const data = await res.json();
            if (data.success) {
                syncDoctorQueue();
            }
        } catch (e) {
            alert('حدث خطأ في الاتصال');
        }
    }

    // Auto poll doctor station every 4s
    setInterval(syncDoctorQueue, 4000);
    syncDoctorQueue();
</script>
@endif
@endsection