@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- ترويسة الصفحة ومعلومات الطبيب --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 bg-white p-3 rounded-4 shadow-sm border">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 55px; height: 55px;">
                        <i class="fas fa-user-md fa-2x"></i>
                    </div>
                    <div>
                        <h2 class="h4 fw-bold text-dark mb-1">
                            كشف حساب الجراح: د. {{ $doctor->user?->name ?? 'غير محدد' }}
                        </h2>
                        <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 rounded-pill">
                                <i class="fas fa-stethoscope me-1"></i> {{ $doctor->specialization ?? 'جراحة' }}
                            </span>
                            <span>•</span>
                            <span>{{ $doctor->department?->name ?? 'قسم العمليات الجراحية' }}</span>
                            <span>•</span>
                            <span>هاتف: {{ $doctor->user?->phone ?? '---' }}</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('cashier.surgeries.doctor-accounts') }}" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                        <i class="fas fa-arrow-left me-1"></i> العودة لقائمة الجراحين
                    </a>
                    <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-outline-danger rounded-pill px-3 shadow-sm">
                        <i class="fas fa-procedures me-1"></i> كاشير العمليات
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- بطاقات المؤشرات المالية الخاصة بالجراح --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-success text-white">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-white-50 small fw-bold d-block mb-1">الرصيد الحالي المتبقي للطبيب</span>
                        <h2 class="mb-0 fw-bold">{{ number_format($stats['balance'], 0) }}</h2>
                        <small class="text-white-50">د.ع بذمة المستشفى</small>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 text-white">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">العمليات المنفذة</span>
                    <h3 class="mb-0 fw-bold text-dark">{{ number_format($stats['surgeries_count']) }}</h3>
                    <small class="text-muted">عملية مسددة</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">الأتعاب الاسمية</span>
                    <h4 class="mb-0 fw-bold text-secondary">{{ number_format($stats['gross_fees'], 0) }}</h4>
                    <small class="text-muted">د.ع قبل الاستقطاع</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">استقطاعات الباقة</span>
                    <h4 class="mb-0 fw-bold text-danger">-{{ number_format($stats['package_deductions'], 0) }}</h4>
                    <small class="text-danger">د.ع لصالح المستشفى</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">إجمالي المصروف</span>
                    <h4 class="mb-0 fw-bold text-success">{{ number_format($stats['total_paid'], 0) }}</h4>
                    <small class="text-success">د.ع مسدد فعلياً</small>
                </div>
            </div>
        </div>
    </div>

    {{-- صندوق صرف دفعة / تصفية رصيد للجراح --}}
    @if($stats['balance'] > 0)
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-light border">
            <div class="card-body p-3">
                <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                    <i class="fas fa-hand-holding-usd text-success"></i>
                    <span>تسجيل سند صرف دفعة من مستحقات الجراح</span>
                </h6>
                <form action="{{ route('cashier.surgeries.doctor-payout', $doctor) }}" method="POST" id="payoutForm" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-bold text-secondary mb-1">المبلغ المراد صرفه (د.ع)</label>
                        <input type="number" name="amount" class="form-control form-control-sm rounded-3 fw-bold font-monospace" max="{{ $stats['balance'] }}" min="1" step="500" value="{{ $stats['balance'] }}" required>
                        <small class="text-muted" style="font-size: 0.7rem;">الحد الأقصى المتاح: {{ number_format($stats['balance'], 0) }} د.ع</small>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-bold text-secondary mb-1">البيان والملاحظات</label>
                        <input type="text" name="notes" class="form-control form-control-sm rounded-3" placeholder="مثال: صرف دفعة نقدية من أتعاب العمليات الجراحية عن شهر...">
                    </div>
                    <div class="col-12 col-md-3">
                        <button type="submit" class="btn btn-success btn-sm w-100 rounded-3 fw-bold py-1-5 shadow-sm">
                            <i class="fas fa-check-circle me-1"></i> تسجيل واعتماد سند الصرف
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- جدول العمليات الجراحية المفصل الخاص بالجراح --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="fas fa-procedures text-danger"></i>
                <span>سجل العمليات الجراحية واستقطاعاتها وتصفيتها</span>
            </h5>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                عدد العمليات: {{ $surgeries->total() }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small fw-bold">
                        <tr>
                            <th class="ps-4"># / العملية</th>
                            <th>التاريخ والإيصال</th>
                            <th>المريض</th>
                            <th>نوع الحساب</th>
                            <th class="text-end">أتعاب الجراحة الاسمية</th>
                            <th class="text-end">استقطاعات الباقة (المستشفى)</th>
                            <th class="text-end">صافي مستحق الجراح</th>
                            <th class="text-center">سداد الكاشير</th>
                            <th class="text-center pe-4">تصفية المستحق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($surgeries as $surgery)
                            @php
                                $patient = $surgery->patient;
                                $payment = $surgery->payments->first();
                                $isIns = in_array($surgery->insurance_type, ['hi', 'moi', 'health_insurance']);
                                $grossFee = (float) (($surgery->surgery_fee ?? 0) + $surgery->additionalOperations->sum('fee'));
                                $deductions = (float) ($payment?->doctor_deductions_amount ?? 0);
                                $netFee = $isIns ? $grossFee : max(0.0, $grossFee - $deductions);
                                $due = $payment ? \App\Models\DoctorDue::where('payment_id', $payment->id)->where('doctor_id', $doctor->id)->first() : null;
                                $isSettled = $due && $due->status === 'paid';
                                $itemsSnapshot = $payment && is_array($payment->included_package_items) 
                                    ? $payment->included_package_items 
                                    : ($payment ? (json_decode($payment->included_package_items, true) ?? []) : []);
                            @endphp
                            <tr>
                                {{-- رقم العملية ونوعها --}}
                                <td class="ps-4">
                                    <div class="fw-bold text-dark fs-6">#{{ $surgery->id }} - {{ $surgery->surgery_type }}</div>
                                    @if($surgery->room)
                                        <span class="badge bg-light text-dark border mt-1" style="font-size: 0.7rem;">
                                            غرفة {{ $surgery->room->room_number }}
                                        </span>
                                    @endif
                                </td>

                                {{-- التاريخ والإيصال --}}
                                <td>
                                    <div class="fw-bold text-secondary">{{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '---' }}</div>
                                    @if($payment)
                                        <small class="text-muted font-monospace d-block">إيصال #{{ $payment->receipt_number ?? $payment->id }}</small>
                                    @endif
                                </td>

                                {{-- اسم المريض --}}
                                <td>
                                    <div class="fw-bold text-dark">{{ $patient?->user?->name ?? $patient?->name ?? 'مريض غير محدد' }}</div>
                                    <small class="text-muted">{{ $patient?->user?->phone ?? '---' }}</small>
                                </td>

                                {{-- نوع الحساب --}}
                                <td>
                                    @if($isIns)
                                        <span class="badge bg-info-subtle text-info border border-info border-opacity-25 rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                            ضمان ({{ strtoupper($surgery->insurance_type) }})
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                                            نقدي (Cash)
                                        </span>
                                    @endif
                                </td>

                                {{-- أتعاب الجراحة الاسمية --}}
                                <td class="text-end font-monospace text-secondary fw-bold">
                                    {{ number_format($grossFee, 0) }} <small class="text-muted">د.ع</small>
                                </td>

                                {{-- استقطاعات الباقة وزر المودال --}}
                                <td class="text-end">
                                    @if($deductions > 0)
                                        <span class="fw-bold text-danger font-monospace">-{{ number_format($deductions, 0) }} د.ع</span>
                                        <button type="button" 
                                                class="btn btn-xs btn-outline-danger rounded-pill px-2 py-0 d-block ms-auto mt-1 show-package-breakdown-btn"
                                                style="font-size: 0.7rem;"
                                                data-surgery="#{{ $surgery->id }} - {{ $surgery->surgery_type }}"
                                                data-receipt="{{ $payment?->receipt_number ?? $payment?->id }}"
                                                data-deductions="{{ number_format($deductions, 0) }} د.ع"
                                                data-items="{{ json_encode($itemsSnapshot, JSON_UNESCAPED_UNICODE) }}">
                                            <i class="fas fa-list me-1"></i> تفاصيل ({{ count($itemsSnapshot) }})
                                        </button>
                                    @else
                                        <span class="text-muted font-monospace">0</span>
                                        @if($isIns)
                                            <small class="text-info d-block" style="font-size: 0.7rem;">عقد ضمان</small>
                                        @endif
                                    @endif
                                </td>

                                {{-- صافي مستحق الجراح --}}
                                <td class="text-end font-monospace text-success fs-6 fw-bold">
                                    {{ number_format($netFee, 0) }} <small class="text-muted">د.ع</small>
                                </td>

                                {{-- حالة السداد في الكاشير --}}
                                <td class="text-center">
                                    @if($surgery->payment_status === 'paid')
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill px-2 py-1">مسدد بالكامل</span>
                                    @elseif($surgery->payment_status === 'partial')
                                        <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 rounded-pill px-2 py-1">دفع جزئي</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 rounded-pill px-2 py-1">معلق</span>
                                    @endif
                                </td>

                                {{-- حالة تصفية مستحق الجراح مع زر الصرف السريع --}}
                                <td class="text-center pe-4">
                                    @if($isSettled)
                                        <div class="d-inline-flex flex-column align-items-center">
                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                                                <i class="fas fa-check-circle me-1"></i> تم الصرف
                                            </span>
                                            <small class="text-muted mt-1" style="font-size: 0.7rem;">
                                                {{ $due->paid_at ? $due->paid_at->format('Y-m-d H:i') : '' }}
                                                @if($due->paidBy)
                                                    | {{ $due->paidBy->name }}
                                                @endif
                                            </small>
                                        </div>
                                    @else
                                        <div class="d-inline-flex flex-column align-items-center gap-1">
                                            <span class="badge bg-warning-subtle text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                                                <i class="fas fa-clock me-1"></i> بانتظار الصرف
                                            </span>
                                            @if($payment)
                                                <button type="button" 
                                                        class="btn btn-sm btn-success rounded-pill px-3 py-0 settle-single-surgery-btn shadow-sm"
                                                        style="font-size: 0.75rem;"
                                                        data-surgery-id="{{ $surgery->id }}"
                                                        data-amount="{{ number_format($netFee, 0) }} د.ع"
                                                        data-action="{{ route('cashier.surgeries.settle-single-surgery', [$doctor, $surgery]) }}">
                                                    <i class="fas fa-hand-holding-usd me-1"></i> صرف العملية
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-procedures fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-bold">لا توجد عمليات جراحية مسجلة لهذا الطبيب حتى الآن.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($surgeries->hasPages())
            <div class="card-footer bg-white py-3 border-top d-flex justify-content-center">
                {{ $surgeries->links() }}
            </div>
        @endif
    </div>

    {{-- سجل السندات والدفعات المصروفة سابقاً للطبيب --}}
    @if($payouts->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 px-4 border-bottom">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="fas fa-history text-success"></i>
                    <span>سجل سندات الصرف والتسويات السابقة للطبيب</span>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light text-secondary small fw-bold">
                            <tr>
                                <th class="ps-4">رقم السند</th>
                                <th>تاريخ ووقت الصرف</th>
                                <th>المحاسب المسؤول</th>
                                <th class="text-end">المبلغ المصروف</th>
                                <th class="pe-4">البيان والملاحظات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payouts as $payout)
                                <tr>
                                    <td class="ps-4 fw-bold font-monospace text-dark">#{{ $payout->id }}</td>
                                    <td>{{ $payout->paid_at ? $payout->paid_at->format('Y-m-d H:i') : '---' }}</td>
                                    <td>
                                        <i class="fas fa-user-tag text-muted me-1"></i>
                                        {{ $payout->paidBy?->name ?? 'محاسب' }}
                                    </td>
                                    <td class="text-end font-monospace text-success fw-bold fs-6">
                                        {{ number_format($payout->amount, 0) }} د.ع
                                    </td>
                                    <td class="pe-4 text-muted small">{{ $payout->notes ?? '---' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- نافذة منبثقة لتفاصيل استقطاعات الباقة (Modal) --}}
<div class="modal fade" id="packageBreakdownModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fw-bold fs-6 d-flex align-items-center gap-2">
                    <i class="fas fa-cut"></i>
                    <span>تفاصيل الخدمات المشمولة بالباقة والمستقطعة من الجراح</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body p-4">
                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">العملية الجراحية:</span>
                        <strong class="text-dark" id="modalSurgeryName">---</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">رقم الإيصال:</span>
                        <span class="badge bg-secondary font-monospace" id="modalReceiptNo">---</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">إجمالي الاستقطاع المخصوم:</span>
                        <strong class="text-danger fs-6 font-monospace" id="modalTotalDeductions">0 د.ع</strong>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>#</th>
                                <th>الخدمة / البند</th>
                                <th>النوع</th>
                                <th class="text-end">القيمة المستقطعة</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsTbody">
                            {{-- تعبئة تلقائية عبر JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3 border-top">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. فتح نافذة تفاصيل استقطاعات الباقة
    const breakdownBtns = document.querySelectorAll('.show-package-breakdown-btn');
    const modalEl = document.getElementById('packageBreakdownModal');
    const bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;

    breakdownBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const surgery = this.getAttribute('data-surgery');
            const receipt = this.getAttribute('data-receipt');
            const deductions = this.getAttribute('data-deductions');
            let items = [];
            try {
                items = JSON.parse(this.getAttribute('data-items') || '[]');
            } catch (e) {
                items = [];
            }

            document.getElementById('modalSurgeryName').textContent = surgery;
            document.getElementById('modalReceiptNo').textContent = '#' + receipt;
            document.getElementById('modalTotalDeductions').textContent = deductions;

            const tbody = document.getElementById('modalItemsTbody');
            tbody.innerHTML = '';

            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">لا توجد عناصر مسجلة</td></tr>';
            } else {
                items.forEach((item, index) => {
                    let typeBadge = '';
                    if (item.type === 'room') {
                        typeBadge = '<span class="badge bg-secondary-subtle text-secondary border">غرفة وإقامة</span>';
                    } else if (item.type === 'lab') {
                        typeBadge = '<span class="badge bg-info-subtle text-info border">تحليل مختبري</span>';
                    } else if (item.type === 'radiology') {
                        typeBadge = '<span class="badge bg-warning-subtle text-dark border">فحص أشعة</span>';
                    } else {
                        typeBadge = '<span class="badge bg-primary-subtle text-primary border">خدمة إضافية</span>';
                    }

                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="text-center text-muted small">${index + 1}</td>
                        <td class="fw-bold text-dark">${item.name || '---'}</td>
                        <td class="text-center">${typeBadge}</td>
                        <td class="text-end font-monospace text-danger fw-bold">${Number(item.price || 0).toLocaleString()} د.ع</td>
                    `;
                    tbody.appendChild(row);
                });
            }

            if (bsModal) {
                bsModal.show();
            }
        });
    });

    // 2. صرف مستحقات عملية جراحية محددة
    const singleBtns = document.querySelectorAll('.settle-single-surgery-btn');
    singleBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const amount = this.getAttribute('data-amount');
            const actionUrl = this.getAttribute('data-action');
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

            Swal.fire({
                title: 'تأكيد صرف مستحقات العملية',
                html: `هل أنت متأكد من صرف مستحقات هذه العملية للجراح بمبلغ <b class="text-success">${amount}</b>؟`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-hand-holding-usd me-1"></i> نعم، تأكيد الصرف',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'جارٍ تسجيل الصرف...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'تم الصرف بنجاح',
                                text: data.message,
                                timer: 1800,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'تعذر الصرف',
                                text: data.message || 'حدث خطأ'
                            });
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ في الاتصال',
                            text: 'تعذر الاتصال بالسيرفر'
                        });
                    });
                }
            });
        });
    });
});
</script>
@endpush
@endsection
