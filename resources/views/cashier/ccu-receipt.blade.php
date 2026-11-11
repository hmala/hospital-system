@extends('layouts.app')

@section('title', 'إيصال استلام العناية المركزة - ' . $ccuPayment->receipt_number)

@section('content')
<div class="container py-4" dir="rtl">
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-3">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        </div>
    @endif

    <div class="card border-0 shadow rounded-4 mx-auto" style="max-width: 760px;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-receipt text-success me-2"></i>
                    إيصال مالي - وحدة العناية المركزة للكبار (CCU)
                </h5>
                <small class="text-muted font-monospace">{{ $ccuPayment->receipt_number }}</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('cashier.ccu.receipt.print', $ccuPayment->id) }}" target="_blank" class="btn btn-dark btn-sm fw-bold">
                    <i class="fas fa-print me-1"></i> طباعة الوصل
                </a>
                <a href="{{ route('cashier.ccu.payment.form', $ccuPayment->icu_admission_id) }}" class="btn btn-outline-primary btn-sm">
                    العودة للكشف
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 mb-4 small">
                <div class="col-6">
                    <div class="text-muted">اسم المريض</div>
                    <div class="fw-bold fs-6">{{ $ccuPayment->admission?->patient?->user?->name ?? 'مريض' }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted">رقم الإضبارة والسرير</div>
                    <div class="fw-bold">#{{ $ccuPayment->icu_admission_id }} — سرير {{ $ccuPayment->admission?->bed?->bed_number }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted">تاريخ ووقت الحركة</div>
                    <div class="fw-bold">{{ optional($ccuPayment->paid_at)->format('Y-m-d H:i') }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted">أمين الصندوق</div>
                    <div class="fw-bold">{{ $ccuPayment->cashier?->name ?? 'النظام' }}</div>
                </div>
            </div>

            <div class="p-4 rounded-4 text-center mb-4 {{ $ccuPayment->payment_type === 'refund' ? 'bg-danger-subtle border border-danger-subtle' : 'bg-success-subtle border border-success-subtle' }}">
                <div class="small fw-bold mb-1">{{ \App\Models\CcuPayment::PAYMENT_TYPES[$ccuPayment->payment_type] ?? $ccuPayment->payment_type }}</div>
                <h2 class="fw-bold font-monospace mb-1 {{ $ccuPayment->payment_type === 'refund' ? 'text-danger' : 'text-success' }}">
                    {{ number_format($ccuPayment->amount) }} د.ع
                </h2>
                <div class="small text-muted">طريقة الدفع: {{ $ccuPayment->payment_method === 'card' ? 'بطاقة إلكترونية' : 'نقدي (كاش)' }}</div>
            </div>

            <div class="p-3 bg-light rounded border small mb-3">
                <strong>البيان:</strong> {{ $ccuPayment->notes }}
            </div>

            <div class="row g-2 text-center small">
                <div class="col-4">
                    <div class="p-2 border rounded">
                        <div class="text-muted">حصة المريض الكلية</div>
                        <strong class="font-monospace">{{ number_format($stayDetails['patient_grand_total']) }} د.ع</strong>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 border rounded">
                        <div class="text-muted">إجمالي المدفوع</div>
                        <strong class="text-success font-monospace">{{ number_format($stayDetails['total_paid']) }} د.ع</strong>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 border rounded">
                        <div class="text-muted">المتبقي بذمة المريض</div>
                        <strong class="text-danger font-monospace">{{ number_format($stayDetails['net_balance']) }} د.ع</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
