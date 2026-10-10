@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="row mb-3 no-print">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="fw-bold mb-1"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>الحركات المالية للعيادات الاستشارية</h3>
                <p class="text-muted mb-0 small">مركز التقارير الشامل للدفعات، الاسترجاعات، وتغطيات الضمان الصحي.</p>
            </div>
            <a href="{{ route('consultant-availability.index') }}" class="btn btn-outline-secondary rounded-pill px-3 no-print">
                <i class="fas fa-arrow-left me-2"></i>العودة إلى توفر الأطباء
            </a>
        </div>
    </div>

    {{-- Financial Summary KPI Cards (Protected by Commission / Admin Permission) --}}
    @if(auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole('admin'))
    <div class="row g-3 mb-4 no-print">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 text-white h-100" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-white-50 small fw-bold">إجمالي المقبوضات</span>
                        <h4 class="fw-bold mb-0 mt-1">{{ number_format($totalReceived, 2) }} <small class="fs-6">IQD</small></h4>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-hand-holding-dollar fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 text-white h-100" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-white-50 small fw-bold">إجمالي المسترجعات</span>
                        <h4 class="fw-bold mb-0 mt-1">{{ number_format($totalRefunded, 2) }} <small class="fs-6">IQD</small></h4>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-undo-alt fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 text-white h-100" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-white-50 small fw-bold">صافي الإيراد المالي</span>
                        <h4 class="fw-bold mb-0 mt-1">{{ number_format($netTotal, 2) }} <small class="fs-6">IQD</small></h4>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-wallet fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Comprehensive Filter & Reporting Card --}}
    <div class="row mb-4">
        <div class="col-12 no-print">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-sliders-h me-2 text-primary"></i>خيارات الفلترة واستخراج التقارير
                        @if(isset($totalCount))
                            <span class="badge bg-light text-primary border ms-2">{{ number_format($totalCount) }} حركة مطابقة</span>
                        @endif
                    </h6>
                    {{-- Quick Date Range Shortcuts --}}
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2" onclick="setQuickDate('today')">اليوم</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2" onclick="setQuickDate('yesterday')">الأمس</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2" onclick="setQuickDate('this_month')">هذا الشهر</button>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <form method="GET" action="{{ route('consultant-availability.financial-movements') }}" id="filterForm">
                        <div class="row g-3">
                            {{-- 1. Search Query --}}
                            <div class="col-md-4 col-lg-4">
                                <label for="search" class="form-label small fw-bold text-muted">
                                    <i class="fas fa-search me-1 text-primary"></i>بحث سريع
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user-tag"></i></span>
                                    <input type="text" id="search" name="search" class="form-control bg-light border-start-0" placeholder="اسم مريض، هاتف، رقم إيصال..." value="{{ old('search', $search ?? '') }}">
                                </div>
                            </div>

                            {{-- 2. Movement Type --}}
                            <div class="col-md-4 col-lg-4">
                                <label for="filter_type" class="form-label small fw-bold text-muted">
                                    <i class="fas fa-exchange-alt me-1 text-primary"></i>نوع الحركة المالية
                                </label>
                                <select id="filter_type" name="filter_type" class="form-select bg-light">
                                    <option value="">جميع الحركات (قبض واسترجاع)</option>
                                    <option value="payment" {{ ($filterType ?? '') === 'payment' ? 'selected' : '' }}>حركات قبض فقط (+)</option>
                                    <option value="refund" {{ ($filterType ?? '') === 'refund' ? 'selected' : '' }}>حركات استرجاع فقط (-)</option>
                                    <option value="appointment_paid" {{ ($filterType ?? '') === 'appointment_paid' ? 'selected' : '' }}>المواعيد المدفوعة بالكامل</option>
                                    <option value="appointment_refunded" {{ ($filterType ?? '') === 'appointment_refunded' ? 'selected' : '' }}>المواعيد المسترجعة</option>
                                </select>
                            </div>

                            {{-- 3. Insurance Status --}}
                            <div class="col-md-4 col-lg-4">
                                <label for="insurance_status" class="form-label small fw-bold text-muted">
                                    <i class="fas fa-shield-alt me-1 text-primary"></i>الضمان الصحي
                                </label>
                                <select id="insurance_status" name="insurance_status" class="form-select bg-light">
                                    <option value="">الكل (مشمول وغير مشمول)</option>
                                    <option value="insurance" {{ ($insuranceStatus ?? '') === 'insurance' ? 'selected' : '' }}>مشمول بالضمان الصحي 🛡️</option>
                                    <option value="cash" {{ ($insuranceStatus ?? '') === 'cash' ? 'selected' : '' }}>كاش نقدي (غير مشمول) 💵</option>
                                </select>
                            </div>

                            {{-- 4. Payment Method --}}
                            <div class="col-md-4 col-lg-4">
                                <label for="payment_method" class="form-label small fw-bold text-muted">
                                    <i class="fas fa-credit-card me-1 text-primary"></i>طريقة الدفع
                                </label>
                                <select id="payment_method" name="payment_method" class="form-select bg-light">
                                    <option value="">جميع طرق الدفع</option>
                                    <option value="cash" {{ ($paymentMethod ?? '') === 'cash' ? 'selected' : '' }}>نقداً (كاش)</option>
                                    <option value="pos" {{ ($paymentMethod ?? '') === 'pos' ? 'selected' : '' }}>بطاقة إلكترونية (POS)</option>
                                    <option value="bank_transfer" {{ ($paymentMethod ?? '') === 'bank_transfer' ? 'selected' : '' }}>حوالة بنكية</option>
                                </select>
                            </div>

                            {{-- 5. From Date --}}
                            <div class="col-md-4 col-lg-4">
                                <label for="from_date" class="form-label small fw-bold text-muted">
                                    <i class="fas fa-calendar-alt me-1 text-primary"></i>من تاريخ
                                </label>
                                <input type="date" id="from_date" name="from_date" class="form-control bg-light" value="{{ old('from_date', $fromDate ?? '') }}">
                            </div>

                            {{-- 6. To Date --}}
                            <div class="col-md-4 col-lg-4">
                                <label for="to_date" class="form-label small fw-bold text-muted">
                                    <i class="fas fa-calendar-check me-1 text-primary"></i>إلى تاريخ
                                </label>
                                <input type="date" id="to_date" name="to_date" class="form-control bg-light" value="{{ old('to_date', $toDate ?? '') }}">
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                    <i class="fas fa-filter me-1"></i>تطبيق الفلترة
                                </button>
                                <a href="{{ route('consultant-availability.financial-movements') }}" class="btn btn-light rounded-pill px-3 border">
                                    <i class="fas fa-undo me-1 text-secondary"></i>تصفير
                                </a>
                            </div>

                            <div class="d-flex gap-2">
                                <a href="{{ route('consultant-availability.financial-movements.export', request()->only(['from_date', 'to_date', 'filter_type', 'search', 'payment_method', 'insurance_status'])) }}" class="btn btn-success rounded-pill px-4 shadow-sm">
                                    <i class="fas fa-file-excel me-1"></i>تصدير إكسل
                                </a>
                                <button type="button" onclick="window.print()" class="btn btn-info text-white rounded-pill px-4 shadow-sm">
                                    <i class="fas fa-print me-1"></i>طباعة التقرير
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0" id="print-area">
                    {{-- Official Print Header --}}
                    <div class="d-none d-print-block p-4 text-center border-bottom">
                        <h2 class="fw-bold mb-1">مستشفى الأهلي</h2>
                        <h4 class="text-secondary mb-2">تقرير الحركات المالية للعيادات الاستشارية</h4>
                        <div class="d-flex justify-content-center gap-4 text-muted small">
                            <span><strong>تاريخ الاستخراج:</strong> {{ now()->format('Y-m-d H:i') }}</span>
                            @if($fromDate || $toDate)
                                <span><strong>الفترة المحددة:</strong> من {{ $fromDate ?: 'البداية' }} إلى {{ $toDate ?: 'اليوم' }}</span>
                            @endif
                            @if($insuranceStatus)
                                <span><strong>الضمان:</strong> {{ $insuranceStatus === 'insurance' ? 'مشمول بالضمان' : 'كاش' }}</span>
                            @endif
                        </div>
                    </div>

                    @if($payments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">#</th>
                                        <th>التاريخ والوقت</th>
                                        <th>المريض</th>
                                        <th>الطبيب</th>
                                        <th>القسم</th>
                                        <th>الضمان الصحي</th>
                                        <th>المبلغ</th>
                                        @if(auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole('admin'))
                                        <th>حصة الطبيب</th>
                                        <th>حصة المستشفى</th>
                                        @endif
                                        <th>نوع الحركة</th>
                                        <th>طريقة الدفع</th>
                                        <th>رقم الإيصال</th>
                                        <th class="pe-3">الكاشير</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $payments->firstItem() + $loop->index }}</td>
                                            <td class="text-nowrap small">
                                                <i class="far fa-clock text-muted me-1"></i>{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : '-' }}
                                            </td>
                                            <td class="fw-bold text-dark">
                                                {{ optional(optional(optional($payment->appointment)->patient)->user)->name ?? optional(optional($payment->patient)->user)->name ?? '-' }}
                                            </td>
                                            <td>
                                                {{ optional(optional(optional($payment->appointment)->doctor)->user)->name ?? '-' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border">{{ optional(optional($payment->appointment)->department)->name ?? '-' }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $insurance = optional($payment->appointment)->insurance_type ?: optional($payment->payment)->insurance_type;
                                                @endphp
                                                @if($insurance)
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                                        <i class="fas fa-shield-alt me-1"></i>{{ $insurance }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-light text-muted border">
                                                        كاش نقدي
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="fw-bold {{ $payment->total_amount < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format(abs($payment->total_amount), 2) }} <small class="text-muted">IQD</small>
                                            </td>
                                            @if(auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole('admin'))
                                            <td class="text-success fw-semibold">{{ number_format($payment->doctor_share, 2) }} IQD</td>
                                            <td class="text-primary fw-semibold">{{ number_format($payment->hospital_share, 2) }} IQD</td>
                                            @endif
                                            <td>
                                                @if($payment->movement_type === 'refund' || $payment->total_amount < 0)
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                        <i class="fas fa-undo-alt me-1"></i>استرجاع
                                                    </span>
                                                @else
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        <i class="fas fa-check me-1"></i>قبض
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(($payment->payment_method ?? 'cash') === 'cash')
                                                    <span class="badge bg-light text-dark border"><i class="fas fa-money-bill-wave me-1 text-success"></i>نقداً</span>
                                                @elseif(($payment->payment_method ?? '') === 'pos')
                                                    <span class="badge bg-primary-subtle text-primary border"><i class="fas fa-credit-card me-1"></i>POS</span>
                                                @else
                                                    <span class="badge bg-light text-secondary border">{{ $payment->payment_method ?? '-' }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($payment->receipt_number && $payment->payment_id)
                                                    <a href="{{ route('cashier.receipt.print', $payment->payment_id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0 no-print" target="_blank" title="طباعة الإيصال">
                                                        <i class="fas fa-print me-1"></i>{{ $payment->receipt_number }}
                                                    </a>
                                                    <span class="d-none d-print-inline fw-semibold font-monospace">{{ $payment->receipt_number }}</span>
                                                @else
                                                    <span class="font-monospace text-muted">{{ $payment->receipt_number ?? '-' }}</span>
                                                @endif
                                            </td>
                                            <td class="pe-3 small text-muted">{{ optional($payment->cashier)->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top no-print d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="text-muted small">عرض {{ $payments->count() }} من أصل {{ $payments->total() }} حركة مسجلة</span>
                            {{ $payments->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="bg-light rounded-circle d-inline-flex p-4 mb-3 text-muted">
                                <i class="fas fa-search-dollar fa-3x"></i>
                            </div>
                            <h5 class="fw-bold text-dark">لا توجد حركات مالية مطابقة للفلترة</h5>
                            <p class="text-muted small">جرب تغيير الفترة الزمنية أو خيارات البحث والضمان للحصول على نتائج.</p>
                            <a href="{{ route('consultant-availability.financial-movements') }}" class="btn btn-outline-primary rounded-pill px-4">
                                <i class="fas fa-redo me-1"></i>عرض كل حركات اليوم
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function setQuickDate(type) {
    const fromInput = document.getElementById('from_date');
    const toInput = document.getElementById('to_date');
    const today = new Date();
    
    function formatDate(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    if (type === 'today') {
        const todayStr = formatDate(today);
        fromInput.value = todayStr;
        toInput.value = todayStr;
    } else if (type === 'yesterday') {
        const yest = new Date();
        yest.setDate(yest.getDate() - 1);
        const yestStr = formatDate(yest);
        fromInput.value = yestStr;
        toInput.value = yestStr;
    } else if (type === 'this_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        fromInput.value = formatDate(firstDay);
        toInput.value = formatDate(today);
    }
    
    document.getElementById('filterForm').submit();
}
</script>
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
