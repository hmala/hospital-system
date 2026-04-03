@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-baby me-2 text-primary"></i>
                        تفاصيل الحاضنة
                    </h2>
                    <p class="text-muted">عرض معلومات الحاضنة {{ $incubator->incubator_number }}</p>
                </div>
                <div>
                    <a href="{{ route('incubators.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
                    @can('update', $incubator)
                    <a href="{{ route('incubators.edit', $incubator) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i>
                        تعديل
                    </a>
                    @endcan
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

    <div class="row">
        <!-- معلومات الحاضنة -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        معلومات الحاضنة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">رقم الحاضنة</h6>
                            <p class="fs-4 fw-bold">{{ $incubator->incubator_number }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">النوع</h6>
                            <p>
                                <span class="badge bg-{{ $incubator->type_color }} fs-6">
                                    <i class="fas {{ $incubator->type_icon }} me-1"></i>
                                    {{ $incubator->type_name }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">الحالة</h6>
                            <p>
                                <span class="badge bg-{{ $incubator->status_color }} fs-6">
                                    {{ $incubator->status_name }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">الأجرة اليومية</h6>
                            <p class="text-success fw-bold">
                                {{ number_format($incubator->daily_fee) }} د.ع
                            </p>
                        </div>
                    </div>

                    @if($incubator->room)
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h6 class="text-muted">الغرفة</h6>
                                <p>{{ $incubator->room->room_number }}</p>
                            </div>
                        </div>
                    @endif

                    @if($incubator->description)
                        <div class="mb-3">
                            <h6 class="text-muted">الوصف</h6>
                            <p>{{ $incubator->description }}</p>
                        </div>
                    @endif

                    @if($incubator->notes)
                        <div class="mb-3">
                            <h6 class="text-muted">ملاحظات</h6>
                            <p class="border-start border-4 border-info ps-3">{{ $incubator->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- الحجوزات -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        سجل الحجوزات
                    </h5>
                </div>
                <div class="card-body">
                    @if($incubator->reservations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>اسم الطفل</th>
                                        <th>تاريخ الدخول</th>
                                        <th>تاريخ الخروج</th>
                                        <th>المدة</th>
                                        <th>الطبيب</th>
                                        <th>التكلفة</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($incubator->reservations as $reservation)
                                        <tr>
                                            <td>
                                                <strong>{{ $reservation->baby_name }}</strong>
                                            </td>
                                            <td>
                                                {{ $reservation->admission_date->format('Y-m-d') }}
                                            </td>
                                            <td>
                                                {{ $reservation->discharge_date ? $reservation->discharge_date->format('Y-m-d') : '-' }}
                                            </td>
                                            <td>
                                                {{ $reservation->actual_duration ?? $reservation->current_duration }} يوم
                                            </td>
                                            <td>
                                                {{ optional($reservation->doctor)->user->name ?? '-' }}
                                            </td>
                                            <td>
                                                {{ number_format($reservation->total_cost) }} د.ع
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $reservation->status_color }}">
                                                    {{ $reservation->status_name }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                            <p class="text-muted">لا توجد حجوزات سابقة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- الحجز الحالي -->
        <div class="col-lg-4">
            @if($incubator->activeReservation)
                <div class="card shadow-sm mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-baby me-2"></i>
                            الحجز الحالي
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6 class="text-muted">اسم الطفل</h6>
                            <p class="fw-bold">{{ $incubator->activeReservation->baby_name }}</p>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted">المريض (الأم)</h6>
                            <p>{{ optional($incubator->activeReservation->patient->user)->name ?? 'غير معروف' }}</p>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted">تاريخ الدخول</h6>
                            <p>{{ $incubator->activeReservation->admission_date->format('Y-m-d') }}</p>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted">المدة الحالية</h6>
                            <p class="text-primary fw-bold">{{ $incubator->activeReservation->current_duration }} يوم</p>
                        </div>

                        <div class="mb-3">
                            <h6 class="text-muted">التكلفة حتى الآن</h6>
                            <p class="text-success fw-bold">{{ number_format($incubator->activeReservation->calculateActualCost()) }} د.ع</p>
                        </div>

                        <a href="{{ route('incubator-reservations.show', $incubator->activeReservation) }}" class="btn btn-info w-100">
                            <i class="fas fa-eye me-1"></i>
                            عرض تفاصيل الحجز
                        </a>
                    </div>
                </div>
            @else
                <div class="card shadow-sm mb-4 border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            الحاضنة متاحة
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p>هذه الحاضنة متاحة للحجز</p>
                        <a href="{{ route('incubator-reservations.create', ['incubator_id' => $incubator->id]) }}" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i>
                            حجز الآن
                        </a>
                    </div>
                </div>
            @endif

            <!-- إجراءات سريعة -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        إجراءات
                    </h5>
                </div>
                <div class="card-body">
                    @can('delete', $incubator)
                    <form action="{{ route('incubators.destroy', $incubator) }}" method="POST" class="mb-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100"
                                onclick="return confirm('هل أنت متأكد من حذف هذه الحاضنة؟')">
                            <i class="fas fa-trash me-1"></i>
                            حذف الحاضنة
                        </button>
                    </form>
                    @endcan

                    @can('update', $incubator)
                    <form action="{{ route('incubators.toggle-maintenance', $incubator) }}" method="POST" class="mb-2">
                        @csrf
                        @method('PATCH')
                        @if($incubator->status === 'maintenance')
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-play me-1"></i>
                                إنهاء الصيانة
                            </button>
                        @else
                            <button type="submit" class="btn btn-warning w-100">
                                <i class="fas fa-tools me-1"></i>
                                وضع الصيانة
                            </button>
                        @endif
                    </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection