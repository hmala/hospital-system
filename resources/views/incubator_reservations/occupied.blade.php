@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-list-alt me-2 text-info"></i>
                        الحاضنات المشغولة حالياً
                    </h2>
                    <p class="text-muted">عرض جميع الأطفال الموجودين في الحاضنات</p>
                </div>
                <div>
                    <a href="{{ route('incubators.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة
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
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-baby me-2"></i>
                الأطفال في الحاضنات ({{ $activeReservations->count() }})
            </h5>
        </div>
        <div class="card-body">
            @if($activeReservations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>الحاضنة</th>
                                <th>اسم الطفل</th>
                                <th>المريض (الأم)</th>
                                <th>وزن الولادة</th>
                                <th>عمر الحمل</th>
                                <th>تاريخ الدخول</th>
                                <th>المدة الحالية</th>
                                <th>الطبيب</th>
                                <th>الحالة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeReservations as $reservation)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $reservation->incubator->type_color }}">
                                            رقم {{ $reservation->incubator->incubator_number }}
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            {{ $reservation->incubator->type_name }}
                                        </small>
                                    </td>
                                    <td>
                                        <strong>{{ $reservation->baby_name }}</strong>
                                    </td>
                                    <td>
                                        {{ optional($reservation->patient->user)->name ?? 'غير معروف' }}
                                        @if(optional($reservation->patient->user)->phone)
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i>
                                                {{ optional($reservation->patient->user)->phone }}
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $reservation->birth_weight ? $reservation->birth_weight . ' غم' : '-' }}
                                    </td>
                                    <td>
                                        {{ $reservation->gestational_age ? $reservation->gestational_age . ' أسبوع' : '-' }}
                                    </td>
                                    <td>
                                        {{ $reservation->admission_date->format('Y-m-d') }}
                                        <br>
                                        <small class="text-muted">
                                            {{ \Carbon\Carbon::parse($reservation->admission_time)->format('H:i') }}
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            {{ $reservation->current_duration }} يوم
                                        </span>
                                        <br>
                                        <small class="text-success">
                                            {{ number_format($reservation->calculateActualCost()) }} د.ع
                                        </small>
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
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('incubator-reservations.show', $reservation) }}" 
                                               class="btn btn-info" title="عرض التفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            @if($reservation->status === 'pending')
                                                <form action="{{ route('incubator-reservations.admit', $reservation) }}" 
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-success" title="تسجيل الدخول">
                                                        <i class="fas fa-sign-in-alt"></i>
                                                    </button>
                                                </form>
                                            @endif

                                            @if($reservation->status === 'admitted')
                                                <button type="button" class="btn btn-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#dischargeModal{{ $reservation->id }}"
                                                        title="تسجيل الخروج">
                                                    <i class="fas fa-sign-out-alt"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal للخروج -->
                                <div class="modal fade" id="dischargeModal{{ $reservation->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form action="{{ route('incubator-reservations.discharge', $reservation) }}" 
                                                  method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">تسجيل خروج</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>تسجيل خروج الطفل: <strong>{{ $reservation->baby_name }}</strong></p>
                                                    <div class="mb-3">
                                                        <label class="form-label">ملاحظات الخروج</label>
                                                        <textarea name="discharge_notes" class="form-control" rows="3"></textarea>
                                                    </div>
                                                    <div class="alert alert-info">
                                                        <strong>المدة الإجمالية:</strong> {{ $reservation->current_duration }} يوم<br>
                                                        <strong>التكلفة الإجمالية:</strong> {{ number_format($reservation->calculateActualCost()) }} د.ع
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="fas fa-sign-out-alt me-1"></i>
                                                        تسجيل الخروج
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-baby fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">لا توجد حاضنات مشغولة حالياً</h4>
                    <p class="text-muted">جميع الحاضنات متاحة</p>
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
