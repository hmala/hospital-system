@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    {{-- Executive Header --}}
    <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 14px;">
        <div class="card-body p-4 text-white">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 54px; height: 54px; background: rgba(59, 130, 246, 0.2); border: 1.5px solid rgba(59, 130, 246, 0.4);">
                        <i class="fas fa-user-md text-primary fs-3"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="fw-bold mb-0 text-white">محطة الطبيب الجراح</h4>
                            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-2.5 py-1 rounded-pill small">
                                Surgeon Station
                            </span>
                        </div>
                        <p class="text-white-50 mb-0 small mt-1">
                            متابعة العمليات الجراحية بعد الصالة، توثيق الخطة العلاجية، وإصدار توجيهات المتابعة للمقيمين
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('surgeries.waiting') }}" class="btn btn-outline-light btn-sm fw-semibold rounded-pill px-3 shadow-sm">
                        <i class="fas fa-procedures me-1"></i> لوحة الصالات
                    </a>
                    <a href="{{ route('surgeon-station.index') }}" class="btn btn-primary btn-sm fw-bold rounded-pill px-3 shadow-sm">
                        <i class="fas fa-sync-alt me-1"></i> تحديث فوري
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- KPI Cards --}}
    @php
        $totalCount = $surgeries->count();
        $pendingCount = $surgeries->filter(fn($s) => !$s->surgeonStation || $s->surgeonStation->status === 'pending')->count();
        $inProgressCount = $surgeries->filter(fn($s) => $s->surgeonStation && $s->surgeonStation->status === 'in_progress')->count();
        $completedCount = $surgeries->filter(fn($s) => $s->surgeonStation && $s->surgeonStation->status === 'completed')->count();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center bg-white h-100">
                <div class="text-muted small fw-semibold">إجمالي العمليات بالمحطة</div>
                <div class="fs-3 fw-bolder text-dark my-1">{{ $totalCount }}</div>
                <div class="text-secondary small">عملية محالة للجراح</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center bg-white h-100 border-start border-4 border-warning">
                <div class="text-muted small fw-semibold">بانتظار المعاينة</div>
                <div class="fs-3 fw-bolder text-warning my-1">{{ $pendingCount }}</div>
                <div class="text-warning small">خرجت من الصالة</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center bg-white h-100 border-start border-4 border-info">
                <div class="text-muted small fw-semibold">قيد المتابعة</div>
                <div class="fs-3 fw-bolder text-info my-1">{{ $inProgressCount }}</div>
                <div class="text-info small">جاري كتابة الخطة</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-3 p-3 text-center bg-white h-100 border-start border-4 border-success">
                <div class="text-muted small fw-semibold">مكتملة ومُعتمدة</div>
                <div class="fs-3 fw-bolder text-success my-1">{{ $completedCount }}</div>
                <div class="text-success small">حُوّلت للردهة والمقيم</div>
            </div>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-list-alt text-primary"></i>
                قائمة العمليات المحالة لمحطة الجراح
            </h5>
            <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold">
                عرض {{ $totalCount }} حالة
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.92rem;">
                    <thead class="bg-light text-secondary text-uppercase fw-semibold" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-4 py-3">المريض</th>
                            <th class="py-3">العملية الجراحية</th>
                            <th class="py-3">موعد العملية</th>
                            <th class="py-3">الطبيب الجراح</th>
                            <th class="py-3 text-center">حالة المحطة</th>
                            <th class="pe-4 py-3 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($surgeries as $surgery)
                            @php
                                $stationStatus = $surgery->surgeonStation?->status ?? 'pending';
                                $isCompleted = ($stationStatus === 'completed');
                                $isInProgress = ($stationStatus === 'in_progress');
                            @endphp
                            <tr>
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs" 
                                             style="width: 38px; height: 38px; min-width: 38px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); font-size: 13px;">
                                            {{ mb_substr($surgery->patient?->user?->name ?? 'م', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $surgery->patient?->user?->name ?? '-' }}</div>
                                            <div class="text-muted small">
                                                <span>ملف: #{{ $surgery->patient?->file_number ?? '-' }}</span>
                                                @if($surgery->patient?->age)
                                                    <span class="mx-1">•</span>
                                                    <span>{{ $surgery->patient->age }} سنة</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span class="fw-bold text-dark">{{ $surgery->surgery_name }}</span>
                                    @if($surgery->department)
                                        <div class="text-muted small">{{ $surgery->department->name }}</div>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <div class="fw-semibold text-dark">
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}
                                    </div>
                                    <div class="text-muted small">
                                        <i class="far fa-clock text-muted me-1"></i>
                                        {{ $surgery->scheduled_time ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('h:i A') : '-' }}
                                    </div>
                                </td>
                                <td class="py-3">
                                    <div class="d-flex align-items-center gap-1.5">
                                        <i class="fas fa-user-md text-primary"></i>
                                        <span class="fw-semibold text-dark">{{ $surgery->doctor?->user?->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="py-3 text-center">
                                    @if($isCompleted)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> مكتملة
                                        </span>
                                    @elseif($isInProgress)
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="fas fa-spinner fa-spin me-1"></i> قيد المتابعة
                                        </span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="fas fa-hourglass-half me-1"></i> بانتظار المعاينة
                                        </span>
                                    @endif
                                </td>
                                <td class="pe-4 py-3 text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1.5 flex-wrap">
                                        <a href="{{ route('surgeon-station.show', $surgery) }}" class="btn btn-sm btn-primary fw-bold px-3 py-1.5 shadow-sm rounded-pill" title="فتح المحطة السريرية للجراح">
                                            <i class="fas fa-stethoscope me-1"></i> فتح المحطة
                                        </a>
                                        <a href="{{ route('surgeon-station.resident-follow-ups', $surgery) }}" class="btn btn-sm btn-outline-info fw-semibold px-2.5 py-1.5 shadow-sm rounded-pill" title="سجل متابعات المقيم">
                                            <i class="fas fa-notes-medical me-1"></i> متابعات المقيم
                                        </a>
                                        <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-secondary fw-semibold px-2.5 py-1.5 shadow-sm rounded-pill" title="عرض السجل العام للعملية">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <i class="fas fa-procedures text-muted fa-3x mb-3 opacity-25"></i>
                                        <h6 class="fw-bold text-secondary">لا توجد عمليات محالة لمحطة الجراح حالياً</h6>
                                        <p class="text-muted small mb-0">تظهر هنا تلقائياً العمليات بعد انتهاء وخروج المريض من صالة العمليات</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
