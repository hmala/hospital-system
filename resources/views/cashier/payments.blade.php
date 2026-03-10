@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-receipt me-2 text-success"></i>سجل الفواتير المدفوعة</h2>
            <div>
                <button class="btn btn-primary me-2" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>طباعة
                </button>
                <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة إلى الكاشير
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if($payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>التاريخ</th>
                                        <th>المريض</th>
                                        <th>المبلغ</th>
                                        <th>طريقة الدفع</th>
                                        <th>شاملة</th>
                                        <th>الوصف</th>
                                        <th>الكاشير</th>
                                        <th class="no-print">إيصال</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>{{ $payments->firstItem() + $loop->index }}</td>
                                            <td>{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : '-' }}</td>
                                            <td>{{ optional(optional($payment->patient)->user)->name ?? '-' }}</td>
                                            <td class="text-success">{{ number_format($payment->amount,2) }} IQD</td>
                                            <td>
                                                @if($payment->is_inclusive)
                                                    <span class="badge bg-success">نعم</span>
                                                @else
                                                    <span class="badge bg-secondary">لا</span>
                                                @endif
                                            </td>
                                            <td>{{ $payment->description }}</td>
                                            <td>{{ optional($payment->cashier)->name ?? '-' }}</td>
                                            <td class="no-print">
                                                <a href="{{ route('cashier.receipt', $payment->id) }}?html=1" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $payments->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <p class="text-muted">لا توجد دفعات مسجلة حالياً</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .no-print, .no-print * {
        display: none !important;
    }
}
</style>
@endpush

@endsection
