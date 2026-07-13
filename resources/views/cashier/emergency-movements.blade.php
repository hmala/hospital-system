@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-ambulance me-2 text-danger"></i>الحركات المالية لقسم الطوارئ</h2>
                <p class="text-muted mb-0">سجل المقبوضات والمبالغ المستردة لحالات الطوارئ.</p>
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
                    <form method="GET" action="{{ route('cashier.emergency.financial-movements') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="from_date" class="form-label">من تاريخ</label>
                            <input type="date" id="from_date" name="from_date" class="form-control" value="{{ old('from_date', $fromDate) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="to_date" class="form-label">إلى تاريخ</label>
                            <input type="date" id="to_date" name="to_date" class="form-control" value="{{ old('to_date', $toDate) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="payment_method" class="form-label">طريقة الدفع</label>
                            <select id="payment_method" name="payment_method" class="form-select">
                                <option value="">كل الطرق</option>
                                <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>نقدي</option>
                                <option value="card" {{ $paymentMethod === 'card' ? 'selected' : '' }}>بطاقة</option>
                                <option value="insurance" {{ $paymentMethod === 'insurance' ? 'selected' : '' }}>تأمين</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-filter me-2"></i>تصفية
                            </button>
                            <a href="{{ route('cashier.emergency.financial-movements') }}" class="btn btn-outline-secondary w-100">
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
                                    <h6 class="mb-1" style="font-size: 0.85rem;">المستلم (الطوارئ)</h6>
                                    <p class="fs-5 mb-0 fw-bold">{{ number_format($totalReceived, 0) }} د.ع</p>
                                </div>
                                <i class="fas fa-hand-holding-dollar fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm text-white bg-danger">
                        <div class="card-body py-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1" style="font-size: 0.85rem;">المسترجع (الطوارئ)</h6>
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
                                    <h6 class="mb-1" style="font-size: 0.85rem;">الصافي (الطوارئ)</h6>
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
                                        <th>المريض</th>
                                        <th>تفاصيل بنود الدفع</th>
                                        <th>المبلغ المدفوع</th>
                                        <th>نوع الحركة</th>
                                        <th>طريقة الدفع</th>
                                        <th>رقم الإيصال</th>
                                        <th>أمين الصندوق</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>{{ $payments->firstItem() + $loop->index }}</td>
                                            <td>{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : '-' }}</td>
                                            <td>
                                                <div class="fw-bold">{{ optional(optional($payment->emergency)->patient->user)->name ?? optional($payment->patient->user)->name ?? 'غير معروف' }}</div>
                                                <small class="text-muted">ID: #{{ $payment->patient_id }}</small>
                                            </td>
                                            <td>
                                                @if($payment->emergency)
                                                    <ul class="list-unstyled mb-0 small">
                                                        @if($payment->emergency->services->isNotEmpty())
                                                            <li><i class="fas fa-hand-holding-medical text-primary me-1"></i>خدمات: {{ $payment->emergency->services->pluck('name')->implode(', ') }}</li>
                                                        @endif
                                                        @if($payment->emergency->labRequests->isNotEmpty())
                                                            @php 
                                                                $testNames = $payment->emergency->labRequests->flatMap->labTests->pluck('name')->filter()->unique();
                                                            @endphp
                                                            @if($testNames->isNotEmpty())
                                                                <li><i class="fas fa-flask text-success me-1"></i>تحاليل: {{ $testNames->implode(', ') }}</li>
                                                            @endif
                                                        @endif
                                                        @if($payment->emergency->radiologyRequests->isNotEmpty())
                                                            @php 
                                                                $radNames = $payment->emergency->radiologyRequests->flatMap->radiologyTypes->pluck('name')->filter()->unique();
                                                            @endphp
                                                            @if($radNames->isNotEmpty())
                                                                <li><i class="fas fa-x-ray text-info me-1"></i>أشعة: {{ $radNames->implode(', ') }}</li>
                                                            @endif
                                                        @endif
                                                        @if($payment->emergency->doctor_follow_up_fee > 0)
                                                            <li><i class="fas fa-user-md text-warning me-1"></i>متابعة طبيب ({{ number_format($payment->emergency->doctor_follow_up_fee, 0) }} د.ع)</li>
                                                        @endif
                                                    </ul>
                                                @else
                                                    <span class="text-muted">{{ $payment->description ?? '—' }}</span>
                                                @endif
                                            </td>
                                            <td class="fw-bold {{ $payment->amount < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format(abs($payment->amount), 0) }} د.ع
                                            </td>
                                            <td>
                                                @if($payment->amount < 0)
                                                    <span class="badge bg-danger">استرجاع</span>
                                                @else
                                                    <span class="badge bg-success">قبض</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    {{ $payment->payment_method_name }}
                                                </span>
                                            </td>
                                            <td><code>{{ $payment->receipt_number ?? '—' }}</code></td>
                                            <td>{{ optional($payment->cashier)->name ?? '—' }}</td>
                                            <td>
                                                <a href="{{ route('cashier.receipt', $payment) }}" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-print me-1"></i>عرض الإيصال
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
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد حركات مالية مسجلة لقسم الطوارئ ضمن الفترة المحددة.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
