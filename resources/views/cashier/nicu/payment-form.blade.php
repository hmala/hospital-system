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
</style>

@php
    $isDischarged = ($reservation->status === 'discharged');
    $babyTitle = $reservation->baby_name ?: ('طفل ' . ($reservation->patient?->name ?? 'غير محدد'));
    $parentName = $reservation->patient?->name ?? 'غير محدد';
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
    $previousPayments = $stayDetails['payments'] ?? collect();

    $labOrders = $medicalOrders->where('order_type', 'lab');
    $radiologyOrders = $medicalOrders->where('order_type', 'radiology');
    $medicationOrders = $medicalOrders->where('order_type', 'medication');
@endphp

<div class="container-fluid py-2">
    <div class="row justify-content-center">
        <div class="col-md-11">
            <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                <!-- Header -->
                <div class="card-header text-white p-3" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-white text-teal rounded-circle p-2 shadow-xs d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; color: #0d9488;">
                                <i class="fas fa-baby fa-lg"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">
                                    تسوية وتسديد رسوم رعاية وحاضنة الخدج
                                    @if($isDischarged)
                                        <span class="badge bg-danger ms-2">خروج طبي - تصفية حساب</span>
                                    @elseif($isSettled)
                                        <span class="badge bg-success ms-2">خالص بالكامل (0 د.ع)</span>
                                    @else
                                        <span class="badge bg-light text-dark ms-2">حاضنة نشطة - قيد الرقود</span>
                                    @endif
                                </h5>
                                <small class="text-white-50">نظام التسديد المالي الموحد ومتابعة بنود الفاتورة والوصل الرسمي</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('cashier.nicu.itemized-receipt.print', $reservation->id) }}" class="btn btn-sm btn-outline-light" target="_blank">
                                <i class="fas fa-print me-1"></i> وصل تفصيلي
                            </a>
                            <a href="{{ route('nicu.statement', $reservation) }}" class="btn btn-sm btn-outline-light" target="_blank">
                                <i class="fas fa-file-invoice-dollar me-1"></i> كشف الحساب
                            </a>
                            <a href="{{ route('cashier.index') }}#tab-nicu" class="btn btn-sm btn-light text-teal fw-bold">
                                <i class="fas fa-arrow-right me-1"></i> العودة للكاشير
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
                                    <span><i class="fas fa-baby me-2"></i>بيانات الحاضنة والرعاية السريرية</span>
                                    <span class="badge bg-primary">إضبارة #{{ $reservation->id }}</span>
                                </h6>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">اسم الطفل:</span>
                                    <strong class="text-dark fs-6">{{ $babyTitle }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">الموقع السريري:</span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-bold">
                                        <i class="fas fa-inbox me-1"></i>حاضنة #{{ $reservation->incubator?->name ?? $reservation->incubator_id }}
                                    </span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">باقة الخدمة الحالية:</span>
                                    <strong class="text-dark">{{ $reservation->nicuService?->name ?? 'باقة الخدج' }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">الطبيب المشرف:</span>
                                    <strong>{{ $reservation->doctor?->user?->name ? 'د. ' . $reservation->doctor->user->name : 'طبيب الخدج الاختصاص' }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">تاريخ وساعة الدخول:</span>
                                    <span class="font-monospace text-dark">{{ $stayDetails['start_datetime']->format('Y-m-d') }} ({{ $stayDetails['start_datetime']->format('h:i A') }})</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                    <span class="text-muted">مدة الرقود المحسوبة:</span>
                                    <div>
                                        <span class="badge bg-dark font-monospace fs-7">{{ $stayDetails['days_count'] }} يوم فندقي</span>
                                        <small class="text-muted ms-1 font-monospace">({{ $stayDetails['total_hours'] }} ساعة فعلياً)</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- معلومات ولي الأمر والتغطية المالية -->
                        <div class="col-md-6">
                            <div class="card border-0 shadow-xs p-3 bg-white h-100 rounded-3">
                                <h6 class="text-success fw-bold mb-3 d-flex align-items-center justify-content-between border-bottom pb-2">
                                    <span><i class="fas fa-user-shield me-2"></i>معلومات ولي الأمر والتغطية المالية</span>
                                    <span class="badge {{ $isInsured ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $isInsured ? '🛡️ الضمان الصحي الوطني' : '💵 نقدي (كاش)' }}
                                    </span>
                                </h6>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">اسم المسؤول / ولي الأمر:</span>
                                    <strong class="text-dark fs-6">{{ $parentName }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">رقم الهوية / الملف:</span>
                                    <span class="font-monospace text-dark fw-bold">#{{ $nationalId }}</span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="text-muted">رقم الهاتف:</span>
                                    <span class="font-monospace" dir="ltr">{{ $parentPhone }}</span>
                                </div>
                                @if($isInsured)
                                    <div class="mb-2 d-flex justify-content-between align-items-center">
                                        <span class="text-muted">فئة التأمين وعمر الحمل:</span>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle fw-bold">
                                            فئة {{ $stayDetails['policy_category'] }} • {{ $stayDetails['gestational_weeks'] ?? '-' }} أسبوع
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <span class="text-muted">تغطية الضمان المقدرة:</span>
                                        <span class="fw-bold text-success font-monospace">{{ number_format($insuranceGrandTotal, 0) }} د.ع</span>
                                    </div>
                                @else
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <span class="text-muted">التصنيف المالي:</span>
                                        <span class="badge bg-light text-dark border">دفع نقدي كامل (خارج مظلة الضمان)</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary Alert Bar -->
                    <div class="card border-0 shadow-xs mb-4 rounded-3 overflow-hidden bg-white">
                        <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark"><i class="fas fa-coins text-warning me-1"></i> ملخص الموقف المالي لحساب الحاضنة</span>
                            <span class="text-muted small">تحديث لحظي لكافة التكاليف والمسدد</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-2 text-center align-items-center">
                                <div class="col-md-4 border-end">
                                    <small class="text-muted d-block mb-1">إجمالي التكلفة المعتمدة</small>
                                    <h4 class="text-dark mb-0 font-monospace fw-bold">{{ number_format($patientGrandTotal, 0) }} <span class="fs-6 text-muted">د.ع</span></h4>
                                    @if($isInsured)
                                        <small class="text-info d-block">(حصة المريض بعد الضمان)</small>
                                    @endif
                                </div>
                                <div class="col-md-4 border-end">
                                    <small class="text-muted d-block mb-1">المسدد سابقاً</small>
                                    <h4 class="text-success mb-0 font-monospace fw-bold">{{ number_format($totalPaid, 0) }} <span class="fs-6 text-muted">د.ع</span></h4>
                                    @if($previousPayments->count() > 0)
                                        <small class="text-muted d-block">({{ $previousPayments->count() }} دفعات مسددة)</small>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    @if($excessDeposit > 0)
                                        <small class="text-danger d-block mb-1 fw-bold">فائض مستحق للإرجاع (Refund)</small>
                                        <h4 class="text-danger mb-0 font-monospace fw-bold">+{{ number_format($excessDeposit, 0) }} <span class="fs-6 text-danger">د.ع</span></h4>
                                        <small class="text-danger d-block">رصيد دائن للمريض</small>
                                    @elseif($isSettled)
                                        <small class="text-success d-block mb-1 fw-bold">حالة الحساب</small>
                                        <h4 class="text-success mb-0 fw-bold"><i class="fas fa-check-circle me-1"></i> خالص (0 د.ع)</h4>
                                        <small class="text-muted d-block">الحساب مسدد بالكامل</small>
                                    @else
                                        <small class="text-danger d-block mb-1 fw-bold">المتبقي المطلوب تسديده</small>
                                        <h4 class="text-danger mb-0 font-monospace fw-bold">{{ number_format($netBalance, 0) }} <span class="fs-6 text-danger">د.ع</span></h4>
                                        <small class="text-danger d-block">مستحق القبض بالصندوق</small>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Refund Section (Only if excessDeposit > 0) -->
                    @if($excessDeposit > 0)
                        <div class="card border-warning border-opacity-75 shadow-sm mb-4 rounded-3" id="refund_section">
                            <div class="card-header bg-warning bg-opacity-25 text-dark fw-bold d-flex justify-content-between align-items-center p-3">
                                <span class="fs-6"><i class="fas fa-undo-alt me-2 text-dark"></i> معالجة إرجاع الفائض المالي للمريض (Refund)</span>
                                <span class="badge bg-warning text-dark border border-warning fs-6 font-monospace">+{{ number_format($excessDeposit, 0) }} د.ع</span>
                            </div>
                            <div class="card-body bg-white p-4">
                                <div class="alert alert-warning border-warning border-opacity-50 py-2 px-3 small d-flex align-items-center mb-3">
                                    <i class="fas fa-info-circle me-2 fs-5 text-warning"></i>
                                    <div>
                                        المبالغ المسددة سابقاً تتجاوز التكلفة الفعلية للطفل بمقدار <strong class="text-danger">{{ number_format($excessDeposit, 0) }} د.ع</strong>. يمكن صرف هذا الفائض للأهل فوراً وإصدار سند صرف رسمي.
                                    </div>
                                </div>
                                <form action="{{ route('cashier.nicu.payment', $reservation->id) }}" method="POST" onsubmit="var btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = '<i class=\'fas fa-spinner fa-spin me-1\'></i> جاري الصرف...';">
                                    @csrf
                                    <input type="hidden" name="payment_type" value="refund">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark small">المبلغ المراد صرفه واسترجاعه (IQD) *</label>
                                            <div class="input-group">
                                                <input type="number" name="amount" class="form-control form-control-lg fw-bold font-monospace text-danger text-center" value="{{ $excessDeposit }}" min="1" max="{{ $excessDeposit }}" step="any" required>
                                                <span class="input-group-text fw-bold">د.ع</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark small">طريقة صرف الاسترجاع *</label>
                                            <select name="payment_method" class="form-select form-select-lg">
                                                <option value="cash" selected>💵 نقداً من الصندوق (Cash)</option>
                                                <option value="card">💳 تحويل إلكتروني / بطاقة</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold text-dark small">ملاحظات وسبب الصرف</label>
                                            <input type="text" name="notes" class="form-control form-control-lg" value="صرف واسترجاع فائض رسوم لحساب الطفل {{ $babyTitle }} بعد تصفية الحساب" placeholder="الشخص المستلم أو سبب الصرف">
                                        </div>
                                    </div>
                                    <div class="text-end mt-3">
                                        <button type="submit" class="btn btn-warning text-dark fw-bold btn-lg px-4 shadow-sm">
                                            <i class="fas fa-undo-alt me-1 text-danger"></i> تأكيد صرف واسترجاع {{ number_format($excessDeposit, 0) }} د.ع وإصدار السند
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    <!-- Itemized Breakdown Tables (قائمة الخدمات والبنود السريرية المفصلة) -->
                    <div class="card border-0 shadow-xs mb-4 rounded-3 bg-white overflow-hidden">
                        <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-list-check text-primary me-2"></i>
                                كشف البنود والخدمات السريرية المفصلة للطفل
                            </h6>
                            <span class="badge bg-light text-muted border">شامل الإقامة، التحاليل، الأشعة، والاستشارات</span>
                        </div>
                        <div class="card-body p-0">
                            <!-- 1. باقات الرعاية والحاضنة -->
                            @if(count($serviceBreakdown) > 0)
                                <div class="p-3 bg-light border-bottom">
                                    <strong class="text-dark small"><i class="fas fa-bed text-primary me-1"></i> 1. باقات الحاضنة والرعاية اليومية والتنفس:</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>باقة الخدمة</th>
                                                <th>الأيام الفندقية</th>
                                                <th>السعر اليومي</th>
                                                <th>الإجمالي الكلي</th>
                                                @if($isInsured)
                                                    <th>تغطية الضمان</th>
                                                    <th>حصة المريض</th>
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
                                                    <td class="font-monospace fw-bold">{{ $sb['days'] }} يوم</td>
                                                    <td class="font-monospace">{{ number_format($sb['daily_fee'], 0) }} د.ع</td>
                                                    <td class="font-monospace fw-bold">{{ number_format($sb['total'], 0) }} د.ع</td>
                                                    @if($isInsured)
                                                        <td class="font-monospace text-success">{{ number_format($sb['insurance_share'], 0) }} د.ع</td>
                                                        <td class="font-monospace text-primary fw-bold">{{ number_format($sb['patient_share'], 0) }} د.ع</td>
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
                                    <strong class="text-dark small"><i class="fas fa-vial text-info me-1"></i> 2. الفحوصات والتحاليل المخبرية ({{ $labOrders->count() }} فحص):</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>اسم التحليل</th>
                                                <th>تاريخ الطلب</th>
                                                <th>الحالة السريرية</th>
                                                <th class="text-end">السعر الكامل</th>
                                                @if($isInsured)
                                                    <th class="text-center">حالة التغطية</th>
                                                    <th class="text-end text-success">مساهمة الضمان</th>
                                                    <th class="text-end pe-3 text-primary">تحمل المريض (كاش)</th>
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
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">مكتمل</span>
                                                        @elseif($order->status === 'cancelled')
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">ملغي</span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">قيد الفحص</span>
                                                        @endif
                                                    </td>
                                                    <td class="font-monospace text-end fw-bold">{{ number_format((float)$order->price, 0) }} د.ع</td>
                                                    @if($isInsured)
                                                        <td class="text-center">
                                                            <span class="badge {{ $order->coverage_badge_class ?? 'bg-secondary' }}">
                                                                {{ $order->coverage_status_text ?? 'نقدي' }}
                                                            </span>
                                                        </td>
                                                        <td class="font-monospace text-end fw-bold text-success">
                                                            {{ number_format((float)($order->insurance_share ?? 0), 0) }} د.ع
                                                        </td>
                                                        <td class="font-monospace text-end pe-3 fw-bold text-primary">
                                                            {{ number_format((float)($order->patient_share ?? $order->price), 0) }} د.ع
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        @if($isInsured)
                                            <tfoot class="table-light small">
                                                <tr class="fw-bold">
                                                    <td colspan="3" class="text-end">مجموع أجور المختبر:</td>
                                                    <td class="text-end font-monospace">{{ number_format($stayDetails['total_lab_cost'] ?? 0, 0) }} د.ع</td>
                                                    <td>-</td>
                                                    <td class="text-end font-monospace text-success">{{ number_format($stayDetails['insurance_lab_share'] ?? 0, 0) }} د.ع</td>
                                                    <td class="text-end pe-3 font-monospace text-primary">{{ number_format($stayDetails['patient_lab_share'] ?? 0, 0) }} د.ع</td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            @endif

                            <!-- 3. الفحوصات الإشعاعية والسونار -->
                            @if($radiologyOrders->count() > 0)
                                <div class="p-3 bg-light border-bottom border-top">
                                    <strong class="text-dark small"><i class="fas fa-radiation text-warning me-1"></i> 3. الفحوصات الإشعاعية والسونار ({{ $radiologyOrders->count() }} فحص):</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>نوع الفحص</th>
                                                <th>تاريخ الطلب</th>
                                                <th>الحالة السريرية</th>
                                                <th class="text-end">السعر الكامل</th>
                                                @if($isInsured)
                                                    <th class="text-center">حالة التغطية</th>
                                                    <th class="text-end text-success">مساهمة الضمان</th>
                                                    <th class="text-end pe-3 text-primary">تحمل المريض (كاش)</th>
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
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">مكتمل</span>
                                                        @elseif($order->status === 'cancelled')
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">ملغي</span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">قيد الإجراء</span>
                                                        @endif
                                                    </td>
                                                    <td class="font-monospace text-end fw-bold">{{ number_format((float)$order->price, 0) }} د.ع</td>
                                                    @if($isInsured)
                                                        <td class="text-center">
                                                            <span class="badge {{ $order->coverage_badge_class ?? 'bg-secondary' }}">
                                                                {{ $order->coverage_status_text ?? 'نقدي' }}
                                                            </span>
                                                        </td>
                                                        <td class="font-monospace text-end fw-bold text-success">
                                                            {{ number_format((float)($order->insurance_share ?? 0), 0) }} د.ع
                                                        </td>
                                                        <td class="font-monospace text-end pe-3 fw-bold text-primary">
                                                            {{ number_format((float)($order->patient_share ?? $order->price), 0) }} د.ع
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        @if($isInsured)
                                            <tfoot class="table-light small">
                                                <tr class="fw-bold">
                                                    <td colspan="3" class="text-end">مجموع أجور الأشعة:</td>
                                                    <td class="text-end font-monospace">{{ number_format($stayDetails['total_radiology_cost'] ?? 0, 0) }} د.ع</td>
                                                    <td>-</td>
                                                    <td class="text-end font-monospace text-success">{{ number_format($stayDetails['insurance_radiology_share'] ?? 0, 0) }} د.ع</td>
                                                    <td class="text-end pe-3 font-monospace text-primary">{{ number_format($stayDetails['patient_radiology_share'] ?? 0, 0) }} د.ع</td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            @endif

                            <!-- 4. استشارات الأطباء الاختصاص -->
                            @if($consultations->count() > 0)
                                <div class="p-3 bg-light border-bottom border-top">
                                    <strong class="text-dark small"><i class="fas fa-user-md text-success me-1"></i> 4. استشارات الأطباء الاختصاص ({{ $consultations->count() }} استشارة):</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>الطبيب المستشار</th>
                                                <th>الاختصاص الطبي</th>
                                                <th>الحالة</th>
                                                <th class="text-end">الأجرة</th>
                                                @if($isInsured)
                                                    <th class="text-center">حالة التغطية</th>
                                                    <th class="text-end text-success">مساهمة الضمان</th>
                                                    <th class="text-end pe-3 text-primary">تحمل المريض (كاش)</th>
                                                @endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($consultations as $cons)
                                                <tr>
                                                    <td class="text-start ps-3 fw-bold text-dark">
                                                        {{ $cons->internalDoctor?->user?->name ? 'د. ' . $cons->internalDoctor->user->name : ($cons->external_doctor_name ?: 'طبيب اختصاص') }}
                                                    </td>
                                                    <td class="text-muted">{{ $cons->specialty ?: '-' }}</td>
                                                    <td>
                                                        @if($cons->status === 'completed')
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">منجز</span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">مطلوب</span>
                                                        @endif
                                                    </td>
                                                    <td class="font-monospace text-end fw-bold">{{ number_format((float)$cons->fee, 0) }} د.ع</td>
                                                    @if($isInsured)
                                                        <td class="text-center">
                                                            <span class="badge {{ $cons->coverage_badge_class ?? 'bg-secondary' }}">
                                                                {{ $cons->coverage_status_text ?? 'نقدي' }}
                                                            </span>
                                                        </td>
                                                        <td class="font-monospace text-end fw-bold text-success">
                                                            {{ number_format((float)($cons->insurance_share ?? 0), 0) }} د.ع
                                                        </td>
                                                        <td class="font-monospace text-end pe-3 fw-bold text-primary">
                                                            {{ number_format((float)($cons->patient_share ?? $cons->fee), 0) }} د.ع
                                                        </td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        @if($isInsured)
                                            <tfoot class="table-light small">
                                                <tr class="fw-bold">
                                                    <td colspan="3" class="text-end">مجموع أجور الاستشارات:</td>
                                                    <td class="text-end font-monospace">{{ number_format($stayDetails['total_consultations_cost'] ?? 0, 0) }} د.ع</td>
                                                    <td>-</td>
                                                    <td class="text-end font-monospace text-success">{{ number_format($stayDetails['insurance_consultations_share'] ?? 0, 0) }} د.ع</td>
                                                    <td class="text-end pe-3 font-monospace text-primary">{{ number_format($stayDetails['patient_consultations_share'] ?? 0, 0) }} د.ع</td>
                                                </tr>
                                            </tfoot>
                                        @endif
                                    </table>
                                </div>
                            @endif

                            <!-- 5. التغذية والعلاجات والأدوية -->
                            @if($medicationOrders->count() > 0)
                                <div class="p-3 bg-light border-bottom border-top">
                                    <strong class="text-dark small"><i class="fas fa-prescription-bottle-alt text-danger me-1"></i> 5. الأدوية وتغذية وحليب الخدج ({{ $medicationOrders->count() }} بند):</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-light small">
                                            <tr>
                                                <th>المادة / العلاج / الحليب</th>
                                                <th>تاريخ التوثيق</th>
                                                <th class="text-end pe-3">التكلفة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($medicationOrders as $order)
                                                <tr>
                                                    <td class="text-start ps-3 fw-bold text-dark">{{ $order->item_name }}</td>
                                                    <td class="font-monospace small text-muted">{{ optional($order->ordered_at)->format('Y-m-d h:i A') ?: '-' }}</td>
                                                    <td class="font-monospace text-end pe-3 fw-bold">{{ number_format((float)$order->price, 0) }} د.ع</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <!-- 6. سجل الدفعات المسددة سابقاً -->
                            @if($previousPayments->count() > 0)
                                <div class="p-3 bg-light border-bottom border-top">
                                    <strong class="text-success small"><i class="fas fa-history me-1"></i> 6. سجل الدفعات المسددة سابقاً في الصندوق ({{ $previousPayments->count() }} دفعات):</strong>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered align-middle mb-0 text-center">
                                        <thead class="table-success small">
                                            <tr>
                                                <th>رقم الوصل</th>
                                                <th>تاريخ وساعة القبض</th>
                                                <th>طريقة الدفع</th>
                                                <th>الكاشير المستلم</th>
                                                <th class="text-end">المبلغ المسدد</th>
                                                <th>معاينة الوصل</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($previousPayments as $pay)
                                                <tr class="{{ $pay->payment_type === 'refund' ? 'table-warning' : '' }}">
                                                    <td class="font-monospace fw-bold">{{ $pay->receipt_number }}</td>
                                                    <td class="font-monospace small">{{ optional($pay->paid_at)->format('Y-m-d h:i A') }}</td>
                                                    <td>
                                                        <span class="badge {{ $pay->payment_method === 'card' ? 'bg-primary' : 'bg-success' }}">
                                                            {{ $pay->payment_method === 'card' ? '💳 بطاقة' : '💵 نقداً' }}
                                                        </span>
                                                    </td>
                                                    <td class="small">{{ $pay->cashier?->name ?? 'غير محدد' }}</td>
                                                    <td class="font-monospace text-end fw-bold {{ $pay->payment_type === 'refund' ? 'text-danger' : 'text-success' }}">
                                                        {{ $pay->payment_type === 'refund' ? '-' : '' }}{{ number_format($pay->amount, 0) }} د.ع
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('cashier.nicu.receipt', $pay->id) }}" class="btn btn-xs btn-outline-primary py-0 px-2" target="_blank">
                                                            <i class="fas fa-print me-1"></i> وصل
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="4" class="text-end fw-bold">إجمالي المسدد الفعلي:</td>
                                                <td class="text-end font-monospace fw-bold text-success">{{ number_format($totalPaid, 0) }} د.ع</td>
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
                                    <h6 class="mb-0 fw-bold text-dark fs-6">صندوق تسديد رسوم الحاضنة وتصفية الحساب</h6>
                                </div>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 fs-7 font-monospace">
                                    صافي المطلوب للتسديد: <strong class="fs-6">{{ number_format($netBalance, 0) }} د.ع</strong>
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
                                                <label class="form-label fw-bolder mb-0 text-dark fs-6">نمط تسديد الرسوم:</label>
                                            </div>
                                            <span class="badge bg-white text-dark border px-2.5 py-1.5 small fw-bold">
                                                المبلغ المستحق حالياً: <strong class="text-success fs-6">{{ number_format($netBalance, 0) }} د.ع</strong>
                                            </span>
                                        </div>

                                        <div class="row g-3 align-items-center">
                                            <div class="col-md-6">
                                                <div class="segmented-control-pills w-100" style="height: 44px;">
                                                    <input type="radio" class="segmented-pill-input global-payment-mode" name="payment_mode" id="pay_mode_full" value="full" checked autocomplete="off">
                                                    <label class="segmented-pill-label pill-included fs-6 fw-bold" for="pay_mode_full">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        تسديد كامل المبلغ المتبقي (<span id="btn_full_amount_label">{{ number_format($netBalance, 0) }}</span> د.ع)
                                                    </label>

                                                    <input type="radio" class="segmented-pill-input global-payment-mode" name="payment_mode" id="pay_mode_partial" value="partial" autocomplete="off">
                                                    <label class="segmented-pill-label pill-unbundled fs-6 fw-bold" for="pay_mode_partial">
                                                        <i class="fas fa-coins me-1"></i>
                                                        تسديد جزئي (دفعة مقبوضة الآن)
                                                    </label>
                                                </div>
                                            </div>

                                            <div class="col-md-6" id="partial_payment_amount_container" style="display: none;">
                                                <div class="p-2.5 rounded-3 border bg-warning-subtle d-flex align-items-center justify-content-between gap-3">
                                                    <div class="flex-grow-1">
                                                        <label class="form-label small fw-bold text-dark mb-1">المبلغ المقبوض فعلياً الآن (IQD) *</label>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-white text-success fw-bold">د.ع</span>
                                                            <input type="number" class="form-control form-control-sm fw-bolder text-success fs-6 text-center" 
                                                                   id="custom_paid_amount" name="custom_paid_amount" min="1" max="{{ $netBalance }}" step="1000" placeholder="{{ $netBalance > 50000 ? 50000 : $netBalance }}">
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-muted d-block fw-bold" style="font-size: 0.72rem;">المتبقي بعد الدفعة</small>
                                                        <span class="badge bg-danger fs-6" id="partial_remaining_badge">0 د.ع</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- أزرار المبالغ السريعة للدفع الجزئي -->
                                        <div class="d-flex gap-1 mt-3 flex-wrap" id="quick_amounts_row" style="display: none !important;">
                                            <span class="text-muted small align-self-center me-2">اقتراحات سريعة:</span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3 quick-amt-btn" data-amt="25000">25,000 د.ع</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3 quick-amt-btn" data-amt="50000">50,000 د.ع</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3 quick-amt-btn" data-amt="100000">100,000 د.ع</button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-3 quick-amt-btn" data-amt="250000">250,000 د.ع</button>
                                            <button type="button" class="btn btn-sm btn-outline-success py-1 px-3 fw-bold quick-amt-btn" data-amt="{{ $netBalance }}">كامل المتبقي ({{ number_format($netBalance, 0) }})</button>
                                        </div>
                                    </div>

                                    <!-- المبلغ الخفي المعياري الموجه للكنترولر -->
                                    <input type="hidden" name="amount" id="final_submission_amount" value="{{ $netBalance }}">

                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark small">
                                                <i class="fas fa-money-bill-wave me-1 text-success"></i> طريقة الدفع <span class="text-danger">*</span>
                                            </label>
                                            <div class="p-3 border rounded-3 bg-light">
                                                <div class="d-flex gap-4">
                                                    <div class="form-check form-check-lg">
                                                        <input class="form-check-input" type="radio" name="payment_method" id="pay_method_cash" value="cash" checked required>
                                                        <label class="form-check-label fw-bold" for="pay_method_cash" style="cursor: pointer;">
                                                            💵 نقداً (Cash)
                                                        </label>
                                                    </div>
                                                    <div class="form-check form-check-lg">
                                                        <input class="form-check-input" type="radio" name="payment_method" id="pay_method_card" value="card">
                                                        <label class="form-check-label fw-bold" for="pay_method_card" style="cursor: pointer;">
                                                            💳 بطاقة إلكترونية / POS
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold text-dark small">ملاحظات التسديد (اختياري)</label>
                                            <input type="text" name="notes" class="form-control form-control-lg" placeholder="مثال: تسديد من والد الطفل / تصفية نهائية">
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
                                        <a href="{{ route('cashier.index') }}#tab-nicu" class="btn btn-outline-secondary px-4">
                                            إلغاء والعودة
                                        </a>
                                        <button type="submit" class="btn btn-success btn-lg fw-bold px-5 shadow" id="submitPaymentBtn">
                                            <i class="fas fa-check-circle me-1"></i>
                                            تأكيد تسديد <span id="btn_submit_amount_display">{{ number_format($netBalance, 0) }}</span> د.ع وطباعة الوصل
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
                                <h4 class="fw-bold text-success mb-1">الحساب خالص ومسدد بالكامل</h4>
                                <p class="text-muted mb-3">لا يترتب أي دين أو رصيد متبقي على حساب هذه الحاضنة حتى اللحظة.</p>
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('cashier.nicu.itemized-receipt.print', $reservation->id) }}" class="btn btn-outline-info px-3" target="_blank">
                                        <i class="fas fa-print me-1"></i> طباعة كشف حراري تفصيلي
                                    </a>
                                    <a href="{{ route('nicu.statement', $reservation) }}" class="btn btn-outline-primary px-3" target="_blank">
                                        <i class="fas fa-file-invoice-dollar me-1"></i> كشف الحساب الرسمي
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
            partialRemainingBadge.textContent = formatNumber(remaining) + ' د.ع';
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
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري حفظ التسديد وإصدار الوصل...';
        });
    }
});
</script>
@endsection
