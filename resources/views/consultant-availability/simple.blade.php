<!-- resources/views/consultant-availability/simple.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-calendar-check me-2"></i>إدارة توفر الأطباء الاستشاريين</h2>
                    <p class="text-muted mb-0">تحديد الأطباء المتاحين للاستشارات اليوم</p>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <h6><i class="fas fa-info-circle me-2"></i>معلومات مهمة</h6>
        <p class="mb-1">هذه الصفحة مخصصة لتحديد توفر الأطباء الاستشاريين يومياً</p>
        <p class="mb-1">يمكنك تحديث توفر كل طبيب على حدة أو تحديث جميع الأطباء معاً</p>
        <p class="mb-0">التحديثات تُحفظ فوراً وتؤثر على جدولة المواعيد</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>قائمة الأطباء الاستشاريين</h5>
                </div>
                <div class="card-body">
                    @if($consultantDoctors->count() > 0)
                        <div class="row">
                            @foreach($groupedDoctors as $specialization => $doctors)
                                <div class="col-lg-6 col-xl-4 mb-4">
                                    <div class="card h-100 border-primary">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0 text-primary">
                                                <i class="fas fa-stethoscope me-2"></i>{{ $specialization }}
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            @foreach($doctors as $doctor)
                                                <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-sm bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                                <span class="text-white fw-bold">{{ substr($doctor->user->name, 0, 1) }}</span>
                                                            </div>
                                                            <div>
                                                                <strong>د. {{ $doctor->user->name }}</strong>
                                                                <br>
                                                                <small class="text-muted">{{ $doctor->department->name ?? 'غير محدد' }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input" type="checkbox"
                                                                   {{ $doctor->is_available_today ? 'checked' : '' }}>
                                                            <label class="form-check-label">
                                                                <span class="availability-status {{ $doctor->is_available_today ? 'text-success' : 'text-danger' }}">
                                                                    {{ $doctor->is_available_today ? 'متوفر' : 'غير متوفر' }}
                                                                </span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border-warning">
                                    <div class="card-body">
                                        <h6 class="text-warning mb-3">
                                            <i class="fas fa-chart-bar me-2"></i>ملخص التوفر اليومي
                                        </h6>
                                        <div class="row text-center">
                                            <div class="col-md-6">
                                                <div class="p-3">
                                                    <h4 class="text-success">{{ $consultantDoctors->where('is_available_today', true)->count() }}</h4>
                                                    <p class="mb-0 text-muted">أطباء متوفرون</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="p-3">
                                                    <h4 class="text-danger">{{ $consultantDoctors->where('is_available_today', false)->count() }}</h4>
                                                    <p class="mb-0 text-muted">أطباء غير متوفرين</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-user-md fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">لا توجد أطباء استشاريين</h4>
                            <p class="text-muted">لم يتم العثور على أطباء استشاريين نشطين في النظام</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content-center;
    font-weight: bold;
}

.availability-toggle {
    transform: scale(1.2);
}

.availability-status {
    font-weight: 600;
    transition: color 0.3s ease;
}
</style>
@endsection