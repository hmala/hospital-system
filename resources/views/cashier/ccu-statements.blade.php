@extends('layouts.app')

@section('title', 'كشوفات وحسابات العناية المركزة للكبار (CCU Statements)')

@section('content')
<div class="container-fluid py-4" dir="rtl">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="fas fa-file-invoice-dollar text-primary me-2"></i>
                كشوفات حسابات ومطالبات العناية المركزة للكبار (CCU Statements)
            </h3>
            <p class="text-muted small mb-0">
                متابعة الإجماليات، الفحوصات المشمولة بقيمتها الأصلية، المطالبة الفعلية على هيئة الضمان، والباقي بذمة الضمان والمريض.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('ccu.services.index') }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-sliders-h me-1"></i> أسعار وشرائح CCU
            </a>
            <a href="{{ route('cashier.ccu.financial-movements') }}" class="btn btn-outline-dark btn-sm">
                <i class="fas fa-exchange-alt me-1"></i> الحركات المالية
            </a>
            <a href="{{ route('accountant.ccu.analytics') }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-chart-pie me-1"></i> التحليلات المحاسبية لـ CCU
            </a>
        </div>
    </div>

    <!-- الفلتر -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form method="GET" action="{{ route('cashier.ccu.statements') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">من تاريخ الدخول</label>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">إلى تاريخ الدخول</label>
                <input type="date" name="to_date" value="{{ $toDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">نوع التغطية</label>
                <select name="insurance_type" class="form-select form-select-sm">
                    <option value="">الكل (الضمان والكاش)</option>
                    <option value="insured" {{ $insuranceFilter === 'insured' ? 'selected' : '' }}>مشمول بالضمان الصحي فقط</option>
                    <option value="cash" {{ $insuranceFilter === 'cash' ? 'selected' : '' }}>نقدي (كاش) فقط</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary fw-bold flex-fill">بحث وفلترة</button>
                <a href="{{ route('cashier.ccu.statements') }}" class="btn btn-sm btn-outline-secondary">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <!-- بطاقات KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-0 shadow-xs rounded-4 p-3 bg-white border-top border-3 border-primary">
                <small class="text-muted">أجور الإقامة والباقات</small>
                <h5 class="fw-bold text-primary mt-1 mb-0 font-monospace">{{ number_format($totalServicesFee) }}</h5>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-xs rounded-4 p-3 bg-white border-top border-3 border-info">
                <small class="text-muted">أصل الفحوصات المشمولة</small>
                <h5 class="fw-bold text-info mt-1 mb-0 font-monospace">{{ number_format($totalBundledReferenceValue) }}</h5>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-xs rounded-4 p-3 bg-white border-top border-3 border-dark">
                <small class="text-muted">صافي مستحقات المرضى</small>
                <h5 class="fw-bold text-dark mt-1 mb-0 font-monospace">{{ number_format($totalPatientShares) }}</h5>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-xs rounded-4 p-3 bg-white border-top border-3 border-success">
                <small class="text-muted">صافي التحصيل (الكاشير)</small>
                <h5 class="fw-bold text-success mt-1 mb-0 font-monospace">{{ number_format($totalCollected) }}</h5>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-xs rounded-4 p-3 bg-white border-top border-3 border-warning">
                <small class="text-muted">مطالبة الضمان الفعلية</small>
                <h5 class="fw-bold text-dark mt-1 mb-0 font-monospace">{{ number_format($totalInsuranceClaims) }}</h5>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-xs rounded-4 p-3 bg-white border-top border-3 border-danger">
                <small class="text-muted">الباقي بذمة الضمان</small>
                <h5 class="fw-bold text-danger mt-1 mb-0 font-monospace">{{ number_format($totalHiaRemaining) }}</h5>
            </div>
        </div>
    </div>

    <!-- جدول كشوفات الإضبارات -->
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-center small">
                <thead class="table-light">
                    <tr>
                        <th>الإضبارة / المريض</th>
                        <th>السرير والباقة</th>
                        <th>الأيام</th>
                        <th>نوع الحساب</th>
                        <th>أصل المشمول</th>
                        <th>حصة المريض</th>
                        <th>المدفوع / المتبقي</th>
                        <th>مطالبة الضمان</th>
                        <th>الباقي بذمة الضمان</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($admissions as $adm)
                        @php
                            $d = $billingService->calculateStayDetails($adm);
                        @endphp
                        <tr>
                            <td class="text-start">
                                <div class="fw-bold text-dark">#{{ $adm->id }} — {{ $adm->patient?->user?->name ?? 'مريض' }}</div>
                                <small class="text-muted">{{ optional($adm->admission_date)->format('Y-m-d') }}</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $adm->bed?->bed_number }}</span>
                                <div class="small text-muted">{{ $d['active_service']?->name ?? 'تلقائي' }}</div>
                            </td>
                            <td class="fw-bold">{{ $d['days_count'] }} يوم</td>
                            <td>
                                @if($d['is_insured'])
                                    <span class="badge bg-success">ضمان ({{ $d['policy_category'] }})</span>
                                @else
                                    <span class="badge bg-secondary">كاش</span>
                                @endif
                            </td>
                            <td class="font-monospace text-info fw-bold">{{ number_format($d['bundled_reference_total']) }}</td>
                            <td class="font-monospace fw-bold">{{ number_format($d['patient_grand_total']) }}</td>
                            <td class="font-monospace">
                                <span class="text-success">{{ number_format($d['total_paid']) }}</span>
                                /
                                <span class="text-danger fw-bold">{{ number_format($d['net_balance']) }}</span>
                            </td>
                            <td class="font-monospace text-success fw-bold">{{ number_format($d['insurance_grand_total']) }}</td>
                            <td class="font-monospace text-danger fw-bold">{{ number_format($d['hia_remaining_balance']) }}</td>
                            <td class="text-nowrap">
                                <a href="{{ route('cashier.ccu.payment.form', $adm->id) }}" class="btn btn-sm btn-primary py-0 px-2">الكاشير</a>
                                <a href="{{ route('cashier.ccu.itemized-receipt.print', $adm->id) }}" target="_blank" class="btn btn-sm btn-outline-dark py-0 px-2">طباعة</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-muted py-5">لا توجد إضبارات عناية مركزة مطابقة لمعايير البحث.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($admissions->hasPages())
            <div class="p-3 border-top">{{ $admissions->links() }}</div>
        @endif
    </div>
</div>
@endsection
