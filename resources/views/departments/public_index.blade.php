@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-clinic-medical me-2"></i>
                        العيادات المتاحة
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        @forelse($departments as $department)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-clinic-medical me-2"></i>
                                        {{ $department->name }}
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>النوع:</strong><br>
                                            {{ $department->type }}
                                        </div>
                                        <div class="col-6">
                                            <strong>الغرفة:</strong><br>
                                            {{ $department->room_number }}
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>رسوم الاستشارة:</strong><br>
                                            {{ number_format($department->consultation_fee, 2) }} ريال
                                        </div>
                                        <div class="col-6">
                                            <strong>مواعيد اليوم:</strong><br>
                                            <span class="badge bg-info">{{ $department->today_appointments_count }}</span>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <strong>ساعات العمل:</strong><br>
                                            {{ $department->working_hours_start }} - {{ $department->working_hours_end }}
                                        </div>
                                    </div>
                                    @if($department->doctors->count() > 0)
                                    <hr>
                                    <div class="row">
                                        <div class="col-12">
                                            <strong>الأطباء ({{ $department->doctors->count() }}):</strong><br>
                                            <small class="text-muted">
                                                @foreach($department->doctors->take(3) as $doctor)
                                                {{ $doctor->name }}@if(!$loop->last), @endif
                                                @endforeach
                                                @if($department->doctors->count() > 3)
                                                و {{ $department->doctors->count() - 3 }} آخرين
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                <div class="card-footer text-center">
                                    <small class="text-muted">
                                        الحد الأقصى للمرضى يومياً: {{ $department->max_patients_per_day }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <h5>لا توجد عيادات متاحة حالياً</h5>
                                <p>يرجى المحاولة لاحقاً أو الاتصال بالمستشفى للمزيد من المعلومات.</p>
                            </div>
                        </div>
                        @endforelse
                    </div>

                    @if($departments->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $departments->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection