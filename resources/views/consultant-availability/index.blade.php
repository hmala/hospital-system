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
            <!-- 1. Live Active Consultations Bold Table Card -->
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
                <div class="card-header bg-white border-bottom py-3 px-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span class="spinner-grow spinner-grow-sm text-success" role="status" aria-hidden="true"></span>
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-stethoscope text-primary me-2"></i>العيادات الجارية الآن</h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('consultant-availability.financial-movements') }}" class="btn btn-xs btn-outline-primary px-2 py-1 rounded-pill shadow-xs" style="font-size: 0.75rem;" title="عرض سجل الحركات المالية">
                            <i class="fas fa-chart-line me-1"></i>حركات مالية
                        </a>
                        <a href="{{ route('queue.all.display') }}" target="_blank" class="btn btn-xs btn-outline-info px-2 py-1 rounded-pill shadow-xs" style="font-size: 0.75rem;" title="عرض شاشة الصالة الرئيسية">
                            <i class="fas fa-tv me-1"></i>شاشة الصالة
                        </a>
                        <span class="badge bg-primary text-white px-2 py-1 small">
                            {{ $consultantDoctors->whereIn('current_status', ['in_consultation', 'calling'])->count() }} عيادة جارية الآن
                        </span>
                    </div>
                </div>
                <div class="card-body p-0 overflow-auto" style="max-height: 340px;">
                    @php
                        $activeRunningDocs = $consultantDoctors->whereIn('current_status', ['in_consultation', 'calling']);
                    @endphp
                    @if($activeRunningDocs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr class="text-muted small text-uppercase">
                                        <th>الطبيب والعيادة</th>
                                        <th>المريض الحالي (بالداخل)</th>
                                        <th style="width: 6.5rem;" class="text-center">الحالة</th>
                                        <th style="width: 5rem;" class="text-center">طابور الانتظار</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($activeRunningDocs as $doc)
                                        <tr class="{{ $doc->current_status === 'in_consultation' ? 'table-primary bg-opacity-25' : ($doc->current_status === 'calling' ? 'table-warning bg-opacity-25' : '') }}">
                                            <!-- Doctor Name & Clinic -->
                                            <td>
                                                <div class="fw-bold text-dark fs-6">د. {{ $doc->user->name }}</div>
                                                <small class="text-muted"><i class="fas fa-clinic-medical text-secondary me-1"></i>{{ $doc->department->name ?? 'العيادة' }}</small>
                                            </td>

                                            <!-- Current Patient -->
                                            <td>
                                                @if($doc->current_status === 'in_consultation')
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-primary text-white rounded-pill px-2 fs-6 shadow-xs">#{{ $doc->current_patient_queue ?? '—' }}</span>
                                                        <div>
                                                            <div class="fw-bold text-primary fs-6">{{ $doc->current_patient_name }}</div>
                                                            <small class="text-muted"><i class="fas fa-clock text-info me-1"></i>داخل منذ <strong>{{ $doc->current_since }}</strong></small>
                                                        </div>
                                                    </div>
                                                @elseif($doc->current_status === 'calling')
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-warning text-dark rounded-pill px-2 fs-6 shadow-xs">#{{ $doc->current_patient_queue ?? '—' }}</span>
                                                        <div>
                                                            <div class="fw-bold text-dark fs-6">{{ $doc->current_patient_name }}</div>
                                                            <small class="text-warning fw-bold"><i class="fas fa-bullhorn me-1"></i>تم النداء للشاشة</small>
                                                        </div>
                                                    </div>
                                                @endif
                                            </td>

                                            <!-- Status Badge -->
                                            <td class="text-center">
                                                @if($doc->current_status === 'in_consultation')
                                                    <span class="badge bg-success text-white px-2 py-1 shadow-xs fw-bold">
                                                        <i class="fas fa-user-check me-1"></i> قيد الفحص
                                                    </span>
                                                @elseif($doc->current_status === 'calling')
                                                    <span class="badge bg-warning text-dark px-2 py-1 shadow-xs fw-bold">
                                                        <i class="fas fa-bell me-1"></i> استدعاء
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Waiting Count -->
                                            <td class="text-center">
                                                <span class="badge {{ $doc->waiting_patients_count > 0 ? 'bg-primary' : 'bg-light text-secondary border' }} rounded-pill px-2 py-1 fs-6">
                                                    {{ $doc->waiting_patients_count }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-stethoscope fa-2x mb-2 text-secondary"></i>
                            <p class="mb-0 fw-bold">لا توجد كشوفات جارية حالياً</p>
                            <small class="text-secondary">ستظهر هنا العيادة فور بدء الكشف على مريض أو استدعائه من قبل الطبيب</small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 2. Compact Today's Patient Queue & Payment Actions Card -->
            <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
                <div class="card-header bg-white border-bottom py-3 px-3 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-users-line text-success fs-5"></i>
                        <h6 class="mb-0 fw-bold text-dark">طابور الحجوزات والقبض</h6>
                    </div>
                    @if(isset($todayAppointments))
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 small">
                            {{ $todayAppointments->count() }} مريض
                        </span>
                    @endif
                </div>
                <div class="card-body p-2 overflow-auto" style="max-height: 380px;">
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
                                                @canany(['process consultation payments', 'process payments'])
                                                    <a href="{{ route('cashier.payment.form', $appointment->id) }}" class="btn btn-xs btn-success text-white fw-bold shadow-xs py-1 px-2" style="font-size: 0.75rem;" title="قبض رسوم الكشفية فوراً">
                                                        <i class="fas fa-cash-register me-1"></i> قبض
                                                    </a>
                                                @else
                                                    <span class="badge bg-secondary" style="font-size: 0.72rem;">غير مدفوع</span>
                                                @endcanany
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

            <!-- 3. Compact Ultrasound / Lab Requests Card (If Any) -->
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
                                            @canany(['process medical requests payments', 'process payments'])
                                                <a href="{{ route('cashier.request.payment.form', $req->id) }}" class="btn btn-xs btn-success text-white fw-bold shadow-xs py-1 px-2" style="font-size: 0.75rem;">
                                                    <i class="fas fa-cash-register me-1"></i> قبض
                                                </a>
                                            @else
                                                <span class="badge bg-secondary" style="font-size: 0.7rem;">بانتظار الصندوق</span>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- 4. Compact Today's Follow-up Appointments from Doctors Card -->
            @if(isset($todayScheduledFollowUps) && $todayScheduledFollowUps->count() > 0)
                <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-4 border-info">
                    <div class="card-header bg-info bg-opacity-10 border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-1">
                            <i class="fas fa-calendar-check text-info small"></i>
                            <h6 class="mb-0 fw-bold text-dark small">مراجعات تم جدولتها اليوم من العيادات</h6>
                        </div>
                        <span class="badge bg-info text-dark fw-bold small">{{ $todayScheduledFollowUps->count() }}</span>
                    </div>
                    <div class="card-body p-2 overflow-auto" style="max-height: 280px;">
                        <div class="list-group list-group-flush">
                            @foreach($todayScheduledFollowUps as $fApp)
                                <div class="list-group-item px-2 py-2 border rounded-2 mb-1 bg-light">
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <div class="text-truncate">
                                            <div class="d-flex align-items-center gap-1">
                                                <strong class="text-dark small text-truncate">
                                                    {{ optional($fApp->patient)->name ?? optional(optional($fApp->patient)->user)->name ?? 'مريض' }}
                                                </strong>
                                                <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-1 py-0 rounded-pill" style="font-size: 0.65rem;">
                                                    مراجعة مجانية
                                                </span>
                                            </div>
                                            <small class="text-muted d-block" style="font-size: 0.72rem;">
                                                <i class="fas fa-user-md text-secondary me-1"></i>د. {{ optional(optional($fApp->doctor)->user)->name ?? 'غير محدد' }}
                                                — <i class="fas fa-calendar-day text-primary me-1"></i><strong class="text-primary">{{ $fApp->appointment_date ? \Carbon\Carbon::parse($fApp->appointment_date)->format('Y-m-d') : '—' }}</strong>
                                                ({{ $fApp->appointment_date ? \Carbon\Carbon::parse($fApp->appointment_date)->locale('ar')->dayName : '' }})
                                            </small>
                                            @if($fApp->notes)
                                                <small class="text-secondary d-block text-truncate" style="font-size: 0.68rem;">
                                                    <i class="fas fa-comment-medical text-warning me-1"></i>{{ $fApp->notes }}
                                                </small>
                                            @endif
                                        </div>
                                        <div class="flex-shrink-0">
                                            <a href="{{ route('appointments.print', $fApp->id) }}" target="_blank" class="btn btn-xs btn-primary text-white fw-bold shadow-xs py-1 px-2" style="font-size: 0.75rem;" title="طباعة وصل المراجعة الحراري فوراً للمريض">
                                                <i class="fas fa-print me-1"></i> طباعة الوصل
                                            </a>
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

/* Clean and Simple Design */
body {
    background-color: #f8f9fa !important;
}

.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: none !important;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.btn {
    border-radius: 15px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.btn-success {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545, #fd7e14);
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.availability-text {
    font-size: 1.1rem;
    transition: color 0.3s ease;
}

.doctor-card {
    background: white;
}

.table-responsive {
    border-radius: 1rem;
}

.table td,
.table th {
    padding: 0.75rem 1rem;
    vertical-align: middle;
}

.display-4 {
    font-size: 2.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .display-5 {
        font-size: 2rem;
    }

    .display-4 {
        font-size: 2rem;
    }

    .btn-lg {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }

    .card-body {
        padding: 2rem 1.5rem;
    }
}

@media (max-width: 576px) {
    .btn {
        width: 100%;
        margin-bottom: 1rem;
    }

    .d-flex.gap-3 {
        flex-direction: column;
        align-items: stretch;
    }
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

// تطبيق الفلتر الافتراضي عند فتح الصفحة (المتواجدون اليوم فقط لتقليل الزحام)
document.addEventListener('DOMContentLoaded', function() {
    applyDoctorRowFilters();
});
</script>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    console.log('Consultant Availability Page Loaded');
    console.log('Using simple HTML forms for updates - no JavaScript required!');
});
</script>
@endpush