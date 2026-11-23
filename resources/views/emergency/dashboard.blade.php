<!-- resources/views/emergency/dashboard.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-chart-line me-2"></i>
                    {{ __('لوحة تحكم الطوارئ') }}
                </h2>
                <a href="{{ route('emergency.index') }}" class="btn btn-primary">
                    <i class="fas fa-list me-2"></i>{{ __('عرض جميع الحالات') }}
                </a>
            </div>
        </div>
    </div>

    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-left-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">{{ __('إجمالي الحالات اليوم') }}</h6>
                            <h3 class="mb-0">{{ $stats['total_today'] }}</h3>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-ambulance fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">{{ __('في الانتظار') }}</h6>
                            <h3 class="mb-0 text-warning">{{ $stats['waiting'] }}</h3>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">{{ __('في العلاج') }}</h6>
                            <h3 class="mb-0 text-info">{{ $stats['in_treatment'] }}</h3>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-user-md fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-left-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">{{ __('حرجة') }}</h6>
                            <h3 class="mb-0 text-danger">{{ $stats['critical'] }}</h3>
                        </div>
                        <div class="text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- توزيع الأولويات -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('توزيع الأولويات') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="priorityChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- أنواع الطوارئ -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('أنواع الطوارئ') }}</h5>
                </div>
                <div class="card-body">
                    <canvas id="typeChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- الحالات الحرجة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>{{ __('الحالات الحرجة') }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($criticalEmergencies->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('المريض') }}</th>
                                        <th>{{ __('نوع الطوارئ') }}</th>
                                        <th>{{ __('وقت الدخول') }}</th>
                                        <th>{{ __('العلامات الحيوية') }}</th>
                                        <th>{{ __('الإجراءات') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($criticalEmergencies as $emergency)
                                    <tr class="table-danger">
                                        <td>
                                            <strong>{{ $emergency->patient ? ($emergency->patient->user->name ?? __('مريض بدون بيانات')) : __('مريض غير معروف') }}</strong>
                                        </td>
                                        <td>{{ __($emergency->emergency_type_text) }}</td>
                                        <td>{{ $emergency->created_at->diffForHumans() }}</td>
                                        <td>
                                            <small>
                                                {{ __('ضغط:') }} {{ $emergency->blood_pressure ?? '---' }} |
                                                {{ __('نبض:') }} {{ $emergency->heart_rate ?? '---' }}
                                            </small>
                                        </td>
                                        <td>
                                            <a href="{{ route('emergency.show', $emergency) }}" class="btn btn-sm btn-danger">
                                                <i class="fas fa-eye"></i> {{ __('عرض') }}
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">{{ __('لا توجد حالات حرجة حالياً') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- قائمة انتظار الطوارئ -->
    <div class="row mb-4">
        <!-- الحالات في الانتظار -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        {{ __('الحالات في الانتظار') }}
                        <span class="badge bg-dark ms-2">{{ $waitingEmergencies->count() }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('المريض') }}</th>
                                    <th>{{ __('نوع الطوارئ') }}</th>
                                    <th>{{ __('الأولوية') }}</th>
                                    <th>{{ __('وقت الانتظار') }}</th>
                                    <th>{{ __('الإجراءات') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($waitingEmergencies as $emergency)
                                <tr class="{{ $emergency->priority == 'critical' ? 'table-danger' : ($emergency->priority == 'urgent' ? 'table-warning' : '') }}">
                                    <td><strong>#{{ $emergency->id }}</strong></td>
                                    <td>
                                        <div class="fw-bold">{{ $emergency->patient ? ($emergency->patient->user->name ?? __('مريض بدون بيانات')) : __('مريض غير معروف') }}</div>
                                        <small class="text-muted">{{ $emergency->patient ? ($emergency->patient->national_id ?? '') : '' }}</small>
                                    </td>
                                    <td>{{ __($emergency->emergency_type_text) }}</td>
                                    <td>
                                        <span class="badge {{ $emergency->priority_badge_class }}">{{ __($emergency->priority_text) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $emergency->admission_time->diffForHumans() }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('emergency.show', $emergency) }}" class="btn btn-sm btn-info mb-1" title="{{ __('عرض التفاصيل') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('emergency.start-treatment', $emergency) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success mb-1">
                                                <i class="fas fa-play me-1"></i>{{ __('بدء العلاج') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                        <p class="mb-0">{{ __('لا توجد حالات في الانتظار') }}</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- الحالات في العلاج -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-md me-2"></i>
                        {{ __('الحالات في العلاج') }}
                        <span class="badge bg-light text-info ms-2">{{ $inTreatmentEmergencies->count() }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('المريض') }}</th>
                                    <th>{{ __('نوع الطوارئ') }}</th>
                                    <th>{{ __('الأولوية') }}</th>
                                    <th>{{ __('وقت العلاج') }}</th>
                                    <th>{{ __('الإجراءات') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inTreatmentEmergencies as $emergency)
                                <tr class="{{ $emergency->priority == 'critical' ? 'table-danger' : ($emergency->priority == 'urgent' ? 'table-warning' : '') }}">
                                    <td><strong>#{{ $emergency->id }}</strong></td>
                                    <td>
                                        <div class="fw-bold">{{ $emergency->patient ? ($emergency->patient->user->name ?? __('مريض بدون بيانات')) : __('مريض غير معروف') }}</div>
                                        <small class="text-muted">{{ $emergency->patient ? ($emergency->patient->national_id ?? '') : '' }}</small>
                                    </td>
                                    <td>{{ __($emergency->emergency_type_text) }}</td>
                                    <td>
                                        <span class="badge {{ $emergency->priority_badge_class }}">{{ __($emergency->priority_text) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ $emergency->admission_time->diffForHumans() }}</span>
                                    </td>
                                    <td>
                                        <a href="{{ route('emergency.show', $emergency) }}" class="btn btn-sm btn-info mb-1" title="{{ __('عرض التفاصيل') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('emergency.complete', $emergency) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger mb-1">
                                                <i class="fas fa-stop me-1"></i>{{ __('إنهاء') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                                        <p class="mb-0">{{ __('لا توجد حالات في العلاج حالياً') }}</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- آخر الحالات -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">{{ __('آخر الحالات المسجلة') }}</h5>
                </div>
                <div class="card-body">
                    @if($recentEmergencies->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('المريض') }}</th>
                                        <th>{{ __('نوع الطوارئ') }}</th>
                                        <th>{{ __('الأولوية') }}</th>
                                        <th>{{ __('الحالة') }}</th>
                                        <th>{{ __('وقت الدخول') }}</th>
                                        <th>{{ __('الإجراءات') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentEmergencies as $emergency)
                                    <tr>
                                        <td>
                                            <strong>{{ $emergency->patient ? ($emergency->patient->user->name ?? __('مريض بدون بيانات')) : __('مريض غير معروف') }}</strong>
                                        </td>
                                        <td>{{ __($emergency->emergency_type_text) }}</td>
                                        <td>
                                            <span class="badge {{ $emergency->priority_badge_class }}">{{ __($emergency->priority_text) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $emergency->status_badge_class }}">{{ __($emergency->status_text) }}</span>
                                        </td>
                                        <td>{{ $emergency->created_at->diffForHumans() }}</td>
                                        <td>
                                            <a href="{{ route('emergency.show', $emergency) }}" class="btn btn-sm btn-outline-primary" title="{{ __('عرض التفاصيل') }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-ambulance fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('لا توجد حالات طوارئ مسجلة') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // مخطط توزيع الأولويات
    const priorityCtx = document.getElementById('priorityChart').getContext('2d');
    const priorityChart = new Chart(priorityCtx, {
        type: 'doughnut',
        data: {
            labels: [@json(__('حرجة')), @json(__('عالية')), @json(__('متوسطة')), @json(__('منخفضة'))],
            datasets: [{
                data: [
                    {{ $stats['priority_critical'] }},
                    {{ $stats['priority_high'] }},
                    {{ $stats['priority_medium'] }},
                    {{ $stats['priority_low'] }}
                ],
                backgroundColor: [
                    '#dc3545', // أحمر للحرجة
                    '#fd7e14', // برتقالي للعالية
                    '#ffc107', // أصفر للمتوسطة
                    '#28a745'  // أخضر للمنخفضة
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // مخطط أنواع الطوارئ
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    const typeChart = new Chart(typeCtx, {
        type: 'bar',
        data: {
            labels: [
                @foreach($stats['types'] as $type => $count)
                    @json(__(\App\Models\Emergency::getEmergencyTypeText($type))),
                @endforeach
            ],
            datasets: [{
                label: @json(__('عدد الحالات')),
                data: [
                    @foreach($stats['types'] as $type => $count)
                        {{ $count }},
                    @endforeach
                ],
                backgroundColor: '#007bff',
                borderColor: '#0056b3',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<style>
.border-left-primary {
    border-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }}: 4px solid #007bff !important;
}
.border-left-warning {
    border-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }}: 4px solid #ffc107 !important;
}
.border-left-info {
    border-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }}: 4px solid #17a2b8 !important;
}
.border-left-danger {
    border-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }}: 4px solid #dc3545 !important;
}
</style>
@endsection

@section('scripts')
<script>
// تحديث الصفحة تلقائياً كل 30 ثانية للحصول على البيانات الحديثة
setInterval(function() {
    location.reload();
}, 30000);
</script>
@endsection
