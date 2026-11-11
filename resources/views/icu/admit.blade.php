@extends('layouts.app')

@section('styles')
<style>
body {
    background-color: #f1f5f9 !important;
    color: #0f172a;
    font-size: 0.88rem;
}

/* كروت مدمجة وأنيقة */
.compact-card {
    background: #ffffff !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04) !important;
    margin-bottom: 14px;
    overflow: hidden;
}

.compact-card-header {
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 10px 14px !important;
    cursor: pointer;
    user-select: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.compact-card-header:hover {
    background: #f8fafc !important;
}

.compact-card-body {
    padding: 14px 16px !important;
    background: #ffffff !important;
}

/* تنسيق الحقول بدقة */
.form-label {
    color: #1e293b !important;
    font-weight: 700 !important;
    font-size: 0.82rem !important;
    margin-bottom: 4px !important;
}

.form-label .text-danger {
    font-size: 0.9rem;
}

#icuAdmitForm .form-control,
#icuAdmitForm .form-select {
    background-color: #ffffff !important;
    border: 1.5px solid #94a3b8 !important;
    border-radius: 5px !important;
    color: #0f172a !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    padding: 0.35rem 0.6rem !important;
    min-height: 36px !important;
    height: 36px !important;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}

#icuAdmitForm .form-control:focus,
#icuAdmitForm .form-select:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.15) !important;
    outline: none !important;
}

/* تبويبات وأزرار الأسرة التفاعلية */
.nav-pills .nav-link {
    border-radius: 5px;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    font-size: 0.8rem;
    padding: 4px 10px;
    transition: all 0.2s ease;
}
.nav-pills .nav-link.active {
    background-color: #2563eb !important;
    color: #ffffff !important;
    border-color: #2563eb;
}

/* بلاطة السرير التفاعلية (مطابقة لبلاطات الغرف بالعمليات) */
.bed-tile {
    width: 64px;
    height: 48px;
    border-width: 2px;
    border-style: solid;
    border-color: #dee2e6;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    background: #ffffff;
    transition: all 0.15s ease;
    margin: 3px;
    padding: 2px;
}

.bed-tile:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.12);
}

.bed-number {
    font-weight: 800;
    font-size: 0.9rem;
    text-align: center;
    line-height: 1;
}

.bed-badges { position: absolute; top: 2px; right: 2px; }
.bed-status { position: absolute; bottom: 2px; left: 3px; }
.bed-actions { position: absolute; top: 2px; left: 3px; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
.bed-selectable { cursor: pointer; }
.bed-selectable[data-available="0"] { cursor: not-allowed; opacity: 0.45; }

/* نتائج البحث المنبثقة */
.autocomplete-results {
    border: 1.5px solid #2563eb;
    border-radius: 6px;
    background: #ffffff;
}

.autocomplete-item {
    cursor: pointer;
    transition: background-color 0.12s ease;
    border-bottom: 1px solid #f1f5f9;
}

.autocomplete-item:hover, .autocomplete-item.active {
    background-color: #eff6ff !important;
}

.accordion-toggle-icon {
    transition: transform 0.2s ease;
}
.collapsed .accordion-toggle-icon {
    transform: rotate(-90deg);
}
</style>
@endsection

@section('content')
<div class="container-fluid py-2">
    <!-- ترويسة الصفحة -->
    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
        <div>
            <h1 class="h5 mb-0 text-dark fw-bold">
                <i class="fas fa-heartbeat text-danger me-2"></i>
                استقبال وحجز مريض بالعناية المركزة (ICU / CCU Admission)
            </h1>
            <small class="text-muted">استعلامات الاستقبال، البحث المباشر عن المريض، الطبيب المشرف، ومسار الضمان والسرير</small>
        </div>
        <a href="{{ route('icu.index') }}" class="btn btn-xs btn-outline-secondary py-1 px-3" style="font-size:0.82rem;">
            <i class="fas fa-arrow-left me-1"></i> العودة للرادار
        </a>
    </div>

    <!-- التنبيهات والأخطاء -->
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3 py-2" role="alert">
            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i> تعذر حفظ الدخول بسبب الأخطاء التالية:</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3 py-2" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('icu.admissions.store') }}" method="POST" id="icuAdmitForm">
        @csrf

        <!-- ================= الكارت 1: استعلامات واستقبال العناية المركزة (البحث الفوري عن المريض) ================= -->
        <div class="compact-card">
            <div class="compact-card-header bg-white" data-bs-toggle="collapse" data-bs-target="#collapsePatientInfo" aria-expanded="true">
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary rounded-circle p-1 me-2" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;">1</span>
                    <h6 class="mb-0 fw-bold text-dark" style="font-size:0.9rem;">
                        <i class="fas fa-id-card text-primary me-1"></i>
                        استعلامات واستقبال العناية: البحث عن المريض ومسار الضمان
                    </h6>
                </div>
                <i class="fas fa-chevron-down text-muted accordion-toggle-icon"></i>
            </div>
            <div id="collapsePatientInfo" class="collapse show">
                <div class="compact-card-body">
                    <!-- الحقل المخفي لمعرف المريض -->
                    <input type="hidden" name="patient_id" id="patient_id" value="{{ old('patient_id', $selectedPatientId) }}" required>

                    <!-- واجهة البحث المباشر السريع من الضغطة الأولى -->
                    <div id="patientSearchContainer" style="{{ old('patient_id') || $selectedPatient ? 'display: none;' : '' }}">
                        <label class="form-label fw-bold">
                            <i class="fas fa-search text-primary me-1"></i>
                            ابحث عن المريض بالاسم أو رقم الهاتف أو رقم الملف: <span class="text-danger">*</span>
                        </label>
                        <div class="position-relative">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" 
                                       class="form-control" 
                                       id="patientSearchInput" 
                                       placeholder="اكتب اسم المريض أو رقم الهاتف واضغط فوراً للاختيار..." 
                                       autocomplete="off"
                                       autofocus>
                                <a href="{{ route('patients.create') }}" target="_blank" class="btn btn-outline-info" title="تسجيل مريض جديد بالنظام">
                                    <i class="fas fa-user-plus me-1"></i> تسجيل مريض جديد
                                </a>
                            </div>
                            <div id="patientSearchSpinner" class="position-absolute" style="left: 140px; top: 10px; display: none;">
                                <span class="spinner-border spinner-border-sm text-primary"></span>
                            </div>
                            <!-- قائمة نتائج البحث المنبثقة -->
                            <div id="patientSearchResults" class="list-group position-absolute w-100 shadow-lg mt-1 autocomplete-results" style="z-index: 1060; max-height: 280px; overflow-y: auto; display: none;"></div>
                        </div>
                        <div class="form-text text-muted mt-1" style="font-size: 0.78rem;">
                            <i class="fas fa-info-circle me-1"></i> اكتب جزءاً من الاسم أو الهاتف وسيظهر المريض فوراً، عند النقر عليه ينزل بكامل بياناته وضمانه في الصفحة.
                        </div>
                    </div>

                    <!-- بطاقة المريض المختار بعد نزوله بالصفحة -->
                    <div id="selectedPatientCard" class="card border-primary bg-light shadow-sm" style="{{ old('patient_id') || $selectedPatient ? '' : 'display: none;' }}">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                                    <i class="fas fa-user-check fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-primary fs-6" id="selectedPatientName">
                                        {{ $selectedPatient?->user?->name ?? $selectedPatient?->name ?? '' }}
                                    </h6>
                                    <div class="small text-muted d-flex flex-wrap gap-3 mt-1 align-items-center">
                                        <span><i class="fas fa-phone-alt me-1 text-secondary"></i><span id="selectedPatientPhone">{{ $selectedPatient?->user?->phone ?? $selectedPatient?->phone ?? 'لا يوجد هاتف' }}</span></span>
                                        <span class="badge bg-secondary" id="selectedPatientIdBadge">#{{ $selectedPatient?->id ?? '' }}</span>
                                        <span class="badge bg-info text-dark" id="selectedPatientAgeBadge">
                                            <span id="selectedPatientAgeText">{{ $selectedPatient?->age ? $selectedPatient->age . ' سنة' : 'العمر غير مسجل' }}</span>
                                        </span>
                                        <span class="badge bg-dark" id="selectedPatientGenderBadge">
                                            <span id="selectedPatientGenderText">{{ $selectedPatient?->gender === 'male' ? 'ذكر' : ($selectedPatient?->gender === 'female' ? 'أنثى' : '') }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="clearSelectedPatientBtn">
                                <i class="fas fa-times me-1"></i> تغيير المريض
                            </button>
                        </div>
                    </div>

                    @php
                        $patientHasInsurance = $selectedPatient && !empty($selectedPatient->insurance_type) && $selectedPatient->insurance_type !== 'none';
                        $defaultIsInsured = $patientHasInsurance ? '1' : '0';
                    @endphp
                    <!-- بطاقة الضمان الصحي المدمجة (تنزل تلقائياً إذا كان المريض مسجلاً بالضمان) -->
                    <div id="patientInsuranceSection" style="{{ (old('is_insured') !== null ? old('is_insured') == '1' : $patientHasInsurance) ? '' : 'display: none;' }}" class="mt-3">
                        <div class="p-3 border rounded-3 bg-white border-success shadow-sm">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
                                <span class="badge bg-success px-2 py-1">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    <span id="patientInsuranceBadgeLabel">{{ $selectedPatient && $selectedPatient->insurance_type === 'moi' ? 'ضمان قوى الأمن الداخلي' : 'هيئة الضمان الصحي الوطني' }}</span>
                                </span>
                                <span class="text-muted small" id="patientInsuranceCardSpan" style="{{ $selectedPatient?->insurance_card_no || $selectedPatient?->insurance_number ? '' : 'display: none;' }}">
                                    <i class="fas fa-id-card me-1 text-primary"></i> رقم الهوية/الدفتر: <b id="patientInsuranceCardNo">{{ $selectedPatient?->insurance_card_no ?: ($selectedPatient?->insurance_number ?: '-') }}</b>
                                </span>
                            </div>
                            
                            <div class="row align-items-center g-2">
                                <div class="col-md-4">
                                    <span class="fw-bold text-dark small d-block">
                                        <i class="fas fa-sliders-h text-primary me-1"></i>
                                        تحديد مسار تسديد العناية المركزة:
                                    </span>
                                    <small class="text-muted">اختر هل الدخول يتبع الضمان أم يسدد نقدياً</small>
                                </div>
                                <div class="col-md-8">
                                    <div class="d-flex flex-wrap gap-3 p-2 bg-light rounded border">
                                        <div class="form-check m-0">
                                            <input class="form-check-input" type="radio" name="is_insured" id="icu_apply_insurance_yes" value="1" {{ old('is_insured', $defaultIsInsured) == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-success cursor-pointer" for="icu_apply_insurance_yes">
                                                <i class="fas fa-check-circle me-1"></i>
                                                🛡️ حجز تحت مظلة الضمان الصحي
                                            </label>
                                        </div>
                                        <div class="form-check m-0">
                                            <input class="form-check-input" type="radio" name="is_insured" id="icu_apply_insurance_no" value="0" {{ old('is_insured', $defaultIsInsured) == '0' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-secondary cursor-pointer" for="icu_apply_insurance_no">
                                                <i class="fas fa-money-bill-wave me-1"></i>
                                                💵 حجز نقدي خاص (كاش كامل خارج الضمان)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="insurance_category" id="insurance_category" value="{{ old('insurance_category', $selectedPatient?->insurance_type) }}">
                            <small class="text-muted d-block mt-2" style="font-size: 0.76rem;"><i class="fas fa-info-circle me-1"></i>سيتم اعتماد هذا الاختيار لدى الكاشير مباشرة عند تسديد تكاليف ورسوم العناية المركزة.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= الكارت 2: الطبيب المشرف، مصدر التحويل، والتقييم السريري ================= -->
        <div class="compact-card">
            <div class="compact-card-header bg-white" data-bs-toggle="collapse" data-bs-target="#collapseClinicalInfo" aria-expanded="true">
                <div class="d-flex align-items-center">
                    <span class="badge bg-danger rounded-circle p-1 me-2" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;">2</span>
                    <h6 class="mb-0 fw-bold text-dark" style="font-size:0.9rem;">
                        <i class="fas fa-stethoscope text-danger me-1"></i>
                        الطبيب المشرف، مصدر التحويل، والتشخيص السريري
                    </h6>
                </div>
                <i class="fas fa-chevron-down text-muted accordion-toggle-icon"></i>
            </div>
            <div id="collapseClinicalInfo" class="collapse show">
                <div class="compact-card-body">
                    <div class="row g-2 mb-3">
                        <!-- اسم الطبيب المشرف (بحث مباشر من الضغطة الأولى بدون نقر مزدوج) -->
                        <div class="col-md-5 position-relative">
                            <label for="doctorSearchInput" class="form-label">
                                الطبيب المشرف / المعالج <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-user-md text-primary"></i></span>
                                <input type="text" 
                                       id="doctorSearchInput" 
                                       class="form-control" 
                                       placeholder="اضغط واكتب اسم الطبيب المشرف مباشرة..." 
                                       autocomplete="off" 
                                       required>
                                <button type="button" class="btn btn-outline-secondary" id="clearDoctorBtn" style="display: none;" title="مسح">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <input type="hidden" name="doctor_id" id="doctor_id" value="{{ old('doctor_id') }}">
                            <!-- قائمة الأطباء المنبثقة للبحث المباشر الفوري -->
                            <div id="doctorSearchResults" class="list-group position-absolute w-100 shadow-lg mt-1 autocomplete-results" style="z-index: 1060; max-height: 250px; overflow-y: auto; display: none;"></div>
                        </div>

                        <!-- مصدر التحويل -->
                        <div class="col-md-4">
                            <label for="admission_source" class="form-label">
                                مصدر التحويل <span class="text-danger">*</span>
                            </label>
                            <select name="admission_source" id="admission_source" class="form-select" required>
                                <option value="emergency" {{ old('admission_source') == 'emergency' ? 'selected' : '' }}>🚨 طوارئ الإنعاش (Emergency)</option>
                                <option value="operation_theater" {{ old('admission_source') == 'operation_theater' ? 'selected' : '' }}>🚪 صالات العمليات (Post-Op Surgical)</option>
                                <option value="ward" {{ old('admission_source') == 'ward' ? 'selected' : '' }}>🛏️ أجنحة الرقود (Ward Deterioration)</option>
                                <option value="external_referral" {{ old('admission_source') == 'external_referral' ? 'selected' : '' }}>🚑 إحالة خارجية من مستشفى آخر</option>
                            </select>
                        </div>

                        <!-- وقت وتاريخ الدخول -->
                        <div class="col-md-3">
                            <label for="admission_date" class="form-label">
                                وقت الدخول <span class="text-danger">*</span>
                            </label>
                            <input type="datetime-local" name="admission_date" id="admission_date" class="form-control" required value="{{ old('admission_date', now()->format('Y-m-d\TH:i')) }}">
                        </div>

                        <!-- اختيار الباقة / نظام التسعير الأولي -->
                        <div class="col-md-6 mt-2">
                            <label for="ccu_service_id" class="form-label">
                                نظام التسعير / باقة العناية الأولية (CCU / ICU Tariff)
                            </label>
                            <select name="ccu_service_id" id="ccu_service_id" class="form-select">
                                <option value="">-- تحديد تلقائي حسب السرير وحالة الفنتليتر والضمان --</option>
                                @foreach(($ccuServices ?? collect()) as $srv)
                                    <option value="{{ $srv->id }}"
                                            data-code="{{ $srv->code }}"
                                            data-cash="{{ (float) $srv->daily_fee }}"
                                            data-hi="{{ (float) $srv->hi_price }}"
                                            data-hi-active="{{ $srv->is_hi_active ? '1' : '0' }}"
                                            data-include-bed="{{ $srv->include_bed_fee ? '1' : '0' }}"
                                            data-bundled-lab="{{ $srv->is_bundled_lab ? '1' : '0' }}"
                                            data-bundled-rad="{{ $srv->is_bundled_radiology ? '1' : '0' }}"
                                            {{ old('ccu_service_id') == $srv->id ? 'selected' : '' }}>
                                        {{ $srv->name }}
                                        @if(!$srv->include_bed_fee)
                                            (سعر السرير منفصل + خدمات إضافية)
                                        @else
                                            (ضمان: {{ number_format($srv->hi_price) }} | كاش: {{ number_format($srv->daily_fee) }} د.ع)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small text-muted" id="servicePricingHint">
                                <i class="fas fa-info-circle text-primary me-1"></i>
                                يمكن للطبيب أو الكاشير تغيير الباقة لاحقاً أو تبديل شمولية التحاليل والأشعة بضغطة زر.
                            </div>
                        </div>

                        <!-- فئة وثيقة الضمان -->
                        <div class="col-md-3 mt-2">
                            <label for="policy_category" class="form-label">فئة الضمان (للشرائح والتسعيرة)</label>
                            <select name="policy_category" id="policy_category" class="form-select">
                                <option value="">تلقائي من بطاقة المريض</option>
                                <option value="A" {{ old('policy_category') == 'A' ? 'selected' : '' }}>الفئة A (إعفاء رعاية اجتماعية 100%)</option>
                                <option value="B" {{ old('policy_category') == 'B' ? 'selected' : '' }}>الفئة B (نفس نسب الوثيقة)</option>
                                <option value="C" {{ old('policy_category') == 'C' ? 'selected' : '' }}>الفئة C (نفس نسب الوثيقة)</option>
                                <option value="D" {{ old('policy_category') == 'D' ? 'selected' : '' }}>الفئة D (نفس نسب الوثيقة)</option>
                                <option value="E" {{ old('policy_category') == 'E' ? 'selected' : '' }}>الفئة E (قياسي 75% → 50% → 25% → 10%)</option>
                            </select>
                        </div>

                        <!-- معاينة اليومية المتوقعة -->
                        <div class="col-md-3 mt-2">
                            <label class="form-label">اليومية المعتمدة مبدئياً</label>
                            <div class="px-3 py-2 rounded border bg-light d-flex align-items-center justify-content-between" style="height: 36px;">
                                <span class="small text-muted">الأجرة/اليوم:</span>
                                <span class="badge bg-primary" id="admitDailyRatePreview">تلقائي</span>
                            </div>
                        </div>

                        <!-- تنويه فصل التشخيص الأولي لطبلة الطبيب -->
                        <div class="col-12 mt-2">
                            <div class="alert alert-info py-2 px-3 mb-0 small d-flex align-items-center gap-2 border-info-subtle bg-info-subtle text-dark">
                                <i class="fas fa-user-md text-info fs-6"></i>
                                <span>
                                    <strong>ملاحظة سير العمل:</strong> التشخيص السريري الأولي، التصنيف الدولي للأمراض <strong>(ICD-10)</strong>، ومقياس الوعي <strong>(GCS)</strong> يتم تحديدها واعتمادها داخل <strong>طبلة المريض</strong> من قِبل الطبيب المعالج وليس من الاستعلامات.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- مفاتيح الميزات السريرية -->
                    <div class="d-flex gap-4 flex-wrap p-2 bg-light rounded border mt-2">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="is_ventilated" value="1" id="is_ventilated" {{ old('is_ventilated') ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold small text-info" for="is_ventilated">
                                <i class="fas fa-lungs me-1"></i> يحتاج جهاز تنفس اصطناعي (Mechanical Ventilator)
                            </label>
                        </div>

                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="is_isolated" value="1" id="is_isolated" {{ old('is_isolated') ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold small text-danger" for="is_isolated">
                                <i class="fas fa-shield-virus me-1"></i> حالة عزل ضغط سالب (Negative Pressure Isolation)
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= الكارت 3: اختيار سرير العناية المركزة والإقامة (في نهاية الصفحة مطابق للعمليات) ================= -->
        <div class="compact-card" id="bedSelectionCard">
            <div class="compact-card-header bg-white" data-bs-toggle="collapse" data-bs-target="#collapseBedStay" aria-expanded="true">
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary rounded-circle p-1 me-2" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;">3</span>
                    <h6 class="mb-0 fw-bold text-dark" style="font-size:0.9rem;">
                        <i class="fas fa-bed text-primary me-1"></i>
                        اختيار سرير العناية المركزة والإقامة <span class="text-danger">*</span>
                    </h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span id="header_bed_badge" class="badge bg-light text-muted border" style="font-size:0.75rem;">لم يتم الاختيار</span>
                    <i class="fas fa-chevron-down text-muted accordion-toggle-icon"></i>
                </div>
            </div>
            <div id="collapseBedStay" class="collapse show">
                <div class="compact-card-body">
                    <input type="hidden" name="icu_bed_id" id="icu_bed_id" value="{{ old('icu_bed_id', $selectedBedId) }}" required>

                    <div class="row g-2 mb-2 align-items-center">
                        <div class="col-md-9">
                            <div class="px-3 py-2 bg-light rounded border d-flex justify-content-between align-items-center small flex-wrap gap-2">
                                <div>
                                    <span class="text-muted">السرير المختار:</span> <b id="selected_bed_name" class="text-dark">لم يتم الاختيار</b>
                                    <span class="mx-2 text-muted">|</span>
                                    <span class="text-muted">النوع:</span> <b id="selected_bed_type" class="text-primary">-</b>
                                    <span class="mx-2 text-muted">|</span>
                                    <span class="text-muted">الحالة:</span> <b id="selected_bed_status" class="text-success">-</b>
                                </div>
                                <div>
                                    <span class="text-muted">السعر اليومي الفندقي:</span> 
                                    <b id="selected_bed_price" class="badge bg-success fs-6 ms-1">0 د.ع</b>
                                </div>
                            </div>
                            <div id="bed_validation_error" class="text-danger small mt-1 d-none">
                                <i class="fas fa-exclamation-circle me-1"></i> يرجى اختيار سرير عناية شاغر للمريض من القائمة التفاعلية أدناه.
                            </div>
                        </div>
                        <div class="col-md-3 text-end" id="clear_bed_section" style="display: none;">
                            <button type="button" class="btn btn-sm btn-outline-danger w-100" id="clear_bed_btn" style="height:36px;">
                                <i class="fas fa-times me-1"></i> إلغاء اختيار السرير
                            </button>
                        </div>
                    </div>

                    <!-- دليل ألوان مدمج -->
                    <div class="d-flex gap-3 py-1 mb-2 small text-muted border-top border-bottom" style="font-size:0.75rem;">
                        <div><span class="badge bg-success me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> شاغر ومتاح</div>
                        <div><span class="badge bg-danger me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> مشغول بمريض</div>
                        <div><span class="badge bg-warning me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> قيد التعقيم</div>
                        <div><span class="badge bg-secondary me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> صيانة</div>
                    </div>

                    <!-- تبويبات أنواع أسرة العناية (General, CCU, Neuro, Isolation) -->
                    <div class="mb-2">
                        <ul class="nav nav-pills gap-1" id="bedTypePills" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-1 px-2" id="pills-bed-all-tab" data-bs-toggle="pill" data-bs-target="#pills-bed-all" type="button" role="tab">
                                    <i class="fas fa-th-large me-1"></i> كل الأسرة
                                    <span class="badge bg-secondary bg-opacity-75 ms-1" style="font-size:0.65rem;">{{ $beds->count() }}</span>
                                </button>
                            </li>
                            @php
                                $bedsByType = $beds->groupBy('bed_type');
                            @endphp
                            @foreach($bedsByType as $type => $typeBeds)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-1 px-2" id="pills-bed-{{ $type }}-tab" data-bs-toggle="pill" data-bs-target="#pills-bed-{{ $type }}" type="button" role="tab">
                                    <i class="fas fa-layer-group me-1"></i>
                                    {{ match($type) {
                                        'general' => 'عناية عامة',
                                        'ccu' => 'عناية قلبية (CCU)',
                                        'neuro' => 'عناية عصبية (Neuro)',
                                        'isolation' => 'عزل ضغط سالب',
                                        default => $type
                                    } }}
                                    <span class="badge bg-secondary bg-opacity-75 ms-1" style="font-size:0.65rem;">{{ $typeBeds->count() }}</span>
                                </button>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- محتوى التبويبات والبلاطات التفاعلية للأسرة -->
                    <div class="tab-content" id="bedPillsContent">
                        <!-- تبويب كل الأسرة -->
                        <div class="tab-pane fade show active" id="pills-bed-all" role="tabpanel">
                            <div class="d-flex flex-wrap gap-1 p-2 bg-light rounded border bed-tiles-container">
                                @foreach($beds as $b)
                                    @php
                                        $borderHex = match($b->status) {
                                            'available'   => '#16a34a',
                                            'occupied'    => '#dc2626',
                                            'sterilizing' => '#d97706',
                                            default       => '#64748b'
                                        };
                                        $typeBg = match($b->bed_type) {
                                            'isolation' => 'bg-danger bg-opacity-10',
                                            'ccu'       => 'bg-warning bg-opacity-10',
                                            'neuro'     => 'bg-purple bg-opacity-10',
                                            default     => ''
                                        };
                                        $isAvail = $b->isAvailable();
                                    @endphp
                                    <div class="bed-tile bed-selectable {{ $typeBg }}"
                                         data-bed-id="{{ $b->id }}"
                                         data-bed-number="{{ $b->bed_number }}"
                                         data-bed-type="{{ $b->type_name }}"
                                         data-bed-price="{{ $b->daily_price }}"
                                         data-available="{{ $isAvail ? '1' : '0' }}"
                                         data-status-color="{{ $borderHex }}"
                                         data-bs-toggle="tooltip"
                                         title="سرير #{{ $b->bed_number }} ({{ $b->type_name }}) - {{ $b->status_name }} - {{ number_format($b->daily_price) }} د.ع"
                                         style="border-color: {{ $borderHex }}; {{ !$isAvail ? 'pointer-events:none; opacity:0.45;' : '' }}">

                                        <div class="bed-number" style="color: {{ $borderHex }};">{{ $b->bed_number }}</div>
                                        <div class="bed-badges">
                                            @if($b->bed_type === 'isolation')
                                                <span class="badge bg-danger text-white" style="font-size: 0.45rem; padding: 1px 2px; line-height:1;">عزل</span>
                                            @elseif($b->bed_type === 'ccu')
                                                <span class="badge bg-warning text-dark" style="font-size: 0.45rem; padding: 1px 2px; line-height:1;">CCU</span>
                                            @elseif($b->bed_type === 'neuro')
                                                <span class="badge bg-purple text-white" style="font-size: 0.45rem; padding: 1px 2px; line-height:1;">Neuro</span>
                                            @endif
                                        </div>
                                        <div class="bed-status">
                                            <span class="status-dot" style="background-color: {{ $borderHex }};"></span>
                                        </div>
                                        @if($isAvail)
                                        <div class="bed-actions" style="display: none;">
                                            <i class="fas fa-check-circle text-success" style="font-size: 0.75rem;"></i>
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- تبويبات الأنواع الفردية -->
                        @foreach($bedsByType as $type => $typeBeds)
                        <div class="tab-pane fade" id="pills-bed-{{ $type }}" role="tabpanel">
                            <div class="d-flex flex-wrap gap-1 p-2 bg-light rounded border bed-tiles-container">
                                @foreach($typeBeds as $b)
                                    @php
                                        $borderHex = match($b->status) {
                                            'available'   => '#16a34a',
                                            'occupied'    => '#dc2626',
                                            'sterilizing' => '#d97706',
                                            default       => '#64748b'
                                        };
                                        $isAvail = $b->isAvailable();
                                    @endphp
                                    <div class="bed-tile bed-selectable"
                                         data-bed-id="{{ $b->id }}"
                                         data-bed-number="{{ $b->bed_number }}"
                                         data-bed-type="{{ $b->type_name }}"
                                         data-bed-price="{{ $b->daily_price }}"
                                         data-available="{{ $isAvail ? '1' : '0' }}"
                                         data-status-color="{{ $borderHex }}"
                                         data-bs-toggle="tooltip"
                                         title="سرير #{{ $b->bed_number }} ({{ $b->type_name }}) - {{ $b->status_name }} - {{ number_format($b->daily_price) }} د.ع"
                                         style="border-color: {{ $borderHex }}; {{ !$isAvail ? 'pointer-events:none; opacity:0.45;' : '' }}">

                                        <div class="bed-number" style="color: {{ $borderHex }};">{{ $b->bed_number }}</div>
                                        <div class="bed-status">
                                            <span class="status-dot" style="background-color: {{ $borderHex }};"></span>
                                        </div>
                                        @if($isAvail)
                                        <div class="bed-actions" style="display: none;">
                                            <i class="fas fa-check-circle text-success" style="font-size: 0.75rem;"></i>
                                        </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= شريط الأزرار النهائي ================= -->
        <div class="d-flex justify-content-between align-items-center p-3 bg-white border rounded shadow-sm mb-4">
            <a href="{{ route('icu.index') }}" class="btn btn-sm btn-outline-danger px-3">
                <i class="fas fa-times me-1"></i> إلغاء العودة للرادار
            </a>
            <button type="submit" class="btn btn-sm btn-success px-5 fw-bold" id="submitAdmitBtn" style="font-size:0.92rem;">
                <i class="fas fa-check-circle me-1"></i> تأكيد حجز وتسكين المريض بالعناية
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
@php
    $doctorsList = $doctors->map(function($doc) {
        return [
            'id' => $doc->id,
            'name' => optional($doc->user)->name ?? 'طبيب #'.$doc->id,
            'specialization' => $doc->specialization ?? 'عناية مركزة وتخدير',
        ];
    })->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ================= 1. استعلامات وبحث المرضى المباشر من الضغطة الأولى =================
    const patientSearchInput = document.getElementById('patientSearchInput');
    const patientSearchResults = document.getElementById('patientSearchResults');
    const patientSearchSpinner = document.getElementById('patientSearchSpinner');
    const patientSearchContainer = document.getElementById('patientSearchContainer');
    const selectedPatientCard = document.getElementById('selectedPatientCard');
    const selectedPatientName = document.getElementById('selectedPatientName');
    const selectedPatientPhone = document.getElementById('selectedPatientPhone');
    const selectedPatientIdBadge = document.getElementById('selectedPatientIdBadge');
    const selectedPatientAgeText = document.getElementById('selectedPatientAgeText');
    const selectedPatientGenderText = document.getElementById('selectedPatientGenderText');
    const clearSelectedPatientBtn = document.getElementById('clearSelectedPatientBtn');
    const patientIdInput = document.getElementById('patient_id');

    // أقسام الضمان الصحي
    const patientInsuranceSection = document.getElementById('patientInsuranceSection');
    const patientInsuranceBadgeLabel = document.getElementById('patientInsuranceBadgeLabel');
    const patientInsuranceCardNo = document.getElementById('patientInsuranceCardNo');
    const patientInsuranceCardSpan = document.getElementById('patientInsuranceCardSpan');
    const insuranceCategoryInput = document.getElementById('insurance_category');
    const icuApplyInsuranceYes = document.getElementById('icu_apply_insurance_yes');
    const icuApplyInsuranceNo = document.getElementById('icu_apply_insurance_no');

    let patientSearchTimeout = null;

    if (patientSearchInput) {
        patientSearchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(patientSearchTimeout);

            if (query.length < 1) {
                patientSearchResults.style.display = 'none';
                patientSearchResults.innerHTML = '';
                return;
            }

            if (patientSearchSpinner) patientSearchSpinner.style.display = 'block';

            patientSearchTimeout = setTimeout(() => {
                fetch(`{{ route('icu.search-patients') }}?query=${encodeURIComponent(query)}`)
                    .then(res => res.json())
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
                            btn.className = 'list-group-item list-group-item-action py-2 px-3 text-start autocomplete-item';
                            const ageStr = (p.age !== null && p.age !== undefined) ? p.age + ' سنة' : 'العمر غير مسجل';
                            const isInsured = p.insurance_type && p.insurance_type !== 'none';

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
                                        ${isInsured ? '<br><span class="badge bg-success">' + p.insurance_label + '</span>' : ''}
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
                        console.error('Error fetching patients:', err);
                        if (patientSearchSpinner) patientSearchSpinner.style.display = 'none';
                    });
            }, 250);
        });

        // إغلاق النتائج عند النقر خارجها
        document.addEventListener('click', function(e) {
            if (patientSearchInput && patientSearchResults && !patientSearchInput.contains(e.target) && !patientSearchResults.contains(e.target)) {
                patientSearchResults.style.display = 'none';
            }
        });
    }

    function selectPatient(p) {
        if (!p) return;

        if (patientIdInput) patientIdInput.value = p.id;
        if (selectedPatientName) selectedPatientName.textContent = p.name;
        if (selectedPatientPhone) selectedPatientPhone.textContent = p.phone || 'لا يوجد هاتف';
        if (selectedPatientIdBadge) selectedPatientIdBadge.textContent = '#' + p.id;
        if (selectedPatientAgeText) selectedPatientAgeText.textContent = p.age ? p.age + ' سنة' : 'العمر غير مسجل';
        if (selectedPatientGenderText) {
            selectedPatientGenderText.textContent = p.gender === 'male' ? 'ذكر' : (p.gender === 'female' ? 'أنثى' : (p.gender || ''));
        }

        if (patientSearchResults) {
            patientSearchResults.style.display = 'none';
            patientSearchResults.innerHTML = '';
        }

        if (patientSearchContainer) patientSearchContainer.style.display = 'none';
        if (selectedPatientCard) selectedPatientCard.style.display = 'block';

        // تفاعل الضمان الصحي التلقائي
        if (p.insurance_type && p.insurance_type !== 'none') {
            if (patientInsuranceBadgeLabel) patientInsuranceBadgeLabel.textContent = 'مشمول بالضمان: ' + p.insurance_label;
            if (patientInsuranceCardNo) patientInsuranceCardNo.textContent = p.insurance_card_no || 'غير مسجل';
            if (patientInsuranceCardSpan) {
                patientInsuranceCardSpan.style.display = p.insurance_card_no ? 'inline' : 'none';
            }
            if (insuranceCategoryInput) insuranceCategoryInput.value = p.insurance_type;
            if (icuApplyInsuranceYes) icuApplyInsuranceYes.checked = true;
            if (patientInsuranceSection) $(patientInsuranceSection).slideDown(150);
        } else {
            if (insuranceCategoryInput) insuranceCategoryInput.value = '';
            if (icuApplyInsuranceNo) icuApplyInsuranceNo.checked = true;
            if (patientInsuranceSection) $(patientInsuranceSection).slideUp(100);
        }
    }

    if (clearSelectedPatientBtn) {
        clearSelectedPatientBtn.addEventListener('click', function() {
            if (patientIdInput) patientIdInput.value = '';
            if (patientSearchInput) {
                patientSearchInput.value = '';
                patientSearchInput.focus();
            }
            if (selectedPatientCard) selectedPatientCard.style.display = 'none';
            if (patientInsuranceSection) $(patientInsuranceSection).slideUp(100);
            if (patientSearchContainer) patientSearchContainer.style.display = 'block';
            setTimeout(() => patientSearchInput?.focus(), 50);
        });
    }

    // ================= 2. الطبيب المشرف: بحث فوري مباشر من الضغطة الأولى بدون نقر مزدوج =================
    const doctorsList = {!! json_encode($doctorsList) !!};
    const doctorSearchInput = document.getElementById('doctorSearchInput');
    const doctorSearchResults = document.getElementById('doctorSearchResults');
    const doctorIdInput = document.getElementById('doctor_id');
    const clearDoctorBtn = document.getElementById('clearDoctorBtn');

    function renderDoctorSuggestions(filterText = '') {
        if (!doctorSearchResults) return;
        doctorSearchResults.innerHTML = '';

        const search = filterText.toLowerCase().trim();
        const filtered = doctorsList.filter(d => {
            return !search || d.name.toLowerCase().includes(search) || d.specialization.toLowerCase().includes(search);
        });

        if (filtered.length === 0) {
            doctorSearchResults.innerHTML = `
                <div class="list-group-item text-center py-2 text-muted small">
                    <i class="fas fa-user-md me-1"></i> لا يوجد طبيب مطابق للبحث
                </div>
            `;
            doctorSearchResults.style.display = 'block';
            return;
        }

        filtered.forEach(doc => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action py-2 px-3 text-start autocomplete-item';
            btn.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong class="text-dark"><i class="fas fa-user-md me-1 text-primary"></i>د. ${doc.name}</strong>
                    </div>
                    <div>
                        <span class="badge bg-light text-primary border">${doc.specialization}</span>
                    </div>
                </div>
            `;

            btn.addEventListener('click', function() {
                selectDoctor(doc);
            });

            doctorSearchResults.appendChild(btn);
        });

        doctorSearchResults.style.display = 'block';
    }

    function selectDoctor(doc) {
        if (!doc) return;
        if (doctorIdInput) doctorIdInput.value = doc.id;
        if (doctorSearchInput) {
            doctorSearchInput.value = `د. ${doc.name} (${doc.specialization})`;
            doctorSearchInput.classList.add('bg-light', 'text-primary', 'fw-bold');
        }
        if (clearDoctorBtn) clearDoctorBtn.style.display = 'block';
        if (doctorSearchResults) doctorSearchResults.style.display = 'none';
    }

    if (doctorSearchInput) {
        // ضغطة واحدة مباشرة تفتح القائمة وتمكّن الكتابة فوراً دون نقر مزدوج
        doctorSearchInput.addEventListener('focus', function() {
            if (!doctorIdInput.value) {
                renderDoctorSuggestions(this.value);
            }
        });

        doctorSearchInput.addEventListener('input', function() {
            if (doctorIdInput.value) {
                doctorIdInput.value = '';
                this.classList.remove('bg-light', 'text-primary', 'fw-bold');
                if (clearDoctorBtn) clearDoctorBtn.style.display = 'none';
            }
            renderDoctorSuggestions(this.value);
        });

        // إغلاق النتائج عند النقر خارجها
        document.addEventListener('click', function(e) {
            if (doctorSearchInput && doctorSearchResults && !doctorSearchInput.contains(e.target) && !doctorSearchResults.contains(e.target)) {
                doctorSearchResults.style.display = 'none';
            }
        });
    }

    if (clearDoctorBtn) {
        clearDoctorBtn.addEventListener('click', function() {
            if (doctorIdInput) doctorIdInput.value = '';
            if (doctorSearchInput) {
                doctorSearchInput.value = '';
                doctorSearchInput.classList.remove('bg-light', 'text-primary', 'fw-bold');
                doctorSearchInput.focus();
            }
            this.style.display = 'none';
            renderDoctorSuggestions('');
        });
    }

    // استرجاع الطبيب المختار مسبقاً إن وجد
    const oldDoctorId = "{{ old('doctor_id') }}";
    if (oldDoctorId) {
        const foundDoc = doctorsList.find(d => d.id == oldDoctorId);
        if (foundDoc) selectDoctor(foundDoc);
    }

    // ================= 3. نظام اختيار السرير التفاعلي بالبلاطات في نهاية الصفحة =================
    const bedIdInput = document.getElementById('icu_bed_id');
    const selectedBedNameEl = document.getElementById('selected_bed_name');
    const selectedBedTypeEl = document.getElementById('selected_bed_type');
    const selectedBedStatusEl = document.getElementById('selected_bed_status');
    const selectedBedPriceEl = document.getElementById('selected_bed_price');
    const clearBedSection = document.getElementById('clear_bed_section');
    const clearBedBtn = document.getElementById('clear_bed_btn');
    const headerBedBadge = document.getElementById('header_bed_badge');
    const bedValidationError = document.getElementById('bed_validation_error');
    const bedPillsContent = document.getElementById('bedPillsContent');

    // حفظ ألوان الحواف الأصلية
    document.querySelectorAll('.bed-tile').forEach(t => {
        t.dataset.origBorder = t.dataset.statusColor || t.style.borderColor || '#dee2e6';
        t.dataset.origWidth  = '2px';
    });

    function selectBed(tile) {
        if (tile.dataset.available === '0') return;

        if (bedValidationError) bedValidationError.classList.add('d-none');
        if (bedPillsContent) bedPillsContent.style.border = '';

        // إعادة ضبط جميع البلاطات
        document.querySelectorAll('.bed-tile').forEach(t => {
            t.style.borderColor = t.dataset.origBorder;
            t.style.borderWidth = t.dataset.origWidth;
            t.style.boxShadow   = '';
            const icon = t.querySelector('.bed-actions');
            if (icon) icon.style.display = 'none';
        });

        // تمييز السرير المختار بلون أزرق وإطار عريض ومربع اختيار
        const targetBedId = tile.dataset.bedId;
        document.querySelectorAll(`.bed-tile[data-bed-id="${targetBedId}"]`).forEach(t => {
            t.style.borderColor = '#0d6efd';
            t.style.borderWidth = '3px';
            t.style.boxShadow   = '0 0 0 3px rgba(13,110,253,.25)';
            const icon = t.querySelector('.bed-actions');
            if (icon) icon.style.display = 'block';
        });

        if (bedIdInput) bedIdInput.value = targetBedId;

        const bedNumber = tile.dataset.bedNumber;
        const bedType = tile.dataset.bedType;
        const bedPrice = parseFloat(tile.dataset.bedPrice) || 0;

        const displayName = `سرير #${bedNumber} (${bedType})`;
        if (selectedBedNameEl) selectedBedNameEl.textContent = `سرير #${bedNumber}`;
        if (selectedBedTypeEl) selectedBedTypeEl.textContent = bedType;
        if (selectedBedStatusEl) {
            selectedBedStatusEl.textContent = 'شاغر وجاهز للاستقبال';
            selectedBedStatusEl.className = 'text-success fw-bold';
        }
        if (selectedBedPriceEl) {
            selectedBedPriceEl.textContent = bedPrice.toLocaleString('en-US') + ' د.ع/يوم';
        }
        if (headerBedBadge) {
            headerBedBadge.textContent = displayName;
            headerBedBadge.className = 'badge bg-success text-white';
        }

        if (clearBedSection) clearBedSection.style.display = 'block';
    }

    // ربط النقر بالبلاطات
    document.querySelectorAll('.bed-selectable').forEach(tile => {
        tile.addEventListener('click', function() { selectBed(this); });
    });

    // زر إلغاء اختيار السرير
    if (clearBedBtn) {
        clearBedBtn.addEventListener('click', function() {
            if (bedIdInput) bedIdInput.value = '';
            document.querySelectorAll('.bed-tile').forEach(t => {
                t.style.borderColor = t.dataset.origBorder;
                t.style.borderWidth = t.dataset.origWidth;
                t.style.boxShadow   = '';
                const icon = t.querySelector('.bed-actions');
                if (icon) icon.style.display = 'none';
            });
            if (selectedBedNameEl) selectedBedNameEl.textContent = 'لم يتم الاختيار';
            if (selectedBedTypeEl) selectedBedTypeEl.textContent = '-';
            if (selectedBedStatusEl) selectedBedStatusEl.textContent = '-';
            if (selectedBedPriceEl) selectedBedPriceEl.textContent = '0 د.ع';
            if (headerBedBadge) {
                headerBedBadge.textContent = 'لم يتم الاختيار';
                headerBedBadge.className = 'badge bg-light text-muted border';
            }
            if (clearBedSection) clearBedSection.style.display = 'none';
        });
    }

    // اختيار السرير المسبق إن وجد
    const initialBedId = "{{ old('icu_bed_id', $selectedBedId) }}";
    if (initialBedId) {
        const preTile = document.querySelector(`.bed-tile[data-bed-id="${initialBedId}"]`);
        if (preTile) selectBed(preTile);
    }

    // ================= 4. التزامن الداينمكي لمعاينة الباقة وسعر السرير والفنتليتر =================
    const ccuServiceSelect = document.getElementById('ccu_service_id');
    const isVentCheckbox = document.getElementById('is_ventilated');
    const admitDailyRatePreview = document.getElementById('admitDailyRatePreview');
    const servicePricingHint = document.getElementById('servicePricingHint');

    function getSelectedBedDailyPrice() {
        const curBedId = bedIdInput ? bedIdInput.value : '';
        if (!curBedId) return 0;
        const tile = document.querySelector(`.bed-tile[data-bed-id="${curBedId}"]`);
        return tile ? (parseFloat(tile.dataset.bedPrice) || 0) : 0;
    }

    function updateTariffPreview() {
        if (!ccuServiceSelect || !admitDailyRatePreview) return;

        const isInsuredSelected = icuApplyInsuranceYes && icuApplyInsuranceYes.checked;
        const opt = ccuServiceSelect.options[ccuServiceSelect.selectedIndex];
        const bedPrice = getSelectedBedDailyPrice();

        if (!opt || !opt.value) {
            if (isVentCheckbox && isVentCheckbox.checked) {
                admitDailyRatePreview.textContent = 'تلقائي (فنتليتر)';
                admitDailyRatePreview.className = 'badge bg-info text-dark';
            } else if (isInsuredSelected) {
                admitDailyRatePreview.textContent = 'تلقائي (باقة الضمان)';
                admitDailyRatePreview.className = 'badge bg-success';
            } else if (bedPrice > 0) {
                admitDailyRatePreview.textContent = bedPrice.toLocaleString('en-US') + ' د.ع (سرير كاش)';
                admitDailyRatePreview.className = 'badge bg-primary';
            } else {
                admitDailyRatePreview.textContent = 'تلقائي';
                admitDailyRatePreview.className = 'badge bg-secondary';
            }
            return;
        }

        const includeBed = opt.dataset.includeBed === '1';
        const hiActive = opt.dataset.hiActive === '1';
        const hiFee = parseFloat(opt.dataset.hi) || 0;
        const cashFee = parseFloat(opt.dataset.cash) || 0;
        const bundledLab = opt.dataset.bundledLab === '1';

        const serviceFee = (isInsuredSelected && hiActive && hiFee > 0) ? hiFee : cashFee;
        const totalDaily = includeBed ? serviceFee : (serviceFee + bedPrice);

        admitDailyRatePreview.textContent = totalDaily.toLocaleString('en-US') + ' د.ع/يوم';
        admitDailyRatePreview.className = includeBed ? 'badge bg-primary' : 'badge bg-warning text-dark';

        if (servicePricingHint) {
            if (!includeBed) {
                servicePricingHint.innerHTML = `<i class="fas fa-layer-group text-warning me-1"></i> نظام منفصل: يحتسب سعر السرير (${bedPrice.toLocaleString('en-US')} د.ع) + الخدمات والتحاليل المضافة منفصلة.`;
            } else if (bundledLab && isInsuredSelected) {
                servicePricingHint.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i> الباقة مشمولة الفحوصات المختبرية (تظهر 0 د.ع على المريض مع إمكانية التبديل بضغطة زر).`;
            } else {
                servicePricingHint.innerHTML = `<i class="fas fa-info-circle text-primary me-1"></i> أجرة يومية شاملة، ويمكن التبديل بين الباقات لاحقاً دون تكرار اليومية.`;
            }
        }
    }

    if (ccuServiceSelect) {
        ccuServiceSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt && isVentCheckbox) {
                if (opt.dataset.code === 'ventilator_support') {
                    isVentCheckbox.checked = true;
                } else if (opt.value && opt.dataset.code !== 'cash_bed_only_separate') {
                    isVentCheckbox.checked = false;
                }
            }
            updateTariffPreview();
        });
    }

    if (isVentCheckbox) {
        isVentCheckbox.addEventListener('change', function() {
            if (ccuServiceSelect && this.checked) {
                const ventOpt = Array.from(ccuServiceSelect.options).find(o => o.dataset.code === 'ventilator_support');
                if (ventOpt && !ccuServiceSelect.value) {
                    ccuServiceSelect.value = ventOpt.value;
                }
            }
            updateTariffPreview();
        });
    }

    [icuApplyInsuranceYes, icuApplyInsuranceNo].forEach(el => {
        if (el) el.addEventListener('change', updateTariffPreview);
    });

    document.querySelectorAll('.bed-selectable').forEach(tile => {
        tile.addEventListener('click', () => setTimeout(updateTariffPreview, 20));
    });
    if (clearBedBtn) {
        clearBedBtn.addEventListener('click', () => setTimeout(updateTariffPreview, 20));
    }

    updateTariffPreview();

    // التحقق عند الإرسال
    function validateBedSelection() {
        const val = bedIdInput ? bedIdInput.value : '';
        if (!val) {
            if (bedValidationError) bedValidationError.classList.remove('d-none');
            if (bedPillsContent) {
                bedPillsContent.style.border = '2.5px solid #dc3545';
                bedPillsContent.style.borderRadius = '8px';
            }

            const bedCollapse = document.getElementById('collapseBedStay');
            if (bedCollapse) {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(bedCollapse, { toggle: false });
                bsCollapse.show();
            }

            const oldToast = document.querySelector('.bed-required-toast');
            if (oldToast) oldToast.remove();
            const toast = document.createElement('div');
            toast.className = 'bed-required-toast alert alert-danger position-fixed shadow-lg';
            toast.style.cssText = 'top:25px;left:50%;transform:translateX(-50%);z-index:9999;border-right:6px solid #dc3545;font-weight:bold;font-size:0.95rem;background:#fff;';
            toast.innerHTML = '<i class="fas fa-exclamation-triangle text-danger me-2"></i> يرجى اختيار سرير عناية مركزة متاح للمريض قبل الحفظ.';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4500);

            document.getElementById('bedSelectionCard')?.scrollIntoView({ behavior: 'smooth' });
            return false;
        }

        if (bedValidationError) bedValidationError.classList.add('d-none');
        if (bedPillsContent) bedPillsContent.style.border = '';
        return true;
    }

    const form = document.getElementById('icuAdmitForm');
    const submitBtn = document.getElementById('submitAdmitBtn');

    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            if (!validateBedSelection()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateBedSelection()) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>
@endsection
