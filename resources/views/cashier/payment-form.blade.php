@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-money-bill-wave me-2 text-success"></i>
                    تسديد رسوم الموعد
                </h2>
                <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة
                </a>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!$appointment->doctor || $appointment->consultation_fee <= 0)
        <div class="alert alert-warning alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 12px; background-color: #fff3cd;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fs-3 text-warning me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">تنبيه: أجور الكشف لهذا الطبيب غير محددة (0 د.ع)</h6>
                        <small class="text-secondary">يمكنك تعديل أجور الطبيب من قسم الأطباء، أو إدخال المبلغ المستحق يدوياً في حقل "المبلغ".</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 ms-auto fw-bold" data-bs-toggle="modal" data-bs-target="#doctorFeeErrorModal">
                    <i class="fas fa-exclamation-circle me-1"></i>عرض سبب التنبيه
                </button>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-success text-white" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice-dollar me-2"></i>
                        معلومات الدفع
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('cashier.payment.process', $appointment->id) }}">
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-money-bill-wave me-1 text-success"></i>
                                    طريقة الدفع *
                                </label>
                                <div class="payment-methods-group">
                                    <div class="form-check form-check-lg mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" 
                                               value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'checked' : '' }} required>
                                        <label class="form-check-label fw-semibold" for="payment_cash">
                                            💵 نقدي (Cash)
                                        </label>
                                    </div>
                                    <div class="form-check form-check-lg mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_card" 
                                               value="card" {{ old('payment_method') == 'card' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="payment_card">
                                            💳 بطاقة ائتمان (Card)
                                        </label>
                                    </div>
                                    <div class="form-check form-check-lg">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_insurance" 
                                               value="insurance" {{ old('payment_method') == 'insurance' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="payment_insurance">
                                            🏥 تأمين صحي (Insurance)
                                        </label>
                                    </div>
                                </div>
                                @error('payment_method')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">المبلغ (IQD) *</label>
                                <input type="number" 
                                       name="amount" 
                                       class="form-control @error('amount') is-invalid @enderror" 
                                       value="{{ old('amount', $appointment->consultation_fee) }}"
                                       step="0.01"
                                       min="0"
                                       required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">ملاحظات</label>
                            <textarea name="notes" 
                                      class="form-control @error('notes') is-invalid @enderror" 
                                      rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            سيتم إصدار إيصال دفع فوراً بعد إتمام العملية
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle me-2"></i>
                                تأكيد الدفع
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- تفاصيل الموعد -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="fas fa-calendar-check me-2"></i>
                        تفاصيل الموعد
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">رقم الموعد:</small>
                        <div class="fw-bold">#{{ $appointment->id }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">التاريخ والوقت:</small>
                        <div class="fw-bold">{{ $appointment->appointment_date->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">القسم:</small>
                        <div class="fw-bold">{{ $appointment->department ? $appointment->department->name : 'غير محدد' }}</div>
                    </div>
                </div>
            </div>

            <!-- معلومات المريض -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        معلومات المريض
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $p = $appointment->patient;
                    @endphp
                    <div class="mb-3">
                        <small class="text-muted">الاسم:</small>
                        <div class="fw-bold">{{ optional(optional($p)->user)->name ?? 'غير محدد' }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">الرقم الوطني:</small>
                        <div class="fw-bold">{{ optional($p)->national_id ?? 'غير محدد' }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">رقم الهاتف:</small>
                        <div class="fw-bold">{{ optional(optional($p)->user)->phone ?? 'غير محدد' }}</div>
                    </div>
                </div>
            </div>

            <!-- معلومات الطبيب -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user-md me-2"></i>
                        معلومات الطبيب
                    </h6>
                </div>
                <div class="card-body">
                    @php
                        $d = $appointment->doctor;
                    @endphp
                    <div class="mb-3">
                        <small class="text-muted">الاسم:</small>
                        <div class="fw-bold">د. {{ optional(optional($d)->user)->name ?? 'غير محدد' }}</div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">التخصص:</small>
                        <div class="fw-bold">{{ optional($d)->specialization ?? 'غير محدد' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal تنبيه خطأ أجور الطبيب / الدفع -->
<div class="modal fade" id="doctorFeeErrorModal" tabindex="-1" aria-labelledby="doctorFeeErrorModalLabel" aria-hidden="true" style="z-index: 1060 !important;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header bg-danger text-white" style="border-radius: 20px 20px 0 0;">
                <h5 class="modal-title fw-bold" id="doctorFeeErrorModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>تنبيه: خطأ في أجور الكشف للطبيب
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 75px; height: 75px; background-color: #fee2e2;">
                    <i class="fas fa-user-md text-danger fs-1"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2" id="modalErrorTitle">لم يتم تحديد أجور الكشف لهذا الطبيب!</h5>
                <p class="text-muted mb-3" id="modalErrorMessage">
                    تنبيه: أجور الكشف المسجلة لهذا الموعد غير محددة أو تساوي <strong>0 د.ع</strong>. يرجى التأكد من المبلغ وتحديده يدوياً في الخانة المخصصة قبل إتمام العملية.
                </p>
                
                <div class="card bg-light border-0 p-3 text-start mb-3" style="border-radius: 12px;">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted fs-7">الطبيب المعالج:</span>
                        <span class="fw-bold fs-7">د. {{ optional(optional($appointment->doctor)->user)->name ?? 'غير محدد' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted fs-7">أجر الكشف المسجل:</span>
                        <span class="badge bg-danger fs-7">{{ number_format($appointment->consultation_fee ?? 0) }} د.ع</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted fs-7">اسم المريض:</span>
                        <span class="fw-bold fs-7">{{ optional(optional($appointment->patient)->user)->name ?? 'غير محدد' }}</span>
                    </div>
                </div>

                <div class="alert alert-warning text-start fs-7 mb-0">
                    <i class="fas fa-lightbulb me-1"></i> <strong>تلميح:</strong> يمكنك إدخال المبلغ يدويًا في حقل "المبلغ (IQD)" في نموذج الدفع، أو تعديل الأجر الثابت للطبيب من صفحة الأطباء.
                </div>
            </div>
            <div class="modal-footer bg-light border-0 justify-content-between p-3" style="border-radius: 0 0 20px 20px;">
                @if($appointment->doctor)
                    <a href="{{ route('doctors.edit', $appointment->doctor->id) }}" target="_blank" class="btn btn-outline-danger rounded-pill px-3">
                        <i class="fas fa-user-edit me-1"></i>تعديل بيانات الطبيب
                    </a>
                @else
                    <div></div>
                @endif
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-dismiss="modal" onclick="focusAmountInput()">
                    <i class="fas fa-pen me-1"></i>إدخال المبلغ يدوياً
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function focusAmountInput() {
    const amountInput = document.querySelector('input[name="amount"]');
    if (amountInput) {
        amountInput.focus();
        amountInput.select();
    }
}

function showDoctorFeeModal() {
    const modalElement = document.getElementById('doctorFeeErrorModal');
    if (modalElement && typeof bootstrap !== 'undefined') {
        if (modalElement.parentElement !== document.body) {
            document.body.appendChild(modalElement);
        }
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const shouldShowModal = @json(!$appointment->doctor || $appointment->consultation_fee <= 0 || session('error') ? true : false);
    if (shouldShowModal) {
        showDoctorFeeModal();
    }

    const form = document.querySelector('form[action*="cashier.payment.process"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const amountInput = form.querySelector('input[name="amount"]');
            const val = parseFloat(amountInput?.value || 0);
            if (val <= 0) {
                e.preventDefault();
                document.getElementById('modalErrorTitle').textContent = 'مبلغ الدفع غير صحيح (0 د.ع)';
                document.getElementById('modalErrorMessage').innerHTML = 'لا يمكن تأكيد الدفع بمبلغ <strong>0 د.ع</strong>. يرجى كتابة المبلغ المستحق أولاً.';
                showDoctorFeeModal();
            }
        });
    }
});
</script>
@endsection
