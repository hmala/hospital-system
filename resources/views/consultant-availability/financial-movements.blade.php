@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-chart-line me-2 text-primary"></i>الحركات المالية للعيادات الاستشارية</h2>
                <p class="text-muted mb-0">سجل الدفعات والاسترجاعات للمواعيد الاستشارية.</p>
            </div>
            <a href="{{ route('consultant-availability.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>العودة إلى توفر الأطباء
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('consultant-availability.financial-movements') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="from_date" class="form-label">من تاريخ</label>
                            <input type="date" id="from_date" name="from_date" class="form-control" value="{{ old('from_date', $fromDate) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="to_date" class="form-label">إلى تاريخ</label>
                            <input type="date" id="to_date" name="to_date" class="form-control" value="{{ old('to_date', $toDate) }}">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">تصفية</button>
                            <a href="{{ route('consultant-availability.financial-movements') }}" class="btn btn-outline-secondary w-100">مسح</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">المستلم</h6>
                                    <p class="fs-5 mb-0">{{ number_format($totalReceived, 2) }} IQD</p>
                                </div>
                                <i class="fas fa-hand-holding-dollar fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-white bg-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">المسترجع</h6>
                                    <p class="fs-5 mb-0">{{ number_format($totalRefunded, 2) }} IQD</p>
                                </div>
                                <i class="fas fa-undo-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">الصافي</h6>
                                    <p class="fs-5 mb-0">{{ number_format($netTotal, 2) }} IQD</p>
                                </div>
                                <i class="fas fa-calculator fa-2x"></i>
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
                <div class="card-body">
                    @if($payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>التاريخ</th>
                                        <th>المريض</th>
                                        <th>الطبيب</th>
                                        <th>القسم</th>
                                        <th>المبلغ</th>
                                        <th>نوع الحركة</th>
                                        <th>طريقة الدفع</th>
                                        <th>الوصف</th>
                                        <th>رقم الإيصال</th>
                                        <th>الجهة / الكاشير</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>{{ $payments->firstItem() + $loop->index }}</td>
                                            <td>{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : '-' }}</td>
                                            <td>{{ optional(optional(optional($payment->appointment)->patient)->user)->name ?? optional(optional($payment->patient)->user)->name ?? '-' }}</td>
                                            <td>{{ optional(optional(optional($payment->appointment)->doctor)->user)->name ?? '-' }}</td>
                                            <td>{{ optional(optional($payment->appointment)->department)->name ?? '-' }}</td>
                                            <td class="fw-bold {{ $payment->amount < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format(abs($payment->amount), 2) }} IQD
                                            </td>
                                            <td>
                                                @if($payment->amount < 0)
                                                    <span class="badge bg-danger">استرجاع</span>
                                                @else
                                                    <span class="badge bg-success">قبض</span>
                                                @endif
                                            </td>
                                            <td>{{ $payment->payment_method_name ?? ($payment->payment_method ?: '-') }}</td>
                                            <td>{{ $payment->description ?? '-' }}</td>
                                            <td>{{ $payment->receipt_number ?? '-' }}</td>
                                            <td>
                                                @if($payment->amount < 0)
                                                    {{ optional($payment->appointment)->cancelled_by ?? optional($payment->appointment)->cancellation_reason ?? 'غير معروف' }}
                                                @else
                                                    {{ optional($payment->cashier)->name ?? '-' }}
                                                @endif
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
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد حركات مالية مسجلة ضمن الفترة الحالية.</p>
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
