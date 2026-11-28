@extends('layouts.app')

@section('content')
@php
    $flatSurgeries = (isset($cashierSurgeries) && is_iterable($cashierSurgeries)) 
        ? $cashierSurgeries 
        : ((isset($surgeriesByPatient) && is_iterable($surgeriesByPatient)) ? $surgeriesByPatient->flatten() : (is_iterable($pendingSurgeries) ? $pendingSurgeries : collect()));
@endphp
<div class="container-fluid py-3 px-4">
    <!-- رأس الصفحة التنفيذي (Executive Header) -->
    <div class="card border-0 shadow-xs rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border: 1px solid #e2e8f0 !important;">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-4 d-flex align-items-center justify-content-center shadow-xs" 
                         style="width: 54px; height: 54px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #fff;">
                        <i class="fas fa-procedures fa-lg"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h3 class="fw-bold mb-0 text-slate-800" style="font-size: 1.45rem;">
                                {{ __('كاشير ومحاسبة العمليات الجراحية') }}
                            </h3>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1 small">
                                <i class="fas fa-cash-register me-1"></i>{{ __('جدول المحاسبة والقبض الفوري') }}
                            </span>
                        </div>
                        <p class="text-muted small mb-0 d-flex align-items-center gap-2">
                            <span><i class="fas fa-shield-alt text-primary me-1"></i>{{ __('إدارة مدفوعات العمليات الجراحية، الإقامة، الفحوصات، وتصفية التأمين والضمان بنقرة واحدة') }}</span>
                        </p>
                    </div>
                </div>

                <!-- أزرار التنقل السريع -->
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <a href="{{ route('cashier.surgeries.doctor-accounts') }}" 
                       class="btn btn-outline-danger btn-sm rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs">
                        <i class="fas fa-wallet text-danger"></i>
                        <span>{{ __('حسابات وأتعاب الجراحين') }}</span>
                    </a>
                    <a href="{{ route('cashier.surgeries.paid') }}" 
                       class="btn btn-outline-success btn-sm rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs">
                        <i class="fas fa-file-invoice-dollar text-success"></i>
                        <span>{{ __('أرشيف المقبوضات والإيصالات') }}</span>
                    </a>
                    <a href="{{ route('cashier.index') }}" 
                       class="btn btn-secondary btn-sm rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-1.5 shadow-2xs">
                        <i class="fas fa-arrow-right"></i>
                        <span>{{ __('الكاشير الرئيسي') }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- التنبيهات والإشعارات -->
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-xs rounded-4 p-3 mb-4 d-flex align-items-start gap-3 fade show" role="alert" style="background-color: #ecfdf5; border-right: 5px solid #10b981 !important;">
            <div class="text-success fs-5 mt-0.5"><i class="fas fa-check-circle"></i></div>
            <div class="flex-grow-1">
                <div class="fw-bold text-success mb-1">{{ __('تمت العملية بنجاح') }}</div>
                <div class="text-dark small">{{ session('success') }}</div>
                @if(session('payment_id'))
                    <div class="mt-2.5 d-flex gap-2">
                        <a href="{{ route('cashier.receipt', session('payment_id')) }}" 
                           class="btn btn-xs btn-success rounded-pill px-3 py-1 shadow-2xs" 
                           target="_blank">
                            <i class="fas fa-eye me-1"></i>{{ __('عرض الإيصال') }}
                        </a>
                        <a href="{{ route('cashier.receipt.print', session('payment_id')) }}" 
                           class="btn btn-xs btn-outline-success bg-white rounded-pill px-3 py-1 shadow-2xs" 
                           target="_blank">
                            <i class="fas fa-print me-1"></i>{{ __('طباعة الإيصال فوراً') }}
                        </a>
                    </div>
                @endif
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning border-0 shadow-xs rounded-4 p-3 mb-4 d-flex align-items-start gap-3 fade show" role="alert" style="background-color: #fffbeb; border-right: 5px solid #f59e0b !important;">
            <div class="text-warning fs-5 mt-0.5"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="flex-grow-1">
                <div class="fw-bold text-dark mb-1">{{ __('ملاحظة هامة') }}</div>
                <div class="text-dark small">{{ session('warning') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-xs rounded-4 p-3 mb-4 d-flex align-items-start gap-3 fade show" role="alert" style="background-color: #fef2f2; border-right: 5px solid #ef4444 !important;">
            <div class="text-danger fs-5 mt-0.5"><i class="fas fa-times-circle"></i></div>
            <div class="flex-grow-1">
                <div class="fw-bold text-danger mb-1">{{ __('حدث خطأ أثناء المعالجة') }}</div>
                <div class="text-dark small">{{ session('error') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- بطاقات مؤشرات الأداء السريعة (KPI Dashboard Cards) -->
    <div class="row g-3 mb-4">
        <!-- 1. العمليات المعلقة -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-xs rounded-4 h-100 position-relative overflow-hidden" 
                 style="background: #ffffff; border: 1px solid #f1f5f9 !important;">
                <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #ef4444, #f87171);"></div>
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block mb-1">{{ __('العمليات بانتظار المحاسبة') }}</span>
                        <h3 class="fw-extrabold mb-0 text-danger">{{ $surgeryStats['pending_count'] ?? 0 }}</h3>
                        <small class="text-danger fw-semibold d-inline-flex align-items-center gap-1 mt-1">
                            <i class="fas fa-procedures"></i> {{ __('عملية تتطلب قبضاً أو تسوية') }}
                        </small>
                    </div>
                    <div class="rounded-3 p-3 d-flex align-items-center justify-content-center" 
                         style="background: #fef2f2; color: #ef4444; width: 50px; height: 50px;">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. نزلاء الردهة المسددين -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-xs rounded-4 h-100 position-relative overflow-hidden" 
                 style="background: #ffffff; border: 1px solid #f1f5f9 !important;">
                <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #10b981, #34d399);"></div>
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block mb-1">{{ __('نزلاء الردهة (المسددون)') }}</span>
                        <h3 class="fw-extrabold mb-0 text-success">{{ $surgeryStats['inpatient_count'] ?? 0 }}</h3>
                        <small class="text-muted fw-semibold d-inline-flex align-items-center gap-1 mt-1">
                            @if(($surgeryStats['medically_cleared_count'] ?? 0) > 0)
                                <span class="badge bg-warning-subtle text-dark border border-warning px-1.5 py-0.5">
                                    <i class="fas fa-stethoscope me-1 text-warning"></i>{{ $surgeryStats['medically_cleared_count'] }} {{ __('جاهز للتخريج') }}
                                </span>
                            @else
                                <i class="fas fa-bed text-success"></i> {{ __('قيد الإقامة للمتابعة') }}
                            @endif
                        </small>
                    </div>
                    <div class="rounded-3 p-3 d-flex align-items-center justify-content-center" 
                         style="background: #ecfdf5; color: #10b981; width: 50px; height: 50px;">
                        <i class="fas fa-bed fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. عمليات مسددة اليوم -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-xs rounded-4 h-100 position-relative overflow-hidden" 
                 style="background: #ffffff; border: 1px solid #f1f5f9 !important;">
                <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #10b981, #34d399);"></div>
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block mb-1">{{ __('حركات مسددة اليوم') }}</span>
                        <h3 class="fw-extrabold mb-0 text-slate-800">{{ $surgeryStats['today_paid'] ?? 0 }}</h3>
                        <small class="text-success fw-semibold d-inline-flex align-items-center gap-1 mt-1">
                            <i class="fas fa-check-double"></i> {{ __('إيصالات مقبوضة اليوم') }}
                        </small>
                    </div>
                    <div class="rounded-3 p-3 d-flex align-items-center justify-content-center" 
                         style="background: #ecfdf5; color: #10b981; width: 50px; height: 50px;">
                        <i class="fas fa-file-check fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. إيرادات اليوم -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-xs rounded-4 h-100 position-relative overflow-hidden" 
                 style="background: #ffffff; border: 1px solid #f1f5f9 !important;">
                <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background: linear-gradient(90deg, #3b82f6, #60a5fa);"></div>
                <div class="card-body p-3.5 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase d-block mb-1">{{ __('إيرادات العمليات اليومية') }}</span>
                        <h3 class="fw-extrabold mb-0 text-primary">
                            {{ number_format($surgeryStats['today_revenue'] ?? 0, 0) }} 
                            <span class="fs-6 fw-normal text-muted">IQD</span>
                        </h3>
                        <small class="text-primary fw-semibold d-inline-flex align-items-center gap-1 mt-1">
                            <i class="fas fa-money-bill-wave"></i> {{ __('إجمالي النقد والبطاقات') }}
                        </small>
                    </div>
                    <div class="rounded-3 p-3 d-flex align-items-center justify-content-center" 
                         style="background: #eff6ff; color: #3b82f6; width: 50px; height: 50px;">
                        <i class="fas fa-coins fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بطاقة البحث والتصفية اللحظية (Smart Live Filter Bar) -->
    <div class="card border-0 shadow-xs rounded-4 mb-4" style="background: #ffffff; border: 1px solid #e2e8f0 !important;">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- حقل البحث الفوري -->
                <div class="col-lg-5 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted ps-3">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" 
                               id="surgeryLiveSearch" 
                               class="form-control bg-light border-start-0 py-2 ps-2" 
                               placeholder="{{ __('بحث فوري باسم المريض، رقم العملية (#)، الهوية، الهاتف، الجراح...') }}" 
                               autocomplete="off">
                        <button class="btn btn-outline-secondary border-start-0 bg-light" type="button" id="clearSearchBtn" title="{{ __('مسح البحث') }}" style="display: none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- أزرار الفلترة السريعة بالحالة -->
                <div class="col-lg-7 col-md-6">
                    <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-1.5" id="statusFilterPills">
                        <span class="text-muted small me-1 d-none d-lg-inline">{{ __('تصفية:') }}</span>
                        <button type="button" class="btn btn-sm btn-dark rounded-pill px-3 py-1.5 filter-btn active" data-filter="all">
                            <i class="fas fa-list me-1"></i>{{ __('الكل') }}
                            <span class="badge bg-white text-dark ms-1 rounded-pill" id="totalSurgeriesBadge">{{ $flatSurgeries ? $flatSurgeries->count() : 0 }}</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1.5 filter-btn text-dark" data-filter="pending">
                            <i class="fas fa-clock me-1 text-warning"></i>{{ __('بانتظار القبض') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1.5 filter-btn" data-filter="refund">
                            <i class="fas fa-undo me-1"></i>{{ __('مستحق استرجاع') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3 py-1.5 filter-btn" data-filter="review">
                            <i class="fas fa-calculator me-1"></i>{{ __('بانتظار التسعير') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول العمليات الموحد المباشر (Master Cashier Surgery Table) -->
    <div class="card border-0 shadow-xs rounded-4 overflow-hidden mb-4" style="background: #ffffff; border: 1px solid #e2e8f0 !important;">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
            @php
                $activeTabKey = request('tab', 'pending');
            @endphp
            <ul class="nav nav-pills p-1.5 bg-light rounded-pill border" id="cashierSurgeryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTabKey === 'pending' ? 'active' : '' }} rounded-pill fw-bold px-3.5 py-1.5 d-flex align-items-center gap-2" id="tab-pending-btn" data-bs-toggle="pill" data-bs-target="#tab-pending-pane" type="button" role="tab">
                        <i class="fas fa-clock text-danger"></i>
                        <span>{{ __('قيد المحاسبة (المعلقة)') }}</span>
                        <span class="badge bg-danger rounded-pill px-2 py-0.5" id="visibleSurgeriesCount">{{ $flatSurgeries->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTabKey === 'inpatient' ? 'active' : '' }} rounded-pill fw-bold px-3.5 py-1.5 d-flex align-items-center gap-2" id="tab-inpatient-btn" data-bs-toggle="pill" data-bs-target="#tab-inpatient-pane" type="button" role="tab">
                        <i class="fas fa-bed text-success"></i>
                        <span>{{ __('نزلاء الردهة المسددون (قيد الإقامة)') }}</span>
                        <span class="badge bg-success rounded-pill px-2 py-0.5">{{ isset($inpatientSurgeries) ? $inpatientSurgeries->count() : 0 }}</span>
                        @if(($surgeryStats['medically_cleared_count'] ?? 0) > 0)
                            <span class="badge bg-warning text-dark rounded-pill px-2 py-0.5 fw-bold" title="{{ __('مرضى منحهم المقيم إذن خروج طبي وتفريغ سرير، بانتظار براءة ذمة الكاشير') }}">
                                <i class="fas fa-stethoscope me-1"></i>{{ $surgeryStats['medically_cleared_count'] }} {{ __('جاهز للتخريج') }}
                            </span>
                        @endif
                    </button>
                </li>
            </ul>

            <div class="text-muted small">
                <i class="fas fa-shield-alt text-primary me-1"></i>{{ __('براءة الذمة والتخريج النهائي حصراً عبر الكاشير') }}
            </div>
        </div>

        <div class="card-body p-0">
            <div class="tab-content" id="cashierSurgeryTabContent">
                {{-- التبويب الأول: قيد المحاسبة (المعلقة) --}}
                <div class="tab-pane fade {{ $activeTabKey === 'pending' ? 'show active' : '' }}" id="tab-pending-pane" role="tabpanel">
            @if($flatSurgeries && $flatSurgeries->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-right" id="surgeriesMasterTable" style="direction: rtl;">
                        <thead class="table-light text-slate-700 border-bottom" style="background: #f8fafc; font-size: 0.85rem;">
                            <tr class="text-uppercase">
                                <th style="width: 75px;" class="text-center">{{ __('رقم العملية') }}</th>
                                <th style="min-width: 220px;">{{ __('المريض والتغطية') }}</th>
                                <th style="min-width: 200px;">{{ __('العملية والقسم والغرفة') }}</th>
                                <th style="min-width: 160px;">{{ __('الطبيب الجراح') }}</th>
                                <th class="text-end" style="width: 130px;">{{ __('التكلفة الكلية') }}</th>
                                <th class="text-end" style="width: 130px;">{{ __('المسدد') }}</th>
                                <th class="text-end" style="width: 160px;">{{ __('المتبقي / الاسترجاع') }}</th>
                                <th class="text-center" style="width: 190px;">{{ __('إجراء الكاشير') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($flatSurgeries as $surgery)
                                @php
                                    $patient = $surgery->patient;
                                    $isCancelled = $surgery->status === 'cancelled';
                                    $additionalOpsFee = $isCancelled ? 0 : $surgery->additionalOperations->sum('fee');
                                    $surgeryFee = $isCancelled ? 0 : (($surgery->surgery_fee ?? 0) + $additionalOpsFee);
                                    $surgeryFeePaidAmount = $surgery->surgery_fee_paid_amount ?? 0;
                                    $remainingSurgeryFee = max(0, $surgeryFee - $surgeryFeePaidAmount);

                                    // التأمين ونسبة التحمل للمريض
                                    $sIns = $surgery->insurance_type ?: ($patient?->insurance_type ?: 'none');
                                    $isIns = in_array($sIns, ['hi', 'moi']);

                                    // رسوم الغرفة وحساب الإقامة الفندقية التراكمية
                                    $stayDetails = $surgery->calculateStayDetails();
                                    $isStayIns = $isIns && ($stayDetails['is_insurance_calculated'] ?? false);
                                    $targetRoomFee = $isStayIns ? ($stayDetails['patient_stay_share'] ?? 0) : ($stayDetails['total_fee'] ?? ($surgery->room_fee ?? 0));
                                    $roomFee = $isCancelled ? 0 : $targetRoomFee;
                                    $roomFeePaidAmount = $surgery->room_fee_paid_amount ?? 0;
                                    $remainingRoomFee = max(0, $roomFee - $roomFeePaidAmount);

                                    // خدمات إضافية
                                    $pendingExtraFee = $isCancelled ? 0 : $surgery->extraServices->where('payment_status', '!=', 'paid')->sum('total_amount');
                                    $paidExtraFee = $surgery->extraServices->where('payment_status', 'paid')->sum(function($extra) {
                                        if ($extra->is_package_included) return 0;
                                        return (float)($extra->total_amount ?? 0);
                                    });

                                    $labCopayPct = match($sIns) {
                                        'hi' => ($patient?->getCopayPercentageFor('lab') ?? 15),
                                        'moi' => (float)($patient?->copay_percentage ?? 15),
                                        default => 100
                                    };
                                    $radCopayPct = match($sIns) {
                                        'hi' => ($patient?->getCopayPercentageFor('radiology') ?? 15),
                                        'moi' => (float)($patient?->copay_percentage ?? 15),
                                        default => 100
                                    };

                                    // تحاليل
                                    $pendingLabFee = $isCancelled ? 0 : $surgery->labTests->where('payment_status', '!=', 'paid')->sum(function($test) use ($sIns, $isIns, $labCopayPct) {
                                        $m = $test->labTest;
                                        if (!$m) return 0;
                                        $base = (float)($m->price ?? 0);
                                        if (!$isIns) return $base;
                                        $hi = (float)(($m->hi_price > 0) ? $m->hi_price : $base);
                                        $moi = (float)(($m->moi_price > 0) ? $m->moi_price : $base);
                                        $approved = ($sIns === 'hi') ? $hi : $moi;
                                        return round($approved * ($labCopayPct / 100));
                                    });
                                    $paidLabFee = $surgery->labTests->where('payment_status', 'paid')->sum(function($test) {
                                        if ($test->is_package_included) return 0;
                                        return (float)($test->labTest?->price ?? 0);
                                    });

                                    // أشعة
                                    $pendingRadiologyFee = $isCancelled ? 0 : $surgery->radiologyTests->where('payment_status', '!=', 'paid')->sum(function($test) use ($sIns, $isIns, $radCopayPct) {
                                        $m = $test->radiologyType;
                                        if (!$m) return 0;
                                        $base = (float)($m->base_price ?? 0);
                                        if (!$isIns) return $base;
                                        $hi = (float)(($m->hi_price > 0) ? $m->hi_price : $base);
                                        $moi = (float)(($m->moi_price > 0) ? $m->moi_price : $base);
                                        $approved = ($sIns === 'hi') ? $hi : $moi;
                                        return round($approved * ($radCopayPct / 100));
                                    });
                                    $paidRadiologyFee = $surgery->radiologyTests->where('payment_status', 'paid')->sum(function($test) {
                                        if ($test->is_package_included) return 0;
                                        return (float)($test->radiologyType?->base_price ?? 0);
                                    });

                                    // استشارات
                                    $pendingConsultationsFee = $isCancelled ? 0 : $surgery->consultations->where('payment_status', '!=', 'paid')->where('status', '!=', 'cancelled')->sum(function($c) {
                                        return (float)($c->fee > 0 ? $c->fee : 50000);
                                    });
                                    $paidConsultationsFee = $surgery->consultations->where('payment_status', 'paid')->sum(function($c) {
                                        if ($c->is_package_included) return 0;
                                        return (float)($c->fee ?? 0);
                                    });

                                    $netPatientPaidCash = (float) $surgery->payments()->sum('amount');
                                    $paidAmount = $isCancelled 
                                        ? ($netPatientPaidCash > 0 ? $netPatientPaidCash : ($surgeryFeePaidAmount + $roomFeePaidAmount))
                                        : ($surgeryFeePaidAmount + $roomFeePaidAmount + $paidLabFee + $paidRadiologyFee + $paidExtraFee + $paidConsultationsFee);
                                    $pendingAmount = $isCancelled ? 0 : ($remainingSurgeryFee + $remainingRoomFee + $pendingLabFee + $pendingRadiologyFee + $pendingExtraFee + $pendingConsultationsFee);
                                    $totalAmount = $surgeryFee + $roomFee + $pendingLabFee + $paidLabFee + $pendingRadiologyFee + $paidRadiologyFee + $pendingExtraFee + $paidExtraFee + $pendingConsultationsFee + $paidConsultationsFee;
                                    
                                    if ($isCancelled) {
                                        $excessAmount = $paidAmount;
                                    } else {
                                        $excessSurgeryFee = $surgeryFeePaidAmount > $surgeryFee ? ($surgeryFeePaidAmount - $surgeryFee) : 0;
                                        $excessRoomFee = $roomFeePaidAmount > $roomFee ? ($roomFeePaidAmount - $roomFee) : 0;
                                        $excessAmount = $excessSurgeryFee + $excessRoomFee;
                                    }

                                    // تصنيف الفلترة
                                    $rowFilterType = 'pending';
                                    if ($excessAmount > 0 || ($isCancelled && $paidAmount > 0)) {
                                        $rowFilterType = 'refund';
                                    } elseif ($pendingAmount == 0 && $surgery->billing_status === 'pending_review') {
                                        $rowFilterType = 'review';
                                    } elseif ($pendingAmount == 0) {
                                        $rowFilterType = 'paid';
                                    }

                                    // نصوص البحث الفوري
                                    $searchText = strtolower($surgery->id . ' ' . ($patient?->user?->name ?? '') . ' ' . ($patient?->national_id ?? '') . ' ' . ($patient?->user?->phone ?? '') . ' ' . $surgery->surgery_type . ' ' . ($surgery->doctor?->user?->name ?? '') . ' ' . ($surgery->department?->name ?? '') . ' ' . ($surgery->room?->room_number ?? ''));
                                @endphp

                                <tr class="surgery-table-row {{ $isCancelled ? 'table-danger bg-opacity-25' : '' }}" 
                                    data-filter-type="{{ $rowFilterType }}" 
                                    data-search-text="{{ $searchText }}">
                                    
                                    <!-- 1. رقم العملية -->
                                    <td class="text-center">
                                        <span class="badge bg-secondary-subtle text-secondary border font-monospace px-2.5 py-1.5 fw-bold fs-7">
                                            #{{ $surgery->id }}
                                        </span>
                                    </td>

                                    <!-- 2. المريض والتغطية -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2.5">
                                            <div class="rounded-circle p-2 d-flex align-items-center justify-content-center shadow-2xs {{ $excessAmount > 0 ? 'bg-danger-subtle text-danger' : 'bg-primary-subtle text-primary' }}" 
                                                 style="width: 36px; height: 36px; flex-shrink: 0;">
                                                <i class="fas {{ $excessAmount > 0 ? 'fa-undo' : 'fa-user' }} small"></i>
                                            </div>
                                            <div>
                                                <div class="d-flex align-items-center gap-1.5 mb-0.5">
                                                    <span class="fw-bold text-slate-800 fs-6">
                                                        {{ $patient && $patient->user ? $patient->user->name : __('غير محدد') }}
                                                    </span>

                                                    @php $pIns = $surgery->insurance_type ?: ($patient?->insurance_type ?: 'none'); @endphp
                                                    @if($pIns === 'moi')
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">
                                                            <i class="fas fa-shield-alt me-1"></i>{{ __('داخليّة') }} ({{ $surgery->copay_percentage !== null ? $surgery->copay_percentage : ($patient->copay_percentage ?? 15) }}%)
                                                        </span>
                                                    @elseif($pIns === 'hi')
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">
                                                            <i class="fas fa-shield-alt me-1"></i>{{ __('ضمان صحي') }}
                                                        </span>
                                                    @else
                                                        <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">
                                                            <i class="fas fa-coins me-1"></i>{{ __('كاش') }}
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="d-flex align-items-center flex-wrap gap-2 text-muted small" style="font-size: 0.78rem;">
                                                    @if($patient && $patient->national_id)
                                                        <span><i class="fas fa-id-card me-1 text-secondary"></i>{{ $patient->national_id }}</span>
                                                    @endif
                                                    @if($patient && $patient->user && $patient->user->phone)
                                                        <span><i class="fas fa-phone me-1 text-secondary"></i><span dir="ltr">{{ $patient->user->phone }}</span></span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- 3. العملية والقسم والغرفة -->
                                    <td>
                                        <div class="fw-bold text-slate-800 d-flex align-items-center gap-1.5 mb-0.5">
                                            <i class="fas fa-procedures text-danger small"></i>
                                            <span>{{ $surgery->surgery_type }}</span>
                                        </div>

                                        <div class="d-flex align-items-center flex-wrap gap-1 text-muted small" style="font-size: 0.78rem;">
                                            <span><i class="fas fa-hospital me-1"></i>{{ $surgery->department->name ?? __('عام') }}</span>

                                            @if($surgery->room)
                                                <span class="badge bg-light text-secondary border ms-1">
                                                    <i class="fas fa-door-open me-1 text-primary"></i>{{ __('غرفة') }} {{ $surgery->room->room_number }}
                                                </span>
                                            @endif

                                            @if($isCancelled)
                                                <span class="badge bg-danger rounded-pill px-2 py-0.5 ms-1">
                                                    <i class="fas fa-times-circle me-1"></i>{{ __('ملغاة') }}
                                                </span>
                                            @elseif($surgery->billing_status === 'pending_review')
                                                <span class="badge bg-warning-subtle text-dark border border-warning rounded-pill px-2 py-0.5 ms-1">
                                                    <i class="fas fa-calculator me-1"></i>{{ __('بانتظار التسعير') }}
                                                </span>
                                            @elseif($surgery->payment_status === 'partial')
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-0.5 ms-1">
                                                    <i class="fas fa-adjust me-1"></i>{{ __('دفع جزئي') }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- 4. الطبيب الجراح -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; flex-shrink: 0;">
                                                <i class="fas fa-user-md small"></i>
                                            </div>
                                            <span class="fw-semibold text-slate-800 text-truncate" style="max-width: 150px;">
                                                {{ $surgery->doctor->user->name ?? $surgery->surgeon_name ?? __('غير محدد') }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- 5. التكلفة الإجمالية -->
                                    <td class="text-end fw-semibold text-slate-700">
                                        {{ number_format($totalAmount, 0) }} 
                                        <small class="text-muted">IQD</small>
                                    </td>

                                    <!-- 6. المسدد -->
                                    <td class="text-end fw-semibold text-success">
                                        {{ number_format($paidAmount, 0) }} 
                                        <small class="text-muted">IQD</small>
                                    </td>

                                    <!-- 7. المتبقي / الفارق الاسترجاعي -->
                                    <td class="text-end">
                                        @if($pendingAmount > 0)
                                            <div class="d-inline-flex flex-column align-items-end">
                                                <strong class="text-danger fs-6">{{ number_format($pendingAmount, 0) }}</strong>
                                                <small class="text-danger fw-semibold" style="font-size: 0.72rem;">{{ __('مطلوب دفعه') }}</small>
                                            </div>
                                        @elseif($excessAmount > 0)
                                            <div class="d-inline-flex flex-column align-items-end">
                                                <strong class="text-warning fs-6 text-dark">+{{ number_format($excessAmount, 0) }}</strong>
                                                <small class="text-danger fw-bold" style="font-size: 0.72rem;">{{ __('مستحق استرجاع') }}</small>
                                            </div>
                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small">
                                                <i class="fas fa-check-circle me-1"></i>0 IQD
                                            </span>
                                        @endif
                                    </td>

                                    <!-- 8. الإجراء المالي المباشر -->
                                    <td class="text-center">
                                        @php
                                            $canPaySurgeries = auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || auth()->user()->can('process surgery payments');
                                            $canRefund = auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || auth()->user()->can('process refunds') || auth()->user()->can('process surgery payments');
                                        @endphp

                                        @if($isCancelled && $excessAmount > 0)
                                            @if($canRefund)
                                                <a href="{{ route('cashier.surgeries.payment.form', $surgery->id) }}" 
                                                   class="btn btn-danger btn-sm px-3 py-1.5 fw-bold rounded-pill shadow-xs d-inline-flex align-items-center gap-1.5">
                                                    <i class="fas fa-undo"></i>
                                                    <span>{{ __('إرجاع المبلغ') }}</span>
                                                    <span class="badge bg-white text-danger ms-1 font-monospace">({{ number_format($excessAmount, 0) }})</span>
                                                </a>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1">{{ __('غير مصرح بالاسترجاع') }}</span>
                                            @endif

                                        @elseif($pendingAmount > 0)
                                            @if($canPaySurgeries)
                                                <a href="{{ route('cashier.surgeries.payment.form', $surgery->id) }}" 
                                                   class="btn btn-success btn-sm px-3.5 py-1.5 fw-bold rounded-pill shadow-xs d-inline-flex align-items-center gap-1.5"
                                                   style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">
                                                    <i class="fas fa-cash-register"></i>
                                                    <span>{{ __('قبض وتسديد') }}</span>
                                                    <i class="fas fa-arrow-left ms-0.5 fa-xs"></i>
                                                </a>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1">{{ __('غير مصرح بالقبض') }}</span>
                                            @endif

                                        @elseif($excessAmount > 0)
                                            @if($canRefund)
                                                <a href="{{ route('cashier.surgeries.payment.form', $surgery->id) }}" 
                                                   class="btn btn-warning btn-sm px-3 py-1.5 text-dark fw-bold rounded-pill shadow-xs d-inline-flex align-items-center gap-1.5">
                                                    <i class="fas fa-undo text-dark"></i>
                                                    <span>{{ __('إرجاع الفارق') }}</span>
                                                    <span class="badge bg-dark text-white ms-1 font-monospace">({{ number_format($excessAmount, 0) }})</span>
                                                </a>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1">{{ __('غير مصرح بالاسترجاع') }}</span>
                                            @endif

                                        @elseif($pendingAmount == 0 && $surgery->billing_status === 'pending_review')
                                            @can('review surgery prices')
                                                <a href="{{ route('accountant.surgeries.review', $surgery->id) }}" 
                                                   class="btn btn-warning btn-sm px-3 py-1.5 text-dark fw-bold rounded-pill shadow-xs d-inline-flex align-items-center gap-1.5">
                                                    <i class="fas fa-calculator"></i>
                                                    <span>{{ __('تسعير العملية') }}</span>
                                                </a>
                                            @else
                                                <span class="badge bg-warning-subtle text-dark border border-warning rounded-pill px-2.5 py-1">
                                                    <i class="fas fa-clock me-1"></i>{{ __('بانتظار التسعير') }}
                                                </span>
                                            @endcan

                                        @else
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1">
                                                <i class="fas fa-check-double"></i>{{ __('مدفوع بالكامل') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- رسالة عند عدم وجود نتائج بحث -->
                <div id="noSearchResultsAlert" class="text-center py-5 d-none">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center p-4 mb-3 text-muted">
                        <i class="fas fa-search fa-2x"></i>
                    </div>
                    <h5 class="fw-bold text-dark">{{ __('لا توجد نتائج مطابقة لبحثك') }}</h5>
                    <p class="text-muted small mb-0">{{ __('يرجى التأكد من اسم المريض أو رقم العملية أو مصطلح البحث.') }}</p>
                </div>
            @else
                <!-- حالة لا توجد عمليات معلقة -->
                <div class="text-center py-5 px-3">
                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center p-4 mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-check-circle fa-3x"></i>
                    </div>
                    <h4 class="fw-bold text-slate-800 mb-1">{{ __('لا توجد عمليات معلقة حالياً') }}</h4>
                    <p class="text-muted small mb-0">{{ __('جميع العمليات الجراحية المجدولة مسددة ومصفاة بالكامل.') }}</p>
                </div>
            @endif
        </div>
        {{-- نهاية التبويب الأول (المعلقة) --}}

        {{-- التبويب الثاني: نزلاء الردهة المسددون (قيد الإقامة) --}}
        <div class="tab-pane fade {{ $activeTabKey === 'inpatient' ? 'show active' : '' }}" id="tab-inpatient-pane" role="tabpanel">
            @if(isset($inpatientSurgeries) && $inpatientSurgeries->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-right" style="direction: rtl;">
                        <thead class="table-light text-slate-700 border-bottom" style="background: #f8fafc; font-size: 0.85rem;">
                            <tr class="text-uppercase">
                                <th style="width: 75px;" class="text-center">{{ __('رقم العملية') }}</th>
                                <th style="min-width: 220px;">{{ __('المريض والتغطية') }}</th>
                                <th style="min-width: 200px;">{{ __('العملية والقسم والغرفة') }}</th>
                                <th style="min-width: 160px;">{{ __('الطبيب الجراح') }}</th>
                                <th style="min-width: 170px;">{{ __('حالة الإقامة والإذن الطبي') }}</th>
                                <th class="text-end" style="width: 130px;">{{ __('المسدد') }}</th>
                                <th class="text-center" style="width: 230px;">{{ __('إجراء الكاشير') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($inpatientSurgeries as $inpatient)
                                @php
                                    $inpPatient = $inpatient->patient;
                                    $inpPatientName = $inpPatient?->user?->name ?? __('غير محدد');
                                    $inpRoom = $inpatient->room;
                                    $inpStay = $inpatient->calculateStayDetails();
                                    $inpPaidTotal = (float)($inpatient->surgery_fee_paid_amount ?? 0) + (float)($inpatient->room_fee_paid_amount ?? 0) + (float)$inpatient->extraServices->where('payment_status', 'paid')->sum('total_amount');
                                    $isHighlighted = (string)request('highlight') === (string)$inpatient->id;
                                @endphp
                                <tr class="inpatient-table-row {{ $isHighlighted ? 'table-success' : '' }}" style="{{ $isHighlighted ? 'border-right: 4px solid #10b981 !important;' : '' }}">
                                    <td class="text-center font-monospace fw-bold text-muted">
                                        #{{ $inpatient->id }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-success-subtle text-success p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <i class="fas fa-user-check"></i>
                                            </div>
                                            <div>
                                                <a href="{{ route('patients.show', $inpPatient->id ?? 0) }}" class="fw-bold text-dark text-decoration-none">
                                                    {{ $inpPatientName }}
                                                </a>
                                                <div class="small text-muted">
                                                    @if($inpPatient?->phone)
                                                        <span class="font-monospace">{{ $inpPatient->phone }}</span>
                                                    @endif
                                                    @if($inpatient->insurance_type === 'hi')
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">ضمان صحي</span>
                                                    @elseif($inpatient->insurance_type === 'moi')
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">داخلية</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1">نقدي</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-slate-800">
                                            {{ $inpatient->surgicalOperation?->name ?? $inpatient->surgery_type ?? __('عملية جراحية') }}
                                        </div>
                                        <div class="small text-muted mt-0.5">
                                            <span>{{ $inpatient->department?->name ?? '-' }}</span>
                                            @if($inpRoom)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">
                                                    <i class="fas fa-bed me-1"></i>غرفة {{ $inpRoom->room_number }}
                                                </span>
                                            @else
                                                <span class="badge bg-warning-subtle text-dark border border-warning ms-1">
                                                    <i class="fas fa-hourglass-half me-1"></i>وضع الانتظار
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-1.5 text-slate-800">
                                            <i class="fas fa-user-md text-primary"></i>
                                            <span class="fw-medium">{{ $inpatient->doctor?->user?->name ?? __('غير محدد') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($inpatient->medical_discharged_at)
                                            <span class="badge bg-success py-1 px-2.5 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center">
                                                <i class="fas fa-stethoscope me-1"></i>{{ __('إذن طبي صادر') }} ({{ $inpatient->medical_discharged_at->format('H:i') }})
                                            </span>
                                            <div class="small text-muted mt-1">
                                                <i class="fas fa-check-double text-success me-1"></i>{{ __('السرير مفرغ ومتاح') }}
                                            </div>
                                        @else
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle py-1 px-2 rounded-pill fw-semibold">
                                                <i class="fas fa-procedures me-1"></i>{{ __('نزيل بالردهة (مسدد)') }}
                                            </span>
                                            <div class="small text-muted mt-1">
                                                <i class="fas fa-clock me-1"></i>{{ __('إقامة:') }} {{ $inpStay['days_count'] ?? 1 }} {{ __('أيام') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-success font-monospace">
                                        {{ number_format($inpPaidTotal, 0) }}
                                        <small class="text-muted fw-normal">IQD</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-1.5 flex-wrap">
                                            {{-- زر إضافة خدمة متفرقة --}}
                                            <a href="{{ route('cashier.surgeries.payment.form', $inpatient->id) }}" 
                                               class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 fw-semibold d-inline-flex align-items-center gap-1 shadow-2xs"
                                               title="{{ __('إضافة خدمات ومستلزمات متفرقة وقبض وصل جديد فوراً دون إعادة فتح الوصل القديم') }}">
                                                <i class="fas fa-plus-circle"></i>
                                                <span>{{ __('إضافة خدمة') }}</span>
                                            </a>

                                            {{-- زر التخريج النهائي وبراءة الذمة من الكاشير --}}
                                            <form action="{{ route('cashier.surgeries.final-discharge', $inpatient->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('تأكيد إتمام براءة الذمة والتخريج النهائي للمريض (:name) وإغلاق ملف العملية مالياً؟', ['name' => $inpPatientName]) }}');">
                                                @csrf
                                                <button type="submit" 
                                                        class="btn btn-sm btn-success rounded-pill px-2.5 py-1 fw-bold d-inline-flex align-items-center gap-1 shadow-2xs"
                                                        title="{{ __('إتمام براءة الذمة وتخريج المريض نهائياً من المستشفى وأرشفة العملية') }}">
                                                    <i class="fas fa-sign-out-alt"></i>
                                                    <span>{{ __('تخريج وبراءة ذمة') }}</span>
                                                </button>
                                            </form>

                                            <a href="{{ route('surgeries.show', $inpatient->id) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1 shadow-2xs" title="{{ __('عرض تفاصيل العملية') }}" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 px-3">
                    <div class="rounded-circle bg-light text-muted d-inline-flex align-items-center justify-content-center p-4 mb-3" style="width: 70px; height: 70px;">
                        <i class="fas fa-bed fa-2x"></i>
                    </div>
                    <h5 class="fw-bold text-slate-800 mb-1">{{ __('لا يوجد نزلاء ردهة مسددون حالياً') }}</h5>
                    <p class="text-muted small mb-0">{{ __('المرضى المسددون بالكامل والراقدون في الردهة سيظهرون هنا لإضافة أي خدمات أو إتمام براءة الذمة.') }}</p>
                </div>
            @endif
        </div>
        {{-- نهاية التبويب الثاني --}}
    </div>
        </div>
    </div>
</div>

<!-- سكريبت البحث والتصفية الفوري لصفوف الجدول -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('surgeryLiveSearch');
    const clearBtn = document.getElementById('clearSearchBtn');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const tableRows = document.querySelectorAll('.surgery-table-row');
    const noResults = document.getElementById('noSearchResultsAlert');
    const visibleCounter = document.getElementById('visibleSurgeriesCount');

    let currentFilter = 'all';
    let currentQuery = '';

    function applyFilterAndSearch() {
        let visibleCount = 0;
        const q = currentQuery.trim().toLowerCase();

        tableRows.forEach(row => {
            const rowFilterType = row.getAttribute('data-filter-type') || 'pending';
            const rowSearchText = row.getAttribute('data-search-text') || '';

            // مطابقة الفلتر
            let matchesFilter = true;
            if (currentFilter === 'pending') {
                matchesFilter = (rowFilterType === 'pending');
            } else if (currentFilter === 'refund') {
                matchesFilter = (rowFilterType === 'refund');
            } else if (currentFilter === 'review') {
                matchesFilter = (rowFilterType === 'review');
            }

            // مطابقة البحث
            let matchesSearch = true;
            if (q.length > 0) {
                matchesSearch = rowSearchText.includes(q);
            }

            if (matchesFilter && matchesSearch) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (visibleCounter) {
            visibleCounter.textContent = visibleCount + ' {{ __('عملية') }}';
        }

        if (noResults) {
            noResults.classList.toggle('d-none', visibleCount > 0);
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentQuery = this.value;
            if (clearBtn) {
                clearBtn.style.display = currentQuery.length > 0 ? 'inline-block' : 'none';
            }
            applyFilterAndSearch();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
                currentQuery = '';
                this.style.display = 'none';
                applyFilterAndSearch();
                searchInput.focus();
            }
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            filterBtns.forEach(b => {
                b.classList.remove('active', 'btn-dark', 'btn-warning', 'btn-danger', 'btn-info');
                const f = b.getAttribute('data-filter');
                if (f === 'all') b.classList.add('btn-outline-secondary');
                else if (f === 'pending') b.classList.add('btn-outline-warning', 'text-dark');
                else if (f === 'refund') b.classList.add('btn-outline-danger');
                else if (f === 'review') b.classList.add('btn-outline-info');
            });

            this.classList.add('active');
            currentFilter = this.getAttribute('data-filter');
            if (currentFilter === 'all') {
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-dark');
            } else if (currentFilter === 'pending') {
                this.classList.remove('btn-outline-warning', 'text-dark');
                this.classList.add('btn-warning', 'text-dark');
            } else if (currentFilter === 'refund') {
                this.classList.remove('btn-outline-danger');
                this.classList.add('btn-danger');
            } else if (currentFilter === 'review') {
                this.classList.remove('btn-outline-info');
                this.classList.add('btn-info', 'text-white');
            }

            applyFilterAndSearch();
        });
    });
});
</script>
@endsection
