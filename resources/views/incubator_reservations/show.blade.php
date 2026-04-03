@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-file-medical me-2 text-primary"></i>
                        تفاصيل حجز الحاضنة
                    </h2>
                    <p class="text-muted">عرض كامل معلومات الحجز</p>
                </div>
                <div>
                    <a href="{{ route('incubator-reservations.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- معلومات الحجز -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-baby me-2"></i>
                        معلومات الطفل والحجز
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">اسم الطفل</h6>
                            <p class="fs-5 fw-bold">{{ $incubatorReservation->baby_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">الحالة</h6>
                            <p>
                                <span class="badge bg-{{ $incubatorReservation->status_color }} fs-6">
                                    {{ $incubatorReservation->status_name }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <h6 class="text-muted">
                                <i class="fas fa-weight me-1"></i>
                                وزن الولادة
                            </h6>
                            <p>{{ $incubatorReservation->birth_weight ?: 'غير محدد' }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                عمر الحمل
                            </h6>
                            <p>{{ $incubatorReservation->gestational_age ?: 'غير محدد' }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">
                                <i class="fas fa-user-md me-1"></i>
                                الطبيب المسؤول
                            </h6>
                            <p>{{ optional($incubatorReservation->doctor)->user->name ?? 'غير محدد' }}</p>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                تاريخ ووقت الدخول
                            </h6>
                            <p>
                                {{ $incubatorReservation->admission_date->format('Y-m-d') }}
                                في الساعة
                                {{ \Carbon\Carbon::parse($incubatorReservation->admission_time)->format('H:i') }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            @if($incubatorReservation->discharge_date)
                                <h6 class="text-muted">
                                    <i class="fas fa-sign-out-alt me-1"></i>
                                    تاريخ ووقت الخروج
                                </h6>
                                <p>
                                    {{ $incubatorReservation->discharge_date->format('Y-m-d') }}
                                    في الساعة
                                    {{ \Carbon\Carbon::parse($incubatorReservation->discharge_time)->format('H:i') }}
                                </p>
                            @else
                                <h6 class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    المدة الحالية
                                </h6>
                                <p>
                                    <span class="badge bg-primary fs-6">
                                        {{ $incubatorReservation->current_duration }} يوم
                                    </span>
                                </p>
                            @endif
                        </div>
                    </div>

                    @if($incubatorReservation->medical_notes)
                        <hr>
                        <div class="mb-3">
                            <h6 class="text-muted">
                                <i class="fas fa-notes-medical me-1"></i>
                                الملاحظات الطبية
                            </h6>
                            <p class="border-start border-4 border-info ps-3">
                                {{ $incubatorReservation->medical_notes }}
                            </p>
                        </div>
                    @endif

                    @if($incubatorReservation->admission_notes)
                        <div class="mb-3">
                            <h6 class="text-muted">
                                <i class="fas fa-file-alt me-1"></i>
                                ملاحظات الدخول
                            </h6>
                            <p class="border-start border-4 border-success ps-3">
                                {{ $incubatorReservation->admission_notes }}
                            </p>
                        </div>
                    @endif

                    @if($incubatorReservation->discharge_notes)
                        <div class="mb-3">
                            <h6 class="text-muted">
                                <i class="fas fa-file-alt me-1"></i>
                                ملاحظات الخروج
                            </h6>
                            <p class="border-start border-4 border-warning ps-3">
                                {{ $incubatorReservation->discharge_notes }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- معلومات المريض (الأم) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>
                        معلومات المريض (الأم أو المسؤول)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">الاسم</h6>
                            <p>{{ optional($incubatorReservation->patient->user)->name ?? 'غير معروف' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">رقم الهاتف</h6>
                            <p>{{ optional($incubatorReservation->patient->user)->phone ?? 'غير متوفر' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الحاضنة والتكلفة -->
        <div class="col-lg-4">
            <!-- معلومات الحاضنة -->
            <div class="card shadow-sm mb-4 border-{{ $incubatorReservation->incubator->type_color }}">
                <div class="card-header bg-{{ $incubatorReservation->incubator->type_color }} text-white">
                    <h5 class="mb-0">
                        <i class="fas {{ $incubatorReservation->incubator->type_icon }} me-2"></i>
                        الحاضنة
                    </h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="display-4 mb-3">{{ $incubatorReservation->incubator->incubator_number }}</h2>
                    <p class="mb-2">
                        <strong>النوع:</strong><br>
                        {{ $incubatorReservation->incubator->type_name }}
                    </p>
                    <p class="mb-2">
                        <strong>الغرفة:</strong><br>
                        {{ $incubatorReservation->incubator->room->room_number ?? 'غير محدد' }}
                    </p>
                    <p class="mb-0">
                        <strong>الأجرة اليومية:</strong><br>
                        <span class="text-success fs-5">
                            {{ number_format($incubatorReservation->incubator->daily_fee) }} د.ع
                        </span>
                    </p>
                </div>
            </div>

            <!-- التكلفة -->
            <div class="card shadow-sm mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        التكلفة
                    </h5>
                </div>
                <div class="card-body">
                    @if($incubatorReservation->status === 'discharged')
                        <div class="text-center">
                            <p class="text-muted mb-2">التكلفة الإجمالية</p>
                            <h2 class="text-success">
                                {{ number_format($incubatorReservation->total_cost) }} د.ع
                            </h2>
                            <small class="text-muted">
                                ({{ $incubatorReservation->actual_duration }} يوم × {{ number_format($incubatorReservation->incubator->daily_fee) }} د.ع)
                            </small>
                        </div>
                    @elseif($incubatorReservation->status === 'admitted')
                        <div class="text-center">
                            <p class="text-muted mb-2">التكلفة الحالية</p>
                            <h2 class="text-primary">
                                {{ number_format($incubatorReservation->calculateActualCost()) }} د.ع
                            </h2>
                            <small class="text-muted">
                                ({{ $incubatorReservation->current_duration }} يوم × {{ number_format($incubatorReservation->incubator->daily_fee) }} د.ع)
                            </small>
                        </div>
                    @else
                        <div class="text-center">
                            <p class="text-muted mb-2">التكلفة المتوقعة</p>
                            <h2 class="text-info">
                                {{ number_format($incubatorReservation->calculateExpectedCost()) }} د.ع
                            </h2>
                            <small class="text-muted">
                                ({{ $incubatorReservation->expected_duration }} يوم × {{ number_format($incubatorReservation->incubator->daily_fee) }} د.ع)
                            </small>
                        </div>
                    @endif
                </div>
            </div>

            <!-- الإجراءات -->
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        الإجراءات
                    </h5>
                </div>
                <div class="card-body">
                    @if($incubatorReservation->status === 'pending')
                        <form action="{{ route('incubator-reservations.admit', $incubatorReservation) }}" 
                              method="POST" class="mb-2">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-sign-in-alt me-1"></i>
                                تسجيل دخول للحاضنة
                            </button>
                        </form>
                        
                        <form action="{{ route('incubator-reservations.cancel', $incubatorReservation) }}" 
                              method="POST" onsubmit="return confirm('هل أنت متأكد من إلغاء الحجز؟')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-times me-1"></i>
                                إلغاء الحجز
                            </button>
                        </form>
                    @elseif($incubatorReservation->status === 'admitted')
                        <button type="button" class="btn btn-warning w-100 mb-2" 
                                data-bs-toggle="modal" data-bs-target="#dischargeModal">
                            <i class="fas fa-sign-out-alt me-1"></i>
                            تسجيل الخروج
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal للخروج -->
@if($incubatorReservation->status === 'admitted')
<div class="modal fade" id="dischargeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('incubator-reservations.discharge', $incubatorReservation) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title">تسجيل خروج الطفل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>تسجيل خروج الطفل: <strong>{{ $incubatorReservation->baby_name }}</strong></p>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات الخروج</label>
                        <textarea name="discharge_notes" class="form-control" rows="4" 
                                  placeholder="أدخل ملاحظات الخروج والحالة الصحية للطفل"></textarea>
                    </div>
                    <div class="alert alert-info">
                        <strong>المدة الإجمالية:</strong> {{ $incubatorReservation->current_duration }} يوم<br>
                        <strong>التكلفة الإجمالية:</strong> {{ number_format($incubatorReservation->calculateActualCost()) }} د.ع
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-sign-out-alt me-1"></i>
                        تأكيد الخروج
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
