@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-clipboard-list me-2 text-primary"></i>
                        حجوزات الحاضنات
                    </h2>
                    <p class="text-muted">عرض وإدارة جميع حجوزات حاضنات الخدج</p>
                </div>
                <div>
                    <a href="{{ route('incubator-reservations.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        حجز جديد
                    </a>
                    <a href="{{ route('incubators.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-th me-1"></i>
                        عرض الحاضنات
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

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                جميع الحجوزات
            </h5>
        </div>
        <div class="card-body">
            @if($reservations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>اسم الطفل</th>
                                <th>المريض (الأم)</th>
                                <th>الحاضنة</th>
                                <th>تاريخ الدخول</th>
                                <th>المدة</th>
                                <th>الطبيب</th>
                                <th>الحالة</th>
                                <th>التكلفة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservations as $reservation)
                                <tr>
                                    <td>{{ $reservation->id }}</td>
                                    <td>
                                        <strong>{{ $reservation->baby_name }}</strong>
                                        @if($reservation->birth_weight)
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-weight me-1"></i>
                                                {{ $reservation->birth_weight }} غم
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ optional($reservation->patient->user)->name ?? 'غير معروف' }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $reservation->incubator->type_color }}">
                                            {{ $reservation->incubator->incubator_number }}
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            {{ $reservation->incubator->type_name }}
                                        </small>
                                    </td>
                                    <td>
                                        {{ $reservation->admission_date->format('Y-m-d') }}
                                        <br>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($reservation->admission_time)->format('H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($reservation->status === 'discharged')
                                            <span class="badge bg-info">
                                                {{ $reservation->actual_duration }} يوم
                                            </span>
                                        @elseif($reservation->status === 'admitted')
                                            <span class="badge bg-success">
                                                {{ $reservation->current_duration }} يوم
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                متوقع: {{ $reservation->expected_duration }} يوم
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ optional($reservation->doctor)->user->name ?? '-' }}
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $reservation->status_color }}">
                                            {{ $reservation->status_name }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-success fw-bold">
                                            {{ number_format($reservation->total_cost ?: $reservation->calculateExpectedCost()) }} د.ع
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('incubator-reservations.show', $reservation) }}" 
                                           class="btn btn-sm btn-info" title="عرض التفاصيل">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $reservations->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">لا توجد حجوزات</h4>
                    <p class="text-muted">لم يتم إنشاء أي حجوزات بعد</p>
                    <a href="{{ route('incubator-reservations.create') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-1"></i>
                        إنشاء حجز جديد
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
