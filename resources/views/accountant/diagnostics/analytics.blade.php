@extends('layouts.app')

@section('content')
<style>
    .analytics-card {
        border: none;
        border-radius: 12px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .analytics-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important;
    }
    .stat-icon-wrapper {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
</style>

<div class="container-fluid py-4">
    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-4">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4">
                        <i class="fas fa-x-ray fs-2"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold text-dark mb-1">
                            حسابات وتحليلات التشخيص (المختبر، المفراس، الإيكو، الرنين، والأشعة)
                        </h2>
                        <p class="text-muted small mb-0">تحليل مالي وتشغيلي دقيق لكافة أجهزة وفحوصات التشخيص الطبي</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                <form method="GET" action="{{ route('accountant.diagnostics.analytics') }}" class="d-inline-flex gap-2 align-items-center bg-light p-2 rounded-3 border">
                    <span class="text-muted small fw-bold ms-1"><i class="fas fa-calendar-alt me-1"></i> الفترة:</span>
                    <input type="date" name="start_date" class="form-control form-control-sm border-0 bg-white shadow-sm" value="{{ $startDate }}">
                    <span class="text-muted small">إلى</span>
                    <input type="date" name="end_date" class="form-control form-control-sm border-0 bg-white shadow-sm" value="{{ $endDate }}">
                    <button type="submit" class="btn btn-sm btn-primary px-3 rounded-2">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card analytics-card shadow-sm bg-white p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">إجمالي الفحوصات المنفذة</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalLabCount + $totalRadCount) }}</h3>
                        <small class="text-muted">مختبر وأشعة وطوارئ</small>
                    </div>
                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-microscope"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card analytics-card shadow-sm bg-white p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">فحوصات المختبر الكليّة</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalLabCount) }}</h3>
                        <small class="text-primary fw-bold" style="font-size: 0.75rem;">
                            عادية: {{ $normalLabCount }} | استشارية: {{ $consultantLabCount }} | طوارئ: {{ $emergencyLabCount }}
                        </small>
                    </div>
                    <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                        <i class="fas fa-vials"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card analytics-card shadow-sm bg-white p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">فحوصات الأشعة والتصوير</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalRadCount) }}</h3>
                        <small class="text-warning fw-bold" style="font-size: 0.75rem;">
                            عادية: {{ $normalRadCount }} | استشارية: {{ $consultantRadCount }} | طوارئ: {{ $emergencyRadCount }}
                        </small>
                    </div>
                    <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-radiation"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card analytics-card shadow-sm bg-white p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">عدد الأطباء الطالبين</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ $doctorsPerformance->count() }}</h3>
                        <small class="text-muted">طوارئ واستشاريين</small>
                    </div>
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="fas fa-user-md"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparison Matrix Table & Dedicated Charts Row -->
    <div class="card analytics-card shadow-sm bg-white mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold text-dark mb-0 fs-6">
                    <i class="fas fa-table-cells text-primary me-2"></i>
                    جدول مقارنة الفحوصات المنفذة (طوارئ vs عادية vs استشارية)
                </h5>
                <small class="text-muted">مقارنة شاملة ودقيقة للخدمات الأكثر استهلاكاً حسب قسم التشخيص ومصدر الطلب</small>
            </div>
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-bold">
                <i class="fas fa-layer-group me-1"></i> مصفوفة مقارنة تفاعلية
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">اسم الفحص / الخدمة</th>
                            <th style="width: 15%;">القسم والنوع</th>
                            <th class="text-center" style="width: 12%;"><span class="badge bg-danger">🔴 طوارئ</span></th>
                            <th class="text-center" style="width: 12%;"><span class="badge bg-primary">🔵 عادية</span></th>
                            <th class="text-center" style="width: 12%;"><span class="badge bg-purple" style="background-color: #7c3aed; color: #fff;">🟣 استشارية</span></th>
                            <th class="text-center" style="width: 12%;">إجمالي الطلبات</th>
                            <th style="width: 12%;">توزيع النسبة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $allMatrix = collect($topLabTestsBreakdown)->map(function($i) { $i['dept'] = 'مختبر'; return $i; })
                                ->merge(collect($topRadTestsBreakdown)->map(function($i) { $i['dept'] = 'أشعة وتصوير'; return $i; }))
                                ->sortByDesc('total_count');
                            $maxCount = $allMatrix->max('total_count') ?: 1;
                        @endphp
                        @foreach($allMatrix as $item)
                        <tr>
                            <td class="fw-bold text-dark">
                                <i class="{{ $item['dept'] === 'مختبر' ? 'fas fa-vial text-info' : 'fas fa-x-ray text-warning' }} me-2"></i>
                                {{ $item['name'] }}
                            </td>
                            <td>
                                <span class="badge {{ $item['dept'] === 'مختبر' ? 'bg-info-subtle text-info' : 'bg-warning-subtle text-warning' }} px-2 py-1">
                                    {{ $item['dept'] }}
                                </span>
                            </td>
                            <td class="text-center fw-bold text-danger fs-6">{{ $item['emergency_count'] }}</td>
                            <td class="text-center fw-bold text-primary fs-6">{{ $item['normal_count'] }}</td>
                            <td class="text-center fw-bold fs-6" style="color: #7c3aed;">{{ $item['consultant_count'] }}</td>
                            <td class="text-center fw-bold text-dark fs-6">
                                <span class="badge bg-light text-dark border px-3 py-1 fs-6">
                                    {{ $item['total_count'] }}
                                </span>
                            </td>
                            <td>
                                <div class="progress" style="height: 8px; border-radius: 4px;">
                                    <div class="progress-bar bg-danger" style="width: {{ ($item['emergency_count'] / $maxCount) * 100 }}%"></div>
                                    <div class="progress-bar bg-primary" style="width: {{ ($item['normal_count'] / $maxCount) * 100 }}%"></div>
                                    <div class="progress-bar" style="background-color: #7c3aed; width: {{ ($item['consultant_count'] / $maxCount) * 100 }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Charts Row: Dedicated Lab & Dedicated Radiology Charts -->
    <div class="row g-4 mb-4">
        <!-- Dedicated Lab Chart -->
        <div class="col-lg-6">
            <div class="card analytics-card shadow-sm bg-white h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold text-dark mb-0 fs-6">
                        <i class="fas fa-flask text-info me-2"></i>
                        مخطط تحاليل المختبر (طوارئ vs عادية vs استشارية)
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 320px;">
                        <canvas id="topLabChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dedicated Radiology Chart -->
        <div class="col-lg-6">
            <div class="card analytics-card shadow-sm bg-white h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold text-dark mb-0 fs-6">
                        <i class="fas fa-radiation text-warning me-2"></i>
                        مخطط فحوصات الأشعة/المفراس/الإيكو (طوارئ vs عادية vs استشارية)
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 320px;">
                        <canvas id="topRadiologyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Doctor Requests Table -->
    <div class="card analytics-card shadow-sm bg-white">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold text-dark mb-0 fs-6">
                <i class="fas fa-user-md text-success me-2"></i>
                حركة وإيرادات أطباء التشخيص والأشعة والطوارئ
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الطبيب المعالج / الطالب</th>
                            <th class="text-center">إجمالي الطلبات المنفذة</th>
                            <th class="text-end">حالة الطلبات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($doctorsPerformance as $doc)
                        <tr>
                            <td class="fw-bold text-dark">
                                <i class="fas fa-user-stethoscope text-primary me-2"></i>
                                {{ $doc['doctor_name'] }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 fs-6">
                                    {{ $doc['total_requests'] }} طلب
                                </span>
                            </td>
                            <td class="text-end text-success fw-bold fs-6">
                                <span class="badge bg-success-subtle text-success">مكتمل ومُتابع</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">لا توجد بيانات أطباء بالفترة المحددة</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Dedicated Lab Tests Chart
    const topLabData = @json($topLabTestsBreakdown);
    new Chart(document.getElementById('topLabChart'), {
        type: 'bar',
        data: {
            labels: topLabData.map(r => r.name),
            datasets: [
                {
                    label: '🔴 طوارئ',
                    data: topLabData.map(r => r.emergency_count),
                    backgroundColor: 'rgba(239, 68, 68, 0.85)',
                    borderColor: '#dc2626',
                    borderWidth: 1
                },
                {
                    label: '🔵 عادية',
                    data: topLabData.map(r => r.normal_count),
                    backgroundColor: 'rgba(59, 130, 246, 0.85)',
                    borderColor: '#2563eb',
                    borderWidth: 1
                },
                {
                    label: '🟣 استشارية',
                    data: topLabData.map(r => r.consultant_count),
                    backgroundColor: 'rgba(139, 92, 246, 0.85)',
                    borderColor: '#7c3aed',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: true, position: 'top' } },
            scales: {
                x: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
                y: { stacked: true }
            }
        }
    });

    // 2. Dedicated Radiology & Imaging Chart
    const topRadData = @json($topRadTestsBreakdown);
    new Chart(document.getElementById('topRadiologyChart'), {
        type: 'bar',
        data: {
            labels: topRadData.map(r => r.name),
            datasets: [
                {
                    label: '🔴 طوارئ',
                    data: topRadData.map(r => r.emergency_count),
                    backgroundColor: 'rgba(239, 68, 68, 0.85)',
                    borderColor: '#dc2626',
                    borderWidth: 1
                },
                {
                    label: '🔵 عادية',
                    data: topRadData.map(r => r.normal_count),
                    backgroundColor: 'rgba(59, 130, 246, 0.85)',
                    borderColor: '#2563eb',
                    borderWidth: 1
                },
                {
                    label: '🟣 استشارية',
                    data: topRadData.map(r => r.consultant_count),
                    backgroundColor: 'rgba(139, 92, 246, 0.85)',
                    borderColor: '#7c3aed',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: true, position: 'top' } },
            scales: {
                x: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
                y: { stacked: true }
            }
        }
    });
});
</script>
@endsection
