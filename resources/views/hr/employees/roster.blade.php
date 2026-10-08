@extends('layouts.app')
@section('title', 'جدول دوام الموظف')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-calendar-check text-info me-2"></i>الجدول الفردي: {{ $employee->full_name }}</h4>
            <p class="text-muted small mb-0">{{ $employee->job_title }} | {{ $employee->department->name ?? '-' }}</p>
        </div>
        <div>
            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="fas fa-print me-1"></i> طباعة الجدول</button>
            <a href="{{ route('hr.schedules.index') }}" class="btn btn-primary"><i class="fas fa-arrow-right me-1"></i> العودة للجدول الشامل</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-bottom p-4">
            <h5 class="mb-0 fw-bold text-center">جدول دوام شهر {{ $startOfMonth->format('F Y') }}</h5>
        </div>
        <div class="card-body p-4">
            <div class="row">
                @forelse($schedules as $sch)
                    <div class="col-md-3 col-sm-4 col-6 mb-3">
                        <div class="border rounded-3 p-3 text-center {{ $sch->is_off_day ? 'bg-light text-muted' : '' }}" style="{{ $sch->hr_shift_id ? 'border-bottom: 4px solid '.$sch->shift->color_code.' !important;' : '' }}">
                            <div class="fw-bold mb-1">{{ $sch->shift_date->format('Y-m-d') }}</div>
                            <div class="small mb-2">{{ $sch->shift_date->locale('ar')->translatedFormat('l') }}</div>
                            @if($sch->is_off_day)
                                <span class="badge bg-secondary">يوم راحة (Off)</span>
                            @elseif($sch->hr_shift_id)
                                <span class="badge" style="background-color: {{ $sch->shift->color_code }}; color: white;">{{ $sch->shift->name }}</span>
                                <div class="mt-2 small text-muted">
                                    {{ \Carbon\Carbon::parse($sch->shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sch->shift->end_time)->format('H:i') }}
                                </div>
                            @else
                                <span class="text-muted small">— غير محدد —</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3 opacity-25"></i>
                        <h5>لا يوجد جدول مخصص لهذا الموظف في الشهر الحالي.</h5>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection