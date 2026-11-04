@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-file-invoice-dollar me-2 text-info"></i>كشوفات حسابات الخدج والحاضنات</h2>
                <p class="text-muted mb-0">تقرير تفصيلي بإيرادات الحواضن، التحاليل، الأشعة، والاستشارات والأدوية لكل طفل راقد.</p>
            </div>
            <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>العودة إلى الكاشير الرئيسية
            </a>
        </div>
    </div>

    {{-- الفلاتر --}}
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('cashier.nicu.statements') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="from_date" class="form-label">من تاريخ الدخول</label>
                            <input type="date" id="from_date" name="from_date" class="form-control" value="{{ old('from_date', $fromDate) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="to_date" class="form-label">إلى تاريخ الدخول</label>
                            <input type="date" id="to_date" name="to_date" class="form-control" value="{{ old('to_date', $toDate) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="insurance_type" class="form-label">نوع التغطية المالية</label>
                            <select id="insurance_type" name="insurance_type" class="form-select">
                                <option value="" {{ empty($insuranceFilter) ? 'selected' : '' }}>جميع الحالات (كاش وضمان)</option>
                                <option value="insured" {{ $insuranceFilter === 'insured' ? 'selected' : '' }}>🛡️ حالات الضمان الصحي فقط</option>
                                <option value="cash" {{ $insuranceFilter === 'cash' ? 'selected' : '' }}>💵 حالات الدفع النقدي (كاش) فقط</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-info text-white w-100">
                                <i class="fas fa-filter me-2"></i>تصفية
                            </button>
                            <a href="{{ route('cashier.nicu.statements') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo me-2"></i>مسح
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- بطاقات الملخص المالي للبند ومطالبات الضمان --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-white bg-primary h-100">
                <div class="card-body py-3">
                    <h6 class="mb-1 opacity-75">إجمالي خدمات الحواضن</h6>
                    <h3 class="fw-bold mb-0">{{ number_format($totalServicesFee, 0) }} د.ع</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-white bg-info h-100">
                <div class="card-body py-3">
                    <h6 class="mb-1 opacity-75">الفحوصات والأشعة والأدوية</h6>
                    <h3 class="fw-bold mb-0">{{ number_format($totalLabs + $totalRadiology + $totalMedications + $totalConsultations, 0) }} د.ع</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-white bg-success h-100">
                <div class="card-body py-3">
                    <h6 class="mb-1 opacity-75">🛡️ مطالبات الضمان للخدج (تطلبها المستشفى)</h6>
                    <h3 class="fw-bold mb-0">+ {{ number_format($totalInsuranceClaims, 0) }} د.ع</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-dark bg-warning bg-opacity-75 h-100">
                <div class="card-body py-3">
                    <h6 class="mb-1 text-muted">💵 الصافي المقبوض نقداً بالصندوق</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalCollected, 0) }} د.ع</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- كشف الجدول --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if($reservations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>المولود والأم</th>
                                        <th>الحاضنة والحالة</th>
                                        <th>الدخول والمدة</th>
                                        <th>تفصيل البنود</th>
                                        <th>إجمالي الفاتورة</th>
                                        <th class="table-success text-success">مطالبة الضمان (تطلبها المستشفى)</th>
                                        <th>حصة المريض</th>
                                        <th>المسدد نقداً</th>
                                        <th>موقف المريض</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservations as $res)
                                        @php
                                            $stay = $billingService->calculateStayDetails($res);
                                            $paid = $res->total_paid_amount;
                                            $grossTotal = $stay['grand_total'];
                                            $patientGrandTotal = $stay['patient_grand_total'];
                                            $insuranceShare = $stay['insurance_grand_total'];
                                            $due = max(0, $patientGrandTotal - $paid);
                                            $excess = max(0, $paid - $patientGrandTotal);
                                        @endphp
                                        <tr>
                                            <td>{{ $reservations->firstItem() + $loop->index }}</td>
                                            <td>
                                                <div class="fw-bold text-dark">{{ $res->baby_title }}</div>
                                                <small class="text-muted d-block">الأم: {{ optional($res->patient?->user)->name ?? 'غير معروف' }}</small>
                                                <div class="mt-1 d-flex flex-wrap gap-1">
                                                    @if($res->is_insured)
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                            <i class="fas fa-shield-alt me-1"></i>ضمان فئة {{ $res->policy_category ?? 'E' }}
                                                        </span>
                                                        @if($res->gestational_age)
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                                                <i class="fas fa-baby me-1"></i>{{ $res->gestational_age }}
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border">نقدي (كاش)</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    حاضنة {{ $res->incubator?->incubator_number ?? '—' }}
                                                </span>
                                                <br>
                                                <span class="badge bg-{{ $res->status_color }} mt-1">
                                                    {{ $res->status_name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="small fw-bold">{{ $res->admission_date->format('Y-m-d') }}</span>
                                                @if($res->discharge_date)
                                                    <br><small class="text-muted">خروج: {{ $res->discharge_date->format('Y-m-d') }}</small>
                                                @endif
                                                <br>
                                                <span class="badge bg-info bg-opacity-10 text-info fw-bold">{{ $stay['calculated_days'] }} يوم</span>
                                                <small class="text-muted">({{ $stay['total_hours'] }}س)</small>
                                            </td>
                                            <td>
                                                <ul class="list-unstyled mb-0 small">
                                                    <li><i class="fas fa-procedures text-primary me-1"></i>حاضنة: {{ number_format($stay['total_service_cost'], 0) }} د.ع</li>
                                                    @if($stay['orders_by_type']['lab'] > 0)
                                                        <li><i class="fas fa-flask text-success me-1"></i>تحاليل: {{ number_format($stay['orders_by_type']['lab'], 0) }} د.ع</li>
                                                    @endif
                                                    @if($stay['orders_by_type']['radiology'] > 0)
                                                        <li><i class="fas fa-x-ray text-info me-1"></i>أشعة: {{ number_format($stay['orders_by_type']['radiology'], 0) }} د.ع</li>
                                                    @endif
                                                    @if($stay['orders_by_type']['medication'] > 0 || $stay['total_consultations_cost'] > 0)
                                                        <li><i class="fas fa-pills text-warning me-1"></i>علاج واستشارات: {{ number_format($stay['orders_by_type']['medication'] + $stay['total_consultations_cost'], 0) }} د.ع</li>
                                                    @endif
                                                </ul>
                                            </td>
                                            <td class="fw-bold text-dark">
                                                {{ number_format($grossTotal, 0) }} د.ع
                                            </td>
                                            <td class="table-success text-center">
                                                @if($res->is_insured && $insuranceShare > 0)
                                                    <span class="badge bg-success fs-6 fw-bold px-2 py-1 shadow-2xs">
                                                        + {{ number_format($insuranceShare, 0) }} د.ع
                                                    </span>
                                                    <small class="text-success d-block fw-semibold mt-1">تطلبها المستشفى</small>
                                                @else
                                                    <span class="text-muted small">0 (كاش كامل)</span>
                                                @endif
                                            </td>
                                            <td class="fw-bold text-primary">
                                                {{ number_format($patientGrandTotal, 0) }} د.ع
                                            </td>
                                            <td class="fw-bold text-success">
                                                {{ number_format($paid, 0) }} د.ع
                                            </td>
                                            <td>
                                                @if($due > 0)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger">مطلوب: {{ number_format($due, 0) }} د.ع</span>
                                                @elseif($excess > 0)
                                                    <span class="badge bg-warning-subtle text-dark border border-warning">فائض: {{ number_format($excess, 0) }} د.ع</span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success border border-success">خالص تماماً</span>
                                                @endif
                                            </td>
                                                @if($due > 0)
                                                    <span class="text-danger fw-bold">مطلوب: {{ number_format($due, 0) }} د.ع</span>
                                                @elseif($excess > 0)
                                                    <span class="text-warning fw-bold">فائض أمانات: {{ number_format($excess, 0) }} د.ع</span>
                                                @else
                                                    <span class="badge bg-success">خالص تماماً</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('nicu.statement', $res) }}" class="btn btn-outline-info" title="كشف الحساب التراكمي">
                                                        <i class="fas fa-file-invoice me-1"></i>كشف الحساب
                                                    </a>
                                                    <a href="{{ route('nicu.show', $res) }}" class="btn btn-outline-secondary" title="ملف الطفل">
                                                        <i class="fas fa-baby"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $reservations->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-file-invoice-dollar fa-3x text-muted mb-3 opacity-50"></i>
                            <p class="text-muted">لا توجد سجلات كشوفات لحالات الخدج ضمن الفترة المحددة.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
