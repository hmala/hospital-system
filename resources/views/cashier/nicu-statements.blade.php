@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-file-invoice-dollar me-2 text-info"></i>{{ __('كشوفات حسابات الخدج والحاضنات') }}</h2>
                <p class="text-muted mb-0">{{ __('تقرير تفصيلي بإيرادات الحواضن، التحاليل، الأشعة، والاستشارات والأدوية لكل طفل راقد.') }}</p>
            </div>
            <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>{{ __('العودة إلى الكاشير الرئيسية') }}
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
                            <label for="from_date" class="form-label">{{ __('من تاريخ الدخول') }}</label>
                            <input type="date" id="from_date" name="from_date" class="form-control" value="{{ old('from_date', $fromDate) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="to_date" class="form-label">{{ __('إلى تاريخ الدخول') }}</label>
                            <input type="date" id="to_date" name="to_date" class="form-control" value="{{ old('to_date', $toDate) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="insurance_type" class="form-label">{{ __('نوع التغطية المالية') }}</label>
                            <select id="insurance_type" name="insurance_type" class="form-select">
                                <option value="" {{ empty($insuranceFilter) ? 'selected' : '' }}>{{ __('جميع الحالات (كاش وضمان)') }}</option>
                                <option value="insured" {{ $insuranceFilter === 'insured' ? 'selected' : '' }}>{{ __('🛡️ حالات الضمان الصحي فقط') }}</option>
                                <option value="cash" {{ $insuranceFilter === 'cash' ? 'selected' : '' }}>{{ __('💵 حالات الدفع النقدي (كاش) فقط') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-info text-white w-100">
                                <i class="fas fa-filter me-2"></i>{{ __('تصفية') }}
                            </button>
                            <a href="{{ route('cashier.nicu.statements') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo me-2"></i>{{ __('مسح') }}
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
                    <h6 class="mb-1 opacity-75">{{ __('إجمالي خدمات الحواضن') }}</h6>
                    <h3 class="fw-bold mb-0">{{ number_format($totalServicesFee, 0) }} {{ __('د.ع') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-white bg-info h-100">
                <div class="card-body py-3">
                    <h6 class="mb-1 opacity-75">{{ __('الفحوصات والأشعة والأدوية') }}</h6>
                    <h3 class="fw-bold mb-0">{{ number_format($totalLabs + $totalRadiology + $totalMedications + $totalConsultations, 0) }} {{ __('د.ع') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-white bg-success h-100">
                <div class="card-body py-3">
                    <h6 class="mb-1 opacity-75">{{ __('🛡️ مطالبات الضمان للخدج (تطلبها المستشفى)') }}</h6>
                    <h3 class="fw-bold mb-0">+ {{ number_format($totalInsuranceClaims, 0) }} {{ __('د.ع') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-dark bg-warning bg-opacity-75 h-100">
                <div class="card-body py-3">
                    <h6 class="mb-1 text-muted">{{ __('💵 الصافي المقبوض نقداً بالصندوق') }}</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalCollected, 0) }} {{ __('د.ع') }}</h3>
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
                                        <th>{{ __('المولود والأم') }}</th>
                                        <th>{{ __('الحاضنة والحالة') }}</th>
                                        <th>{{ __('الدخول والمدة') }}</th>
                                        <th>{{ __('تفصيل البنود') }}</th>
                                        <th>{{ __('إجمالي الفاتورة') }}</th>
                                        <th class="table-success text-success">{{ __('مطالبة الضمان (تطلبها المستشفى)') }}</th>
                                        <th>{{ __('حصة المريض') }}</th>
                                        <th>{{ __('المسدد نقداً') }}</th>
                                        <th>{{ __('موقف المريض') }}</th>
                                        <th>{{ __('الإجراءات') }}</th>
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
                                                <small class="text-muted d-block">{{ __('الأم:') }} {{ optional($res->patient?->user)->name ?? __('غير معروف') }}</small>
                                                <div class="mt-1 d-flex flex-wrap gap-1">
                                                    @if($res->is_insured)
                                                        <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                                            <i class="fas fa-shield-alt me-1"></i>{{ __('ضمان فئة') }} {{ $res->policy_category ?? 'E' }}
                                                        </span>
                                                        @if($res->gestational_age)
                                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                                                <i class="fas fa-baby me-1"></i>{{ $res->gestational_age }}
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ __('نقدي (كاش)') }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    {{ __('حاضنة') }} {{ $res->incubator?->incubator_number ?? '—' }}
                                                </span>
                                                <br>
                                                <span class="badge bg-{{ $res->status_color }} mt-1">
                                                    {{ $res->status_name }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="small fw-bold">{{ $res->admission_date->format('Y-m-d') }}</span>
                                                @if($res->discharge_date)
                                                    <br><small class="text-muted">{{ __('خروج:') }} {{ $res->discharge_date->format('Y-m-d') }}</small>
                                                @endif
                                                <br>
                                                <span class="badge bg-info bg-opacity-10 text-info fw-bold">{{ $stay['calculated_days'] }} {{ __('يوم') }}</span>
                                                <small class="text-muted">({{ $stay['total_hours'] }}{{ __('س') }})</small>
                                            </td>
                                            <td>
                                                <ul class="list-unstyled mb-0 small">
                                                    <li>
                                                        <i class="fas fa-procedures text-primary me-1"></i>{{ __('حاضنة:') }} {{ number_format($stay['total_service_cost'], 0) }} {{ __('د.ع') }}
                                                        @if($res->is_insured && ($stay['insurance_share_total'] ?? 0) > 0)
                                                            <small class="text-success d-block ps-3">({{ __('ضمان:') }} {{ number_format($stay['insurance_share_total'], 0) }})</small>
                                                        @endif
                                                    </li>
                                                    @if(($stay['total_lab_cost'] ?? 0) > 0)
                                                        <li>
                                                            <i class="fas fa-flask text-success me-1"></i>{{ __('تحاليل:') }} {{ number_format($stay['total_lab_cost'], 0) }} {{ __('د.ع') }}
                                                            @if($res->is_insured && ($stay['insurance_lab_share'] ?? 0) > 0)
                                                                <small class="text-success d-block ps-3">({{ __('ضمان:') }} {{ number_format($stay['insurance_lab_share'], 0) }})</small>
                                                            @endif
                                                        </li>
                                                    @endif
                                                    @if(($stay['total_radiology_cost'] ?? 0) > 0)
                                                        <li>
                                                            <i class="fas fa-x-ray text-info me-1"></i>{{ __('أشعة:') }} {{ number_format($stay['total_radiology_cost'], 0) }} {{ __('د.ع') }}
                                                            @if($res->is_insured && ($stay['insurance_radiology_share'] ?? 0) > 0)
                                                                <small class="text-success d-block ps-3">({{ __('ضمان:') }} {{ number_format($stay['insurance_radiology_share'], 0) }})</small>
                                                            @endif
                                                        </li>
                                                    @endif
                                                    @if(($stay['orders_by_type']['medication'] ?? 0) > 0 || ($stay['total_consultations_cost'] ?? 0) > 0)
                                                        <li>
                                                            <i class="fas fa-pills text-warning me-1"></i>{{ __('علاج واستشارات:') }} {{ number_format(($stay['orders_by_type']['medication'] ?? 0) + ($stay['total_consultations_cost'] ?? 0), 0) }} {{ __('د.ع') }}
                                                            @if($res->is_insured && ($stay['insurance_consultations_share'] ?? 0) > 0)
                                                                <small class="text-success d-block ps-3">({{ __('ضمان:') }} {{ number_format($stay['insurance_consultations_share'], 0) }})</small>
                                                            @endif
                                                        </li>
                                                    @endif
                                                </ul>
                                            </td>
                                            <td class="fw-bold text-dark">
                                                {{ number_format($grossTotal, 0) }} {{ __('د.ع') }}
                                            </td>
                                            <td class="table-success text-center">
                                                @if($res->is_insured && $insuranceShare > 0)
                                                    <span class="badge bg-success fs-6 fw-bold px-2 py-1 shadow-2xs">
                                                        + {{ number_format($insuranceShare, 0) }} {{ __('د.ع') }}
                                                    </span>
                                                    <small class="text-success d-block fw-semibold mt-1">{{ __('تطلبها المستشفى') }}</small>
                                                @else
                                                    <span class="text-muted small">{{ __('0 (كاش كامل)') }}</span>
                                                @endif
                                            </td>
                                            <td class="fw-bold text-primary">
                                                {{ number_format($patientGrandTotal, 0) }} {{ __('د.ع') }}
                                            </td>
                                            <td class="fw-bold text-success">
                                                {{ number_format($paid, 0) }} {{ __('د.ع') }}
                                            </td>
                                            <td>
                                                @if($due > 0)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger">{{ __('مطلوب:') }} {{ number_format($due, 0) }} {{ __('د.ع') }}</span>
                                                @elseif($excess > 0)
                                                    <span class="badge bg-warning-subtle text-dark border border-warning">{{ __('فائض:') }} {{ number_format($excess, 0) }} {{ __('د.ع') }}</span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success border border-success">{{ __('خالص تماماً') }}</span>
                                                @endif
                                            </td>
                                                @if($due > 0)
                                                    <span class="text-danger fw-bold">{{ __('مطلوب:') }} {{ number_format($due, 0) }} {{ __('د.ع') }}</span>
                                                @elseif($excess > 0)
                                                    <span class="text-warning fw-bold">{{ __('فائض أمانات:') }} {{ number_format($excess, 0) }} {{ __('د.ع') }}</span>
                                                @else
                                                    <span class="badge bg-success">{{ __('خالص تماماً') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('nicu.statement', $res) }}" class="btn btn-outline-info" title="{{ __('كشف الحساب التراكمي') }}">
                                                        <i class="fas fa-file-invoice me-1"></i>{{ __('كشف الحساب') }}
                                                    </a>
                                                    <a href="{{ route('nicu.show', $res) }}" class="btn btn-outline-secondary" title="{{ __('ملف الطفل') }}">
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
                            <p class="text-muted">{{ __('لا توجد سجلات كشوفات لحالات الخدج ضمن الفترة المحددة.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
