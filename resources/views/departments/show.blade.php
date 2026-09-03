@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-1 fw-bold"><i class="fas fa-clinic-medical me-2"></i>{{ $department->name }}</h4>
                        <span class="badge bg-light text-primary fw-semibold">{{ $department->room_number }}</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('departments.edit', $department) }}" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold">
                            <i class="fas fa-edit me-1"></i>تعديل
                        </a>
                        <a href="{{ route('departments.admin') }}" class="btn btn-light btn-sm rounded-pill px-3">
                            <i class="fas fa-arrow-right me-1"></i>رجوع
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small">نوع العيادة</div>
                                <div class="fw-bold fs-6">{{ $department->type }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small">سعر الكشفية</div>
                                <div class="fw-bold fs-6 text-success">{{ number_format($department->consultation_fee, 0) }} د.ع</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small">ساعات العمل</div>
                                <div class="fw-bold fs-6">{{ substr($department->working_hours_start, 0, 5) }} - {{ substr($department->working_hours_end, 0, 5) }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 bg-light rounded-3">
                                <div class="text-muted small">الحد الأقصى يومياً</div>
                                <div class="fw-bold fs-6">{{ $department->max_patients_per_day }} مريض</div>
                            </div>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3"><i class="fas fa-user-md text-primary me-2"></i>أطباء العيادة ({{ $department->doctors->count() }})</h5>
                    @if($department->doctors->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>الطبيب</th>
                                        <th>التخصص</th>
                                        <th>الهاتف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($department->doctors as $doctor)
                                    <tr>
                                        <td class="fw-bold">{{ $doctor->name }}</td>
                                        <td>{{ $doctor->specialization }}</td>
                                        <td>{{ $doctor->phone ?? '—' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">لا يوجد أطباء مرتبطين بهذه العيادة حالياً.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
