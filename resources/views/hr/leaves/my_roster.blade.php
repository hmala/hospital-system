@extends('layouts.app')
@section('title', 'جدولي الشهري - بوابتي')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-calendar-check text-info me-2"></i>جدول دوامي الشهري</h4>
            <p class="text-muted small mb-0">{{ $employee?->full_name }} — {{ $employee?->department?->name ?? '—' }}</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="{{ route('my.roster') }}" class="d-flex gap-2">
                <input type="month" name="month" class="form-control form-control-sm" value="{{ $startOfMonth->format('Y-m') }}" onchange="this.form.submit()">
            </form>
            <a href="{{ route('my.leaves.index') }}" class="btn btn-sm btn-outline-primary">طلبات إجازاتي</a>
        </div>
    </div>

    @if(!$employee)
        <div class="alert alert-warning border-0 shadow-sm text-center p-4">حسابك غير مرتبط بملف موظف في الموارد البشرية.</div>
    @else
        @if($employee->isPerCall())
            <div class="alert alert-info border-0 shadow-sm d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <i class="fas fa-phone-volume me-1"></i>
                    <strong>نظام الأجر بالـ Call / الشفتات:</strong>
                    عدد الشفتات المحتسبة بهذا الشهر: <strong>{{ $estimatedCallsCount }}</strong>
                </div>
                <div class="fw-bold fs-5 text-primary">
                    المجموع المقدر: {{ number_format($estimatedCallsAmount, 0) }} د.ع
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <div class="row">
                    @forelse($schedules as $sch)
                        <div class="col-md-3 col-sm-4 col-6 mb-3">
                            <div class="border rounded-3 p-3 text-center {{ $sch->is_off_day ? 'bg-light text-muted' : ($sch->is_leave ? 'bg-info bg-opacity-10 border-info' : '') }}" style="{{ $sch->hr_shift_id && $sch->shift ? 'border-bottom: 4px solid '.$sch->shift->color_code.' !important;' : '' }}">
                                <div class="fw-bold mb-1">{{ $sch->shift_date->format('Y-m-d') }}</div>
                                <div class="small mb-2">{{ $sch->shift_date->locale('ar')->translatedFormat('l') }}</div>
                                @if($sch->is_leave)
                                    <span class="badge bg-info text-dark">إجازة معتمدة</span>
                                @elseif($sch->is_off_day)
                                    <span class="badge bg-secondary">يوم راحة (Off)</span>
                                @elseif($sch->shift)
                                    <span class="badge" style="background-color: {{ $sch->shift->color_code }}; color: #fff;">{{ $sch->shift->name }}</span>
                                    <div class="mt-2 small text-muted">
                                        {{ \Carbon\Carbon::parse($sch->shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sch->shift->end_time)->format('H:i') }}
                                    </div>
                                    @if($sch->attendance_status === 'absent')
                                        <span class="badge bg-danger mt-1">غائب</span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-5 text-muted">لا يوجد جدول مسجل لك في هذا الشهر بعد.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
