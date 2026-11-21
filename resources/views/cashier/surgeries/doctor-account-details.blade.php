@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- ترويسة الصفحة ومعلومات الطبيب --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 bg-white p-3 rounded-4 shadow-sm border">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                        <i class="fas fa-user-md fa-2x"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold text-dark mb-1">
                            {{ __('كشف حساب الجراح:') }} {{ __('د.') }} {{ $doctor->user?->name ?? __('غير محدد') }}
                        </h2>
                        <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 rounded-pill">
                                <i class="fas fa-stethoscope me-1"></i> {{ $doctor->specialization ?? __('جراحة') }}
                            </span>
                            <span>•</span>
                            <span>{{ $doctor->department?->name ?? __('قسم العمليات الجراحية') }}</span>
                            <span>•</span>
                            <span>{{ __('هاتف:') }} {{ $doctor->user?->phone ?? '---' }}</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('cashier.surgeries.doctor-accounts') }}" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> {{ __('العودة لقائمة الجراحين') }}
                    </a>
                    <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-outline-danger rounded-pill px-3 shadow-sm">
                        <i class="fas fa-procedures me-1"></i> {{ __('كاشير العمليات') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- بطاقات المؤشرات المالية الخاصة بالجراح --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-success text-white">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-white-50 small fw-bold d-block mb-1">{{ __('الرصيد الحالي المتبقي للطبيب') }}</span>
                        <h2 class="mb-0 fw-bold">{{ number_format($stats['balance'], 0) }}</h2>
                        <small class="text-white-50">{{ __('د.ع بذمة المستشفى') }}</small>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 text-white">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">{{ __('العمليات المنفذة') }}</span>
                    <h3 class="mb-0 fw-bold text-dark">{{ number_format($stats['surgeries_count']) }}</h3>
                    <small class="text-muted">{{ __('عملية مسددة') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">{{ __('الأتعاب الاسمية') }}</span>
                    <h4 class="mb-0 fw-bold text-secondary">{{ number_format($stats['gross_fees'], 0) }}</h4>
                    <small class="text-muted">{{ __('د.ع قبل الاستقطاع') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">{{ __('استقطاعات الباقة') }}</span>
                    <h4 class="mb-0 fw-bold text-danger">-{{ number_format($stats['package_deductions'], 0) }}</h4>
                    <small class="text-danger">{{ __('د.ع لصالح المستشفى') }}</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">{{ __('إجمالي المصروف') }}</span>
                    <h4 class="mb-0 fw-bold text-success">{{ number_format($stats['total_paid'], 0) }}</h4>
                    <small class="text-success">{{ __('د.ع مسدد فعلياً') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- صندوق صرف دفعة / تصفية رصيد للجراح --}}
    @if($stats['balance'] > 0)
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light border">
            <div class="card-body p-3">
                <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                    <i class="fas fa-hand-holding-usd text-success"></i>
                    <span>{{ __('تسجيل سند صرف دفعة من مستحقات الجراح') }}</span>
                </h6>
                <form action="{{ route('cashier.surgeries.doctor-payout', $doctor) }}" method="POST" id="payoutForm" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-bold text-secondary mb-1">{{ __('المبلغ المراد صرفه (د.ع)') }}</label>
                        <input type="number" name="amount" class="form-control form-control-sm rounded-3 fw-bold font-monospace" max="{{ $stats['balance'] }}" min="1" step="500" value="{{ $stats['balance'] }}" required>
                        <small class="text-muted" style="font-size: 0.7rem;">{{ __('الحد الأقصى المتاح:') }} {{ number_format($stats['balance'], 0) }} {{ __('د.ع') }}</small>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold text-secondary mb-1">{{ __('البيان والملاحظات') }}</label>
                        <input type="text" name="notes" class="form-control form-control-sm rounded-3" placeholder="{{ __('مثال: صرف دفعة نقدية من أتعاب العمليات الجراحية عن شهر...') }}">
                    </div>
                    <div class="col-12 col-md-3">
                        <button type="submit" class="btn btn-success btn-sm w-100 rounded-3 fw-bold py-1-5 shadow-sm">
                            <i class="fas fa-check-circle me-1"></i> {{ __('تسجيل واعتماد سند الصرف') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- تنبيه رقابي لوجود عمليات ملغاة مع زر وصول سريع --}}
    @if($cancelledSurgeries->isNotEmpty())
        <div class="alert alert-danger bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-4 p-3 mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2 shadow-sm">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-exclamation-triangle text-danger fs-5"></i>
                <div>
                    <strong class="text-danger">{{ __('تنبيه رقابي:') }}</strong>
                    <span class="text-dark small">{{ __('يوجد لهذا الجراح') }} <strong>{{ $cancelledSurgeries->count() }} {{ __('عملية ملغاة ومسترجعة للمواطنين') }}</strong> ({{ __('استحقاقها 0 د.ع ومحفوظة في تبويب الأرشيف الرقابي دون التأثير على حسابه') }}).</span>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold shadow-sm" onclick="if(window.activateDoctorTab) window.activateDoctorTab('#cancelled-surgeries-pane');">
                <i class="fas fa-eye me-1"></i> {{ __('عرض العمليات الملغاة') }} ({{ $cancelledSurgeries->count() }})
            </button>
        </div>
    @endif

    {{-- تبويبات حسابات الجراح --}}
    <ul class="nav nav-pills gap-2 mb-3 bg-white p-2 rounded-4 shadow-sm border" id="doctorAccountTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active doctor-account-tab-btn rounded-pill fw-bold px-3 py-2 d-flex align-items-center gap-2" 
                    id="active-surgeries-tab" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#active-surgeries-pane" data-target="#active-surgeries-pane" type="button" role="tab" aria-controls="active-surgeries-pane" aria-selected="true">
                <i class="fas fa-procedures text-primary"></i>
                <span>{{ __('العمليات الجراحية المستحقة') }}</span>
                <span class="badge bg-primary text-white rounded-pill px-2">{{ $surgeries->total() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link doctor-account-tab-btn rounded-pill fw-bold px-3 py-2 d-flex align-items-center gap-2 {{ $cancelledSurgeries->isNotEmpty() ? 'text-danger' : 'text-muted' }}" 
                    id="cancelled-surgeries-tab" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#cancelled-surgeries-pane" data-target="#cancelled-surgeries-pane" type="button" role="tab" aria-controls="cancelled-surgeries-pane" aria-selected="false">
                <i class="fas fa-ban text-danger"></i>
                <span>{{ __('العمليات الملغاة والمسترجعة (للعلم)') }}</span>
                <span class="badge {{ $cancelledSurgeries->isNotEmpty() ? 'bg-danger text-white' : 'bg-light text-secondary border' }} rounded-pill px-2">
                    {{ $cancelledSurgeries->count() }}
                </span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link doctor-account-tab-btn rounded-pill fw-bold px-3 py-2 d-flex align-items-center gap-2" 
                    id="payouts-history-tab" data-bs-toggle="tab" data-toggle="tab" data-bs-target="#payouts-history-pane" data-target="#payouts-history-pane" type="button" role="tab" aria-controls="payouts-history-pane" aria-selected="false">
                <i class="fas fa-history text-success"></i>
                <span>{{ __('سجل سندات الصرف والتسويات') }}</span>
                <span class="badge bg-success text-white rounded-pill px-2">{{ $payouts->count() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="doctorAccountTabsContent">
        {{-- التبويب 1: جدول العمليات الجراحية المستحقة المعتمدة --}}
        <div class="tab-pane fade show active" id="active-surgeries-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-procedures text-danger"></i>
                        <span>{{ __('سجل العمليات الجراحية المعتمدة واستقطاعاتها وتصفيتها') }}</span>
                    </h5>
                    <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                        {{ __('عدد العمليات:') }} {{ $surgeries->total() }}
                    </span>
                </div>
                <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small fw-bold">
                        <tr>
                            <th class="ps-4"># / العملية</th>
                            <th>{{ __('التاريخ والإيصال') }}</th>
                            <th>{{ __('المريض') }}</th>
                            <th>{{ __('نوع الحساب') }}</th>
                            <th class="text-end">{{ __('أتعاب الجراحة الاسمية') }}</th>
                            <th class="text-end">{{ __('استقطاعات الباقة (المستشفى)') }}</th>
                            <th class="text-end">{{ __('صافي مستحق الجراح') }}</th>
                            <th class="text-center">{{ __('سداد الكاشير') }}</th>
                            <th class="text-center pe-4">{{ __('تصفية المستحق') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($surgeries as $surgery)
                            @php
                                $patient = $surgery->patient;
                                $payment = $surgery->payments->first();
                                $isIns = in_array($surgery->insurance_type, ['hi', 'moi', 'health_insurance']);
                                $grossFee = (float) (($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee'));
                                $deductions = (float) ($payment?->doctor_deductions_amount ?? 0);
                                $netFee = $isIns ? $grossFee : max(0.0, $grossFee - $deductions);
                                $due = $payment ? \App\Models\DoctorDue::where('payment_id', $payment->id)->where('doctor_id', $doctor->id)->first() : null;
                                $isSettled = $due && $due->status === 'paid';
                                $itemsSnapshot = $payment && is_array($payment->included_package_items) 
                                    ? $payment->included_package_items 
                                    : ($payment ? (json_decode($payment->included_package_items, true) ?? []) : []);
                            @endphp
                            <tr>
                                {{-- رقم العملية ونوعها --}}
                                <td class="ps-4">
                                    <div class="fw-bold text-dark fs-6">#{{ $surgery->id }} - {{ $surgery->surgery_type }}</div>
                                    @if($surgery->room)
                                        <span class="badge bg-light text-dark border mt-1" style="font-size: 0.7rem;">
                                            {{ __('غرفة') }} {{ $surgery->room->room_number }}
                                        </span>
                                    @endif
                                </td>

                                {{-- التاريخ والإيصال --}}
                                <td>
                                    <div class="fw-bold text-secondary">{{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '---' }}</div>
                                    @if($payment)
                                        <small class="text-muted font-monospace d-block">{{ __('إيصال') }} #{{ $payment->receipt_number ?? $payment->id }}</small>
                                    @endif
                                </td>

                                {{-- اسم المريض --}}
                                <td>
                                    <div class="fw-bold text-dark">{{ $patient?->user?->name ?? $patient?->name ?? __('مريض غير محدد') }}</div>
                                    <small class="text-muted">{{ $patient?->user?->phone ?? '---' }}</small>
                                </td>

                                {{-- نوع الحساب --}}
                                <td>
                                    @if($isIns)
                                        <span class="badge bg-info-subtle text-info border border-info border-opacity-25 rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                            {{ __('ضمان') }} ({{ strtoupper($surgery->insurance_type) }})
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                            {{ __('نقدي (Cash)') }}
                                        </span>
                                    @endif
                                </td>

                                {{-- أتعاب الجراحة الاسمية --}}
                                <td class="text-end font-monospace text-secondary fw-bold">
                                    {{ number_format($grossFee, 0) }} <small class="text-muted">{{ __('د.ع') }}</small>
                                </td>

                                {{-- استقطاعات الباقة وزر المودال --}}
                                <td class="text-end">
                                    @if($deductions > 0)
                                        <span class="fw-bold text-danger font-monospace">-{{ number_format($deductions, 0) }} {{ __('د.ع') }}</span>
                                        <button type="button" 
                                                class="btn btn-xs btn-outline-danger rounded-pill px-2 py-0 d-block ms-auto mt-1 show-package-breakdown-btn"
                                                style="font-size: 0.7rem;"
                                                data-surgery="#{{ $surgery->id }} - {{ $surgery->surgery_type }}"
                                                data-receipt="{{ $payment?->receipt_number ?? $payment?->id }}"
                                                data-deductions="{{ number_format($deductions, 0) }} {{ __('د.ع') }}"
                                                data-items="{{ json_encode($itemsSnapshot, JSON_UNESCAPED_UNICODE) }}">
                                            <i class="fas fa-list me-1"></i> {{ __('تفاصيل') }} ({{ count($itemsSnapshot) }})
                                        </button>
                                    @else
                                        <span class="text-muted font-monospace">0</span>
                                        @if($isIns)
                                            <small class="text-info d-block" style="font-size: 0.7rem;">{{ __('عقد ضمان') }}</small>
                                        @endif
                                    @endif
                                </td>

                                {{-- صافي مستحق الجراح --}}
                                <td class="text-end font-monospace text-success fs-6 fw-bold">
                                    {{ number_format($netFee, 0) }} <small class="text-muted">{{ __('د.ع') }}</small>
                                </td>

                                {{-- حالة السداد في الكاشير --}}
                                <td class="text-center">
                                    @if($surgery->payment_status === 'paid')
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill px-2 py-1">{{ __('مسدد بالكامل') }}</span>
                                    @elseif($surgery->payment_status === 'partial')
                                        <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 rounded-pill px-2 py-1">{{ __('دفع جزئي') }}</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1">{{ __('معلق') }}</span>
                                    @endif
                                </td>

                                {{-- حالة تصفية مستحق الجراح مع زر الصرف السريع --}}
                                <td class="text-center pe-4">
                                    @if($isSettled)
                                        <div class="d-inline-flex flex-column align-items-center">
                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                                                <i class="fas fa-check-circle me-1"></i> {{ __('تم الصرف') }}
                                            </span>
                                            <small class="text-muted mt-1" style="font-size: 0.7rem;">
                                                {{ $due->paid_at ? $due->paid_at->format('Y-m-d H:i') : '' }}
                                                @if($due->paidBy)
                                                    | {{ $due->paidBy->name }}
                                                @endif
                                            </small>
                                        </div>
                                    @else
                                        <div class="d-inline-flex flex-column align-items-center gap-1">
                                            <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                                                <i class="fas fa-clock me-1"></i> {{ __('بانتظار الصرف') }}
                                            </span>
                                            @if($payment)
                                                <button type="button" 
                                                        class="btn btn-sm btn-success rounded-pill px-3 py-0 settle-single-surgery-btn shadow-sm"
                                                        style="font-size: 0.75rem;"
                                                        data-surgery-id="{{ $surgery->id }}"
                                                        data-amount="{{ number_format($netFee, 0) }} {{ __('د.ع') }}"
                                                        data-action="{{ route('cashier.surgeries.settle-single-surgery', [$doctor, $surgery]) }}">
                                                    <i class="fas fa-hand-holding-usd me-1"></i> {{ __('صرف العملية') }}
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-procedures fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-bold">{{ __('لا توجد عمليات جراحية مسجلة لهذا الطبيب حتى الآن.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($surgeries->hasPages())
            <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
                {{ $surgeries->links() }}
            </div>
        @endif
            </div>
        </div>

        {{-- التبويب 2: جدول العمليات الملغاة والمسترجعة (للعلم والأرشفة التدقيقية) --}}
        <div class="tab-pane fade" id="cancelled-surgeries-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-danger bg-opacity-10 py-3 px-4 border-bottom border-danger border-opacity-25 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-danger d-flex align-items-center gap-2">
                        <i class="fas fa-ban"></i>
                        <span>{{ __('أرشيف العمليات الملغاة والمسترجعة (للعلم والتدقيق الرقابي)') }}</span>
                    </h5>
                    <span class="badge bg-danger text-white px-3 py-2 rounded-pill">
                        {{ __('عدد العمليات:') }} {{ $cancelledSurgeries->count() }} ({{ __('استحقاق الطبيب: 0 د.ع') }})
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light text-secondary small fw-bold">
                                <tr>
                                    <th class="ps-4"># / العملية</th>
                                    <th>{{ __('التاريخ') }}</th>
                                    <th>{{ __('المريض') }}</th>
                                    <th class="text-end">{{ __('أجور الجراحة الاسمية') }}</th>
                                    <th class="text-center">{{ __('استحقاق الجراح') }}</th>
                                    <th class="text-center">{{ __('حالة الإلغاء والاسترجاع') }}</th>
                                    <th class="pe-4">{{ __('سندات الاسترجاع والتفاصيل') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cancelledSurgeries as $cancelledSurgery)
                                    @php
                                        $cPatient = $cancelledSurgery->patient;
                                        $refundPayment = $cancelledSurgery->payments->where('amount', '<', 0)->first();
                                    @endphp
                                    <tr class="table-light bg-opacity-50">
                                        {{-- رقم العملية ونوعها --}}
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark fs-6">#{{ $cancelledSurgery->id }} - {{ $cancelledSurgery->surgery_type }}</div>
                                            <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 mt-1" style="font-size: 0.7rem;">
                                                <i class="fas fa-times-circle me-1"></i>{{ __('عملية ملغاة') }}
                                            </span>
                                        </td>

                                        {{-- التاريخ --}}
                                        <td>
                                            <div class="fw-bold text-secondary">{{ $cancelledSurgery->scheduled_date ? $cancelledSurgery->scheduled_date->format('Y-m-d') : '---' }}</div>
                                        </td>

                                        {{-- المريض --}}
                                        <td>
                                            <div class="fw-bold text-dark">{{ $cPatient?->user?->name ?? $cPatient?->name ?? __('مريض غير محدد') }}</div>
                                            <small class="text-muted font-monospace">{{ $cPatient?->patient_id ?? '-' }}</small>
                                        </td>

                                        {{-- الأجور الاسمية --}}
                                        <td class="text-end text-muted text-decoration-line-through font-monospace">
                                            {{ number_format($cancelledSurgery->surgery_fee, 0) }} {{ __('د.ع') }}
                                        </td>

                                        {{-- استحقاق الجراح --}}
                                        <td class="text-center">
                                            <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                                                <i class="fas fa-ban me-1"></i>0 {{ __('د.ع') }} ({{ __('لا يترتب استحقاق') }})
                                            </span>
                                        </td>

                                        {{-- حالة الاسترجاع --}}
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1">
                                                <i class="fas fa-undo me-1"></i>{{ __('تم استرجاع المبلغ للمواطن') }}
                                            </span>
                                        </td>

                                        {{-- سند الاسترجاع والتفاصيل --}}
                                        <td class="pe-4 small text-muted">
                                            @if($refundPayment)
                                                <span class="fw-bold text-danger font-monospace d-block">
                                                    {{ __('إيصال استرجاع') }} #{{ $refundPayment->receipt_number }} ({{ number_format(abs($refundPayment->amount), 0) }} {{ __('د.ع') }})
                                                </span>
                                                <small class="text-muted">{{ $refundPayment->paid_at ? $refundPayment->paid_at->format('Y-m-d H:i') : '' }}</small>
                                            @else
                                                <span class="text-muted">{{ __('أُلغيت قبل التسديد المالي') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                                            <p class="mb-0 fw-bold">{{ __('لا توجد أي عمليات ملغاة أو مسترجعة لهذا الطبيب.') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- التبويب 3: سجل السندات والدفعات المصروفة سابقاً للطبيب --}}
        <div class="tab-pane fade" id="payouts-history-pane" role="tabpanel">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="fas fa-history text-success"></i>
                        <span>{{ __('سجل سندات الصرف والتسويات السابقة للطبيب') }}</span>
                    </h6>
                    <span class="badge bg-success text-white px-3 py-2 rounded-pill">
                        {{ __('عدد السندات:') }} {{ $payouts->count() }} ({{ __('الإجمالي:') }} {{ number_format($stats['total_paid'], 0) }} {{ __('د.ع') }})
                    </span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap">
                            <thead class="table-light text-secondary small fw-bold">
                                <tr>
                                    <th class="ps-4">{{ __('رقم السند') }}</th>
                                    <th>{{ __('تاريخ ووقت الصرف') }}</th>
                                    <th>{{ __('المحاسب المسؤول') }}</th>
                                    <th class="text-end">{{ __('المبلغ المصروف') }}</th>
                                    <th class="pe-4">{{ __('البيان والملاحظات') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payouts as $payout)
                                    <tr>
                                        <td class="ps-4 fw-bold font-monospace text-dark">#{{ $payout->id }}</td>
                                        <td>{{ $payout->paid_at ? $payout->paid_at->format('Y-m-d H:i') : '---' }}</td>
                                        <td>
                                            <i class="fas fa-user-tag text-muted me-1"></i>
                                            {{ $payout->paidBy?->name ?? __('محاسب') }}
                                        </td>
                                        <td class="text-end font-monospace text-success fw-bold fs-6">
                                            {{ number_format($payout->amount, 0) }} {{ __('د.ع') }}
                                        </td>
                                        <td class="pe-4 text-muted small">{{ $payout->notes ?? '---' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                                            <p class="mb-0 fw-bold">{{ __('لا توجد سندات صرف مسجلة لهذا الطبيب حتى الآن.') }}</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- نافذة منبثقة لتفاصيل استقطاعات الباقة (Modal) --}}
<div class="modal fade" id="packageBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2">
                    <i class="fas fa-cut"></i>
                    <span>{{ __('تفاصيل الخدمات المشمولة بالباقة والمستقطعة من الجراح') }}</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('إغلاق') }}"></button>
            </div>
            <div class="modal-body p-4">
                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">{{ __('العملية الجراحية:') }}</span>
                        <strong class="text-dark" id="modalSurgeryName">---</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">{{ __('رقم الإيصال:') }}</span>
                        <span class="badge bg-secondary font-monospace" id="modalReceiptNo">---</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">{{ __('إجمالي الاستقطاع المخصوم:') }}</span>
                        <strong class="text-danger fs-6 font-monospace" id="modalTotalDeductions">0 {{ __('د.ع') }}</strong>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>#</th>
                                <th>{{ __('الخدمة / البند') }}</th>
                                <th>{{ __('النوع') }}</th>
                                <th class="text-end">{{ __('القيمة المستقطعة') }}</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsTbody">
                            {{-- تعبئة تلقائية عبر JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-top">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">{{ __('إغلاق') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. فتح نافذة تفاصيل استقطاعات الباقة
    const breakdownBtns = document.querySelectorAll('.show-package-breakdown-btn');
    const modalEl = document.getElementById('packageBreakdownModal');
    const bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;

    breakdownBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const surgery = this.getAttribute('data-surgery');
            const receipt = this.getAttribute('data-receipt');
            const deductions = this.getAttribute('data-deductions');
            let items = [];
            try {
                items = JSON.parse(this.getAttribute('data-items') || '[]');
            } catch (e) {
                items = [];
            }

            document.getElementById('modalSurgeryName').textContent = surgery;
            document.getElementById('modalReceiptNo').textContent = '#' + receipt;
            document.getElementById('modalTotalDeductions').textContent = deductions;

            const tbody = document.getElementById('modalItemsTbody');
            tbody.innerHTML = '';

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">' + @json(__('لا توجد عناصر مسجلة')) + '</td></tr>';
            } else {
                items.forEach((item, index) => {
                    let typeBadge = '';
                    if (item.type === 'room') {
                        typeBadge = '<span class="badge bg-secondary-subtle text-secondary border">' + @json(__('غرفة وإقامة')) + '</span>';
                    } else if (item.type === 'lab') {
                        typeBadge = '<span class="badge bg-info-subtle text-info border">' + @json(__('تحليل مختبري')) + '</span>';
                    } else if (item.type === 'radiology') {
                        typeBadge = '<span class="badge bg-warning-subtle text-dark border">' + @json(__('فحص أشعة')) + '</span>';
                    } else {
                        typeBadge = '<span class="badge bg-primary-subtle text-primary border">' + @json(__('خدمة إضافية')) + '</span>';
                    }

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="text-center text-muted small">${index + 1}</td>
                        <td class="fw-bold text-dark">${item.name || '---'}</td>
                        <td class="text-center">${typeBadge}</td>
                        <td class="text-end font-monospace text-danger fw-bold">${Number(item.price || 0).toLocaleString()} ' + @json(__('د.ع')) + '</td>
                    `;
                    tbody.appendChild(row);
                });
            }

            if (bsModal) {
                bsModal.show();
            }
        });
    });

    // 2. صرف مستحقات عملية جراحية محددة
    const singleBtns = document.querySelectorAll('.settle-single-surgery-btn');
    singleBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const amount = this.getAttribute('data-amount');
            const actionUrl = this.getAttribute('data-action');
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            Swal.fire({
                title: @json(__('تأكيد صرف مستحقات العملية')),
                html: @json(__('هل أنت متأكد من صرف مستحقات هذه العملية للجراح بمبلغ')) + ' <b class="text-success">' + amount + '</b>' + @json(__('؟')),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-hand-holding-usd me-1"></i> ' + @json(__('نعم، تأكيد الصرف')),
                cancelButtonText: @json(__('إلغاء'))
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: @json(__('جارٍ تسجيل الصرف...')),
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: @json(__('تم الصرف بنجاح')),
                                text: data.message,
                                timer: 1800,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: @json(__('تعذر الصرف')),
                                text: data.message || @json(__('حدث خطأ'))
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: @json(__('خطأ في الاتصال')),
                            text: @json(__('تعذر الاتصال بالسيرفر'))
                        });
                    });
                }
            });
        });
    });

    // 3. التبديل الفوري والصلب بين التبويبات (Fail-safe direct tab switching)
    const tabNavButtons = document.querySelectorAll('#doctorAccountTabs button');
    const tabPanes = document.querySelectorAll('#doctorAccountTabsContent .tab-pane');

    window.activateDoctorTab = function(targetId) {
        if (!targetId) return;
        const cleanId = targetId.startsWith('#') ? targetId : '#' + targetId;
        const targetBtn = document.querySelector(`#doctorAccountTabs button[data-bs-target="${cleanId}"], #doctorAccountTabs button[data-target="${cleanId}"]`);
        const targetPane = document.querySelector(cleanId);

        if (targetBtn && targetPane) {
            tabNavButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.setAttribute('aria-selected', 'false');
            });
            tabPanes.forEach(pane => {
                pane.classList.remove('show', 'active');
                pane.style.display = 'none';
            });

            targetBtn.classList.add('active');
            targetBtn.setAttribute('aria-selected', 'true');
            targetPane.classList.add('show', 'active');
            targetPane.style.display = 'block';

            if (history.replaceState) {
                history.replaceState(null, null, cleanId);
            }
        }
    };

    tabNavButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const targetSelector = this.getAttribute('data-bs-target') || this.getAttribute('data-target');
            window.activateDoctorTab(targetSelector);
        });
    });

    // دعم الانتقال المباشر للتبويب عبر الهاش أو الرابط
    const currentHash = window.location.hash;
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');

    if (currentHash && document.querySelector(currentHash)) {
        window.activateDoctorTab(currentHash);
    } else if (tabParam === 'cancelled') {
        window.activateDoctorTab('#cancelled-surgeries-pane');
    } else if (tabParam === 'payouts') {
        window.activateDoctorTab('#payouts-history-pane');
    }
});
</script>
@endpush

@push('styles')
<style>
    .doctor-account-tab-btn {
        transition: all 0.2s ease-in-out;
        border: 1px solid #dee2e6 !important;
        background-color: #f8f9fa;
        color: #495057;
    }
    .doctor-account-tab-btn:hover {
        background-color: #e9ecef;
    }
    .doctor-account-tab-btn.active#active-surgeries-tab {
        background-color: #0d6efd !important;
        border-color: #0d6efd !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
    }
    .doctor-account-tab-btn.active#active-surgeries-tab i {
        color: #ffffff !important;
    }
    .doctor-account-tab-btn.active#active-surgeries-tab .badge {
        background-color: #ffffff !important;
        color: #0d6efd !important;
    }
    .doctor-account-tab-btn.active#cancelled-surgeries-tab {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.25);
    }
    .doctor-account-tab-btn.active#cancelled-surgeries-tab i {
        color: #ffffff !important;
    }
    .doctor-account-tab-btn.active#cancelled-surgeries-tab .badge {
        background-color: #ffffff !important;
        color: #dc3545 !important;
    }
    .doctor-account-tab-btn.active#payouts-history-tab {
        background-color: #198754 !important;
        border-color: #198754 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(25, 135, 84, 0.25);
    }
    .doctor-account-tab-btn.active#payouts-history-tab i {
        color: #ffffff !important;
    }
    .doctor-account-tab-btn.active#payouts-history-tab .badge {
        background-color: #ffffff !important;
        color: #198754 !important;
    }
</style>
@endpush
@endsection
