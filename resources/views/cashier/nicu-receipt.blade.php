@extends('layouts.app')

@section('content')
<div class="container py-3">
    <!-- شريط التحكم العلوي -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                @if($nicuPayment->payment_type === 'refund')
                    <i class="fas fa-undo text-warning me-2"></i>
                    سند استرجاع فائض أمانات حاضنة الخدج
                @else
                    <i class="fas fa-receipt text-success me-2"></i>
                    سند قبض رسمي - أمانات الخدج والحاضنات (NICU)
                @endif
            </h4>
            <span class="text-muted small">رقم الإيصال: <strong class="text-dark">{{ $nicuPayment->receipt_number }}</strong></span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('cashier.nicu.receipt.print', $nicuPayment->id) }}" class="btn btn-success px-4 rounded-pill shadow-sm" target="_blank">
                <i class="fas fa-print me-1"></i> طباعة الوصل الحراري
            </a>
            <a href="{{ route('cashier.nicu.itemized-receipt.print', $nicuPayment->incubator_reservation_id) }}" class="btn btn-outline-info px-3 rounded-pill" target="_blank">
                <i class="fas fa-file-invoice-dollar me-1"></i> كشف خدمات تفصيلي حراري
            </a>
            <a href="{{ route('nicu.statement', $nicuPayment->incubator_reservation_id) }}" class="btn btn-outline-primary px-3 rounded-pill" target="_blank">
                <i class="fas fa-file-alt me-1"></i> كشف الحساب التراكمي
            </a>
            <a href="{{ route('cashier.index') }}" class="btn btn-outline-secondary px-3 rounded-pill">
                <i class="fas fa-arrow-right me-1"></i> العودة للكاشير
            </a>
        </div>
    </div>

    @php
        $res = $nicuPayment->reservation;
        $babyTitle = $res ? ($res->baby_name ?: ('طفل ' . ($res->patient?->name ?? 'غير مسجل'))) : 'الخديج';
        $incName = $res?->incubator?->name ?? ('حاضنة #' . ($res?->incubator_id ?? ''));
        $docName = $res?->doctor?->user?->name ? ('د. ' . $res->doctor->user->name) : 'طبيب الخدج المشرف';
        $guardian = $res?->patient?->name ?? 'غير مسجل';
        $phone = $res?->patient?->phone ?? '—';
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-dark text-white py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold"><i class="fas fa-baby me-2 text-info"></i>{{ $babyTitle }}</h5>
                        <small class="text-light opacity-75">{{ $incName }} — ولي الأمر: {{ $guardian }}</small>
                    </div>
                    <div>
                        {!! $nicuPayment->payment_type_badge !!}
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- تفاصيل السند المالية -->
                    <div class="text-center py-4 bg-light rounded-4 mb-4 border {{ $nicuPayment->payment_type === 'refund' ? 'border-danger-subtle bg-danger-subtle bg-opacity-10' : '' }}">
                        <span class="text-muted d-block small mb-1">
                            @if($nicuPayment->payment_type === 'refund')
                                المبلغ المصروف والمسترجع للمريض (نقداً)
                            @else
                                المبلغ المقبوض في الصندوق
                            @endif
                        </span>
                        <h1 class="display-5 fw-black {{ $nicuPayment->payment_type === 'refund' ? 'text-danger' : 'text-success' }} mb-1">
                            {{ $nicuPayment->payment_type === 'refund' ? '-' : '' }}{{ number_format($nicuPayment->amount) }} <span class="fs-4">د.ع</span>
                        </h1>
                        <span class="badge {{ $nicuPayment->payment_type === 'refund' ? 'bg-danger text-white' : 'bg-secondary' }} px-3 py-1">
                            {{ $nicuPayment->payment_type === 'refund' ? 'طريقة الصرف: ' : 'طريقة القبض: ' }}{{ $nicuPayment->payment_method === 'cash' ? 'نقدي (كاش)' : 'بطاقة دفع إلكتروني' }}
                        </span>
                    </div>

                    <!-- شبكة بيانات الحاضنة والطفل -->
                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="p-3 border rounded-3 bg-white h-100">
                                <span class="text-muted small d-block mb-1">اسم الطفل الخديج:</span>
                                <strong class="fs-6 text-dark"><i class="fas fa-baby text-primary me-1"></i>{{ $babyTitle }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 border rounded-3 bg-white h-100">
                                <span class="text-muted small d-block mb-1">الحاضنة / الغرفة:</span>
                                <strong class="fs-6 text-dark"><i class="fas fa-procedures text-info me-1"></i>{{ $incName }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 border rounded-3 bg-white h-100">
                                <span class="text-muted small d-block mb-1">الطبيب المشرف:</span>
                                <strong class="fs-6 text-dark"><i class="fas fa-user-md text-success me-1"></i>{{ $docName }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 border rounded-3 bg-white h-100">
                                <span class="text-muted small d-block mb-1">{{ $nicuPayment->payment_type === 'refund' ? 'الكاشير الصارف:' : 'الكاشير المستلم:' }}</span>
                                <strong class="fs-6 text-dark"><i class="fas fa-cash-register text-warning me-1"></i>{{ $nicuPayment->cashier?->name ?? 'الكاشير' }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 border rounded-3 bg-white h-100">
                                <span class="text-muted small d-block mb-1">{{ $nicuPayment->payment_type === 'refund' ? 'تاريخ ووقت الصرف:' : 'تاريخ ووقت القبض:' }}</span>
                                <strong>{{ $nicuPayment->paid_at ? $nicuPayment->paid_at->format('Y/m/d h:i A') : now()->format('Y/m/d h:i A') }}</strong>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 border rounded-3 bg-white h-100">
                                <span class="text-muted small d-block mb-1">رقم الهاتف / الملف:</span>
                                <strong>{{ $phone }} / #{{ $res?->patient_id ?? '-' }}</strong>
                            </div>
                        </div>
                    </div>

                    @if($nicuPayment->notes)
                        <div class="alert alert-light border py-2 px-3 mb-4 small">
                            <strong class="text-dark"><i class="fas fa-comment-alt me-1 text-muted"></i>ملاحظات السند:</strong>
                            {{ $nicuPayment->notes }}
                        </div>
                    @endif

                    <!-- موقف الحساب التراكمي للحاضنة لحظياً -->
                    @if(isset($stayDetails))
                        <div class="border rounded-4 p-3 bg-white">
                            <h6 class="fw-bold text-dark mb-3">
                                <i class="fas fa-calculator text-primary me-2"></i>
                                ملخص الموقف المالي التراكمي للطفل حتى هذه اللحظة:
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>بيان الحساب التراكمي</th>
                                            <th class="text-end">المبلغ (د.ع)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>أجور الحاضنة والإقامة السريرية</td>
                                            <td class="text-end">{{ number_format($stayDetails['total_service_cost']) }} د.ع</td>
                                        </tr>
                                        <tr>
                                            <td>الفحوصات المخبرية والتحاليل ({{ $stayDetails['medical_orders']->where('order_type', 'lab')->count() }} فحص)</td>
                                            <td class="text-end text-primary fw-bold">{{ number_format($stayDetails['medical_orders']->where('order_type', 'lab')->sum('price')) }} د.ع</td>
                                        </tr>
                                        <tr>
                                            <td>الفحوصات الإشعاعية والسونار ({{ $stayDetails['medical_orders']->where('order_type', 'radiology')->count() }} فحص)</td>
                                            <td class="text-end text-info fw-bold">{{ number_format($stayDetails['medical_orders']->where('order_type', 'radiology')->sum('price')) }} د.ع</td>
                                        </tr>
                                        @if($stayDetails['total_consultations_cost'] > 0)
                                            <tr>
                                                <td>استشارات الأطباء التخصصية</td>
                                                <td class="text-end">{{ number_format($stayDetails['total_consultations_cost']) }} د.ع</td>
                                            </tr>
                                        @endif
                                        <tr class="table-light">
                                            <td class="fw-bold">إجمالي المطالبة المستحقة على المريض:</td>
                                            <td class="text-end fw-bold fs-6">{{ number_format($stayDetails['patient_grand_total']) }} د.ع</td>
                                        </tr>
                                        <tr class="table-success text-success">
                                            <td class="fw-bold">إجمالي الأمانات والدفعات المسددة (شاملاً هذا الوصل):</td>
                                            <td class="text-end fw-bold fs-6">{{ number_format($stayDetails['total_paid']) }} د.ع</td>
                                        </tr>
                                        <tr class="{{ $stayDetails['net_balance'] > 0 ? 'table-warning text-dark' : 'table-light text-muted' }}">
                                            <td class="fw-bold">الرصيد المتبقي بذمة المريض:</td>
                                            <td class="text-end fw-bold fs-6 text-danger">{{ number_format($stayDetails['net_balance']) }} د.ع</td>
                                        </tr>
                                        @if($stayDetails['excess_deposit'] > 0)
                                            <tr class="table-info text-dark">
                                                <td class="fw-bold">فائض أمانات مستحق للأهل (رصيد دائن):</td>
                                                <td class="text-end fw-bold fs-6 text-success">+{{ number_format($stayDetails['excess_deposit']) }} د.ع</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                </div>

                <div class="card-footer bg-light py-3 px-4 d-flex justify-content-between align-items-center">
                    <span class="small text-muted">نظام إدارة المستشفى الذكي — قسم الخدج ورعاية حديثي الولادة</span>
                    <a href="{{ route('cashier.nicu.receipt.print', $nicuPayment->id) }}" class="btn btn-sm btn-dark" target="_blank">
                        <i class="fas fa-print me-1"></i> طباعة الوصل الحراري (78mm)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
