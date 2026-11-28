@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border: 1px solid #e2e8f0 !important;">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 d-flex align-items-center justify-content-center shadow-xs" 
                         style="width: 52px; height: 52px; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: #fff;">
                        <i class="fas fa-procedures fa-lg"></i>
                    </div>
                    <div>
                        <h3 class="fw-bold mb-1 text-slate-800" style="font-size: 1.35rem;">
                            {{ __("العمليات التي تحتاج اختيار أشعة") }}
                        </h3>
                        <p class="text-muted small mb-0">
                            {{ __("قائمة العمليات الجراحية المجدولة التي تنتظر تحديد وتكليف الفحوصات الإشعاعية المناسبة") }}
                        </p>
                    </div>
                </div>

                <div>
                    <a href="{{ route('staff.surgery-radiology-tests.index') }}" class="btn btn-outline-secondary rounded-pill px-3.5 py-2 fw-semibold shadow-2xs d-inline-flex align-items-center gap-1.5">
                        <i class="fas {{ app()->getLocale() === 'en' ? 'fa-arrow-right' : 'fa-arrow-left' }}"></i>
                        <span>{{ __("العودة لطلبات الأشعة") }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="fas fa-search me-2"></i>
                {{ __("البحث والتصفية") }}
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('staff.surgery-radiology-tests.selection') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">{{ __("بحث") }}</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="{{ __("ابحث باسم المريض أو نوع العملية...") }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">{{ __("من تاريخ") }}</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">{{ __("إلى تاريخ") }}</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>{{ __("بحث") }}
                    </button>
                    <a href="{{ route('staff.surgery-radiology-tests.selection') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-1"></i>{{ __("إعادة تعيين") }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
                <i class="fas fa-list me-2"></i>
                {{ __("العمليات الجراحية التي تحتاج اختيار أشعة") }}
            </h6>
        </div>
        <div class="card-body">
            @if($surgeries->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 60px;">#</th>
                            <th>{{ __("المريض") }}</th>
                            <th>{{ __("نوع العملية") }}</th>
                            <th>{{ __("تاريخ العملية") }}</th>
                            <th class="text-center">{{ __("الحالة") }}</th>
                            <th class="text-center" style="width: 170px;">{{ __("الإجراءات") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($surgeries as $surgery)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold">{{ optional($surgery->patient->user)->name ?? __('غير معروف') }}</div>
                                <div class="text-muted">ID: {{ $surgery->patient_id }}</div>
                            </td>
                            <td>{{ $surgery->surgery_type }}</td>
                            <td>
                                {{ optional($surgery->scheduled_date)->format('Y-m-d') ?? __('غير محدد') }}
                                <br>
                                <small class="text-muted">{{ optional($surgery->scheduled_time)->format('H:i') ?? '-' }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $surgery->status == 'waiting' ? 'warning' : ($surgery->status == 'in_progress' ? 'info' : ($surgery->status == 'scheduled' ? 'secondary' : 'dark')) }}">
                                    {{ ucfirst($surgery->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('staff.surgery-radiology-tests.create-selection', $surgery) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-check-square me-1"></i>{{ __("اختر الأشعة") }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $surgeries->links() }}
            </div>
            @else
            <div class="alert alert-info">
                {{ __("لا توجد الآن عمليات تحتاج اختيار أشعة.") }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
