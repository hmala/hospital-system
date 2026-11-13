@extends('layouts.app')

@section('content')
<style>
    .segmented-control-pills {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 50rem;
        padding: 3px;
        gap: 2px;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        position: relative;
        user-select: none;
        width: 100%;
        justify-content: space-between;
    }
    .segmented-pill-input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        pointer-events: none;
    }
    .segmented-pill-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 4px 10px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        border-radius: 50rem;
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 0;
        flex: 1;
        text-align: center;
        white-space: nowrap;
    }
    .segmented-pill-label:hover {
        color: #1e293b;
        background: rgba(255, 255, 255, 0.7);
    }
    .segmented-pill-label i {
        font-size: 0.72rem;
    }
    /* Active Included (Green) */
    .segmented-pill-input:checked + .segmented-pill-label.pill-included {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.35);
    }
    /* Active Unbundled / Non-included (Blue) */
    .segmented-pill-input:checked + .segmented-pill-label.pill-unbundled {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.35);
    }

    /* Extra Services Smart Autocomplete (ICD-10 Style) */
    .extra-service-autocomplete-wrapper {
        position: relative;
        overflow: visible !important;
    }
    .extra-services-autocomplete-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        right: 0;
        left: 0;
        width: 100% !important;
        min-width: 340px;
        z-index: 1065 !important;
        max-height: 380px;
        overflow-y: auto;
        background: #ffffff !important;
        border: 2px solid #f59e0b !important;
        border-radius: 12px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.22), 0 4px 12px rgba(245, 158, 11, 0.15) !important;
    }
    .extra-service-dropdown-header {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        border-bottom: 1px solid #fde68a;
        padding: 8px 14px;
        font-size: 0.8rem;
        font-weight: 700;
        color: #92400e;
    }
    .extra-service-item {
        padding: 9px 14px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: all 0.15s ease;
        text-align: right;
        background: #ffffff;
    }
    .extra-service-item:hover, .extra-service-item.active {
        background-color: #fffbeb !important;
        border-right: 4px solid #f59e0b;
    }
    .extra-service-item:last-child {
        border-bottom: none;
    }
    .extra-service-price-badge {
        font-family: monospace;
        font-weight: 700;
        font-size: 0.85rem;
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
        padding: 2px 8px;
        border-radius: 6px;
        white-space: nowrap;
    }
    .extra-service-cat-badge {
        font-size: 0.72rem;
        padding: 2px 7px;
        border-radius: 5px;
        font-weight: 600;
        white-space: nowrap;
    }
    .extra-service-custom-option {
        background-color: #f8fafc;
        border-top: 2px dashed #f59e0b;
        font-weight: 700;
        color: #b45309;
        padding: 10px 14px;
    }
    .extra-service-custom-option:hover {
        background-color: #fef3c7 !important;
    }
    .price-flash-highlight {
        animation: priceFlash 1.2s ease-out;
    }
    @keyframes priceFlash {
        0% { background-color: #d1fae5; border-color: #10b981; }
        100% { background-color: #ffffff; border-color: #dee2e6; }
    }
</style>
@php
    $isCancelled = $surgery->status === 'cancelled';
    $netPatientPaidCash = (float) $surgery->payments()->sum('amount');

    // حساب التكاليف مع تتبع حالة الدفع
    $surgeryFee = $isCancelled ? 0 : ($surgery->surgery_fee ?? 0);
    $additionalOpsFee = $isCancelled ? 0 : $surgery->additionalOperations->sum('fee');
    $devicesFee = $isCancelled ? 0 : $surgery->medicalDevices->sum('pivot.price');
    $totalSurgeryFee = $isCancelled ? 0 : ($surgeryFee + $additionalOpsFee + $devicesFee);
    $surgeryFeePaidAmount = $surgery->surgery_fee_paid_amount ?? 0;
    $remainingSurgeryFee = $isCancelled ? 0 : max(0, $totalSurgeryFee - $surgeryFeePaidAmount);
    $excessSurgeryFee = $isCancelled ? ($netPatientPaidCash > 0 ? $netPatientPaidCash : $surgeryFeePaidAmount) : ($surgeryFeePaidAmount > $totalSurgeryFee ? ($surgeryFeePaidAmount - $totalSurgeryFee) : 0);
    $surgeryFeePaid = $isCancelled ? ($surgeryFeePaidAmount <= 0) : ($surgery->surgery_fee_paid === 'paid' || ($remainingSurgeryFee <= 0 && $excessSurgeryFee <= 0));
    
    // بيانات التأمين والضمان الصحي
    $patient = $surgery->patient;
    $defaultInsurance = $surgery->insurance_type ?? $patient->insurance_type ?? 'none';
    $isInsurance = ($defaultInsurance !== 'none');
    $defaultCardNo = $patient->insurance_card_no ?? $patient->insurance_booklet_number ?? '';
    $hiCategory = $patient ? $patient->healthInsuranceCategory : null;
    if ($defaultInsurance === 'hi' && $patient) {
        $defaultCopay = $patient->getCopayPercentageFor('surgery');
    } else {
        $defaultCopay = (float)($patient->copay_percentage ?? 15.0);
    }

    // رسوم الغرفة الفندقية (الليلة الأولى + الليالي الإضافية بعد 12 ظهراً مع استقطاع الضمان المتدرج)
    $stayDetails = $surgery->calculateStayDetails();
    $isStayIns = in_array($defaultInsurance, ['hi', 'moi']) && ($stayDetails['is_insurance_calculated'] ?? false);
    $stayPatientShare = (float)($stayDetails['patient_stay_share'] ?? ($stayDetails['total_fee'] ?? 0));
    $stayInsuranceShare = (float)($stayDetails['insurance_stay_share'] ?? 0);

    $roomFee = $isCancelled ? 0 : ($stayDetails['total_fee'] ?? ($surgery->room_fee ?? 0));
    $roomFeePaidAmount = (float)($surgery->room_fee_paid_amount ?? 0);
    $targetPatientRoomFee = $isStayIns ? $stayPatientShare : $roomFee;
    $remainingRoomFee = $isCancelled ? 0 : max(0, $targetPatientRoomFee - $roomFeePaidAmount);
    $excessRoomFee = $isCancelled ? 0 : max(0, $roomFeePaidAmount - $targetPatientRoomFee);
    $roomFeePaid = $isCancelled ? ($roomFeePaidAmount <= 0) : ($remainingRoomFee <= 0 && $excessRoomFee <= 0);
    $totalExcess = $excessSurgeryFee + $excessRoomFee;
    
    // تحاليل معلقة ومدفوعة
    $pendingLabTests = $isCancelled ? collect() : $surgery->labTests->where('payment_status', '!=', 'paid');
    $paidLabTests = $surgery->labTests->filter(function($test) use ($isCancelled) {
        return $test->payment_status === 'paid' || !empty($test->payment_id) || ($isCancelled && $test->labTest);
    });
    $labCopayPct = match($defaultInsurance) {
        'hi' => ($patient?->getCopayPercentageFor('lab') ?? 15),
        'moi' => (float)($patient?->copay_percentage ?? 15),
        default => 100
    };
    $pendingLabFee = $isCancelled ? 0 : $pendingLabTests->sum(function($test) use ($defaultInsurance, $labCopayPct) {
        $m = $test->labTest;
        if (!$m) return 0;
        $base = (float)($m->price ?? 0);
        if (!in_array($defaultInsurance, ['hi', 'moi'])) {
            return $base;
        }
        $hi = (float)(($m->hi_price > 0) ? $m->hi_price : $base);
        $moi = (float)(($m->moi_price > 0) ? $m->moi_price : $base);
        $approved = ($defaultInsurance === 'hi') ? $hi : $moi;
        return round($approved * ($labCopayPct / 100));
    });
    $paidLabFee = $paidLabTests->sum(function($test) {
        if ($test->is_package_included) {
            return 0;
        }
        return (float)($test->labTest?->price ?? 0);
    });
    
    // أشعة معلقة ومدفوعة
    $pendingRadiologyTests = $isCancelled ? collect() : $surgery->radiologyTests->where('payment_status', '!=', 'paid');
    $paidRadiologyTests = $surgery->radiologyTests->filter(function($test) use ($isCancelled) {
        return $test->payment_status === 'paid' || !empty($test->payment_id) || ($isCancelled && $test->radiologyType);
    });
    $radCopayPct = match($defaultInsurance) {
        'hi' => ($patient?->getCopayPercentageFor('radiology') ?? 15),
        'moi' => (float)($patient?->copay_percentage ?? 15),
        default => 100
    };
    $pendingRadiologyFee = $isCancelled ? 0 : $pendingRadiologyTests->sum(function($test) use ($defaultInsurance, $radCopayPct) {
        $m = $test->radiologyType;
        if (!$m) return 0;
        $base = (float)($m->base_price ?? 0);
        if (!in_array($defaultInsurance, ['hi', 'moi'])) {
            return $base;
        }
        $hi = (float)(($m->hi_price > 0) ? $m->hi_price : $base);
        $moi = (float)(($m->moi_price > 0) ? $m->moi_price : $base);
        $approved = ($defaultInsurance === 'hi') ? $hi : $moi;
        return round($approved * ($radCopayPct / 100));
    });
    $paidRadiologyFee = $paidRadiologyTests->sum(function($test) {
        if ($test->is_package_included) {
            return 0;
        }
        return (float)($test->radiologyType?->base_price ?? 0);
    });
    
    // خدمات إضافية وفندقية معلقة ومدفوعة
    $pendingExtraServices = $isCancelled ? collect() : $surgery->extraServices->where('payment_status', '!=', 'paid');
    $paidExtraServices = $surgery->extraServices->where('payment_status', 'paid');
    $pendingExtraFee = $isCancelled ? 0 : $pendingExtraServices->sum('total_amount');
    $paidExtraFee = $paidExtraServices->sum(function($extra) {
        if ($extra->is_package_included) {
            return 0;
        }
        return (float)($extra->total_amount ?? 0);
    });

    // تفاصيل مدفوعات الصندوق والتأمين
    $patientPaidCash = (float) $surgery->payments->sum('amount');
    $insurancePaidAmount = (float) $surgery->payments->sum('insurance_share');

    // المبالغ (تشمل رسوم الغرفة وتعتمد على المبالغ المدفوعة جزئياً والخدمات الإضافية)
    $hasPendingItems = ($remainingSurgeryFee > 0) || ($remainingRoomFee > 0) || ($pendingLabTests->count() > 0) || ($pendingRadiologyTests->count() > 0) || ($pendingExtraServices->count() > 0);
    $pendingAmount = $isCancelled ? 0 : ($remainingSurgeryFee + $remainingRoomFee + $pendingLabFee + $pendingRadiologyFee + $pendingExtraFee);
    $paidAmount = $surgeryFeePaidAmount + $roomFeePaidAmount + $paidLabFee + $paidRadiologyFee + $paidExtraFee;
    $totalAmount = $isCancelled ? 0 : ($totalSurgeryFee + $roomFee + $pendingLabFee + $paidLabFee + $pendingRadiologyFee + $paidRadiologyFee + $pendingExtraFee + $paidExtraFee);
@endphp

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card border-0 shadow-sm">
                <div class="card-header text-white" style="background: linear-gradient(135deg, {{ $isCancelled ? '#dc2626 0%, #b91c1c 100%' : '#0d6efd 0%, #0a58ca 100%' }});">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            @if($isCancelled)
                                <i class="fas fa-undo me-2"></i>
                                استرجاع رسوم العملية الجراحية (Refund)
                                <span class="badge bg-white text-danger ms-2">عملية ملغاة</span>
                            @else
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                تسوية ودفع رسوم العملية الجراحية
                                @if($surgery->payment_status === 'partial')
                                    <span class="badge bg-warning text-dark ms-2">دفع جزئي سابق</span>
                                @endif
                            @endif
                        </h5>
                        <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-arrow-right me-1"></i>العودة لسجل العمليات
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="alert-heading mb-1"><i class="fas fa-times-circle me-1"></i> تعذر الدفع بسبب أخطاء في المدخلات:</h6>
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- بطاقة معلومات العملية والمريض الموحدة السريرية -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100 shadow-2xs">
                                <h6 class="text-primary fw-bold mb-3 d-flex align-items-center gap-2">
                                    <i class="fas fa-procedures"></i>
                                    تفاصيل العملية الجراحية
                                </h6>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">رقم العملية:</span>
                                    <span class="badge bg-dark font-monospace fs-7">#{{ $surgery->id }}</span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">نوع العملية:</span>
                                    <strong class="text-dark">{{ $surgery->surgery_type }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">الموقع السريري:</span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                                        <i class="fas fa-bed me-1"></i>غرفة {{ $surgery->room?->room_number ?? 'غير محدد' }} ({{ $surgery->room?->floor ?? '—' }})
                                    </span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">الجراح المعالج:</span>
                                    <strong>د. {{ $surgery->doctor?->user?->name ?? 'غير محدد' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">الموعد المحدد:</span>
                                    <span class="font-monospace">{{ $surgery->scheduled_date->format('Y-m-d') }} ({{ $surgery->scheduled_time->format('H:i') }})</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100 shadow-2xs">
                                <h6 class="text-success fw-bold mb-3 d-flex align-items-center justify-content-between">
                                    <span><i class="fas fa-user-circle me-2"></i>معلومات المريض والتغطية المالية</span>
                                    <span class="badge {{ $defaultInsurance !== 'none' ? 'bg-success' : 'bg-primary' }}">
                                        {{ $defaultInsurance === 'hi' ? '🛡️ الضمان الصحي الوطني' : ($defaultInsurance === 'moi' ? '🛡️ ضمان الداخلية' : '💵 نقدي (كاش)') }}
                                    </span>
                                </h6>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">اسم المريض:</span>
                                    <strong class="text-dark fs-6">{{ $surgery->patient?->user?->name ?? 'غير محدد' }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">رقم الهوية / الملف:</span>
                                    <span class="font-monospace text-dark fw-bold">{{ $surgery->patient?->national_id ?? 'غير محدد' }}</span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">رقم الهاتف:</span>
                                    <span class="font-monospace" dir="ltr">{{ $surgery->patient?->user?->phone ?? 'غير محدد' }}</span>
                                </div>
                                @if($defaultInsurance !== 'none')
                                    <div class="mb-2 d-flex justify-content-between align-items-center">
                                        <span class="text-muted">رقم بطاقة الضمان:</span>
                                        <span class="font-monospace text-primary fw-bold">{{ $defaultCardNo ?: 'مسجل' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">فئة المريض والتحمل:</span>
                                        <span class="badge bg-info text-dark">
                                            {{ $hiCategory?->name ?? 'الفئة المعرفة' }} (استقطاع العمليات: {{ (float)$defaultCopay }}%)
                                        </span>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted">التصنيف المالي:</span>
                                        <span class="badge bg-secondary-subtle text-secondary border">دفع نقدي كامل (خارج التأمين)</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- ملخص حالة الدفع -->
                    @if($paidAmount > 0)
                    <div class="alert alert-info mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center border-end">
                                <small class="text-muted d-block">المدفوع سابقاً (المعتمد المسدد)</small>
                                <h4 class="text-success mb-0">{{ number_format($paidAmount, 0) }} IQD</h4>
                            </div>
                            <div class="col-md-4 text-center border-end">
                                <small class="text-muted d-block">المتبقي للدفع</small>
                                <h4 class="text-warning mb-0">{{ number_format($pendingAmount, 0) }} IQD</h4>
                            </div>
                            <div class="col-md-4 text-center">
                                <small class="text-muted d-block">الإجمالي الكلي المعتمد</small>
                                <h4 class="text-primary mb-0">{{ number_format($totalAmount, 0) }} IQD</h4>
                            </div>
                        </div>
                        @if($insurancePaidAmount > 0 || in_array($defaultInsurance, ['hi', 'moi']))
                        <div class="row pt-2 mt-2 border-top text-center" style="font-size: 0.85rem;">
                            <div class="col-md-6 text-md-start ps-3">
                                <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                    <i class="fas fa-user-check me-1"></i>
                                    المدفوع نقداً من المريض: <strong>{{ number_format($patientPaidCash, 0) }} د.ع</strong>
                                </span>
                            </div>
                            <div class="col-md-6 text-md-end pe-3">
                                <span class="badge bg-info-subtle text-info-emphasis border border-info px-2 py-1">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    مغطى بالضمان الصحي: <strong>{{ number_format($insurancePaidAmount, 0) }} د.ع</strong>
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- العناصر المدفوعة سابقاً -->
                    @if($paidAmount > 0)
                    <div class="mb-4">
                        <h6 class="text-success mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            العناصر المدفوعة سابقاً
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="table-success">
                                    <tr>
                                        <th>البند</th>
                                        <th>التفاصيل</th>
                                        <th class="text-end">التكلفة (IQD)</th>
                                        <th class="text-center">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($surgeryFeePaidAmount > 0)
                                    <tr class="table-success">
                                        <td>
                                            <i class="fas fa-procedures text-success me-2"></i>
                                            رسوم العملية الجراحية
                                        </td>
                                        <td>
                                            {{ $surgery->surgery_type }}
                                            @if($remainingSurgeryFee > 0)
                                                <small class="text-muted d-block">(تم دفع جزء من الرسوم)</small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($surgeryFeePaidAmount, 0) }}
                                            @if($remainingSurgeryFee > 0)
                                                <br><small class="text-muted">من إجمالي {{ number_format($surgeryFee, 0) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($remainingSurgeryFee > 0)
                                                <span class="badge bg-warning text-dark"><i class="fas fa-adjust me-1"></i>مدفوع جزئياً</span>
                                            @else
                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    
                                    @if($roomFeePaidAmount > 0)
                                    <tr class="table-success">
                                        <td>
                                            <i class="fas fa-door-open text-success me-2"></i>
                                            أجور الغرفة
                                        </td>
                                        <td>
                                            الغرفة {{ $surgery->room->room_number ?? 'غير محدد' }}
                                            @if($remainingRoomFee > 0)
                                                <small class="text-muted d-block">(تم دفع جزء من الرسوم)</small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            {{ number_format($roomFeePaidAmount, 0) }}
                                            @if($remainingRoomFee > 0)
                                                <br><small class="text-muted">من إجمالي {{ number_format($roomFee, 0) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($remainingRoomFee > 0)
                                                <span class="badge bg-warning text-dark"><i class="fas fa-adjust me-1"></i>مدفوع جزئياً</span>
                                            @else
                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endif
                                    
                                    @foreach($paidLabTests as $labTest)
                                    <tr class="table-success">
                                        <td>
                                            <i class="fas fa-vial text-success me-2"></i>
                                            تحليل
                                        </td>
                                        <td>
                                            @if($labTest->labTest)
                                                {{ $labTest->labTest->name }} ({{ $labTest->labTest->code ?? '-' }})
                                            @else
                                                <em>غير محدد</em> (ID #{{ $labTest->lab_test_id }})
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($labTest->is_package_included)
                                                <span class="badge bg-info text-dark">مشمول بالباقة (0 د.ع)</span>
                                            @else
                                                {{ number_format(optional($labTest->labTest)->price ?? 0, 0) }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                    
                                    @foreach($paidRadiologyTests as $radiologyTest)
                                    <tr class="table-success">
                                        <td>
                                            <i class="fas fa-radiation text-success me-2"></i>
                                            أشعة
                                        </td>
                                        <td>{{ $radiologyTest->radiologyType->name ?? 'غير محدد' }}</td>
                                        <td class="text-end">
                                            @if($radiologyTest->is_package_included)
                                                <span class="badge bg-info text-dark">مشمول بالباقة (0 د.ع)</span>
                                            @else
                                                {{ number_format($radiologyTest->radiologyType->base_price ?? 0, 0) }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                        </td>
                                    </tr>
                                    @endforeach

                                    @foreach($paidExtraServices as $extraService)
                                    <tr class="table-success">
                                        <td>
                                            <i class="fas fa-concierge-bell text-success me-2"></i>
                                            خدمة إضافية
                                        </td>
                                        <td>{{ $extraService->name ?? 'غير محدد' }}</td>
                                        <td class="text-end">
                                            @if($extraService->is_package_included)
                                                <span class="badge bg-info text-dark">مشمول بالباقة (0 د.ع)</span>
                                            @else
                                                {{ number_format($extraService->total_amount ?? 0, 0) }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>مدفوع</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-success">
                                    <tr>
                                        <td colspan="2" class="text-end"><strong>إجمالي المدفوع المعتمد:</strong></td>
                                        <td class="text-end"><strong>{{ number_format($paidAmount, 0) }} IQD</strong></td>
                                        <td></td>
                                    </tr>
                                    @if($patientPaidCash > 0 && ($insurancePaidAmount > 0 || in_array($defaultInsurance, ['hi', 'moi'])))
                                    <tr class="text-muted" style="font-size: 0.85rem; background-color: #f8fafc;">
                                        <td colspan="4" class="text-end py-2">
                                            <span class="text-success fw-bold"><i class="fas fa-user-check me-1"></i>المسدد نقداً من المريض: {{ number_format($patientPaidCash, 0) }} د.ع</span>
                                            <span class="mx-2 text-muted">|</span>
                                            <span class="text-info-emphasis fw-bold"><i class="fas fa-shield-alt me-1"></i>مطالبة وتغطية الضمان: {{ number_format($insurancePaidAmount, 0) }} د.ع</span>
                                        </td>
                                    </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if($totalExcess > 0)
                    <!-- نموذج إرجاع المبلغ (Refund) -->
                    <div class="alert {{ $isCancelled ? 'alert-danger border-danger' : 'alert-info border-info' }} shadow-sm mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas {{ $isCancelled ? 'fa-ban text-danger' : 'fa-info-circle text-info' }} fa-2x me-3"></i>
                            <div>
                                <h5 class="alert-heading fw-bold mb-1">
                                    {{ $isCancelled ? 'العملية ملغاة - مستحق استرجاع مالي للمريض' : 'مسترجع مالي معلق للمريض' }}
                                </h5>
                                <p class="mb-0">
                                    @if($isCancelled)
                                        تم إلغاء حجز العملية. المبلغ النقدي الفعلي المسدد من قبل المريض والمستحق إرجاعه هو: 
                                        <strong class="text-danger fs-5">{{ number_format($totalExcess, 0) }} د.ع</strong>
                                        @if($surgery->insurance_type && $surgery->insurance_type !== 'none')
                                            <span class="badge bg-secondary ms-2">تم إلغاء مطالبة التأمين تلقائياً</span>
                                        @endif
                                    @else
                                        يوجد مبلغ مدفوع فائض للمريض يستوجب الاسترجاع بمقدار: <strong>{{ number_format($totalExcess, 0) }} د.ع</strong>
                                        @if($excessRoomFee > 0 && $excessSurgeryFee > 0)
                                            (فارق تخفيض الغرفة: {{ number_format($excessRoomFee, 0) }} د.ع + فارق العملية: {{ number_format($excessSurgeryFee, 0) }} د.ع)
                                        @elseif($excessRoomFee > 0)
                                            (بسبب تخفيض أجور الغرفة من {{ number_format($roomFeePaidAmount, 0) }} إلى {{ number_format($roomFee, 0) }} د.ع)
                                        @endif
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card {{ $isCancelled ? 'border-danger' : 'border-info' }} mb-4 shadow-sm">
                        <div class="card-header {{ $isCancelled ? 'bg-danger' : 'bg-info' }} text-white fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-undo me-2"></i> معالجة إرجاع المبلغ (Refund)</span>
                            <span class="badge bg-white {{ $isCancelled ? 'text-danger' : 'text-info' }} fs-6">{{ number_format($totalExcess, 0) }} د.ع</span>
                        </div>
                        <div class="card-body bg-white">
                            <form action="{{ route('cashier.surgeries.payment.refund', $surgery->id) }}" method="POST">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="p-3 border rounded bg-light">
                                            <label class="form-label fw-bold">طريقة إرجاع المبلغ <span class="text-danger">*</span></label>
                                            <div class="d-flex gap-4 mt-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="refund_cash" value="cash" checked required>
                                                    <label class="form-check-label fw-bold" for="refund_cash">💵 نقداً (Cash)</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="payment_method" id="refund_card" value="card">
                                                    <label class="form-check-label fw-bold" for="refund_card">💳 بطاقة (Card)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 border rounded bg-light">
                                            <label for="refund_notes" class="form-label fw-bold">ملاحظات الاسترجاع</label>
                                            <textarea class="form-control" id="refund_notes" name="notes" rows="2" placeholder="ملاحظات حول سبب الاسترجاع..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn {{ $isCancelled ? 'btn-danger' : 'btn-info text-white' }} btn-lg fw-bold px-5 shadow">
                                        <i class="fas fa-check-circle me-2"></i> تأكيد استرجاع {{ number_format($totalExcess, 0) }} د.ع للمريض
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    @if(!$isCancelled)
                    <!-- صندوق إضافة الخدمات الإضافية والفندقية الذكي (مستوحى من محرك ICD-10) -->
                    <div class="card border border-warning border-opacity-50 rounded-4 mb-4 shadow-sm bg-white overflow-visible position-relative" style="z-index: 1050;">
                        <div class="card-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25 py-2.5 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark p-2 rounded-circle">
                                    <i class="fas fa-concierge-bell"></i>
                                </span>
                                <span>إضافة خدمات إضافية وفندقية للعملية الجراحية (تخطيط قلب، مرافق، تزيين، طبلة، غرامات...)</span>
                            </h6>
                            <span class="badge bg-warning bg-opacity-20 text-dark border border-warning border-opacity-50 px-2.5 py-1 rounded-pill small">
                                <i class="fas fa-magic me-1"></i> تصنيف ذكي وبحث فوري مثل ICD-10
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <!-- شرائح التصفية السريعة للفئات -->
                            <div class="d-flex flex-wrap align-items-center gap-1.5 mb-2.5">
                                <span class="small text-muted fw-bold me-1"><i class="fas fa-filter me-1 text-warning"></i> تصنيف سريع:</span>
                                <button type="button" class="btn btn-sm btn-warning fw-bold rounded-pill px-2.5 py-0.5 extra-cat-filter-btn active" data-category="all" style="font-size: 11px;">
                                    الكل ({{ $availableExtraServices->count() }})
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-cat-filter-btn" data-category="medical" style="font-size: 11px;">
                                    <i class="fas fa-heartbeat text-danger me-1"></i> فحوصات وسريرية
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-cat-filter-btn" data-category="accommodation" style="font-size: 11px;">
                                    <i class="fas fa-bed text-primary me-1"></i> فندقية ومرافق
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-cat-filter-btn" data-category="administrative" style="font-size: 11px;">
                                    <i class="fas fa-file-invoice text-info me-1"></i> إدارية وطبلة
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-cat-filter-btn" data-category="decorating" style="font-size: 11px;">
                                    <i class="fas fa-ribbon me-1" style="color: #e91e63;"></i> تزيين الغرف
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-cat-filter-btn" data-category="fine" style="font-size: 11px;">
                                    <i class="fas fa-exclamation-triangle text-danger me-1"></i> غرامات ومخالفات
                                </button>
                            </div>

                            <form action="{{ route('cashier.surgeries.extra-services.add', $surgery) }}" method="POST" id="addExtraServiceForm">
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label small text-muted mb-1 fw-bold">اسم الخدمة الإضافية (ابحث أو اختر أو اكتب خدمة جديدة) *</label>
                                        <div class="position-relative extra-service-autocomplete-wrapper">
                                            <div class="input-group input-group-sm shadow-xs">
                                                <span class="input-group-text bg-white border-warning text-warning"><i class="fas fa-search" id="extra-service-search-icon"></i></span>
                                                <input type="text" class="form-control form-control-sm fw-bold border-warning" id="service_name_input" name="service_name" placeholder="انقر لاختيار خدمة أو اكتب للبحث الذكي..." required autocomplete="off">
                                                <button type="button" class="btn btn-outline-secondary px-2 d-none" id="btn-clear-extra-service" title="مسح">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <datalist id="extra_services_catalog_list" style="display: none;">
                                                @foreach($availableExtraServices as $asrv)
                                                    <option value="{{ $asrv->name }}" data-price="{{ (float)$asrv->default_price }}" data-id="{{ $asrv->id }}" data-category="{{ $asrv->category ?? 'general' }}">
                                                        {{ $asrv->name }} (افتراضي: {{ number_format($asrv->default_price, 0) }} د.ع)
                                                    </option>
                                                @endforeach
                                            </datalist>
                                            <input type="hidden" name="service_id" id="service_id_hidden">
                                            <input type="hidden" name="service_category" id="service_category_hidden" value="general">

                                            <!-- القائمة المنسدلة الذكية للخدمات الإضافية -->
                                            <div id="extra_services_dropdown" class="extra-services-autocomplete-dropdown shadow-lg d-none"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1 fw-bold">السعر المعتمد للوحدة (IQD) *</label>
                                        <div class="input-group input-group-sm shadow-xs">
                                            <span class="input-group-text bg-light text-success fw-bold">IQD</span>
                                            <input type="number" class="form-control form-control-sm fw-bold text-success fs-6" id="service_price_input" name="service_price" min="0" step="any" placeholder="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small text-muted mb-1 fw-bold">الكمية</label>
                                        <input type="number" class="form-control form-control-sm text-center fw-bold" id="service_qty_input" name="service_quantity" min="1" value="1">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted mb-1 fw-bold">ملاحظات (اختياري)</label>
                                        <input type="text" class="form-control form-control-sm" name="service_notes" placeholder="ملاحظة أو تفصيل...">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold text-dark shadow-xs" id="btnAddExtraService">
                                            <i class="fas fa-plus me-1"></i> إضافة
                                        </button>
                                    </div>
                                </div>

                                <!-- بطاقة تأكيد الخدمة المختارة النشطة -->
                                <div id="selected_extra_service_card" class="mt-2.5 p-2 px-3 rounded-3 border bg-warning bg-opacity-10 border-warning border-opacity-25 d-flex align-items-center justify-content-between d-none">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <span class="badge bg-warning text-dark font-monospace px-2.5 py-1" id="selected_extra_cat_badge">خدمة إضافية</span>
                                        <div>
                                            <strong class="text-dark small d-block" id="selected_extra_name_text">اسم الخدمة</strong>
                                            <span class="text-muted small" style="font-size: 11px;">السعر المعتمد بالدليل: <strong class="text-success" id="selected_extra_price_text">0 د.ع</strong></span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0 d-flex align-items-center gap-1" id="btn_change_extra_service">
                                        <i class="fas fa-times-circle"></i>
                                        <span style="font-size: 11px;">تغيير أو اختيار خدمة أخرى</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @endif

                    @if(!$isCancelled && ($pendingAmount > 0 || $hasPendingItems))
                    <!-- نموذج الدفع للعناصر المعلقة -->
                    <form action="{{ route('cashier.surgeries.payment.process', $surgery->id) }}" method="POST" id="paymentForm" novalidate>
                        @csrf

                        @if($defaultInsurance !== 'none')
                            <!-- قسم الضمان الصحي ونسبة التحمل التلقائية المحمية للعمليات -->
                            <div class="p-3 mb-4 rounded-3 border" style="background-color: #f8fafc;">
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                    <h6 class="fw-bold text-primary mb-0 d-flex align-items-center gap-2">
                                        <i class="fas fa-shield-alt"></i>
                                        تغطية الضمان ونسبة التحمل (Co-payment) للعملية الجراحية
                                    </h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success" id="copayBadge">
                                            {{ $defaultInsurance === 'hi' ? 'مشمول بالضمان الصحي الوطني' : 'مشمول بضمان قوى الأمن' }}
                                        </span>
                                        <span class="badge bg-secondary-subtle text-secondary border small py-1 px-2 rounded-pill">
                                            <i class="fas fa-lock me-1"></i>مثبتة آلياً ومحمية
                                        </span>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <!-- جهة الضمان -->
                                    <div class="col-md-6" id="insurance_type_wrapper">
                                        <label class="form-label fw-bold small text-muted">جهة الضمان / التأمين</label>
                                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-dark">
                                                <i class="fas fa-check-circle text-success me-1"></i>
                                                {{ $defaultInsurance === 'hi' ? 'هيئة الضمان الصحي الوطني (HI)' : 'ضمان قوى الأمن الداخلي (وزارة الداخلية)' }}
                                            </span>
                                            <input type="hidden" name="insurance_type" id="insurance_type" value="{{ $defaultInsurance }}">
                                        </div>
                                    </div>

                                    <!-- رقم بطاقة الضمان -->
                                    <div class="col-md-6" id="insurance_card_col">
                                        <label class="form-label fw-bold small text-muted">رقم بطاقة / دفتر الضمان</label>
                                        <input type="text" class="form-control" id="insurance_card_no" name="insurance_card_no" 
                                               value="{{ old('insurance_card_no', $defaultCardNo) }}" placeholder="أدخل رقم الهوية أو الدفتر" readonly>
                                    </div>

                                    @if($hiCategory)
                                    <div class="col-12" id="patient_hi_category_badge">
                                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div>
                                                <span class="badge bg-info text-dark me-1"><i class="fas fa-layer-group me-1"></i>{{ $hiCategory->name }} (الفئة {{ $hiCategory->code }})</span>
                                                <small class="text-muted">نسبة استقطاع العمليات الجراحية: <strong class="text-primary">{{ (float)$hiCategory->surgery_copay }}%</strong></small>
                                            </div>
                                            @if($hiCategory->requires_thermal_stamp)
                                                <span class="badge bg-danger text-white"><i class="fas fa-stamp me-1"></i>شرط الختم الحراري (0%)</span>
                                            @endif
                                        </div>
                                    </div>
                                    @endif

                                    <!-- نسبة التحمل -->
                                    <div class="col-12" id="copay_section">
                                        <div class="p-2 rounded-3 bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-percentage text-primary"></i>
                                                <span class="small text-muted">نسبة التحمل المقررة رسمياً على المريض: <strong class="text-primary fs-6" id="display_copay_badge">{{ (float)$defaultCopay }}%</strong></span>
                                            </div>
                                            <span class="text-muted small">
                                                <i class="fas fa-shield-alt text-success me-1"></i>
                                                مقفلة ومحددة آلياً وفق فئة المريض الرسمية
                                            </span>
                                        </div>
                                        <input type="hidden" id="copay_percentage" name="copay_percentage" value="{{ (float)$defaultCopay }}">
                                    </div>
                                </div>

                                <!-- بطاقة معاينة الحسبة المالية المباشرة للضمان -->
                                <div class="row g-2 mt-3 pt-3 border-top text-center" id="live_pricing_summary">
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border">
                                            <small class="text-muted d-block">إجمالي المبلغ المحدد</small>
                                            <strong class="text-dark fs-6" id="display_approved_price">{{ number_format($pendingAmount, 0) }} د.ع</strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border border-success">
                                            <small class="text-success d-block fw-bold">حصة المريض (المستلم نقداً)</small>
                                            <strong class="text-success fs-6" id="display_patient_share">{{ number_format($pendingAmount, 0) }} د.ع</strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border border-info">
                                            <small class="text-info d-block fw-bold">حصة الضمان (مطالبة)</small>
                                            <strong class="text-info fs-6" id="display_insurance_share">0 د.ع</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- قسم الدفع النقدي والخصم الخاص (عمليات خارج الضمان 100%) -->
                            <input type="hidden" name="insurance_type" id="insurance_type" value="none">
                            <input type="hidden" id="copay_percentage" name="copay_percentage" value="100">

                            <div class="p-3 mb-4 rounded-3 border" style="background-color: #f8fafc;">
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                        <i class="fas fa-wallet text-success"></i>
                                        حساب الدفع النقدي والخصم الخاص (عمليات جراحية)
                                    </h6>
                                    <span class="badge bg-secondary-subtle text-secondary border px-3 py-1 rounded-pill">
                                        دفع نقدي كامل (خارج الضمان)
                                    </span>
                                </div>

                                <!-- صندوق الخصم المالي بكتابة ومقترحات -->
                                <div class="p-3 rounded-3 bg-white border border-danger border-opacity-25 shadow-2xs mb-3" id="discount_section">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-tags text-danger"></i>
                                            <label class="form-label fw-bold small text-dark mb-0">
                                                خصم خاص / إداري للمريض:
                                            </label>
                                            <span class="badge bg-danger fs-6 px-3 py-1" id="display_discount_badge">
                                                0%
                                            </span>
                                        </div>
                                        <span class="text-muted small">
                                            <i class="fas fa-keyboard text-secondary me-1"></i>
                                            اكتب النسبة مباشرة أو اختر من المقترحات
                                        </span>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1 fw-bold">نسبة الخصم %</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light text-danger fw-bold">%</span>
                                                <input type="number" 
                                                       class="form-control form-control-sm fw-bold text-danger" 
                                                       id="discount_percentage" 
                                                       name="discount_percentage" 
                                                       min="0" 
                                                       max="100" 
                                                       step="5" 
                                                       value="0" 
                                                       placeholder="0" 
                                                       list="surgery_discount_percentage_suggestions">
                                                <datalist id="surgery_discount_percentage_suggestions">
                                                    <option value="0">0% (بدون خصم)</option>
                                                    <option value="5">5%</option>
                                                    <option value="10">10%</option>
                                                    <option value="15">15%</option>
                                                    <option value="20">20%</option>
                                                    <option value="25">25%</option>
                                                    <option value="30">30%</option>
                                                    <option value="35">35%</option>
                                                    <option value="40">40%</option>
                                                    <option value="45">45%</option>
                                                    <option value="50">50%</option>
                                                    <option value="55">55%</option>
                                                    <option value="60">60%</option>
                                                    <option value="70">70%</option>
                                                    <option value="75">75%</option>
                                                    <option value="80">80%</option>
                                                    <option value="90">90%</option>
                                                    <option value="100">100% (إعفاء كامل)</option>
                                                </datalist>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1 fw-bold">سبب الخصم أو التوجيه</label>
                                            <input type="text" class="form-control form-control-sm" id="discount_reason" name="discount_reason" 
                                                   placeholder="سبب الخصم أو الجهة المخولة (إدارة / طبيب / إنساني)..." 
                                                   list="surgery_discount_reason_suggestions">
                                            <datalist id="surgery_discount_reason_suggestions">
                                                <option value="خصم إدارة"></option>
                                                <option value="إعفاء بتوجيه الطبيب"></option>
                                                <option value="تخفيض إنساني"></option>
                                                <option value="حالة عملية خاصة"></option>
                                                <option value="خصم موظف / ذوي موظف"></option>
                                                <option value="موافقة المدير التنفيذي"></option>
                                            </datalist>
                                        </div>
                                        <div class="col-md-4 text-md-end pt-md-3">
                                            <span class="text-danger fw-bold small" id="display_discount_amount">
                                                قيمة الخصم: 0 د.ع
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- بطاقة معاينة الحسبة المالية المباشرة للنقدي -->
                                <div class="row g-2 pt-2 border-top text-center" id="live_pricing_summary">
                                    <div class="col-6">
                                        <div class="bg-white p-2 rounded border">
                                            <small class="text-muted d-block">إجمالي المبلغ المحدد</small>
                                            <strong class="text-dark fs-6" id="display_approved_price">{{ number_format($pendingAmount, 0) }} د.ع</strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-white p-2 rounded border border-success">
                                            <small class="text-success d-block fw-bold">الصافي المطلوب قبضه نقداً</small>
                                            <strong class="text-success fs-6" id="display_patient_share">{{ number_format($pendingAmount, 0) }} د.ع</strong>
                                            <div id="summary_discount_note" class="text-danger small mt-1 fw-bold d-none">
                                                <i class="fas fa-tag me-1"></i>خصم: <span id="summary_discount_val">0 د.ع</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- العناصر المعلقة للدفع -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-warning mb-0">
                                    <i class="fas fa-clock me-2"></i>
                                    العناصر المعلقة - اختر ما تريد دفعه الآن مع ضبط الشمولية بالباقة
                                </h6>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success me-2" id="selectAllBtn">
                                        <i class="fas fa-check-double me-1"></i>
                                        تحديد الكل للدفع
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllBtn">
                                        <i class="fas fa-times me-1"></i>
                                        إلغاء الكل
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle table-hover shadow-2xs rounded-3 overflow-hidden" id="surgeryItemsTable">
                                    <thead class="table-primary text-dark" style="background: linear-gradient(135deg, #e0e7ff 0%, #ede9fe 100%);">
                                        <tr>
                                            <th style="width: 45px;" class="text-center">
                                                <i class="fas fa-check-square"></i>
                                            </th>
                                            <th>البند والخدمة</th>
                                            <th class="text-end" style="width: 150px;">السعر المعتمد</th>
                                            <th class="text-end" style="width: 150px;">تحمل المريض (نقداً)</th>
                                            <th class="text-end" style="width: 140px;">حصة الضمان (ذمة)</th>
                                            <th class="text-center" style="width: 220px;">الإجراء والشمول بالباقة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- رسوم العملية (إذا لم تُدفع بالكامل) -->
                                        @if(!$surgeryFeePaid && $remainingSurgeryFee > 0)
                                        <tr class="table-light align-middle">
                                            <td class="text-center">
                                                <input type="checkbox" 
                                                       class="form-check-input payment-item" 
                                                       id="pay_surgery_checkbox"
                                                       name="pay_surgery" 
                                                       value="1"
                                                       data-is-custom="true"
                                                       checked>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fas fa-procedures text-danger fs-5"></i>
                                                    <div>
                                                        <strong class="text-dark fs-6">{{ $surgery->surgery_type ?? 'عملية جراحية' }}</strong>
                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1">العملية الأساسية</span>
                                                        <div class="small text-muted mt-1">
                                                            أجور الجراحة الأساسية: {{ number_format($surgeryFee, 0) }} د.ع
                                                            @if($additionalOpsFee > 0)
                                                                + العمليات الإضافية: {{ number_format($additionalOpsFee, 0) }} د.ع
                                                            @endif
                                                            @if($surgery->medicalDevices->isNotEmpty())
                                                                <br>+ الأجهزة الطبية ({{ $surgery->medicalDevices->count() }}): 
                                                                @foreach($surgery->medicalDevices as $device)
                                                                    {{ $device->name }} ({{ number_format($device->pivot->price ?? 0, 0) }} د.ع)@if(!$loop->last)، @endif
                                                                @endforeach
                                                            @endif
                                                        </div>
                                                        @if($surgeryFeePaidAmount > 0)
                                                            <span class="badge bg-warning text-dark mt-1">تم دفع {{ number_format($surgeryFeePaidAmount, 0) }} سابقاً</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end fw-bold">
                                                {{ number_format($totalSurgeryFee, 0) }} د.ع
                                            </td>
                                            <td class="text-end fw-bold text-success fs-6">
                                                <strong id="surgery_fee_display">{{ number_format($remainingSurgeryFee, 0) }}</strong> د.ع
                                                <input type="hidden" id="surgery_fee_value" value="{{ $remainingSurgeryFee }}">
                                            </td>
                                            <td class="text-end text-muted fw-bold" id="surgery_insurance_display">
                                                0 د.ع
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1.5 fw-bold" style="font-size: 0.82rem;">
                                                    <i class="fas fa-procedures me-1"></i>أصل العملية الجراحية
                                                </span>
                                                <input type="hidden" name="surgery_payment_type" value="full">
                                            </td>
                                        </tr>
                                        @endif

                                        <!-- رسوم الغرفة (إذا كانت محجوزة للعملية ولم تُدفع بالكامل أو مشمولة بالعملية) -->
                                        @if($surgery->room && (!$roomFeePaid || $remainingRoomFee > 0 || ($isStayIns && $roomFeePaidAmount == 0)))
                                        <tr class="table-primary align-middle">
                                            <td class="text-center">
                                                <input type="checkbox" 
                                                       class="form-check-input payment-item" 
                                                       id="pay_room_checkbox"
                                                       name="pay_room" 
                                                       value="1"
                                                       data-is-custom="true"
                                                       checked>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fas fa-door-open text-primary fs-5"></i>
                                                    <div class="w-100">
                                                        <div class="d-flex align-items-center flex-wrap gap-1">
                                                            <strong class="text-dark">أجور إقامة الغرفة ({{ $surgery->room->room_number ?? 'غير محدد' }})</strong>
                                                            @if($surgery->room && $surgery->room->room_type === 'vvip')
                                                                <span class="badge bg-dark text-white"><i class="fas fa-gem text-warning me-1"></i>VVIP</span>
                                                            @elseif($surgery->room && $surgery->room->room_type === 'vip')
                                                                <span class="badge bg-warning text-dark"><i class="fas fa-crown me-1"></i>VIP</span>
                                                            @else
                                                                <span class="badge bg-light text-secondary border">عادية</span>
                                                            @endif

                                                            @if($isStayIns)
                                                                <span class="badge bg-primary text-white"><i class="fas fa-shield-alt me-1"></i>استقطاع متدرج (ضمان)</span>
                                                            @endif
                                                        </div>

                                                        <small class="text-muted d-block mt-1">
                                                            @if($remainingRoomFee == 0 && $isStayIns)
                                                                أجرة الليلة الأولى: مشمولة بالكامل ضمن باقة وتكلفة العملية (0 د.ع)
                                                            @else
                                                                أجرة الليلة الأولى: {{ number_format($stayDetails['initial_fee'], 0) }} د.ع
                                                            @endif
                                                            @if($stayDetails['extra_nights'] > 0)
                                                                + {{ $stayDetails['extra_nights'] }} ليال إضافية ({{ number_format($stayDetails['extra_nights_fee'], 0) }} د.ع)
                                                            @endif
                                                            (نظام فندقي 12:00 ظهراً - إجمالي {{ $stayDetails['total_nights'] }} ليال)
                                                        </small>

                                                        @if($roomFeePaidAmount > 0)
                                                            <span class="badge bg-warning text-dark mt-1">تم دفع {{ number_format($roomFeePaidAmount, 0) }} سابقاً</span>
                                                        @endif

                                                        <div id="room_package_badge_container" class="mt-1">
                                                            @if($remainingRoomFee == 0 || $isStayIns)
                                                                @if(!$isInsurance)
                                                                    <span class="package-status-badge badge bg-warning-subtle text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1">
                                                                        <i class="fas fa-cut"></i> استقطاع من حصة الجراح: <strong id="room_deduction_val">{{ number_format($roomFee, 0) }}</strong> د.ع
                                                                    </span>
                                                                @else
                                                                    <span class="package-status-badge badge bg-info-subtle text-info border border-info border-opacity-25 rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1">
                                                                        <i class="fas fa-shield-alt"></i> مشمول بعقد الضمان
                                                                    </span>
                                                                @endif
                                                            @else
                                                                <span class="package-status-badge badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1">
                                                                    <i class="fas fa-coins me-1"></i>تسديد أجور الإقامة منفصلة
                                                                </span>
                                                            @endif
                                                        </div>

                                                        @if(!empty($stayDetails['nights_breakdown']))
                                                            <div class="mt-2">
                                                                <a class="btn btn-xs btn-outline-info py-0 px-2 rounded-pill small" data-bs-toggle="collapse" href="#stayNightsCollapse" role="button" aria-expanded="false" style="font-size: 0.75rem;">
                                                                    <i class="fas fa-table-list me-1"></i>عرض تفكيك استقطاع الليالي ({{ count($stayDetails['nights_breakdown']) }})
                                                                </a>
                                                                <div class="collapse mt-2" id="stayNightsCollapse">
                                                                    <div class="card card-body p-2 bg-light border-0 small">
                                                                        <div class="table-responsive">
                                                                            <table class="table table-sm table-bordered bg-white mb-0 text-center align-middle" style="font-size: 0.72rem;">
                                                                                <thead class="table-light">
                                                                                    <tr>
                                                                                        <th>الليلة</th>
                                                                                        <th>الشريحة</th>
                                                                                        <th>نسبة التحمل</th>
                                                                                        <th>سعر العادية</th>
                                                                                        <th>فرق الترقية</th>
                                                                                        <th>تحمل المريض</th>
                                                                                        <th>مطالبة الضمان</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    @foreach($stayDetails['nights_breakdown'] as $nb)
                                                                                        <tr>
                                                                                            <td class="fw-bold">{{ $nb['night_number'] }}</td>
                                                                                            <td>{{ $nb['tier_name'] }}</td>
                                                                                            <td><span class="badge bg-secondary">{{ $nb['copay_percentage'] }}%</span></td>
                                                                                            <td>{{ number_format($nb['standard_price'], 0) }}</td>
                                                                                            <td class="{{ $nb['upgrade_diff'] > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                                                                                {{ $nb['upgrade_diff'] > 0 ? '+'.number_format($nb['upgrade_diff'], 0) : '0' }}
                                                                                            </td>
                                                                                            <td class="text-success fw-bold">{{ number_format($nb['patient_share'], 0) }}</td>
                                                                                            <td class="text-primary fw-bold">{{ number_format($nb['insurance_share'], 0) }}</td>
                                                                                        </tr>
                                                                                    @endforeach
                                                                                </tbody>
                                                                                <tfoot class="table-secondary fw-bold">
                                                                                    <tr>
                                                                                        <td colspan="5" class="text-end">المجموع الكلي للإقامة:</td>
                                                                                        <td class="text-success">{{ number_format($stayDetails['patient_stay_share'], 0) }} د.ع</td>
                                                                                        <td class="text-primary">{{ number_format($stayDetails['insurance_stay_share'], 0) }} د.ع</td>
                                                                                    </tr>
                                                                                </tfoot>
                                                                            </table>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end fw-bold">
                                                {{ number_format($roomFee, 0) }} د.ع
                                            </td>
                                            <td class="text-end fw-bold text-success fs-6">
                                                <strong id="room_fee_display">{{ number_format($remainingRoomFee, 0) }}</strong> د.ع
                                                <input type="hidden" id="room_fee_value" value="{{ $remainingRoomFee }}">
                                            </td>
                                            <td class="text-end text-muted fw-bold" id="room_insurance_display">
                                                {{ number_format($isStayIns ? $stayInsuranceShare : 0, 0) }} د.ع
                                            </td>
                                            <td class="text-center">
                                                <div class="segmented-control-pills" style="max-width: 170px; margin: 0 auto;">
                                                    <input type="radio" class="segmented-pill-input room-inclusion-radio inclusion-pill-radio" 
                                                           name="pkg_radio_room" 
                                                           id="rad_inc_room" 
                                                           value="1" 
                                                           {{ ($remainingRoomFee == 0 || $isStayIns) ? 'checked' : '' }} autocomplete="off">
                                                    <label class="segmented-pill-label pill-included" for="rad_inc_room" title="مشمول ضمن باقة وتكلفة العملية (0 د.ع)">
                                                        <i class="fas fa-box-open"></i>
                                                        <span>مشمول</span>
                                                    </label>

                                                    <input type="radio" class="segmented-pill-input room-inclusion-radio inclusion-pill-radio" 
                                                           name="pkg_radio_room" 
                                                           id="rad_sep_room" 
                                                           value="0" 
                                                           {{ ($remainingRoomFee > 0 && !$isStayIns) ? 'checked' : '' }} autocomplete="off">
                                                    <label class="segmented-pill-label pill-unbundled" for="rad_sep_room" title="غير مشمول - تسديد أجور الإقامة منفصلة">
                                                        <i class="fas fa-coins"></i>
                                                        <span>منفصل</span>
                                                    </label>
                                                    <input type="checkbox" class="package-inclusion-switch d-none" 
                                                           name="package_included[room]" 
                                                           id="pkg_incl_room" 
                                                           value="1" 
                                                           {{ ($remainingRoomFee == 0 || $isStayIns) ? 'checked' : '' }}>
                                                </div>
                                                <input type="hidden" name="room_payment_type" value="full">
                                            </td>
                                        </tr>
                                        @endif

                                        <!-- التحاليل المعلقة -->
                                        @if($pendingLabTests->count() > 0)
                                            <tr class="table-info">
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input" id="selectAllLab">
                                                </td>
                                                <td colspan="5">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <strong>
                                                            <i class="fas fa-flask me-2 text-primary"></i>
                                                            التحاليل المخبرية للعملية ({{ $pendingLabTests->count() }})
                                                        </strong>
                                                        <span class="badge bg-white text-info border small">
                                                            <i class="fas fa-info-circle me-1"></i>يمكن شمولها ضمن باقة العملية (0 د.ع) أو تسديدها منفصلة
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @foreach($pendingLabTests as $labTest)
                                                @php
                                                    $lModel = $labTest->labTest;
                                                    $lBase = (float)($lModel->price ?? 0);
                                                    $lHi = (float)(($lModel && $lModel->hi_price > 0) ? $lModel->hi_price : $lBase);
                                                    $lMoi = (float)(($lModel && $lModel->moi_price > 0) ? $lModel->moi_price : $lBase);
                                                    $lApproved = match($defaultInsurance) {
                                                        'hi' => $lHi,
                                                        'moi' => $lMoi,
                                                        default => $lBase
                                                    };
                                                @endphp
                                                <tr class="align-middle item-row" data-item-group="lab" data-item-id="{{ $labTest->id }}">
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input payment-item lab-test-item" 
                                                               name="pay_lab_tests[]" 
                                                               value="{{ $labTest->id }}"
                                                               data-base-price="{{ $lBase }}"
                                                               data-moi-price="{{ $lMoi }}"
                                                               data-hi-price="{{ $lHi }}"
                                                               data-is-moi-active="{{ ($lModel?->is_moi_active ?? true) ? '1' : '0' }}"
                                                               data-is-hi-active="{{ ($lModel?->is_hi_active ?? true) ? '1' : '0' }}"
                                                               data-item-type="lab"
                                                               checked>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fas fa-vial text-primary me-1"></i>
                                                            <div>
                                                                <strong class="text-dark">{{ $lModel->name ?? 'غير محدد' }}</strong> 
                                                                <small class="text-muted">({{ $lModel->code ?? '-' }})</small>
                                                                <div class="package-badge-container mt-1">
                                                                    @if(!$isInsurance)
                                                                        <span class="package-status-badge badge bg-warning-subtle text-danger border border-danger border-opacity-25 rounded-pill px-2 py-0-5 d-inline-flex align-items-center gap-1">
                                                                            <i class="fas fa-cut"></i> استقطاع من حصة الجراح: <strong>{{ number_format($lBase, 0) }}</strong> د.ع
                                                                        </span>
                                                                    @else
                                                                        <span class="package-status-badge badge bg-info-subtle text-info border border-info border-opacity-25 rounded-pill px-2 py-0-5 d-inline-flex align-items-center gap-1">
                                                                            <i class="fas fa-shield-alt"></i> مشمول بعقد الضمان
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold row-approved-price">
                                                        {{ number_format($lApproved, 0) }} د.ع
                                                    </td>
                                                    <td class="text-end fw-bold text-success row-patient-share item-price-display">
                                                        0 د.ع
                                                    </td>
                                                    <td class="text-end fw-bold text-primary row-insurance-share">
                                                        0 د.ع
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex align-items-center justify-content-center gap-1.5">
                                                            <div class="segmented-control-pills" style="max-width: 170px; margin: 0 auto;">
                                                                <input type="radio" class="segmented-pill-input inclusion-pill-radio" 
                                                                       name="pkg_radio_lab_{{ $labTest->id }}" 
                                                                       id="rad_inc_lab_{{ $labTest->id }}" 
                                                                       value="1" checked autocomplete="off">
                                                                <label class="segmented-pill-label pill-included" for="rad_inc_lab_{{ $labTest->id }}" title="مشمول ضمن باقة العملية (0 د.ع)">
                                                                    <i class="fas fa-box-open"></i>
                                                                    <span>مشمول</span>
                                                                </label>

                                                                <input type="radio" class="segmented-pill-input inclusion-pill-radio" 
                                                                       name="pkg_radio_lab_{{ $labTest->id }}" 
                                                                       id="rad_sep_lab_{{ $labTest->id }}" 
                                                                       value="0" autocomplete="off">
                                                                <label class="segmented-pill-label pill-unbundled" for="rad_sep_lab_{{ $labTest->id }}" title="غير مشمول - منفصل">
                                                                    <i class="fas fa-coins"></i>
                                                                    <span>منفصل</span>
                                                                </label>
                                                                <input type="checkbox" class="package-inclusion-switch d-none" 
                                                                       name="package_included[lab][{{ $labTest->id }}]" 
                                                                       id="pkg_incl_lab_{{ $labTest->id }}" 
                                                                       value="1" checked>
                                                            </div>
                                                            <button type="button" class="btn btn-outline-danger btn-sm p-1 rounded-circle delete-surgery-item-btn d-inline-flex align-items-center justify-content-center" 
                                                                    style="width: 28px; height: 28px; flex-shrink: 0;"
                                                                    data-url="{{ route('cashier.surgeries.lab-tests.remove', [$surgery, $labTest]) }}"
                                                                    data-name="{{ $lModel->name ?? 'تحليل' }}"
                                                                    title="حذف هذا التحليل من العملية">
                                                                <i class="fas fa-trash-alt small"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <td></td>
                                                <td colspan="2" class="text-end"><strong>مجموع التحاليل المعلقة:</strong></td>
                                                <td class="text-end"><strong id="pending_lab_fee_display" class="text-success">0 د.ع</strong></td>
                                                <td colspan="2"></td>
                                            </tr>
                                        @endif

                                        <!-- الأشعة المعلقة -->
                                        @if($pendingRadiologyTests->count() > 0)
                                            <tr class="table-warning">
                                                <td class="text-center">
                                                    <input type="checkbox" 
                                                           class="form-check-input" 
                                                           id="selectAllRadiology">
                                                </td>
                                                <td colspan="5">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <strong>
                                                            <i class="fas fa-x-ray me-2 text-info"></i>
                                                            الفحوصات الإشعاعية المعلقة ({{ $pendingRadiologyTests->count() }})
                                                        </strong>
                                                        <span class="badge bg-white text-secondary border small">
                                                            <i class="fas fa-info-circle me-1"></i>يمكن جعلها مشمولة بالعملية أو منفصلة
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @foreach($pendingRadiologyTests as $radiologyTest)
                                                @php
                                                    $rModel = $radiologyTest->radiologyType;
                                                    $rBase = (float)($rModel ? $rModel->getRegularPrice() : 0);
                                                    $rMoi = (float)($rModel && $rModel->moi_price > 0 ? $rModel->moi_price : $rBase);
                                                    $rHi = (float)($rModel && $rModel->hi_price > 0 ? $rModel->hi_price : $rBase);
                                                    $rMoiActive = (bool)($rModel ? ($rModel->is_moi_active ?? true) : true);
                                                    $rHiActive = (bool)($rModel ? ($rModel->is_hi_active ?? true) : true);
                                                    $rApproved = match($defaultInsurance) {
                                                        'hi' => $rHi,
                                                        'moi' => $rMoi,
                                                        default => $rBase
                                                    };
                                                @endphp
                                                <tr class="align-middle item-row" data-item-group="radiology" data-item-id="{{ $radiologyTest->id }}">
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input payment-item radiology-test-item" 
                                                               name="pay_radiology_tests[]" 
                                                               value="{{ $radiologyTest->id }}"
                                                               data-base-price="{{ $rBase }}"
                                                               data-moi-price="{{ $rMoi }}"
                                                               data-hi-price="{{ $rHi }}"
                                                               data-is-moi-active="{{ ($rModel?->is_moi_active ?? true) ? '1' : '0' }}"
                                                               data-is-hi-active="{{ ($rModel?->is_hi_active ?? true) ? '1' : '0' }}"
                                                               data-item-type="radiology"
                                                               checked>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="fas fa-radiation text-info me-1"></i>
                                                            <div>
                                                                <strong class="text-dark">{{ $rModel->name ?? 'غير محدد' }}</strong>
                                                                <div class="package-badge-container mt-1">
                                                                    <span class="package-status-badge badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-0-5">
                                                                        <i class="fas fa-coins me-1"></i>منفصل عن العملية
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold row-approved-price">
                                                        {{ number_format($rApproved, 0) }} د.ع
                                                    </td>
                                                    <td class="text-end fw-bold text-success row-patient-share item-price-display">
                                                        0 د.ع
                                                    </td>
                                                    <td class="text-end fw-bold text-primary row-insurance-share">
                                                        0 د.ع
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex align-items-center justify-content-center gap-1.5">
                                                            <div class="segmented-control-pills" style="max-width: 170px; margin: 0 auto;">
                                                                <input type="radio" class="segmented-pill-input inclusion-pill-radio" 
                                                                       name="pkg_radio_rad_{{ $radiologyTest->id }}" 
                                                                       id="rad_inc_rad_{{ $radiologyTest->id }}" 
                                                                       value="1" autocomplete="off">
                                                                <label class="segmented-pill-label pill-included" for="rad_inc_rad_{{ $radiologyTest->id }}" title="مشمول ضمن باقة العملية (0 د.ع)">
                                                                    <i class="fas fa-box-open"></i>
                                                                    <span>مشمول</span>
                                                                </label>

                                                                <input type="radio" class="segmented-pill-input inclusion-pill-radio" 
                                                                       name="pkg_radio_rad_{{ $radiologyTest->id }}" 
                                                                       id="rad_sep_rad_{{ $radiologyTest->id }}" 
                                                                       value="0" checked autocomplete="off">
                                                                <label class="segmented-pill-label pill-unbundled" for="rad_sep_rad_{{ $radiologyTest->id }}" title="غير مشمول - منفصل">
                                                                    <i class="fas fa-coins"></i>
                                                                    <span>منفصل</span>
                                                                </label>
                                                                <input type="checkbox" class="package-inclusion-switch d-none" 
                                                                       name="package_included[radiology][{{ $radiologyTest->id }}]" 
                                                                       id="pkg_incl_rad_{{ $radiologyTest->id }}" 
                                                                       value="1">
                                                            </div>
                                                            <button type="button" class="btn btn-outline-danger btn-sm p-1 rounded-circle delete-surgery-item-btn d-inline-flex align-items-center justify-content-center" 
                                                                    style="width: 28px; height: 28px; flex-shrink: 0;"
                                                                    data-url="{{ route('cashier.surgeries.radiology-tests.remove', [$surgery, $radiologyTest]) }}"
                                                                    data-name="{{ $rModel->name ?? 'فحص أشعة' }}"
                                                                    title="حذف هذا الفحص من العملية">
                                                                <i class="fas fa-trash-alt small"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <td></td>
                                                <td colspan="2" class="text-end"><strong>مجموع الأشعة المعلقة:</strong></td>
                                                <td class="text-end"><strong id="pending_radiology_fee_display">{{ number_format($pendingRadiologyFee, 0) }}</strong></td>
                                                <td colspan="2"></td>
                                            </tr>
                                        @endif

                                        <!-- الخدمات الإضافية والفندقية المعلقة -->
                                        @if($pendingExtraServices->count() > 0)
                                            <tr class="table-warning">
                                                <td class="text-center">
                                                    <input type="checkbox" 
                                                           class="form-check-input" 
                                                           id="selectAllExtraServices" checked>
                                                </td>
                                                <td colspan="5">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <strong>
                                                            <i class="fas fa-concierge-bell me-2 text-warning"></i>
                                                            الخدمات الإضافية والفندقية المعلقة ({{ $pendingExtraServices->count() }})
                                                        </strong>
                                                        <span class="badge bg-white text-dark border small">
                                                            <i class="fas fa-heartbeat text-danger me-1"></i>تخطيط القلب والخدمات قابلة للشمول بالاتفاق
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @foreach($pendingExtraServices as $extraService)
                                                @php
                                                    $eTotal = (float)$extraService->total_amount;
                                                    $isEcgOrMedical = ($extraService->service?->category === 'medical')
                                                        || str_contains($extraService->name, 'تخطيط')
                                                        || str_contains(strtolower($extraService->name), 'ecg');
                                                @endphp
                                                <tr class="align-middle item-row" data-item-group="extra" data-item-id="{{ $extraService->id }}">
                                                    <td class="text-center">
                                                        <input type="checkbox" 
                                                               class="form-check-input payment-item extra-service-item" 
                                                               name="pay_extra_services[]" 
                                                               value="{{ $extraService->id }}"
                                                               data-base-price="{{ $eTotal }}"
                                                               data-moi-price="{{ $eTotal }}"
                                                               data-hi-price="{{ $eTotal }}"
                                                               data-is-medical="{{ $isEcgOrMedical ? '1' : '0' }}"
                                                               data-item-type="extra"
                                                               checked>
                                                    </td>
                                                    <td class="ps-3">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="rounded-circle p-2 {{ $isEcgOrMedical ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning' }} d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                                <i class="fas {{ $isEcgOrMedical ? 'fa-heartbeat' : 'fa-concierge-bell' }} small"></i>
                                                            </div>
                                                            <div>
                                                                <span class="fw-bold text-dark">{{ $extraService->name }}</span>
                                                                <div class="small text-muted mt-1">
                                                                    <span class="badge bg-light text-secondary border me-1">{{ $isEcgOrMedical ? 'فحص سريري / تخطيط' : 'خدمة إضافية' }}</span>
                                                                    @if($extraService->quantity > 1)
                                                                        <span class="badge bg-secondary-subtle text-secondary me-1">×{{ $extraService->quantity }}</span>
                                                                    @endif
                                                                    @if($extraService->notes)
                                                                        <span><i class="fas fa-info-circle me-1"></i>{{ $extraService->notes }}</span>
                                                                    @endif
                                                                </div>
                                                                <div class="package-badge-container mt-1">
                                                                    @if($isEcgOrMedical)
                                                                        @if(!$isInsurance)
                                                                            <span class="package-status-badge badge bg-warning-subtle text-danger border border-danger border-opacity-25 rounded-pill px-2 py-0-5 d-inline-flex align-items-center gap-1">
                                                                                <i class="fas fa-cut"></i> استقطاع من حصة الجراح: <strong>{{ number_format($eTotal, 0) }}</strong> د.ع
                                                                            </span>
                                                                        @else
                                                                            <span class="package-status-badge badge bg-info-subtle text-info border border-info border-opacity-25 rounded-pill px-2 py-0-5 d-inline-flex align-items-center gap-1">
                                                                                <i class="fas fa-shield-alt"></i> مشمول بعقد الضمان
                                                                            </span>
                                                                        @endif
                                                                    @else
                                                                        <span class="package-status-badge badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-0-5">
                                                                            <i class="fas fa-coins me-1"></i>منفصل عن العملية
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end">
                                                        <div class="d-inline-flex align-items-center justify-content-end gap-1">
                                                            <input type="number" 
                                                                   class="form-control form-control-sm text-end fw-bold extra-service-price-input" 
                                                                   name="extra_service_prices[{{ $extraService->id }}]" 
                                                                   value="{{ (int)$eTotal }}" 
                                                                   min="0" 
                                                                   step="500" 
                                                                   data-id="{{ $extraService->id }}"
                                                                   data-url="{{ route('cashier.surgeries.extra-services.update', [$surgery, $extraService]) }}"
                                                                   style="width: 110px;"
                                                                   title="سعر الخدمة (يمكن تعديله)">
                                                            <span class="small text-muted">د.ع</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-end fw-bold text-success fs-6 row-patient-share">
                                                        {{ number_format($isEcgOrMedical ? 0 : $eTotal, 0) }} د.ع
                                                    </td>
                                                    <td class="text-end text-muted fw-bold row-insurance-share">
                                                        0 د.ع
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                                            <div class="segmented-control-pills" style="max-width: 160px;">
                                                                <input type="radio" class="segmented-pill-input inclusion-pill-radio" 
                                                                       name="pkg_radio_extra_{{ $extraService->id }}" 
                                                                       id="extra_inc_{{ $extraService->id }}" 
                                                                       value="1" 
                                                                       {{ $isEcgOrMedical ? 'checked' : '' }} autocomplete="off">
                                                                <label class="segmented-pill-label pill-included" for="extra_inc_{{ $extraService->id }}" title="مشمول ضمن باقة العملية (0 د.ع)">
                                                                    <i class="fas fa-box-open"></i>
                                                                    <span>مشمول</span>
                                                                </label>

                                                                <input type="radio" class="segmented-pill-input inclusion-pill-radio" 
                                                                       name="pkg_radio_extra_{{ $extraService->id }}" 
                                                                       id="extra_sep_{{ $extraService->id }}" 
                                                                       value="0" 
                                                                       {{ !$isEcgOrMedical ? 'checked' : '' }} autocomplete="off">
                                                                <label class="segmented-pill-label pill-unbundled" for="extra_sep_{{ $extraService->id }}" title="غير مشمول - منفصل">
                                                                    <i class="fas fa-coins"></i>
                                                                    <span>منفصل</span>
                                                                </label>
                                                                
                                                                <input type="checkbox" class="package-inclusion-switch d-none" 
                                                                       name="package_included[extra][{{ $extraService->id }}]" 
                                                                       id="pkg_incl_extra_{{ $extraService->id }}" 
                                                                       value="1" 
                                                                       {{ $isEcgOrMedical ? 'checked' : '' }}>
                                                            </div>
                                                            <button type="button" class="btn btn-outline-danger btn-sm p-1 rounded-circle delete-extra-service-btn d-inline-flex align-items-center justify-content-center" 
                                                                    style="width: 28px; height: 28px;"
                                                                    data-url="{{ route('cashier.surgeries.extra-services.remove', [$surgery, $extraService]) }}"
                                                                    data-name="{{ $extraService->name }}"
                                                                    title="حذف الخدمة المعلقة">
                                                                <i class="fas fa-trash-alt small"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            <tr class="table-light">
                                                <td></td>
                                                <td colspan="2" class="text-end"><strong>مجموع الخدمات الإضافية:</strong></td>
                                                <td class="text-end"><strong id="pending_extra_services_fee_display">{{ number_format($pendingExtraFee, 0) }} د.ع</strong></td>
                                                <td colspan="2"></td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    <tfoot>
                                        <!-- المجموع الإجمالي المعتمد والمحدد -->
                                        <tr class="table-light border-top border-2">
                                            <td class="text-center"><i class="fas fa-calculator text-muted"></i></td>
                                            <td class="fw-bold fs-6">إجمالي البنود والخدمات المعتمدة:</td>
                                            <td class="text-end fw-bold text-dark fs-6" id="table_total_approved">{{ number_format($pendingAmount, 0) }} د.ع</td>
                                            <td class="text-end fw-bold text-success fs-5" id="selectedAmount">{{ number_format($pendingAmount, 0) }} IQD</td>
                                            <td class="text-end fw-bold text-primary fs-6" id="table_total_insurance">0 د.ع</td>
                                            <td class="text-center">
                                                <span class="badge bg-success-subtle text-success border border-success px-2 py-1" id="table_actions_summary">
                                                    <i class="fas fa-check-circle me-1"></i>جاهز للدفع
                                                </span>
                                            </td>
                                        </tr>
                                        @if(!$isInsurance)
                                            <!-- إجمالي استقطاعات الباقة المخصومة من حصة الجراح لصالح المستشفى -->
                                            <tr class="table-warning bg-opacity-10 border-top" id="doctorDeductionsRow">
                                                <td class="text-center"><i class="fas fa-cut text-danger"></i></td>
                                                <td class="fw-bold text-danger">إجمالي استقطاعات الباقة المخصومة من حصة الجراح:</td>
                                                <td class="text-end fw-bold text-danger fs-6" id="totalDoctorDeductionsDisplay">0 د.ع</td>
                                                <td colspan="3" class="small text-muted align-middle">
                                                    <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1">
                                                        <i class="fas fa-info-circle me-1"></i> تُقيد كإيراد مستشفى وتُخصم من صافي أتعاب الجراح في كشوفات الحسابات
                                                    </span>
                                                </td>
                                            </tr>
                                        @endif
                                        <!-- المبلغ المؤجل إن وجد -->
                                        <tr class="table-warning" id="deferredRow" style="display: none;">
                                            <td></td>
                                            <td colspan="2" class="text-end fw-bold text-warning">
                                                <i class="fas fa-clock me-1"></i> سيبقى معلقاً (مؤجل للدفع لاحقاً):
                                            </td>
                                            <td class="text-end fw-bold text-warning fs-5" id="deferredAmount">0 IQD</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- حقل المبلغ المخفي -->
                        <input type="hidden" name="amount" id="amountInput" value="{{ $pendingAmount }}">
                        <input type="hidden" name="total_amount" value="{{ $totalAmount }}">

                        <!-- بطاقة اختيار نمط السداد الإجمالي: دفع كامل أو دفع جزئي من إجمالي الفاتورة -->
                        <div class="card border rounded-4 p-3 mb-4 shadow-2xs bg-white" id="global_payment_split_card">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-hand-holding-usd text-primary fs-5"></i>
                                    <label class="form-label fw-bolder mb-0 text-dark fs-6">نمط تسديد الفاتورة الإجمالية:</label>
                                </div>
                                <span class="badge bg-light text-dark border px-2.5 py-1.5 small fw-bold">
                                    صافي المطلوب قبضه: <strong class="text-success fs-6" id="grand_patient_total_display">{{ number_format($pendingAmount, 0) }} د.ع</strong>
                                </span>
                            </div>

                            <div class="row g-3 align-items-center">
                                <div class="col-md-6">
                                    <div class="segmented-control-pills w-100" style="height: 42px;">
                                        <input type="radio" class="segmented-pill-input global-payment-mode" name="payment_mode" id="pay_mode_full" value="full" checked autocomplete="off">
                                        <label class="segmented-pill-label pill-included fs-6 fw-bold" for="pay_mode_full">
                                            <i class="fas fa-check-circle me-1"></i>
                                            دفع كامل للمبلغ المعتمد (<span id="btn_full_amount_label">{{ number_format($pendingAmount, 0) }}</span> د.ع)
                                        </label>

                                        <input type="radio" class="segmented-pill-input global-payment-mode" name="payment_mode" id="pay_mode_partial" value="partial" autocomplete="off">
                                        <label class="segmented-pill-label pill-unbundled fs-6 fw-bold" for="pay_mode_partial">
                                            <i class="fas fa-coins me-1"></i>
                                            دفع جزئي (دفعة مقبوضة الآن)
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6" id="partial_payment_amount_container" style="display: none;">
                                    <div class="p-2.5 rounded-3 border bg-warning-subtle d-flex align-items-center justify-content-between gap-3">
                                        <div class="flex-grow-1">
                                            <label class="form-label small fw-bold text-dark mb-1">المبلغ المقبوض فعلياً الآن (IQD) *</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white text-success fw-bold">IQD</span>
                                                <input type="number" class="form-control form-control-sm fw-bolder text-success fs-6" 
                                                       id="custom_paid_amount" name="custom_paid_amount" min="1" step="1000" placeholder="0">
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block fw-bold" style="font-size: 0.72rem;">المتبقي كدين / مؤجل</small>
                                            <span class="badge bg-danger fs-6" id="partial_remaining_badge">0 د.ع</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-money-bill-wave me-1 text-success"></i>
                                        طريقة الدفع <span class="text-danger">*</span>
                                    </label>
                                    <div class="payment-methods-group">
                                        <div class="form-check form-check-lg mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="surgery_payment_cash" 
                                                   value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'checked' : '' }} 
                                                   onchange="if(typeof updateSplitUI === 'function') updateSplitUI();" required>
                                            <label class="form-check-label fw-semibold" for="surgery_payment_cash" style="cursor: pointer;">
                                                💵 نقدي (Cash)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-lg mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="surgery_payment_card" 
                                                   value="card" {{ old('payment_method') == 'card' ? 'checked' : '' }}
                                                   onchange="if(typeof updateSplitUI === 'function') updateSplitUI();">
                                            <label class="form-check-label fw-semibold" for="surgery_payment_card" style="cursor: pointer;">
                                                💳 بطاقة ائتمان (Card)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-lg mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="surgery_payment_split" 
                                                   value="split" {{ old('payment_method') == 'split' ? 'checked' : '' }}
                                                   onchange="if(typeof updateSplitUI === 'function') updateSplitUI();">
                                            <label class="form-check-label fw-semibold text-primary" for="surgery_payment_split" style="cursor: pointer;">
                                                🔄 دفع مركب / مختلط (مقسم كاش + بطاقة)
                                            </label>
                                        </div>
                                        <div class="form-check form-check-lg">
                                            <input class="form-check-input" type="radio" name="payment_method" id="surgery_payment_insurance" 
                                                   value="insurance" {{ old('payment_method') == 'insurance' ? 'checked' : '' }}
                                                   onchange="if(typeof updateSplitUI === 'function') updateSplitUI();">
                                            <label class="form-check-label fw-semibold" for="surgery_payment_insurance" style="cursor: pointer;">
                                                🏥 تأمين صحي (Insurance)
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <!-- صندوق الدفع المركب (كاش + بطاقة) -->
                                    <div id="split_payment_box" class="p-3 mt-3 rounded-3 border border-primary bg-primary-subtle" style="display: none;">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="fw-bold text-primary">
                                                <i class="fas fa-coins me-1"></i> تفصيل الدفع المركب:
                                            </span>
                                            <span id="split_status_badge" class="badge bg-secondary">0 / 0 IQD</span>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="form-label small fw-bold mb-1">
                                                    💵 المبلغ نقداً (Cash)
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="any" min="0" class="form-control fw-bold" id="cash_amount" name="cash_amount" value="{{ old('cash_amount', 0) }}" placeholder="0">
                                                    <span class="input-group-text">IQD</span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-bold mb-1">
                                                    💳 المبلغ بالبطاقة (Card)
                                                </label>
                                                <div class="input-group input-group-sm">
                                                    <input type="number" step="any" min="0" class="form-control fw-bold" id="card_amount" name="card_amount" value="{{ old('card_amount', 0) }}" placeholder="0">
                                                    <span class="input-group-text">IQD</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2 small">
                                            <span id="split_helper_text" class="text-muted">أدخل جزءاً بالكاش وسيتم إكمال الباقي بالبطاقة آلياً.</span>
                                            <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" id="split_equal_btn" style="font-size: 0.75rem;">
                                                مناصفة (50/50)
                                            </button>
                                        </div>
                                    </div>
                                    @error('payment_method')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        ملاحظات
                                    </label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                              id="notes" 
                                              name="notes" 
                                              rows="2" 
                                              placeholder="أي ملاحظات إضافية...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>
                                        العودة
                                    </a>
                                    <div>
                                        <span class="me-3 text-muted" id="itemsCount">
                                            <i class="fas fa-check-circle text-success me-1"></i>
                                            تم تحديد <strong id="selectedItemsCount">0</strong> عنصر للدفع
                                        </span>
                                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn">
                                            <i class="fas fa-money-bill-wave me-2"></i>
                                            تأكيد الدفع
                                            <span id="submitAmount" class="ms-2">({{ number_format($pendingAmount, 0) }} IQD)</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    @elseif($totalExcess <= 0 && !$hasPendingItems)
                    <!-- لا توجد عناصر معلقة -->
                    <div class="alert alert-success text-center">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h5>تم دفع جميع رسوم هذه العملية!</h5>
                        <p class="mb-0">لا توجد مبالغ معلقة للدفع أو مسترجعات مالية.</p>
                        <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-primary mt-3">
                            <i class="fas fa-arrow-left me-1"></i>
                            العودة لقائمة العمليات
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($pendingAmount > 0 || $hasPendingItems)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const pendingAmount = {{ $pendingAmount }};
    const remainingSurgeryFee = {{ $remainingSurgeryFee }};
    const remainingRoomFee = {{ $remainingRoomFee }};
    const remainingRoomFeeSelfPay = {{ max(0, $roomFee - $roomFeePaidAmount) }};
    const remainingRoomFeeInsurance = {{ max(0, $stayPatientShare - $roomFeePaidAmount) }};
    const roomInsuranceShareVal = {{ $stayInsuranceShare }};
    const isInsuranceStayCalculated = {{ $isStayIns ? 'true' : 'false' }};
    const inclusiveCheckbox = document.getElementById('inclusive');

    // عناصر الضمان
    const insuranceTypeSelect = document.getElementById('insurance_type');
    const insuranceCardCol = document.getElementById('insurance_card_col');
    const copaySection = document.getElementById('copay_section');
    const copayBadge = document.getElementById('copayBadge');
    const copayInput = document.getElementById('copay_percentage');
    const paymentInsuranceRadio = document.getElementById('surgery_payment_insurance');
    const paymentCashRadio = document.getElementById('surgery_payment_cash');

    const hiCategoryCopay = @json($patient ? (float)$patient->getCopayPercentageFor('surgery') : 15.0);
    const hiCopays = {
        surgery: @json($patient ? (float)$patient->getCopayPercentageFor('surgery') : 15.0),
        lab: @json($patient ? (float)$patient->getCopayPercentageFor('lab') : 15.0),
        radiology: @json($patient ? (float)$patient->getCopayPercentageFor('radiology') : 15.0),
        ecg: @json($patient ? (float)$patient->getCopayPercentageFor('ecg') : 10.0),
    };
    const moiCopay = @json((float)($patient->copay_percentage ?? 15.0));
    const hiBadge = document.getElementById('patient_hi_category_badge');

    function numberFormat(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // --- عناصر ومنطق الدفع المركب (كاش + بطاقة) ---
    const splitPaymentBox = document.getElementById('split_payment_box');
    const cashAmountInput = document.getElementById('cash_amount');
    const cardAmountInput = document.getElementById('card_amount');
    const splitStatusBadge = document.getElementById('split_status_badge');
    const splitEqualBtn = document.getElementById('split_equal_btn');
    const surgeryPaymentSplitRadio = document.getElementById('surgery_payment_split');
    const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');

    function isSplitActive() {
        return surgeryPaymentSplitRadio && surgeryPaymentSplitRadio.checked;
    }

    function updateSplitUI() {
        if (!splitPaymentBox) return;

        const inputAmount = document.getElementById('amountInput');
        const currentTarget = parseFloat(inputAmount ? inputAmount.value : 0) || 0;

        if (isSplitActive()) {
            splitPaymentBox.style.display = 'block';

            let cashVal = parseFloat(cashAmountInput ? cashAmountInput.value : 0) || 0;
            let cardVal = parseFloat(cardAmountInput ? cardAmountInput.value : 0) || 0;

            if (cashVal === 0 && cardVal === 0 && currentTarget > 0) {
                cashVal = currentTarget;
                if (cashAmountInput) cashAmountInput.value = currentTarget;
                if (cardAmountInput) cardAmountInput.value = 0;
            }

            const totalSplit = Math.round(cashVal + cardVal);
            const targetRounded = Math.round(currentTarget);

            if (splitStatusBadge) {
                if (totalSplit === targetRounded && targetRounded > 0) {
                    splitStatusBadge.className = 'badge bg-success';
                    splitStatusBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>متطابق: ' + numberFormat(totalSplit) + ' IQD';
                } else {
                    splitStatusBadge.className = 'badge bg-danger';
                    splitStatusBadge.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>المجموع: ' + numberFormat(totalSplit) + ' / المطلوب: ' + numberFormat(targetRounded) + ' IQD';
                }
            }
        } else {
            splitPaymentBox.style.display = 'none';
        }
    }

    function syncSplitAmounts(source) {
        const inputAmount = document.getElementById('amountInput');
        const currentTarget = parseFloat(inputAmount ? inputAmount.value : 0) || 0;
        let cashVal = parseFloat(cashAmountInput ? cashAmountInput.value : 0) || 0;
        let cardVal = parseFloat(cardAmountInput ? cardAmountInput.value : 0) || 0;

        if (source === 'cash') {
            if (cashVal > currentTarget) {
                cashVal = currentTarget;
                if (cashAmountInput) cashAmountInput.value = cashVal;
            }
            cardVal = Math.max(0, currentTarget - cashVal);
            if (cardAmountInput) cardAmountInput.value = Math.round(cardVal);
        } else if (source === 'card') {
            if (cardVal > currentTarget) {
                cardVal = currentTarget;
                if (cardAmountInput) cardAmountInput.value = cardVal;
            }
            cashVal = Math.max(0, currentTarget - cardVal);
            if (cashAmountInput) cashAmountInput.value = Math.round(cashVal);
        }

        updateSplitUI();
    }

    window.updateSplitUI = updateSplitUI;
    window.syncSplitAmounts = syncSplitAmounts;

    function calculateSelectedAmount() {
        let totalApprovedPayable = 0;
        let totalPatientShare = 0;
        let totalInsuranceShare = 0;
        let selectedCount = 0;
        let unselectedDeferredAmount = 0;
        let totalDoctorDeductions = 0;

        const currentInsType = insuranceTypeSelect ? insuranceTypeSelect.value : 'none';
        const isIns = (currentInsType === 'hi' || currentInsType === 'moi');

        // Dynamic copays:
        let surgeryCopayPct = 100;
        let labCopayPct = 100;
        let radCopayPct = 100;
        let ecgCopayPct = 100;

        if (currentInsType === 'hi') {
            surgeryCopayPct = hiCopays.surgery;
            labCopayPct = hiCopays.lab;
            radCopayPct = hiCopays.radiology;
            ecgCopayPct = hiCopays.ecg;
        } else if (currentInsType === 'moi') {
            surgeryCopayPct = moiCopay;
            labCopayPct = moiCopay;
            radCopayPct = moiCopay;
            ecgCopayPct = moiCopay;
        }

        // 1. Surgery Fee portion
        const paySurgeryCheckbox = document.getElementById('pay_surgery_checkbox');
        let surgeryPaidThisTime = 0;
        if (paySurgeryCheckbox && paySurgeryCheckbox.checked) {
            surgeryPaidThisTime = remainingSurgeryFee;

            if (surgeryPaidThisTime > 0) {
                totalApprovedPayable += surgeryPaidThisTime;
                let sPatShare = surgeryPaidThisTime;
                let sInsShare = 0;
                if (isIns) {
                    sPatShare = Math.round(surgeryPaidThisTime * (surgeryCopayPct / 100));
                    sInsShare = Math.max(0, surgeryPaidThisTime - sPatShare);
                    totalPatientShare += sPatShare;
                    totalInsuranceShare += sInsShare;
                } else {
                    totalPatientShare += surgeryPaidThisTime;
                }
                const feeDisp = document.getElementById('surgery_fee_display');
                if (feeDisp) feeDisp.textContent = numberFormat(sPatShare);
                const insDisp = document.getElementById('surgery_insurance_display');
                if (insDisp) insDisp.textContent = numberFormat(sInsShare) + ' د.ع';
                selectedCount++;
            }
        } else if (paySurgeryCheckbox) {
            let sPatShare = remainingSurgeryFee;
            if (isIns) {
                sPatShare = Math.round(remainingSurgeryFee * (surgeryCopayPct / 100));
            }
            unselectedDeferredAmount += sPatShare;
            const feeDisp = document.getElementById('surgery_fee_display');
            const insDisp = document.getElementById('surgery_insurance_display');
            if (feeDisp) feeDisp.textContent = '0';
            if (insDisp) insDisp.textContent = '0 د.ع';
        }

        // 2. Room Fee portion
        const payRoomCheckbox = document.getElementById('pay_room_checkbox');
        let roomPaidThisTime = 0;
        const pkgSwitchRoom = document.getElementById('pkg_incl_room');
        const isRoomIncluded = pkgSwitchRoom ? pkgSwitchRoom.checked : false;
        const effectiveRemainingRoom = (isIns && isInsuranceStayCalculated)
            ? remainingRoomFeeInsurance
            : remainingRoomFeeSelfPay;

        const roomDeductionContainer = document.getElementById('room_package_badge_container');
        const roomFeeValue = parseFloat(document.getElementById('room_fee_value')?.value || {{ (float)$roomFee }});
        if (roomDeductionContainer) {
            if (isRoomIncluded) {
                if (!isIns) {
                    roomDeductionContainer.innerHTML = '<span class="package-status-badge badge bg-warning-subtle text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1"><i class="fas fa-cut"></i> استقطاع من حصة الجراح: <strong id="room_deduction_val">' + numberFormat(roomFeeValue) + ' د.ع</strong></span>';
                    if (payRoomCheckbox && payRoomCheckbox.checked) {
                        totalDoctorDeductions += roomFeeValue;
                    }
                } else {
                    roomDeductionContainer.innerHTML = '<span class="package-status-badge badge bg-info-subtle text-info border border-info border-opacity-25 rounded-pill px-2 py-1 d-inline-flex align-items-center gap-1"><i class="fas fa-shield-alt"></i> مشمول بعقد الضمان</span>';
                }
            } else {
                roomDeductionContainer.innerHTML = '<span class="package-status-badge badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1"><i class="fas fa-coins me-1"></i>تسديد أجور الإقامة منفصلة</span>';
            }
        }

        if (payRoomCheckbox && payRoomCheckbox.checked) {
            const feeDisp = document.getElementById('room_fee_display');
            const insDisp = document.getElementById('room_insurance_display');

            if (isRoomIncluded) {
                // مشمول ضمن باقة وتكلفة العملية (0 د.ع على المريض)
                let rInsShare = isIns ? roomInsuranceShareVal : 0;
                totalApprovedPayable += rInsShare;
                totalInsuranceShare += rInsShare;
                if (feeDisp) feeDisp.textContent = '0';
                if (insDisp) insDisp.textContent = numberFormat(rInsShare) + ' د.ع';
                selectedCount++;
            } else {
                // منفصل: يحسب بالكامل
                roomPaidThisTime = effectiveRemainingRoom;
                if (roomPaidThisTime > 0) {
                    let rPatShare = roomPaidThisTime;
                    let rInsShare = 0;
                    if (isIns) {
                        if (isInsuranceStayCalculated) {
                            rPatShare = roomPaidThisTime;
                            rInsShare = roomInsuranceShareVal;
                        } else {
                            rPatShare = Math.round(roomPaidThisTime * (surgeryCopayPct / 100));
                            rInsShare = Math.max(0, roomPaidThisTime - rPatShare);
                        }
                        totalApprovedPayable += (rPatShare + rInsShare);
                        totalPatientShare += rPatShare;
                        totalInsuranceShare += rInsShare;
                    } else {
                        totalApprovedPayable += roomPaidThisTime;
                        totalPatientShare += roomPaidThisTime;
                    }
                    if (feeDisp) feeDisp.textContent = numberFormat(rPatShare);
                    if (insDisp) insDisp.textContent = numberFormat(rInsShare) + ' د.ع';
                    selectedCount++;
                } else {
                    if (feeDisp) feeDisp.textContent = '0';
                    if (insDisp) insDisp.textContent = '0 د.ع';
                }
            }
        } else if (payRoomCheckbox) {
            if (!isRoomIncluded && effectiveRemainingRoom > 0) {
                let rPatShare = effectiveRemainingRoom;
                if (isIns && !isInsuranceStayCalculated) {
                    rPatShare = Math.round(effectiveRemainingRoom * (surgeryCopayPct / 100));
                }
                unselectedDeferredAmount += rPatShare;
            }
            const feeDisp = document.getElementById('room_fee_display');
            const insDisp = document.getElementById('room_insurance_display');
            if (feeDisp) feeDisp.textContent = '0';
            if (insDisp) insDisp.textContent = '0 د.ع';
        }

        // 3. Other items: Lab Tests, Radiology, Extra Services
        let currentLabTotal = 0;
        let currentRadTotal = 0;
        let currentExtraTotal = 0;

        document.querySelectorAll('.item-row').forEach(function(row) {
            const itemCheckbox = row.querySelector('.payment-item');
            const pkgSwitch = row.querySelector('.package-inclusion-switch');
            const statusBadge = row.querySelector('.package-status-badge');
            const priceCell = row.querySelector('.item-price-display');

            if (!itemCheckbox) return;

            const isCheckedForPay = itemCheckbox.checked;
            const isPkgIncluded = pkgSwitch ? pkgSwitch.checked : false;
            const itemType = itemCheckbox.dataset.itemType; // 'lab', 'radiology', 'extra'
            const priceInput = row.querySelector('.extra-service-price-input');
            let basePrice = parseFloat(itemCheckbox.dataset.basePrice) || 0;
            if (priceInput && !isNaN(parseFloat(priceInput.value))) {
                basePrice = Math.max(0, parseFloat(priceInput.value));
                itemCheckbox.dataset.basePrice = basePrice;
                itemCheckbox.dataset.moiPrice = basePrice;
                itemCheckbox.dataset.hiPrice = basePrice;
            }
            const moiPrice = parseFloat(itemCheckbox.dataset.moiPrice) || basePrice;
            const hiPrice = parseFloat(itemCheckbox.dataset.hiPrice) || basePrice;
            const isMedical = itemCheckbox.dataset.isMedical === '1';

            // Update badge UI
            const badgeContainer = row.querySelector('.package-badge-container');
            if (isPkgIncluded) {
                if (!isIns) {
                    if (badgeContainer) {
                        badgeContainer.innerHTML = '<span class="package-status-badge badge bg-warning-subtle text-danger border border-danger border-opacity-25 rounded-pill px-2 py-0-5 d-inline-flex align-items-center gap-1"><i class="fas fa-cut"></i> استقطاع من حصة الجراح: <strong>' + numberFormat(basePrice) + ' د.ع</strong></span>';
                    }
                    if (isCheckedForPay) {
                        totalDoctorDeductions += basePrice;
                    }
                } else {
                    if (badgeContainer) {
                        badgeContainer.innerHTML = '<span class="package-status-badge badge bg-info-subtle text-info border border-info border-opacity-25 rounded-pill px-2 py-0-5 d-inline-flex align-items-center gap-1"><i class="fas fa-shield-alt"></i> مشمول بعقد الضمان</span>';
                    }
                }
            } else {
                if (badgeContainer) {
                    badgeContainer.innerHTML = '<span class="package-status-badge badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-0-5"><i class="fas fa-coins me-1"></i>منفصل عن العملية</span>';
                }
            }

            // Calculate item financial figures
            let itemApproved = 0;
            let itemPatientShare = 0;
            let itemInsuranceShare = 0;

            if (isPkgIncluded) {
                // Included in surgery package -> 0 IQD impact to patient & insurance
                itemApproved = 0;
                itemPatientShare = 0;
                itemInsuranceShare = 0;

                if (priceCell) {
                    priceCell.innerHTML = '<span class="text-success fw-bold">0 د.ع</span> <small class="text-muted">(مشمول)</small>';
                }
                if (priceInput) {
                    priceInput.classList.add('bg-light', 'text-muted');
                }
            } else {
                if (priceInput) {
                    priceInput.classList.remove('bg-light', 'text-muted');
                }
                // Unbundled item:
                if (itemType === 'lab') {
                    if (currentInsType === 'hi') {
                        itemApproved = hiPrice > 0 ? hiPrice : basePrice;
                        itemPatientShare = Math.round(itemApproved * (labCopayPct / 100));
                        itemInsuranceShare = Math.max(0, itemApproved - itemPatientShare);
                    } else if (currentInsType === 'moi') {
                        itemApproved = moiPrice > 0 ? moiPrice : basePrice;
                        itemPatientShare = Math.round(itemApproved * (labCopayPct / 100));
                        itemInsuranceShare = Math.max(0, itemApproved - itemPatientShare);
                    } else {
                        itemApproved = basePrice;
                        itemPatientShare = basePrice;
                        itemInsuranceShare = 0;
                    }

                    if (priceCell) {
                        if (isIns) {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong><br><small class="text-muted">(مطالبة: ' + numberFormat(itemInsuranceShare) + ')</small>';
                        } else {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong>';
                        }
                    }
                } else if (itemType === 'radiology') {
                    if (currentInsType === 'hi') {
                        itemApproved = hiPrice > 0 ? hiPrice : basePrice;
                        itemPatientShare = Math.round(itemApproved * (radCopayPct / 100));
                        itemInsuranceShare = Math.max(0, itemApproved - itemPatientShare);
                    } else if (currentInsType === 'moi') {
                        itemApproved = moiPrice > 0 ? moiPrice : basePrice;
                        itemPatientShare = Math.round(itemApproved * (radCopayPct / 100));
                        itemInsuranceShare = Math.max(0, itemApproved - itemPatientShare);
                    } else {
                        itemApproved = basePrice;
                        itemPatientShare = basePrice;
                        itemInsuranceShare = 0;
                    }

                    if (priceCell) {
                        if (isIns) {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong><br><small class="text-muted">(مطالبة: ' + numberFormat(itemInsuranceShare) + ')</small>';
                        } else {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong>';
                        }
                    }
                } else if (itemType === 'extra') {
                    if (isIns && isMedical) {
                        itemApproved = basePrice;
                        itemPatientShare = Math.round(itemApproved * (ecgCopayPct / 100));
                        itemInsuranceShare = Math.max(0, itemApproved - itemPatientShare);
                    } else {
                        itemApproved = basePrice;
                        itemPatientShare = basePrice;
                        itemInsuranceShare = 0;
                    }

                    if (priceCell) {
                        if (isIns && isMedical) {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong><br><small class="text-muted">(تحمل ' + ecgCopayPct + '%)</small>';
                        } else {
                            priceCell.innerHTML = '<strong>' + numberFormat(itemPatientShare) + ' د.ع</strong>';
                        }
                    }
                }
            }

            const patShareCell = row.querySelector('.row-patient-share');
            const insShareCell = row.querySelector('.row-insurance-share');
            const approvedCell = row.querySelector('.row-approved-price');

            if (approvedCell && itemApproved > 0) {
                approvedCell.textContent = numberFormat(itemApproved) + ' د.ع';
            }

            if (patShareCell) {
                if (isPkgIncluded) {
                    patShareCell.innerHTML = '<span class="badge bg-success-subtle text-success border border-success">0 د.ع (مشمول)</span>';
                } else {
                    patShareCell.textContent = numberFormat(itemPatientShare) + ' د.ع';
                }
            }
            if (insShareCell) {
                insShareCell.textContent = numberFormat(itemInsuranceShare) + ' د.ع';
            }

            if (isCheckedForPay) {
                totalApprovedPayable += itemApproved;
                totalPatientShare += itemPatientShare;
                totalInsuranceShare += itemInsuranceShare;
                selectedCount++;

                if (itemType === 'lab') currentLabTotal += itemPatientShare;
                if (itemType === 'radiology') currentRadTotal += itemPatientShare;
                if (itemType === 'extra') currentExtraTotal += itemPatientShare;
            } else {
                if (!isPkgIncluded && itemPatientShare > 0) {
                    unselectedDeferredAmount += itemPatientShare;
                }
            }
        });

        const labTotalDisplay = document.getElementById('pending_lab_fee_display');
        if (labTotalDisplay) labTotalDisplay.textContent = numberFormat(currentLabTotal) + ' د.ع';
        const radTotalDisplay = document.getElementById('pending_radiology_fee_display');
        if (radTotalDisplay) radTotalDisplay.textContent = numberFormat(currentRadTotal) + ' د.ع';
        const extraTotalDisplay = document.getElementById('pending_extra_services_fee_display');
        if (extraTotalDisplay) extraTotalDisplay.textContent = numberFormat(currentExtraTotal) + ' د.ع';

        const doctorDeductionsDisplay = document.getElementById('totalDoctorDeductionsDisplay');
        if (doctorDeductionsDisplay) {
            doctorDeductionsDisplay.textContent = numberFormat(totalDoctorDeductions) + ' د.ع';
        }

        const doctorDeductionsRow = document.getElementById('doctorDeductionsRow');
        if (doctorDeductionsRow) {
            doctorDeductionsRow.style.display = (!isIns && totalDoctorDeductions > 0) ? '' : 'none';
        }

        // Discounts for cash
        const discountInput = document.getElementById('discount_percentage');
        const discountSection = document.getElementById('discount_section');
        const displayDiscountBadge = document.getElementById('display_discount_badge');
        const displayDiscountAmount = document.getElementById('display_discount_amount');
        const summaryDiscountNote = document.getElementById('summary_discount_note');
        const summaryDiscountVal = document.getElementById('summary_discount_val');

        let discountPct = 0;
        if (!isIns) {
            if (discountSection) discountSection.style.display = 'block';
            discountPct = parseFloat(discountInput ? discountInput.value : 0) || 0;
            if (discountPct < 0) discountPct = 0;
            if (discountPct > 100) discountPct = 100;
        } else {
            if (discountSection) discountSection.style.display = 'none';
            if (discountInput) discountInput.value = '0';
            discountPct = 0;
        }

        let discountAmount = 0;
        let netPatientShare = totalPatientShare;
        if (!isIns && discountPct > 0) {
            discountAmount = Math.round(totalPatientShare * (discountPct / 100));
            netPatientShare = Math.max(0, totalPatientShare - discountAmount);
        }

        if (displayDiscountBadge) displayDiscountBadge.textContent = discountPct + '%';
        if (displayDiscountAmount) displayDiscountAmount.textContent = 'قيمة الخصم: ' + numberFormat(discountAmount) + ' د.ع';
        if (summaryDiscountNote) {
            if (discountAmount > 0) {
                summaryDiscountNote.classList.remove('d-none');
                if (summaryDiscountVal) summaryDiscountVal.textContent = '-' + numberFormat(discountAmount) + ' د.ع';
            } else {
                summaryDiscountNote.classList.add('d-none');
            }
        }

        // Global Payment Mode: Full vs Partial
        const payModePartialRadio = document.getElementById('pay_mode_partial');
        const isPayModePartial = payModePartialRadio ? payModePartialRadio.checked : false;
        const partialContainer = document.getElementById('partial_payment_amount_container');
        const customPaidInput = document.getElementById('custom_paid_amount');
        const partialRemainingBadge = document.getElementById('partial_remaining_badge');
        const grandPatientTotalDisplay = document.getElementById('grand_patient_total_display');
        const btnFullAmountLabel = document.getElementById('btn_full_amount_label');

        if (grandPatientTotalDisplay) grandPatientTotalDisplay.textContent = numberFormat(netPatientShare) + ' د.ع';
        if (btnFullAmountLabel) btnFullAmountLabel.textContent = numberFormat(netPatientShare);

        let finalActualAmountToCollect = netPatientShare;
        let partialDeferredDebt = 0;

        if (isPayModePartial) {
            if (partialContainer) partialContainer.style.display = 'block';
            if (customPaidInput) customPaidInput.disabled = false;
            let enteredPaid = parseFloat(customPaidInput ? customPaidInput.value : 0) || 0;
            if (enteredPaid > netPatientShare) {
                enteredPaid = netPatientShare;
                if (customPaidInput) customPaidInput.value = Math.round(netPatientShare);
            }
            finalActualAmountToCollect = enteredPaid;
            partialDeferredDebt = Math.max(0, netPatientShare - enteredPaid);
            if (partialRemainingBadge) {
                partialRemainingBadge.textContent = numberFormat(partialDeferredDebt) + ' د.ع';
            }
        } else {
            if (partialContainer) partialContainer.style.display = 'none';
            if (customPaidInput) {
                customPaidInput.value = Math.round(netPatientShare);
                customPaidInput.disabled = true;
            }
            finalActualAmountToCollect = netPatientShare;
            partialDeferredDebt = 0;
        }

        // Update displays
        const dispApproved = document.getElementById('display_approved_price');
        const dispPatient = document.getElementById('display_patient_share');
        const dispInsurance = document.getElementById('display_insurance_share');
        const dispSelected = document.getElementById('selectedAmount');
        const dispDeferred = document.getElementById('deferredAmount');
        const inputAmount = document.getElementById('amountInput');
        const submitAmountSpan = document.getElementById('submitAmount');
        const selectedCountSpan = document.getElementById('selectedItemsCount');
        const submitBtn = document.getElementById('submitBtn');
        const deferredRow = document.getElementById('deferredRow');

        let totalDeferredToDisplay = unselectedDeferredAmount + partialDeferredDebt;

        if (dispApproved) dispApproved.textContent = numberFormat(totalApprovedPayable) + ' د.ع';
        if (dispPatient) dispPatient.textContent = numberFormat(finalActualAmountToCollect) + ' د.ع';
        if (dispInsurance) dispInsurance.textContent = numberFormat(totalInsuranceShare) + ' د.ع';
        if (dispSelected) dispSelected.textContent = numberFormat(finalActualAmountToCollect) + ' IQD';
        if (dispDeferred) dispDeferred.textContent = numberFormat(totalDeferredToDisplay) + ' IQD';
        if (inputAmount) inputAmount.value = finalActualAmountToCollect;
        if (submitAmountSpan) submitAmountSpan.textContent = '(' + numberFormat(finalActualAmountToCollect) + ' IQD)';
        if (selectedCountSpan) selectedCountSpan.textContent = selectedCount;

        const tableApproved = document.getElementById('table_total_approved');
        const tableInsurance = document.getElementById('table_total_insurance');
        if (tableApproved) tableApproved.textContent = numberFormat(totalApprovedPayable) + ' د.ع';
        if (tableInsurance) tableInsurance.textContent = numberFormat(totalInsuranceShare) + ' د.ع';

        if (deferredRow) {
            deferredRow.style.display = totalDeferredToDisplay > 0 ? '' : 'none';
        }

        if (submitBtn) {
            if (selectedCount === 0) {
                submitBtn.classList.add('opacity-50');
            } else {
                submitBtn.classList.remove('opacity-50');
            }
        }

        updateGroupCheckbox('lab-test-item', 'selectAllLab');
        updateGroupCheckbox('radiology-test-item', 'selectAllRadiology');
        updateGroupCheckbox('extra-service-item', 'selectAllExtraServices');
        updateSplitUI();
    }

    function updateCopayUI() {
        const insType = insuranceTypeSelect ? insuranceTypeSelect.value : 'none';
        const discountSection = document.getElementById('discount_section');
        const discountSelect = document.getElementById('discount_percentage');
        if (insType === 'moi') {
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (hiBadge) hiBadge.style.display = 'none';
            if (discountSection) discountSection.style.display = 'none';
            if (discountSelect) discountSelect.value = '0';
            if (copayBadge) {
                copayBadge.className = 'badge bg-primary';
                copayBadge.textContent = 'ضمان الداخلية';
            }
        } else if (insType === 'hi') {
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (hiBadge) hiBadge.style.display = 'block';
            if (discountSection) discountSection.style.display = 'none';
            if (discountSelect) discountSelect.value = '0';
            if (copayBadge) {
                copayBadge.className = 'badge bg-info text-dark';
                copayBadge.textContent = 'الضمان الصحي الوطني';
            }
        } else {
            if (insuranceCardCol) insuranceCardCol.style.display = 'none';
            if (copaySection) copaySection.style.display = 'none';
            if (hiBadge) hiBadge.style.display = 'none';
            if (discountSection) discountSection.style.display = 'block';
            if (copayBadge) {
                copayBadge.className = 'badge bg-secondary';
                copayBadge.textContent = 'دفع نقدي كامل (100%)';
            }
        }
        calculateSelectedAmount();
    }

    const discountInputEl = document.getElementById('discount_percentage');
    if (discountInputEl) {
        discountInputEl.addEventListener('input', calculateSelectedAmount);
        discountInputEl.addEventListener('change', calculateSelectedAmount);
    }

    if (insuranceTypeSelect) {
        insuranceTypeSelect.addEventListener('change', function() {
            if (this.value === 'hi') {
                if (paymentInsuranceRadio) paymentInsuranceRadio.checked = true;
                if (copayInput) copayInput.value = hiCategoryCopay;
            } else if (this.value === 'moi') {
                if (paymentInsuranceRadio) paymentInsuranceRadio.checked = true;
                if (copayInput) copayInput.value = moiCopay;
            } else {
                if (paymentCashRadio) paymentCashRadio.checked = true;
            }
            updateCopayUI();
        });
    }

    if (copayInput) {
        copayInput.addEventListener('input', calculateSelectedAmount);
    }

    document.querySelectorAll('.copay-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const pct = this.getAttribute('data-pct');
            if (copayInput) {
                copayInput.value = pct;
                calculateSelectedAmount();
            }
            document.querySelectorAll('.copay-btn').forEach(b => b.classList.remove('active', 'btn-primary'));
            document.querySelectorAll('.copay-btn').forEach(b => b.classList.add('btn-outline-primary'));
            this.classList.remove('btn-outline-primary');
            this.classList.add('active', 'btn-primary');
        });
    });

    document.querySelectorAll('.package-inclusion-switch').forEach(function(sw) {
        sw.addEventListener('change', calculateSelectedAmount);
    });

    document.querySelectorAll('.inclusion-pill-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const container = this.closest('.segmented-control-pills');
            if (container) {
                const hiddenSwitch = container.querySelector('.package-inclusion-switch');
                if (hiddenSwitch) {
                    hiddenSwitch.checked = (this.value === '1');
                }
            }
            calculateSelectedAmount();
        });
    });

    document.querySelectorAll('.extra-service-price-input').forEach(function(input) {
        input.addEventListener('input', calculateSelectedAmount);
        input.addEventListener('change', function() {
            calculateSelectedAmount();
            const url = this.getAttribute('data-url');
            const val = parseFloat(this.value) || 0;
            if (url) {
                fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ price: val })
                }).then(r => r.json()).catch(err => console.error(err));
            }
        });
    });

    function updateGroupCheckbox(itemClass, groupCheckboxId) {
        const items = document.querySelectorAll('.' + itemClass);
        const groupCheckbox = document.getElementById(groupCheckboxId);

        if (!groupCheckbox || items.length === 0) return;

        const checkedItems = document.querySelectorAll('.' + itemClass + ':checked');

        if (checkedItems.length === 0) {
            groupCheckbox.checked = false;
            groupCheckbox.indeterminate = false;
        } else if (checkedItems.length === items.length) {
            groupCheckbox.checked = true;
            groupCheckbox.indeterminate = false;
        } else {
            groupCheckbox.checked = false;
            groupCheckbox.indeterminate = true;
        }
    }

    function numberFormat(num) {
        return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function selectAll() {
        document.querySelectorAll('.payment-item').forEach(function(item) {
            item.checked = true;
        });
        const selectAllLab = document.getElementById('selectAllLab');
        const selectAllRadiology = document.getElementById('selectAllRadiology');
        const selectAllExtra = document.getElementById('selectAllExtraServices');
        if (selectAllLab) selectAllLab.checked = true;
        if (selectAllRadiology) selectAllRadiology.checked = true;
        if (selectAllExtra) selectAllExtra.checked = true;
        calculateSelectedAmount();
    }

    function deselectAll() {
        document.querySelectorAll('.payment-item').forEach(function(item) {
            item.checked = false;
        });
        const selectAllLab = document.getElementById('selectAllLab');
        const selectAllRadiology = document.getElementById('selectAllRadiology');
        const selectAllExtra = document.getElementById('selectAllExtraServices');
        if (selectAllLab) selectAllLab.checked = false;
        if (selectAllRadiology) selectAllRadiology.checked = false;
        if (selectAllExtra) selectAllExtra.checked = false;
        calculateSelectedAmount();
    }

    function toggleLabTests() {
        const selectAllLab = document.getElementById('selectAllLab');
        document.querySelectorAll('.lab-test-item').forEach(function(item) {
            item.checked = selectAllLab.checked;
        });
        calculateSelectedAmount();
    }

    function toggleRadiologyTests() {
        const selectAllRadiology = document.getElementById('selectAllRadiology');
        document.querySelectorAll('.radiology-test-item').forEach(function(item) {
            item.checked = selectAllRadiology.checked;
        });
        calculateSelectedAmount();
    }

    function toggleExtraServices() {
        const selectAllExtra = document.getElementById('selectAllExtraServices');
        document.querySelectorAll('.extra-service-item').forEach(function(item) {
            item.checked = selectAllExtra.checked;
        });
        calculateSelectedAmount();
    }

    // إضافة مستمعي الأحداث
    document.getElementById('selectAllBtn').addEventListener('click', selectAll);
    document.getElementById('deselectAllBtn').addEventListener('click', deselectAll);

    // مستمعي أحداث checkboxes المجموعات
    const selectAllLab = document.getElementById('selectAllLab');
    const selectAllRadiology = document.getElementById('selectAllRadiology');
    const selectAllExtraServices = document.getElementById('selectAllExtraServices');

    if (selectAllLab) {
        selectAllLab.addEventListener('change', toggleLabTests);
    }

    if (selectAllRadiology) {
        selectAllRadiology.addEventListener('change', toggleRadiologyTests);
    }

    if (selectAllExtraServices) {
        selectAllExtraServices.addEventListener('change', toggleExtraServices);
    }

    // =========================================================================
    // محرك البحث والفرز الذكي للخدمات الإضافية (مستوحى من محرك ICD-10)
    // =========================================================================
    @php
        $extraServicesCatalogJson = $availableExtraServices->map(function($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category ?? 'general',
                'price' => (float)$s->default_price,
                'formatted_price' => number_format($s->default_price, 0),
            ];
        })->values();
    @endphp

    const extraServicesCatalog = @json($extraServicesCatalogJson);
    const extraCategoryMap = {
        medical: { name: 'فحوصات وسريرية', icon: 'fa-heartbeat', textClass: 'text-danger', badgeClass: 'bg-danger-subtle text-danger border border-danger border-opacity-25' },
        accommodation: { name: 'فندقية ومرافقين', icon: 'fa-bed', textClass: 'text-primary', badgeClass: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        administrative: { name: 'إدارية وطبلة', icon: 'fa-file-invoice', textClass: 'text-info', badgeClass: 'bg-info-subtle text-info border border-info border-opacity-25' },
        decorating: { name: 'تزيين الغرف', icon: 'fa-ribbon', textClass: 'text-pink', badgeClass: 'bg-pink-subtle text-pink border border-pink border-opacity-25' },
        fine: { name: 'غرامات ومخالفات', icon: 'fa-exclamation-triangle', textClass: 'text-danger', badgeClass: 'bg-warning-subtle text-dark border border-warning' },
        general: { name: 'خدمات عامة', icon: 'fa-tag', textClass: 'text-secondary', badgeClass: 'bg-light text-secondary border' },
    };

    let currentExtraCategory = 'all';
    const serviceNameInput = document.getElementById('service_name_input');
    const servicePriceInput = document.getElementById('service_price_input');
    const serviceIdHidden = document.getElementById('service_id_hidden');
    const serviceCategoryHidden = document.getElementById('service_category_hidden');
    const extraDropdown = document.getElementById('extra_services_dropdown');
    const clearExtraBtn = document.getElementById('btn-clear-extra-service');
    const selectedExtraCard = document.getElementById('selected_extra_service_card');
    const selectedExtraNameText = document.getElementById('selected_extra_name_text');
    const selectedExtraCatBadge = document.getElementById('selected_extra_cat_badge');
    const selectedExtraPriceText = document.getElementById('selected_extra_price_text');
    const btnChangeExtra = document.getElementById('btn_change_extra_service');
    const extraWrapper = document.querySelector('.extra-service-autocomplete-wrapper');

    function escapeExtraHtml(text) {
        if (!text) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function renderExtraServicesDropdown(query = '') {
        if (!extraDropdown) return;
        const q = (query || '').trim().toLowerCase();

        // تصفية حسب الفئة المختارة وحسب نص البحث
        let matches = extraServicesCatalog.filter(item => {
            const matchesCat = (currentExtraCategory === 'all') || (item.category === currentExtraCategory);
            if (!matchesCat) return false;
            if (!q) return true;
            const name = (item.name || '').toLowerCase();
            const catName = (extraCategoryMap[item.category]?.name || '').toLowerCase();
            return name.includes(q) || catName.includes(q);
        });

        let html = '';
        const catTitle = currentExtraCategory === 'all' ? 'جميع الخدمات' : (extraCategoryMap[currentExtraCategory]?.name || 'الخدمات');
        html += `
            <div class="extra-service-dropdown-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list me-1"></i> دليل الخدمات: <strong>${escapeExtraHtml(catTitle)}</strong></span>
                <span class="badge bg-warning text-dark border border-warning" style="font-size: 10px;">${matches.length} خدمة</span>
            </div>
        `;

        if (matches.length > 0) {
            html += '<div class="list-group list-group-flush">';
            matches.forEach(item => {
                const catMeta = extraCategoryMap[item.category] || extraCategoryMap.general;
                html += `
                    <div class="extra-service-item d-flex align-items-center justify-content-between" 
                         data-id="${item.id}" 
                         data-name="${escapeExtraHtml(item.name)}" 
                         data-price="${item.price}" 
                         data-formatted-price="${item.formatted_price}"
                         data-category="${item.category}">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge ${catMeta.badgeClass} extra-service-cat-badge">
                                <i class="fas ${catMeta.icon} me-1"></i>${catMeta.name}
                            </span>
                            <strong class="text-dark extra-service-name">${escapeExtraHtml(item.name)}</strong>
                        </div>
                        <span class="extra-service-price-badge">
                            ${item.formatted_price} د.ع
                        </span>
                    </div>
                `;
            });
            html += '</div>';
        } else {
            html += `
                <div class="p-3 text-center text-muted small">
                    <i class="fas fa-search me-1"></i> لا توجد خدمات مطابقة في هذه الفئة
                </div>
            `;
        }

        // خيار إضافة خدمة مخصصة جديدة وحفظها تلقائياً بالدليل (Custom Option مثل ICD-10)
        if (q.length > 0) {
            const activeCatName = currentExtraCategory !== 'all' ? (extraCategoryMap[currentExtraCategory]?.name || 'عام') : 'عام';
            html += `
                <div class="extra-service-item extra-service-custom-option d-flex align-items-center justify-content-between" 
                     data-custom="1" 
                     data-name="${escapeExtraHtml(query)}">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-plus-circle text-warning fs-6"></i>
                        <div>
                            <span class="d-block fw-bold text-dark">إضافة كخدمة جديدة: <strong>"${escapeExtraHtml(query)}"</strong></span>
                            <small class="text-muted" style="font-size: 10px;">✨ سيتم حفظها في دليل الخدمات (${escapeExtraHtml(activeCatName)}) لتظهر تلقائياً بالمرات القادمة</small>
                        </div>
                    </div>
                    <span class="badge bg-warning text-dark border border-warning" style="font-size: 10px;">حفظ في الدليل</span>
                </div>
            `;
        }

        extraDropdown.innerHTML = html;
        extraDropdown.classList.remove('d-none');
    }

    function selectExtraServiceItem(id, name, price, formattedPrice, category) {
        if (serviceNameInput) serviceNameInput.value = name;
        if (serviceIdHidden) serviceIdHidden.value = id || '';
        if (servicePriceInput) {
            servicePriceInput.value = price;
            servicePriceInput.classList.remove('price-flash-highlight');
            void servicePriceInput.offsetWidth; // trigger reflow
            servicePriceInput.classList.add('price-flash-highlight');
        }

        if (clearExtraBtn) clearExtraBtn.classList.remove('d-none');
        if (extraDropdown) extraDropdown.classList.add('d-none');

        if (selectedExtraCard && id) {
            const catMeta = extraCategoryMap[category] || extraCategoryMap.general;
            if (selectedExtraNameText) selectedExtraNameText.textContent = name;
            if (selectedExtraCatBadge) {
                selectedExtraCatBadge.textContent = catMeta.name;
                selectedExtraCatBadge.className = 'badge font-monospace px-2.5 py-1 ' + catMeta.badgeClass;
            }
            if (selectedExtraPriceText) selectedExtraPriceText.textContent = (formattedPrice || price) + ' د.ع';
            selectedExtraCard.classList.remove('d-none');
        }
    }

    if (serviceNameInput && extraDropdown) {
        // 1. فتح القائمة عند التركيز أو النقر
        serviceNameInput.addEventListener('focus', function() {
            renderExtraServicesDropdown(this.value.trim());
        });

        serviceNameInput.addEventListener('click', function() {
            renderExtraServicesDropdown(this.value.trim());
        });

        // 2. تصفية القائمة عند الكتابة
        serviceNameInput.addEventListener('input', function() {
            const val = this.value.trim();
            if (val.length > 0) {
                if (clearExtraBtn) clearExtraBtn.classList.remove('d-none');
            } else {
                if (clearExtraBtn) clearExtraBtn.classList.add('d-none');
                if (serviceIdHidden) serviceIdHidden.value = '';
                if (selectedExtraCard) selectedExtraCard.classList.add('d-none');
            }
            renderExtraServicesDropdown(val);
        });

        // 3. النقر على عنصر من القائمة المنسدلة
        extraDropdown.addEventListener('click', function(e) {
            const item = e.target.closest('.extra-service-item');
            if (!item) return;

            if (item.dataset.custom === '1') {
                const customName = item.dataset.name;
                if (serviceNameInput) serviceNameInput.value = customName;
                if (serviceIdHidden) serviceIdHidden.value = '';
                if (clearExtraBtn) clearExtraBtn.classList.remove('d-none');
                if (extraDropdown) extraDropdown.classList.add('d-none');
                if (selectedExtraCard) selectedExtraCard.classList.add('d-none');
                if (servicePriceInput) {
                    servicePriceInput.focus();
                    servicePriceInput.select();
                }
                return;
            }

            selectExtraServiceItem(
                item.dataset.id,
                item.dataset.name,
                parseFloat(item.dataset.price) || 0,
                item.dataset.formattedPrice,
                item.dataset.category
            );
        });

        // 4. أزرار شرائح الفلترة السريعة
        document.querySelectorAll('.extra-cat-filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.extra-cat-filter-btn').forEach(b => {
                    b.classList.remove('btn-warning', 'active', 'fw-bold');
                    b.classList.add('btn-outline-secondary');
                });
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-warning', 'active', 'fw-bold');

                currentExtraCategory = this.dataset.category || 'all';
                if (serviceCategoryHidden) {
                    serviceCategoryHidden.value = (currentExtraCategory !== 'all') ? currentExtraCategory : 'general';
                }
                renderExtraServicesDropdown(serviceNameInput.value.trim());
                serviceNameInput.focus();
            });
        });

        // 5. زر المسح (X)
        if (clearExtraBtn) {
            clearExtraBtn.addEventListener('click', function() {
                serviceNameInput.value = '';
                if (serviceIdHidden) serviceIdHidden.value = '';
                if (servicePriceInput) servicePriceInput.value = '';
                clearExtraBtn.classList.add('d-none');
                if (selectedExtraCard) selectedExtraCard.classList.add('d-none');
                serviceNameInput.focus();
                renderExtraServicesDropdown('');
            });
        }

        // 6. زر تغيير الخدمة من بطاقة التأكيد
        if (btnChangeExtra) {
            btnChangeExtra.addEventListener('click', function(e) {
                e.preventDefault();
                serviceNameInput.value = '';
                if (serviceIdHidden) serviceIdHidden.value = '';
                if (selectedExtraCard) selectedExtraCard.classList.add('d-none');
                serviceNameInput.focus();
                renderExtraServicesDropdown('');
            });
        }

        // 7. إغلاق القائمة عند النقر في الخارج
        document.addEventListener('click', function(e) {
            if (extraWrapper && !extraWrapper.contains(e.target) && !e.target.closest('.extra-cat-filter-btn')) {
                extraDropdown.classList.add('d-none');
            }
        });

        // 8. دعم أزرار لوحة المفاتيح (Escape)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !extraDropdown.classList.contains('d-none')) {
                extraDropdown.classList.add('d-none');
            }
        });
    }

    // تأكيد وحذف العناصر المعلقة (تحاليل، أشعة، خدمات إضافية)
    document.querySelectorAll('.delete-surgery-item-btn, .delete-extra-service-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-url');
            const name = this.getAttribute('data-name');
            const row = this.closest('tr');

            const doDelete = function() {
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (row) {
                            row.style.transition = 'all 0.3s ease';
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.remove();
                                calculateSelectedAmount();
                            }, 300);
                        }
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم الحذف',
                                text: data.message,
                                timer: 1800,
                                showConfirmButton: false
                            });
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('خطأ', data.message || 'تعذر الحذف', 'error');
                        } else {
                            alert(data.message || 'تعذر الحذف');
                        }
                    }
                })
                .catch(() => {
                    // Fallback form submit
                    const delForm = document.createElement('form');
                    delForm.method = 'POST';
                    delForm.action = url;
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    const method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'DELETE';
                    delForm.appendChild(csrf);
                    delForm.appendChild(method);
                    document.body.appendChild(delForm);
                    delForm.submit();
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'تأكيد الحذف',
                    html: 'هل أنت متأكد من حذف <strong>' + name + '</strong> من العملية؟',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'نعم، احذف',
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        doDelete();
                    }
                });
            } else if (confirm('هل أنت متأكد من حذف "' + name + '" من العملية؟')) {
                doDelete();
            }
        });
    });

    // مستمعي أحداث تغيير الراديو للغرفة (مشمول / منفصل)
    document.querySelectorAll('.room-inclusion-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const hiddenSwitch = document.getElementById('pkg_incl_room');
            if (hiddenSwitch) {
                hiddenSwitch.checked = (this.value === '1');
            }
            calculateSelectedAmount();
        });
    });

    // مستمعي أحداث اختيار نمط الدفع الكلي (دفع كامل / دفع جزئي)
    document.querySelectorAll('.global-payment-mode').forEach(function(radio) {
        radio.addEventListener('change', calculateSelectedAmount);
    });
    const customPaidAmountInput = document.getElementById('custom_paid_amount');
    if (customPaidAmountInput) {
        customPaidAmountInput.addEventListener('input', calculateSelectedAmount);
        customPaidAmountInput.addEventListener('change', calculateSelectedAmount);
    }

    // إضافة مستمعي الأحداث لجميع checkboxes العناصر
    document.querySelectorAll('.payment-item').forEach(function(item) {
        item.addEventListener('change', calculateSelectedAmount);
    });

    if (cashAmountInput) {
        cashAmountInput.addEventListener('input', function() { syncSplitAmounts('cash'); });
        cashAmountInput.addEventListener('change', function() { syncSplitAmounts('cash'); });
    }
    if (cardAmountInput) {
        cardAmountInput.addEventListener('input', function() { syncSplitAmounts('card'); });
        cardAmountInput.addEventListener('change', function() { syncSplitAmounts('card'); });
    }
    if (splitEqualBtn) {
        splitEqualBtn.addEventListener('click', function() {
            const inputAmount = document.getElementById('amountInput');
            const currentTarget = parseFloat(inputAmount ? inputAmount.value : 0) || 0;
            const half = Math.floor(currentTarget / 2);
            const remaining = currentTarget - half;
            if (cashAmountInput) cashAmountInput.value = half;
            if (cardAmountInput) cardAmountInput.value = remaining;
            updateSplitUI();
        });
    }

    paymentMethodRadios.forEach(radio => {
        radio.addEventListener('change', updateSplitUI);
        radio.addEventListener('click', updateSplitUI);
    });

    const paymentForm = document.getElementById('paymentForm');
    const submitBtnEl = document.getElementById('submitBtn');

    function validateAndProcessPayment(e) {
        const countSpan = document.getElementById('selectedItemsCount');
        const count = parseInt(countSpan ? countSpan.textContent : '0') || 0;

        if (count === 0) {
            if (e) e.preventDefault();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'تنبيه',
                    text: 'يرجى تحديد عنصر واحد على الأقل للدفع قبل التأكيد.',
                    confirmButtonText: 'حسناً'
                });
            } else {
                alert('يرجى تحديد عنصر واحد على الأقل للدفع قبل التأكيد.');
            }
            return false;
        }

        if (typeof isSplitActive === 'function' && isSplitActive()) {
            const inputAmount = document.getElementById('amountInput');
            const currentTarget = parseFloat(inputAmount ? inputAmount.value : 0) || 0;
            const cashVal = parseFloat(cashAmountInput ? cashAmountInput.value : 0) || 0;
            const cardVal = parseFloat(cardAmountInput ? cardAmountInput.value : 0) || 0;
            const totalSplit = Math.round(cashVal + cardVal);
            const targetRounded = Math.round(currentTarget);

            if (totalSplit !== targetRounded) {
                if (e) e.preventDefault();
                alert('تنبيه: مجموع مبالغ الدفع المركب (كاش: ' + numberFormat(cashVal) + ' + بطاقة: ' + numberFormat(cardVal) + ' = ' + numberFormat(totalSplit) + ' د.ع) لا يتطابق مع المبلغ المطلوب قبضه (' + numberFormat(targetRounded) + ' د.ع). يرجى ضبط المبالغ قبل التأكيد.');
                return false;
            }
        }

        const payModePartialRadio = document.getElementById('pay_mode_partial');
        const customPaidInput = document.getElementById('custom_paid_amount');
        if (customPaidInput && payModePartialRadio && !payModePartialRadio.checked) {
            customPaidInput.disabled = true;
        } else if (customPaidInput) {
            customPaidInput.disabled = false;
        }

        if (submitBtnEl) {
            submitBtnEl.classList.add('disabled');
            submitBtnEl.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري تأكيد الدفع...';
        }

        return true;
    }

    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            if (!validateAndProcessPayment(e)) {
                e.preventDefault();
                return false;
            }
        });
    }

    if (submitBtnEl && paymentForm) {
        submitBtnEl.addEventListener('click', function(e) {
            e.preventDefault();
            if (validateAndProcessPayment(e)) {
                paymentForm.submit();
            }
        });
    }

    // حساب أولي
    calculateSelectedAmount();
});
</script>
@endif
@endsection
