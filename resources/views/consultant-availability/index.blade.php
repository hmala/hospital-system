<!-- resources/views/consultant-availability/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="background-color: #f8f9fa; min-height: 100vh;">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="text-center">
                <h1 class="display-5 fw-bold text-primary mb-2">
                    <i class="fas fa-calendar-check me-3"></i>
                    توفر الأطباء الاستشاريين
                </h1>
                <p class="lead text-muted">إدارة وتحديث توفر الأطباء الاستشاريين بسهولة</p>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="display-4 fw-bold text-primary mb-2">{{ $consultantDoctors->count() }}</div>
                    <h5 class="text-muted mb-0">إجمالي الأطباء</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="display-4 fw-bold text-success mb-2">{{ $consultantDoctors->where('is_available_today', true)->count() }}</div>
                    <h5 class="text-muted mb-0">متاح اليوم</h5>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center py-4">
                    <div class="display-4 fw-bold text-danger mb-2">{{ $consultantDoctors->where('is_available_today', false)->count() }}</div>
                    <h5 class="text-muted mb-0">غير متاح</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Day Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills justify-content-center" role="tablist">
                @foreach($weekDays as $day)
                    <li class="nav-item me-2" role="presentation">
                        <a href="?day={{ urlencode($day) }}" class="nav-link {{ $day === $selectedDay ? 'active' : '' }}">
                            {{ $day }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>


    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mx-auto" style="max-width: 600px;" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mx-auto" style="max-width: 600px;" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Today's Appointments Section -->
    @if(isset($todayAppointments) && $todayAppointments->count() > 0)
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-day me-2"></i>
                        المواعيد وطابور اليوم
                        <span class="badge bg-light text-primary ms-2">{{ $todayAppointments->count() }}</span>
                    </h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;"># الدور</th>
                                    <th>الوقت</th>
                                    <th>المريض</th>
                                    <th>الطبيب</th>
                                    <th>المصدر</th>
                                    <th>السبب</th>
                                    <th>حالة الدفع</th>
                                    <th>حالة الطابور</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($todayAppointments as $appointment)
                                <tr class="{{ $appointment->status === 'calling' ? 'table-primary' : '' }}">
                                    <td class="text-center">
                                        <span class="badge bg-dark fs-6">{{ $appointment->queue_number ?: $appointment->id }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-clock me-1 text-primary"></i>
                                            {{ $appointment->appointment_date ? $appointment->appointment_date->format('H:i') : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($appointment->patient && $appointment->patient->user)
                                            <div class="fw-bold">{{ $appointment->patient->user->name }}</div>
                                            <small class="text-muted">{{ $appointment->patient->user->phone ?? '' }}</small>
                                        @elseif($appointment->emergency && $appointment->emergency->emergencyPatient)
                                            <div class="fw-bold text-danger">{{ $appointment->emergency->emergencyPatient->name }}</div>
                                            <small class="text-muted">(طوارئ)</small>
                                        @else
                                            <span class="text-muted">مريض غير محدد</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold">د. {{ $appointment->doctor->user->name ?? 'غير محدد' }}</div>
                                        <small class="text-muted">{{ $appointment->doctor->specialization ?? '' }}</small>
                                        @if($appointment->doctor_id)
                                            <div>
                                                <a href="{{ route('queue.doctor.display', $appointment->doctor_id) }}" target="_blank" class="badge bg-light text-primary border text-decoration-none mt-1" title="فتح شاشة التلفاز الخاصة بهذا الطبيب">
                                                    <i class="fas fa-tv me-1"></i> شاشة العيادة
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($appointment->emergency_id)
                                            <span class="badge bg-danger">
                                                <i class="fas fa-ambulance me-1"></i> استشارة طوارئ
                                            </span>
                                        @elseif(str_contains($appointment->reason ?? '', 'سونار') || optional($appointment->doctor)->specialization === 'سونار')
                                            <span class="badge bg-primary text-white">
                                                <i class="fas fa-wave-square me-1"></i> حجز سونار
                                            </span>
                                        @else
                                            <span class="badge bg-info text-dark">
                                                <i class="fas fa-calendar-check me-1"></i> حجز استشارية
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $appointment->reason ?? '-' }}</small>
                                    </td>
                                    <td>
                                        @if($appointment->payment_status === 'paid')
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> مدفوع</span>
                                        @elseif($appointment->payment_status === 'refunded')
                                            <span class="badge bg-secondary"><i class="fas fa-undo me-1"></i> مسترجع</span>
                                        @else
                                            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> غير مدفوع</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $appointment->status_color }}">
                                            {{ $appointment->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 flex-wrap">
                                            @if($appointment->payment_status === 'paid' || $appointment->emergency_id)
                                                <!-- Call / Recall Button -->
                                                @if($appointment->status === 'calling')
                                                    <button type="button" class="btn btn-sm btn-warning text-dark fw-bold" onclick="callPatient({{ $appointment->id }}, this)" title="إعادة مناداة المريض للشاشة الخارجية">
                                                        <i class="fas fa-redo me-1"></i>
                                                        إعادة استدعاء 📢
                                                    </button>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="callPatient({{ $appointment->id }}, this)" title="استدعاء المريض للشاشة الخارجية">
                                                        <i class="fas fa-bullhorn me-1"></i>
                                                        استدعاء
                                                    </button>
                                                @endif

                                                <!-- Convert / Admit Button -->
                                                <form method="POST" action="{{ route('appointments.convert', $appointment) }}" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-success" title="إدخال المريض للطبيب">
                                                        <i class="fas fa-sign-in-alt me-1"></i>
                                                        إدخال
                                                    </button>
                                                </form>
                                            @else
                                                @can('process payments')
                                                    <a href="{{ route('cashier.payment.form', $appointment->id) }}" class="btn btn-sm btn-success text-white fw-bold shadow-xs" title="قبض رسوم الكشفية">
                                                        <i class="fas fa-cash-register me-1"></i>
                                                        قبض الكشفية
                                                    </a>
                                                @else
                                                    <button type="button" class="btn btn-sm btn-secondary" disabled title="يجب دفع الرسوم أولاً">
                                                        <i class="fas fa-sign-in-alt me-1"></i>
                                                        غير مدفوع
                                                    </button>
                                                @endcan
                                            @endif

                                            @if($appointment->canBeCancelled())
                                                <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من إلغاء هذا الموعد؟')" title="إلغاء الحجز">
                                                        <i class="fas fa-times me-1"></i>
                                                        إلغاء
                                                    </button>
                                                </form>
                                            @endif

                                            @if($appointment->doctor_id)
                                                <a href="{{ route('queue.doctor.display', $appointment->doctor_id) }}" target="_blank" class="btn btn-sm btn-outline-info" title="فتح شاشة التلفاز الخاصة بهذا الطبيب">
                                                    <i class="fas fa-tv me-1"></i>
                                                    الشاشة
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
            </div>
        </div>
    </div>
    @endif

    <!-- Pending Clinic Medical Requests Section (فحوصات وسونار العيادات بانتظار السداد) -->
    @if(isset($pendingConsultantRequests) && $pendingConsultantRequests->count() > 0)
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm border-start border-4 border-warning">
                <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-dark fw-bold">
                        <i class="fas fa-wave-square text-primary me-2"></i>
                        طلبات السونار والفحوصات المحولة من العيادات اليوم (بانتظار السداد)
                        <span class="badge bg-warning text-dark ms-2">{{ $pendingConsultantRequests->count() }}</span>
                    </h5>
                    <span class="text-muted small">يتم إدخال المريض فورياً في طابور السونار بعد قبض الرسوم</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 80px;" class="text-center"># الطلب</th>
                                    <th>الوقت</th>
                                    <th>المريض</th>
                                    <th>الطبيب المحيل</th>
                                    <th>نوع الفحص المطلوب</th>
                                    <th>التفاصيل والملاحظات</th>
                                    <th>المبلغ المطلوب</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingConsultantRequests as $req)
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-dark fs-6">#{{ $req->id }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-clock me-1 text-primary"></i>
                                            {{ $req->created_at ? $req->created_at->format('H:i') : '-' }}
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark">
                                        <i class="fas fa-user text-secondary me-1"></i>
                                        {{ optional(optional($req->visit)->patient)->name ?? optional(optional(optional($req->visit)->patient)->user)->name ?? 'مريض غير مسجل' }}
                                    </td>
                                    <td class="text-primary fw-semibold">
                                        <i class="fas fa-user-md me-1"></i>
                                        د. {{ optional(optional($req->doctor)->user)->name ?? (optional(optional($req->visit)->doctor)->user->name ?? 'العيادة') }}
                                    </td>
                                    <td>
                                        @if($req->subtype === 'ultrasound')
                                            <span class="badge bg-info text-dark"><i class="fas fa-wave-square me-1"></i>سونار (Ultrasound)</span>
                                        @elseif($req->type === 'radiology')
                                            <span class="badge bg-primary"><i class="fas fa-x-ray me-1"></i>أشعة</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $req->type }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ Str::limit($req->description ?? $req->notes ?? '-', 50) }}</small>
                                    </td>
                                    <td class="fw-bold text-success fs-6">
                                        {{ number_format($req->total_amount ?? 0) }} د.ع
                                    </td>
                                    <td>
                                        @can('process payments')
                                            <a href="{{ route('cashier.request.payment.form', $req->id) }}" class="btn btn-sm btn-success fw-bold text-white shadow-xs">
                                                <i class="fas fa-cash-register me-1"></i>
                                                قبض الرسوم
                                            </a>
                                        @else
                                            <span class="badge bg-secondary">بانتظار الصندوق</span>
                                        @endcan
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Unified Doctors Section with Quick Filters & Live Search -->
    <div class="row g-4">
        <div class="col-12">
            @if($consultantDoctors->count() > 0)
                <!-- Filter Pills & Live Search Bar -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap flex-lg-nowrap gap-2 align-items-center justify-content-between">
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span class="text-muted fw-bold small me-1"><i class="fas fa-filter me-1 text-primary"></i>فلترة:</span>
                                <button type="button" class="btn btn-sm btn-outline-success active fw-bold doctor-filter-btn px-2 py-1 rounded-pill shadow-xs" data-filter="available" onclick="filterDoctorsTable('available', this)">
                                    <i class="fas fa-user-check me-1"></i>
                                    المتواجدون
                                    <span class="badge bg-success ms-1 rounded-pill" id="countAvailable">{{ $consultantDoctors->where('is_available_today', true)->count() }}</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger fw-bold doctor-filter-btn px-2 py-1 rounded-pill shadow-xs" data-filter="unavailable" onclick="filterDoctorsTable('unavailable', this)">
                                    <i class="fas fa-user-times me-1"></i>
                                    غير المتاحين
                                    <span class="badge bg-danger ms-1 rounded-pill" id="countUnavailable">{{ $consultantDoctors->where('is_available_today', false)->count() }}</span>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary fw-bold doctor-filter-btn px-2 py-1 rounded-pill shadow-xs" data-filter="all" onclick="filterDoctorsTable('all', this)">
                                    <i class="fas fa-users me-1"></i>
                                    الجميع
                                    <span class="badge bg-secondary ms-1 rounded-pill" id="countAll">{{ $consultantDoctors->count() }}</span>
                                </button>
                                <span class="text-muted d-none d-md-inline mx-1">|</span>
                                <a href="{{ route('consultant-availability.financial-movements') }}" class="btn btn-sm btn-outline-primary px-2 py-1 rounded-pill shadow-xs d-inline-flex align-items-center gap-1" title="عرض سجل الحركات المالية">
                                    <i class="fas fa-chart-line"></i>
                                    <span class="fw-semibold">حركات مالية</span>
                                </a>
                                <a href="{{ route('queue.all.display') }}" target="_blank" class="btn btn-sm btn-outline-info px-2 py-1 rounded-pill shadow-xs d-inline-flex align-items-center gap-1" title="عرض طابور كافة العيادات">
                                    <i class="fas fa-tv"></i>
                                    <span class="fw-semibold">شاشة الصالة</span>
                                </a>
                            </div>
                            <div style="width: 220px; min-width: 180px;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white border-end-0 text-muted py-1"><i class="fas fa-search"></i></span>
                                    <input type="text" id="doctorQuickSearch" class="form-control border-start-0 py-1" placeholder="بحث سريع..." oninput="handleDoctorSearch(this.value)">
                                    <button class="btn btn-outline-secondary py-1" type="button" onclick="clearDoctorSearch()" id="clearDoctorSearchBtn" style="display: none;" title="مسح">
                                        <i class="fas fa-times"></i>
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
                                <th style="width: 4rem;">#</th>
                                <th>الطبيب</th>
                                <th>التخصص / العيادة</th>
                                <th style="width: 9rem;" class="text-center">الحالة</th>
                                <th style="width: 10rem;" class="text-center">شاشة الانتظار</th>
                                <th style="width: 8rem;" class="text-center">التوفر اللحظي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($consultantDoctors as $index => $doctor)
                                <tr class="doctor-row" 
                                    id="doctor-row-{{ $doctor->id }}" 
                                    data-status="{{ $doctor->is_available_today ? 'available' : 'unavailable' }}" 
                                    data-search="{{ strtolower($doctor->user->name . ' ' . $doctor->specialization . ' ' . ($doctor->department->name ?? '')) }}">
                                    <td class="text-muted small fw-bold">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 1.1rem; flex-shrink: 0;">
                                                {{ mb_substr($doctor->user->name, 0, 1) }}
                                            </span>
                                            <div>
                                                <div class="fw-bold text-dark fs-6">د. {{ $doctor->user->name }}</div>
                                                <div class="text-muted small">{{ $doctor->department->name ?? 'غير محدد' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-primary border px-2 py-1 fs-6">
                                            {{ $doctor->specialization ?: ($doctor->department->name ?? 'استشاري') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $doctor->is_available_today ? 'bg-success' : 'bg-danger' }} fs-6 px-3 py-2 doctor-status-badge" id="badge-doc-{{ $doctor->id }}">
                                            {{ $doctor->is_available_today ? 'متاح' : 'غير متاح' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('queue.doctor.display', $doctor->id) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="فتح شاشة الانتظار المخصصة للتلفاز">
                                            <i class="fas fa-tv me-1"></i>
                                            شاشة العيادة
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block m-0 p-0" style="min-height: auto;">
                                            <input class="form-check-input doctor-toggle-switch fs-4 m-0" 
                                                   type="checkbox" 
                                                   role="switch" 
                                                   id="switch-doc-{{ $doctor->id }}" 
                                                   data-doctor-id="{{ $doctor->id }}"
                                                   data-doctor-name="{{ $doctor->user->name }}"
                                                   {{ $doctor->is_available_today ? 'checked' : '' }}
                                                   onchange="toggleDoctorAvailability({{ $doctor->id }}, this.checked, this)"
                                                   title="{{ $doctor->is_available_today ? 'انقر لجعله غير متاح فوراً' : 'انقر لجعله متاحاً فوراً' }}"
                                                   style="cursor: pointer;">
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div id="noDoctorsMatchingFilter" class="text-center py-5" style="display: none;">
                        <i class="fas fa-user-slash fa-3x text-muted mb-3 d-block"></i>
                        <h6 class="text-muted fw-bold">لا يوجد أطباء مطابقين لهذا الفلتر أو البحث</h6>
                        <small class="text-secondary">جرّب اختيار فلتر آخر أو تفريغ شريط البحث أعلاه</small>
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
    </div>
</div>

<style>
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
        const baseUrl = "{{ url('/') }}";
        const res = await fetch(`${baseUrl}/queue/tts?text=${encodeURIComponent(text)}`);
        if (res.ok) {
            const blob = await res.blob();
            if (blob && blob.size > 500) {
                const url = URL.createObjectURL(blob);
                const audio = new Audio(url);
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

    const baseUrl = "{{ url('/') }}";
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