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
        padding: 6px 14px;
        font-size: 0.85rem;
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
        font-size: 0.8rem;
    }
    /* Active Included (Green) */
    .segmented-pill-input:checked + .segmented-pill-label.pill-included {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.35);
    }
    /* Active Partial / Unbundled (Blue) */
    .segmented-pill-input:checked + .segmented-pill-label.pill-unbundled {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.35);
    }

    /* Extra Services Smart Autocomplete (ICD-10 Style for NICU) */
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
        border: 2px solid #0d9488 !important;
        border-radius: 12px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.22), 0 4px 12px rgba(13, 148, 136, 0.16) !important;
    }
    .extra-service-dropdown-header {
        background: linear-gradient(135deg, #f0fdfa, #ccfbf1);
        border-bottom: 1px solid #99f6e4;
        padding: 8px 14px;
        font-size: 0.8rem;
        font-weight: 700;
        color: #115e59;
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
        background-color: #f0fdfa !important;
        border-right: 4px solid #0d9488;
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
        border-top: 2px dashed #0d9488;
        font-weight: 700;
        color: #0f766e;
        padding: 10px 14px;
    }
    .extra-service-custom-option:hover {
        background-color: #ccfbf1 !important;
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
    $isDischarged = ($reservation->status === 'discharged');
    $babyTitle = $reservation->baby_name ?: (__('طفل') . ' ' . ($reservation->patient?->name ?? __('غير محدد')));
    $parentName = $reservation->patient?->name ?? __('غير محدد');
    $parentPhone = $reservation->patient?->phone ?? '-';
    $nationalId = $reservation->patient?->national_id ?: $reservation->patient_id;

    $isInsured = $stayDetails['is_insured'] ?? false;
    $insuranceType = $stayDetails['insurance_type'] ?? 'none';
    $grandTotal = (float)($stayDetails['grand_total'] ?? 0);
    $patientGrandTotal = (float)($stayDetails['patient_grand_total'] ?? $grandTotal);
    $insuranceGrandTotal = (float)($stayDetails['insurance_grand_total'] ?? 0);
    $totalPaid = (float)($stayDetails['total_paid'] ?? 0);
    $netBalance = (float)($stayDetails['net_balance'] ?? 0);
    $excessDeposit = (float)($stayDetails['excess_deposit'] ?? 0);
    $isSettled = ($netBalance <= 0 && $excessDeposit <= 0);

    $serviceBreakdown = $stayDetails['service_breakdown'] ?? [];
    $consultations = $stayDetails['consultations'] ?? collect();
    $medicalOrders = $stayDetails['medical_orders'] ?? collect();
    $extraServices = $stayDetails['extra_services'] ?? collect();
    $totalExtraServicesCost = (float)($stayDetails['total_extra_services_cost'] ?? 0);
    $previousPayments = $stayDetails['payments'] ?? collect();
    $availableExtraServices = $availableExtraServices ?? collect();

    $labOrders = $medicalOrders->where('order_type', 'lab');
    $radiologyOrders = $medicalOrders->where('order_type', 'radiology');
    $medicationOrders = $medicalOrders->where('order_type', 'medication');

    $canManageExtraCatalog = auth()->check() && (
        auth()->user()->hasAnyRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin', 'مدير النظام'])
        || auth()->user()->can('manage settings')
        || auth()->user()->can('manage users')
        || auth()->user()->can('manage nicu services')
    );
@endphp

<div class="container-fluid py-2">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: visible;">
                <!-- Header -->
                <div class="card-header text-white p-3" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); border-radius: 16px 16px 0 0;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-white text-teal rounded-circle p-2 shadow-xs d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; color: #0d9488;">
                                <i class="fas fa-baby fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">
                                    {{ __('تسوية وتسديد رسوم رعاية وحاضنة الخدج') }}
                                    @if($isDischarged)
                                        <span class="badge bg-danger ms-2">{{ __('خروج طبي - تصفية حساب') }}</span>
                                    @elseif($isSettled)
                                        <span class="badge bg-success ms-2">{{ __('خالص بالكامل (0 د.ع)') }}</span>
                                    @else
                                        <span class="badge bg-light text-dark ms-2">{{ __('حاضنة نشطة - قيد الرقود') }}</span>
                                    @endif
                                </h5>
                                <small class="text-white-50">{{ __('نظام التسديد المالي الموحد ومتابعة بنود الفاتورة والوصل الرسمي') }}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('cashier.nicu.itemized-receipt.print', $reservation->id) }}" class="btn btn-sm btn-outline-light" target="_blank">
                                <i class="fas fa-print me-1"></i> {{ __('وصل تفصيلي') }}
                            </a>
                            <a href="{{ route('nicu.statement', $reservation) }}" class="btn btn-sm btn-outline-light" target="_blank">
                                <i class="fas fa-file-invoice-dollar me-1"></i> {{ __('كشف الحساب') }}
                            </a>
                            <a href="{{ route('cashier.index') }}#tab-nicu" class="btn btn-sm btn-light text-teal fw-bold">
                                <i class="fas fa-arrow-right me-1"></i> {{ __('العودة للكاشير') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 bg-light">
                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show border-0 shadow-xs mb-3" role="alert">
                            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-xs mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Two Information Cards -->
                    <div class="row g-3 mb-4">
                        <!-- معلومات الحاضنة والرعاية السريرية -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-xs p-3 bg-white h-100 rounded-3">
                                <h6 class="text-primary fw-bold mb-3 d-flex align-items-center justify-content-between border-bottom pb-2">
                                    <span><i class="fas fa-baby me-2"></i>{{ __('بيانات الحاضنة والرعاية السريرية') }}</span>
                                    <span class="badge bg-primary">{{ __('إضبارة') }} #{{ $reservation->id }}</span>
                                </h6>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">{{ __('اسم الطفل:') }}</span>
                                    <strong class="text-dark fs-6">{{ $babyTitle }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">{{ __('الموقع السريري:') }}</span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                                        <i class="fas fa-inbox me-1"></i>{{ __('حاضنة') }} #{{ $reservation->incubator?->name ?? $reservation->incubator_id }}
                                    </span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">{{ __('باقة الخدمة الحالية:') }}</span>
                                    <strong class="text-dark">{{ $reservation->nicuService?->name ?? __('باقة الخدج') }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">{{ __('الطبيب المشرف:') }}</span>
                                    <strong>{{ $reservation->doctor?->user?->name ? __('د.') . ' ' . $reservation->doctor->user->name : __('طبيب الخدج الاختصاص') }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">{{ __('تاريخ وساعة الدخول:') }}</span>
                                    <span class="font-monospace text-dark">{{ $stayDetails['start_datetime']->format('Y-m-d') }} ({{ $stayDetails['start_datetime']->format('h:i A') }})</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="text-muted">{{ __('مدة الرقود المحسوبة:') }}</span>
                                    <div>
                                        <span class="badge bg-dark font-monospace fs-7">{{ $stayDetails['days_count'] }} {{ __('يوم فندقي') }}</span>
                                        <small class="text-muted ms-1 font-monospace">({{ $stayDetails['total_hours'] }} {{ __('ساعة فعلياً') }})</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- معلومات ولي الأمر والتغطية المالية -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-xs p-3 bg-white h-100 rounded-3">
                                <h6 class="text-success fw-bold mb-3 d-flex align-items-center justify-content-between border-bottom pb-2">
                                    <span><i class="fas fa-user-shield me-2"></i>{{ __('معلومات ولي الأمر والتغطية المالية') }}</span>
                                    <span class="badge {{ $isInsured ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $isInsured ? __('🛡️ الضمان الصحي الوطني') : __('💵 نقدي (كاش)') }}
                                    </span>
                                </h6>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">{{ __('اسم المسؤول / ولي الأمر:') }}</span>
                                    <strong class="text-dark fs-6">{{ $parentName }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">{{ __('رقم الهوية / الملف:') }}</span>
                                    <span class="font-monospace text-dark fw-bold">#{{ $nationalId }}</span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">{{ __('رقم الهاتف:') }}</span>
                                    <span class="font-monospace" dir="ltr">{{ $parentPhone }}</span>
                                </div>
                                @if($isInsured)
                                    <div class="mb-2 d-flex justify-content-between align-items-center">
                                        <span class="text-muted">{{ __('فئة التأمين وعمر الحمل:') }}</span>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle fw-bold">
                                            {{ __('فئة') }} {{ $stayDetails['policy_category'] }} • {{ $stayDetails['gestational_weeks'] ?? '-' }} {{ __('أسبوع') }}
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <span class="text-muted">{{ __('تغطية الضمان المقدرة:') }}</span>
                                        <span class="fw-bold text-success font-monospace">{{ number_format($insuranceGrandTotal, 0) }} {{ __('د.ع') }}</span>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <span class="text-muted">{{ __('التصنيف المالي:') }}</span>
                                        <span class="badge bg-light text-dark border">{{ __('دفع نقدي كامل (خارج مظلة الضمان)') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary Alert Bar -->
                    <div class="card border-0 shadow-xs mb-4 rounded-3 overflow-hidden bg-white">
                        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark"><i class="fas fa-coins text-warning me-1"></i> {{ __('ملخص الموقف المالي لحساب الحاضنة') }}</span>
                            <span class="text-muted small">{{ __('تحديث لحظي لكافة التكاليف والمسدد') }}</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2 text-center align-items-center">
                                <div class="col-md-4 border-end">
                                    <small class="text-muted d-block mb-1">{{ __('إجمالي التكلفة المعتمدة') }}</small>
                                    <h4 class="text-dark mb-0 font-monospace fw-bold">{{ number_format($patientGrandTotal, 0) }} <span class="fs-6 text-muted">{{ __('د.ع') }}</span></h4>
                                    @if($isInsured)
                                        <small class="text-info d-block">({{ __('حصة المريض بعد الضمان') }})</small>
                                    @endif
                                </div>
                                <div class="col-md-4 border-end">
                                    <small class="text-muted d-block mb-1">{{ __('المسدد سابقاً') }}</small>
                                    <h4 class="text-success mb-0 font-monospace fw-bold">{{ number_format($totalPaid, 0) }} <span class="fs-6 text-muted">{{ __('د.ع') }}</span></h4>
                                    @if($previousPayments->count() > 0)
                                        <small class="text-muted d-block">({{ $previousPayments->count() }} {{ __('دفعات مسددة') }})</small>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    @if($excessDeposit > 0)
                                        <small class="text-danger d-block mb-1 fw-bold">{{ __('فائض مستحق للإرجاع (Refund)') }}</small>
                                        <h4 class="text-danger mb-0 font-monospace fw-bold">+{{ number_format($excessDeposit, 0) }} <span class="fs-6 text-danger">{{ __('د.ع') }}</span></h4>
                                        <small class="text-danger d-block">{{ __('رصيد دائن للمريض') }}</small>
                                    @elseif($isSettled)
                                        <small class="text-success d-block mb-1 fw-bold">{{ __('حالة الحساب') }}</small>
                                        <h4 class="text-success mb-0 fw-bold"><i class="fas fa-check-circle me-1"></i> {{ __('خالص (0 د.ع)') }}</h4>
                                        <small class="text-muted d-block">{{ __('الحساب مسدد بالكامل') }}</small>
                                    @else
                                        <small class="text-danger d-block mb-1 fw-bold">{{ __('المتبقي المطلوب تسديده') }}</small>
                                        <h4 class="text-danger mb-0 font-monospace fw-bold">{{ number_format($netBalance, 0) }} <span class="fs-6 text-danger">{{ __('د.ع') }}</span></h4>
                                        <small class="text-danger d-block">{{ __('مستحق القبض بالصندوق') }}</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Refund Section (Only if excessDeposit > 0) -->
                    @if($excessDeposit > 0)
                        <div class="card border-warning border-opacity-75 shadow-sm mb-4 rounded-3" id="refund_section">
                            <div class="card-header bg-warning bg-opacity-25 text-dark fw-bold d-flex justify-content-between align-items-center p-3">
                                <span class="fs-6"><i class="fas fa-undo-alt me-2 text-dark"></i> {{ __('معالجة إرجاع الفائض المالي للمريض (Refund)') }}</span>
                                <span class="badge bg-warning text-dark border border-warning fs-6 font-monospace">+{{ number_format($excessDeposit, 0) }} {{ __('د.ع') }}</span>
                            </div>
                            <div class="card-body bg-white p-4">
                                <div class="alert alert-warning border-warning border-opacity-50 py-2 px-3 small d-flex align-items-center mb-3">
                                    <i class="fas fa-info-circle me-2 fs-5 text-warning"></i>
                                    <div>
                                        {{ __('المبالغ المسددة سابقاً تتجاوز التكلفة الفعلية للطفل بمقدار') }} <strong class="text-danger">{{ number_format($excessDeposit, 0) }} {{ __('د.ع') }}</strong>. {{ __('يمكن صرف هذا الفائض للأهل فوراً وإصدار سند صرف رسمي.') }}
                                    </div>
                                </div>
                                <form action="{{ route('cashier.nicu.payment', $reservation->id) }}" method="POST" onsubmit="var btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = '<i class=\'fas fa-spinner fa-spin me-1\'></i> ' + '{{ __('جاري الصرف...') }}';">
                                    @csrf
                                    <input type="hidden" name="payment_type" value="refund">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark small">{{ __('المبلغ المراد صرفه واسترجاعه (IQD) *') }}</label>
                                            <div class="input-group">
                                                <input type="number" name="amount" class="form-control form-control-lg fw-bold font-monospace text-danger text-center" value="{{ $excessDeposit }}" min="1" max="{{ $excessDeposit }}" step="any" required>
                                                <span class="input-group-text fw-bold">{{ __('د.ع') }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark small">{{ __('طريقة صرف الاسترجاع *') }}</label>
                                            <select name="payment_method" class="form-select form-select-lg">
                                                <option value="cash" selected>{{ __('💵 نقداً من الصندوق (Cash)') }}</option>
                                                <option value="card">{{ __('💳 تحويل إلكتروني / بطاقة') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark small">{{ __('ملاحظات وسبب الصرف') }}</label>
                                            <input type="text" name="notes" class="form-control form-control-lg" value="{{ __('صرف واسترجاع فائض رسوم لحساب الطفل') }} {{ $babyTitle }} {{ __('بعد تصفية الحساب') }}" placeholder="{{ __('الشخص المستلم أو سبب الصرف') }}">
                                        </div>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-warning text-dark fw-bold btn-lg px-4 shadow-sm">
                                            <i class="fas fa-undo-alt me-1 text-danger"></i> {{ __('تأكيد صرف واسترجاع') }} {{ number_format($excessDeposit, 0) }} {{ __('د.ع وإصدار السند') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    <!-- صندوق إضافة الخدمات الإضافية والفندقية الذكي لقسم الخدج (مستوحى من محرك ICD-10 ومطابق لترتيب العمليات) -->
                    <div class="card border border-warning border-opacity-50 rounded-4 mb-4 shadow-sm bg-white overflow-visible position-relative" style="z-index: 1050;">
                        <div class="card-header bg-warning bg-opacity-10 border-bottom border-warning border-opacity-25 py-2.5 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark p-2 rounded-circle">
                                    <i class="fas fa-concierge-bell"></i>
                                </span>
                                <span>{{ __('إضافة خدمات إضافية وفندقية للخدج والحاضنة (علاج ضوئي، مرافق، طبلة، مستلزمات، غرامات...)') }}</span>
                            </h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning bg-opacity-20 text-dark border border-warning border-opacity-50 px-2.5 py-1 rounded-pill small">
                                    <i class="fas fa-magic me-1"></i> {{ __('تصنيف ذكي وبحث فوري مثل ICD-10') }}
                                </span>
                                @if($canManageExtraCatalog)
                                    <a href="{{ route('admin.extra-services.index', ['department' => 'nicu']) }}" class="btn btn-xs btn-outline-dark rounded-pill px-2.5 py-0.5 small fw-bold" target="_blank" title="{{ __('إدارة وتعديل أسعار الخدمات المتفرقة للخدج') }}">
                                        <i class="fas fa-sliders-h me-1"></i> {{ __('إدارة الأسعار') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <!-- شرائح التصفية السريعة لفئات الخدج -->
                            <div class="d-flex flex-wrap align-items-center gap-1.5 mb-2.5">
                                <span class="small text-muted fw-bold me-1"><i class="fas fa-filter me-1 text-warning"></i> {{ __('تصنيف سريع:') }}</span>
                                <button type="button" class="btn btn-sm btn-warning fw-bold rounded-pill px-2.5 py-0.5 extra-cat-filter-btn active" data-category="all" style="font-size: 11px;">
                                    {{ __('الكل') }} ({{ $availableExtraServices->count() }})
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-cat-filter-btn" data-category="clinical" style="font-size: 11px;">
                                    <i class="fas fa-stethoscope text-danger me-1"></i> {{ __('سريرية وإجراءات') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-cat-filter-btn" data-category="care" style="font-size: 11px;">
                                    <i class="fas fa-baby-carriage text-success me-1"></i> {{ __('عناية ومستلزمات') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-cat-filter-btn" data-category="administrative" style="font-size: 11px;">
                                    <i class="fas fa-file-invoice text-info me-1"></i> {{ __('إدارية ووثائق') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-cat-filter-btn" data-category="accommodation" style="font-size: 11px;">
                                    <i class="fas fa-bed text-primary me-1"></i> {{ __('فندقية ومرافق') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-0.5 extra-cat-filter-btn" data-category="fine" style="font-size: 11px;">
                                    <i class="fas fa-exclamation-triangle text-danger me-1"></i> {{ __('غرامات ومخالفات') }}
                                </button>
                            </div>

                            <form action="{{ route('cashier.nicu.extra-services.add', $reservation) }}" method="POST" id="addNicuExtraServiceForm">
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label small text-muted mb-1 fw-bold">{{ __('اسم الخدمة الإضافية (ابحث أو اختر أو اكتب خدمة جديدة) *') }}</label>
                                        <div class="position-relative extra-service-autocomplete-wrapper">
                                            <div class="input-group input-group-sm shadow-xs">
                                                <span class="input-group-text bg-white border-warning text-warning"><i class="fas fa-search" id="extra-service-search-icon"></i></span>
                                                <input type="text" class="form-control form-control-sm fw-bold border-warning" id="service_name_input" name="service_name" placeholder="{{ __('انقر لاختيار خدمة للخدج أو اكتب للبحث الذكي...') }}" required autocomplete="off">
                                                <button type="button" class="btn btn-outline-secondary px-2 d-none" id="btn-clear-extra-service" title="{{ __('مسح') }}">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" name="service_id" id="service_id_hidden">
                                            <input type="hidden" name="service_category" id="service_category_hidden" value="clinical">

                                            <!-- القائمة المنسدلة الذكية للخدمات الإضافية -->
                                            <div id="extra_services_dropdown" class="extra-services-autocomplete-dropdown shadow-lg d-none"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1 fw-bold">{{ __('السعر المعتمد للوحدة (IQD) *') }}</label>
                                        <div class="input-group input-group-sm shadow-xs">
                                            <span class="input-group-text bg-light text-success fw-bold">IQD</span>
                                            <input type="number" class="form-control form-control-sm fw-bold text-success fs-6" id="service_price_input" name="service_price" min="0" step="any" placeholder="0" required>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small text-muted mb-1 fw-bold">{{ __('الكمية') }}</label>
                                        <input type="number" class="form-control form-control-sm text-center fw-bold" id="service_qty_input" name="service_quantity" min="1" value="1">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted mb-1 fw-bold">{{ __('ملاحظات (اختياري)') }}</label>
                                        <input type="text" class="form-control form-control-sm" name="service_notes" placeholder="{{ __('ملاحظة أو تفصيل...') }}">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold text-dark shadow-xs" id="btnAddExtraService">
                                            <i class="fas fa-plus me-1"></i> {{ __('إضافة') }}
                                        </button>
                                    </div>
                                </div>

                                <!-- بطاقة تأكيد الخدمة المختارة النشطة -->
                                <div id="selected_extra_service_card" class="mt-2.5 p-2 px-3 rounded-3 border bg-warning bg-opacity-10 border-warning border-opacity-25 d-flex align-items-center justify-content-between d-none">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <span class="badge bg-warning text-dark font-monospace px-2.5 py-1" id="selected_extra_cat_badge">{{ __('خدمة خدج إضافية') }}</span>
                                        <div>
                                            <strong class="text-dark small d-block" id="selected_extra_name_text">{{ __('اسم الخدمة') }}</strong>
                                            <span class="text-muted small" style="font-size: 11px;">{{ __('السعر المعتمد بالدليل:') }} <strong class="text-success" id="selected_extra_price_text">0 {{ __('د.ع') }}</strong></span>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0 d-flex align-items-center gap-1" id="btn_change_extra_service">
                                        <i class="fas fa-times-circle"></i>
                                        <span style="font-size: 11px;">{{ __('تغيير أو اختيار خدمة أخرى') }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Itemized Breakdown Tables (قائمة الخدمات والبنود السريرية المفصلة) -->
                    <div class="card border-0 shadow-xs mb-4 rounded-3 bg-white overflow-hidden">
                        <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-list-check text-primary me-2"></i>
                                {{ __('كشف البنود والخدمات السريرية المفصلة للطفل') }}
                            </h6>
                            <span class="badge bg-light text-muted border">{{ __('شامل الإقامة، التحاليل، الأشعة، الاستشارات، والخدمات الإضافية') }}</span>
                        </div>
                        <div class="card-body p-0">
                            <!-- 1. باقات الرعاية والحاضنة -->
                            @if(count($serviceBreakdown) > 0)
                                <div class="p-3 bg-light border-bottom">
                                    <strong class="text-dark small"><i class="fas fa-bed text-primary me-1"></i> {{ __('1. باقات الحاضنة والرعاية اليومية والتنفس:') }}</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>{{ __('باقة الخدمة') }}</th>
                                                <th>{{ __('الأيام الفندقية') }}</th>
                                                <th>{{ __('السعر اليومي') }}</th>
                                                <th>{{ __('الإجمالي الكلي') }}</th>
                                                @if($isInsured)
                                                    <th>{{ __('تغطية الضمان') }}</th>
                                                    <th>{{ __('حصة المريض') }}</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($serviceBreakdown as $sb)
                                                <tr>
                                                    <td class="text-start ps-3">
                                                        <strong class="text-dark">{{ $sb['service_name'] }}</strong>
                                                        @if(!empty($sb['official_claim_name']))
                                                            <small class="text-muted d-block">({{ $sb['official_claim_name'] }})</small>
                                                        @endif
                                                    </td>
                                                    <td class="font-monospace fw-bold">{{ $sb['days'] }} {{ __('يوم') }}</td>
                                                    <td class="font-monospace">{{ number_format($sb['daily_fee'], 0) }} {{ __('د.ع') }}</td>
                                                    <td class="font-monospace fw-bold">{{ number_format($sb['total'], 0) }} {{ __('د.ع') }}</td>
                                                    @if($isInsured)
                                                        <td class="font-monospace text-success">{{ number_format($sb['insurance_share'], 0) }} {{ __('د.ع') }}</td>
                                                        <td class="font-monospace text-primary fw-bold">{{ number_format($sb['patient_share'], 0) }} {{ __('د.ع') }}</td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <!-- 2. الفحوصات والتحاليل المخبرية -->
                            @if($labOrders->count() > 0)
                                <div class="p-3 bg-light border-bottom border-top">
                                    <strong class="text-dark small"><i class="fas fa-vial text-info me-1"></i> {{ __('2. الفحوصات والتحاليل المخبرية') }} ({{ $labOrders->count() }} {{ __('فحص') }}):</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>{{ __('اسم التحليل') }}</th>
                                                <th>{{ __('تاريخ الطلب') }}</th>
                                                <th>{{ __('الحالة السريرية') }}</th>
                                                <th class="text-end">{{ __('السعر الكامل') }}</th>
                                                @if($isInsured)
                                                    <th class="text-center">{{ __('حالة التغطية') }}</th>
                                                    <th class="text-end text-success">{{ __('مساهمة الضمان') }}</th>
                                                    <th class="text-end pe-3 text-primary">{{ __('تحمل المريض (كاش)') }}</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($labOrders as $order)
                                                <tr>
                                                    <td class="text-start ps-3 fw-bold text-dark">
                                                        {{ $order->labTest?->name ?? $order->item_name }}
                                                    </td>
                                                    <td class="font-monospace small text-muted">{{ optional($order->ordered_at)->format('Y-m-d h:i A') ?: '-' }}</td>
                                                    <td>
                                                        @if($order->status === 'completed')
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('مكتمل') }}</span>
                                                        @elseif($order->status === 'cancelled')
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('ملغي') }}</span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('قيد الفحص') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="font-monospace text-end fw-bold">{{ number_format((float)$order->price, 0) }} {{ __('د.ع') }}</td>
                                                    @if($isInsured)
                                                        <td class="text-center">
                                                            <span class="badge {{ $order->coverage_badge_class ?? 'bg-secondary' }}">
                                                                {{ $order->coverage_status_text ?? __('نقدي') }}
                                                            </span>
                                                        </td>
                                                        <td class="font-monospace text-end fw-bold text-success">
                                                            {{ number_format((float)($order->insurance_share ?? 0), 0) }} {{ __('د.ع') }}
                                                        </td>
                                                        <td class="font-monospace text-end pe-3 fw-bold text-primary">
                                                            {{ number_format((float)($order->patient_share ?? $order->price), 0) }} {{ __('د.ع') }}
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        @if($isInsured)
                                            <tfoot class="table-light small">
                                                <tr class="fw-bold">
                                                    <td colspan="3" class="text-end">{{ __('مجموع أجور المختبر:') }}</td>
                                                    <td class="text-end font-monospace">{{ number_format($stayDetails['total_lab_cost'] ?? 0, 0) }} {{ __('د.ع') }}</td>
                                                    <td>-</td>
                                                    <td class="text-end font-monospace text-success">{{ number_format($stayDetails['insurance_lab_share'] ?? 0, 0) }} {{ __('د.ع') }}</td>
                                                    <td class="text-end pe-3 font-monospace text-primary">{{ number_format($stayDetails['patient_lab_share'] ?? 0, 0) }} {{ __('د.ع') }}</td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            @endif

                            <!-- 3. الفحوصات الإشعاعية والسونار -->
                            @if($radiologyOrders->count() > 0)
                                <div class="p-3 bg-light border-bottom border-top">
                                    <strong class="text-dark small"><i class="fas fa-radiation text-warning me-1"></i> {{ __('3. الفحوصات الإشعاعية والسونار') }} ({{ $radiologyOrders->count() }} {{ __('فحص') }}):</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>{{ __('نوع الفحص') }}</th>
                                                <th>{{ __('تاريخ الطلب') }}</th>
                                                <th>{{ __('الحالة السريرية') }}</th>
                                                <th class="text-end">{{ __('السعر الكامل') }}</th>
                                                @if($isInsured)
                                                    <th class="text-center">{{ __('حالة التغطية') }}</th>
                                                    <th class="text-end text-success">{{ __('مساهمة الضمان') }}</th>
                                                    <th class="text-end pe-3 text-primary">{{ __('تحمل المريض (كاش)') }}</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($radiologyOrders as $order)
                                                <tr>
                                                    <td class="text-start ps-3 fw-bold text-dark">
                                                        {{ $order->radiologyType?->name ?? $order->item_name }}
                                                    </td>
                                                    <td class="font-monospace small text-muted">{{ optional($order->ordered_at)->format('Y-m-d h:i A') ?: '-' }}</td>
                                                    <td>
                                                        @if($order->status === 'completed')
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('مكتمل') }}</span>
                                                        @elseif($order->status === 'cancelled')
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">{{ __('ملغي') }}</span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('قيد الإجراء') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="font-monospace text-end fw-bold">{{ number_format((float)$order->price, 0) }} {{ __('د.ع') }}</td>
                                                    @if($isInsured)
                                                        <td class="text-center">
                                                            <span class="badge {{ $order->coverage_badge_class ?? 'bg-secondary' }}">
                                                                {{ $order->coverage_status_text ?? __('نقدي') }}
                                                            </span>
                                                        </td>
                                                        <td class="font-monospace text-end fw-bold text-success">
                                                            {{ number_format((float)($order->insurance_share ?? 0), 0) }} {{ __('د.ع') }}
                                                        </td>
                                                        <td class="font-monospace text-end pe-3 fw-bold text-primary">
                                                            {{ number_format((float)($order->patient_share ?? $order->price), 0) }} {{ __('د.ع') }}
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        @if($isInsured)
                                            <tfoot class="table-light small">
                                                <tr class="fw-bold">
                                                    <td colspan="3" class="text-end">{{ __('مجموع أجور الأشعة:') }}</td>
                                                    <td class="text-end font-monospace">{{ number_format($stayDetails['total_radiology_cost'] ?? 0, 0) }} {{ __('د.ع') }}</td>
                                                    <td>-</td>
                                                    <td class="text-end font-monospace text-success">{{ number_format($stayDetails['insurance_radiology_share'] ?? 0, 0) }} {{ __('د.ع') }}</td>
                                                    <td class="text-end pe-3 font-monospace text-primary">{{ number_format($stayDetails['patient_radiology_share'] ?? 0, 0) }} {{ __('د.ع') }}</td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            @endif

                            <!-- 4. استشارات الأطباء الاختصاص -->
                            @if($consultations->count() > 0)
                                <div class="p-3 bg-light border-bottom border-top">
                                    <strong class="text-dark small"><i class="fas fa-user-md text-success me-1"></i> {{ __('4. استشارات الأطباء الاختصاص') }} ({{ $consultations->count() }} {{ __('استشارة') }}):</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>{{ __('الطبيب المستشار') }}</th>
                                                <th>{{ __('الاختصاص الطبي') }}</th>
                                                <th>{{ __('الحالة') }}</th>
                                                <th class="text-end">{{ __('الأجرة') }}</th>
                                                @if($isInsured)
                                                    <th class="text-center">{{ __('حالة التغطية') }}</th>
                                                    <th class="text-end text-success">{{ __('مساهمة الضمان') }}</th>
                                                    <th class="text-end pe-3 text-primary">{{ __('تحمل المريض (كاش)') }}</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($consultations as $cons)
                                                <tr>
                                                    <td class="text-start ps-3 fw-bold text-dark">
                                                        {{ $cons->internalDoctor?->user?->name ? __('د.') . ' ' . $cons->internalDoctor->user->name : ($cons->external_doctor_name ?: __('طبيب اختصاص')) }}
                                                    </td>
                                                    <td class="text-muted">{{ $cons->specialty ?: '-' }}</td>
                                                    <td>
                                                        @if($cons->status === 'completed')
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ __('منجز') }}</span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">{{ __('مطلوب') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="font-monospace text-end fw-bold">{{ number_format((float)$cons->fee, 0) }} {{ __('د.ع') }}</td>
                                                    @if($isInsured)
                                                        <td class="text-center">
                                                            <span class="badge {{ $cons->coverage_badge_class ?? 'bg-secondary' }}">
                                                                {{ $cons->coverage_status_text ?? __('نقدي') }}
                                                            </span>
                                                        </td>
                                                        <td class="font-monospace text-end fw-bold text-success">
                                                            {{ number_format((float)($cons->insurance_share ?? 0), 0) }} {{ __('د.ع') }}
                                                        </td>
                                                        <td class="font-monospace text-end pe-3 fw-bold text-primary">
                                                            {{ number_format((float)($cons->patient_share ?? $cons->fee), 0) }} {{ __('د.ع') }}
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        @if($isInsured)
                                            <tfoot class="table-light small">
                                                <tr class="fw-bold">
                                                    <td colspan="3" class="text-end">{{ __('مجموع أجور الاستشارات:') }}</td>
                                                    <td class="text-end font-monospace">{{ number_format($stayDetails['total_consultations_cost'] ?? 0, 0) }} {{ __('د.ع') }}</td>
                                                    <td>-</td>
                                                    <td class="text-end font-monospace text-success">{{ number_format($stayDetails['insurance_consultations_share'] ?? 0, 0) }} {{ __('د.ع') }}</td>
                                                    <td class="text-end pe-3 font-monospace text-primary">{{ number_format($stayDetails['patient_consultations_share'] ?? 0, 0) }} {{ __('د.ع') }}</td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            @endif

                            <!-- 5. التغذية والعلاجات والأدوية -->
                            @if($medicationOrders->count() > 0)
                                <div class="p-3 bg-light border-bottom border-top">
                                    <strong class="text-dark small"><i class="fas fa-prescription-bottle-alt text-danger me-1"></i> {{ __('5. الأدوية وتغذية وحليب الخدج') }} ({{ $medicationOrders->count() }} {{ __('بند') }}):</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>{{ __('المادة / العلاج / الحليب') }}</th>
                                                <th>{{ __('تاريخ التوثيق') }}</th>
                                                <th class="text-end pe-3">{{ __('التكلفة') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($medicationOrders as $order)
                                                <tr>
                                                    <td class="text-start ps-3 fw-bold text-dark">{{ $order->item_name }}</td>
                                                    <td class="font-monospace small text-muted">{{ optional($order->ordered_at)->format('Y-m-d h:i A') ?: '-' }}</td>
                                                    <td class="font-monospace text-end pe-3 fw-bold">{{ number_format((float)$order->price, 0) }} {{ __('د.ع') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <!-- 6. الخدمات الإضافية والفندقية المضافة للخديج -->
                            @if($extraServices->count() > 0)
                                <div class="p-3 bg-warning bg-opacity-10 border-bottom border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <strong class="text-dark small">
                                        <i class="fas fa-concierge-bell text-warning me-1"></i>
                                        {{ __('6. الخدمات الإضافية والفندقية للخدج') }} ({{ $extraServices->count() }} {{ __('خدمة') }}):
                                    </strong>
                                    @if($isInsured)
                                        <span class="badge bg-secondary-subtle text-secondary border small">{{ __('خدمات إضافية خارج تغطية الضمان (كاش 100%)') }}</span>
                                    @endif
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th class="text-start ps-3">{{ __('اسم الخدمة والتفاصيل') }}</th>
                                                <th>{{ __('التصنيف') }}</th>
                                                <th style="width: 150px;">{{ __('سعر الوحدة (د.ع)') }}</th>
                                                <th style="width: 85px;">{{ __('الكمية') }}</th>
                                                <th class="text-end">{{ __('الإجمالي') }}</th>
                                                <th style="width: 120px;">{{ __('إجراء') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($extraServices as $extra)
                                                <tr>
                                                    <td class="text-start ps-3">
                                                        <strong class="text-dark d-block">{{ $extra->name }}</strong>
                                                        @if($extra->notes)
                                                            <small class="text-muted"><i class="fas fa-sticky-note me-1"></i>{{ $extra->notes }}</small>
                                                        @endif
                                                        <small class="text-muted d-block" style="font-size: 10px;">
                                                            {{ optional($extra->created_at)->format('Y-m-d h:i A') }}
                                                            @if($extra->createdBy) • {{ $extra->createdBy->name }} @endif
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $extra->category_badge_class }}">{{ $extra->category_label }}</span>
                                                    </td>
                                                    <td>
                                                        <form action="{{ route('cashier.nicu.extra-services.update', [$reservation, $extra]) }}" method="POST" id="update-extra-form-{{ $extra->id }}" class="d-none">
                                                            @csrf
                                                            @method('PATCH')
                                                        </form>
                                                        <input type="number" name="price" value="{{ (float)$extra->price }}" min="0" step="any" form="update-extra-form-{{ $extra->id }}" class="form-control form-control-sm text-center fw-bold text-success font-monospace mx-auto" style="width: 115px;">
                                                    </td>
                                                    <td>
                                                        <input type="number" name="quantity" value="{{ (int)$extra->quantity }}" min="1" form="update-extra-form-{{ $extra->id }}" class="form-control form-control-sm text-center fw-bold mx-auto" style="width: 65px;">
                                                    </td>
                                                    <td class="text-end font-monospace fw-bold text-dark">
                                                        {{ number_format((float)$extra->total_amount, 0) }} {{ __('د.ع') }}
                                                    </td>
                                                    <td>
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="submit" form="update-extra-form-{{ $extra->id }}" class="btn btn-xs btn-outline-success py-0 px-2" title="{{ __('حفظ التعديل') }}">
                                                                <i class="fas fa-save"></i>
                                                            </button>
                                                            <form action="{{ route('cashier.nicu.extra-services.remove', [$reservation, $extra]) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('هل أنت متأكد من حذف هذه الخدمة الإضافية؟') }}');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-2" title="{{ __('حذف الخدمة') }}">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light small">
                                            <tr class="fw-bold">
                                                <td colspan="4" class="text-end">{{ __('مجموع الخدمات الإضافية والفندقية:') }}</td>
                                                <td class="text-end font-monospace text-primary fs-6">{{ number_format($totalExtraServicesCost, 0) }} {{ __('د.ع') }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif

                            <!-- 7. سجل الدفعات المسددة سابقاً -->
                            @if($previousPayments->count() > 0)
                                <div class="p-3 bg-light border-bottom border-top">
                                    <strong class="text-success small"><i class="fas fa-history me-1"></i> {{ __('7. سجل الدفعات المسددة سابقاً في الصندوق') }} ({{ $previousPayments->count() }} {{ __('دفعات') }}):</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-success small">
                                            <tr>
                                                <th>{{ __('رقم الوصل') }}</th>
                                                <th>{{ __('تاريخ وساعة القبض') }}</th>
                                                <th>{{ __('طريقة الدفع') }}</th>
                                                <th>{{ __('الكاشير المستلم') }}</th>
                                                <th class="text-end">{{ __('المبلغ المسدد') }}</th>
                                                <th>{{ __('معاينة الوصل') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($previousPayments as $pay)
                                                <tr class="{{ $pay->payment_type === 'refund' ? 'table-warning' : '' }}">
                                                    <td class="font-monospace fw-bold">{{ $pay->receipt_number }}</td>
                                                    <td class="font-monospace small">{{ optional($pay->paid_at)->format('Y-m-d h:i A') }}</td>
                                                    <td>
                                                        <span class="badge {{ $pay->payment_method === 'card' ? 'bg-primary' : 'bg-success' }}">
                                                            {{ $pay->payment_method === 'card' ? __('💳 بطاقة') : __('💵 نقداً') }}
                                                        </span>
                                                    </td>
                                                    <td class="small">{{ $pay->cashier?->name ?? __('غير محدد') }}</td>
                                                    <td class="font-monospace text-end fw-bold {{ $pay->payment_type === 'refund' ? 'text-danger' : 'text-success' }}">
                                                        {{ $pay->payment_type === 'refund' ? '-' : '' }}{{ number_format($pay->amount, 0) }} {{ __('د.ع') }}
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('cashier.nicu.receipt', $pay->id) }}" class="btn btn-xs btn-outline-primary py-0 px-2" target="_blank">
                                                            <i class="fas fa-print me-1"></i> {{ __('وصل') }}
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="4" class="text-end fw-bold">{{ __('إجمالي المسدد الفعلي:') }}</td>
                                                <td class="text-end font-monospace fw-bold text-success">{{ number_format($totalPaid, 0) }} {{ __('د.ع') }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Payment Dock (نموذج التسديد الجديد بنفس ستايل العمليات الجراحية) -->
                    @if($netBalance > 0)
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3 bg-white">
                            <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-success text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                        <i class="fas fa-cash-register"></i>
                                    </div>
                                    <h6 class="mb-0 fw-bold text-dark fs-6">{{ __('صندوق تسديد رسوم الحاضنة وتصفية الحساب') }}</h6>
                                </div>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-7 font-monospace">
                                    {{ __('صافي المطلوب للتسديد:') }} <strong class="fs-6">{{ number_format($netBalance, 0) }} {{ __('د.ع') }}</strong>
                                </span>
                            </div>

                            <div class="card-body p-4">
                                <form action="{{ route('cashier.nicu.payment', $reservation->id) }}" method="POST" id="nicuPaymentForm">
                                    @csrf

                                    <!-- Segmented Controls for Full vs Partial Payment -->
                                    <div class="card border rounded-4 p-3 mb-4 shadow-2xs bg-light">
                                        <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-hand-holding-usd text-primary fs-5"></i>
                                                <label class="form-label fw-bolder mb-0 text-dark fs-6">{{ __('نمط تسديد الرسوم:') }}</label>
                                            </div>
                                            <span class="badge bg-white text-dark border px-2.5 py-1.5 small fw-bold">
                                                {{ __('المبلغ المستحق حالياً:') }} <strong class="text-success fs-6">{{ number_format($netBalance, 0) }} {{ __('د.ع') }}</strong>
                                            </span>
                                        </div>

                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-6">
                                                <div class="segmented-control-pills w-100" style="height: 44px;">
                                                    <input type="radio" class="segmented-pill-input global-payment-mode" name="payment_mode" id="pay_mode_full" value="full" checked autocomplete="off">
                                                    <label class="segmented-pill-label pill-included fs-6 fw-bold" for="pay_mode_full">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        {{ __('تسديد كامل المبلغ المتبقي') }} (<span id="btn_full_amount_label">{{ number_format($netBalance, 0) }}</span> {{ __('د.ع') }})
                                                    </label>

                                                    <input type="radio" class="segmented-pill-input global-payment-mode" name="payment_mode" id="pay_mode_partial" value="partial" autocomplete="off">
                                                    <label class="segmented-pill-label pill-unbundled fs-6 fw-bold" for="pay_mode_partial">
                                                        <i class="fas fa-coins me-1"></i>
                                                        {{ __('تسديد جزئي (دفعة مقبوضة الآن)') }}
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="col-md-6" id="partial_payment_amount_container" style="display: none;">
                                                <div class="p-2.5 rounded-3 border bg-warning-subtle d-flex align-items-center justify-content-between gap-3">
                                                    <div class="flex-grow-1">
                                                        <label class="form-label small fw-bold text-dark mb-1">{{ __('المبلغ المقبوض فعلياً الآن (IQD) *') }}</label>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-white text-success fw-bold">{{ __('د.ع') }}</span>
                                                            <input type="number" class="form-control form-control-sm fw-bolder text-success fs-6 text-center" 
                                                                   id="custom_paid_amount" name="custom_paid_amount" min="1" max="{{ $netBalance }}" step="1000" placeholder="{{ $netBalance > 50000 ? 50000 : $netBalance }}">
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-muted d-block fw-bold" style="font-size: 0.72rem;">{{ __('المتبقي بعد الدفعة') }}</small>
                                                        <span class="badge bg-danger fs-6" id="partial_remaining_badge">0 {{ __('د.ع') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- أزرار المبالغ السريعة للدفع الجزئي -->
                                        <div class="d-flex gap-1 mt-3 flex-wrap" id="quick_amounts_row" style="display: none !important;">
                                            <span class="text-muted small align-self-center me-2">{{ __('اقتراحات سريعة:') }}</span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3 quick-amt-btn" data-amt="25000">25,000 {{ __('د.ع') }}</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3 quick-amt-btn" data-amt="50000">50,000 {{ __('د.ع') }}</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3 quick-amt-btn" data-amt="100000">100,000 {{ __('د.ع') }}</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3 quick-amt-btn" data-amt="250000">250,000 {{ __('د.ع') }}</button>
                                            <button type="button" class="btn btn-sm btn-outline-success py-1 px-3 fw-bold quick-amt-btn" data-amt="{{ $netBalance }}">{{ __('كامل المتبقي') }} ({{ number_format($netBalance, 0) }})</button>
                                        </div>
                                    </div>

                                    <!-- المبلغ الخفي المعياري الموجه للكنترولر -->
                                    <input type="hidden" name="amount" id="final_submission_amount" value="{{ $netBalance }}">

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark small">
                                                <i class="fas fa-money-bill-wave me-1 text-success"></i> {{ __('طريقة الدفع') }} <span class="text-danger">*</span>
                                            </label>
                                            <div class="p-3 border rounded-3 bg-light">
                                                <div class="d-flex gap-4">
                                                    <div class="form-check form-check-lg">
                                                        <input class="form-check-input" type="radio" name="payment_method" id="pay_method_cash" value="cash" checked required>
                                                        <label class="form-check-label fw-bold" for="pay_method_cash" style="cursor: pointer;">
                                                            {{ __('💵 نقداً (Cash)') }}
                                                        </label>
                                                    </div>
                                                    <div class="form-check form-check-lg">
                                                        <input class="form-check-input" type="radio" name="payment_method" id="pay_method_card" value="card">
                                                        <label class="form-check-label fw-bold" for="pay_method_card" style="cursor: pointer;">
                                                            {{ __('💳 بطاقة إلكترونية / POS') }}
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark small">{{ __('ملاحظات التسديد (اختياري)') }}</label>
                                            <input type="text" name="notes" class="form-control form-control-lg" placeholder="{{ __('مثال: تسديد من والد الطفل / تصفية نهائية') }}">
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
                                        <a href="{{ route('cashier.index') }}#tab-nicu" class="btn btn-outline-secondary px-4">
                                            {{ __('إلغاء والعودة') }}
                                        </a>
                                        <button type="submit" class="btn btn-success btn-lg fw-bold px-5 shadow" id="submitPaymentBtn">
                                            <i class="fas fa-check-circle me-1"></i>
                                            {{ __('تأكيد تسديد') }} <span id="btn_submit_amount_display">{{ number_format($netBalance, 0) }}</span> {{ __('د.ع وطباعة الوصل') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @elseif($isSettled)
                        <div class="card border-success border-opacity-50 shadow-sm mb-4 rounded-4 bg-white text-center p-4">
                            <div class="py-3">
                                <div class="bg-success text-white rounded-circle p-3 d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 60px; height: 60px;">
                                    <i class="fas fa-check-double fa-2x"></i>
                                </div>
                                <h4 class="fw-bold text-success mb-1">{{ __('الحساب خالص ومسدد بالكامل') }}</h4>
                                <p class="text-muted mb-3">{{ __('لا يترتب أي دين أو رصيد متبقي على حساب هذه الحاضنة حتى اللحظة.') }}</p>
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('cashier.nicu.itemized-receipt.print', $reservation->id) }}" class="btn btn-outline-info px-3" target="_blank">
                                        <i class="fas fa-print me-1"></i> {{ __('طباعة كشف حراري تفصيلي') }}
                                    </a>
                                    <a href="{{ route('nicu.statement', $reservation) }}" class="btn btn-outline-primary px-3" target="_blank">
                                        <i class="fas fa-file-invoice-dollar me-1"></i> {{ __('كشف الحساب الرسمي') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const netBalance = {{ (float)$netBalance }};
    const payModeFull = document.getElementById('pay_mode_full');
    const payModePartial = document.getElementById('pay_mode_partial');
    const partialContainer = document.getElementById('partial_payment_amount_container');
    const quickAmountsRow = document.getElementById('quick_amounts_row');
    const customPaidInput = document.getElementById('custom_paid_amount');
    const partialRemainingBadge = document.getElementById('partial_remaining_badge');
    const finalSubmissionAmount = document.getElementById('final_submission_amount');
    const btnSubmitAmountDisplay = document.getElementById('btn_submit_amount_display');
    const paymentForm = document.getElementById('nicuPaymentForm');
    const submitBtn = document.getElementById('submitPaymentBtn');

    function formatNumber(num) {
        return new Intl.NumberFormat('en-US').format(Math.round(num));
    }

    function updatePaymentModeUI() {
        if (!payModeFull || !payModePartial) return;

        if (payModeFull.checked) {
            if (partialContainer) partialContainer.style.display = 'none';
            if (quickAmountsRow) quickAmountsRow.style.setProperty('display', 'none', 'important');
            if (finalSubmissionAmount) finalSubmissionAmount.value = netBalance;
            if (btnSubmitAmountDisplay) btnSubmitAmountDisplay.textContent = formatNumber(netBalance);
        } else {
            if (partialContainer) partialContainer.style.display = 'block';
            if (quickAmountsRow) quickAmountsRow.style.removeProperty('display');
            
            let currentVal = parseFloat(customPaidInput.value);
            if (!currentVal || isNaN(currentVal) || currentVal <= 0) {
                currentVal = netBalance > 50000 ? 50000 : netBalance;
                customPaidInput.value = currentVal;
            }
            updatePartialCalculation();
        }
    }

    function updatePartialCalculation() {
        if (!customPaidInput) return;
        let enteredAmt = parseFloat(customPaidInput.value) || 0;
        
        if (enteredAmt > netBalance) {
            enteredAmt = netBalance;
            customPaidInput.value = enteredAmt;
        }

        let remaining = Math.max(0, netBalance - enteredAmt);
        if (partialRemainingBadge) {
            partialRemainingBadge.textContent = formatNumber(remaining) + ' ' + '{{ __('د.ع') }}';
        }

        if (finalSubmissionAmount) {
            finalSubmissionAmount.value = enteredAmt;
        }
        if (btnSubmitAmountDisplay) {
            btnSubmitAmountDisplay.textContent = formatNumber(enteredAmt);
        }
    }

    if (payModeFull && payModePartial) {
        payModeFull.addEventListener('change', updatePaymentModeUI);
        payModePartial.addEventListener('change', updatePaymentModeUI);
    }

    if (customPaidInput) {
        customPaidInput.addEventListener('input', updatePartialCalculation);
    }

    // Quick amount suggestion buttons
    document.querySelectorAll('.quick-amt-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            let amt = parseFloat(this.getAttribute('data-amt')) || 0;
            if (amt > netBalance) amt = netBalance;
            if (customPaidInput) {
                customPaidInput.value = amt;
                updatePartialCalculation();
            }
        });
    });

    if (paymentForm && submitBtn) {
        paymentForm.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> ' + '{{ __('جاري حفظ التسديد وإصدار الوصل...') }}';
        });
    }

    // =========================================================================
    // محرك البحث والفرز الذكي للخدمات الإضافية لقسم الخدج (مستوحى من محرك ICD-10)
    // =========================================================================
    @php
        $extraServicesCatalogJson = $availableExtraServices->map(function ($s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category ?? 'general',
                'price' => (float) $s->default_price,
                'formatted_price' => number_format($s->default_price, 0),
            ];
        })->values();
    @endphp

    const extraServicesCatalog = @json($extraServicesCatalogJson, JSON_UNESCAPED_UNICODE);
    const extraCategoryMap = {
        clinical: { name: '{{ __('سريرية وإجراءات') }}', icon: 'fa-stethoscope', badgeClass: 'bg-danger-subtle text-danger border border-danger border-opacity-25' },
        medical: { name: '{{ __('فحوصات وسريرية') }}', icon: 'fa-heartbeat', badgeClass: 'bg-danger-subtle text-danger border border-danger border-opacity-25' },
        care: { name: '{{ __('عناية ومستلزمات') }}', icon: 'fa-baby-carriage', badgeClass: 'bg-success-subtle text-success border border-success border-opacity-25' },
        administrative: { name: '{{ __('إدارية ووثائق') }}', icon: 'fa-file-invoice', badgeClass: 'bg-info-subtle text-info border border-info border-opacity-25' },
        accommodation: { name: '{{ __('فندقية ومرافق') }}', icon: 'fa-bed', badgeClass: 'bg-primary-subtle text-primary border border-primary border-opacity-25' },
        decorating: { name: '{{ __('تزيين الغرف') }}', icon: 'fa-ribbon', badgeClass: 'bg-warning-subtle text-dark border border-warning border-opacity-25' },
        fine: { name: '{{ __('غرامات ومخالفات') }}', icon: 'fa-exclamation-triangle', badgeClass: 'bg-warning-subtle text-dark border border-warning' },
        general: { name: '{{ __('خدمات عامة') }}', icon: 'fa-tag', badgeClass: 'bg-light text-secondary border' },
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
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderExtraServicesDropdown(query = '') {
        if (!extraDropdown) return;
        const q = (query || '').trim().toLowerCase();

        const matches = extraServicesCatalog.filter(item => {
            const matchesCat = (currentExtraCategory === 'all')
                || (item.category === currentExtraCategory)
                || (currentExtraCategory === 'clinical' && item.category === 'medical');
            if (!matchesCat) return false;
            if (!q) return true;
            const name = (item.name || '').toLowerCase();
            const catName = (extraCategoryMap[item.category]?.name || '').toLowerCase();
            return name.includes(q) || catName.includes(q);
        });

        const catTitle = currentExtraCategory === 'all'
            ? '{{ __('جميع خدمات الخدج الإضافية') }}'
            : (extraCategoryMap[currentExtraCategory]?.name || '{{ __('الخدمات') }}');

        let html = `
            <div class="extra-service-dropdown-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list me-1"></i> {{ __('دليل خدمات الخدج:') }} <strong>${escapeExtraHtml(catTitle)}</strong></span>
                <span class="badge bg-warning text-dark border border-warning" style="font-size: 10px;">${matches.length} {{ __('خدمة') }}</span>
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
                            ${item.formatted_price} {{ __('د.ع') }}
                        </span>
                    </div>
                `;
            });
            html += '</div>';
        } else {
            html += `
                <div class="p-3 text-center text-muted small">
                    <i class="fas fa-search me-1"></i> {{ __('لا توجد خدمات مطابقة في هذه الفئة') }}
                </div>
            `;
        }

        if (q.length > 0) {
            const activeCatName = currentExtraCategory !== 'all'
                ? (extraCategoryMap[currentExtraCategory]?.name || '{{ __('عام') }}')
                : '{{ __('سريرية وإجراءات') }}';
            html += `
                <div class="extra-service-item extra-service-custom-option d-flex align-items-center justify-content-between"
                     data-custom="1"
                     data-name="${escapeExtraHtml(query)}">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-plus-circle text-success fs-6"></i>
                        <div>
                            <span class="d-block fw-bold text-dark">{{ __('إضافة كخدمة خدج جديدة:') }} <strong>"${escapeExtraHtml(query)}"</strong></span>
                            <small class="text-muted" style="font-size: 10px;">✨ {{ __('سيتم حفظها تلقائياً في دليل خدمات الخدج') }} (${escapeExtraHtml(activeCatName)})</small>
                        </div>
                    </div>
                    <span class="badge bg-warning text-dark border border-warning" style="font-size: 10px;">{{ __('حفظ بالدليل') }}</span>
                </div>
            `;
        }

        extraDropdown.innerHTML = html;
        extraDropdown.classList.remove('d-none');
    }

    function selectExtraServiceItem(id, name, price, formattedPrice, category) {
        if (serviceNameInput) serviceNameInput.value = name;
        if (serviceIdHidden) serviceIdHidden.value = id || '';
        if (serviceCategoryHidden && category) serviceCategoryHidden.value = category;
        if (servicePriceInput) {
            servicePriceInput.value = price;
            servicePriceInput.classList.remove('price-flash-highlight');
            void servicePriceInput.offsetWidth;
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
            if (selectedExtraPriceText) selectedExtraPriceText.textContent = (formattedPrice || price) + '{{ __(' د.ع') }}';
            selectedExtraCard.classList.remove('d-none');
        }
    }

    if (serviceNameInput && extraDropdown) {
        serviceNameInput.addEventListener('focus', function() {
            renderExtraServicesDropdown(this.value.trim());
        });

        serviceNameInput.addEventListener('click', function() {
            renderExtraServicesDropdown(this.value.trim());
        });

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
                    serviceCategoryHidden.value = (currentExtraCategory !== 'all') ? currentExtraCategory : 'clinical';
                }
                renderExtraServicesDropdown(serviceNameInput.value.trim());
                serviceNameInput.focus();
            });
        });

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

        document.addEventListener('click', function(e) {
            if (extraWrapper && !extraWrapper.contains(e.target) && !e.target.closest('.extra-cat-filter-btn')) {
                extraDropdown.classList.add('d-none');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !extraDropdown.classList.contains('d-none')) {
                extraDropdown.classList.add('d-none');
            }
        });
    }
});
</script>
@endsection
