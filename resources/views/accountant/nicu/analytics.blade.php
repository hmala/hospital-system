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
        border-right: 4px solid #0dcaf0;
    }
</style>

<div class="container-fluid py-4">
    <!-- Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white p-4">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-info bg-opacity-10 text-info p-3 rounded-4">
                        <i class="fas fa-baby fs-2"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold text-dark mb-1">
                            لوحة تحليلات وإحصاءات الخدج والحاضنات (NICU)
                        </h2>
                        <p class="text-muted small mb-0">تتبع استهلاك خدمات الحواضن، معدلات الإشغال، والنشاط المالي والسريري للأطباء</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                <form method="GET" action="{{ route('accountant.nicu.analytics') }}" class="d-inline-flex gap-2 align-items-center bg-light p-2 rounded-3 border">
                    <span class="text-muted small fw-bold ms-1"><i class="fas fa-calendar-alt me-1"></i> الفترة:</span>
                    <input type="date" name="start_date" class="form-control form-control-sm border-0 bg-white shadow-sm" value="{{ $startDate }}">
                    <span class="text-muted small">إلى</span>
                    <input type="date" name="end_date" class="form-control form-control-sm border-0 bg-white shadow-sm" value="{{ $endDate }}">
                    <button type="submit" class="btn btn-sm btn-info text-white px-3 rounded-2">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl col-md-4 col-sm-6">
            <div class="card analytics-card shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">حالات دخول الخدج</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ number_format($totalNicuCases) }}</h3>
                        <small class="text-muted">
                            <span class="text-primary fw-bold">{{ $cashCasesCount }} نقدي</span> | 
                            <span class="text-success fw-bold">{{ $insuredCasesCount }} ضمان</span>
                        </small>
                    </div>
                    <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-baby"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-4 col-sm-6">
            <div class="card analytics-card shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">نسبة إشغال الحاضنات</span>
                        <h3 class="fw-bold text-dark mt-1 mb-0">{{ $occupancyRate }}%</h3>
                        <small class="text-info fw-bold">{{ $activeIncubatorsCount }} مشغولة من {{ $totalIncubatorsCount }}</small>
                    </div>
                    <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                        <i class="fas fa-procedures"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-4 col-sm-6">
            <div class="card analytics-card shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">المقبوضات والأمانات</span>
                        <h3 class="fw-bold text-success mt-1 mb-0">{{ number_format($totalPaymentsReceived) }} <span class="fs-6">د.ع</span></h3>
                        <small class="text-danger">مسترجع: {{ number_format($totalPaymentsRefunded) }} د.ع</small>
                    </div>
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6 col-sm-6">
            <div class="card analytics-card shadow-sm bg-white p-3 h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">🛡️ مطالبات الضمان للخدج</span>
                        <h3 class="fw-bold text-success mt-1 mb-0">+ {{ number_format($totalInsuranceClaims) }} <span class="fs-6">د.ع</span></h3>
                        <small class="text-success fw-semibold"><i class="fas fa-shield-alt me-1"></i>المبالغ التي تطلبها المستشفى</small>
                    </div>
                    <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl col-md-6 col-sm-12">
            <div class="card analytics-card shadow-sm bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold">صافي إيراد الصندوق</span>
                        <h3 class="fw-bold text-info mt-1 mb-0">{{ number_format($netNicuRevenue) }} <span class="fs-6">د.ع</span></h3>
                        <small class="text-muted">الصافي الفعلي المحصل بالصندوق</small>
                    </div>
                    <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-vault"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بطاقة تحليل وتصفيات الضمان الصحي وفق مصفوفة عمر الحمل الوزارية -->
    <div class="card analytics-card shadow-sm bg-white mb-4 border-start border-4 border-success">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="fw-bold text-dark mb-1 fs-6">
                    <i class="fas fa-shield-alt text-success me-2"></i>
                    تصفيات ومطالبات الضمان الصحي للخدج وفق مصفوفة عمر الحمل الوزارية
                </h5>
                <p class="text-muted small mb-0">المبالغ التي تطلبها المستشفى من هيئة الضمان بناءً على أسابيع الحمل والأيام الفندقية المعتمدة</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('cashier.nicu.statements', ['insurance_type' => 'insured']) }}" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-file-invoice-dollar me-1"></i> كشف مطالبات الضمان التفصيلي
                </a>
                <a href="{{ url('/nicu/services') }}" target="_blank" class="btn btn-sm btn-light border text-muted">
                    <i class="fas fa-table me-1"></i> جدول اللائحة الوزارية
                </a>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 border rounded-3">
                    <thead class="table-light">
                        <tr class="small text-muted">
                            <th>شريحة العمر الحملي والحد الأقصى للتغطية</th>
                            <th class="text-center">عدد الحالات</th>
                            <th class="text-end text-success">إجمالي مطالبة الضمان (تطلبها المستشفى)</th>
                            <th class="text-end text-primary">حصة المريض المقررة (كاش)</th>
                            <th class="text-end">الإجمالي الكلي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gestationalMatrixStats as $tier)
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="fas fa-layer-group text-primary me-2"></i>
                                    {{ $tier['label'] }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary bg-opacity-10 text-primary fs-7 px-3 py-1.5">
                                        {{ $tier['count'] }} طفل
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-success fs-6">
                                    + {{ number_format($tier['claims_sum']) }} د.ع
                                </td>
                                <td class="text-end fw-bold text-primary fs-6">
                                    {{ number_format($tier['patient_sum']) }} د.ع
                                </td>
                                <td class="text-end fw-bold text-dark fs-6">
                                    {{ number_format($tier['claims_sum'] + $tier['patient_sum']) }} د.ع
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-shield-alt fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                                    لا توجد حالات ضمان مسجلة خلال هذه الفترة
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($gestationalMatrixStats->isNotEmpty())
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td>المجموع التراكمي لمطالبات الضمان:</td>
                                <td class="text-center">{{ $gestationalMatrixStats->sum('count') }} حالة</td>
                                <td class="text-end text-success fs-6">+ {{ number_format($gestationalMatrixStats->sum('claims_sum')) }} د.ع</td>
                                <td class="text-end text-primary fs-6">{{ number_format($gestationalMatrixStats->sum('patient_sum')) }} د.ع</td>
                                <td class="text-end text-dark fs-6">{{ number_format($gestationalMatrixStats->sum('claims_sum') + $gestationalMatrixStats->sum('patient_sum')) }} د.ع</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
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
                        <i class="fas fa-chart-pie text-info me-2"></i>
                        خدمات الخدج الأكثر استخداماً
                    </h5>
                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill">أعلى الخدمات طلباً</span>
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
                                    <th class="text-center">الأيام المحتسبة</th>
                                    <th class="text-center">عدد الاستخدام</th>
                                    <th class="text-end">الإيراد التقديري</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topServices as $service)
                                <tr>
                                    <td class="fw-bold text-dark small">
                                        {{ $service->name }}
                                        @if($service->code)
                                            <span class="badge bg-light text-muted border ms-1">{{ $service->code }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center"><span class="badge bg-info bg-opacity-10 text-info">{{ $service->total_days }} يوم</span></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border">{{ $service->usage_count }}</span></td>
                                    <td class="text-end text-success fw-bold small">{{ number_format($service->total_revenue) }} د.ع</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">لا توجد خدمات مسجلة بالفترة المحددة</td>
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
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        مقارنة نشاط أطباء الأطفال والخدج
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

    <!-- Doctors Detailed Cards Grid Section -->
    <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
        <h5 class="fw-bold text-dark mb-0">
            <i class="fas fa-user-md text-info me-2"></i>
            تفاصيل نشاط أطباء الأطفال والخدج
        </h5>
        <span class="text-muted small">عرض الحالات والاستشارات الأكثر طلباً لكل طبيب</span>
    </div>

    <div class="row g-3">
        @forelse($doctorsActivity as $index => $doc)
        <div class="col-lg-6">
            <div class="card analytics-card shadow-sm bg-white p-3 doctor-chart-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fas fa-stethoscope text-info me-1"></i>
                            د. {{ $doc['doctor_name'] }}
                        </h6>
                        <small class="text-muted">{{ $doc['specialization'] }}</small>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary">
                            الحالات: {{ $doc['total_cases'] }}
                        </span>
                        <span class="badge bg-success bg-opacity-10 text-success">
                            استشارات: {{ $doc['consultations_count'] }}
                        </span>
                        <span class="badge bg-info bg-opacity-10 text-info">
                            طلبات: {{ $doc['orders_count'] }}
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
                            <span class="small">لا توجد خدمات خاصة مسجلة لهذا الطبيب بالفترة الحالية</span>
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
    // 1. Top Services Bar Chart
    const topServicesData = @json($topServices);
    const serviceLabels = topServicesData.map(s => s.name);
    const serviceCounts = topServicesData.map(s => s.usage_count);

    if (document.getElementById('topServicesChart') && serviceLabels.length > 0) {
        new Chart(document.getElementById('topServicesChart'), {
            type: 'bar',
            data: {
                labels: serviceLabels,
                datasets: [{
                    label: 'عدد مرات الاستخدام',
                    data: serviceCounts,
                    backgroundColor: 'rgba(13, 202, 240, 0.85)',
                    borderColor: '#0dcaf0',
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
    }

    // 2. Doctor Activity Comparison Chart
    const doctorsData = @json($doctorsActivity);
    const doctorNames = doctorsData.map(d => d.doctor_name);
    const doctorCases = doctorsData.map(d => d.total_cases);
    const doctorConsultations = doctorsData.map(d => d.consultations_count);

    if (document.getElementById('doctorsChart') && doctorNames.length > 0) {
        new Chart(document.getElementById('doctorsChart'), {
            type: 'bar',
            data: {
                labels: doctorNames,
                datasets: [
                    {
                        label: 'حالات الخدج المشرف عليها',
                        data: doctorCases,
                        backgroundColor: 'rgba(59, 130, 246, 0.8)',
                        borderRadius: 4
                    },
                    {
                        label: 'استشارات الخدج المنفذة',
                        data: doctorConsultations,
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
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
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { ticks: { font: { size: 10 } } }
                }
            }
        });
    }

    // 3. Individual Doctor Horizontal Bar Charts
    doctorsData.forEach((doc, idx) => {
        const canvas = document.getElementById(`docChart-${idx}`);
        if (!canvas || !doc.top_services || doc.top_services.length === 0) return;

        const labels = doc.top_services.map(s => s.name);
        const counts = doc.top_services.map(s => s.count);

        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'عدد الاستخدام',
                    data: counts,
                    backgroundColor: 'rgba(13, 202, 240, 0.65)',
                    borderColor: '#0dcaf0',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 } },
                    y: { ticks: { font: { size: 9 } } }
                }
            }
        });
    });
});
</script>
@endsection
