@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
    <!-- تنبيه الخطأ إن وجد -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-3" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

        // فحص ما إذا كان الموعد مرتبطاً بفحص سونار أو خدمة أشعة
        $scanType = null;
        if ($appointment->visit) {
            $medReq = \App\Models\Request::where('visit_id', $appointment->visit->id)->where('type', 'radiology')->first();
            if ($medReq) {
                $details = is_string($medReq->details) ? json_decode($medReq->details, true) : $medReq->details;
                $radTypeId = $details['ultrasound_type_id'] ?? ($details['radiology_type_ids'][0] ?? null);
                if ($radTypeId) {
                    $scanType = \App\Models\RadiologyType::find($radTypeId);
                }
            }
        }

        if ($scanType) {
            $serviceName = $scanType->name;
            $serviceCode = $scanType->code;
            $serviceCategory = $scanType->main_category ?? 'سونار';
            $regularPrice = (float)$scanType->base_price;
            $moiPrice = (float)($scanType->moi_price > 0 ? $scanType->moi_price : 0);
            $isMoiActive = (bool)($scanType->is_moi_active ?? true);
            $hiPrice = (float)($scanType->hi_price > 0 ? $scanType->hi_price : 0);
            $isHiActive = (bool)($scanType->is_hi_active ?? true);
        } else {
            $serviceName = $appointment->reason ?? 'كشف طبي عام';
            $serviceCode = null;
            $serviceCategory = 'استشارية';
            $regularPrice = (float)($appointment->consultation_fee > 0 
                ? $appointment->consultation_fee 
                : ($doctor ? $doctor->getRegularPrice() : 0));
            $moiPrice = (float)($doctor && $doctor->moi_price > 0 ? $doctor->moi_price : 0);
            $isMoiActive = (bool)($doctor ? ($doctor->is_moi_active ?? true) : true);
            $hiPrice = (float)($doctor && $doctor->hi_price > 0 ? $doctor->hi_price : 0);
            $isHiActive = (bool)($doctor ? ($doctor->is_hi_active ?? true) : true);
        }
    @endphp

    @if((!$appointment->doctor && !$scanType) || $regularPrice <= 0)
        <div class="alert alert-warning alert-dismissible fade show shadow-sm border-0 mb-3 rounded-3" role="alert">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fs-4 text-warning me-2"></i>
                    <div>
                        <strong class="text-dark">أجور الخدمة غير محددة (0 د.ع)</strong>
                        <span class="text-muted ms-2 small">يرجى كتابة المبلغ المستلم يدوياً.</span>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#doctorFeeErrorModal">
                    عرض سبب التنبيه
                </button>
            </div>
        </div>
    @endif

    <!-- شريط معلومات المريض والعيادة العلوي الموحد والأنيق -->
    <div class="card border-0 shadow-sm rounded-4 mb-3" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <!-- المريض -->
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px; font-size: 1.4rem;">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h4 class="fw-bold mb-0 text-white">{{ optional(optional($patient)->user)->name ?? 'مريض غير محدد' }}</h4>
                            <span class="badge bg-primary px-2 py-1 rounded-pill">موعد #{{ $appointment->id }}</span>
                        </div>
                        <div class="text-white-50 small d-flex flex-wrap gap-3">
                            <span><i class="fas fa-id-card me-1"></i>{{ $patient->national_id ?? 'بدون رقم هوية' }}</span>
                            <span><i class="fas fa-phone me-1"></i>{{ optional(optional($patient)->user)->phone ?? 'بدون هاتف' }}</span>
                            <span><i class="fas fa-clock me-1"></i>{{ $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d H:i') : '' }}</span>
                        </div>
                    </div>
                </div>

                <!-- الطبيب والخدمة -->
                <div class="d-flex align-items-center gap-3 bg-white bg-opacity-10 p-2 px-3 rounded-3">
                    <div class="text-end">
                        <span class="badge {{ $scanType ? 'bg-info text-dark' : 'bg-success' }} mb-1">{{ $serviceCategory }}</span>
                        <div class="fw-bold text-white">{{ $serviceName }}</div>
                        <small class="text-white-50">
                            @if($doctor)
                                د. {{ optional($doctor->user)->name }} ({{ $doctor->specialization ?? 'عام' }})
                            @else
                                قسم: {{ optional($appointment->department)->name ?? 'عام' }}
                            @endif
                        </small>
                    </div>
                    <div class="rounded-circle bg-white bg-opacity-20 text-white d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.2rem;">
                        <i class="fas {{ $scanType ? 'fa-wave-square' : 'fa-stethoscope' }}"></i>
                    </div>
                </div>

                <!-- زر الرجوع -->
                <a href="{{ route('cashier.index') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fas fa-arrow-right me-1"></i> قائمة الانتظار
                </a>
            </div>
        </div>
    </div>

    <!-- نموذج الدفع الرئيسي المبسط -->
    <form method="POST" action="{{ route('cashier.payment.process', $appointment->id) }}" id="appointmentPaymentForm">
        @csrf

        <div class="row g-3">
            <!-- البطاقة المالية المركزية -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="card-body p-4">
                        
                        <!-- 1. شريط الأرقام المالية الثلاثية المباشرة -->
                        <div class="row g-3 mb-4 text-center" id="live_pricing_summary">
                            <div class="col-md-4">
                                <div class="p-3 rounded-3 border bg-light">
                                    <span class="text-muted small d-block mb-1">السعر المعتمد للخدمة</span>
                                    <h4 class="fw-bold mb-0 text-dark" id="display_approved_price">0 د.ع</h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded-3 border bg-primary bg-opacity-10 border-primary">
                                    <span class="text-primary fw-bold small d-block mb-1">
                                        <i class="fas fa-shield-alt me-1"></i>تغطية الضمان (ذمة)
                                    </span>
                                    <h4 class="fw-bold mb-0 text-primary" id="display_insurance_share">0 د.ع</h4>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 rounded-3 border bg-success bg-opacity-10 border-success shadow-sm">
                                    <span class="text-success fw-bold small d-block mb-1">
                                        <i class="fas fa-hand-holding-usd me-1"></i>المطلوب من المريض
                                    </span>
                                    <h4 class="fw-bold mb-0 text-success" id="display_patient_share">0 د.ع</h4>
                                </div>
                            </div>
                        </div>

                        <!-- 2. قسم التغطية التأمينية التلقائية -->
                        <div class="p-3 rounded-3 border mb-4" style="background-color: #f8fafc;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-file-medical-alt text-primary fs-5"></i>
                                    <h6 class="fw-bold text-dark mb-0">حالة التغطية والضمان الصحي</h6>
                                </div>
                                
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $defaultInsurance !== 'none' ? 'bg-success' : 'bg-secondary' }} px-3 py-2 fs-7" id="copayBadge">
                                        {{ $defaultInsurance === 'hi' ? 'مشمول بالضمان الصحي الوطني' : ($defaultInsurance === 'moi' ? 'مشمول بضمان الداخلية' : 'دفع نقدي كامل (100%)') }}
                                    </span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill" onclick="toggleInsuranceEdit()">
                                        <i class="fas fa-lock me-1" id="lock_icon"></i><span id="lock_text">تثبيت آلي</span>
                                    </button>
                                </div>
                            </div>

                            <!-- تفاصيل الضمان المثبتة -->
                            <div class="row g-2 align-items-center mt-1">
                                <div class="col-md-6" id="insurance_type_wrapper">
                                    <div class="p-2 px-3 rounded-2 bg-white border text-secondary small d-flex justify-content-between align-items-center" id="locked_insurance_display">
                                        <span>جهة التغطية: <strong class="text-dark">{{ $defaultInsurance === 'hi' ? 'هيئة الضمان الصحي الوطني (HI)' : ($defaultInsurance === 'moi' ? 'ضمان قوى الأمن الداخلي' : 'بدون ضمان') }}</strong></span>
                                        @if($defaultInsurance !== 'none')
                                            <i class="fas fa-check-circle text-success"></i>
                                        @endif
                                    </div>
                                    <select class="form-select d-none" id="insurance_type" name="insurance_type">
                                        <option value="none" {{ old('insurance_type', $defaultInsurance) == 'none' ? 'selected' : '' }}>بدون ضمان (دفع نقدي كامل 100%)</option>
                                        <option value="moi" {{ old('insurance_type', $defaultInsurance) == 'moi' ? 'selected' : '' }}>ضمان قوى الأمن الداخلي (وزارة الداخلية)</option>
                                        <option value="hi" {{ old('insurance_type', $defaultInsurance) == 'hi' ? 'selected' : '' }}>هيئة الضمان الصحي الوطني</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="insurance_card_col" style="{{ $defaultInsurance === 'none' ? 'display: none;' : '' }}">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white small text-muted"><i class="fas fa-id-badge me-1"></i>رقم البطاقة / الدفتر:</span>
                                        <input type="text" class="form-control" id="insurance_card_no" name="insurance_card_no" 
                                               value="{{ old('insurance_card_no', $defaultCardNo) }}" placeholder="أدخل رقم الهوية">
                                    </div>
                                </div>

                                @if($hiCategory)
                                    <div class="col-12" id="patient_hi_category_badge">
                                        <div class="p-2 rounded-2 bg-info bg-opacity-10 border border-info border-opacity-25 d-flex align-items-center justify-content-between flex-wrap gap-2 small">
                                            <span class="text-dark">
                                                <i class="fas fa-layer-group text-primary me-1"></i>
                                                الفئة المعتمدة: <strong>{{ $hiCategory->name }} ({{ $hiCategory->code }})</strong> — نسبة التحمل: <strong class="text-primary fs-7">{{ (float)$hiCategory->consultation_copay }}%</strong>
                                            </span>
                                            @if($hiCategory->requires_thermal_stamp)
                                                <span class="badge bg-danger text-white"><i class="fas fa-stamp me-1"></i>شرط الختم الحراري (0%)</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="col-12" id="copay_section" style="{{ $defaultInsurance === 'none' ? 'display: none;' : '' }}">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <span class="small text-muted">نسبة التحمل المقررة على المريض: <strong class="text-primary fs-6" id="display_copay_badge">{{ (float)$defaultCopay }}%</strong></span>
                                        <div class="input-group" id="manual_copay_input_group" style="max-width: 140px; display: none !important;">
                                            <input type="number" step="1" min="0" max="100" class="form-control form-control-sm text-center fw-bold" 
                                                   id="copay_percentage" name="copay_percentage" value="{{ old('copay_percentage', $defaultCopay) }}">
                                            <span class="input-group-text bg-light">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. طريقة الدفع (نقدي / إلكتروني فقط) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted mb-2">طريقة تحصيل حصة المريض *</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="payment_cash" value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'checked' : '' }} required>
                                    <label class="btn btn-outline-success w-100 p-3 rounded-3 text-start d-flex align-items-center justify-content-between payment-method-card" for="payment_cash">
                                        <div>
                                            <div class="fw-bold fs-6">💵 نقدي (Cash)</div>
                                            <small class="text-muted">استلام المبلغ نقداً في الصندوق</small>
                                        </div>
                                        <i class="fas fa-check-circle check-icon fs-5"></i>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="payment_card" value="card" {{ old('payment_method') == 'card' ? 'checked' : '' }}>
                                    <label class="btn btn-outline-primary w-100 p-3 rounded-3 text-start d-flex align-items-center justify-content-between payment-method-card" for="payment_card">
                                        <div>
                                            <div class="fw-bold fs-6">💳 دفع إلكتروني (POS / Card)</div>
                                            <small class="text-muted">بطاقة مصرفية أو جهاز الدفع</small>
                                        </div>
                                        <i class="fas fa-check-circle check-icon fs-5"></i>
                                    </label>
                                </div>
                            </div>
                            @error('payment_method')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- 4. ملاحظات إضافية اختيارية -->
                        <div class="mb-2">
                            <a class="text-decoration-none small text-muted" data-bs-toggle="collapse" href="#notesCollapse" role="button">
                                <i class="fas fa-comment-alt me-1"></i> إضافة ملاحظة على السند (اختياري)
                            </a>
                            <div class="collapse mt-2" id="notesCollapse">
                                <textarea name="notes" class="form-control" rows="2" placeholder="اكتب أي ملاحظة للمحاسبة أو الإيصال...">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- الجانب الأيسر: خانة المبلغ النهائي وزر التأكيد والطباعة -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100 d-flex flex-column justify-content-between p-4">
                    <div>
                        <div class="text-center pb-3 mb-3 border-bottom">
                            <span class="text-muted small d-block mb-1">المبلغ الصافي المطلوب قبضه</span>
                            <div class="input-group input-group-lg justify-content-center">
                                <input type="number" 
                                       name="amount" 
                                       id="amount_input"
                                       class="form-control text-center fw-bold text-success border-success fs-3 @error('amount') is-invalid @enderror" 
                                       value="{{ old('amount', $regularPrice) }}"
                                       step="0.01"
                                       min="0"
                                       style="max-width: 240px; border-radius: 12px;"
                                       required>
                                <span class="input-group-text bg-success text-white fw-bold rounded-3 ms-2">د.ع</span>
                            </div>
                            @error('amount')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-2">يُعدل آلياً حسب نسبة التحمل للضمان</small>
                        </div>

                        <!-- ملخص سريع للعملية -->
                        <div class="bg-light p-3 rounded-3 mb-4 small">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">نوع الخدمة:</span>
                                <strong class="text-dark">{{ $serviceCategory }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">الطبيب:</span>
                                <strong class="text-dark">{{ $doctor ? optional($doctor->user)->name : 'عام' }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">الجهة الضامنة:</span>
                                <strong class="text-dark">{{ $defaultInsurance !== 'none' ? ($defaultInsurance === 'hi' ? 'الضمان الصحي' : 'الداخلية') : 'نقدي 100%' }}</strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">الإيصال:</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">تلقائي فور الدفع</span>
                            </div>
                        </div>
                    </div>

                    <!-- زر التأكيد والطباعة الكبير -->
                    <div>
                        <div id="insuranceErrorAlert" class="alert alert-danger d-none fw-bold small mb-2 text-center" style="font-size: 0.85rem;">
                            <i class="fas fa-ban me-1"></i>
                            <span id="insuranceErrorText">لا يمكن التسديد! الطبيب/الخدمة غير مشمول بهذا الضمان أو أن التسعيرة غير مدخلة.</span>
                        </div>
                        <button type="submit" id="submitPaymentBtn" class="btn btn-success btn-lg w-100 py-3 rounded-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size: 1.15rem;">
                            <i class="fas fa-print fs-5"></i>
                            <span>تأكيد القبض وإصدار الوصل</span>
                        </button>
                        <small class="text-center text-muted d-block mt-2">
                            <i class="fas fa-bolt text-warning me-1"></i> يُحدث طابور الطبيب تلقائياً بالدخول
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal تنبيه خطأ أجور الطبيب -->
<div class="modal fade" id="doctorFeeErrorModal" tabindex="-1" aria-labelledby="doctorFeeErrorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-danger text-white rounded-top-4">
                <h5 class="modal-title fw-bold" id="doctorFeeErrorModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>أجور الكشف غير محددة
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <h6 class="fw-bold text-dark mb-2" id="modalErrorTitle">لم يتم تسجيل تسعيرة لهذا الطبيب</h6>
                <p class="text-muted small mb-3" id="modalErrorMessage">
                    يرجى كتابة المبلغ المستحق المطلوب قبضه يدوياً في خانة المبلغ.
                </p>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold" data-bs-dismiss="modal" onclick="focusAmountInput()">
                    إدخال المبلغ يدوياً
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
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
    }
}

function toggleInsuranceEdit() {
    const lockedDisplay = document.getElementById('locked_insurance_display');
    const selectElem = document.getElementById('insurance_type');
    const manualGroup = document.getElementById('manual_copay_input_group');
    const lockIcon = document.getElementById('lock_icon');
    const lockText = document.getElementById('lock_text');

    if (selectElem && selectElem.classList.contains('d-none')) {
        selectElem.classList.remove('d-none');
        if (lockedDisplay) lockedDisplay.classList.add('d-none');
        if (manualGroup) manualGroup.style.display = 'flex';
        if (lockIcon) lockIcon.className = 'fas fa-unlock text-warning me-1';
        if (lockText) lockText.textContent = 'تعديل مفتوح';
    } else if (selectElem) {
        selectElem.classList.add('d-none');
        if (lockedDisplay) lockedDisplay.classList.remove('d-none');
        if (manualGroup) manualGroup.style.display = 'none';
        if (lockIcon) lockIcon.className = 'fas fa-lock me-1';
        if (lockText) lockText.textContent = 'تثبيت آلي';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const shouldShowModal = @json(((!$appointment->doctor && !$scanType) || $regularPrice <= 0 || session('error')) ? true : false);
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
    const amountInput = document.getElementById('amount_input');
    const displayApproved = document.getElementById('display_approved_price');
    const displayPatientShare = document.getElementById('display_patient_share');
    const displayInsuranceShare = document.getElementById('display_insurance_share');
    const displayCopayBadge = document.getElementById('display_copay_badge');
    const insuranceCardCol = document.getElementById('insurance_card_col');
    const copaySection = document.getElementById('copay_section');
    const copayBadge = document.getElementById('copayBadge');
    const hiBadge = document.getElementById('patient_hi_category_badge');

    function formatNumber(num) {
        return Math.round(num).toLocaleString('en-US') + ' د.ع';
    }

    function recalculate() {
        const insType = insuranceTypeSelect ? insuranceTypeSelect.value : 'none';
        let copayPct = parseFloat(copayInput ? copayInput.value : 100);
        if (isNaN(copayPct) || copayPct < 0) copayPct = 0;
        if (copayPct > 100) copayPct = 100;

        let approvedPrice = regularPrice;
        let isCovered = false;
        let hasError = false;

        if (insType === 'moi') {
            if (!isMoiActive || moiPrice <= 0) {
                hasError = true;
            } else {
                approvedPrice = moiPrice;
                isCovered = true;
            }
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (copayBadge) {
                copayBadge.className = 'badge bg-primary px-3 py-2 fs-7';
                copayBadge.textContent = 'ضمان قوى الأمن الداخلي';
            }
        } else if (insType === 'hi') {
            if (!isHiActive || hiPrice <= 0) {
                hasError = true;
            } else {
                approvedPrice = hiPrice;
                isCovered = true;
            }
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (copayBadge) {
                copayBadge.className = 'badge bg-success px-3 py-2 fs-7';
                copayBadge.textContent = 'هيئة الضمان الصحي الوطني';
            }
        } else {
            // none
            approvedPrice = regularPrice;
            copayPct = 100;
            isCovered = false;
            if (insuranceCardCol) insuranceCardCol.style.display = 'none';
            if (copaySection) copaySection.style.display = 'none';
            if (copayBadge) {
                copayBadge.className = 'badge bg-secondary px-3 py-2 fs-7';
                copayBadge.textContent = 'دفع نقدي كامل (100%)';
            }
        }

        let patientShare = 0;
        let insuranceShare = 0;

        const alertBox = document.getElementById('insuranceErrorAlert');
        const submitBtn = document.getElementById('submitPaymentBtn');

        if (hasError) {
            if (alertBox) alertBox.classList.remove('d-none');
            if (submitBtn) submitBtn.disabled = true;
            approvedPrice = 0;
            patientShare = 0;
            insuranceShare = 0;
        } else {
            if (alertBox) alertBox.classList.add('d-none');
            if (submitBtn) submitBtn.disabled = false;
            
            if (isCovered) {
                patientShare = Math.round(approvedPrice * (copayPct / 100));
                insuranceShare = Math.max(0, approvedPrice - patientShare);
            } else {
                patientShare = approvedPrice;
                insuranceShare = 0;
            }
        }

        if (displayApproved) displayApproved.textContent = formatNumber(approvedPrice);
        if (displayPatientShare) displayPatientShare.textContent = formatNumber(patientShare);
        if (displayInsuranceShare) displayInsuranceShare.textContent = formatNumber(insuranceShare);
        if (displayCopayBadge) displayCopayBadge.textContent = copayPct + '%';

        if (amountInput) {
            amountInput.value = patientShare;
        }
    }

    const hiCategoryCopay = @json($patient ? $patient->getCopayPercentageFor('consultation') : 15.0);
    const moiCopay = @json((float)($patient->copay_percentage ?? 15.0));

    if (insuranceTypeSelect) {
        insuranceTypeSelect.addEventListener('change', function() {
            if (this.value === 'hi') {
                if (copayInput) copayInput.value = hiCategoryCopay;
                if (hiBadge) hiBadge.style.display = 'block';
            } else if (this.value === 'moi') {
                if (copayInput) copayInput.value = moiCopay;
                if (hiBadge) hiBadge.style.display = 'none';
            } else {
                if (hiBadge) hiBadge.style.display = 'none';
            }
            recalculate();
        });
    }

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

            if (val <= 0 && !(insType !== 'none' && copayVal === 0)) {
                e.preventDefault();
                alert('يرجى التأكد من كتابة المبلغ المستحق المطلوب قبضه.');
            }
        });
    }
});
</script>
@endsection
