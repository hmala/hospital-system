@extends('layouts.app')

@section('styles')
<style>
/* تحسين عرض حجز الحاضنات */
.incubator-card {
    position: relative;
    width: 240px;
    border: 2px solid #dee2e6;
    border-radius: 14px;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
}

.incubator-card .selection-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #0d6efd;
    color: white;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 2px 8px rgba(13,110,253,0.4);
    z-index: 100;
}

.incubator-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.12);
}

.incubator-card.selected,
input[type="radio"].incubator-radio:checked + label.incubator-card {
    border-color: #0d6efd !important;
    box-shadow: 0 10px 24px rgba(13,110,253,0.3) !important;
    background: rgba(13,110,253,0.1) !important;
    transform: scale(1.02) !important;
}

input[type="radio"].incubator-radio:checked + label.incubator-card .selection-indicator {
    display: flex !important;
}

/* ضمان أن البطاقة هي بلوك كامل بحيث يتم تفعيلها عند النقر */
label.incubator-card {
    display: block !important;
    margin: 0 !important;
}

.incubator-card[data-available="0"] {
    opacity: 0.55;
    cursor: not-allowed;
    background: red !important;
    color: white !important;
}
.incubator-card[data-available="0"] .card-body {
    pointer-events: none;
}

/* تنسيقات Select2 للبحث السريع عن الطبيب المشرف */
.select2-container--bootstrap-5 .select2-selection--single {
    min-height: 38px !important;
    height: 38px !important;
    border: 1px solid #ced4da !important;
    border-radius: 0.375rem !important;
    padding: 0.375rem 0.75rem !important;
    display: flex !important;
    align-items: center !important;
    background-color: #ffffff !important;
    font-size: 0.95rem !important;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
}

.select2-container--bootstrap-5.select2-container--focus .select2-selection,
.select2-container--bootstrap-5.select2-container--open .select2-selection {
    border-color: #86b7fe !important;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
    outline: 0 !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    color: #1e293b !important;
    padding-right: 0 !important;
    padding-left: 25px !important;
    font-weight: 600 !important;
    line-height: 1.5 !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__placeholder {
    color: #6c757d !important;
    font-weight: normal !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow {
    position: absolute !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    left: 10px !important;
    right: auto !important;
    width: 20px !important;
    height: 20px !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__clear {
    position: absolute !important;
    left: 28px !important;
    right: auto !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    font-size: 1.1rem !important;
    color: #dc3545 !important;
    font-weight: bold !important;
}

.select2-dropdown {
    border: 1px solid #86b7fe !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    font-size: 0.9rem !important;
    z-index: 1060 !important;
    overflow: hidden !important;
}

.select2-search--dropdown {
    padding: 8px !important;
    background: #f8fafc !important;
    border-bottom: 1px solid #e2e8f0 !important;
}

.select2-search--dropdown .select2-search__field {
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    padding: 6px 12px !important;
    outline: none !important;
    width: 100% !important;
    box-sizing: border-box !important;
    font-size: 0.9rem !important;
    direction: rtl !important;
    text-align: right !important;
}

.select2-search--dropdown .select2-search__field:focus {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2) !important;
}

.select2-results__options {
    max-height: 220px !important;
    overflow-y: auto !important;
    padding: 4px 0 !important;
}

.select2-results__option {
    padding: 8px 14px !important;
    font-size: 0.9rem !important;
    font-weight: 500 !important;
    color: #1e293b !important;
    border-bottom: 1px solid #f1f5f9 !important;
    cursor: pointer !important;
    transition: background-color 0.15s ease !important;
}

.select2-results__option--highlighted[aria-selected] {
    background-color: #0d6efd !important;
    color: #ffffff !important;
}

.select2-results__option[aria-selected="true"] {
    background-color: #e0edff !important;
    color: #0d6efd !important;
    font-weight: 700 !important;
}
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-baby-carriage me-2 text-primary"></i>
                        حجز حاضنة جديدة
                    </h2>
                    <p class="text-muted">إنشاء حجز جديد لحاضنة خدج</p>
                </div>
                <div>
                    <a href="{{ route('incubators.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(isset($existingReservation) && $existingReservation)
        <!-- Modal: Existing Reservation Notice -->
        <div class="modal fade" id="existingReservationModal" tabindex="-1" aria-labelledby="existingReservationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="existingReservationModalLabel">معلومة مهمة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <p>هذا الطفل لديه حجز نشط بالفعل في الحاضنة رقم <strong>{{ $existingReservation->incubator->incubator_number }}</strong>.</p>
                        <p>يمكنك إما إكمال الحجز الحالي أو إلغاءه قبل إنشاء حجز جديد.</p>
                        <div class="mt-3">
                            <a href="{{ route('incubator-reservations.show', $existingReservation) }}" class="btn btn-primary w-100">عرض الحجز الحالي</a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    </div>
                </div>
            </div>
        </div>
    @endif


    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>
                        بيانات الحجز
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('incubator-reservations.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- بيانات المريض (الأم أو المسؤول) والطفل الخديج -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-user-circle me-2 text-info"></i>
                                    بيانات المريض (الأم أو المسؤول) والطفل الخديج
                                </h6>
                            </div>

                            <div class="col-md-12 mb-3">
                                <!-- الحقل المخفي لمعرف المريض -->
                                <input type="hidden" name="patient_id" id="patient_id" value="{{ old('patient_id', $patient?->id ?? '') }}" required>

                                <!-- واجهة البحث المباشر عن المريض -->
                                <div id="patientSearchContainer" style="{{ old('patient_id') || isset($patient) ? 'display: none;' : '' }}">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-search text-primary me-1"></i>
                                        ابحث عن المريض (الأم أو المسؤول) <span class="text-danger">*</span>
                                    </label>
                                    <div class="position-relative">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                            <input type="text" 
                                                   class="form-control border-start-0 @error('patient_id') is-invalid @enderror" 
                                                   id="patientSearchInput" 
                                                   placeholder="اكتب اسم المريض أو رقم الهاتف..."
                                                   autocomplete="off">
                                            <a href="{{ route('patients.create') }}" target="_blank" class="btn btn-outline-info" title="تسجيل مريض جديد في النظام">
                                                <i class="fas fa-user-plus me-1"></i> مريض جديد
                                            </a>
                                        </div>
                                        <div id="patientSearchSpinner" class="position-absolute" style="left: 125px; top: 10px; display: none;">
                                            <span class="spinner-border spinner-border-sm text-primary"></span>
                                        </div>
                                        <!-- قائمة نتائج البحث المنبثقة -->
                                        <div id="patientSearchResults" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1050; max-height: 280px; overflow-y: auto; display: none;"></div>
                                    </div>
                                    @error('patient_id')
                                        <div class="text-danger small mt-1"><i class="fas fa-exclamation-circle me-1"></i>{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-muted mt-1">
                                        <i class="fas fa-info-circle me-1"></i>
                                        اكتب جزءاً من اسم المريض أو رقم الهاتف للبحث الفوري واختيار الملف.
                                    </div>
                                </div>

                                <!-- بطاقة المريض المختار -->
                                <div id="selectedPatientCard" class="card border-primary bg-light shadow-sm" style="{{ old('patient_id') || isset($patient) ? '' : 'display: none;' }}">
                                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 46px; height: 46px;">
                                                <i class="fas fa-user-check fa-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-primary" id="selectedPatientName">
                                                    {{ old('patient_name', optional($patient?->user)->name ?? '') }}
                                                </h6>
                                                <div class="small text-muted d-flex flex-wrap gap-2 mt-1 align-items-center">
                                                    <span><i class="fas fa-phone-alt me-1 text-secondary"></i><span id="selectedPatientPhone">{{ optional($patient?->user)->phone ?? 'لا يوجد هاتف' }}</span></span>
                                                    <span class="badge bg-secondary" id="selectedPatientIdBadge">#{{ old('patient_id', $patient?->id ?? '') }}</span>
                                                    <span class="badge bg-info text-dark" id="selectedPatientAgeBadge" style="{{ isset($patient) && $patient->age !== null ? '' : 'display: none;' }}">
                                                        <span id="selectedPatientAgeText">{{ isset($patient) && $patient->age !== null ? ($patient->age < 1 ? 'أقل من سنة' : $patient->age . ' سنة') : '' }}</span>
                                                    </span>

                                                    <!-- شارة صفة القيد المحددة تلقائياً حسب العمر -->
                                                    @php
                                                        $isBabyPatient = (isset($patient) && $patient->age !== null && $patient->age < 1);
                                                        $defaultRoleValue = old('is_independent_patient', $isBabyPatient ? '1' : '0');
                                                    @endphp
                                                    <span class="badge {{ $defaultRoleValue === '1' ? 'bg-success-subtle text-success border border-success' : 'bg-primary-subtle text-primary border border-primary' }} px-2 py-1" id="selectedPatientRoleBadge" style="{{ old('patient_id') || isset($patient) ? 'display: inline-flex;' : 'display: none;' }}">
                                                        <span id="selectedPatientRoleText">
                                                            @if($defaultRoleValue === '1')
                                                                <i class="fas fa-baby me-1"></i> صفة القيد: طفل خديج (ملف مستقل)
                                                            @else
                                                                <i class="fas fa-user-friends me-1"></i> صفة القيد: الأم أو الأب (ولي أمر بالغ{{ isset($patient) && $patient->age !== null ? ' - ' . $patient->age . ' سنة' : '' }})
                                                            @endif
                                                        </span>
                                                    </span>
                                                    <a href="javascript:void(0);" id="toggleManualRoleBtn" class="small text-decoration-underline text-secondary ms-1" title="تغيير صفة القيد يدوياً إذا لزم الأمر" style="{{ old('patient_id') || isset($patient) ? 'display: inline;' : 'display: none;' }}">
                                                        <i class="fas fa-sliders-h me-1"></i>تعديل الصفة
                                                    </a>

                                                    <span class="badge bg-success" id="selectedPatientInsuranceBadge" style="{{ (isset($patient) && $patient->insurance_type === 'hi') || old('insurance_type') === 'hi' ? 'display: inline-flex;' : 'display: none;' }}">
                                                        <i class="fas fa-shield-alt me-1"></i><span id="selectedPatientInsuranceText">مشمول بالضمان الصحي</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="clearSelectedPatientBtn">
                                            <i class="fas fa-times me-1"></i> تغيير المريض
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- خيار تعديل صفة المريض يدوياً (مخفي افتراضياً لأن النظام يحددها تلقائياً بحسب العمر دون إرباك المستخدم) -->
                            <div class="col-md-12 mb-3" id="manualRoleContainer" style="display: none;">
                                <div class="card p-3 border-0 bg-white shadow-sm" style="border-radius: 10px; border: 1.5px dashed #94a3b8 !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold text-dark mb-0 small">
                                            <i class="fas fa-sliders-h text-primary me-1"></i>
                                            تعديل صفة القيد يدوياً (اختياري - تم تحديدها تلقائياً بناءً على العمر)
                                        </label>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="closeManualRoleBtn" title="اعتماد وإخفاء الخيارات اليدوية">
                                            <i class="fas fa-check me-1"></i> تم
                                        </button>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="form-check p-2.5 border rounded h-100 bg-light" id="parentCheckCard">
                                                <input class="form-check-input" type="radio" name="is_independent_patient" id="patient_is_parent" value="0" {{ $defaultRoleValue === '0' ? 'checked' : '' }}>
                                                <label class="form-check-label w-100 cursor-pointer" for="patient_is_parent">
                                                    <strong class="text-primary d-block"><i class="fas fa-female me-1"></i> المريض المختار هو الأم أو الأب</strong>
                                                    <small class="text-muted">المولود مسجل تابعاً لملف الأم/الأب (النمط الشائع للبالغين)</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check p-2.5 border rounded h-100 bg-light" id="babyCheckCard">
                                                <input class="form-check-input" type="radio" name="is_independent_patient" id="patient_is_baby" value="1" {{ $defaultRoleValue === '1' ? 'checked' : '' }}>
                                                <label class="form-check-label w-100 cursor-pointer" for="patient_is_baby">
                                                    <strong class="text-success d-block"><i class="fas fa-baby me-1"></i> المريض المختار هو الطفل نفسه</strong>
                                                    <small class="text-muted">ملف مستقل للطفل (أقل من سنة - محول أو مسجل مباشرة)</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- حقل اسم الطفل الخديج (اختياري) -->
                            <div class="col-md-12 mb-3">
                                <label for="baby_name" class="form-label fw-bold">
                                    <i class="fas fa-baby text-primary me-1"></i>
                                    اسم الطفل الخديج 
                                    <small class="text-muted fw-normal" id="babyNameHint">(اختياري - يولد تلقائياً كـ "طفل + اسم الأم" إذا تُرِك فارغاً)</small>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-id-badge text-primary"></i></span>
                                    <input type="text" name="baby_name" id="baby_name" 
                                           class="form-control @error('baby_name') is-invalid @enderror" 
                                           placeholder="مثال: طفل مريم علي / يوسف كرار" 
                                           value="{{ old('baby_name') }}">
                                </div>
                                @error('baby_name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- حقول بيان الولادة الرسمي (اختياري وغير إلزامي) -->
                            <div class="col-md-6 mb-2">
                                <label for="birth_certificate_no" class="form-label fw-bold text-secondary">
                                    <i class="fas fa-certificate text-warning me-1"></i>
                                    رقم بيان الولادة الرسمي
                                    <small class="text-muted fw-normal">(اختياري - إن وجد)</small>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white"><i class="fas fa-hashtag text-warning"></i></span>
                                    <input type="text" name="birth_certificate_no" id="birth_certificate_no" 
                                           class="form-control @error('birth_certificate_no') is-invalid @enderror" 
                                           placeholder="مثال: 14820/2026" 
                                           value="{{ old('birth_certificate_no') }}">
                                </div>
                                @error('birth_certificate_no') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6 mb-2">
                                <label for="birth_certificate_date" class="form-label fw-bold text-secondary">
                                    <i class="fas fa-calendar-alt text-warning me-1"></i>
                                    تاريخ إصدار بيان الولادة
                                    <small class="text-muted fw-normal">(اختياري)</small>
                                </label>
                                <input type="date" name="birth_certificate_date" id="birth_certificate_date" 
                                       class="form-control @error('birth_certificate_date') is-invalid @enderror" 
                                       value="{{ old('birth_certificate_date') }}">
                                @error('birth_certificate_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- نوع الحساب والتغطية المالية -->
                        @php
                            $initInsuranceType = old('insurance_type', (isset($patient) && $patient->insurance_type === 'hi') ? 'hi' : 'none');
                            $initPolicyCategory = old('policy_category', (isset($patient) && $patient->healthInsuranceCategory?->code) ? $patient->healthInsuranceCategory->code : 'E');
                            $initInsuranceCardNo = old('insurance_card_no', isset($patient) ? ($patient->insurance_card_no ?: ($patient->insurance_number ?: $patient->insurance_booklet_number)) : '');
                        @endphp
                        <div class="card p-3 mb-4 bg-light border-0 shadow-sm" style="border-radius: 12px;">
                            <div class="row align-items-center">
                                <div class="col-md-5 mb-2 mb-md-0">
                                    <label class="form-label fw-bold mb-1 text-dark">
                                        <i class="fas fa-file-invoice-dollar text-primary me-1"></i>
                                        نوع الحساب والتغطية المالية <span class="text-danger">*</span>
                                    </label>
                                    <p class="text-muted small mb-0">حدد ما إذا كان الحجز كاش (خاص) أو مشمولاً بالضمان الصحي العراقي (HIA)</p>
                                </div>
                                <div class="col-md-7">
                                    <div class="btn-group w-100 shadow-sm" role="group">
                                        <input type="radio" class="btn-check" name="insurance_type" id="type_cash" value="none" autocomplete="off" {{ $initInsuranceType === 'none' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-primary fw-bold py-2" for="type_cash">
                                            <i class="fas fa-money-bill-wave me-1"></i> كاش / خاص
                                        </label>

                                        <input type="radio" class="btn-check" name="insurance_type" id="type_hi" value="hi" autocomplete="off" {{ $initInsuranceType === 'hi' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-success fw-bold py-2" for="type_hi">
                                            <i class="fas fa-shield-alt me-1"></i> ضمان صحي (HIA)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- قسم تفاصيل الضمان الصحي (يظهر فقط عند اختيار ضمان صحي) -->
                        @php
                            $categoriesList = (isset($healthInsuranceCategories) && $healthInsuranceCategories->isNotEmpty())
                                ? $healthInsuranceCategories
                                : \App\Models\HealthInsuranceCategory::where('is_active', true)->orderBy('sort_order')->get();

                            if ($categoriesList->isEmpty()) {
                                $categoriesList = collect([
                                    (object)['code' => 'A', 'name' => 'الفئة A', 'description' => 'أمراض الدم الوراثية، عجز الكلى، المعالج بالديلزة، العوق الذهني بما في ذلك التوحد وذوي الاحتياجات الخاصة (شرط ختم حراري)'],
                                    (object)['code' => 'B', 'name' => 'الفئة B', 'description' => 'يتيم الأبوين (شرط ختم حراري)'],
                                    (object)['code' => 'C', 'name' => 'الفئة C', 'description' => 'معاقو القوات الأمنية والعسكرية (شرط ختم حراري)'],
                                    (object)['code' => 'D', 'name' => 'الفئة D', 'description' => 'المشمولين بالرعاية الاجتماعية'],
                                    (object)['code' => 'E', 'name' => 'الفئة E', 'description' => 'الأطفال دون سن الخامسة'],
                                    (object)['code' => 'F', 'name' => 'الفئة F', 'description' => 'الإناث من غير الموظفات المتقاعدات فوق سن الـ 55'],
                                    (object)['code' => 'G', 'name' => 'الفئة G', 'description' => 'الذكور من غير الموظفين المتقاعدين فوق سن الـ 60'],
                                    (object)['code' => 'H', 'name' => 'الفئة H', 'description' => 'موظفو الدولة المدنيين، العسكريين، والمتقاعدين'],
                                    (object)['code' => 'I', 'name' => 'الفئة I', 'description' => 'الدرجات العليا والخاصة من مدير عام فما فوق'],
                                ]);
                            }
                        @endphp
                        <div id="insuranceDetailsSection" class="card border border-success-subtle bg-success bg-opacity-10 p-3 mb-4 {{ $initInsuranceType === 'hi' ? '' : 'd-none' }}" style="border-radius: 12px;">
                            <h6 class="fw-bold text-success mb-3">
                                <i class="fas fa-id-card me-2"></i> بيانات وثيقة الضمان الصحي والمطالبة الإلكترونية
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="policy_category" class="form-label fw-bold">فئة المشمول بالضمان <span class="text-danger">*</span></label>
                                    <select name="policy_category" id="policy_category" class="form-select border-success">
                                        @foreach($categoriesList as $cat)
                                            <option value="{{ $cat->code }}" 
                                                    data-id="{{ $cat->id ?? '' }}"
                                                    data-code="{{ $cat->code }}"
                                                    {{ $initPolicyCategory === $cat->code ? 'selected' : '' }}>
                                                {{ $cat->name }} ({{ $cat->code }}){{ !empty($cat->description) ? ' - ' . $cat->description : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.74rem;">الفئة الافتراضية للخدج هي الفئة E (الأطفال دون سن الخامسة).</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="insurance_card_no" class="form-label fw-bold">رقم بطاقة الضمان الصحي</label>
                                    <input type="text" name="insurance_card_no" id="insurance_card_no" class="form-control" placeholder="مثال: HIA-987654321" value="{{ $initInsuranceCardNo }}">
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info py-2 px-3 mb-0 small" id="tierPreviewAlert">
                                        <i class="fas fa-info-circle me-1"></i>
                                        <span id="tierPreviewText">يتم احتساب التغطية وفق ضوابط هيئة الضمان الصحي للعمر الحملي.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- اختيار نوع الحاضنة -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-filter me-2 text-warning"></i>
                                    نوع الحاضنة
                                </h6>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="incubator_type" class="form-label">
                                    اختر نوع الحاضنة
                                </label>
                                <select name="incubator_type" id="incubator_type" 
                                        class="form-select @error('incubator_type') is-invalid @enderror">
                                    <option value="">-- عرض جميع الحاضنات --</option>
                                    @foreach($incubatorTypes as $typeValue => $typeLabel)
                                        <option value="{{ $typeValue }}" 
                                                {{ old('incubator_type', $selectedType ?? null) === $typeValue ? 'selected' : '' }}>
                                            {{ $typeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('incubator_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    استخدم الفلترة لتقليل الخيارات وجعل الاختيار أسرع.
                                </small>
                            </div>
                        </div>

                        <!-- اختيار الحاضنة -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-procedures me-2 text-warning"></i>
                                    حاضنات متاحة
                                </h6>
                            </div>

                            <div class="col-12">
                                <div class="d-flex flex-wrap gap-3" id="incubatorCardGrid">
                                    @forelse($incubators as $inc)
                                        @php $isAvailable = $inc->status === 'available'; @endphp
                                        <input type="radio" 
                                               name="incubator_id" 
                                               id="incubator_{{ $inc->id }}" 
                                               value="{{ $inc->id }}" 
                                               class="d-none incubator-radio"
                                               data-incubator-type="{{ $inc->incubator_type }}"
                                               data-available="{{ $isAvailable ? '1' : '0' }}"
                                               {{ old('incubator_id', request('incubator_id')) == $inc->id ? 'checked' : '' }}
                                               {{ $isAvailable ? '' : 'disabled' }}>

                                        <label for="incubator_{{ $inc->id }}" 
                                               class="card incubator-card shadow-sm"
                                               data-incubator-type="{{ $inc->incubator_type }}"
                                               data-available="{{ $isAvailable ? '1' : '0' }}">
                                            <span class="selection-indicator">
                                                <i class="fas fa-check-circle"></i>
                                            </span>
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h5 class="mb-0">{{ $inc->incubator_number }}</h5>
                                                        <small class="text-muted">{{ $inc->type_name }}</small>
                                                    </div>
                                                    <span class="badge bg-{{ $inc->status_color }}">{{ $inc->status_name }}</span>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="text-muted small">
                                                        <i class="fas fa-money-bill-wave me-1"></i>
                                                        {{ number_format($inc->daily_fee) }} د.ع/يوم
                                                    </div>
                                                    @if($inc->room)
                                                        <div class="text-muted small">
                                                            <i class="fas fa-door-open me-1"></i>
                                                            {{ $inc->room->room_number }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </label>
                                    @empty
                                        <div class="alert alert-info w-100 text-center">
                                            <i class="fas fa-info-circle me-1"></i>
                                            لا توجد حاضنات متاحة حالياً
                                        </div>
                                    @endforelse
                                </div>
                                @error('incubator_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    انقر على الحاضنة لتحديدها (الحالة يجب أن تكون "متاحة").
                                </small>
                            </div>
                        </div>

                        <!-- باقة وخدمة الخدج السريرية -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-stethoscope me-2 text-primary"></i>
                                    باقة وخدمة الخدج السريرية (التسعيرة اليومية)
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="nicu_service_id" class="form-label fw-bold">
                                    اختر باقة الخدمة السريرية <span class="text-danger">*</span>
                                </label>
                                <select name="nicu_service_id" id="nicu_service_id" class="form-select @error('nicu_service_id') is-invalid @enderror" required>
                                    <option value="">-- اختر الباقة السريرية --</option>
                                    @foreach($nicuServices as $service)
                                        <option value="{{ $service->id }}" 
                                                data-name="{{ $service->name }}"
                                                data-cash="{{ $service->daily_fee }}"
                                                data-hi="{{ $service->hi_price > 0 && $service->is_hi_active ? $service->hi_price : $service->daily_fee }}"
                                                data-claim="{{ $service->official_claim_name ?? '' }}"
                                                {{ old('nicu_service_id') == $service->id ? 'selected' : '' }}>
                                            {{ $service->name }} — {{ number_format($service->daily_fee) }} د.ع/يوم
                                        </option>
                                    @endforeach
                                </select>
                                @error('nicu_service_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="doctor_id" class="form-label fw-bold">
                                    <i class="fas fa-user-md text-primary me-1"></i> الطبيب الأخصائي المشرف
                                </label>
                                <select name="doctor_id" id="doctor_id" class="form-select select2-doctor @error('doctor_id') is-invalid @enderror" data-placeholder="-- ابحث عن الطبيب المشرف بالاسم أو التخصص... --">
                                    <option value=""></option>
                                    @foreach($doctors as $d)
                                        <option value="{{ $d->id }}" {{ old('doctor_id') == $d->id ? 'selected' : '' }}>
                                            {{ optional($d->user)->name }} ({{ $d->specialization ?? 'أخصائي خدج وأطفال' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('doctor_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="birth_weight" class="form-label fw-bold">وزن الدخول (بالجرام)</label>
                                <input type="number" name="birth_weight" id="birth_weight" class="form-control" placeholder="مثال: 1500" value="{{ old('birth_weight') }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="gestational_age" class="form-label fw-bold">
                                    عمر الحمل (بالأسابيع) <span class="text-danger">*</span>
                                </label>
                                <select name="gestational_age" id="gestational_age" class="form-select" required>
                                    <option value="">-- اختر عمر الحمل --</option>
                                    <option value="أقل من 28 أسبوع" {{ old('gestational_age') == 'أقل من 28 أسبوع' ? 'selected' : '' }}>أقل من 28 أسبوعاً (&lt;28 - شديد الخداجة)</option>
                                    @for($w = 28; $w <= 40; $w++)
                                        <option value="{{ $w }} أسبوع" {{ old('gestational_age') == ($w . ' أسبوع') ? 'selected' : '' }}>
                                            {{ $w }} أسبوعاً {{ $w >= 37 ? '(كامل النمو)' : ($w >= 34 ? '(خداجة متأخرة)' : ($w >= 32 ? '(خداجة متوسطة)' : '(خداجة مبكرة)')) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="admission_source" class="form-label fw-bold">مصدر الحالة</label>
                                <select name="admission_source" id="admission_source" class="form-select">
                                    <option value="internal" {{ old('admission_source') == 'internal' ? 'selected' : '' }}>ولادة طبيعية / داخلية (IN)</option>
                                    <option value="c_section" {{ old('admission_source') == 'c_section' ? 'selected' : '' }}>صالة عمليات قيصرية</option>
                                    <option value="external" {{ old('admission_source') == 'external' ? 'selected' : '' }}>محال من خارج المستشفى (OUT)</option>
                                </select>
                            </div>
                        </div>

                        <!-- بيانات الدخول -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-calendar me-2 text-danger"></i>
                                    بيانات الدخول
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="admission_date" class="form-label">
                                    تاريخ الدخول <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="admission_date" id="admission_date" 
                                       class="form-control @error('admission_date') is-invalid @enderror" 
                                       value="{{ old('admission_date', now()->format('Y-m-d')) }}" required>
                                @error('admission_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="admission_time" class="form-label">
                                    وقت الدخول <span class="text-danger">*</span>
                                </label>
                                <input type="time" name="admission_time" id="admission_time" 
                                       class="form-control @error('admission_time') is-invalid @enderror" 
                                       value="{{ old('admission_time', now()->format('H:i')) }}" required>
                                @error('admission_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- المستندات والمرفقات الرسمية (اختياري - يدعم السكانر والرفع المباشر) -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-paperclip me-2 text-primary"></i>
                                    المستندات الرسمية ومرفقات الخديج <small class="text-muted fw-normal">(اختياري - يدعم المسح بالسكانر والرفع المباشر)</small>
                                </h6>
                            </div>

                            <!-- 1. وثيقة بيان الولادة -->
                            <div class="col-md-4 mb-3">
                                <div class="card p-3 h-100 border-0 bg-light shadow-xs rounded-3" style="border: 1px solid #e2e8f0 !important;">
                                    <label class="form-label fw-bold text-dark small mb-2">
                                        <i class="fas fa-file-medical text-warning me-1"></i> وثيقة بيان الولادة
                                    </label>
                                    <div class="d-flex gap-1 mb-2">
                                        <button type="button" class="btn btn-sm btn-primary flex-grow-1" id="scan_birth_cert_btn" onclick="scanAttachment('birth_cert')" style="height:34px;">
                                            <i class="fas fa-print me-1"></i> مسح بالسكانر
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('birth_certificate_file').click()" style="height:34px;">
                                            <i class="fas fa-upload me-1"></i> رفع ملف
                                        </button>
                                        <input type="file" name="birth_certificate_file" id="birth_certificate_file" class="d-none" accept="image/*,application/pdf" onchange="previewSelectedFile(this, 'birth_cert')">
                                        <textarea id="scanned_birth_certificate" name="scanned_birth_certificate" style="display:none;"></textarea>
                                    </div>
                                    <div id="preview_birth_cert" style="display: none;">
                                        <div class="alert alert-success py-1 px-2 d-flex justify-content-between align-items-center mb-0 small">
                                            <span id="text_birth_cert"><i class="fas fa-check-circle me-1"></i> تم تجهيز الوثيقة</span>
                                            <button type="button" class="btn btn-xs btn-outline-danger py-0" onclick="clearAttachment('birth_cert')"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. البطاقة الموحدة / هوية الأحوال -->
                            <div class="col-md-4 mb-3">
                                <div class="card p-3 h-100 border-0 bg-light shadow-xs rounded-3" style="border: 1px solid #e2e8f0 !important;">
                                    <label class="form-label fw-bold text-dark small mb-2">
                                        <i class="fas fa-id-card text-info me-1"></i> هوية / موحدة الأم أو الأب
                                    </label>
                                    <div class="d-flex gap-1 mb-2">
                                        <button type="button" class="btn btn-sm btn-primary flex-grow-1" id="scan_parent_id_btn" onclick="scanAttachment('parent_id')" style="height:34px;">
                                            <i class="fas fa-print me-1"></i> مسح بالسكانر
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('parent_id_file').click()" style="height:34px;">
                                            <i class="fas fa-upload me-1"></i> رفع ملف
                                        </button>
                                        <input type="file" name="parent_id_file" id="parent_id_file" class="d-none" accept="image/*,application/pdf" onchange="previewSelectedFile(this, 'parent_id')">
                                        <textarea id="scanned_parent_id" name="scanned_parent_id" style="display:none;"></textarea>
                                    </div>
                                    <div id="preview_parent_id" style="display: none;">
                                        <div class="alert alert-success py-1 px-2 d-flex justify-content-between align-items-center mb-0 small">
                                            <span id="text_parent_id"><i class="fas fa-check-circle me-1"></i> تم تجهيز الوثيقة</span>
                                            <button type="button" class="btn btn-xs btn-outline-danger py-0" onclick="clearAttachment('parent_id')"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. تقرير الإحالة / وثيقة التحويل -->
                            <div class="col-md-4 mb-3">
                                <div class="card p-3 h-100 border-0 bg-light shadow-xs rounded-3" style="border: 1px solid #e2e8f0 !important;">
                                    <label class="form-label fw-bold text-dark small mb-2">
                                        <i class="fas fa-file-export text-success me-1"></i> تقرير التحويل / الإحالة
                                    </label>
                                    <div class="d-flex gap-1 mb-2">
                                        <button type="button" class="btn btn-sm btn-primary flex-grow-1" id="scan_referral_doc_btn" onclick="scanAttachment('referral_doc')" style="height:34px;">
                                            <i class="fas fa-print me-1"></i> مسح بالسكانر
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('referral_doc_file').click()" style="height:34px;">
                                            <i class="fas fa-upload me-1"></i> رفع ملف
                                        </button>
                                        <input type="file" name="referral_doc_file" id="referral_doc_file" class="d-none" accept="image/*,application/pdf" onchange="previewSelectedFile(this, 'referral_doc')">
                                        <textarea id="scanned_referral_doc" name="scanned_referral_doc" style="display:none;"></textarea>
                                    </div>
                                    <div id="preview_referral_doc" style="display: none;">
                                        <div class="alert alert-success py-1 px-2 d-flex justify-content-between align-items-center mb-0 small">
                                            <span id="text_referral_doc"><i class="fas fa-check-circle me-1"></i> تم تجهيز الوثيقة</span>
                                            <button type="button" class="btn btn-xs btn-outline-danger py-0" onclick="clearAttachment('referral_doc')"><i class="fas fa-times"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- أزرار التحكم -->
                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('incubators.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>
                                        إلغاء
                                    </a>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-1"></i>
                                        حفظ الحجز
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تفعيل البحث السريع والذكي للأطباء عبر Select2
    if (window.jQuery && typeof $.fn.select2 !== 'undefined') {
        $('#doctor_id').select2({
            theme: 'bootstrap-5',
            dir: 'rtl',
            width: '100%',
            placeholder: '-- ابحث عن الطبيب المشرف بالاسم أو التخصص... --',
            allowClear: true,
            language: {
                noResults: function() {
                    return 'لا توجد نتائج مطابقة للبحث';
                },
                searching: function() {
                    return 'جاري البحث...';
                }
            }
        });
    }

    const typeSelect = document.getElementById('incubator_type');
    const radios = Array.from(document.querySelectorAll('input.incubator-radio'));

    function updateSelectionVisuals() {
        radios.forEach(radio => {
            const card = radio.nextElementSibling;
            if (!card) return;
            
            const indicator = card.querySelector('.selection-indicator');
            
            if (radio.checked) {
                card.classList.add('selected');
                if (indicator) {
                    indicator.style.display = 'flex';
                }
                console.log('Selected card:', radio.value);
            } else {
                card.classList.remove('selected');
                if (indicator) {
                    indicator.style.display = 'none';
                }
            }
        });
    }

    function applyFilter() {
        const selectedType = typeSelect ? typeSelect.value : '';
        radios.forEach(radio => {
            const matches = !selectedType || radio.dataset.incubatorType === selectedType;
            const card = radio.nextElementSibling;
            if (!card) return;
            card.style.display = matches ? '' : 'none';

            // if hidden and currently selected, uncheck
            if (!matches && radio.checked) {
                radio.checked = false;
            }
        });

        updateSelectionVisuals();
    }

    radios.forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('Radio changed:', this.value, this.checked);
            updateSelectionVisuals();
        });
    });

    if (typeSelect) {
        typeSelect.addEventListener('change', applyFilter);
    }

    // Initial update
    updateSelectionVisuals();
    applyFilter();

    // عرض تنبيه الحجز النشط إذا كان موجوداً
    @if(isset($existingReservation) && $existingReservation)
        const existingModal = new bootstrap.Modal(document.getElementById('existingReservationModal'));
        existingModal.show();
    @endif

    // البحث الحي عن المرضى (Live Patient Search)
    const patientSearchInput = document.getElementById('patientSearchInput');
    const patientSearchResults = document.getElementById('patientSearchResults');
    const patientSearchSpinner = document.getElementById('patientSearchSpinner');
    const patientSearchContainer = document.getElementById('patientSearchContainer');
    const selectedPatientCard = document.getElementById('selectedPatientCard');
    const selectedPatientName = document.getElementById('selectedPatientName');
    const selectedPatientPhone = document.getElementById('selectedPatientPhone');
    const selectedPatientIdBadge = document.getElementById('selectedPatientIdBadge');
    const selectedPatientAgeBadge = document.getElementById('selectedPatientAgeBadge');
    const selectedPatientAgeText = document.getElementById('selectedPatientAgeText');
    const selectedPatientInsuranceBadge = document.getElementById('selectedPatientInsuranceBadge');
    const clearSelectedPatientBtn = document.getElementById('clearSelectedPatientBtn');
    const patientIdInput = document.getElementById('patient_id');
    const babyNameInput = document.getElementById('baby_name');

    let searchTimeout = null;

    if (patientSearchInput) {
        patientSearchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(searchTimeout);

            if (query.length < 1) {
                patientSearchResults.style.display = 'none';
                patientSearchResults.innerHTML = '';
                return;
            }

            if (patientSearchSpinner) patientSearchSpinner.style.display = 'block';

            searchTimeout = setTimeout(() => {
                fetch(`{{ route('incubator-reservations.search-patients') }}?query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (patientSearchSpinner) patientSearchSpinner.style.display = 'none';
                        patientSearchResults.innerHTML = '';

                        if (!data || data.length === 0) {
                            patientSearchResults.innerHTML = `
                                <div class="list-group-item text-center py-3 text-muted">
                                    <i class="fas fa-user-slash me-1"></i> لا توجد نتائج مطابقة
                                    <br>
                                    <a href="{{ route('patients.create') }}" target="_blank" class="btn btn-sm btn-link mt-1">
                                        <i class="fas fa-plus"></i> تسجيل مريض جديد الآن
                                    </a>
                                </div>
                            `;
                            patientSearchResults.style.display = 'block';
                            return;
                        }

                        data.forEach(p => {
                            const btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'list-group-item list-group-item-action py-2 px-3 text-start';
                            const ageStr = p.age !== null && p.age !== undefined ? (p.age < 1 ? 'أقل من سنة' : p.age + ' سنة') : 'العمر غير مسجل';
                            const isInsured = p.insurance_type === 'hi';

                            btn.innerHTML = `
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong class="text-primary"><i class="fas fa-user me-1 text-secondary"></i>${p.name}</strong>
                                        <div class="small text-muted">
                                            <i class="fas fa-phone me-1"></i>${p.phone} 
                                            ${p.national_id ? ' | رقم الهوية: ' + p.national_id : ''}
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary mb-1">${ageStr}</span>
                                        ${isInsured ? '<br><span class="badge bg-success">ضمان صحي</span>' : ''}
                                    </div>
                                </div>
                            `;

                            btn.addEventListener('click', function() {
                                selectPatient(p);
                            });

                            patientSearchResults.appendChild(btn);
                        });

                        patientSearchResults.style.display = 'block';
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        if (patientSearchSpinner) patientSearchSpinner.style.display = 'none';
                    });
            }, 300);
        });

        // إغلاق النتائج عند النقر خارجها
        document.addEventListener('click', function(e) {
            if (patientSearchInput && patientSearchResults && !patientSearchInput.contains(e.target) && !patientSearchResults.contains(e.target)) {
                patientSearchResults.style.display = 'none';
            }
        });
    }

    function selectPatient(p) {
        if (!patientIdInput) return;
        patientIdInput.value = p.id;
        if (selectedPatientName) selectedPatientName.textContent = p.name;
        if (selectedPatientPhone) selectedPatientPhone.textContent = p.phone;
        if (selectedPatientIdBadge) selectedPatientIdBadge.textContent = '#' + p.id;

        if (selectedPatientAgeBadge && selectedPatientAgeText) {
            if (p.age !== null && p.age !== undefined) {
                selectedPatientAgeText.textContent = p.age < 1 ? 'أقل من سنة' : p.age + ' سنة';
                selectedPatientAgeBadge.style.display = 'inline-block';
            } else {
                selectedPatientAgeBadge.style.display = 'none';
            }
        }

        // عناصر صفة المريض المختار والتحديد التلقائي
        const radioBaby = document.getElementById('patient_is_baby');
        const radioParent = document.getElementById('patient_is_parent');
        const roleBadge = document.getElementById('selectedPatientRoleBadge');
        const roleText = document.getElementById('selectedPatientRoleText');
        const toggleManualRoleBtn = document.getElementById('toggleManualRoleBtn');
        const manualRoleContainer = document.getElementById('manualRoleContainer');
        const babyHint = document.getElementById('babyNameHint');

        // التحديد التلقائي لصفة القيد بناءً على العمر (إذا العمر أقل من سنة فهو طفل، وإلا فهو الأم أو الأب)
        const isBaby = (p.age !== null && p.age !== undefined && p.age < 1);
        if (isBaby) {
            if (radioBaby) radioBaby.checked = true;
            if (roleBadge) {
                roleBadge.className = 'badge bg-success-subtle text-success border border-success px-2 py-1';
                roleBadge.style.display = 'inline-flex';
            }
            if (roleText) {
                roleText.innerHTML = '<i class="fas fa-baby me-1"></i> صفة القيد: طفل خديج (ملف طفل مستقل - العمر أقل من سنة)';
            }
            if (babyNameInput) {
                babyNameInput.placeholder = p.name;
                if (!babyNameInput.value) {
                    babyNameInput.value = p.name;
                }
            }
            if (babyHint) {
                babyHint.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> تم التعرف على المريض كـ (طفل مستقل) تلقائياً بحسب العمر (أقل من سنة). تم اعتماد الاسم.</span>';
            }
        } else {
            if (radioParent) radioParent.checked = true;
            if (roleBadge) {
                roleBadge.className = 'badge bg-primary-subtle text-primary border border-primary px-2 py-1';
                roleBadge.style.display = 'inline-flex';
            }
            if (roleText) {
                const ageLabel = (p.age !== null && p.age !== undefined) ? ` (${p.age} سنة)` : '';
                roleText.innerHTML = `<i class="fas fa-user-friends me-1"></i> صفة القيد: الأم أو الأب (ولي أمر بالغ${ageLabel})`;
            }
            if (babyNameInput) {
                const autoPlaceholder = p.name.startsWith('طفل') ? p.name : ('طفل ' + p.name);
                babyNameInput.placeholder = autoPlaceholder;
                if (babyNameInput.value === p.name) {
                    babyNameInput.value = '';
                }
            }
            if (babyHint) {
                const ageLabel = (p.age !== null && p.age !== undefined) ? ` (${p.age} سنة)` : '';
                babyHint.innerHTML = `<span class="text-primary"><i class="fas fa-magic me-1"></i> تم التعرف تلقائياً على أن المريض هو (الأم أو الأب) لأن العمر أكبر من سنة${ageLabel}. يولد اسم الطفل تلقائياً كـ "طفل + اسم ولي الأمر" إن تُرِك فارغاً.</span>`;
            }
        }

        if (toggleManualRoleBtn) toggleManualRoleBtn.style.display = 'inline';
        if (manualRoleContainer) manualRoleContainer.style.display = 'none';

        if (selectedPatientInsuranceBadge) {
            if (p.insurance_type === 'hi') {
                selectedPatientInsuranceBadge.style.display = 'inline-flex';
                // تفعيل خيار الضمان الصحي تلقائياً إن كان المريض مشمولاً
                const hiRadio = document.getElementById('type_hi');
                if (hiRadio && !hiRadio.checked) {
                    hiRadio.checked = true;
                    hiRadio.dispatchEvent(new Event('change'));
                }
                const polCat = document.getElementById('policy_category');
                if (polCat) {
                    polCat.value = p.policy_category || 'E';
                    polCat.dispatchEvent(new Event('change'));
                }
                if (p.insurance_card_no) {
                    const cardInput = document.getElementById('insurance_card_no');
                    if (cardInput && !cardInput.value) {
                        cardInput.value = p.insurance_card_no;
                    }
                }
            } else {
                selectedPatientInsuranceBadge.style.display = 'none';
                const cashRadio = document.getElementById('type_cash');
                const hiRadio = document.getElementById('type_hi');
                if (cashRadio && (!hiRadio || !hiRadio.checked)) {
                    cashRadio.checked = true;
                    cashRadio.dispatchEvent(new Event('change'));
                }
                const polCat = document.getElementById('policy_category');
                if (polCat && (!polCat.value || polCat.value === '')) {
                    polCat.value = 'E';
                    polCat.dispatchEvent(new Event('change'));
                }
            }
        }

        if (patientSearchResults) patientSearchResults.style.display = 'none';
        if (patientSearchContainer) patientSearchContainer.style.display = 'none';
        if (selectedPatientCard) selectedPatientCard.style.display = 'block';
    }

    // تفاعل التبديل اليدوي بين صفة المريض (الأم/الأب أو الطفل) إن رغب المستخدم في تغييره
    const radioBaby = document.getElementById('patient_is_baby');
    const radioParent = document.getElementById('patient_is_parent');
    const roleBadge = document.getElementById('selectedPatientRoleBadge');
    const roleText = document.getElementById('selectedPatientRoleText');
    const toggleManualRoleBtn = document.getElementById('toggleManualRoleBtn');
    const closeManualRoleBtn = document.getElementById('closeManualRoleBtn');
    const manualRoleContainer = document.getElementById('manualRoleContainer');
    const babyHint = document.getElementById('babyNameHint');

    if (toggleManualRoleBtn && manualRoleContainer) {
        toggleManualRoleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (manualRoleContainer.style.display === 'none' || manualRoleContainer.style.display === '') {
                manualRoleContainer.style.display = 'block';
                manualRoleContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                manualRoleContainer.style.display = 'none';
            }
        });
    }

    if (closeManualRoleBtn && manualRoleContainer) {
        closeManualRoleBtn.addEventListener('click', function() {
            manualRoleContainer.style.display = 'none';
        });
    }

    if (radioBaby) {
        radioBaby.addEventListener('change', function() {
            if (this.checked) {
                if (roleBadge) {
                    roleBadge.className = 'badge bg-success-subtle text-success border border-success px-2 py-1';
                    roleBadge.style.display = 'inline-flex';
                }
                if (roleText) {
                    roleText.innerHTML = '<i class="fas fa-baby me-1"></i> صفة القيد: طفل خديج (ملف طفل مستقل)';
                }
                if (selectedPatientName && selectedPatientName.textContent.trim()) {
                    const name = selectedPatientName.textContent.trim();
                    if (babyNameInput) {
                        babyNameInput.placeholder = name;
                        if (!babyNameInput.value || babyNameInput.value.startsWith('طفل ')) {
                            babyNameInput.value = name;
                        }
                    }
                }
                if (babyHint) babyHint.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i> تم تعيين الصفة يدوياً: ملف طفل مستقل.</span>';
            }
        });
    }

    if (radioParent) {
        radioParent.addEventListener('change', function() {
            if (this.checked) {
                if (roleBadge) {
                    roleBadge.className = 'badge bg-primary-subtle text-primary border border-primary px-2 py-1';
                    roleBadge.style.display = 'inline-flex';
                }
                if (roleText) {
                    roleText.innerHTML = '<i class="fas fa-user-friends me-1"></i> صفة القيد: الأم أو الأب (ولي أمر بالغ)';
                }
                if (selectedPatientName && selectedPatientName.textContent.trim()) {
                    const name = selectedPatientName.textContent.trim();
                    if (babyNameInput) {
                        babyNameInput.placeholder = name.startsWith('طفل') ? name : ('طفل ' + name);
                        if (babyNameInput.value === name) {
                            babyNameInput.value = '';
                        }
                    }
                }
                if (babyHint) babyHint.innerHTML = '<span class="text-primary"><i class="fas fa-magic me-1"></i> تم تعيين الصفة يدوياً: الأم أو الأب (بالغ).</span>';
            }
        });
    }

    if (clearSelectedPatientBtn) {
        clearSelectedPatientBtn.addEventListener('click', function() {
            if (patientIdInput) patientIdInput.value = '';
            if (patientSearchInput) patientSearchInput.value = '';
            if (selectedPatientCard) selectedPatientCard.style.display = 'none';
            if (roleBadge) roleBadge.style.display = 'none';
            if (toggleManualRoleBtn) toggleManualRoleBtn.style.display = 'none';
            if (manualRoleContainer) manualRoleContainer.style.display = 'none';
            if (patientSearchContainer) patientSearchContainer.style.display = 'block';
            if (patientSearchInput) patientSearchInput.focus();
        });
    }

    // التبديل الديناميكي بين الكاش والضمان الصحي
    const typeRadios = document.querySelectorAll('input[name="insurance_type"]');
    const insuranceDetails = document.getElementById('insuranceDetailsSection');
    const serviceSelect = document.getElementById('nicu_service_id');
    const gestationalSelect = document.getElementById('gestational_age');
    const policyCategorySelect = document.getElementById('policy_category');
    const tierPreviewText = document.getElementById('tierPreviewText');
    const tierPreviewAlert = document.getElementById('tierPreviewAlert');

    function updateServicePrices() {
        const isHi = document.getElementById('type_hi') && document.getElementById('type_hi').checked;
        if (serviceSelect) {
            Array.from(serviceSelect.options).forEach(opt => {
                if (!opt.value) return;
                const name = opt.getAttribute('data-name') || opt.text.split('—')[0].trim();
                const cashFee = parseFloat(opt.getAttribute('data-cash')) || 0;
                const hiFee = parseFloat(opt.getAttribute('data-hi')) || cashFee;

                if (isHi) {
                    opt.text = `${name} — ${hiFee.toLocaleString('en-US')} د.ع/يوم (تسعيرة الضمان المعتمدة)`;
                } else {
                    opt.text = `${name} — ${cashFee.toLocaleString('en-US')} د.ع/يوم`;
                }
            });
        }
    }

    function updateTierPreview() {
        if (!tierPreviewText) return;
        const isHi = document.getElementById('type_hi') && document.getElementById('type_hi').checked;
        if (!isHi) return;

        const category = policyCategorySelect ? policyCategorySelect.value : 'E';
        const ageVal = gestationalSelect ? gestationalSelect.value : '';
        let weeks = null;
        if (ageVal.includes('<') || ageVal.includes('أقل')) {
            weeks = 26;
        } else {
            const m = ageVal.match(/\d+/);
            if (m) weeks = parseInt(m[0]);
        }

        if (category === 'A') {
            tierPreviewAlert.className = 'alert alert-success py-2 px-3 mb-0 small';
            tierPreviewText.innerHTML = '<strong>الفئة A (أمراض الدم الوراثية / العجز الكلوي / الشهداء):</strong> إعفاء تام وتغطية 100% لكامل مدة الرقود بدون أي أجور أو نسبة تحمل على المريض.';
            return;
        }

        tierPreviewAlert.className = 'alert alert-info py-2 px-3 mb-0 small';

        if (!weeks) {
            tierPreviewText.innerHTML = '<strong>تغطية الضمان (الفئة E - الأطفال دون سن الخامسة):</strong> يرجى اختيار عمر الحمل بالأسابيع لمعاينة شرائح التحمل المعتمدة وزارياً.';
            return;
        }

        if (weeks >= 34) {
            tierPreviewText.innerHTML = `<strong>عمر الحمل (${weeks} أسبوع - أقصى تغطية 10 أيام):</strong> الأيام 1-5 تغطية 75% (المريض 25%) | الأيام 6-10 تغطية 50% (المريض 50%) | أكثر من 10 أيام 100% على حساب المريض.`;
        } else if (weeks >= 32) {
            tierPreviewText.innerHTML = `<strong>عمر الحمل (${weeks} أسبوع - أقصى تغطية 15 يوماً):</strong> الأيام 1-5 تغطية 75% | الأيام 6-10 تغطية 50% | الأيام 11-15 تغطية 25% | بعد 15 يوماً 100% على حساب المريض.`;
        } else if (weeks >= 28) {
            tierPreviewText.innerHTML = `<strong>عمر الحمل (${weeks} أسبوع - أقصى تغطية 20 يوماً):</strong> الأيام 1-5 تغطية 75% | الأيام 6-10 تغطية 50% | الأيام 11-15 تغطية 25% | الأيام 16-20 تغطية 10% | بعد 20 يوماً 100% على حساب المريض.`;
        } else {
            tierPreviewText.innerHTML = `<strong>عمر الحمل (&lt;28 أسبوع - استثناء خداجة شديدة):</strong> الأيام 1-5 تغطية 75% | الأيام 6-10 تغطية 50% | الأيام 11-15 تغطية 25% | الأيام 16 فما فوق تغطية مستمرة 10% (تحمل المريض 90%).`;
        }
    }

    typeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked && this.value === 'hi') {
                insuranceDetails.classList.remove('d-none');
            } else {
                insuranceDetails.classList.add('d-none');
            }
            updateServicePrices();
            updateTierPreview();
        });
    });

    if (gestationalSelect) {
        gestationalSelect.addEventListener('change', updateTierPreview);
    }
    if (policyCategorySelect) {
        policyCategorySelect.addEventListener('change', updateTierPreview);
    }

    @if(isset($patient))
        // مزامنة وتشغيل التحديد التلقائي الأولي لبيانات المريض المحمل عبر الرابط (?patient_id=...)
        selectPatient({
            id: {{ $patient->id }},
            name: @json(optional($patient->user)->name ?? ''),
            phone: @json(optional($patient->user)->phone ?? ''),
            age: {{ $patient->age !== null ? $patient->age : 'null' }},
            gender: @json($patient->gender),
            insurance_type: @json($patient->insurance_type ?? 'none'),
            policy_category: @json($patient->healthInsuranceCategory?->code ?? null),
            insurance_card_no: @json($patient->insurance_card_no ?: ($patient->insurance_number ?: $patient->insurance_booklet_number))
        });
    @endif

    // تشغيل التحديث الأولي عند تحميل الصفحة
    updateServicePrices();
    updateTierPreview();
});

// وظائف المسح بالسكانر ومعاينة المرفقات
window.scanAttachment = async function(type) {
    const btnMap = {
        'birth_cert': document.getElementById('scan_birth_cert_btn'),
        'parent_id': document.getElementById('scan_parent_id_btn'),
        'referral_doc': document.getElementById('scan_referral_doc_btn')
    };
    const inputMap = {
        'birth_cert': document.getElementById('scanned_birth_certificate'),
        'parent_id': document.getElementById('scanned_parent_id'),
        'referral_doc': document.getElementById('scanned_referral_doc')
    };
    const previewMap = {
        'birth_cert': document.getElementById('preview_birth_cert'),
        'parent_id': document.getElementById('preview_parent_id'),
        'referral_doc': document.getElementById('preview_referral_doc')
    };
    const textMap = {
        'birth_cert': document.getElementById('text_birth_cert'),
        'parent_id': document.getElementById('text_parent_id'),
        'referral_doc': document.getElementById('text_referral_doc')
    };

    const btn = btnMap[type];
    if (!btn) return;

    const origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري المسح...';

    const endpoints = ['http://127.0.0.1:5000/scan', 'http://127.0.0.1:37426/scan'];
    let response = null;
    let lastError = null;

    for (const url of endpoints) {
        try {
            const res = await fetch(url, { method: 'POST', mode: 'cors' });
            if (res.ok) {
                response = res;
                break;
            }
        } catch (e) {
            lastError = e;
        }
    }

    try {
        if (!response) {
            throw lastError || new Error('تعذر الاتصال بالسكانر');
        }
        const blob = await response.blob();
        const reader = new FileReader();
        reader.onloadend = function() {
            const base64data = reader.result;
            if (inputMap[type]) inputMap[type].value = base64data;
            if (textMap[type]) textMap[type].innerHTML = '<i class="fas fa-camera me-1"></i> تم سحب الصورة من السكانر بنجاح';
            if (previewMap[type]) previewMap[type].style.display = 'block';
        };
        reader.readAsDataURL(blob);
    } catch (err) {
        console.error('Scanner error:', err);
        if (window.location.protocol === 'https:') {
            alert('⚠️ تعذر الاتصال بالسكانر المحلي!\n\n1. تأكد من تشغيل ملف (تشغيل_السكانر_التلقائي.bat) على حاسبتك.\n2. على المتصفح: اضغط علامة القفل بجانب الرابط ⬅️ إعدادات الموقع ⬅️ غيّر (Insecure content) إلى سماح (Allow).\n\nأو يمكنك استخدام زر "رفع ملف" كبديل سريع.');
        } else {
            alert('برنامج السكانر غير مشغل أو غير متصل. تأكد من تشغيل (تشغيل_السكانر_التلقائي.bat) أو استخدم زر "رفع ملف".');
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = origText;
    }
};

window.previewSelectedFile = function(input, type) {
    const previewMap = {
        'birth_cert': document.getElementById('preview_birth_cert'),
        'parent_id': document.getElementById('preview_parent_id'),
        'referral_doc': document.getElementById('preview_referral_doc')
    };
    const textMap = {
        'birth_cert': document.getElementById('text_birth_cert'),
        'parent_id': document.getElementById('text_parent_id'),
        'referral_doc': document.getElementById('text_referral_doc')
    };

    if (input.files && input.files[0]) {
        if (textMap[type]) {
            textMap[type].innerHTML = '<i class="fas fa-file-alt me-1"></i> تم اختيار ملف: ' + input.files[0].name;
        }
        if (previewMap[type]) {
            previewMap[type].style.display = 'block';
        }
    }
};

window.clearAttachment = function(type) {
    const fileInputMap = {
        'birth_cert': document.getElementById('birth_certificate_file'),
        'parent_id': document.getElementById('parent_id_file'),
        'referral_doc': document.getElementById('referral_doc_file')
    };
    const scanInputMap = {
        'birth_cert': document.getElementById('scanned_birth_certificate'),
        'parent_id': document.getElementById('scanned_parent_id'),
        'referral_doc': document.getElementById('scanned_referral_doc')
    };
    const previewMap = {
        'birth_cert': document.getElementById('preview_birth_cert'),
        'parent_id': document.getElementById('preview_parent_id'),
        'referral_doc': document.getElementById('preview_referral_doc')
    };

    if (fileInputMap[type]) fileInputMap[type].value = '';
    if (scanInputMap[type]) scanInputMap[type].value = '';
    if (previewMap[type]) previewMap[type].style.display = 'none';
};
</script>
@endsection
