@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-user-md text-primary"></i>
                            تفاصيل الطبيب: {{ $doctor->user ? $doctor->user->name : 'طبيب بدون بيانات' }}
                        </h4>
                        <div>
                            <a href="{{ route('doctors.edit', $doctor) }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> تعديل
                            </a>
                            <a href="{{ route('doctors.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left"></i> العودة للقائمة
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- معلومات الطبيب الأساسية -->
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-info mb-3">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0"><i class="fas fa-id-card"></i> المعلومات الشخصية</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>الاسم:</strong> {{ $doctor->user ? $doctor->user->name : 'غير محدد' }}</p>
                                            <p><strong>البريد الإلكتروني:</strong> {{ $doctor->user ? $doctor->user->email : 'غير محدد' }}</p>
                                            <p><strong>رقم الهاتف:</strong> {{ $doctor->phone ?? 'غير محدد' }}</p>
                                            <p><strong>تاريخ التسجيل:</strong> {{ $doctor->created_at->format('Y/m/d') }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border-success mb-3">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0"><i class="fas fa-stethoscope"></i> المعلومات المهنية</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>التخصص:</strong> {{ $doctor->specialization }}</p>
                                            <p><strong>المؤهل العلمي:</strong> {{ $doctor->qualification ?? 'غير محدد' }}</p>
                                            <p><strong>رقم الترخيص:</strong> {{ $doctor->license_number }}</p>
                                            <p><strong>سنوات الخبرة:</strong> {{ $doctor->experience_years }} سنة</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card border-primary mb-3">
                                        <div class="card-header bg-primary text-white">
                                            <h6 class="mb-0"><i class="fas fa-building"></i> معلومات العمل</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>القسم:</strong> {{ $doctor->department ? $doctor->department->name : 'غير محدد' }}</p>
                                            <p><strong>نوع القسم:</strong> {{ $doctor->department ? $doctor->department->getTypeText() : 'غير محدد' }}</p>
                                            <p><strong>الحالة:</strong>
                                                @if($doctor->is_active)
                                                    <span class="badge bg-success">نشط</span>
                                                @else
                                                    <span class="badge bg-danger">غير نشط</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card border-warning mb-3">
                                        <div class="card-header bg-warning text-dark">
                                            <h6 class="mb-0"><i class="fas fa-money-bill-wave"></i> معلومات مالية</h6>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>رسوم الكشف:</strong> {{ number_format($doctor->consultation_fee, 2) }} دينار</p>
                                            <p><strong>الحد الأقصى يومياً:</strong> {{ $doctor->max_patients_per_day }} مريض</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if($doctor->bio)
                            <div class="card border-secondary mb-3">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> نبذة تعريفية</h6>
                                </div>
                                <div class="card-body">
                                    <p class="mb-0">{{ $doctor->bio }}</p>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- إحصائيات سريعة -->
                        <div class="col-md-4">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white text-center">
                                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> الإحصائيات</h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <div class="border rounded p-3">
                                                <h3 class="text-primary mb-1">{{ $doctor->appointments()->whereDate('appointment_date', today())->count() }}</h3>
                                                <small class="text-muted">مواعيد اليوم</small>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <div class="border rounded p-3">
                                                <h3 class="text-success mb-1">{{ $doctor->appointments()->where('status', 'completed')->count() }}</h3>
                                                <small class="text-muted">مواعيد مكتملة</small>
                                            </div>
                                        </div>
                                        <div class="col-12 mb-3">
                                            <div class="border rounded p-3">
                                                <h3 class="text-warning mb-1">{{ $doctor->appointments()->where('status', 'pending')->count() }}</h3>
                                                <small class="text-muted">مواعيد معلقة</small>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="border rounded p-3">
                                                <h3 class="text-info mb-1">{{ $doctor->appointments()->count() }}</h3>
                                                <small class="text-muted">إجمالي المواعيد</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- المواعيد القادمة -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fas fa-calendar-alt"></i> المواعيد القادمة</h6>
                                </div>
                                <div class="card-body">
                                    @if($doctor->appointments->where('appointment_date', '>=', today())->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover">
                                                <thead class="table-primary">
                                                    <tr>
                                                        <th>التاريخ</th>
                                                        <th>المريض</th>
                                                        <th>الحالة</th>
                                                        <th>الملاحظات</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($doctor->appointments->where('appointment_date', '>=', today())->sortBy('appointment_date')->take(10) as $appointment)
                                                        <tr>
                                                            <td>{{ $appointment->appointment_date ? $appointment->appointment_date->format('Y/m/d') : 'غير محدد' }}</td>
                                                            <td>{{ $appointment->patient->user ? $appointment->patient->user->name : 'مريض بدون بيانات' }}</td>
                                                            <td>
                                                                @switch($appointment->status)
                                                                    @case('pending')
                                                                        <span class="badge bg-warning">معلق</span>
                                                                        @break
                                                                    @case('confirmed')
                                                                        <span class="badge bg-info">مؤكد</span>
                                                                        @break
                                                                    @case('completed')
                                                                        <span class="badge bg-success">مكتمل</span>
                                                                        @break
                                                                    @case('cancelled')
                                                                        <span class="badge bg-danger">ملغي</span>
                                                                        @break
                                                                @endswitch
                                                            </td>
                                                            <td>{{ $appointment->notes ?? 'لا توجد' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-4">
                                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">لا توجد مواعيد قادمة</h5>
                                            <p class="text-muted">سيتم عرض المواعيد القادمة هنا عند الحجز</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-header {
    font-weight: 600;
}

.card-body p {
    margin-bottom: 0.5rem;
}

.card-body p strong {
    color: #495057;
    font-weight: 600;
}

.badge {
    font-size: 0.75em;
}

.table th {
    font-weight: 600;
    font-size: 0.9em;
}

.border {
    border: 2px solid #dee2e6 !important;
}

.border-info {
    border-color: #0dcaf0 !important;
}

.border-success {
    border-color: #198754 !important;
}

.border-primary {
    border-color: #0d6efd !important;
}

.border-warning {
    border-color: #ffc107 !important;
}

.border-secondary {
    border-color: #6c757d !important;
}
</style>
@endsection