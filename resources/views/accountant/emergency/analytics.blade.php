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
    .doctor-chart-card {
        border-right: 4px solid #3b82f6;
    }
</style>

<div class="container-fluid py-4">
    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-4">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-4">
                        <i class="fas fa-chart-pie fs-2"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold text-dark mb-1">
                            لوحة تحليلات وإحصاءات خدمات الطوارئ
                        </h2>
                        <p class="text-muted small mb-0">تتبع الخدمات الطبية الأنشط ومخطط الاستهلاك التفصيلي لكل طبيب طوارئ</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                <form method="GET" action="{{ route('accountant.emergency.analytics') }}" class="d-inline-flex gap-2 align-items-center bg-light p-2 rounded-3 border">
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
        <div class="col-md-4">
            <div class="card analytics-card shadow-sm bg-white p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">إجمالي حالات الطوارئ</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalEmergencyCases) }}</h3>
                    </div>
                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-ambulance"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card analytics-card shadow-sm bg-white p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">إجمالي الخدمات المقدمة</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalServicesProvided) }}</h3>
                    </div>
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="fas fa-briefcase-medical"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card analytics-card shadow-sm bg-white p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">الأطباء النشطون بالمناوبة</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ $doctorsActivity->count() }}</h3>
                    </div>
                    <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                        <i class="fas fa-user-md"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Section: Overview Charts -->
    <div class="row g-4 mb-4">
        <!-- Top Services Chart -->
        <div class="col-lg-6">
            <div class="card analytics-card shadow-sm bg-white h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0 fs-6">
                        <i class="fas fa-chart-pie text-primary me-2"></i>
                        الخدمات الأكثر استخداماً بالقسم
                    </h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">أعلى 10 خدمات</span>
                </div>
                <div class="card-body">
                    <div style="height: 250px;" class="mb-3">
                        <canvas id="topServicesChart"></canvas>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0 border-top">
                            <thead class="table-light">
                                <tr class="small">
                                    <th>اسم الخدمة</th>
                                    <th class="text-center">عدد الاستخدام</th>
                                    <th class="text-end">الإيراد التقديري</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topServices as $service)
                                <tr>
                                    <td class="fw-bold text-dark small">{{ $service->name }}</td>
                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $service->usage_count }}</span></td>
                                    <td class="text-end text-success fw-bold small">{{ number_format($service->total_revenue) }} د.ع</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">لا توجد خدمات مسجلة</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctor General Comparison -->
        <div class="col-lg-6">
            <div class="card analytics-card shadow-sm bg-white h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold text-dark mb-0 fs-6">
                        <i class="fas fa-chart-bar text-success me-2"></i>
                        مقارنة حجم العمل العام لكل طبيب طوارئ
                    </h5>
                </div>
                <div class="card-body">
                    <div style="height: 380px;">
                        <canvas id="doctorsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Doctors Detailed Charts Grid Section (ترتيب أنيق ومنظم في كروت متناسقة) -->
    <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
        <h5 class="fw-bold text-dark mb-0">
            <i class="fas fa-user-md text-danger me-2"></i>
            مخططات الخدمات الخاصة بكل طبيب طوارئ
        </h5>
        <span class="text-muted small">عرض الخدمات الأكثر طلباً لكل طبيب على حدة</span>
    </div>

    <div class="row g-3">
        @forelse($doctorsActivity as $index => $doc)
        <div class="col-lg-6">
            <div class="card analytics-card shadow-sm bg-white p-3 doctor-chart-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fas fa-stethoscope text-primary me-1"></i>
                            d. {{ $doc['doctor_name'] }}
                        </h6>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary">
                            الحالات: {{ $doc['total_cases'] }}
                        </span>
                        <span class="badge bg-info bg-opacity-10 text-info">
                            الخدمات: {{ $doc['services_count'] }}
                        </span>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if(count($doc['top_services']) > 0)
                        <div style="height: 140px;">
                            <canvas id="docChart-{{ $index }}"></canvas>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted bg-light rounded-3">
                            <i class="fas fa-info-circle mb-1"></i><br>
                            <span class="small">لا توجد خدمات مسجلة لهذا الطبيب بالفترة الحالية</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 shadow-sm p-4 text-center text-muted">
                لا توجد بيانات أطباء مسجلة بالفترة المحددة
            </div>
        </div>
        @endforelse
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Top Services Bar Chart (مخطط أعمدة للخدمات الأكثر استخداماً)
    const topServicesData = @json($topServices);
    const serviceLabels = topServicesData.map(s => s.name);
    const serviceCounts = topServicesData.map(s => s.usage_count);

    new Chart(document.getElementById('topServicesChart'), {
        type: 'bar',
        data: {
            labels: serviceLabels,
            datasets: [{
                label: 'عدد مرات الاستخدام',
                data: serviceCounts,
                backgroundColor: 'rgba(59, 130, 246, 0.85)',
                borderColor: '#2563eb',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { ticks: { font: { size: 10, weight: 'bold' } } }
            }
        }
    });

    // 2. Doctor Activity Grouped Bar Chart
    const doctorsData = @json($doctorsActivity);
    const docLabels = doctorsData.map(d => d.doctor_name);
    const docServices = doctorsData.map(d => d.services_count);
    const docLab = doctorsData.map(d => d.lab_count);
    const docRad = doctorsData.map(d => d.rad_count);

    new Chart(document.getElementById('doctorsChart'), {
        type: 'bar',
        data: {
            labels: docLabels,
            datasets: [
                {
                    label: 'خدمات طبية',
                    data: docServices,
                    backgroundColor: 'rgba(59, 130, 246, 0.85)',
                    borderRadius: 4
                },
                {
                    label: 'تحاليل',
                    data: docLab,
                    backgroundColor: 'rgba(245, 158, 11, 0.85)',
                    borderRadius: 4
                },
                {
                    label: 'أشعة',
                    data: docRad,
                    backgroundColor: 'rgba(107, 114, 128, 0.85)',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });

    // 3. Individual Doctor Charts (شبكة كروت ممتازة مرتبة)
    doctorsData.forEach((doc, index) => {
        if (doc.top_services && doc.top_services.length > 0) {
            const canvasEl = document.getElementById(`docChart-${index}`);
            if (canvasEl) {
                new Chart(canvasEl, {
                    type: 'bar',
                    data: {
                        labels: doc.top_services.map(s => s.name),
                        datasets: [{
                            label: 'عدد الاستخدام',
                            data: doc.top_services.map(s => s.count),
                            backgroundColor: [
                                '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'
                            ],
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { beginAtZero: true, ticks: { precision: 0 } },
                            y: { ticks: { font: { size: 11, weight: 'bold' } } }
                        }
                    }
                });
            }
        }
    });
});
</script>
@endsection
