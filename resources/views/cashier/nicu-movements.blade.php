@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-baby me-2 text-info"></i>الحركات المالية لقسم الخدج والحاضنات</h2>
                <p class="text-muted mb-0">سجل المقبوضات، الأمانات، الدفعات تحت الحساب، والتسويات والمبالغ المستردة لحالات الحواضن.</p>
            </div>
            <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>العودة إلى الكاشير الرئيسية
            </a>
        </div>
    </div>

    <div class="row mb-4">
        {{-- فلترة البحث --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('cashier.nicu.financial-movements') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="from_date" class="form-label">من تاريخ</label>
                            <input type="date" id="from_date" name="from_date" class="form-control" value="{{ old('from_date', $fromDate) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="to_date" class="form-label">إلى تاريخ</label>
                            <input type="date" id="to_date" name="to_date" class="form-control" value="{{ old('to_date', $toDate) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="payment_type" class="form-label">نوع الحركة</label>
                            <select id="payment_type" name="payment_type" class="form-select">
                                <option value="">كل الحركات</option>
                                <option value="deposit" {{ $paymentType === 'deposit' ? 'selected' : '' }}>أمانات وسلفة دخول</option>
                                <option value="interim" {{ $paymentType === 'interim' ? 'selected' : '' }}>دفعة تحت الحساب</option>
                                <option value="final_settlement" {{ $paymentType === 'final_settlement' ? 'selected' : '' }}>تسوية نهائية عند التخريج</option>
                                <option value="refund" {{ $paymentType === 'refund' ? 'selected' : '' }}>استرجاع فائض أمانات</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="payment_method" class="form-label">طريقة الدفع</label>
                            <select id="payment_method" name="payment_method" class="form-select">
                                <option value="">كل الطرق</option>
                                <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>نقدي (كاش)</option>
                                <option value="card" {{ $paymentMethod === 'card' ? 'selected' : '' }}>بطاقة إلكترونية</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2 justify-content-end">
                            <button type="submit" class="btn btn-info text-white px-4">
                                <i class="fas fa-filter me-2"></i>تصفية
                            </button>
                            <a href="{{ route('cashier.nicu.financial-movements') }}" class="btn btn-outline-secondary px-3">
                                <i class="fas fa-redo me-2"></i>مسح
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- بطاقات الملخص المالي --}}
        <div class="col-lg-4">
            <div class="row g-3">
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-white bg-success">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1" style="font-size: 0.85rem;">المستلم والأمانات (الخدج)</h6>
                                    <p class="fs-5 mb-0 fw-bold">{{ number_format($totalReceived, 0) }} د.ع</p>
                                </div>
                                <i class="fas fa-hand-holding-usd fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-white bg-danger">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1" style="font-size: 0.85rem;">المسترجع وفائض الأمانات</h6>
                                    <p class="fs-5 mb-0 fw-bold">{{ number_format($totalRefunded, 0) }} د.ع</p>
                                </div>
                                <i class="fas fa-undo-alt fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-white bg-info">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1" style="font-size: 0.85rem;">الصافي الفعلي المحصل</h6>
                                    <p class="fs-5 mb-0 fw-bold">{{ number_format($netTotal, 0) }} د.ع</p>
                                </div>
                                <i class="fas fa-calculator fa-2x opacity-50"></i>
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
                                        <th>التاريخ والوقت</th>
                                        <th>المولود / الحاضنة</th>
                                        <th>نوع الحركة</th>
                                        <th>المبلغ</th>
                                        <th>طريقة الدفع</th>
                                        <th>رقم الإيصال</th>
                                        <th>أمين الصندوق</th>
                                        <th>البيان والملاحظات</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>{{ $payments->firstItem() + $loop->index }}</td>
                                            <td>{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : '-' }}</td>
                                            <td>
                                                <div class="fw-bold text-dark">
                                                    {{ $payment->reservation?->baby_title ?? 'طفل غير محدد' }}
                                                </div>
                                                <small class="text-muted">
                                                    الأم: {{ optional(optional($payment->reservation)->patient?->user)->name ?? 'غير مسجل' }}
                                                    @if($payment->reservation?->incubator)
                                                        <span class="badge bg-light text-dark border ms-1">حاضنة {{ $payment->reservation->incubator->incubator_number }}</span>
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                {!! $payment->payment_type_badge !!}
                                            </td>
                                            <td class="fw-bold {{ $payment->payment_type === \App\Models\NicuPayment::TYPE_REFUND ? 'text-danger' : 'text-success' }}">
                                                {{ $payment->payment_type === \App\Models\NicuPayment::TYPE_REFUND ? '-' : '+' }}{{ number_format($payment->amount, 0) }} د.ع
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    {{ \App\Models\NicuPayment::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method }}
                                                </span>
                                            </td>
                                            <td><code>{{ $payment->receipt_number }}</code></td>
                                            <td>{{ optional($payment->cashier)->name ?? '—' }}</td>
                                            <td><span class="small text-muted">{{ $payment->notes ?? '—' }}</span></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('cashier.nicu.receipt', $payment) }}" class="btn btn-outline-info" title="عرض السند">
                                                        <i class="fas fa-eye me-1"></i>السند
                                                    </a>
                                                    <a href="{{ route('cashier.nicu.receipt.print', $payment) }}" target="_blank" class="btn btn-outline-secondary" title="طباعة حرارية">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    @if($payment->incubator_reservation_id)
                                                        <a href="{{ route('nicu.statement', $payment->incubator_reservation_id) }}" class="btn btn-outline-primary" title="كشف حساب الطفل">
                                                            <i class="fas fa-file-invoice"></i>
                                                        </a>
                                                    @endif
                                                </div>
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
                            <i class="fas fa-baby fa-3x text-muted mb-3 opacity-50"></i>
                            <p class="text-muted">لا توجد حركات مالية مسجلة لقسم الخدج ضمن الفترة المحددة.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
