@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 no-print">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-chart-line me-2 text-primary"></i>الحركات المالية للعيادات الاستشارية</h2>
                <p class="text-muted mb-0">سجل الدفعات والاسترجاعات للمواعيد الاستشارية.</p>
            </div>
            <a href="{{ route('consultant-availability.index') }}" class="btn btn-secondary no-print">
                <i class="fas fa-arrow-left me-2"></i>العودة إلى توفر الأطباء
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 no-print">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('consultant-availability.financial-movements') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="from_date" class="form-label">من تاريخ</label>
                            <input type="date" id="from_date" name="from_date" class="form-control" value="{{ old('from_date', $fromDate) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="to_date" class="form-label">إلى تاريخ</label>
                            <input type="date" id="to_date" name="to_date" class="form-control" value="{{ old('to_date', $toDate) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_type" class="form-label">نوع الحركة</label>
                            <select id="filter_type" name="filter_type" class="form-select">
                                <option value="">كل الحركات</option>
                                <option value="payment" {{ $filterType === 'payment' ? 'selected' : '' }}>حركات قبض</option>
                                <option value="refund" {{ $filterType === 'refund' ? 'selected' : '' }}>حركات استرجاع</option>
                                <option value="appointment_paid" {{ $filterType === 'appointment_paid' ? 'selected' : '' }}>المواعيد المدفوعة بالكامل</option>
                                <option value="appointment_refunded" {{ $filterType === 'appointment_refunded' ? 'selected' : '' }}>المواعيد المدفوعة ثم المسترجعة</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex flex-column gap-2 no-print">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>تصفية
                                </button>
                                <a href="{{ route('consultant-availability.financial-movements') }}" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-redo me-2"></i>مسح
                                </a>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('consultant-availability.financial-movements.export', request()->only(['from_date', 'to_date', 'filter_type'])) }}" class="btn btn-success w-100">
                                    <i class="fas fa-file-excel me-2"></i>إكسل
                                </a>
                                <button type="button" onclick="window.print()" class="btn btn-info text-white w-100">
                                    <i class="fas fa-print me-2"></i>طباعة
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @if(auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole('admin'))
        <div class="col-lg-4 no-print">
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
        @endif
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body" id="print-area">
                    <div class="d-none d-print-block mb-4 text-center">
                        <h2>مستشفى الأهلي</h2>
                        <h4>تقرير الحركات المالية للعيادات الاستشارية</h4>
                        <div class="mt-2">
                            <span>تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</span>
                            @if($fromDate || $toDate)
                                <span class="ms-3">الفترة: من {{ $fromDate ?? 'البداية' }} إلى {{ $toDate ?? 'النهاية' }}</span>
                            @endif
                        </div>
                        <hr>
                    </div>
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
                                        @if(auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole('admin'))
                                        <th>حصة الطبيب</th>
                                        <th>حصة المستشفى</th>
                                        <th class="no-print text-center" style="width: 80px;">إجراءات</th>
                                        @endif
                                        <th>نوع الحركة</th>
                                        <th>طريقة الدفع</th>
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
                                            <td class="fw-bold {{ $payment->total_amount < 0 ? 'text-danger' : 'text-success' }}">
                                                {{ number_format(abs($payment->total_amount), 2) }} IQD
                                            </td>
                                            @if(auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole('admin'))
                                            <td class="text-success fw-semibold" id="doc-share-val-{{ $payment->id }}">{{ number_format($payment->doctor_share, 2) }} IQD</td>
                                            <td class="text-info fw-semibold" id="hosp-share-val-{{ $payment->id }}">{{ number_format($payment->hospital_share, 2) }} IQD</td>
                                            <td class="no-print text-center">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-primary px-2 py-1" 
                                                        title="تعديل حصة الطبيب والمستشفى لهذا القيد يدوياً"
                                                        onclick="openEditRevenueModal({{ $payment->id }}, '{{ addslashes($payment->receipt_number ?? '-') }}', '{{ addslashes(optional(optional(optional($payment->appointment)->patient)->user)->name ?? optional(optional($payment->patient)->user)->name ?? '-') }}', '{{ addslashes(optional(optional(optional($payment->appointment)->doctor)->user)->name ?? '-') }}', '{{ addslashes(optional(optional($payment->appointment)->department)->name ?? '-') }}', {{ abs((float)$payment->total_amount) }}, {{ (float)$payment->doctor_share }})">
                                                    <i class="fas fa-edit me-1"></i> تعديل
                                                </button>
                                            </td>
                                            @endif
                                            <td>
                                                @if($payment->movement_type === 'refund' || $payment->total_amount < 0)
                                                    <span class="badge bg-danger">استرجاع</span>
                                                @else
                                                    <span class="badge bg-success">قبض</span>
                                                @endif
                                            </td>
                                            <td>{{ $payment->payment_method ?? '-' }}</td>
                                            <td>
                                                @if($payment->receipt_number && $payment->payment_id)
                                                    <a href="{{ route('cashier.receipt.print', $payment->payment_id) }}" class="btn btn-sm btn-link p-0 no-print" target="_blank" title="طباعة الإيصال">
                                                        <i class="fas fa-print me-1 text-primary"></i>{{ $payment->receipt_number }}
                                                    </a>
                                                    <span class="d-none d-print-inline">{{ $payment->receipt_number }}</span>
                                                @else
                                                    {{ $payment->receipt_number ?? '-' }}
                                                @endif
                                            </td>
                                            <td>{{ optional($payment->cashier)->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 no-print">
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

<!-- مودال تعديل الحصص المالية للقيد -->
@if(auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole('admin'))
<div class="modal fade" id="editRevenueModal" tabindex="-1" aria-labelledby="editRevenueModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <h6 class="modal-title fw-bold" id="editRevenueModalLabel">
                    <i class="fas fa-balance-scale me-1"></i> تعديل الحصص المالية للقيد: <span id="modalReceiptBadge" class="font-monospace text-warning"></span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form id="editRevenueForm" onsubmit="submitRevenueShareEdit(event)">
                @csrf
                <div class="modal-body p-3">
                    <input type="hidden" id="modalRevenueId" value="">
                    <input type="hidden" id="modalTotalAmount" value="0">

                    <!-- بطاقة معلومات القيد -->
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="row g-2 small">
                            <div class="col-6">
                                <span class="text-muted d-block">المريض:</span>
                                <strong id="modalPatientName" class="text-dark">-</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">الطبيب والقسم:</span>
                                <strong id="modalDoctorDept" class="text-dark">-</strong>
                            </div>
                            <div class="col-12 mt-2 pt-2 border-top d-flex justify-content-between align-items-center">
                                <span class="text-muted">المبلغ الإجمالي للكشفية:</span>
                                <span class="fw-bold font-monospace text-primary fs-6" id="modalTotalDisplay">0.00 IQD</span>
                            </div>
                        </div>
                    </div>

                    <!-- إدخال حصة الطبيب -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark mb-1">
                            👨‍⚕️ حصة الطبيب الجديدة (د.ع):
                        </label>
                        <div class="input-group">
                            <input type="number" 
                                   id="modalDoctorShareInput" 
                                   class="form-control form-control-lg font-monospace text-center fw-bold text-success" 
                                   step="500" 
                                   min="0" 
                                   required 
                                   oninput="calculateModalShares()">
                            <span class="input-group-text bg-success-subtle text-success fw-bold">د.ع</span>
                        </div>
                        <div class="form-text small text-muted">أدخل المبلغ الثابت الذي يستحقه الطبيب من هذا الوصل.</div>
                    </div>

                    <!-- معاينة حصة المستشفى -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="small fw-bold text-dark d-block">🏥 حصة المستشفى الناتجة:</span>
                                <span class="text-muted small">المبلغ الإجمالي - حصة الطبيب</span>
                            </div>
                            <div class="text-end">
                                <span class="fw-bold font-monospace text-info fs-5 d-block" id="modalHospitalShareDisplay">0.00 IQD</span>
                                <span class="badge bg-info-subtle text-info font-monospace" id="modalPercentageDisplay">0%</span>
                            </div>
                        </div>
                    </div>

                    <!-- سبب التعديل -->
                    <div class="mb-2">
                        <label class="form-label small text-muted mb-1">سبب التعديل الإداري (اختياري / للرقابة والتاريخ):</label>
                        <input type="text" id="modalReasonInput" class="form-control form-control-sm" placeholder="مثال: تصحيح المبلغ الثابت المتفق عليه">
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-sm btn-primary px-3 fw-bold" id="btnSaveRevenueEdit">
                        <i class="fas fa-save me-1"></i> حفظ التعديل المالي وتحديث الحساب
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let editModalInstance = null;

function openEditRevenueModal(id, receipt, patient, doctor, dept, total, doctorShare) {
    document.getElementById('modalRevenueId').value = id;
    document.getElementById('modalTotalAmount').value = total;
    document.getElementById('modalReceiptBadge').textContent = '#' + receipt;
    document.getElementById('modalPatientName').textContent = patient;
    document.getElementById('modalDoctorDept').textContent = doctor + ' (' + dept + ')';
    document.getElementById('modalTotalDisplay').textContent = Number(total).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' IQD';
    document.getElementById('modalDoctorShareInput').value = doctorShare;
    document.getElementById('modalDoctorShareInput').max = total;
    document.getElementById('modalReasonInput').value = '';

    calculateModalShares();

    const modalEl = document.getElementById('editRevenueModal');
    if (!editModalInstance) {
        editModalInstance = new bootstrap.Modal(modalEl);
    }
    editModalInstance.show();
}

function calculateModalShares() {
    const total = parseFloat(document.getElementById('modalTotalAmount').value || '0');
    let docShare = parseFloat(document.getElementById('modalDoctorShareInput').value || '0');

    if (docShare > total) {
        docShare = total;
        document.getElementById('modalDoctorShareInput').value = total;
    }
    if (docShare < 0) {
        docShare = 0;
        document.getElementById('modalDoctorShareInput').value = 0;
    }

    const hospShare = Math.max(0, total - docShare);
    const percent = total > 0 ? Math.round((docShare / total) * 100) : 0;

    document.getElementById('modalHospitalShareDisplay').textContent = hospShare.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' IQD';
    document.getElementById('modalPercentageDisplay').textContent = percent + '% للطبيب (' + (100 - percent) + '% للمستشفى)';
}

function submitRevenueShareEdit(e) {
    e.preventDefault();
    const id = document.getElementById('modalRevenueId').value;
    const docShare = document.getElementById('modalDoctorShareInput').value;
    const reason = document.getElementById('modalReasonInput').value;
    const btn = document.getElementById('btnSaveRevenueEdit');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الحفظ...';

    fetch('/consultant-availability/financial-movements/' + id + '/update-shares', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            doctor_share: docShare,
            reason: reason
        })
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ التعديل المالي وتحديث الحساب';

        if (data.success) {
            // تحديث الجدول مباشرة
            const docCell = document.getElementById('doc-share-val-' + id);
            const hospCell = document.getElementById('hosp-share-val-' + id);
            if (docCell) docCell.textContent = data.doctor_share_formatted;
            if (hospCell) hospCell.textContent = data.hospital_share_formatted;

            if (editModalInstance) {
                editModalInstance.hide();
            }

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: data.message,
                    timer: 2000,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false
                });
            } else {
                alert(data.message);
            }
        } else {
            alert(data.message || 'حدث خطأ أثناء حفظ التعديل');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ التعديل المالي وتحديث الحساب';
        console.error('Error updating shares:', err);
        alert('حدث خطأ أثناء الاتصال بالسيرفر');
    });
}
</script>
@endif

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
