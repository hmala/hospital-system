@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- الترويسة وشريط العمليات -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h3 fw-bold text-success mb-1">
                <i class="fas fa-cash-register me-2"></i>كاشير ونقطة تحصيل مركز العيون
            </h2>
            <p class="text-muted mb-0 small">تحصيل رسوم الكشوفات، فحوصات الأجهزة (OCT)، العمليات والعدسات، والإغلاق المالي اليومي</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('eye.reception.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-users me-1"></i>طابور العيون
            </a>
            <form action="{{ route('eye.cashier.dailyReconciliation') }}" method="POST" onsubmit="return confirm('هل أنت متأكد من إغلاق وترحيل صندوق مركز العيون لليوم إلى حسابات المستشفى الرئيسية؟');">
                @csrf
                <button type="submit" class="btn btn-success fw-bold">
                    <i class="fas fa-file-invoice-dollar me-1"></i>الإغلاق المالي وترحيل الصندوق لليوم
                </button>
            </form>
        </div>
    </div>

    <!-- بطاقات الإحصائيات المالية لليوم -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-success text-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">المقبوضات النقدية لليوم</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ number_format($stats['total_collected']) }} <small class="fs-6">د.ع</small></h3>
                        </div>
                        <i class="fas fa-coins fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-info text-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">مستحقات التأمين الصحي</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ number_format($stats['insurance_due']) }} <small class="fs-6">د.ع</small></h3>
                        </div>
                        <i class="fas fa-file-contract fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-warning text-dark h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-dark small fw-semibold">فواتير معلقة بانتظار الدفع</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ $stats['pending_count'] }}</h3>
                        </div>
                        <i class="fas fa-hourglass-half fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-primary text-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">فواتير تم تحصيلها اليوم</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ $stats['paid_count'] }}</h3>
                        </div>
                        <i class="fas fa-receipt fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط البحث والفلترة -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('eye.cashier.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="ابحث برقم الفاتورة، اسم المريض، أو الهاتف..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">-- كل الحالات --</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>معلقة (بانتظار الدفع)</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>مدفوعة بالكامل</option>
                        <option value="partially_paid" {{ request('status') == 'partially_paid' ? 'selected' : '' }}>مدفوعة جزئياً</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="payment_method" class="form-select">
                        <option value="">-- طريقة الدفع --</option>
                        <option value="cash" {{ request('payment_method') == 'cash' ? 'selected' : '' }}>نقدي (Cash)</option>
                        <option value="card" {{ request('payment_method') == 'card' ? 'selected' : '' }}>بطاقة دفع (Card)</option>
                        <option value="insurance" {{ request('payment_method') == 'insurance' ? 'selected' : '' }}>تأمين صحي</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-success flex-grow-1">بحث</button>
                    <a href="{{ route('eye.cashier.index') }}" class="btn btn-outline-secondary"><i class="fas fa-undo"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول فواتير كاشير العيون -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-list me-2 text-success"></i>سجل فواتير اليوم (كاشير العيون)</h5>
            <span class="badge bg-light text-dark border">الإجمالي: {{ $invoices->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>المريض</th>
                        <th>الخدمة / الموعد</th>
                        <th>إجمالي المبلغ</th>
                        <th>حصة المريض</th>
                        <th>المدفوع</th>
                        <th>حالة السداد</th>
                        <th>الترحيل للخزينة</th>
                        <th class="text-center" style="width: 140px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr>
                        <td>
                            <span class="fw-bold font-monospace text-dark">{{ $inv->invoice_number }}</span>
                            <div class="small text-muted">{{ $inv->created_at->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-primary">{{ $inv->patient->name }}</div>
                            <div class="small text-muted">{{ $inv->patient->phone ?? 'بدون هاتف' }}</div>
                        </td>
                        <td>
                            @if($inv->appointment)
                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                    {{ $inv->appointment->visit_type_arabic }} (#{{ $inv->appointment->queue_number }})
                                </span>
                            @else
                                <span class="text-muted">خدمة مباشرة</span>
                            @endif
                            <div class="small text-muted">{{ $inv->items->pluck('description')->join(', ') }}</div>
                        </td>
                        <td>
                            <span class="fw-bold text-dark">{{ number_format($inv->total_amount) }} د.ع</span>
                        </td>
                        <td>
                            <span class="fw-bold text-primary">{{ number_format($inv->patient_share) }} د.ع</span>
                            @if($inv->insurance_share > 0)
                                <div class="small text-success">تأمين: {{ number_format($inv->insurance_share) }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-success">{{ number_format($inv->paid_amount) }} د.ع</span>
                        </td>
                        <td>
                            @if($inv->status == 'paid')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="fas fa-check-circle me-1"></i>مدفوع
                                </span>
                            @elseif($inv->status == 'pending')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                    <i class="fas fa-clock me-1"></i>بانتظار الدفع
                                </span>
                            @else
                                <span class="badge bg-secondary">{{ $inv->status_arabic }}</span>
                            @endif
                        </td>
                        <td>
                            @if($inv->reconciled_with_hospital)
                                <span class="badge bg-success-subtle text-success border"><i class="fas fa-lock me-1"></i>مُرحل للمستشفى</span>
                            @else
                                <span class="badge bg-light text-muted border">قيد الصندوق</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('eye.cashier.show', $inv) }}" class="btn btn-outline-success" title="تحصيل / تفاصيل">
                                    <i class="fas fa-money-bill-wave me-1"></i>سداد
                                </a>
                                @if($inv->status == 'paid')
                                <a href="{{ route('eye.cashier.printReceipt', $inv) }}" class="btn btn-outline-secondary" target="_blank" title="طباعة الوصل">
                                    <i class="fas fa-print"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                            <div class="h5">لا توجد فواتير كاشير مسجلة اليوم</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
