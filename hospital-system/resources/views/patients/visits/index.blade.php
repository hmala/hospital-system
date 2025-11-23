@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-calendar-check me-2"></i>
                    زياراتي الطبية
                </h2>
                <small class="text-muted">{{ auth()->user()->name }}</small>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- قائمة الزيارات -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        سجل الزيارات الطبية
                    </h5>
                </div>
                <div class="card-body">
                    @if($visits->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>تاريخ الزيارة</th>
                                        <th>الطبيب</th>
                                        <th>نوع الزيارة</th>
                                        <th>التشخيص</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($visits as $visit)
                                    <tr>
                                        <td>
                                            {{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد' }}
                                            @if($visit->visit_time)
                                                <br><small class="text-muted">{{ $visit->visit_time ? $visit->visit_time->format('H:i') : 'غير محدد' }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            د. {{ $visit->doctor?->user?->name ?? 'غير محدد' }}
                                            <br><small class="text-muted">{{ $visit->doctor?->specialization ?? 'غير محدد' }}</small>
                                        </td>
                                        <td>{{ $visit->visit_type_text }}</td>
                                        <td>
                                            @if($visit->diagnosis && isset($visit->diagnosis['description']))
                                                <span class="badge bg-info">{{ Str::limit($visit->diagnosis['description'], 30) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $visit->status == 'completed' ? 'success' : 'warning' }}">
                                                {{ $visit->status_text }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('patient.visits.show', $visit) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>
                                                عرض التفاصيل
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $visits->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد زيارات طبية</h5>
                            <p class="text-muted">ستظهر هنا سجلات زياراتك الطبية</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection