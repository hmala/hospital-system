@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 no-print">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-chart-line me-2 text-primary"></i>الحركات المالية للعيادات الاستشارية</h2>
                <p class="text-muted mb-0">سجل الدفعات والاسترجاعات للمواعيد الاستشارية.</p>
            </div>
            <a href="{{ route('consultant-availability.index') }}" class="btn btn-secondary no-print">
                <i class="fas fa-arrow-left me-2"></i>العودة إلى توفر الأطباء
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 no-print">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('consultant-availability.financial-movements') }}" class="row g-3">
                        <div class="col-md-2 col-sm-6">
                            <label for="from_date" class="form-label fw-bold small">من تاريخ</label>
                            <input type="date" id="from_date" name="from_date" class="form-control form-control-sm" value="{{ old('from_date', $fromDate) }}">
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label for="to_date" class="form-label fw-bold small">إلى تاريخ</label>
                            <input type="date" id="to_date" name="to_date" class="form-control form-control-sm" value="{{ old('to_date', $toDate) }}">
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="filter_type" class="form-label fw-bold small">نوع الحركة</label>
                            <select id="filter_type" name="filter_type" class="form-select form-select-sm">
                                <option value="">كل الحركات</option>
                                <option value="payment" {{ $filterType === 'payment' ? 'selected' : '' }}>حركات قبض (النشطة فقط)</option>
                                <option value="refund" {{ $filterType === 'refund' ? 'selected' : '' }}>حركات استرجاع</option>
                                <option value="appointment_paid" {{ $filterType === 'appointment_paid' ? 'selected' : '' }}>المواعيد المدفوعة بالكامل</option>
                                <option value="appointment_refunded" {{ $filterType === 'appointment_refunded' ? 'selected' : '' }}>المواعيد المسترجعة والملغاة</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-sm-6">
                            <label for="insurance_status" class="form-label fw-bold small">حالة التغطية</label>
                            <select id="insurance_status" name="insurance_status" class="form-select form-select-sm">
                                <option value="">الكل (كاش وضمان)</option>
                                <option value="cash" {{ $insuranceStatus === 'cash' ? 'selected' : '' }}>نقداً فقط (كاش)</option>
                                <option value="insurance" {{ $insuranceStatus === 'insurance' ? 'selected' : '' }}>مشمول بالضمان</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <label for="shift" class="form-label fw-bold small">فترة الدوام</label>
                            <select id="shift" name="shift" class="form-select form-select-sm">
                                <option value="">كامل اليوم (صباحي ومسائي)</option>
                                <option value="morning" {{ ($shift ?? '') === 'morning' ? 'selected' : '' }}>☀️ الدوام الصباحي (8 ص - 3 م)</option>
                                <option value="evening" {{ ($shift ?? '') === 'evening' ? 'selected' : '' }}>🌙 الدوام المسائي (3 م فما بعد)</option>
                            </select>
                        </div>

                        <!-- الصف الثاني: نوع الخدمة والبحث السريع وأزرار الإجراءات -->
                        <div class="col-md-3 col-sm-6">
                            <label for="service_category" class="form-label fw-bold small">نوع الخدمة / الإيراد</label>
                            <select id="service_category" name="service_category" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">الكل (كشفيات عيادات + سونار)</option>
                                <option value="consultation" {{ ($serviceCategory ?? '') === 'consultation' ? 'selected' : '' }}>🩺 كشفيات العيادات فقط</option>
                                <option value="ultrasound" {{ ($serviceCategory ?? '') === 'ultrasound' ? 'selected' : '' }}>📡 السونار الخارجي المباشر فقط</option>
                            </select>
                        </div>

                        <div class="col-md-4 col-sm-6">
                            <label for="search" class="form-label fw-bold small">بحث سريع</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="search" name="search" class="form-control" placeholder="اسم المريض، الهاتف، رقم الوصل..." value="{{ old('search', $search) }}">
                            </div>
                        </div>

                        <div class="col-md-5 col-sm-12 d-flex align-items-end gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">
                                <i class="fas fa-filter me-1"></i>تصفية
                            </button>
                            <a href="{{ route('consultant-availability.financial-movements') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-redo me-1"></i>مسح
                            </a>
                            <a href="{{ route('consultant-availability.financial-movements.export', request()->all()) }}" class="btn btn-success btn-sm fw-bold">
                                <i class="fas fa-file-excel me-1"></i>تصدير إكسل
                            </a>
                            <a href="{{ route('consultant-availability.financial-movements.print', request()->query()) }}" target="_blank" class="btn btn-info btn-sm text-white fw-bold">
                                <i class="fas fa-print me-1"></i>طباعة التقرير الرسمي
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4 no-print">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-white bg-success">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 small fw-bold text-white-50">المستلم نقداً بالصندوق (الدرج)</h6>
                                    <p class="fs-5 fw-bold mb-0">{{ number_format($totalReceived, 2) }} IQD</p>
                                    <small class="text-white-50" style="font-size: 0.70rem;">ما دفعه المرضى فعلياً في الكاشير</small>
                                </div>
                                <i class="fas fa-hand-holding-dollar fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-white bg-danger">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 small fw-bold text-white-50">المسترجع نقداً للمرضى</h6>
                                    <p class="fs-5 fw-bold mb-0">{{ number_format($totalRefunded, 2) }} IQD</p>
                                    <small class="text-white-50" style="font-size: 0.70rem;">مبالغ الاسترجاع النقدية المنفذة</small>
                                </div>
                                <i class="fas fa-undo-alt fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-white bg-info">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 small fw-bold text-white-50">الصافي النقدي الفعلي</h6>
                                    <p class="fs-5 fw-bold mb-0">{{ number_format($netTotal, 2) }} IQD</p>
                                    <small class="text-white-50" style="font-size: 0.70rem;">صافي السيولة النقدية من الكشفيات</small>
                                </div>
                                <i class="fas fa-calculator fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body" id="print-area">
                    <div class="d-none d-print-block mb-4 text-center">
                        <h2>مستشفى الأهلي</h2>
                        <h4>تقرير الحركات المالية للعيادات الاستشارية</h4>
                        <div class="mt-2">
                            <span>تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</span>
                            @if($fromDate || $toDate)
                                <span class="ms-3">الفترة: من {{ $fromDate ?? 'البداية' }} إلى {{ $toDate ?? 'النهاية' }}</span>
                            @endif
                            @if($insuranceStatus)
                                <span class="ms-3">التغطية: {{ $insuranceStatus === 'cash' ? 'نقداً (كاش)' : 'مشمول بالضمان' }}</span>
                            @endif
                            @if(!empty($shift))
                                <span class="ms-3">الدوام: {{ $shift === 'morning' ? 'الصباحي (8 ص - 3 م)' : 'المسائي (3 م فما بعد)' }}</span>
                            @endif
                            @if(!empty($serviceCategory))
                                <span class="ms-3">الخدمة: {{ $serviceCategory === 'ultrasound' ? 'السونار الخارجي المباشر' : 'كشفيات العيادات الاستشارية' }}</span>
                            @endif
                        </div>
                        <hr>
                    </div>
                    @if($payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>التاريخ والتوقيت</th>
                                        <th>المريض</th>
                                        <th>الطبيب</th>
                                        <th>نوع الخدمة / القسم</th>
                                        <th style="min-width: 140px;">المبلغ المدفوع (نقداً)</th>
                                        @if(auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']))
                                        <th>حصة الطبيب</th>
                                        <th>حصة المستشفى</th>
                                        @endif
                                        <th>نوع الحركة</th>
                                        <th>طريقة الدفع والتغطية</th>
                                        <th>رقم الإيصال</th>
                                        <th>الجهة / الكاشير</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        @php
                                            $actualPaid = $payment->patient_paid_amount;
                                            $isRefund = $payment->movement_type === 'refund' || $actualPaid < 0;
                                            $isCancelledOrRefunded = $payment->is_refunded_or_cancelled;
                                            $isInsurance = $payment->is_insurance;
                                            $insuranceShare = $payment->insurance_share_amount;
                                            $totalTariff = abs($payment->total_amount);
                                            $isMorning = $payment->paid_at && $payment->paid_at->format('H:i:s') < '15:00:00';
                                            $isUltrasound = $payment->payment_type === 'ultrasound';
                                        @endphp
                                        <tr class="{{ $isCancelledOrRefunded && !$isRefund ? 'table-light opacity-75' : '' }}">
                                            <td>{{ $payments->firstItem() + $loop->index }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d') : '-' }}</div>
                                                @if($payment->paid_at)
                                                    <div class="small d-flex align-items-center gap-1 mt-1">
                                                        <span class="text-muted" dir="ltr" style="font-size: 0.75rem;">{{ $payment->paid_at->format('h:i A') }}</span>
                                                        @if($isMorning)
                                                            <span class="badge rounded-pill bg-warning text-dark px-2 py-0" style="font-size: 0.65rem;" title="دوام صباحي (قبل 3 عصراً)">☀️ صباحي</span>
                                                        @else
                                                            <span class="badge rounded-pill text-white px-2 py-0" style="font-size: 0.65rem; background-color: #6f42c1;" title="دوام مسائي (3 عصراً فما بعد)">🌙 مسائي</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <strong class="text-dark">{{ optional(optional(optional($payment->appointment)->patient)->user)->name ?? optional(optional($payment->patient)->user)->name ?? optional($payment->patient)->name ?? '-' }}</strong>
                                            </td>
                                            <td>{{ optional(optional(optional($payment->appointment)->doctor)->user)->name ?? optional(optional($payment->doctor)->user)->name ?? '-' }}</td>
                                            <td>
                                                @if($isUltrasound)
                                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-50 px-2 py-1">
                                                        <i class="fas fa-wave-square me-1"></i>سونار خارجي
                                                    </span>
                                                @else
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-50 px-2 py-1">
                                                        <i class="fas fa-stethoscope me-1"></i>كشفية عيادة
                                                    </span>
                                                    <div class="small text-muted mt-0.5" style="font-size: 0.70rem;">{{ optional(optional($payment->appointment)->department)->name ?? 'الاستشارية' }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    @if($isCancelledOrRefunded && !$isRefund)
                                                        <span class="fw-bold text-muted text-decoration-line-through" style="font-size: 0.95rem;" title="تم إلغاء الحجز واسترجاع المبلغ">
                                                            {{ number_format(abs($actualPaid), 2) }} IQD
                                                        </span>
                                                        <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary border-opacity-50 mt-1" style="font-size: 0.65rem;">
                                                            <i class="fas fa-undo-alt me-1"></i>ملغى ومسترجع
                                                        </span>
                                                    @else
                                                        <span class="fw-bold {{ $isRefund ? 'text-danger' : 'text-success' }}" style="font-size: 1rem;">
                                                            {{ number_format(abs($actualPaid), 2) }} IQD
                                                        </span>
                                                    @endif
                                                    @if($isInsurance)
                                                        <div class="mt-1">
                                                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-75" style="font-size: 0.68rem;">
                                                                <i class="fas fa-shield-alt text-warning me-1"></i>تحمّل المريض
                                                            </span>
                                                            <small class="d-block text-muted mt-0.5" style="font-size: 0.70rem;">
                                                                كشفية: {{ number_format($totalTariff, 0) }} | ضمان: {{ number_format($insuranceShare, 0) }}
                                                            </small>
                                                        </div>
                                                    @else
                                                        <div class="mt-1">
                                                            <span class="badge bg-light text-secondary border" style="font-size: 0.68rem;">
                                                                <i class="fas fa-money-bill-wave text-success me-1"></i>كاش نقدي
                                                            </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            @if(auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']))
                                            <td class="text-success fw-semibold">{{ number_format($payment->doctor_share, 2) }} IQD</td>
                                            <td class="text-info fw-semibold">{{ number_format($payment->hospital_share, 2) }} IQD</td>
                                            @endif
                                            <td>
                                                @if($isRefund)
                                                    <span class="badge bg-danger"><i class="fas fa-undo-alt me-1"></i>استرجاع</span>
                                                @elseif($isCancelledOrRefunded)
                                                    <span class="badge bg-secondary text-white" title="تم إلغاء الحجز واسترجاع المبلغ">
                                                        <i class="fas fa-ban me-1"></i>قبض (ملغى ومسترجع)
                                                    </span>
                                                @else
                                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>قبض</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $isInsurance ? 'bg-success bg-opacity-15 text-success border border-success border-opacity-50' : 'bg-light text-dark border' }}">
                                                    {{ $payment->insurance_type_label }}
                                                </span>
                                                <small class="d-block text-muted mt-1" style="font-size: 0.72rem;">
                                                    <i class="fas fa-wallet me-1 text-secondary"></i>{{ $payment->payment_method ?? 'نقد' }}
                                                </small>
                                            </td>
                                            <td>
                                                @if($payment->receipt_number && $payment->payment_id)
                                                    <a href="{{ route('cashier.receipt.print', $payment->payment_id) }}" class="btn btn-sm btn-link p-0 no-print fw-bold font-monospace" target="_blank" title="طباعة الإيصال">
                                                        <i class="fas fa-print me-1 text-primary"></i>{{ $payment->receipt_number }}
                                                    </a>
                                                    <span class="d-none d-print-inline font-monospace">{{ $payment->receipt_number }}</span>
                                                @else
                                                    <span class="font-monospace">{{ $payment->receipt_number ?? '-' }}</span>
                                                @endif
                                            </td>
                                            <td>{{ optional($payment->cashier)->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 no-print">
                            {{ $payments->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد حركات مالية مسجلة ضمن الفترة الحالية.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('styles')
<style>
@media print {
    /* Hide layout chrome and marked elements */
    .sidebar, .navbar, header, footer, .no-print, .no-print * {
        display: none !important;
    }
    
    /* Make main content occupy full width */
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        background: transparent !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
    
    body {
        background: #fff !important;
        color: #000 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
    }
    
    .table-responsive {
        overflow: visible !important;
    }
    
    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    
    th, td {
        border: 1px solid #dee2e6 !important;
        padding: 6px !important;
        font-size: 11px !important;
    }
}
</style>
@endsection
