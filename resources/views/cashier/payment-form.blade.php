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

    @php
        $patient = $appointment->patient;
        $doctor = $appointment->doctor;
        $defaultInsurance = $appointment->insurance_type ?? $patient->insurance_type ?? 'none';
        $defaultCardNo = $patient->insurance_card_no ?? $patient->insurance_booklet_number ?? '';
        $hiCategory = $patient ? $patient->healthInsuranceCategory : null;
        if ($defaultInsurance === 'hi' && $patient) {
            $defaultCopay = $patient->getCopayPercentageFor('consultation');
        } else {
            $defaultCopay = (float)($patient->copay_percentage ?? 15.0);
        }

        $regularPrice = (float)($doctor ? $doctor->getRegularPrice() : ($appointment->consultation_fee ?? 0));
        $moiPrice = (float)($doctor && $doctor->moi_price > 0 ? $doctor->moi_price : $regularPrice);
        $isMoiActive = (bool)($doctor ? ($doctor->is_moi_active ?? true) : true);
        $hiPrice = (float)($doctor && $doctor->hi_price > 0 ? $doctor->hi_price : $regularPrice);
        $isHiActive = (bool)($doctor ? ($doctor->is_hi_active ?? true) : true);
    @endphp

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-gradient-success text-white" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice-dollar me-2"></i>
                        تسوية ودفع رسوم الاستشارية
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('cashier.payment.process', $appointment->id) }}" id="appointmentPaymentForm">
                        @csrf

                        <!-- قسم الضمان الصحي ونسبة التحمل الخماسية المباشرة للكاشير -->
                        <div class="p-3 mb-4 rounded-3 border" style="background-color: #f8fafc;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    تغطية الضمان ونسبة التحمل (Co-payment)
                                </h6>
                                <span class="badge bg-secondary" id="copayBadge">تحكم مباشر للكاشير</span>
                            </div>

                            <div class="row g-3">
                                <!-- اختيار جهة الضمان -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">جهة الضمان / التأمين *</label>
                                    <select class="form-select" id="insurance_type" name="insurance_type">
                                        <option value="none" {{ old('insurance_type', $defaultInsurance) == 'none' ? 'selected' : '' }}>بدون ضمان (دفع نقدي كامل 100%)</option>
                                        <option value="moi" {{ old('insurance_type', $defaultInsurance) == 'moi' ? 'selected' : '' }}>ضمان قوى الأمن الداخلي (وزارة الداخلية)</option>
                                        <option value="hi" {{ old('insurance_type', $defaultInsurance) == 'hi' ? 'selected' : '' }}>هيئة الضمان الصحي الوطني</option>
                                    </select>
                                </div>

                                <!-- رقم بطاقة الضمان -->
                                <div class="col-md-6" id="insurance_card_col">
                                    <label class="form-label fw-bold small text-muted">رقم بطاقة / دفتر الضمان</label>
                                    <input type="text" class="form-control" id="insurance_card_no" name="insurance_card_no" 
                                           value="{{ old('insurance_card_no', $defaultCardNo) }}" placeholder="أدخل رقم الهوية أو الدفتر">
                                </div>

                                @if($hiCategory)
                                <div class="col-12" id="patient_hi_category_badge">
                                    <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div>
                                            <span class="badge bg-info text-dark me-1"><i class="fas fa-layer-group me-1"></i>{{ $hiCategory->name }} (الفئة {{ $hiCategory->code }})</span>
                                            <small class="text-muted">نسبة استقطاع الاستشارية المعتمدة: <strong class="text-primary">{{ (float)$hiCategory->consultation_copay }}%</strong></small>
                                        </div>
                                        @if($hiCategory->requires_thermal_stamp)
                                            <span class="badge bg-danger text-white"><i class="fas fa-stamp me-1"></i>شرط الختم الحراري (0%)</span>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <!-- أزرار النسب الخماسية ونسبة التحمل -->
                                <div class="col-12" id="copay_section">
                                    <label class="form-label fw-bold small text-muted mb-1">
                                        نسبة التحمل على المريض (Co-payment %)
                                    </label>
                                    
                                    <div class="d-flex flex-wrap align-items-center gap-1 mb-2">
                                        <span class="small text-muted me-2">خيارات خماسية سريعة:</span>
                                        @foreach([0, 5, 10, 15, 20, 25, 30, 50, 100] as $pct)
                                            <button type="button" class="btn btn-sm btn-outline-primary copay-btn" data-pct="{{ $pct }}">{{ $pct }}%</button>
                                        @endforeach
                                    </div>

                                    <div class="input-group" style="max-width: 250px;">
                                        <span class="input-group-text bg-light fw-bold">النسبة المعتمدة:</span>
                                        <input type="number" step="1" min="0" max="100" class="form-control text-center fw-bold" 
                                               id="copay_percentage" name="copay_percentage" value="{{ old('copay_percentage', $defaultCopay) }}">
                                        <span class="input-group-text bg-light fw-bold">%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- بطاقة معاينة الحسبة المالية المباشرة -->
                            <div class="row g-2 mt-3 pt-3 border-top text-center" id="live_pricing_summary">
                                <div class="col-4">
                                    <div class="bg-white p-2 rounded border">
                                        <small class="text-muted d-block">السعر المعتمد</small>
                                        <strong class="text-dark fs-6" id="display_approved_price">0 د.ع</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-white p-2 rounded border border-success">
                                        <small class="text-success fw-bold d-block">تحمل المريض (نقداً)</small>
                                        <strong class="text-success fs-6" id="display_patient_share">0 د.ع</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-white p-2 rounded border border-primary">
                                        <small class="text-primary fw-bold d-block">حصة الضمان (ذمة)</small>
                                        <strong class="text-primary fs-6" id="display_insurance_share">0 د.ع</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-money-bill-wave me-1 text-success"></i>
                                    طريقة الدفع *
                                </label>
                                <div class="payment-methods-group">
                                    <div class="form-check form-check-lg mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" 
                                               value="cash" {{ old('payment_method', ($defaultInsurance !== 'none' ? 'insurance' : 'cash')) == 'cash' ? 'checked' : '' }} required>
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
                                               value="insurance" {{ old('payment_method', ($defaultInsurance !== 'none' ? 'insurance' : 'cash')) == 'insurance' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="payment_insurance">
                                            🏥 تأمين / ضمان صحي (Insurance / Copay)
                                        </label>
                                    </div>
                                </div>
                                @error('payment_method')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">المبلغ المحصّل نقداً من المريض (IQD) *</label>
                                <input type="number" 
                                       name="amount" 
                                       id="amount_input"
                                       class="form-control form-control-lg fw-bold text-success @error('amount') is-invalid @enderror" 
                                       value="{{ old('amount', $regularPrice) }}"
                                       step="0.01"
                                       min="0"
                                       required>
                                <small class="text-muted">المبلغ الفعلي المستلم في الصندوق من المريض.</small>
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

    // أسعار وبيانات الطبيب
    const regularPrice = @json($regularPrice);
    const moiPrice = @json($moiPrice);
    const isMoiActive = @json($isMoiActive);
    const hiPrice = @json($hiPrice);
    const isHiActive = @json($isHiActive);

    const insuranceTypeSelect = document.getElementById('insurance_type');
    const copayInput = document.getElementById('copay_percentage');
    const copayBtns = document.querySelectorAll('.copay-btn');
    const amountInput = document.getElementById('amount_input');
    const displayApproved = document.getElementById('display_approved_price');
    const displayPatientShare = document.getElementById('display_patient_share');
    const displayInsuranceShare = document.getElementById('display_insurance_share');
    const insuranceCardCol = document.getElementById('insurance_card_col');
    const copaySection = document.getElementById('copay_section');
    const copayBadge = document.getElementById('copayBadge');
    const paymentInsuranceRadio = document.getElementById('payment_insurance');
    const paymentCashRadio = document.getElementById('payment_cash');

    function formatNumber(num) {
        return Math.round(num).toLocaleString('en-US') + ' د.ع';
    }

    function updateCopayButtonsActive(val) {
        copayBtns.forEach(btn => {
            const btnPct = parseFloat(btn.getAttribute('data-pct'));
            if (btnPct === val) {
                btn.classList.remove('btn-outline-primary');
                btn.classList.add('btn-primary', 'active');
            } else {
                btn.classList.remove('btn-primary', 'active');
                btn.classList.add('btn-outline-primary');
            }
        });
    }

    function recalculate() {
        const insType = insuranceTypeSelect ? insuranceTypeSelect.value : 'none';
        let copayPct = parseFloat(copayInput ? copayInput.value : 100);
        if (isNaN(copayPct) || copayPct < 0) copayPct = 0;
        if (copayPct > 100) copayPct = 100;

        let approvedPrice = regularPrice;
        let isCovered = false;

        if (insType === 'moi') {
            if (isMoiActive) {
                approvedPrice = moiPrice > 0 ? moiPrice : regularPrice;
                isCovered = true;
            }
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (copayBadge) {
                copayBadge.className = 'badge bg-primary';
                copayBadge.textContent = 'ضمان الداخلية';
            }
        } else if (insType === 'hi') {
            if (isHiActive) {
                approvedPrice = hiPrice > 0 ? hiPrice : regularPrice;
                isCovered = true;
            }
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (copayBadge) {
                copayBadge.className = 'badge bg-info text-dark';
                copayBadge.textContent = 'الضمان الصحي الوطني';
            }
        } else {
            // none
            approvedPrice = regularPrice;
            copayPct = 100;
            isCovered = false;
            if (insuranceCardCol) insuranceCardCol.style.display = 'none';
            if (copaySection) copaySection.style.display = 'none';
            if (copayBadge) {
                copayBadge.className = 'badge bg-secondary';
                copayBadge.textContent = 'دفع نقدي كامل (100%)';
            }
        }

        let patientShare = 0;
        let insuranceShare = 0;

        if (isCovered) {
            patientShare = Math.round(approvedPrice * (copayPct / 100));
            insuranceShare = Math.max(0, approvedPrice - patientShare);
        } else {
            patientShare = approvedPrice;
            insuranceShare = 0;
        }

        if (displayApproved) displayApproved.textContent = formatNumber(approvedPrice);
        if (displayPatientShare) displayPatientShare.textContent = formatNumber(patientShare);
        if (displayInsuranceShare) displayInsuranceShare.textContent = formatNumber(insuranceShare);

        if (amountInput) {
            amountInput.value = patientShare;
        }

        updateCopayButtonsActive(copayPct);
    }

    const hiCategoryCopay = @json($patient ? $patient->getCopayPercentageFor('consultation') : 15.0);
    const moiCopay = @json((float)($patient->copay_percentage ?? 15.0));
    const hiBadge = document.getElementById('patient_hi_category_badge');

    if (insuranceTypeSelect) {
        insuranceTypeSelect.addEventListener('change', function() {
            if (this.value === 'hi') {
                if (paymentInsuranceRadio) paymentInsuranceRadio.checked = true;
                if (copayInput) copayInput.value = hiCategoryCopay;
                if (hiBadge) hiBadge.style.display = 'block';
            } else if (this.value === 'moi') {
                if (paymentInsuranceRadio) paymentInsuranceRadio.checked = true;
                if (copayInput) copayInput.value = moiCopay;
                if (hiBadge) hiBadge.style.display = 'none';
            } else {
                if (paymentCashRadio) paymentCashRadio.checked = true;
                if (hiBadge) hiBadge.style.display = 'none';
            }
            recalculate();
        });
    }

    copayBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const pct = parseFloat(this.getAttribute('data-pct'));
            if (copayInput) {
                copayInput.value = pct;
            }
            recalculate();
        });
    });

    if (copayInput) {
        copayInput.addEventListener('input', recalculate);
    }

    // Initial calculation
    recalculate();

    const form = document.querySelector('form[action*="cashier.payment.process"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const amountIn = form.querySelector('input[name="amount"]');
            const val = parseFloat(amountIn?.value || 0);
            const insType = insuranceTypeSelect ? insuranceTypeSelect.value : 'none';
            const copayVal = parseFloat(copayInput?.value || 0);

            // السماح بـ 0 إذا كانت نسبة التحمل 0% تحت الضمان
            if (val <= 0 && !(insType !== 'none' && copayVal === 0)) {
                e.preventDefault();
                document.getElementById('modalErrorTitle').textContent = 'مبلغ الدفع غير صحيح (0 د.ع)';
                document.getElementById('modalErrorMessage').innerHTML = 'لا يمكن تأكيد الدفع بمبلغ <strong>0 د.ع</strong> ما لم تكن نسبة التحمل 0% تحت الضمان. يرجى كتابة المبلغ المستحق أولاً.';
                showDoctorFeeModal();
            }
        });
    }
});
</script>
@endsection
