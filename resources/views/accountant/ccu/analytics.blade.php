@extends('layouts.app')

@section('title', 'التحليلات المحاسبية والضمانية للعناية المركزة للكبار (CCU)')

@section('content')
<div class="container-fluid py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="fas fa-chart-pie text-danger me-2"></i>
                التحليلات المحاسبية ومطالبات الضمان — العناية المركزة للكبار (CCU Analytics)
            </h3>
            <p class="text-muted small mb-0">
                متابعة الإيرادات الفعلية، مطالبات الضمان وفق شرائح الأيام، القيمة الأصلية للفحوصات المشمولة بالباقة، والباقي بذمة الضمان الصحي.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('ccu.services.index') }}" class="btn btn-outline-primary btn-sm">إدارة الأسعار والشرائح</a>
            <a href="{{ route('cashier.ccu.statements') }}" class="btn btn-outline-success btn-sm">كشوفات حسابات CCU</a>
            <a href="{{ route('insurance.claims.index') }}" class="btn btn-outline-dark btn-sm">مطالبات الضمان الصحي</a>
        </div>
    </div>

    <!-- فلتر الفترة -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form method="GET" action="{{ route('accountant.ccu.analytics') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">من تاريخ</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">إلى تاريخ</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary fw-bold w-100">تحديث التقرير</button>
            </div>
        </form>
    </div>

    <!-- مؤشرات KPI -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-primary">
                <small class="text-muted">حالات العناية (الفترة)</small>
                <h4 class="fw-bold text-primary mb-0">{{ $totalCcuCases }}</h4>
                <small class="text-muted">ضمان: {{ $insuredCasesCount }} | كاش: {{ $cashCasesCount }}</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-warning">
                <small class="text-muted">إشغال الأسرة</small>
                <h4 class="fw-bold text-dark mb-0">{{ $occupancyRate }}%</h4>
                <small class="text-muted">{{ $activeBedsCount }} من {{ $totalBedsCount }} سرير</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-success">
                <small class="text-muted">صافي مقبوضات الصندوق</small>
                <h4 class="fw-bold text-success mb-0 font-monospace">{{ number_format($netCcuRevenue) }}</h4>
                <small class="text-muted">مستردات: {{ number_format($totalPaymentsRefunded) }}</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-info">
                <small class="text-muted">أصل الفحوصات المشمولة</small>
                <h4 class="fw-bold text-info mb-0 font-monospace">{{ number_format($totalBundledReferenceValue) }}</h4>
                <small class="text-muted">مدمجة بأجرة الرقود (0 للمريض)</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-dark">
                <small class="text-muted">مطالبات الضمان الفعلية</small>
                <h4 class="fw-bold text-dark mb-0 font-monospace">{{ number_format($totalInsuranceClaims) }}</h4>
                <small class="text-muted">المُحصّل: {{ number_format($totalHiaCollected) }}</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-4 border-danger">
                <small class="text-muted">الباقي بذمة الضمان</small>
                <h4 class="fw-bold text-danger mb-0 font-monospace">{{ number_format($totalHiaRemaining) }}</h4>
                <small class="text-muted">ذمم غير محصلة بعد</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- تفصيل الشرائح -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-layer-group text-success me-1"></i> توزيع أيام الرقود حسب شرائح الضمان التنازلية</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">الشريحة المطبقة</th>
                                <th>عدد الأيام</th>
                                <th>حصة الضمان</th>
                                <th>حصة المرضى</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tierStats as $t)
                                <tr>
                                    <td class="text-start fw-bold">{{ $t['label'] }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $t['days_count'] }} يوم</span></td>
                                    <td class="text-success font-monospace fw-bold">{{ number_format($t['hia_sum']) }}</td>
                                    <td class="text-danger font-monospace">{{ number_format($t['patient_sum']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted py-4">لا توجد بيانات ضمن الفترة المحددة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- الباقات والخدمات الأكثر استخداماً + الأطباء -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-procedures text-primary me-1"></i> تفصيل الإيراد حسب الباقات والخدمات المنفصلة</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">الباقة / الخدمة</th>
                                <th>السجلات</th>
                                <th>إجمالي القيمة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topServices as $s)
                                <tr>
                                    <td class="text-start fw-bold">{{ $s->service_name_snapshot }}</td>
                                    <td>{{ $s->uses_count }}</td>
                                    <td class="font-monospace fw-bold text-primary">{{ number_format($s->total_billed) }} د.ع</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted py-4">لا توجد سجلات باقات ضمن الفترة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-user-md text-info me-1"></i> نشاط الأطباء المشرفين في العناية</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">الطبيب المشرف</th>
                                <th>عدد الحالات</th>
                                <th>حالات الضمان</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($doctorsActivity as $doc)
                                <tr>
                                    <td class="text-start fw-bold">{{ $doc->doctor_name }}</td>
                                    <td>{{ $doc->cases_count }}</td>
                                    <td><span class="badge bg-success-subtle text-success">{{ $doc->insured_count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted py-4">لا يوجد أطباء مسجلون في الفترة.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
