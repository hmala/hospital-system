<!-- resources/views/cashier/report.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- رأس الصفحة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-file-invoice-dollar me-2 text-success"></i>
                        تقرير حركات ومدفوعات الكاشير
                    </h2>
                    <p class="text-muted mb-0">سجل الوصولات المالية والمقبوضات اليومية والتفصيلية</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ request()->fullUrlWithQuery(['print' => '1']) }}" target="_blank" class="btn btn-primary shadow-sm">
                        <i class="fas fa-print me-1"></i> طباعة التقرير (A4)
                    </a>
                    <a href="{{ route('cashier.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> العودة للكاشير
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- كروت الإحصائيات السريعة -->
    <div class="row mb-4 g-3">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm text-white bg-success bg-gradient h-100" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small opacity-75 d-block mb-1">إجمالي المقبوضات</span>
                            <h3 class="mb-0 fw-bold">{{ number_format($totalAmount, 0) }} <small style="font-size: 0.9rem;">د.ع</small></h3>
                        </div>
                        <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm text-white bg-primary bg-gradient h-100" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small opacity-75 d-block mb-1">عدد الوصولات المصدرة</span>
                            <h3 class="mb-0 fw-bold">{{ number_format($totalCount) }}</h3>
                        </div>
                        <i class="fas fa-receipt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm text-white bg-info bg-gradient h-100" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small opacity-75 d-block mb-1">متوسط قيمة الوصل</span>
                            <h3 class="mb-0 fw-bold">{{ $totalCount > 0 ? number_format($totalAmount / $totalCount, 0) : 0 }} <small style="font-size: 0.9rem;">د.ع</small></h3>
                        </div>
                        <i class="fas fa-calculator fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm text-white bg-dark bg-gradient h-100" style="border-radius: 12px;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="small opacity-75 d-block mb-1">الفترة الزمنية</span>
                            <h6 class="mb-0 fw-bold" style="font-size: 0.95rem;">
                                @if($fromDate && $toDate)
                                    {{ $fromDate }} <br><span class="opacity-75">إلى</span> {{ $toDate }}
                                @else
                                    كافة السجلات
                                @endif
                            </h6>
                        </div>
                        <i class="fas fa-calendar-alt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- كرت الفلاتر والبحث -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('cashier.report') }}" id="reportFilterForm">
                <div class="row g-2 align-items-end">
                    <!-- من تاريخ -->
                    <div class="col-lg-3 col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-calendar-day me-1"></i>من تاريخ</label>
                        <input type="date" name="from_date" id="fromDateInput" class="form-control form-control-sm" value="{{ $fromDate }}">
                    </div>

                    <!-- إلى تاريخ -->
                    <div class="col-lg-3 col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-calendar-day me-1"></i>إلى تاريخ</label>
                        <input type="date" name="to_date" id="toDateInput" class="form-control form-control-sm" value="{{ $toDate }}">
                    </div>

                    <!-- القسم -->
                    <div class="col-lg-2 col-md-3 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-clinic-medical me-1"></i>القسم / الخدمة</label>
                        <select name="payment_type" class="form-select form-select-sm">
                            <option value="">جميع الأقسام</option>
                            @foreach(\App\Models\Payment::PAYMENT_TYPES as $typeKey => $typeLabel)
                                <option value="{{ $typeKey }}" {{ $paymentType == $typeKey ? 'selected' : '' }}>{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- البحث النصي -->
                    <div class="col-lg-2 col-md-6 col-sm-6">
                        <label class="form-label small fw-bold text-muted mb-1"><i class="fas fa-search me-1"></i>رقم الوصل أو المريض</label>
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="بحث بالاسم أو الرقم..." value="{{ $search }}">
                    </div>

                    <!-- أزرار الفلترة -->
                    <div class="col-lg-2 col-md-6 col-sm-6 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="fas fa-filter me-1"></i> تطبيق
                        </button>
                        <a href="{{ route('cashier.report') }}" class="btn btn-outline-secondary btn-sm" title="إعادة تعيين">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>

                <!-- أزرار التاريخ السريعة -->
                <div class="d-flex flex-wrap gap-2 mt-3 pt-2 border-top">
                    <span class="small text-muted align-self-center"><i class="fas fa-bolt text-warning me-1"></i>فترات سريعة:</span>
                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 quick-date" data-range="today">اليوم</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 quick-date" data-range="yesterday">أمس</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 quick-date" data-range="this_week">هذا الأسبوع</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 quick-date" data-range="this_month">هذا الشهر</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 quick-date" data-range="all">الكل</button>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول التقرير -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px;">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-list-alt me-2 text-primary"></i>تفاصيل المقبوضات والوصولات
            </h5>
            <span class="badge bg-light text-dark border">
                عرض {{ $payments->count() }} من أصل {{ $payments->total() }} وصل
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>رقم الوصل</th>
                            <th>التاريخ والوقت</th>
                            <th class="text-start">اسم المريض</th>
                            <th>المستخدم (الكاشير)</th>
                            <th>القسم / الخدمة</th>
                            <th>المبلغ المدفوع</th>
                            <th>طريقة الدفع</th>
                            <th style="width: 90px;">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td>{{ $loop->iteration + ($payments->currentPage() - 1) * $payments->perPage() }}</td>
                            <td>
                                <a href="{{ route('cashier.receipt', $payment) }}" class="fw-bold text-primary text-decoration-none">
                                    <span class="badge bg-light text-primary border border-primary px-2 py-1">
                                        {{ $payment->receipt_number ?: ('#REC-' . $payment->id) }}
                                    </span>
                                </a>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d') : $payment->created_at->format('Y-m-d') }}</span>
                                <br>
                                <small class="text-muted">{{ $payment->paid_at ? $payment->paid_at->format('H:i A') : $payment->created_at->format('H:i A') }}</small>
                            </td>
                            <td class="text-start">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px; font-weight: bold; min-width: 35px;">
                                        {{ mb_substr(optional($payment->patient)->user->name ?? optional($payment->emergency)->emergencyPatient?->name ?? optional($payment->emergency)->patient?->user?->name ?? 'م', 0, 1) }}
                                    </div>
                                    <div>
                                        <strong class="text-dark">{{ optional($payment->patient)->user->name ?? optional($payment->emergency)->emergencyPatient?->name ?? optional($payment->emergency)->patient?->user?->name ?? 'غير محدد' }}</strong>
                                        <br>
                                        <small class="text-muted">{{ optional($payment->patient)->user->phone ?? optional($payment->emergency)->emergencyPatient?->phone ?? optional($payment->emergency)->patient?->user?->phone ?? 'لا يوجد هاتف' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-user-circle me-1 text-secondary"></i>{{ optional($payment->cashier)->name ?? 'النظام' }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $typeBadge = match($payment->payment_type) {
                                        'appointment' => ['class' => 'bg-info bg-opacity-10 text-info border-info', 'icon' => 'fas fa-stethoscope', 'label' => 'استشارية / كشفية'],
                                        'lab' => ['class' => 'bg-warning bg-opacity-10 text-warning border-warning', 'icon' => 'fas fa-vial', 'label' => 'مختبر'],
                                        'radiology' => ['class' => 'bg-primary bg-opacity-10 text-primary border-primary', 'icon' => 'fas fa-x-ray', 'label' => 'أشعة'],
                                        'emergency' => ['class' => 'bg-danger bg-opacity-10 text-danger border-danger', 'icon' => 'fas fa-ambulance', 'label' => 'طوارئ'],
                                        'surgery' => ['class' => 'bg-purple bg-opacity-10 text-purple border-purple', 'icon' => 'fas fa-procedures', 'label' => 'عمليات'],
                                        'pharmacy' => ['class' => 'bg-success bg-opacity-10 text-success border-success', 'icon' => 'fas fa-pills', 'label' => 'صيدلية'],
                                        default => ['class' => 'bg-secondary bg-opacity-10 text-secondary border-secondary', 'icon' => 'fas fa-file-invoice', 'label' => $payment->payment_type_name]
                                    };
                                @endphp
                                <span class="badge {{ $typeBadge['class'] }} border mb-1">
                                    <i class="{{ $typeBadge['icon'] }} me-1"></i>{{ $typeBadge['label'] }}
                                </span>
                                <div class="fw-semibold text-dark small text-truncate" style="max-width: 220px;" title="{{ $payment->service_name }}">
                                    {{ $payment->service_name }}
                                </div>
                            </td>
                            <td>
                                <strong class="text-success fs-6">{{ number_format($payment->amount, 0) }}</strong>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">د.ع</small>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border">
                                    {{ $payment->payment_method_name }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('cashier.receipt', $payment) }}" class="btn btn-outline-primary" title="عرض الوصل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('cashier.receipt.print', $payment) }}" target="_blank" class="btn btn-outline-success" title="طباعة الوصل">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fas fa-receipt fa-3x mb-3 text-secondary opacity-50"></i>
                                <h5 class="fw-bold">لا توجد حركات أو وصولات مسجلة بهذه الفلاتر</h5>
                                <p class="small text-muted">جرب تغيير نطاق التواريخ أو تصفية الأقسام</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($payments->count() > 0)
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="6" class="text-start py-3 fs-6">
                                <i class="fas fa-sigma me-1"></i> إجمالي المقبوضات في هذا الجدول:
                            </td>
                            <td class="text-success fs-5 py-3">
                                {{ number_format($payments->sum('amount'), 0) }} <small class="fs-6">د.ع</small>
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            @if($payments->hasPages())
            <div class="d-flex justify-content-center p-3 border-top">
                {{ $payments->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fromInput = document.getElementById('fromDateInput');
    const toInput = document.getElementById('toDateInput');
    const form = document.getElementById('reportFilterForm');

    function formatDate(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    document.querySelectorAll('.quick-date').forEach(btn => {
        btn.addEventListener('click', function() {
            const range = this.dataset.range;
            const now = new Date();

            if (range === 'today') {
                fromInput.value = formatDate(now);
                toInput.value = formatDate(now);
            } else if (range === 'yesterday') {
                const yest = new Date(now);
                yest.setDate(yest.getDate() - 1);
                fromInput.value = formatDate(yest);
                toInput.value = formatDate(yest);
            } else if (range === 'this_week') {
                const day = now.getDay();
                const diff = now.getDate() - day + (day === 0 ? -6 : 1);
                const monday = new Date(now.setDate(diff));
                fromInput.value = formatDate(monday);
                toInput.value = formatDate(new Date());
            } else if (range === 'this_month') {
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                fromInput.value = formatDate(firstDay);
                toInput.value = formatDate(new Date());
            } else if (range === 'all') {
                fromInput.value = '';
                toInput.value = '';
            }

            form.submit();
        });
    });
});
</script>
@endsection
