<!-- resources/views/appointments/show.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        تفاصيل الموعد
                    </h2>
                    <p class="text-muted mb-0">عرض تفاصيل الموعد الطبي</p>
                </div>
                <div>
                    <a href="{{ route('appointments.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
                    </a>
                    @if(auth()->user()->role === 'receptionist' )
                    <form action="{{ route('appointments.convert', $appointment) }}" method="POST" class="d-inline">
                        @csrf @method('PUT')
                        <button type="submit" class="btn btn-success" onclick="return confirm('هل أنت متأكد من تحويل هذا الموعد إلى زيارة؟')">
                            <i class="fas fa-user-md me-2"></i>تحويل إلى زيارة
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Appointment Details -->
        <div class="col-lg-8">
            <!-- Appointment Status Card -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-gradient-primary text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-calendar-alt me-2"></i>
                                الموعد #{{ $appointment->id }}
                            </h5>
                        </div>
                        <div>
                            <span class="badge fs-6 px-3 py-2" style="background: rgba(255,255,255,0.2); border-radius: 20px;">
                                <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                                {{ $appointment->status_text }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <!-- Date & Time -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-3 me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-clock text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">تاريخ ووقت الموعد</h6>
                                    <p class="mb-0 fw-bold">{{ $appointment->appointment_date->format('Y-m-d') }}</p>
                                    <small class="text-muted">{{ $appointment->appointment_date->format('H:i') }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-3 me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-stethoscope text-success"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">سبب الزيارة</h6>
                                    <p class="mb-0 fw-bold">{{ $appointment->reason }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Fee -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-3 me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-dollar-sign text-warning"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">أجر الكشف</h6>
                                    <p class="mb-0 fw-bold text-success">{{ number_format($appointment->consultation_fee) }} د.ع</p>
                                </div>
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-3 me-3" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-hourglass-half text-info"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 text-muted">مدة الموعد</h6>
                                    <p class="mb-0 fw-bold">{{ $appointment->duration ?? 30 }} دقيقة</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            @if($appointment->notes)
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                <div class="card-header bg-light" style="border: none; border-radius: 15px 15px 0 0;">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-sticky-note me-2"></i>
                        ملاحظات إضافية
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">{{ $appointment->notes }}</p>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Patient Information -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                <div class="card-header bg-gradient-info text-white" style="background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%); border: none; border-radius: 15px 15px 0 0;">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        معلومات المريض
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-user text-white fs-4"></i>
                        </div>
                    </div>
                    <h5 class="text-center mb-3">{{ $appointment->patient ? $appointment->patient->name : 'مريض غير محدد' }}</h5>
                    <div class="row g-2">
                        <div class="col-12">
                            <small class="text-muted d-block">رقم الهاتف</small>
                            <span class="fw-bold">{{ $appointment->patient && $appointment->patient->user ? $appointment->patient->user->phone : 'غير محدد' }}</span>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">البريد الإلكتروني</small>
                            <span class="fw-bold">{{ $appointment->patient && $appointment->patient->user ? $appointment->patient->user->email : 'غير محدد' }}</span>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">رقم الهوية</small>
                            <span class="fw-bold">{{ $appointment->patient ? $appointment->patient->national_id : 'غير محدد' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doctor Information -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                <div class="card-header bg-gradient-success text-white" style="background: linear-gradient(135deg, #00b894 0%, #00cec9 100%); border: none; border-radius: 15px 15px 0 0;">
                    <h6 class="mb-0">
                        <i class="fas fa-user-md me-2"></i>
                        معلومات الطبيب
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-user-md text-white fs-4"></i>
                        </div>
                    </div>
                    <h5 class="text-center mb-3">د. {{ $appointment->doctor && $appointment->doctor->user ? $appointment->doctor->user->name : 'طبيب غير محدد' }}</h5>
                    <div class="row g-2">
                        <div class="col-12">
                            <small class="text-muted d-block">التخصص</small>
                            <span class="fw-bold">{{ $appointment->doctor ? $appointment->doctor->specialization : 'غير محدد' }}</span>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">رقم الهاتف</small>
                            <span class="fw-bold">{{ $appointment->doctor && $appointment->doctor->user ? $appointment->doctor->user->phone : 'غير محدد' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Information -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
                <div class="card-header bg-gradient-warning text-white" style="background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%); border: none; border-radius: 15px 15px 0 0;">
                    <h6 class="mb-0">
                        <i class="fas fa-hospital me-2"></i>
                        معلومات العيادة
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="bg-warning rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-hospital text-white fs-4"></i>
                        </div>
                    </div>
                    <h5 class="text-center mb-3">{{ $appointment->department ? $appointment->department->name : 'عيادة غير محددة' }}</h5>
                    <div class="row g-2">
                        <div class="col-12">
                            <small class="text-muted d-block">رقم الغرفة</small>
                            <span class="fw-bold">{{ $appointment->department ? $appointment->department->room_number : 'غير محدد' }}</span>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">الوصف</small>
                            <span class="fw-bold">{{ $appointment->department ? $appointment->department->description : 'غير محدد' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm" style="border-radius: 15px;">
                <div class="card-header bg-light" style="border: none; border-radius: 15px 15px 0 0;">
                    <h6 class="mb-0 text-primary">
                        <i class="fas fa-bolt me-2"></i>
                        إجراءات سريعة
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('appointments.edit', $appointment) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-2"></i>تعديل الموعد
                        </a>
                        @if($appointment->canBeCancelled())
                        <form action="{{ route('appointments.cancel', $appointment) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning w-100" onclick="return confirm('هل أنت متأكد من إلغاء الموعد؟')">
                                <i class="fas fa-times me-2"></i>إلغاء الموعد
                            </button>
                        </form>
                        @endif
                        <a href="{{ route('appointments.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i>جميع المواعيد
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-info {
    background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
}

.bg-gradient-success {
    background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
}

.bg-gradient-warning {
    background: linear-gradient(135deg, #fdcb6e 0%, #e17055 100%);
}

.card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.btn {
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.badge {
    font-weight: 500;
}

.rounded-circle {
    border-radius: 50% !important;
}
</style>
@endsection