<!-- resources/views/consultant-availability/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="background-color: #f8f9fa; min-height: 100vh;">
    <!-- Compact Header & Day Selector Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-calendar-check fs-5"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-dark">توفر الأطباء والاستشارية</h4>
                        <small class="text-muted">متابعة توفر الاستشاريين والعيادات الجارية وطابور المرضى</small>
                    </div>
                </div>

                <!-- Day Tabs -->
                <div class="d-flex align-items-center gap-1 flex-wrap">
                    @foreach($weekDays as $day)
                        <a href="?day={{ urlencode($day) }}" class="btn btn-sm {{ $day === $selectedDay ? 'btn-primary shadow-xs fw-bold' : 'btn-light text-secondary' }} px-3 py-1 rounded-pill">
                            {{ $day }}
                        </a>
                    @endforeach
                </div>

                <!-- Quick Status Stats -->
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 rounded-pill small">
                        <i class="fas fa-user-check me-1"></i> متاح اليوم: <strong>{{ $consultantDoctors->where('is_available_for_view', true)->count() }}</strong>
                    </span>
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 rounded-pill small">
                        <i class="fas fa-user-times me-1"></i> غير متاح: <strong>{{ $consultantDoctors->where('is_available_for_view', false)->count() }}</strong>
                    </span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1 rounded-pill small">
                        <i class="fas fa-users me-1"></i> الإجمالي: <strong>{{ $consultantDoctors->count() }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 1. TOP PRIMARY SECTION: Doctors Availability Table (Right - Compact) + Live Clinics & Queue (Left - Wide & Bold) -->
    <div class="row g-3 mb-4">
        <!-- Main Doctors Availability Table (Right Side - Compact) -->
        <div class="col-xl-5 col-lg-5">
            @if($consultantDoctors->count() > 0)
                <!-- Filter Pills & Live Search Bar -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-2 px-3">
                        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <button type="button" class="btn btn-xs btn-outline-success active fw-bold doctor-filter-btn px-2 py-1 rounded-pill shadow-xs" style="font-size: 0.75rem;" data-filter="available" onclick="filterDoctorsTable('available', this)">
                                    <i class="fas fa-user-check me-1"></i>المتواجدون
                                    <span class="badge bg-success ms-1 rounded-pill" id="countAvailable">{{ $consultantDoctors->where('is_available_for_view', true)->count() }}</span>
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-danger fw-bold doctor-filter-btn px-2 py-1 rounded-pill shadow-xs" style="font-size: 0.75rem;" data-filter="unavailable" onclick="filterDoctorsTable('unavailable', this)">
                                    <i class="fas fa-user-times me-1"></i>غير المتاحين
                                    <span class="badge bg-danger ms-1 rounded-pill" id="countUnavailable">{{ $consultantDoctors->where('is_available_for_view', false)->count() }}</span>
                                </button>
                                <button type="button" class="btn btn-xs btn-outline-secondary fw-bold doctor-filter-btn px-2 py-1 rounded-pill shadow-xs" style="font-size: 0.75rem;" data-filter="all" onclick="filterDoctorsTable('all', this)">
                                    <i class="fas fa-users me-1"></i>الجميع
                                    <span class="badge bg-secondary ms-1 rounded-pill" id="countAll">{{ $consultantDoctors->count() }}</span>
                                </button>
                            </div>
                            <div style="width: 130px;">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="doctorQuickSearch" class="form-control py-0" style="font-size: 0.75rem;" placeholder="بحث..." oninput="handleDoctorSearch(this.value)">
                                    <button class="btn btn-outline-secondary py-0 px-1" type="button" onclick="clearDoctorSearch()" id="clearDoctorSearchBtn" style="display: none;" title="مسح">
                                        <i class="fas fa-times" style="font-size: 0.7rem;"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive shadow-sm rounded-3 bg-white">
                    <table class="table table-hover align-middle mb-0" id="doctorsAvailabilityTable">
                        <thead class="table-light">
                            <tr class="text-muted small text-uppercase">
                                <th style="width: 2.2rem;">#</th>
                                <th>الطبيب والعيادة</th>
                                <th style="width: 4.5rem;" class="text-center">الحالة</th>
                                <th style="width: 5.5rem;" class="text-center" title="تحديد رقم العيادة وشاشة التلفاز المعلقة خارج الباب">الغرفة / الشاشة</th>
                                <th style="width: 3.5rem;" class="text-center">شاشة</th>
                                <th style="width: 4.2rem;" class="text-center">التوفر</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($consultantDoctors as $index => $doctor)
                                <tr class="doctor-row" 
                                    id="doctor-row-{{ $doctor->id }}" 
                                    data-status="{{ $doctor->is_available_for_view ? 'available' : 'unavailable' }}" 
                                    data-search="{{ strtolower($doctor->user->name . ' ' . $doctor->specialization . ' ' . ($doctor->department->name ?? '')) }}">
                                    <td class="text-muted small fw-bold">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem; flex-shrink: 0;">
                                                {{ mb_substr($doctor->user->name, 0, 1) }}
                                            </span>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.88rem;">د. {{ $doctor->user->name }}</div>
                                                <small class="text-muted d-block" style="font-size: 0.72rem;">
                                                    {{ $doctor->specialization ?: ($doctor->department->name ?? 'استشاري') }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($doctor->is_available_for_view)
                                            <span class="badge bg-success small px-2 py-1 doctor-status-badge" style="font-size: 0.72rem;" id="badge-doc-{{ $doctor->id }}">متاح</span>
                                        @elseif($doctor->is_working_selected_day)
                                            <span class="badge bg-danger small px-2 py-1 doctor-status-badge" style="font-size: 0.72rem;" id="badge-doc-{{ $doctor->id }}">غير متاح</span>
                                        @else
                                            <span class="badge bg-secondary small px-2 py-1 doctor-status-badge" style="font-size: 0.72rem;" id="badge-doc-{{ $doctor->id }}">غير مجدول</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $assignedRoom = $doctor->current_room ?: optional($doctor->department)->room_number;
                                        @endphp
                                        <div class="dropdown d-inline-block">
                                            <button type="button" 
                                                    class="btn btn-sm room-badge-btn rounded-pill px-3 py-1 fw-bold d-inline-flex align-items-center gap-1 dropdown-toggle {{ $assignedRoom ? 'btn-success-soft' : 'btn-light text-secondary border-dashed' }}" 
                                                    id="room-dropdown-btn-{{ $doctor->id }}" 
                                                    data-bs-toggle="dropdown" 
                                                    data-bs-auto-close="outside"
                                                    aria-expanded="false"
                                                    title="تخصيص شاشة العيادة لهذا الطبيب">
                                                <i class="fas {{ $assignedRoom ? 'fa-door-open text-success' : 'fa-plus-circle text-muted' }}" id="room-icon-{{ $doctor->id }}"></i>
                                                <span id="room-text-{{ $doctor->id }}">{{ $assignedRoom ? 'عيادة ' . $assignedRoom : 'تعيين عيادة' }}</span>
                                            </button>

                                            <div class="dropdown-menu p-3 shadow-lg border-0 rounded-4 room-grid-menu text-end" aria-labelledby="room-dropdown-btn-{{ $doctor->id }}" style="min-width: 275px; z-index: 1060;">
                                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                                    <span class="small fw-bold text-dark"><i class="fas fa-tv text-primary me-1"></i> اختر شاشة العيادة:</span>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary small px-2 py-1">د. {{ $doctor->user->name }}</span>
                                                </div>

                                                <!-- Room Grid Buttons 1 to 15 -->
                                                <div class="room-buttons-grid mb-2">
                                                    @for($r = 1; $r <= 15; $r++)
                                                        @php
                                                            $isCurrent = ((string)$assignedRoom === (string)$r);
                                                        @endphp
                                                        <button type="button" 
                                                                class="btn btn-xs room-cell-btn {{ $isCurrent ? 'btn-success active fw-bold text-white shadow-xs' : 'btn-outline-primary' }}"
                                                                data-room="{{ $r }}"
                                                                onclick="selectDoctorRoom({{ $doctor->id }}, '{{ $r }}', this)"
                                                                title="تعيين عيادة {{ $r }}">
                                                            🚪 عيادة {{ $r }}
                                                        </button>
                                                    @endfor
                                                </div>

                                                <div class="border-top pt-2">
                                                    <button type="button" 
                                                            class="btn btn-xs btn-outline-danger w-100 rounded-pill py-1 fw-bold" 
                                                            onclick="selectDoctorRoom({{ $doctor->id }}, '', this)">
                                                        <i class="fas fa-times-circle me-1"></i> تفريغ / إلغاء تعيين العيادة
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ $assignedRoom ? route('queue.room', $assignedRoom) : route('queue.doctor.display', $doctor->id) }}" 
                                           id="tv-link-{{ $doctor->id }}"
                                           target="_blank" 
                                           class="btn btn-xs rounded-circle p-0 d-inline-flex align-items-center justify-content-center {{ $assignedRoom ? 'btn-primary shadow-xs' : 'btn-outline-secondary' }}" 
                                           style="width: 28px; height: 28px; font-size: 0.75rem;" 
                                           title="{{ $assignedRoom ? 'معاينة شاشة عيادة ' . $assignedRoom : 'معاينة شاشة الطبيب' }}">
                                            <i class="fas fa-tv"></i>
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block m-0 p-0" style="min-height: auto;">
                                            <input class="form-check-input doctor-toggle-switch fs-5 m-0" 
                                                   type="checkbox" 
                                                   role="switch" 
                                                   id="switch-doc-{{ $doctor->id }}" 
                                                   data-doctor-id="{{ $doctor->id }}"
                                                   data-doctor-name="{{ $doctor->user->name }}"
                                                   {{ $doctor->is_available_for_view ? 'checked' : '' }}
                                                   onchange="toggleDoctorAvailability({{ $doctor->id }}, this.checked, this)"
                                                   title="{{ $doctor->is_available_for_view ? 'انقر لجعله غير متاح' : 'انقر لجعله متاحاً' }}"
                                                   style="cursor: pointer;">
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div id="noDoctorsMatchingFilter" class="text-center py-4" style="display: none;">
                        <i class="fas fa-user-slash fa-2x text-muted mb-2 d-block"></i>
                        <h6 class="text-muted fw-bold small">لا يوجد أطباء مطابقين</h6>
                    </div>
                </div>
            @else
                <div class="text-center py-5 bg-white rounded-3 shadow-sm">
                    <i class="fas fa-user-md fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted mb-3">لا توجد أطباء استشاريين</h4>
                    <p class="text-muted">لم يتم العثور على أطباء استشاريين نشطين في النظام لهذا اليوم</p>
                </div>
            @endif
        </div>

        <!-- Live Clinic Consultation Monitor & Compact Patient Queue (Left Side Panel - Wide & Bold Table) -->
        <div class="col-xl-7 col-lg-7">
            
            <!-- Top Section: Active Running Clinics + Today's Follow-up Appointments Side-by-Side -->
            <div class="row g-2 mb-3">
                <!-- 1. Live Active Consultations Card -->
                <div class="col-lg-6 col-12">
                    <div class="card border-0 shadow-sm rounded-3 bg-white h-100 d-flex flex-column">
                        <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-1 text-truncate">
                                <span class="spinner-grow spinner-grow-sm text-success flex-shrink-0" role="status" aria-hidden="true" style="width: 0.65rem; height: 0.65rem;"></span>
                                <h6 class="mb-0 fw-bold text-dark small text-truncate"><i class="fas fa-stethoscope text-primary me-1"></i>العيادات الجارية الآن</h6>
                            </div>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                <a href="{{ route('consultant-availability.financial-movements') }}" class="btn btn-xs btn-outline-primary px-1 py-0 rounded-pill" style="font-size: 0.68rem;" title="عرض الحركات المالية">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                                <a href="{{ route('queue.all.display') }}" target="_blank" class="btn btn-xs btn-outline-info px-1 py-0 rounded-pill" style="font-size: 0.68rem;" title="شاشة الصالة">
                                    <i class="fas fa-tv"></i>
                                </a>
                                <span class="badge bg-primary text-white px-2 py-0 rounded-pill" id="runningClinicsCountBadge" style="font-size: 0.68rem;">
                                    {{ $consultantDoctors->whereIn('current_status', ['in_consultation', 'calling'])->count() }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-0 overflow-auto flex-grow-1" id="runningClinicsContainer" style="height: 300px; max-height: 300px;">
                            @php
                                $activeRunningDocs = $consultantDoctors->whereIn('current_status', ['in_consultation', 'calling']);
                            @endphp
                            @if($activeRunningDocs->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                        <thead class="table-light sticky-top">
                                            <tr class="text-muted small text-uppercase" style="font-size: 0.72rem;">
                                                <th>الطبيب والعيادة</th>
                                                <th>المريض بالداخل</th>
                                                <th class="text-center">الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($activeRunningDocs as $doc)
                                                <tr class="{{ $doc->current_status === 'in_consultation' ? 'table-primary bg-opacity-25' : ($doc->current_status === 'calling' ? 'table-warning bg-opacity-25' : '') }}">
                                                    <!-- Doctor Name & Clinic -->
                                                    <td class="text-truncate" style="max-width: 140px;">
                                                        <div class="fw-bold text-dark text-truncate">د. {{ $doc->user->name }}</div>
                                                        <small class="text-muted d-block text-truncate" style="font-size: 0.7rem;"><i class="fas fa-clinic-medical text-secondary me-1"></i>{{ $doc->department->name ?? 'العيادة' }}</small>
                                                    </td>

                                                    <!-- Current Patient -->
                                                    <td>
                                                        @if($doc->current_status === 'in_consultation')
                                                            <div class="d-flex align-items-center gap-1">
                                                                <span class="badge bg-primary text-white rounded-pill px-1" style="font-size: 0.7rem;">#{{ $doc->current_patient_queue ?? '—' }}</span>
                                                                <div class="text-truncate">
                                                                    <div class="fw-bold text-primary small text-truncate">{{ $doc->current_patient_name }}</div>
                                                                    <small class="text-muted d-block" style="font-size: 0.65rem;"><i class="fas fa-clock text-info me-1"></i>منذ {{ $doc->current_since }}</small>
                                                                </div>
                                                            </div>
                                                        @elseif($doc->current_status === 'calling')
                                                            <div class="d-flex align-items-center gap-1">
                                                                <span class="badge bg-warning text-dark rounded-pill px-1" style="font-size: 0.7rem;">#{{ $doc->current_patient_queue ?? '—' }}</span>
                                                                <div class="text-truncate">
                                                                    <div class="fw-bold text-dark small text-truncate">{{ $doc->current_patient_name }}</div>
                                                                    <small class="text-warning fw-bold d-block" style="font-size: 0.65rem;"><i class="fas fa-bullhorn me-1"></i>نداء للشاشة</small>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <!-- Status Badge -->
                                                    <td class="text-center">
                                                        @if($doc->current_status === 'in_consultation')
                                                            <span class="badge bg-success text-white px-1 py-0 shadow-xs" style="font-size: 0.65rem;">
                                                                فحص
                                                            </span>
                                                        @elseif($doc->current_status === 'calling')
                                                            <span class="badge bg-warning text-dark px-1 py-0 shadow-xs" style="font-size: 0.65rem;">
                                                                نداء
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-stethoscope fa-2x mb-2 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-bold small">لا توجد كشوفات جارية الآن</p>
                                    <small class="text-muted" style="font-size: 0.7rem;">ستظهر العيادة هنا فور استدعاء المريض</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 2. Today's Follow-up Appointments from Doctors Card with Sub-tabs -->
                <div class="col-lg-6 col-12">
                    <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-4 border-info h-100 d-flex flex-column">
                        <div class="card-header bg-info bg-opacity-10 border-bottom py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center gap-1 text-truncate">
                                    <i class="fas fa-calendar-check text-info small"></i>
                                    <h6 class="mb-0 fw-bold text-dark small text-truncate">مراجعات العيادات والاستشارية</h6>
                                </div>
                                <span class="badge bg-info text-dark fw-bold rounded-pill" id="allFollowUpsCountBadge" style="font-size: 0.68rem;">
                                    {{ (isset($pendingPrintFollowUps) ? $pendingPrintFollowUps->count() : 0) + (isset($printedTodayFollowUps) ? $printedTodayFollowUps->count() : 0) }} اليوم
                                </span>
                            </div>
                            <!-- Sub-tabs Navigation -->
                            <div class="nav nav-pills nav-fill gap-1" id="followUpTabs" role="tablist">
                                <button type="button" class="nav-link active py-0 px-2 rounded-pill fw-bold border-0 d-flex align-items-center justify-content-center gap-1" id="tabFuPending" onclick="switchFollowUpTab('pending')" style="font-size: 0.72rem; min-height: 24px;">
                                    <span>⏳ بانتظار الطباعة</span>
                                    <span class="badge bg-warning text-dark rounded-pill px-1" id="pendingFollowUpsCountBadge" style="font-size: 0.65rem;">
                                        {{ isset($pendingPrintFollowUps) ? $pendingPrintFollowUps->count() : 0 }}
                                    </span>
                                </button>
                                <button type="button" class="nav-link py-0 px-2 rounded-pill fw-bold border-0 d-flex align-items-center justify-content-center gap-1" id="tabFuPrinted" onclick="switchFollowUpTab('printed')" style="font-size: 0.72rem; min-height: 24px;">
                                    <span>✔️ المطبوعة اليوم</span>
                                    <span class="badge bg-success text-white rounded-pill px-1" id="printedFollowUpsCountBadge" style="font-size: 0.65rem;">
                                        {{ isset($printedTodayFollowUps) ? $printedTodayFollowUps->count() : 0 }}
                                    </span>
                                </button>
                                <button type="button" class="nav-link py-0 px-2 rounded-pill fw-bold border-0 d-flex align-items-center justify-content-center gap-1" id="tabFuArchive" onclick="switchFollowUpTab('archive')" style="font-size: 0.72rem; min-height: 24px;">
                                    <span>🗄️ كافة الأيام</span>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-2 overflow-auto flex-grow-1" id="followUpsMainContainer" style="height: 300px; max-height: 300px;">
                            <!-- Pane 1: Pending Print Follow-ups -->
                            <div id="fuPanePending">
                                @if(isset($pendingPrintFollowUps) && $pendingPrintFollowUps->count() > 0)
                                    <div class="list-group list-group-flush" id="fuPendingListContainer">
                                        @foreach($pendingPrintFollowUps as $fApp)
                                            <div class="list-group-item px-2 py-2 border border-warning border-opacity-50 rounded-2 mb-1 bg-warning bg-opacity-10 shadow-2xs">
                                                <div class="d-flex justify-content-between align-items-center gap-2">
                                                    <div class="text-truncate">
                                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                                            <strong class="text-dark small text-truncate" style="font-size: 0.8rem;">
                                                                {{ optional($fApp->patient)->name ?? optional(optional($fApp->patient)->user)->name ?? 'مريض' }}
                                                            </strong>
                                                            <span class="badge bg-success text-white px-1 py-0 rounded-pill" style="font-size: 0.60rem;">
                                                                مراجعة مجانية
                                                            </span>
                                                            <span class="badge bg-warning text-dark px-1 py-0 rounded-pill" style="font-size: 0.60rem;">
                                                                <i class="fas fa-clock me-1"></i>بانتظار الطباعة
                                                            </span>
                                                        </div>
                                                        <small class="text-muted d-block text-truncate" style="font-size: 0.7rem;">
                                                            <i class="fas fa-user-md text-secondary me-1"></i>د. {{ optional(optional($fApp->doctor)->user)->name ?? 'غير محدد' }}
                                                            — <i class="fas fa-calendar-day text-primary me-1"></i><strong class="text-primary">{{ $fApp->appointment_date ? \Carbon\Carbon::parse($fApp->appointment_date)->format('Y-m-d') : '—' }}</strong>
                                                            ({{ $fApp->appointment_date ? \Carbon\Carbon::parse($fApp->appointment_date)->locale('ar')->dayName : '' }})
                                                        </small>
                                                        @if($fApp->notes)
                                                            <small class="text-secondary d-block text-truncate" style="font-size: 0.65rem;">
                                                                <i class="fas fa-comment-medical text-warning me-1"></i>{{ $fApp->notes }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <a href="{{ route('appointments.print', $fApp->id) }}" target="_blank" class="btn btn-xs btn-primary text-white fw-bold shadow-xs py-1 px-2" style="font-size: 0.72rem;" title="طباعة وصل المراجعة الحراري فوراً للمريض">
                                                            <i class="fas fa-print me-1"></i> طباعة الوصل
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-5 text-muted" id="fuPendingEmptyState">
                                        <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-75"></i>
                                        <p class="mb-0 fw-bold small text-dark">لا توجد مراجعات بانتظار الطباعة</p>
                                        <small class="text-muted" style="font-size: 0.7rem;">أي مراجعة يحددها الطبيب ستظهر هنا فوراً وتختفي بمجرد طباعتها</small>
                                    </div>
                                @endif
                            </div>

                            <!-- Pane 2: Printed Today Follow-ups -->
                            <div id="fuPanePrinted" style="display: none;">
                                @if(isset($printedTodayFollowUps) && $printedTodayFollowUps->count() > 0)
                                    <div class="list-group list-group-flush" id="fuPrintedListContainer">
                                        @foreach($printedTodayFollowUps as $fApp)
                                            <div class="list-group-item px-2 py-2 border rounded-2 mb-1 bg-light shadow-2xs">
                                                <div class="d-flex justify-content-between align-items-center gap-2">
                                                    <div class="text-truncate">
                                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                                            <strong class="text-dark small text-truncate" style="font-size: 0.8rem;">
                                                                {{ optional($fApp->patient)->name ?? optional(optional($fApp->patient)->user)->name ?? 'مريض' }}
                                                            </strong>
                                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-1 py-0 rounded-pill" style="font-size: 0.60rem;">
                                                                مراجعة مجانية
                                                            </span>
                                                            <span class="badge bg-success text-white px-1 py-0 rounded-pill" style="font-size: 0.60rem;">
                                                                <i class="fas fa-check-double me-1"></i>تمت الطباعة {{ $fApp->printed_at ? '(' . $fApp->printed_at->format('H:i') . ')' : '' }}
                                                            </span>
                                                        </div>
                                                        <small class="text-muted d-block text-truncate" style="font-size: 0.7rem;">
                                                            <i class="fas fa-user-md text-secondary me-1"></i>د. {{ optional(optional($fApp->doctor)->user)->name ?? 'غير محدد' }}
                                                            — <i class="fas fa-calendar-day text-primary me-1"></i><strong class="text-primary">{{ $fApp->appointment_date ? \Carbon\Carbon::parse($fApp->appointment_date)->format('Y-m-d') : '—' }}</strong>
                                                            ({{ $fApp->appointment_date ? \Carbon\Carbon::parse($fApp->appointment_date)->locale('ar')->dayName : '' }})
                                                        </small>
                                                        @if($fApp->notes)
                                                            <small class="text-secondary d-block text-truncate" style="font-size: 0.65rem;">
                                                                <i class="fas fa-comment-medical text-warning me-1"></i>{{ $fApp->notes }}
                                                            </small>
                                                        @endif
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <a href="{{ route('appointments.print', $fApp->id) }}" target="_blank" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size: 0.72rem;" title="إعادة طباعة وصل المراجعة للمريض">
                                                            <i class="fas fa-redo me-1"></i> إعادة طباعة
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-5 text-muted" id="fuPrintedEmptyState">
                                        <i class="fas fa-print fa-2x mb-2 text-secondary opacity-50"></i>
                                        <p class="mb-0 fw-bold small">لا توجد مراجعات مطبوعة اليوم بعد</p>
                                        <small class="text-muted" style="font-size: 0.7rem;">ستنتقل المراجعات المطبوعة إلى هنا فور طباعتها</small>
                                    </div>
                                @endif
                            </div>

                            <!-- Pane 3: Archive & All Days Search -->
                            <div id="fuPaneArchive" style="display: none;">
                                <div class="p-1 mb-2 bg-light rounded-2 border">
                                    <div class="row g-1">
                                        <div class="col-7">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0 py-0 px-2"><i class="fas fa-search text-muted" style="font-size: 0.7rem;"></i></span>
                                                <input type="text" id="fuArchiveSearchInput" class="form-control form-control-sm border-start-0 py-0" placeholder="بحث باسم المريض أو الهاتف أو رقم الإضبارة..." style="font-size: 0.75rem;" oninput="debounceFuArchiveSearch()">
                                            </div>
                                        </div>
                                        <div class="col-5">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white border-end-0 py-0 px-1"><i class="fas fa-calendar-alt text-muted" style="font-size: 0.7rem;"></i></span>
                                                <input type="date" id="fuArchiveDateInput" class="form-control form-control-sm border-start-0 py-0" style="font-size: 0.75rem;" onchange="executeFuArchiveSearch()">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="fuArchiveResultsContainer">
                                    <div class="text-center py-4 text-muted" id="fuArchiveInitialHint">
                                        <i class="fas fa-archive fa-2x mb-2 text-primary opacity-50"></i>
                                        <p class="mb-0 fw-bold small text-dark">سجل مراجعات ومواعيد كافة الأيام</p>
                                        <small class="text-muted" style="font-size: 0.7rem;">اكتب اسم المريض أو اختر تاريخاً للبحث السريع وإعادة الطباعة</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Section: Today's Patient Queue & Pending Tests -->
            <!-- 3. Compact Today's Patient Queue & Payment Actions Card -->
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
                <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-users-line text-success fs-6"></i>
                        <h6 class="mb-0 fw-bold text-dark small">طابور الحجوزات والقبض</h6>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 small" id="todayAppointmentsCountBadge">
                        {{ isset($todayAppointments) ? $todayAppointments->count() : 0 }} مريض
                    </span>
                </div>
                <div class="card-body p-2 overflow-auto" id="todayAppointmentsContainer" style="max-height: 340px;">
                    @if(isset($todayAppointments) && $todayAppointments->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($todayAppointments as $appointment)
                                <div class="list-group-item px-2 py-2 border rounded-2 mb-2 {{ $appointment->status === 'calling' ? 'border-primary bg-primary bg-opacity-10' : ($appointment->payment_status === 'paid' ? 'border-light bg-light' : 'border-warning bg-warning bg-opacity-10') }}">
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <!-- Patient Info -->
                                        <div class="d-flex align-items-center gap-2 text-truncate">
                                            <span class="badge {{ $appointment->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }} rounded-pill px-2 flex-shrink-0" style="font-size: 0.8rem;">
                                                #{{ $appointment->queue_number ?: $appointment->id }}
                                            </span>
                                            <div class="text-truncate">
                                                <div class="fw-bold text-dark small text-truncate">
                                                    @if($appointment->patient && $appointment->patient->user)
                                                        {{ $appointment->patient->user->name }}
                                                    @elseif($appointment->emergency && $appointment->emergency->emergencyPatient)
                                                        {{ $appointment->emergency->emergencyPatient->name }} <span class="badge bg-danger p-1" style="font-size: 0.65rem;">طوارئ</span>
                                                    @else
                                                        مريض غير محدد
                                                    @endif
                                                </div>
                                                <small class="text-muted d-block" style="font-size: 0.72rem;">
                                                    د. {{ $appointment->doctor->user->name ?? 'غير محدد' }}
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Quick Action Button -->
                                        <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                            @if($appointment->payment_status === 'paid' || $appointment->emergency_id)
                                                @if($appointment->status === 'calling')
                                                    <button type="button" class="btn btn-xs btn-warning text-dark fw-bold py-1 px-2" style="font-size: 0.75rem;" onclick="callPatient({{ $appointment->id }}, this)" title="إعادة النداء للشاشة الخارجية">
                                                        <i class="fas fa-redo me-1"></i> إعادة نداء
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-xs btn-primary text-white py-1 px-2" style="font-size: 0.75rem;" onclick="callPatient({{ $appointment->id }}, this)" title="استدعاء للشاشة الخارجية">
                                                        <i class="fas fa-bullhorn me-1"></i> استدعاء
                                                    </button>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary" style="font-size: 0.72rem;">غير مدفوع</span>
                                            @endif

                                            @if($appointment->is_free_recheck)
                                                <span class="badge bg-success text-white px-1 py-0 rounded-pill" style="font-size: 0.65rem;">مراجعة مجانية</span>
                                            @endif

                                            <!-- زر طباعة الوصل الحراري -->
                                            <a href="{{ route('appointments.print', $appointment->id) }}" target="_blank" class="btn btn-xs btn-outline-dark py-1 px-2" style="font-size: 0.75rem;" title="طباعة وصل الموعد والمراجعة للمريض">
                                                <i class="fas fa-print"></i>
                                            </a>

                                            @if($appointment->canBeCancelled())
                                                <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" class="d-inline m-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2" style="font-size: 0.75rem;" onclick="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟')" title="إلغاء الحجز">
                                                        <i class="fas fa-times me-1"></i> إلغاء
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-calendar-check fa-2x mb-2 text-secondary"></i>
                            <p class="mb-0 small">لا توجد حجوزات مسجلة اليوم</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 4. Compact Ultrasound / Lab Requests Card (If Any) -->
            @if(isset($pendingConsultantRequests) && $pendingConsultantRequests->count() > 0)
                <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-4 border-warning mb-3">
                    <div class="card-header bg-warning bg-opacity-10 border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-1">
                            <i class="fas fa-wave-square text-primary small"></i>
                            <h6 class="mb-0 fw-bold text-dark small">فحوصات وسونار بانتظار السداد</h6>
                        </div>
                        <span class="badge bg-warning text-dark small">{{ $pendingConsultantRequests->count() }}</span>
                    </div>
                    <div class="card-body p-2 overflow-auto" style="max-height: 240px;">
                        <div class="list-group list-group-flush">
                            @foreach($pendingConsultantRequests as $req)
                                <div class="list-group-item px-2 py-2 border rounded-2 mb-1 bg-light">
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <div class="text-truncate">
                                            <div class="fw-bold text-dark small text-truncate">
                                                {{ optional(optional($req->visit)->patient)->name ?? optional(optional(optional($req->visit)->patient)->user)->name ?? 'مريض' }}
                                            </div>
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">
                                                {{ $req->subtype === 'ultrasound' ? 'سونار' : ($req->type === 'radiology' ? 'أشعة' : $req->type) }} — <strong class="text-success">{{ number_format($req->total_amount ?? 0) }} د.ع</strong>
                                            </small>
                                        </div>
                                        <div>
                                            @canany(['process medical requests payments', 'process consultation payments', 'process payments'])
                                                <a href="{{ route('cashier.request.payment.form', $req->id) }}" class="btn btn-xs btn-success text-white fw-bold shadow-xs py-1 px-2" style="font-size: 0.75rem;">
                                                    <i class="fas fa-cash-register me-1"></i> قبض
                                                </a>
                                            @else
                                                <span class="badge bg-secondary" style="font-size: 0.7rem;">بانتظار الصندوق</span>
                                            @endcanany
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
/* Modern Room Badge & Interactive Grid Styling (Option 1) */
.room-badge-btn {
    font-size: 0.78rem;
    transition: all 0.2s ease;
    cursor: pointer;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.room-badge-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.12);
}
.btn-success-soft {
    background-color: #ecfdf5 !important;
    color: #047857 !important;
    border: 1px solid #10b981 !important;
}
.btn-success-soft:hover {
    background-color: #d1fae5 !important;
    border-color: #059669 !important;
    color: #065f46 !important;
}
.border-dashed {
    border: 1px dashed #cbd5e1 !important;
    background-color: #f8fafc !important;
}
.border-dashed:hover {
    background-color: #f1f5f9 !important;
    border-color: #94a3b8 !important;
}
.room-grid-menu {
    border-radius: 16px !important;
    box-shadow: 0 14px 35px rgba(0,0,0,0.18) !important;
}
.room-buttons-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
}
.room-cell-btn {
    font-size: 0.74rem;
    padding: 6px 4px;
    border-radius: 8px;
    font-weight: 700;
    transition: all 0.15s ease;
}
.room-cell-btn:hover {
    transform: translateY(-1px);
}

</style>

<script>
let globalAudioCtx = null;

function getAudioContext() {
    if (!globalAudioCtx) {
        const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
        if (AudioCtxClass) {
            globalAudioCtx = new AudioCtxClass();
        }
    }
    if (globalAudioCtx && globalAudioCtx.state === 'suspended') {
        globalAudioCtx.resume();
    }
    return globalAudioCtx;
}

function playDeskChime() {
    try {
        const ctx = getAudioContext();
        if (!ctx) return;
        const now = ctx.currentTime;

        const osc1 = ctx.createOscillator();
        const gain1 = ctx.createGain();
        osc1.type = 'sine';
        osc1.frequency.setValueAtTime(880, now);
        gain1.gain.setValueAtTime(0.35, now);
        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.75);
        osc1.connect(gain1);
        gain1.connect(ctx.destination);
        osc1.start(now);
        osc1.stop(now + 0.75);

        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.setValueAtTime(587.33, now + 0.3);
        gain2.gain.setValueAtTime(0.4, now + 0.3);
        gain2.gain.exponentialRampToValueAtTime(0.001, now + 1.3);
        osc2.connect(gain2);
        gain2.connect(ctx.destination);
        osc2.start(now + 0.3);
        osc2.stop(now + 1.3);
    } catch(e) {}
}

async function playDeskVoice(text) {
    try {
        const baseUrl = window.location.origin + (window.location.pathname.startsWith('/hearmz') ? '/hearmz' : '');
        const res = await fetch(`${baseUrl}/queue/tts?text=${encodeURIComponent(text)}`);
        if (res.ok) {
            const blob = await res.blob();
            if (blob && blob.size > 500) {
                const url = URL.createObjectURL(blob);
                audio = new Audio(url);
                audio.play().catch(() => {});
                audio.onended = () => URL.revokeObjectURL(url);
                return;
            }
        }
    } catch(e) {}

    // Fallback to browser SpeechSynthesis
    if ('speechSynthesis' in window) {
        try {
            if (window.speechSynthesis.paused) window.speechSynthesis.resume();
            const u = new SpeechSynthesisUtterance(text);
            u.lang = 'ar-SA';
            u.rate = 0.85;
            window.speechSynthesis.speak(u);
        } catch(e) {}
    }
}

function callPatient(appointmentId, btnElement) {
    const btn = btnElement || (window.event ? window.event.target.closest('button') : null);
    
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري النداء...';
    }

    const baseUrl = window.location.origin + (window.location.pathname.startsWith('/hearmz') ? '/hearmz' : '');
    fetch(`${baseUrl}/queue/appointment/${appointmentId}/recall`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
        return res.json();
    })
    .then(async data => {
        if (data.success) {
            // تشغيل الجرس والنداء الصوتي فوراً على جهاز الاستقبال أيضاً
            playDeskChime();
            setTimeout(() => {
                const text = data.queue_number
                    ? `المراجع ${data.patient_name}، دورك رقم ${data.queue_number}، تفضل لعيادة دكتور ${data.doctor_name || ''}.`
                    : `المراجع ${data.patient_name}، تفضل لعيادة دكتور ${data.doctor_name || ''}.`;
                playDeskVoice(text);
            }, 850);

            if (btn) {
                btn.className = 'btn btn-sm btn-warning text-dark fw-bold';
                btn.innerHTML = '<i class="fas fa-redo me-1"></i> إعادة استدعاء 📢';
                btn.disabled = false;
                btn.title = 'إعادة مناداة المريض للشاشة الخارجية';
            }
        } else {
            alert(data.message || 'حدث خطأ أثناء الاستدعاء');
            if (btn) {
                btn.className = 'btn btn-sm btn-primary';
                btn.innerHTML = '<i class="fas fa-bullhorn me-1"></i> استدعاء';
                btn.disabled = false;
            }
        }
    })
    .catch(err => {
        alert('حدث خطأ في الاتصال بالخادم');
        if (btn) {
            btn.className = 'btn btn-sm btn-primary';
            btn.innerHTML = '<i class="fas fa-bullhorn me-1"></i> استدعاء';
            btn.disabled = false;
        }
    });
}

// ===== نظام الفلترة والبحث اللحظي وسويتشات التبديل للأطباء الاستشاريين =====
let currentDoctorFilter = 'available'; // العرض الافتراضي: المتواجدون اليوم فقط لتقليل الزحام

function filterDoctorsTable(filterType, btnElement) {
    currentDoctorFilter = filterType;
    document.querySelectorAll('.doctor-filter-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.classList.remove('shadow-sm');
    });
    if (btnElement) {
        btnElement.classList.add('active');
        btnElement.classList.add('shadow-sm');
    }
    applyDoctorRowFilters();
}

function handleDoctorSearch(query) {
    const clearBtn = document.getElementById('clearDoctorSearchBtn');
    if (clearBtn) clearBtn.style.display = query.trim() ? 'block' : 'none';
    applyDoctorRowFilters();
}

function clearDoctorSearch() {
    const input = document.getElementById('doctorQuickSearch');
    if (input) {
        input.value = '';
        const clearBtn = document.getElementById('clearDoctorSearchBtn');
        if (clearBtn) clearBtn.style.display = 'none';
        applyDoctorRowFilters();
        input.focus();
    }
}

function applyDoctorRowFilters() {
    const query = (document.getElementById('doctorQuickSearch')?.value || '').trim().toLowerCase();
    const rows = document.querySelectorAll('.doctor-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowStatus = row.getAttribute('data-status');
        const rowSearch = row.getAttribute('data-search') || '';

        const matchesStatus = (currentDoctorFilter === 'all') || (rowStatus === currentDoctorFilter);
        const matchesQuery = !query || rowSearch.includes(query);

        if (matchesStatus && matchesQuery) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const noMatchesEl = document.getElementById('noDoctorsMatchingFilter');
    if (noMatchesEl) {
        noMatchesEl.style.display = (visibleCount === 0) ? 'block' : 'none';
    }
}

function toggleDoctorAvailability(doctorId, isAvailable, switchEl) {
    switchEl.disabled = true;
    const badgeEl = document.getElementById(`badge-doc-${doctorId}`);
    const rowEl = document.getElementById(`doctor-row-${doctorId}`);

    fetch(`{{ url('consultant-availability') }}/${doctorId}`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            day: '{{ $selectedDay }}',
            is_available_today: isAvailable ? 1 : 0
        })
    })
    .then(res => res.json())
    .then(data => {
        switchEl.disabled = false;
        if (data.success) {
            // تحديث الشارة
            if (badgeEl) {
                badgeEl.className = `badge ${isAvailable ? 'bg-success' : 'bg-danger'} fs-6 px-3 py-2 doctor-status-badge`;
                badgeEl.textContent = isAvailable ? 'متاح' : 'غير متاح';
            }
            // تحديث حالة الصف
            if (rowEl) {
                rowEl.setAttribute('data-status', isAvailable ? 'available' : 'unavailable');
            }
            switchEl.title = isAvailable ? 'انقر لجعله غير متاح فوراً' : 'انقر لجعله متاحاً فوراً';

            // تحديث عدادات الفلاتر
            updateAvailabilityCounts();

            // تطبيق الفلتر فوراً
            applyDoctorRowFilters();

            showQuickToast(data.message || (isAvailable ? 'تم تفعيل توفر الطبيب ✅' : 'تم إلغاء توفر الطبيب ❌'), isAvailable ? 'success' : 'warning');
        } else {
            switchEl.checked = !isAvailable;
            alert(data.message || 'فشل التحديث');
        }
    })
    .catch(err => {
        switchEl.disabled = false;
        switchEl.checked = !isAvailable;
        console.error(err);
        alert('حدث خطأ في الاتصال بالخادم');
    });
}

function updateAvailabilityCounts() {
    const rows = document.querySelectorAll('.doctor-row');
    let avail = 0;
    let unavail = 0;
    rows.forEach(r => {
        if (r.getAttribute('data-status') === 'available') avail++;
        else unavail++;
    });
    const elAvail = document.getElementById('countAvailable');
    const elUnavail = document.getElementById('countUnavailable');
    const elAll = document.getElementById('countAll');
    if (elAvail) elAvail.textContent = avail;
    if (elUnavail) elUnavail.textContent = unavail;
    if (elAll) elAll.textContent = rows.length;
}

function selectDoctorRoom(doctorId, room, btnEl) {
    const baseUrl = @json(url('/'));
    
    // Close dropdown safely
    const dropdownToggle = document.getElementById(`room-dropdown-btn-${doctorId}`);
    if (dropdownToggle && window.bootstrap && bootstrap.Dropdown) {
        const bsDropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
        if (bsDropdown) {
            bsDropdown.hide();
        }
    }

    // Optimistic UI state for the badge button
    const textEl = document.getElementById(`room-text-${doctorId}`);
    const iconEl = document.getElementById(`room-icon-${doctorId}`);
    if (textEl && iconEl && dropdownToggle) {
        if (room) {
            textEl.textContent = `عيادة ${room}`;
            iconEl.className = 'fas fa-door-open text-success me-1';
            dropdownToggle.className = 'btn btn-sm room-badge-btn rounded-pill px-3 py-1 fw-bold d-inline-flex align-items-center gap-1 dropdown-toggle btn-success-soft';
        } else {
            textEl.textContent = 'تعيين عيادة';
            iconEl.className = 'fas fa-plus-circle text-muted me-1';
            dropdownToggle.className = 'btn btn-sm room-badge-btn rounded-pill px-3 py-1 fw-bold d-inline-flex align-items-center gap-1 dropdown-toggle btn-light text-secondary border-dashed';
        }
    }

    // Update active state in grid buttons
    const parentMenu = dropdownToggle ? dropdownToggle.nextElementSibling : null;
    if (parentMenu) {
        parentMenu.querySelectorAll('.room-cell-btn').forEach(b => {
            if (room && b.getAttribute('data-room') === String(room)) {
                b.className = 'btn btn-xs room-cell-btn btn-success active fw-bold text-white shadow-xs';
            } else {
                b.className = 'btn btn-xs room-cell-btn btn-outline-primary';
            }
        });
    }

    // Update TV preview link
    const tvLink = document.getElementById(`tv-link-${doctorId}`);
    if (tvLink) {
        if (room) {
            tvLink.href = `${baseUrl}/queue/room/${room}`;
            tvLink.className = 'btn btn-xs rounded-circle p-0 d-inline-flex align-items-center justify-content-center btn-primary shadow-xs';
            tvLink.title = `معاينة شاشة عيادة ${room}`;
        } else {
            tvLink.href = `${baseUrl}/queue/doctor/${doctorId}`;
            tvLink.className = 'btn btn-xs rounded-circle p-0 d-inline-flex align-items-center justify-content-center btn-outline-secondary';
            tvLink.title = `معاينة شاشة الطبيب`;
        }
    }

    // Server AJAX Request
    fetch(`${baseUrl}/queue/assign-room`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ doctor_id: doctorId, room: room })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showQuickToast(data.message, 'success');
        } else {
            alert(data.message || 'فشل تعيين الغرفة');
        }
    })
    .catch(err => {
        console.error('Error assigning room:', err);
        alert('حدث خطأ أثناء تعيين الغرفة');
    });
}

function showQuickToast(msg, type = 'success') {
    let toast = document.getElementById('liveAvailabilityToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'liveAvailabilityToast';
        toast.style.cssText = 'position:fixed;bottom:24px;left:24px;z-index:9999;padding:12px 22px;border-radius:12px;font-weight:bold;box-shadow:0 8px 24px rgba(0,0,0,0.18);transition:all 0.3s ease;transform:translateY(100px);opacity:0;display:flex;align-items:center;gap:8px;font-size:0.95rem;';
        document.body.appendChild(toast);
    }
    toast.className = type === 'success' ? 'bg-success text-white' : 'bg-dark text-white border border-secondary';
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle text-white' : 'info-circle text-warning'} fs-5"></i><span>${msg}</span>`;
    toast.style.transform = 'translateY(0)';
    toast.style.opacity = '1';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => {
        toast.style.transform = 'translateY(100px)';
        toast.style.opacity = '0';
    }, 2800);
}

// تبديل تبويبات المراجعات (بانتظار الطباعة / المطبوعة اليوم / كافة الأيام)
let currentFollowUpTab = 'pending';
function switchFollowUpTab(tab) {
    currentFollowUpTab = tab;
    
    // أزرار التبويبات
    document.querySelectorAll('#followUpTabs .nav-link').forEach(btn => btn.classList.remove('active', 'bg-white', 'shadow-xs'));
    
    // الحاويات
    const pPending = document.getElementById('fuPanePending');
    const pPrinted = document.getElementById('fuPanePrinted');
    const pArchive = document.getElementById('fuPaneArchive');
    
    if (pPending) pPending.style.display = 'none';
    if (pPrinted) pPrinted.style.display = 'none';
    if (pArchive) pArchive.style.display = 'none';
    
    if (tab === 'pending') {
        const btn = document.getElementById('tabFuPending');
        if (btn) btn.classList.add('active');
        if (pPending) pPending.style.display = 'block';
    } else if (tab === 'printed') {
        const btn = document.getElementById('tabFuPrinted');
        if (btn) btn.classList.add('active');
        if (pPrinted) pPrinted.style.display = 'block';
    } else if (tab === 'archive') {
        const btn = document.getElementById('tabFuArchive');
        if (btn) btn.classList.add('active');
        if (pArchive) pArchive.style.display = 'block';
        executeFuArchiveSearch();
    }
}

// البحث في سجل المراجعات لكافة الأيام
let fuArchiveDebounceTimer = null;
function debounceFuArchiveSearch() {
    clearTimeout(fuArchiveDebounceTimer);
    fuArchiveDebounceTimer = setTimeout(executeFuArchiveSearch, 300);
}

function executeFuArchiveSearch() {
    const searchInput = document.getElementById('fuArchiveSearchInput');
    const dateInput = document.getElementById('fuArchiveDateInput');
    const resultsContainer = document.getElementById('fuArchiveResultsContainer');
    
    const query = searchInput ? searchInput.value.trim() : '';
    const date = dateInput ? dateInput.value : '';
    
    if (!resultsContainer) return;
    
    resultsContainer.innerHTML = `
        <div class="text-center py-4 text-muted">
            <span class="spinner-border spinner-border-sm text-primary me-1" role="status"></span>
            <span class="small">جاري البحث في سجل المراجعات...</span>
        </div>
    `;
    
    const url = new URL('{{ route('consultant-availability.search-follow-ups') }}', window.location.origin);
    if (query) url.searchParams.append('q', query);
    if (date) url.searchParams.append('date', date);
    
    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.results && data.results.length > 0) {
            let listHtml = '<div class="list-group list-group-flush">';
            data.results.forEach(item => {
                const notesHtml = item.notes 
                    ? `<small class="text-secondary d-block text-truncate" style="font-size: 0.65rem;"><i class="fas fa-comment-medical text-warning me-1"></i>${item.notes}</small>`
                    : '';
                const printBadge = item.is_printed 
                    ? `<span class="badge bg-success text-white px-1 py-0 rounded-pill" style="font-size: 0.60rem;"><i class="fas fa-check-double me-1"></i>مطبوع (${item.print_count}x)</span>`
                    : `<span class="badge bg-warning text-dark px-1 py-0 rounded-pill" style="font-size: 0.60rem;"><i class="fas fa-clock me-1"></i>غير مطبوع</span>`;
                const btnClass = item.is_printed ? 'btn-outline-secondary' : 'btn-primary text-white fw-bold shadow-xs';
                const btnIcon = item.is_printed ? 'fa-redo' : 'fa-print';
                const btnText = item.is_printed ? 'إعادة طباعة' : 'طباعة الوصل';
                
                listHtml += `
                    <div class="list-group-item px-2 py-2 border rounded-2 mb-1 bg-light shadow-2xs">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div class="text-truncate">
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <strong class="text-dark small text-truncate" style="font-size: 0.8rem;">${item.patient_name}</strong>
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-1 py-0 rounded-pill" style="font-size: 0.60rem;">مراجعة</span>
                                    ${printBadge}
                                </div>
                                <small class="text-muted d-block text-truncate" style="font-size: 0.7rem;">
                                    <i class="fas fa-user-md text-secondary me-1"></i>د. ${item.doctor_name}
                                    — <i class="fas fa-calendar-day text-primary me-1"></i><strong class="text-primary">${item.appointment_date}</strong>
                                    (${item.day_name})
                                </small>
                                ${notesHtml}
                            </div>
                            <div class="flex-shrink-0">
                                <a href="${item.print_url}" target="_blank" class="btn btn-xs ${btnClass} py-1 px-2" style="font-size: 0.72rem;" title="${btnText}">
                                    <i class="fas ${btnIcon} me-1"></i> ${btnText}
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            });
            listHtml += '</div>';
            resultsContainer.innerHTML = listHtml;
        } else {
            resultsContainer.innerHTML = `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-search fa-2x mb-2 text-secondary opacity-50"></i>
                    <p class="mb-0 fw-bold small text-dark">لا توجد مراجعات مطابقة للبحث</p>
                    <small class="text-muted" style="font-size: 0.7rem;">جرّب كتابة اسم آخر أو مسح فلتر التاريخ</small>
                </div>
            `;
        }
    })
    .catch(err => {
        resultsContainer.innerHTML = `
            <div class="text-center py-4 text-danger small">
                <i class="fas fa-exclamation-triangle me-1"></i> تعذر جلب سجل المراجعات
            </div>
        `;
    });
}

// التحديث التلقائي الفوري (Auto-Refresh Polling) كل 4 ثوانٍ للعيادات الجارية والمراجعات المجدولة
function pollLiveConsultantStatus() {
    fetch('{{ route('consultant-availability.live-status') }}', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // 1. تحديث العيادات الجارية
            const runningCountBadge = document.getElementById('runningClinicsCountBadge');
            const runningContainer = document.getElementById('runningClinicsContainer');
            if (runningCountBadge) runningCountBadge.textContent = data.running_clinics_count;
            if (runningContainer) {
                if (data.running_clinics && data.running_clinics.length > 0) {
                    let rowsHtml = '';
                    data.running_clinics.forEach(doc => {
                        const isConsult = (doc.status === 'in_consultation');
                        const trClass = isConsult ? 'table-primary bg-opacity-25' : 'table-warning bg-opacity-25';
                        const badgeHtml = isConsult 
                            ? `<span class="badge bg-success text-white px-1 py-0 shadow-xs" style="font-size: 0.65rem;">فحص</span>`
                            : `<span class="badge bg-warning text-dark px-1 py-0 shadow-xs" style="font-size: 0.65rem;">نداء</span>`;
                        const queueBadgeClass = isConsult ? 'bg-primary text-white' : 'bg-warning text-dark';
                        const subText = isConsult
                            ? `<small class="text-muted d-block" style="font-size: 0.65rem;"><i class="fas fa-clock text-info me-1"></i>منذ ${doc.current_since}</small>`
                            : `<small class="text-warning fw-bold d-block" style="font-size: 0.65rem;"><i class="fas fa-bullhorn me-1"></i>نداء للشاشة</small>`;

                        rowsHtml += `
                            <tr class="${trClass}">
                                <td class="text-truncate" style="max-width: 140px;">
                                    <div class="fw-bold text-dark text-truncate">د. ${doc.doctor_name}</div>
                                    <small class="text-muted d-block text-truncate" style="font-size: 0.7rem;"><i class="fas fa-clinic-medical text-secondary me-1"></i>${doc.department_name}</small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-1">
                                        <span class="badge ${queueBadgeClass} rounded-pill px-1" style="font-size: 0.7rem;">#${doc.patient_queue || '—'}</span>
                                        <div class="text-truncate">
                                            <div class="fw-bold text-primary small text-truncate">${doc.patient_name}</div>
                                            ${subText}
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">${badgeHtml}</td>
                            </tr>
                        `;
                    });

                    runningContainer.innerHTML = `
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                                <thead class="table-light sticky-top">
                                    <tr class="text-muted small text-uppercase" style="font-size: 0.72rem;">
                                        <th>الطبيب والعيادة</th>
                                        <th>المريض بالداخل</th>
                                        <th class="text-center">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rowsHtml}
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    runningContainer.innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-stethoscope fa-2x mb-2 text-secondary opacity-50"></i>
                            <p class="mb-0 fw-bold small">لا توجد كشوفات جارية الآن</p>
                            <small class="text-muted" style="font-size: 0.7rem;">ستظهر العيادة هنا فور استدعاء المريض</small>
                        </div>
                    `;
                }
            }

            // 2. تحديث العدادات العلوية للمراجعات
            const allCountBadge = document.getElementById('allFollowUpsCountBadge');
            const pendingCountBadge = document.getElementById('pendingFollowUpsCountBadge');
            const printedCountBadge = document.getElementById('printedFollowUpsCountBadge');
            
            if (allCountBadge) allCountBadge.textContent = `${data.all_today_count} اليوم`;
            if (pendingCountBadge) pendingCountBadge.textContent = data.pending_follow_ups_count;
            if (printedCountBadge) printedCountBadge.textContent = data.printed_today_follow_ups_count;

            // 3. تحديث قائمة "بانتظار الطباعة" (Pane 1)
            const pPending = document.getElementById('fuPanePending');
            if (pPending) {
                if (data.pending_follow_ups && data.pending_follow_ups.length > 0) {
                    let pListHtml = '<div class="list-group list-group-flush" id="fuPendingListContainer">';
                    data.pending_follow_ups.forEach(fApp => {
                        const notesHtml = fApp.notes 
                            ? `<small class="text-secondary d-block text-truncate" style="font-size: 0.65rem;"><i class="fas fa-comment-medical text-warning me-1"></i>${fApp.notes}</small>`
                            : '';
                        pListHtml += `
                            <div class="list-group-item px-2 py-2 border border-warning border-opacity-50 rounded-2 mb-1 bg-warning bg-opacity-10 shadow-2xs">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div class="text-truncate">
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <strong class="text-dark small text-truncate" style="font-size: 0.8rem;">${fApp.patient_name}</strong>
                                            <span class="badge bg-success text-white px-1 py-0 rounded-pill" style="font-size: 0.60rem;">مراجعة مجانية</span>
                                            <span class="badge bg-warning text-dark px-1 py-0 rounded-pill" style="font-size: 0.60rem;"><i class="fas fa-clock me-1"></i>بانتظار الطباعة</span>
                                        </div>
                                        <small class="text-muted d-block text-truncate" style="font-size: 0.7rem;">
                                            <i class="fas fa-user-md text-secondary me-1"></i>د. ${fApp.doctor_name}
                                            — <i class="fas fa-calendar-day text-primary me-1"></i><strong class="text-primary">${fApp.appointment_date}</strong>
                                            (${fApp.day_name})
                                        </small>
                                        ${notesHtml}
                                    </div>
                                    <div class="flex-shrink-0">
                                        <a href="${fApp.print_url}" target="_blank" class="btn btn-xs btn-primary text-white fw-bold shadow-xs py-1 px-2" style="font-size: 0.72rem;" title="طباعة وصل المراجعة الحراري فوراً للمريض">
                                            <i class="fas fa-print me-1"></i> طباعة الوصل
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    pListHtml += '</div>';
                    pPending.innerHTML = pListHtml;
                } else {
                    pPending.innerHTML = `
                        <div class="text-center py-5 text-muted" id="fuPendingEmptyState">
                            <i class="fas fa-check-circle fa-2x mb-2 text-success opacity-75"></i>
                            <p class="mb-0 fw-bold small text-dark">لا توجد مراجعات بانتظار الطباعة</p>
                            <small class="text-muted" style="font-size: 0.7rem;">أي مراجعة يحددها الطبيب ستظهر هنا فوراً وتختفي بمجرد طباعتها</small>
                        </div>
                    `;
                }
            }

            // 4. تحديث قائمة "المطبوعة اليوم" (Pane 2)
            const pPrinted = document.getElementById('fuPanePrinted');
            if (pPrinted) {
                if (data.printed_today_follow_ups && data.printed_today_follow_ups.length > 0) {
                    let prListHtml = '<div class="list-group list-group-flush" id="fuPrintedListContainer">';
                    data.printed_today_follow_ups.forEach(fApp => {
                        const notesHtml = fApp.notes 
                            ? `<small class="text-secondary d-block text-truncate" style="font-size: 0.65rem;"><i class="fas fa-comment-medical text-warning me-1"></i>${fApp.notes}</small>`
                            : '';
                        prListHtml += `
                            <div class="list-group-item px-2 py-2 border rounded-2 mb-1 bg-light shadow-2xs">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div class="text-truncate">
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <strong class="text-dark small text-truncate" style="font-size: 0.8rem;">${fApp.patient_name}</strong>
                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-1 py-0 rounded-pill" style="font-size: 0.60rem;">مراجعة مجانية</span>
                                            <span class="badge bg-success text-white px-1 py-0 rounded-pill" style="font-size: 0.60rem;"><i class="fas fa-check-double me-1"></i>تمت الطباعة ${fApp.printed_time ? '(' + fApp.printed_time + ')' : ''}</span>
                                        </div>
                                        <small class="text-muted d-block text-truncate" style="font-size: 0.7rem;">
                                            <i class="fas fa-user-md text-secondary me-1"></i>د. ${fApp.doctor_name}
                                            — <i class="fas fa-calendar-day text-primary me-1"></i><strong class="text-primary">${fApp.appointment_date}</strong>
                                            (${fApp.day_name})
                                        </small>
                                        ${notesHtml}
                                    </div>
                                    <div class="flex-shrink-0">
                                        <a href="${fApp.print_url}" target="_blank" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size: 0.72rem;" title="إعادة طباعة وصل المراجعة للمريض">
                                            <i class="fas fa-redo me-1"></i> إعادة طباعة
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    prListHtml += '</div>';
                    pPrinted.innerHTML = prListHtml;
                } else {
                    pPrinted.innerHTML = `
                        <div class="text-center py-5 text-muted" id="fuPrintedEmptyState">
                            <i class="fas fa-print fa-2x mb-2 text-secondary opacity-50"></i>
                            <p class="mb-0 fw-bold small">لا توجد مراجعات مطبوعة اليوم بعد</p>
                            <small class="text-muted" style="font-size: 0.7rem;">ستنتقل المراجعات المطبوعة إلى هنا فور طباعتها</small>
                        </div>
                    `;
                }
            }
            // 5. تحديث طابور الحجوزات والقبض لليوم (Today's Queue & Cashier)
            const apptCountBadge = document.getElementById('todayAppointmentsCountBadge');
            const apptContainer = document.getElementById('todayAppointmentsContainer');
            
            if (apptCountBadge && data.today_appointments_count !== undefined) {
                apptCountBadge.textContent = `${data.today_appointments_count} مريض`;
            }

            if (apptContainer && data.today_appointments) {
                const csrfToken = '{{ csrf_token() }}';
                if (data.today_appointments.length > 0) {
                    let apptHtml = '<div class="list-group list-group-flush">';
                    data.today_appointments.forEach(app => {
                        const isCalling = (app.status === 'calling');
                        const isPaid = (app.payment_status === 'paid');
                        const itemClass = isCalling 
                            ? 'border-primary bg-primary bg-opacity-10' 
                            : (isPaid ? 'border-light bg-light' : 'border-warning bg-warning bg-opacity-10');
                        const queueBadgeClass = isPaid ? 'bg-success' : 'bg-warning text-dark';
                        const emergencyBadge = app.is_emergency ? `<span class="badge bg-danger p-1" style="font-size: 0.65rem;">طوارئ</span>` : '';
                        const recheckBadge = app.is_free_recheck ? `<span class="badge bg-success text-white px-1 py-0 rounded-pill" style="font-size: 0.65rem;">مراجعة مجانية</span>` : '';

                        let actionBtnHtml = '';
                        if (isPaid || app.is_emergency) {
                            if (isCalling) {
                                actionBtnHtml = `
                                    <button type="button" class="btn btn-xs btn-warning text-dark fw-bold py-1 px-2" style="font-size: 0.75rem;" onclick="callPatient(${app.id}, this)" title="إعادة النداء للشاشة الخارجية">
                                        <i class="fas fa-redo me-1"></i> إعادة نداء
                                    </button>
                                `;
                            } else {
                                actionBtnHtml = `
                                    <button type="button" class="btn btn-xs btn-primary text-white py-1 px-2" style="font-size: 0.75rem;" onclick="callPatient(${app.id}, this)" title="استدعاء للشاشة الخارجية">
                                        <i class="fas fa-bullhorn me-1"></i> استدعاء
                                    </button>
                                `;
                            }
                        } else {
                            if (app.can_process_payments) {
                                actionBtnHtml = `
                                    <a href="${app.payment_url}" class="btn btn-xs btn-success text-white fw-bold shadow-xs py-1 px-2" style="font-size: 0.75rem;" title="قبض رسوم الكشفية فوراً">
                                        <i class="fas fa-cash-register me-1"></i> قبض
                                    </a>
                                `;
                            } else {
                                actionBtnHtml = `<span class="badge bg-secondary" style="font-size: 0.72rem;">غير مدفوع</span>`;
                            }
                        }

                        let cancelBtnHtml = '';
                        if (app.can_cancel) {
                            cancelBtnHtml = `
                                <form method="POST" action="${app.cancel_url}" class="d-inline m-0">
                                    <input type="hidden" name="_token" value="${csrfToken}">
                                    <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2" style="font-size: 0.75rem;" onclick="return confirm('هل أنت متأكد من إلغاء هذا الحجز؟')" title="إلغاء الحجز">
                                        <i class="fas fa-times me-1"></i> إلغاء
                                    </button>
                                </form>
                            `;
                        }

                        apptHtml += `
                            <div class="list-group-item px-2 py-2 border rounded-2 mb-2 ${itemClass}">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <div class="d-flex align-items-center gap-2 text-truncate">
                                        <span class="badge ${queueBadgeClass} rounded-pill px-2 flex-shrink-0" style="font-size: 0.8rem;">
                                            #${app.queue_number}
                                        </span>
                                        <div class="text-truncate">
                                            <div class="fw-bold text-dark small text-truncate">
                                                ${app.patient_name} ${emergencyBadge}
                                            </div>
                                            <small class="text-muted d-block" style="font-size: 0.72rem;">
                                                د. ${app.doctor_name}
                                            </small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                        ${actionBtnHtml}
                                        ${recheckBadge}
                                        <a href="${app.print_url}" target="_blank" class="btn btn-xs btn-outline-dark py-1 px-2" style="font-size: 0.75rem;" title="طباعة وصل الموعد والمراجعة للمريض">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        ${cancelBtnHtml}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    apptHtml += '</div>';
                    apptContainer.innerHTML = apptHtml;
                } else {
                    apptContainer.innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-calendar-check fa-2x mb-2 text-secondary"></i>
                            <p class="mb-0 small">لا توجد حجوزات مسجلة اليوم</p>
                        </div>
                    `;
                }
            }
        }
    })
    .catch(err => {
        console.debug('Background poll error:', err);
    });
}

// تطبيق الفلتر الافتراضي عند فتح الصفحة وتشغيل التحديث التلقائي
document.addEventListener('DOMContentLoaded', function() {
    applyDoctorRowFilters();
    setInterval(pollLiveConsultantStatus, 4000);
});
</script>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    console.log('Consultant Availability Page Loaded');
});
</script>
@endpush