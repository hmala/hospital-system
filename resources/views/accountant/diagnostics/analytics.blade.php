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
                        <span class="text-muted small fw-bold">فحوصات المختبر</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalLabCount) }}</h3>
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
                        <span class="text-muted small fw-bold">فحوصات الأشعة/المفراس/الإيكو</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalRadCount) }}</h3>
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
                    </div>
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="fas fa-user-md"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Top Radiology Types Chart -->
        <div class="col-lg-6">
            <div class="card analytics-card shadow-sm bg-white h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0 fs-6">
                        <i class="fas fa-fire text-danger me-2"></i>
                        أعلى 10 فحوصات أشعة/مفراس/إيكو طلباً وإيراداً
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 320px;">
                        <canvas id="topRadiologyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Diagnostic Units Revenue Doughnut Chart -->
        <div class="col-lg-6">
            <div class="card analytics-card shadow-sm bg-white h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold text-dark mb-0 fs-6">
                        <i class="fas fa-chart-pie text-info me-2"></i>
                        توزيع الإيرادات حسب أجهزة ووحدات التشخيص
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 320px;">
                        <canvas id="categoryBreakdownChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Doctor Requests Table -->
    <div class="card analytics-card shadow-sm bg-white">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold text-dark mb-0 fs-6">
                <i class="fas fa-list text-secondary me-2"></i>
                حركة وإيرادات أطباء التشخيص والأشعة
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>الطبيب المعالج / الطالب</th>
                            <th class="text-center">إجمالي الطلبات المنفذة</th>
                            <th class="text-end">إجمالي القيمة الماليّة للطلبات</th>
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
                                {{ number_format($doc['total_value']) }} د.ع
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
    // 1. Top Radiology Types Chart
    const topRadData = @json($topRadiologyTypes);
    const radLabels = topRadData.map(r => r.name);
    const radCounts = topRadData.map(r => r.usage_count);

    new Chart(document.getElementById('topRadiologyChart'), {
        type: 'bar',
        data: {
            labels: radLabels,
            datasets: [{
                label: 'عدد الفحوصات المنفذة',
                data: radCounts,
                backgroundColor: 'rgba(239, 68, 68, 0.85)',
                borderColor: '#dc2626',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });

    // 2. Diagnostic Units Doughnut Chart
    const breakdownData = @json($categoryBreakdown);
    const categoryLabels = breakdownData.map(c => c.main_category || 'أخرى');
    const categoryAmounts = breakdownData.map(c => c.total_amount);

    const colors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#f97316'];

    new Chart(document.getElementById('categoryBreakdownChart'), {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryAmounts,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });
});
</script>
@endsection
