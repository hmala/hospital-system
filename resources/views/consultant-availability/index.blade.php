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

    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <form method="POST" action="{{ route('consultant-availability.bulk-update') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" name="is_available_today" value="1">
                    <button type="submit" class="btn btn-success btn-lg px-4 py-3" onclick="return confirm('هل أنت متأكد من تفعيل التوفر لجميع الأطباء الاستشاريين؟')">
                        <i class="fas fa-toggle-on fa-2x me-2"></i>
                        <div>
                            <div class="fw-bold">تفعيل الكل</div>
                            <small>اجعل جميع الأطباء متاحين</small>
                        </div>
                    </button>
                </form>
                <form method="POST" action="{{ route('consultant-availability.bulk-update') }}" style="display: inline;">
                    @csrf
                    <input type="hidden" name="is_available_today" value="0">
                    <button type="submit" class="btn btn-danger btn-lg px-4 py-3" onclick="return confirm('هل أنت متأكد من إلغاء التوفر لجميع الأطباء الاستشاريين؟')">
                        <i class="fas fa-toggle-off fa-2x me-2"></i>
                        <div>
                            <div class="fw-bold">إلغاء الكل</div>
                            <small>اجعل جميع الأطباء غير متاحين</small>
                        </div>
                    </button>
                </form>
            </div>
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
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-day me-2"></i>
                        المواعيد المحجوزة اليوم
                        <span class="badge bg-light text-primary ms-2">{{ $todayAppointments->count() }}</span>
                    </h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>الوقت</th>
                                    <th>المريض</th>
                                    <th>الطبيب</th>
                                    <th>المصدر</th>
                                    <th>السبب</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($todayAppointments as $appointment)
                                <tr>
                                    <td>
                                        <strong class="text-primary">{{ $appointment->appointment_date->format('H:i') }}</strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $appointment->patient->user->name ?? 'غير محدد' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $appointment->patient->user->phone ?? '-' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            د. {{ $appointment->doctor->user->name ?? 'غير محدد' }}
                                            <br>
                                            <small class="text-muted">{{ $appointment->doctor->specialization }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($appointment->emergency_id)
                                            <span class="badge bg-danger">
                                                <i class="fas fa-ambulance me-1"></i>
                                                طوارئ
                                            </span>
                                        @else
                                            <span class="badge bg-info">
                                                <i class="fas fa-desktop me-1"></i>
                                                استعلامات
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $appointment->reason ?? '-' }}</small>
                                    </td>
                                    <td>
                                        @if($appointment->status == 'scheduled')
                                            <span class="badge bg-warning">محجوز</span>
                                        @elseif($appointment->status == 'confirmed')
                                            <span class="badge bg-success">مؤكد</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('appointments.convert', $appointment) }}" class="d-inline">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-success" title="إدخال المريض للطبيب">
                                                <i class="fas fa-sign-in-alt me-1"></i>
                                                إدخال
                                            </button>
                                        </form>
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

    <!-- Doctors Grid -->
    <div class="row g-4">
        @forelse($groupedDoctors as $specialization => $doctors)
            <!-- Specialization Header -->
            <div class="col-12">
                <div class="text-center mb-4">
                    <h3 class="text-primary fw-bold">
                        <i class="fas fa-stethoscope me-2"></i>
                        {{ $specialization }}
                        <span class="badge bg-primary ms-2 fs-6">{{ count($doctors) }}</span>
                    </h3>
                </div>
            </div>

            <!-- Doctors Table -->
            <div class="col-12">
                <div class="table-responsive shadow-sm rounded-3 bg-white">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-muted small text-uppercase">
                                <th>الطبيب</th>
                                <th style="width: 8rem;">الحالة</th>
                                <th style="width: 7rem;">تعديل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($doctors as $doctor)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 1.1rem;">
                                                {{ mb_substr($doctor->user->name, 0, 1) }}
                                            </span>
                                            <div>
                                                <div class="fw-semibold">د. {{ $doctor->user->name }}</div>
                                                <div class="text-muted small">{{ $doctor->department->name ?? 'غير محدد' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="availability-text fw-semibold {{ $doctor->is_available_today ? 'text-success' : 'text-danger' }}">
                                            {{ $doctor->is_available_today ? 'متاح' : 'غير متاح' }}
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('consultant-availability.update', $doctor->id) }}" style="display: inline;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_available_today" value="{{ $doctor->is_available_today ? '0' : '1' }}">
                                            <button type="submit" class="btn btn-sm {{ $doctor->is_available_today ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $doctor->is_available_today ? 'اجعل غير متاح' : 'اجعل متاح' }}">
                                                <i class="fas {{ $doctor->is_available_today ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-user-md fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted mb-3">لا توجد أطباء استشاريين</h4>
                    <p class="text-muted">لم يتم العثور على أطباء استشاريين نشطين في النظام</p>
                </div>
            </div>
        @endforelse
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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    console.log('Consultant Availability Page Loaded');
    console.log('Using simple HTML forms for updates - no JavaScript required!');
});
</script>
@endpush