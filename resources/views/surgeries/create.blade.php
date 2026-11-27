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
    margin-bottom: 12px;
    overflow: hidden;
}

.compact-card-header {
    background: #ffffff !important;
    border-bottom: 1px solid #e2e8f0 !important;
    padding: 8px 14px !important;
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
    padding: 12px 14px !important;
    background: #ffffff !important;
}

/* تصغير وتنسيق الحقول بدقة وتباعد عالي */
.form-label {
    color: #1e293b !important;
    font-weight: 700 !important;
    font-size: 0.82rem !important;
    margin-bottom: 3px !important;
}

.form-label .text-danger {
    font-size: 0.9rem;
}

#surgeryForm .form-control,
#surgeryForm .form-select {
    background-color: #ffffff !important;
    border: 1.5px solid #94a3b8 !important;
    border-radius: 5px !important;
    color: #0f172a !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    padding: 0.3rem 0.55rem !important;
    min-height: 34px !important;
    height: 34px !important;
    transition: border-color 0.2s, box-shadow 0.2s !important;
}

#surgeryForm .form-control:focus,
#surgeryForm .form-select:focus {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.15) !important;
    outline: none !important;
}

/* تنسيق Select2 المدمج */
.select2-container--bootstrap-5 .select2-selection {
    background-color: #ffffff !important;
    border: 1.5px solid #94a3b8 !important;
    border-radius: 5px !important;
    min-height: 34px !important;
    height: 34px !important;
    display: flex !important;
    align-items: center !important;
    padding: 0.15rem 0.45rem !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    color: #0f172a !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    color: #0f172a !important;
    font-weight: 700 !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    line-height: 30px !important;
}

.select2-container--bootstrap-5.select2-container--focus .select2-selection,
.select2-container--bootstrap-5.select2-container--open .select2-selection {
    border-color: #2563eb !important;
    box-shadow: 0 0 0 2.5px rgba(37, 99, 235, 0.15) !important;
}

.select2-dropdown {
    border: 1.5px solid #2563eb !important;
    border-radius: 6px !important;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.12) !important;
    font-size: 0.84rem !important;
    z-index: 1060 !important;
}

.select2-results__options {
    max-height: 200px !important;
    overflow-y: auto !important;
}

.select2-results__options::-webkit-scrollbar {
    width: 6px;
}
.select2-results__options::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}
.select2-results__options::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}

.select2-results__group {
    padding: 4px 10px !important;
    font-size: 0.74rem !important;
    background: #f1f5f9 !important;
    color: #475569 !important;
    font-weight: 700 !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-top: 1px solid #e2e8f0 !important;
}

.select2-results__option {
    padding: 6px 8px !important;
    font-weight: 600 !important;
    color: #0f172a !important;
    border-bottom: 1px solid #f1f5f9 !important;
}

.select2-results__option--highlighted[aria-selected] {
    background-color: #2563eb !important;
    color: #ffffff !important;
}

.select2-new-tag {
    background: #f0fdf4;
    padding: 5px 8px;
    border-radius: 4px;
    border: 1.5px dashed #16a34a;
    margin: 2px 0;
    font-size: 0.82rem;
}

/* تبويبات وأزرار الغرف الميكرو */
.nav-pills .nav-link {
    border-radius: 5px;
    color: #475569;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    font-size: 0.8rem;
    padding: 3px 8px;
    transition: all 0.2s ease;
}
.nav-pills .nav-link.active {
    background-color: #2563eb !important;
    color: #ffffff !important;
    border-color: #2563eb;
}

.room-tile {
    width: 54px;
    height: 40px;
    border-width: 2px;
    border-style: solid;
    border-color: #dee2e6;
    border-radius: 5px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    background: #ffffff;
    transition: all 0.15s ease;
    margin: 2px;
    padding: 1px;
}

.room-tile:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}

.room-number {
    font-weight: 800;
    font-size: 0.82rem;
    text-align: center;
    line-height: 1;
}

.room-badges { position: absolute; top: 1px; right: 2px; }
.room-status { position: absolute; bottom: 2px; left: 2px; }
.room-actions { position: absolute; top: 1px; left: 2px; }
.status-dot { width: 5px; height: 5px; border-radius: 50%; display: inline-block; }
.room-selectable { cursor: pointer; }
.room-selectable[data-available="0"] { cursor: not-allowed; opacity: 0.45; }

.accordion-toggle-icon {
    transition: transform 0.2s ease;
}
.collapsed .accordion-toggle-icon {
    transform: rotate(-90deg);
}

/* جدول العمليات الجراحية الموحد */
#surgicalOperationsTable {
    table-layout: fixed;
    width: 100%;
}
#surgicalOperationsTable th {
    vertical-align: middle;
    font-size: 0.82rem;
    font-weight: 700;
    padding: 8px 10px;
    background-color: #f1f5f9;
    border-bottom: 2px solid #cbd5e1;
}
#surgicalOperationsTable td {
    vertical-align: middle;
    padding: 6px 8px;
}
#surgicalOperationsTable .select2-container {
    width: 100% !important;
}
#surgicalOperationsTable .select2-selection--single {
    height: 31px !important;
    padding: 2px 8px !important;
    font-size: 0.85rem !important;
    border-radius: 4px !important;
    display: flex !important;
    align-items: center !important;
}
#surgicalOperationsTable .select2-selection__rendered {
    line-height: 27px !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
}
#surgicalOperationsTable .select2-selection__arrow {
    height: 29px !important;
}

/* تصميم بيئة العمل ثنائية الأعمدة واللوحة الثابتة */
.sticky-action-sidebar {
    position: sticky;
    top: 15px;
    z-index: 1020;
}
.patient-banner-card {
    background: #ffffff !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 12px !important;
    box-shadow: 0 3px 10px rgba(15, 23, 42, 0.04) !important;
}
.sticky-summary-card {
    background: #ffffff !important;
    border: 1.5px solid #cbd5e1 !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06) !important;
}
</style>
@endsection

@section('content')
@php
    $categoriesList = $surgicalOperations->pluck('category')->filter()->unique()->values();
@endphp
<div class="container-fluid py-2">
    <!-- ترويسة مدمجة -->
    <div class="d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom">
        <h1 class="h5 mb-0 text-dark fw-bold">
            <i class="fas fa-procedures text-primary me-2"></i>
            {{ __('حجز عملية جراحية جديدة') }}
        </h1>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-xs btn-outline-secondary py-1 px-2" id="toggleAllSectionsBtn" style="font-size:0.8rem;">
                <i class="fas fa-layer-group me-1"></i> {{ __('طي / توسيع') }}
            </button>
            <a href="{{ route('surgeries.index') }}" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size:0.8rem;">
                <i class="fas fa-arrow-left me-1"></i> {{ __('العمليات') }}
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-2 py-2" role="alert">
            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-1"></i> {{ __('تعذر حفظ العملية بسبب الأخطاء التالية:') }}</div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $pInsType = optional($selectedPatient)->insurance_type ?? 'none';
        $pCardNo = optional($selectedPatient)->insurance_card_no ?: (optional($selectedPatient)->insurance_booklet_number ?: '');
    @endphp

    <!-- نموذج حجز العملية الجراحية -->
    <form action="{{ route('surgeries.store') }}" method="POST" id="surgeryForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="visit_id" value="{{ request('visit_id') }}">

    <!-- شريط هوية المريض التنفيذي العلوي (Patient Executive Banner) -->
    <div id="patientExecutiveBanner" class="patient-banner-card mb-3 p-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm flex-shrink-0" style="width: 50px; height: 50px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); font-size: 1.3rem;">
                    <i class="fas fa-user-injured"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="fw-bold text-dark mb-0" id="bannerPatientName">
                            {{ $selectedPatient?->user?->name ?? 'يرجى اختيار مريض من القائمة أدناه' }}
                        </h5>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                            <i class="fas fa-folder-open me-1"></i> {{ __('ملف #') }}<span id="bannerPatientId">{{ $selectedPatient->id ?? '-' }}</span>
                        </span>
                        <span id="bannerInsuranceBadge" class="badge {{ $pInsType !== 'none' ? 'bg-success text-white' : 'bg-secondary text-white' }} rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                            <i class="fas fa-shield-alt me-1"></i>
                            <span id="bannerInsuranceLabel">{{ $pInsType === 'moi' ? 'ضمان قوى الأمن الداخلي' : ($pInsType === 'hi' ? 'هيئة الضمان الصحي الوطني' : 'حساب خاص (نقدي)') }}</span>
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-1 text-muted small flex-wrap" style="font-size: 0.82rem;">
                        <span><i class="fas fa-birthday-cake text-secondary me-1"></i>{{ __('العمر:') }} <strong id="bannerPatientAge" class="text-dark">{{ $selectedPatient?->age ?? '-' }} {{ __('سنة') }}</strong></span>
                        <span><i class="fas fa-venus-mars text-secondary me-1"></i>{{ __('الجنس:') }} <strong id="bannerPatientGender" class="text-dark">{{ $selectedPatient?->gender ?? '-' }}</strong></span>
                        <span><i class="fas fa-phone text-secondary me-1"></i>{{ __('الهاتف:') }} <strong id="bannerPatientPhone" class="text-dark" dir="ltr">{{ $selectedPatient?->user?->phone ?? '-' }}</strong></span>
                        <span id="bannerCardSpan" style="{{ $pCardNo ? '' : 'display: none;' }}"><i class="fas fa-id-card text-primary me-1"></i>{{ __('رقم الهوية/الدفتر:') }} <strong id="bannerCardNo" class="text-dark">{{ $pCardNo ?: '-' }}</strong></span>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap" id="bannerActionsContainer">
                <a href="{{ $selectedPatient ? route('inquiry.patients.history', $selectedPatient->id) : '#' }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 shadow-xs" id="bannerHistoryBtn" style="font-size: 0.82rem; {{ $selectedPatient ? '' : 'display: none;' }}">
                    <i class="fas fa-notes-medical me-1"></i> {{ __('السجل الطبي الشامل') }}
                </a>
                <a href="{{ $selectedPatient ? route('inquiry.patients.dossier.print', $selectedPatient->id) : '#' }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 shadow-xs" id="bannerDossierBtn" style="font-size: 0.82rem; {{ $selectedPatient ? '' : 'display: none;' }}">
                    <i class="fas fa-print me-1"></i> {{ __('طباعة الإضبارة') }}
                </a>
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill" style="font-size: 0.8rem;">
                    <i class="fas fa-procedures me-1"></i> {{ __('محطة حجز العمليات') }}
                </span>
            </div>
        </div>
    </div>

    <!-- تقسيم بيئة العمل ثنائية الأعمدة: يمين المعلومات (5) + يسار العمليات والغرف (7) -->
    <div class="row g-3">
        <!-- يمين الشاشة: المعلومات الأساسية، الفريق الطبي، والملخص المالي (col-lg-5) -->
        <div class="col-lg-5 col-xl-5">
            <!-- الكارت 1: بيانات المريض والفريق الطبي ومسار التغطية -->
            <div class="compact-card mb-3">
                <div class="compact-card-header" data-bs-toggle="collapse" data-bs-target="#collapseSurgeryInfo" aria-expanded="true">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary rounded-circle p-1 me-2" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;">1</span>
                        <h6 class="mb-0 fw-bold text-dark" style="font-size:0.9rem;">
                            <i class="fas fa-notes-medical text-primary me-1"></i>
                            {{ __('بيانات المريض، الفريق الطبي، والمسار') }}
                        </h6>
                    </div>
                    <i class="fas fa-chevron-down text-muted accordion-toggle-icon"></i>
                </div>
                <div id="collapseSurgeryInfo" class="collapse show">
                    <div class="compact-card-body">
                        <!-- قسم تحديد نوع تغطية العملية (ضمان أم كاش) -->
                        <div id="patientInsuranceSection" style="{{ ($selectedPatient && $pInsType !== 'none') ? '' : 'display: none;' }}" class="mb-3">
                            <div class="p-3 border rounded-3 bg-light border-primary shadow-2xs">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom">
                                    <span class="badge bg-success px-2 py-1">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        <span id="patientInsuranceBadgeLabel">{{ $selectedPatient ? 'مشمول بالضمان: ' . ($pInsType === 'moi' ? 'ضمان قوى الأمن الداخلي' : 'هيئة الضمان الصحي الوطني') : 'المريض مسجل بالضمان' }}</span>
                                    </span>
                                    <span class="text-muted small" id="patientInsuranceCardSpan" style="{{ $pCardNo ? '' : 'display: none;' }}">
                                        <i class="fas fa-id-card me-1 text-primary"></i> {{ __('رقم الهوية/الدفتر:') }} <b id="patientInsuranceCardNo">{{ $pCardNo ?: '-' }}</b>
                                    </span>
                                </div>
                                
                                <div class="mb-2">
                                    <span class="fw-bold text-dark small d-block mb-1">
                                        <i class="fas fa-sliders-h text-primary me-1"></i>
                                        {{ __('تحديد مسار حجز هذه العملية:') }}
                                    </span>
                                    <div class="d-flex flex-column gap-2 p-2 bg-white rounded border">
                                        <div class="form-check m-0">
                                            <input class="form-check-input" type="radio" name="apply_insurance" id="surgery_apply_insurance_yes" value="1" {{ old('apply_insurance', '1') == '1' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-success cursor-pointer" for="surgery_apply_insurance_yes">
                                                <i class="fas fa-check-circle me-1"></i>
                                                <span id="surgery_insurance_option_text">{{ $pInsType === 'moi' ? __('🛡️ حجز تحت مظلة ضمان قوى الأمن الداخلي (MOI)') : __('🛡️ حجز تحت مظلة هيئة الضمان الصحي الوطني') }}</span>
                                            </label>
                                        </div>
                                        <div class="form-check m-0">
                                            <input class="form-check-input" type="radio" name="apply_insurance" id="surgery_apply_insurance_no" value="0" {{ old('apply_insurance') === '0' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-secondary cursor-pointer" for="surgery_apply_insurance_no">
                                                <i class="fas fa-money-bill-wave me-1"></i>
                                                {{ __('💵 حجز نقدي خاص (كاش كامل خارج الضمان)') }}
                                            </label>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size:0.75rem;"><i class="fas fa-info-circle me-1"></i>{{ __('يعتمد لدى الكاشير عند تسديد الحساب واحتساب المطالبات.') }}</small>
                                </div>
                            </div>
                        </div>

                        <!-- المريض (محدد مسبقاً ومعروض في الشريط العلوي) -->
                        <input type="hidden" name="patient_id" id="patient_id" value="{{ old('patient_id', $selectedPatient?->id ?? request('patient_id')) }}">

                        <!-- الأطباء: الجراح + المرسل -->
                        <div class="row g-2 mb-2">
                            <!-- الطبيب الجراح -->
                            <div class="col-md-6">
                                <label for="doctor_id" class="form-label">
                                    {{ __('الطبيب الجراح') }} <span class="text-danger">*</span>
                                </label>
                                <select name="doctor_id" id="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" style="width: 100%;" required>
                                    <option value="">{{ __('اختر الجراح') }}</option>
                                    @foreach($doctors as $doctor)
                                        @php $doctorName = optional($doctor->user)->name ?? __('غير معروف'); @endphp
                                        <option value="{{ $doctor->id }}" {{ (old('doctor_id', request('doctor_id')) == $doctor->id) ? 'selected' : '' }}>
                                            {{ __('د.') }} {{ $doctorName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('doctor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <!-- الطبيب المرسل -->
                            <div class="col-md-6">
                                <label class="form-label">
                                    {{ __('الطبيب المرسل') }} <span class="text-danger">*</span>
                                </label>
                                <select name="referring_doctor_name" id="referring_doctor_name_select" class="form-select @error('referring_doctor_name') is-invalid @enderror" style="width: 100%;" required>
                                    <option value="">{{ __('اختر أو اكتب جديد') }}</option>
                                    <optgroup label="{{ __('أطباء المستشفى') }}">
                                        @foreach($doctors as $doctor)
                                            @php $referringDoctorName = optional($doctor->user)->name ?? ''; @endphp
                                            <option value="{{ $referringDoctorName }}" {{ (old('referring_doctor_name', request('referring_doctor_name')) == $referringDoctorName) ? 'selected' : '' }}>
                                                {{ __('د.') }} {{ $referringDoctorName ?: __('غير معروف') }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                    @php
                                        $externalDoctors = \App\Models\Surgery::whereNotNull('referring_doctor_name')
                                            ->distinct()
                                            ->pluck('referring_doctor_name')
                                            ->filter()
                                            ->unique()
                                            ->reject(function($name) use ($doctors) {
                                                return $doctors->pluck('user.name')->contains($name);
                                            })
                                            ->sort();
                                    @endphp
                                    @if($externalDoctors->isNotEmpty())
                                        <optgroup label="{{ __('أطباء خارجيين سابقين') }}">
                                            @foreach($externalDoctors as $externalDoctor)
                                                <option value="{{ $externalDoctor }}" {{ (old('referring_doctor_name', request('referring_doctor_name')) == $externalDoctor) ? 'selected' : '' }}>
                                                    {{ $externalDoctor }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                @error('referring_doctor_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <!-- التاريخ + ورقة التحويل -->
                        <div class="row g-2 mb-1 align-items-center">
                            <!-- تاريخ العملية -->
                            <div class="col-md-5">
                                <label for="scheduled_date" class="form-label">
                                    {{ __('تاريخ العملية') }} <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="scheduled_date" id="scheduled_date" 
                                       class="form-control @error('scheduled_date') is-invalid @enderror" 
                                       value="{{ old('scheduled_date', date('Y-m-d')) }}" required>
                                @error('scheduled_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <!-- ورقة التحويل الطبي بالسكانر / الرفع -->
                            <div class="col-md-7">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label text-muted mb-0 small fw-bold">{{ __('ورقة التحويل (سكانر / رفع):') }}</label>
                                    <span id="scannerStatusIndicator" class="badge bg-light text-muted border py-0 px-2" style="font-size: 0.72rem;" title="{{ __('حالة السكانر') }}">
                                        <i class="fas fa-circle text-secondary me-1" style="font-size: 7px;"></i>{{ __('غير مفحوص') }}
                                    </span>
                                </div>
                                <div class="d-flex gap-1 flex-wrap" id="referral_letter_container">
                                    <!-- زر كشف وقراءة السكانر المرتبط بالحاسبة -->
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="detect_scanner_btn" onclick="detectScannerDevice(true)" style="height:36px;" title="{{ __('قراءة واكتشاف جهاز السكانر المتصل بالحاسبة') }}">
                                        <i class="fas fa-search me-1" id="detect_spinner"></i> {{ __('كشف السكانر') }}
                                    </button>

                                    <!-- زر المسح من السكانر المكتبي USB -->
                                    <button type="button" class="btn btn-sm btn-primary flex-grow-1" id="scan_btn" onclick="scanFromDevice()" style="height:36px; min-width: 95px;">
                                        <i class="fas fa-print me-1"></i> <span id="scan_btn_label">{{ __('مسح من السكانر') }}</span>
                                    </button>

                                    <!-- زر رفع ملف -->
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('referral_letter').click()" style="height:36px;" title="{{ __('رفع ملف أو صورة من جهازك') }}">
                                        <i class="fas fa-upload me-1"></i> {{ __('رفع ملف') }}
                                    </button>

                                    <input type="file" name="referral_letter" id="referral_letter" class="d-none @error('referral_letter') is-invalid @enderror" accept="image/*,application/pdf">
                                    <textarea id="scan_data_receiver" name="scanned_referral_letter" style="display:none;"></textarea>
                                </div>

                                <div id="referral_letter_preview" class="mt-2" style="display: none;">
                                    <div class="alert alert-success py-1 px-2 d-flex justify-content-between align-items-center mb-0 small rounded-3 shadow-xs border-success">
                                        <div class="d-flex align-items-center gap-2 text-truncate">
                                            <i class="fas fa-check-circle text-success"></i>
                                            <span id="preview_text" class="fw-bold">{{ __('تم سحب/التقاط الوثيقة بنجاح') }}</span>
                                            <button type="button" class="btn btn-link btn-xs text-primary p-0 text-decoration-none fw-bold" onclick="viewCapturedDocument()">
                                                <i class="fas fa-eye me-1"></i>{{ __('معاينة') }}
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 rounded-pill" onclick="clearScannedDoc()">
                                            <i class="fas fa-times me-1"></i> {{ __('إلغاء') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- أزرار الحفظ والإجراءات المباشرة -->
            <div class="d-flex flex-column gap-2 mb-3">
                <button type="submit" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" id="submitBtn" style="font-size: 1rem; border-radius: 8px;">
                    <i class="fas fa-save me-1"></i> {{ __('تأكيد وحجز العملية الجراحية') }}
                </button>
                <a href="{{ route('surgeries.index') }}" class="btn btn-outline-danger w-100 btn-sm" style="border-radius: 6px;">
                    <i class="fas fa-times me-1"></i> {{ __('إلغاء') }} والعودة لقائمة العمليات
                </a>
            </div>

            <div class="alert alert-info py-2 px-3 small border-0 rounded-3 shadow-2xs mb-0" style="font-size: 0.8rem; background: #e0f2fe; color: #0369a1;">
                <i class="fas fa-lightbulb me-1"></i>
                <strong>{{ __('ملاحظة للمسؤول:') }}</strong> {{ __('بمجرد الحجز، تنتقل الحالة لقوائم المقيم والصالات والتمريض والكاشير.') }}
            </div>
        </div>

        <!-- يسار الشاشة: العمليات الجراحية بالأعلى وتحتها اختيار الغرف (col-lg-7) -->
        <div class="col-lg-7 col-xl-7">
            <!-- الكارت 1 (الأعلى): جدول العمليات الجراحية -->
            <div class="compact-card mb-3">
                <div class="compact-card-header" data-bs-toggle="collapse" data-bs-target="#collapseOperations" aria-expanded="true">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary rounded-circle p-1 me-2" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;">2</span>
                        <h6 class="mb-0 fw-bold text-dark" style="font-size:0.9rem;">
                            <i class="fas fa-procedures text-primary me-1"></i>
                            {{ __('جدول العمليات الجراحية المطلوبة') }}
                        </h6>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">{{ __('عدد العمليات:') }} <strong id="totalOpsCount" class="text-primary">1</strong></span>
                        <span id="summaryTotalFee" class="badge bg-success px-3 py-1 fs-7">0 {{ __('د.ع') }}</span>
                        <i class="fas fa-chevron-down text-muted accordion-toggle-icon"></i>
                    </div>
                </div>
                <div id="collapseOperations" class="collapse show">
                    <div class="compact-card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0" id="surgicalOperationsTable" style="min-width: 600px;">
                                <thead>
                                    <tr class="text-secondary text-center small">
                                        <th style="width: 85px;">{{ __('العملية') }}</th>
                                        <th style="width: 26%;">{{ __('صنف العملية') }} <span class="text-danger">*</span></th>
                                        <th style="width: 28%;">{{ __('نوع العملية') }} <span class="text-danger">*</span></th>
                                        <th style="width: 140px;">{{ __('السعر (د.ع)') }} <span class="text-danger">*</span></th>
                                        <th>{{ __('ملاحظات') }}</th>
                                        <th style="width: 65px;">{{ __('إجراء') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="operationsTableBody">
                                    <!-- العملية (1) - العملية الأولى الأساسية -->
                                    <tr id="op_row_primary" class="align-middle bg-white">
                                        <td class="text-center bg-light">
                                            <span class="badge bg-primary text-white px-2 py-1" style="font-size: 0.8rem;">{{ __('العملية (1)') }}</span>
                                        </td>
                                        <td>
                                            <select name="surgery_category" id="surgery_category" class="form-select form-select-sm op-category-select @error('surgery_category') is-invalid @enderror" required style="width: 100%;">
                                                <option value="">{{ __('-- اختر الصنف --') }}</option>
                                                @foreach($categoriesList as $category)
                                                    <option value="{{ $category }}" {{ old('surgery_category') == $category ? 'selected' : '' }}>
                                                        {{ $category }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('surgery_category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </td>
                                        <td>
                                            <select name="surgical_operation_id" id="surgical_operation_id" class="form-select form-select-sm op-name-select @error('surgical_operation_id') is-invalid @enderror" required style="width: 100%;">
                                                <option value="">{{ __('-- اختر العملية --') }}</option>
                                                @foreach($surgicalOperations as $operation)
                                                    <option value="{{ $operation->id }}" 
                                                            data-category="{{ $operation->category }}"
                                                            data-fee="{{ $operation->fee }}"
                                                            {{ old('surgical_operation_id') == $operation->id ? 'selected' : '' }}>
                                                        {{ $operation->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('surgical_operation_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="text" 
                                                       name="custom_surgery_fee" 
                                                       id="custom_surgery_fee" 
                                                       class="form-control form-control-sm op-fee-input text-end fw-bold @error('custom_surgery_fee') is-invalid @enderror" 
                                                       value="{{ old('custom_surgery_fee') }}"
                                                       placeholder="0"
                                                       inputmode="numeric"
                                                       required>
                                                <span class="input-group-text small px-1 bg-light text-muted" style="font-size: 0.72rem;">{{ __('د.ع') }}</span>
                                            </div>
                                            @error('custom_surgery_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </td>
                                        <td>
                                            <input type="text" 
                                                   name="description" 
                                                   id="description" 
                                                   class="form-control form-control-sm" 
                                                   value="{{ old('description') }}"
                                                   placeholder="{{ __('ملاحظات...') }}">
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" title="العملية (1) أساسية مطلوبة">
                                                <i class="fas fa-lock me-1"></i> {{ __('أساسية') }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- زر الإضافة المباشر أسفل الجدول مع الملخص المالي -->
                        <div class="p-2 px-3 bg-light border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-primary fw-bold px-3 py-1 shadow-xs" id="addAnotherOpBtn">
                                <i class="fas fa-plus-circle me-1"></i> {{ __('➕ إضافة عملية أخرى') }}
                            </button>
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted small">{{ __('عدد العمليات:') }} <strong id="totalOpsCountBottom" class="text-primary">1</strong></span>
                                <div class="d-flex align-items-center">
                                    <span class="text-dark small me-2 fw-bold">{{ __('إجمالي سعر العمليات:') }}</span>
                                    <span id="summaryTotalFeeBottom" class="badge bg-success fs-7 px-3 py-1">0 {{ __('د.ع') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- بطاقة تحديد نسبة تحمل مريض قوى الأمن الداخلي (MOI) وحساب المطالبة اللحظي -->
                        <div id="moiCopaySection" class="p-3 m-3 border rounded-3 bg-primary-subtle border-primary-subtle shadow-2xs" style="{{ (old('insurance_type', $pInsType) === 'moi') ? '' : 'display: none;' }}">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2 pb-2 border-bottom border-primary border-opacity-25">
                                <span class="badge bg-primary text-white px-2 py-1">
                                    <i class="fas fa-user-shield me-1"></i>
                                    {{ __('🛡️ تغطية قوى الأمن الداخلي (وزارة الداخلية)') }}
                                </span>
                                <span class="text-muted small">
                                    <i class="fas fa-info-circle me-1 text-primary"></i> {{ __('تحديد نسبة ما يدفعه المريض نقداً وما يُرحل كمطالبة ذمة على وزارة الداخلية') }}
                                </span>
                            </div>

                            <label class="form-label small fw-bold text-dark d-block mb-1">
                                <i class="fas fa-percentage text-primary me-1"></i>
                                {{ __('نسبة تحمل المريض في أجور العملية الجراحية:') }}
                            </label>

                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="p-2 rounded-2 border d-flex align-items-center justify-content-between cursor-pointer bg-white h-100" id="tile_copay_15" style="border-width: 2px !important;">
                                        <div class="form-check m-0">
                                            <input class="form-check-input moi-copay-radio" type="radio" name="copay_percentage" id="copay_15" value="15" {{ old('copay_percentage', '15') == '15' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-primary cursor-pointer ms-1" for="copay_15">
                                                {{ __('15% (النسبة القياسية)') }}
                                            </label>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary small">مطالبة 85%</span>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="p-2 rounded-2 border d-flex align-items-center justify-content-between cursor-pointer bg-white h-100" id="tile_copay_0" style="border-width: 2px !important;">
                                        <div class="form-check m-0">
                                            <input class="form-check-input moi-copay-radio" type="radio" name="copay_percentage" id="copay_0" value="0" {{ old('copay_percentage') === '0' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-success cursor-pointer ms-1" for="copay_0">
                                                {{ __('0% (إعفاء كامل - جرحى/شهداء)') }}
                                            </label>
                                        </div>
                                        <span class="badge bg-success-subtle text-success small">مطالبة 100%</span>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="p-2 rounded-2 border d-flex align-items-center justify-content-between cursor-pointer bg-white h-100" id="tile_copay_100" style="border-width: 2px !important;">
                                        <div class="form-check m-0">
                                            <input class="form-check-input moi-copay-radio" type="radio" name="copay_percentage" id="copay_100" value="100" {{ old('copay_percentage') === '100' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-bold text-secondary cursor-pointer ms-1" for="copay_100">
                                                {{ __('100% (تحمل كامل - خارج التغطية)') }}
                                            </label>
                                        </div>
                                        <span class="badge bg-secondary-subtle text-secondary small">مطالبة 0%</span>
                                    </label>
                                </div>
                            </div>

                            <!-- بطاقة الحساب الفوري لأثر النسبة على العملية -->
                            <div class="p-2 bg-white rounded border d-flex justify-content-around align-items-center flex-wrap gap-2 text-center" id="moiBreakdownBox">
                                <div>
                                    <span class="text-muted text-xs d-block">{{ __('إجمالي سعر العملية') }}</span>
                                    <strong class="text-dark fs-7" id="moiGrossFeeDisp">0 د.ع</strong>
                                </div>
                                <div class="border-start ps-3">
                                    <span class="text-muted text-xs d-block">{{ __('المطلوب كاش من المريض') }}</span>
                                    <strong class="text-success fs-7" id="moiPatientShareDisp">0 د.ع</strong>
                                </div>
                                <div class="border-start ps-3">
                                    <span class="text-muted text-xs d-block">{{ __('مطالبة ذمة على وزارة الداخلية') }}</span>
                                    <strong class="text-primary fs-7" id="moiClaimShareDisp">0 د.ع</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الكارت 2 (الأسفل): حجز الغرفة والإقامة الإجباري -->
            <div class="compact-card mb-3">
                <div class="compact-card-header" data-bs-toggle="collapse" data-bs-target="#collapseRoomStay" aria-expanded="true">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-warning text-dark rounded-circle p-1 me-2" style="width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:0.7rem;">3</span>
                        <h6 class="mb-0 fw-bold text-dark" style="font-size:0.9rem;">
                            <i class="fas fa-door-open text-warning me-1"></i>
                            {{ __('اختيار الغرفة والإقامة السريرية') }} <span class="text-danger">*</span>
                        </h6>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="header_room_badge" class="badge bg-light text-muted border" style="font-size:0.75rem;">{{ __('لم يتم الاختيار') }}</span>
                        <i class="fas fa-chevron-down text-muted accordion-toggle-icon"></i>
                    </div>
                </div>
                <div id="collapseRoomStay" class="collapse show">
                    <div class="compact-card-body">
                        <input type="hidden" name="expected_stay_days" id="expected_stay_days" value="1">
                        <input type="hidden" name="room_waiting_mode" id="room_waiting_mode" value="{{ old('room_waiting_mode', '0') }}">

                        <!-- خيار تخصيص الغرفة الآن أو إبقاء المريض في وضع الانتظار -->
                        <div class="d-flex flex-wrap gap-2 mb-2 p-2 bg-light rounded border">
                            <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill px-3" id="btnRoomDirectMode">
                                <i class="fas fa-bed me-1"></i> {{ __('تخصيص غرفة مباشرة الآن') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold rounded-pill px-3" id="btnRoomWaitingMode">
                                <i class="fas fa-hourglass-half me-1"></i> {{ __('وضع الانتظار (بدون غرفة حالياً — تُخصص لاحقاً)') }}
                            </button>
                        </div>
                        <div id="room_waiting_banner" class="alert alert-warning py-2 px-3 mb-2 d-none small fw-bold border-warning">
                            <i class="fas fa-hourglass-half me-1"></i>
                            {{ __('المريض في وضع انتظار الغرفة: سيتم حجز العملية بدون إشغال غرفة الآن (0 د.ع أجور غرفة مبدئياً) ويمكن للمخولين تخصيص الغرفة المتاحة له لاحقاً.') }}
                        </div>
                        <div class="row g-2 mb-2 align-items-center">
                            <div class="col-md-9">
                                <div class="px-3 py-2 bg-light rounded border d-flex justify-content-between align-items-center small flex-wrap gap-2">
                                    <div>
                                        <span class="text-muted">{{ __('الغرفة:') }}</span> <b id="selected_room_name" class="text-dark">{{ __('لم يتم الاختيار') }}</b>
                                        <span class="mx-2 text-muted">|</span>
                                        <span class="text-muted">{{ __('الحالة:') }}</span> <b id="selected_room_status" class="text-success">-</b>
                                    </div>
                                    <div>
                                        <span class="text-muted">{{ __('أجرة الليلة الأولى (تثبيت الحجز):') }}</span> 
                                        <b id="room_total_fee" class="badge bg-success fs-6 ms-1">0 {{ __('د.ع') }}</b>
                                    </div>
                                </div>
                                <div class="mt-1 text-muted small" style="font-size: 0.76rem;">
                                    <i class="fas fa-info-circle text-primary me-1"></i>
                                    <strong>{{ __('النظام الفندقي:') }}</strong> {{ __('الليلة الأولى تغطي المريض حتى') }} <strong>{{ __('12:00 ظهراً') }}</strong> {{ __('من اليوم التالي. أي ليالٍ إضافية تُحسب تلقائياً عند إجراء الخروج (عادية: مجانية أول ليلة | VIP: 100,000 د.ع | VVIP: 200,000 د.ع).') }}
                                </div>
                            </div>
                            <div class="col-md-3 text-end" id="clear_room_section" style="display: none;">
                                <button type="button" class="btn btn-sm btn-outline-danger w-100" id="clear_room_btn" style="height:38px;">
                                    <i class="fas fa-times me-1"></i> {{ __('إلغاء') }} اختيار الغرفة
                                </button>
                            </div>
                        </div>

                        <!-- دليل ألوان مدمج -->
                        <div class="d-flex gap-3 py-1 mb-2 small text-muted border-top border-bottom" style="font-size:0.75rem;">
                            <div><span class="badge bg-success me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> {{ __('متاحة') }}</div>
                            <div><span class="badge bg-danger me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> {{ __('محجوزة') }}</div>
                            <div><span class="badge bg-warning me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> {{ __('صيانة') }}</div>
                            <div><span class="badge bg-warning text-dark me-1" style="width:7px;height:7px;display:inline-block;padding:0;"></span> VIP</div>
                        </div>

                        <input type="hidden" name="room_id" id="room_id" value="{{ old('room_id', '') }}">

                        <!-- رسالة خطأ عند عدم اختيار غرفة -->
                        <div id="room_validation_error" class="alert alert-danger py-1 mb-2 d-none fw-bold" style="border-right: 4px solid #dc3545; font-size:0.82rem;">
                            <i class="fas fa-exclamation-triangle me-1"></i> {{ __('يرجى النقر على إحدى الغرف المتاحة (باللون الأخضر) لاختيارها للمريض قبل الحجز.') }}
                        </div>

                        <!-- تبويبات الطوابق المدمجة لاختيار الغرفة بأقل مساحة -->
                        @php $roomsByFloor = $rooms->groupBy('floor'); @endphp
                        @if($roomsByFloor->isNotEmpty())
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <ul class="nav nav-pills gap-1" id="floorPills" role="tablist">
                                @foreach($roomsByFloor as $floor => $floorRooms)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                            id="pills-floor-{{ $loop->index }}-tab" 
                                            data-bs-toggle="pill" 
                                            data-bs-target="#pills-floor-{{ $loop->index }}" 
                                            type="button" role="tab">
                                        <i class="fas fa-layer-group me-1"></i> {{ $floor ?: 'عام' }}
                                        <span class="badge bg-secondary bg-opacity-75 ms-1" style="font-size:0.65rem;">{{ $floorRooms->count() }}</span>
                                    </button>
                                </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="tab-content" id="floorPillsContent">
                            @foreach($roomsByFloor as $floor => $floorRooms)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="pills-floor-{{ $loop->index }}" role="tabpanel">
                                <div class="d-flex flex-wrap gap-1 p-1 bg-light rounded border">
                                    @foreach($floorRooms as $room)
                                    @php
                                        $borderHex = match($room->status) {
                                            'available'   => '#16a34a',
                                            'occupied'    => '#dc2626',
                                            'maintenance' => '#d97706',
                                            default       => '#64748b'
                                        };
                                        $typeBg = $room->room_type === 'vvip' ? 'bg-dark bg-opacity-10 border-dark' : ($room->room_type === 'vip' ? 'bg-warning bg-opacity-10' : '');
                                        $isAvailable = $room->status === 'available';
                                    @endphp
                                    <div class="room-tile room-selectable {{ $typeBg }} {{ !$room->is_active ? 'opacity-50' : '' }}"
                                         data-room-id="{{ $room->id }}"
                                         data-room-fee="{{ $room->daily_fee }}"
                                         data-room-type="{{ $room->room_type }}"
                                         data-room-number="{{ $room->room_number }}"
                                         data-available="{{ $isAvailable ? '1' : '0' }}"
                                         data-status-color="{{ $borderHex }}"
                                         data-bs-toggle="tooltip"
                                         title="غرفة {{ $room->room_number }} ({{ $room->room_type_name }}) - {{ number_format($room->daily_fee) }} د.ع"
                                         style="border-color: {{ $borderHex }}; {{ !$isAvailable ? 'pointer-events:none; opacity:0.45;' : '' }}">

                                        <div class="room-number" style="color: {{ $borderHex }};">{{ $room->room_number }}</div>
                                        <div class="room-badges">
                                            @if($room->room_type === 'vvip')
                                                <span class="badge bg-dark text-white" style="font-size: 0.45rem; padding: 1px 2px; line-height:1;">VVIP</span>
                                            @elseif($room->room_type === 'vip')
                                                <span class="badge bg-warning text-dark" style="font-size: 0.45rem; padding: 1px 2px; line-height:1;">VIP</span>
                                            @endif
                                        </div>
                                        <div class="room-status">
                                            <span class="status-dot" style="background-color: {{ $borderHex }};"></span>
                                        </div>
                                        @if($isAvailable)
                                        <div class="room-actions" style="display: none;">
                                            <i class="fas fa-check-circle text-success" style="font-size: 0.7rem;"></i>
                                        </div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="alert alert-info py-1 small text-center mb-0">{{ __('لا توجد غرف متاحة') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
</div>

<!-- Modal إضافة طبيب جديد -->
<div class="modal fade" id="addDoctorModal" tabindex="-1" aria-labelledby="addDoctorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title mb-0 fw-bold" id="addDoctorModalLabel" style="font-size:0.9rem;">
                    <i class="fas fa-user-plus me-1"></i> {{ __('إضافة طبيب مرسل جديد') }}
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <form id="addDoctorForm">
                    <div class="mb-2">
                        <label for="new_doctor_name" class="form-label">{{ __('اسم الطبيب') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_doctor_name" required>
                    </div>
                    <div class="mb-2">
                        <label for="new_doctor_specialization" class="form-label">{{ __('التخصص') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_doctor_specialization" placeholder="مثال: جراحة عامة" required>
                    </div>
                    <div class="mb-2">
                        <label for="new_doctor_phone" class="form-label">{{ __('رقم الهاتف') }}</label>
                        <input type="text" class="form-control" id="new_doctor_phone" placeholder="اختياري">
                    </div>
                    <div class="mb-0">
                        <label for="new_doctor_notes" class="form-label">{{ __('ملاحظات') }}</label>
                        <textarea class="form-control" id="new_doctor_notes" rows="2" placeholder="اختياري"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('إلغاء') }}</button>
                <button type="button" class="btn btn-sm btn-primary" id="saveDoctorBtn">
                    <i class="fas fa-save me-1"></i> <span id="saveDoctorBtnText">{{ __('حفظ وإضافة') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- مودال معاينة الوثيقة الممسوحة أو المرفوعة (Document Full Preview Modal) -->
<div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-labelledby="documentPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header py-2 px-3 border-bottom">
                <h6 class="modal-title fw-bold text-dark" id="documentPreviewModalLabel">
                    <i class="fas fa-file-invoice text-primary me-1"></i> {{ __('معاينة ورقة التحويل الطبي الممسوحة') }}
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2 text-center bg-light" style="min-height: 300px; max-height: 75vh; overflow-y: auto;">
                <img id="documentPreviewImg" src="" alt="{{ __('ورقة التحويل') }}" class="img-fluid rounded shadow-sm border" style="max-height: 70vh; display: none;">
                <div id="documentPdfPreview" class="alert alert-info my-4" style="display: none;">
                    <i class="fas fa-file-pdf fa-2x mb-2 d-block text-danger"></i>
                    <strong>{{ __('ملف PDF مرفوع:') }}</strong> <span id="documentPdfName">-</span>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-white">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">{{ __('إغلاق') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@php
    $categoriesList = $surgicalOperations->pluck('category')->filter()->unique()->values();
    $opsData = $surgicalOperations->map(function($op) {
        return [
            'id' => (string) $op->id,
            'text' => $op->name,
            'category' => $op->category ?? '',
            'fee' => $op->fee,
        ];
    })->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', function() {
    // مراجع وحالة اختيار الغرفة (تُعرف أولاً لتجنب أخطاء التهيئة المبكرة عند وجود مريض محدد)
    const roomIdInput = document.getElementById('room_id');
    const stayDaysInput = document.getElementById('expected_stay_days');
    const roomTotalFeeEl = document.getElementById('room_total_fee');
    const selectedRoomNameEl = document.getElementById('selected_room_name');
    const clearRoomSection = document.getElementById('clear_room_section');
    const clearRoomBtn = document.getElementById('clear_room_btn');
    const headerRoomBadge = document.getElementById('header_room_badge');
    const roomValidationError = document.getElementById('room_validation_error');
    const floorPillsContent = document.getElementById('floorPillsContent');
    let selectedRoomFee = 0;
    let selectedRoomType = '';

    // قائمة جميع العمليات والأصناف المتاحة بالنظام
    const allCategories = {!! json_encode($categoriesList) !!};
    const allOperations = {!! json_encode($opsData) !!};
    allOperations.sort(function(a, b) {
        return a.text.localeCompare(b.text, 'ar');
    });

    // زر طي/توسيع كل الأقسام
    const toggleAllBtn = document.getElementById('toggleAllSectionsBtn');
    if (toggleAllBtn) {
        let allExpanded = true;
        toggleAllBtn.addEventListener('click', function() {
            const collapses = document.querySelectorAll('.compact-card .collapse');
            allExpanded = !allExpanded;
            collapses.forEach(c => {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(c, { toggle: false });
                if (allExpanded) {
                    bsCollapse.show();
                } else {
                    bsCollapse.hide();
                }
            });
        });
    }

    // تهيئة Select2
    if (typeof $.fn.select2 !== 'undefined') {
        $('#doctor_id, #department_id, #surgery_category').select2({
            theme: 'bootstrap-5',
            dir: 'rtl',
            width: '100%',
            language: {
                noResults: function() { return 'لا توجد نتائج'; },
                searching: function() { return 'جاري البحث...'; }
            },
            placeholder: function() { return $(this).data('placeholder') || 'اختر...'; },
            allowClear: true
        });



        // قائمة الطبيب المرسل
        let pendingDoctorName = '';
        let isNewDoctor = false;
        
        $('#referring_doctor_name_select').select2({
            theme: 'bootstrap-5',
            dir: 'rtl',
            width: '100%',
            dropdownParent: $('body'),
            tags: true,
            createTag: function (params) {
                const term = $.trim(params.term);
                if (term === '') return null;
                return {
                    id: term,
                    text: '➕ إضافة طبيب جديد: ' + term,
                    newTag: true,
                    originalName: term
                };
            },
            templateResult: function(data) {
                if (!data.id) return data.text;
                if (data.newTag) {
                    return $('<div class="select2-new-tag"><i class="fas fa-user-plus text-success me-1"></i><strong style="color: #16a34a;">' + data.text + '</strong></div>');
                }
                return data.text;
            },
            templateSelection: function(data) {
                if (data.newTag && data.originalName) return data.originalName;
                return data.text;
            },
            escapeMarkup: function(markup) { return markup; },
            placeholder: 'اختر الطبيب أو أدخل اسماً جديداً',
            allowClear: true
        });

        const addDoctorModalElement = document.getElementById('addDoctorModal');
        if (addDoctorModalElement && addDoctorModalElement.parentElement !== document.body) {
            document.body.appendChild(addDoctorModalElement);
        }
        const addDoctorModalInstance = new bootstrap.Modal(addDoctorModalElement);

        $('#referring_doctor_name_select').on('select2:select', function(e) {
            const data = e.params.data;
            if (data.newTag && data.originalName) {
                isNewDoctor = true;
                pendingDoctorName = data.originalName;
                $(this).select2('close');
                $('.select2-container--open').removeClass('select2-container--open');
                $('.select2-container--open .select2-dropdown').hide();
                $('.select2-dropdown').remove();
                if (document.activeElement) document.activeElement.blur();
                $('#new_doctor_name').val(data.originalName);
                $('#new_doctor_specialization').val('');
                $('#new_doctor_phone').val('');
                $('#new_doctor_notes').val('');
                setTimeout(function() { addDoctorModalInstance.show(); }, 50);
            }
        });

        $('#saveDoctorBtn').on('click', function() {
            const name = $('#new_doctor_name').val().trim();
            const specialization = $('#new_doctor_specialization').val().trim();
            const phone = $('#new_doctor_phone').val().trim();
            const notes = $('#new_doctor_notes').val().trim();
            
            if (!name || !specialization) {
                alert('الرجاء إدخال اسم الطبيب والتخصص');
                return;
            }
            
            const $btn = $(this);
            $btn.prop('disabled', true);
            $('#saveDoctorBtnText').html('<i class="fas fa-spinner fa-spin me-1"></i>جاري الحفظ...');
            
            $.ajax({
                url: '{{ route("doctors.store-referring") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    name: name,
                    specialization: specialization,
                    phone: phone,
                    notes: notes
                },
                success: function(response) {
                    if (response.success) {
                        $('#referring_doctor_name_select option[value="' + name + '"]').remove();
                        const newOption = new Option(name, name, true, true);
                        $('#referring_doctor_name_select').append(newOption).trigger('change');
                        isNewDoctor = false;
                        bootstrap.Modal.getInstance(document.getElementById('addDoctorModal')).hide();
                    }
                },
                error: function(xhr) {
                    alert(xhr.responseJSON?.message || 'حدث خطأ أثناء حفظ الطبيب');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $('#saveDoctorBtnText').text('حفظ وإضافة');
                }
            });
        });

        // فلترة وترتيب العمليات الجراحية أبجدياً
        const initialOldOpId = @json(old('surgical_operation_id', ''));

        function initOperationSelect2() {
            const selectedCategory = $('#surgery_category').val();
            const currentOpVal = $('#surgical_operation_id').val() || initialOldOpId;
            $('#surgical_operation_id option:not(:first)').remove();
            
            allOperations.forEach(function(op) {
                if (!selectedCategory || op.category === selectedCategory) {
                    const $option = $('<option></option>')
                        .val(op.id)
                        .text(op.text)
                        .attr('data-category', op.category)
                        .attr('data-fee', op.fee);
                    if (currentOpVal && String(op.id) === String(currentOpVal)) {
                        $option.attr('selected', 'selected');
                    }
                    $('#surgical_operation_id').append($option);
                }
            });
            
            $('#surgical_operation_id').select2({
                theme: 'bootstrap-5',
                dir: 'rtl',
                width: '100%',
                placeholder: 'اختر نوع العملية',
                allowClear: true
            });

            if (currentOpVal && $('#surgical_operation_id option[value="' + currentOpVal + '"]').length) {
                $('#surgical_operation_id').val(String(currentOpVal)).trigger('change.select2');
            }
        }

        initOperationSelect2();

        $('#surgery_category').on('change', function() {
            $('#surgical_operation_id').val(null).trigger('change');
            $('#surgical_operation_id').select2('destroy');
            initOperationSelect2();
        });

        const patientInsuranceType = '{{ $pInsType }}';

        function checkMoiVisibility() {
            const isApplyInsurance = $('input[name="apply_insurance"]:checked').val() === '1';
            const isMoi = isApplyInsurance && (patientInsuranceType === 'moi');
            
            if (isMoi) {
                $('#moiCopaySection').slideDown(150);
                $('#bannerInsuranceBadge').removeClass('bg-secondary bg-success').addClass('bg-primary text-white');
                $('#bannerInsuranceLabel').text('ضمان قوى الأمن الداخلي (MOI)');
                $('#surgery_insurance_option_text').text('🛡️ حجز تحت مظلة ضمان قوى الأمن الداخلي (MOI)');
            } else {
                $('#moiCopaySection').slideUp(100);
                if (isApplyInsurance && patientInsuranceType === 'hi') {
                    $('#bannerInsuranceBadge').removeClass('bg-secondary bg-primary').addClass('bg-success text-white');
                    $('#bannerInsuranceLabel').text('هيئة الضمان الصحي الوطني');
                    $('#surgery_insurance_option_text').text('🛡️ حجز تحت مظلة هيئة الضمان الصحي الوطني');
                } else if (!isApplyInsurance) {
                    $('#bannerInsuranceBadge').removeClass('bg-success bg-primary').addClass('bg-secondary text-white');
                    $('#bannerInsuranceLabel').text('حساب خاص (نقدي)');
                }
            }
            updateMoiCalculations();
        }

        function updateMoiCalculations() {
            const primaryVal = $('#custom_surgery_fee').val() || '0';
            const primaryFee = parseInt(primaryVal.replace(/[^0-9]/g, ''), 10) || 0;
            
            let copayPct = 15;
            const checkedRadio = $('input[name="copay_percentage"]:checked');
            if (checkedRadio.length) {
                copayPct = parseFloat(checkedRadio.val());
            }
            
            const patientShare = Math.round(primaryFee * (copayPct / 100));
            const claimShare = Math.max(0, primaryFee - patientShare);
            
            $('#moiGrossFeeDisp').text(primaryFee.toLocaleString('en-US') + ' د.ع');
            $('#moiPatientShareDisp').text(patientShare.toLocaleString('en-US') + ' د.ع' + (copayPct === 0 ? ' (إعفاء كامل)' : ` (${copayPct}%)`));
            $('#moiClaimShareDisp').text(claimShare.toLocaleString('en-US') + ' د.ع');
            
            $('#tile_copay_15, #tile_copay_0, #tile_copay_100').removeClass('border-primary border-success border-secondary shadow-sm');
            if (copayPct === 15) {
                $('#tile_copay_15').addClass('border-primary shadow-sm');
            } else if (copayPct === 0) {
                $('#tile_copay_0').addClass('border-success shadow-sm');
            } else if (copayPct === 100) {
                $('#tile_copay_100').addClass('border-secondary shadow-sm');
            }
        }

        // مزامنة راديو مسار التغطية واختيار جهة الضمان
        $('input[name="apply_insurance"]').on('change', function() {
            checkMoiVisibility();
            updateStickySummary();
        });

        $('input[name="copay_percentage"]').on('change', function() {
            updateMoiCalculations();
            updateStickySummary();
        });

        if ($('#patient_id').val()) {
            $('#patient_id').trigger('change');
        }

        // تفاصيل العملية والسعر المقترح
        $('#surgical_operation_id').on('change', function() {
            const selectedOption = $(this).find(':selected');
            const opId = selectedOption.val();
            const fee = selectedOption.data('fee');

            if (opId && fee !== undefined && fee !== null) {
                const formattedFee = new Intl.NumberFormat('en-US').format(fee);
                $('#custom_surgery_fee').attr('title', 'السعر الافتراضي: ' + formattedFee + ' د.ع');
                if (!$('#custom_surgery_fee').val()) {
                    $('#custom_surgery_fee').val(formattedFee);
                }
            } else {
                $('#custom_surgery_fee').removeAttr('title');
            }
            updateOpsFinancialSummary();
        });

        if ($('#surgical_operation_id').val()) {
            $('#surgical_operation_id').trigger('change');
        }
    }

    // تنسيق فواصل الآلاف
    const customFeeInput = document.getElementById('custom_surgery_fee');
    function formatWithCommas(val) {
        if (!val && val !== 0) return '';
        let str = val.toString().trim();
        if (str.includes('.')) {
            str = str.split('.')[0];
        }
        const digits = str.replace(/[^0-9]/g, '');
        if (!digits) return '';
        return parseInt(digits, 10).toLocaleString('en-US');
    }

    if (customFeeInput) {
        customFeeInput.addEventListener('input', function() {
            const pos = customFeeInput.selectionStart;
            const len = customFeeInput.value.length;
            customFeeInput.value = formatWithCommas(customFeeInput.value);
            const diff = customFeeInput.value.length - len;
            customFeeInput.setSelectionRange(pos + diff, pos + diff);
            updateOpsFinancialSummary();
        });
    }

    // إدارة العمليات الجراحية الموحدة والمتعددة
    let addOpIndex = 0;

    function renumberAllOpRows() {
        let currentNumber = 2;
        $('#operationsTableBody .additional-op-row').each(function() {
            $(this).find('.op-number-badge').text('العملية (' + currentNumber + ')');
            currentNumber++;
        });
        const totalCount = 1 + $('#operationsTableBody .additional-op-row').length;
        $('#totalOpsCount').text(totalCount);
        $('#totalOpsCountBottom').text(totalCount);
    }

    function updateOpsFinancialSummary() {
        const primaryVal = $('#custom_surgery_fee').val() || '0';
        const primaryFee = parseInt(primaryVal.replace(/[^0-9]/g, ''), 10) || 0;
        
        let additionalTotal = 0;
        $('.add-op-fee').each(function() {
            const rowFeeVal = $(this).val() || '0';
            const rowFee = parseInt(rowFeeVal.replace(/[^0-9]/g, ''), 10) || 0;
            additionalTotal += rowFee;
        });

        renumberAllOpRows();
        const formattedTotal = (primaryFee + additionalTotal).toLocaleString('en-US') + ' د.ع';
        $('#summaryTotalFee').text(formattedTotal);
        $('#summaryTotalFeeBottom').text(formattedTotal);
        if (typeof updateMoiCalculations === 'function') {
            updateMoiCalculations();
        }
        updateStickySummary();
    }

    // تحديث اللوحة التنفيذية والملخص المالي الثابت
    function updateStickySummary() {
        const primaryVal = $('#custom_surgery_fee').val() || '0';
        const primaryFee = parseInt(primaryVal.replace(/[^0-9]/g, ''), 10) || 0;
        
        let additionalTotal = 0;
        $('.add-op-fee').each(function() {
            const rowFeeVal = $(this).val() || '0';
            const rowFee = parseInt(rowFeeVal.replace(/[^0-9]/g, ''), 10) || 0;
            additionalTotal += rowFee;
        });

        const totalOps = primaryFee + additionalTotal;
        const totalOpsCount = 1 + $('#operationsTableBody .additional-op-row').length;

        if ($('#stickyOpsCount').length) $('#stickyOpsCount').text(totalOpsCount);
        if ($('#stickyOpsFee').length) $('#stickyOpsFee').text(totalOps.toLocaleString('en-US') + ' د.ع');

        let roomFee = 0;
        if (typeof selectedRoomType !== 'undefined' && selectedRoomType && typeof roomIdInput !== 'undefined' && roomIdInput && roomIdInput.value) {
            if (selectedRoomType === 'vvip') {
                roomFee = 200000;
            } else if (selectedRoomType === 'vip') {
                roomFee = 100000;
            } else {
                roomFee = 0;
            }
        }

        const grandTotal = totalOps + roomFee;
        if ($('#stickyGrandTotal').length) $('#stickyGrandTotal').text(grandTotal.toLocaleString('en-US') + ' د.ع');

        const isInsurance = $('input[name="apply_insurance"]:checked').val() === '1';
        const hasInsurance = patientInsuranceType && patientInsuranceType !== 'none';
        const isMoi = isInsurance && (patientInsuranceType === 'moi');
        
        if (isMoi) {
            $('#stickyCoverageBadge').text('🛡️ قوى الأمن الداخلي (MOI)').removeClass('bg-secondary bg-success text-white').addClass('bg-primary text-white');
        } else if (hasInsurance && isInsurance) {
            $('#stickyCoverageBadge').text('🛡️ ضمان صحي').removeClass('bg-secondary bg-primary text-white').addClass('bg-success text-white');
        } else {
            $('#stickyCoverageBadge').text('💵 كاش نقدي').removeClass('bg-success bg-primary text-white').addClass('bg-secondary text-white');
        }
    }

    function addAdditionalOpRow(selectedOpId = null, initialFee = null, initialNotes = '', selectedCategory = null) {
        const idx = addOpIndex++;

        // تحديد الصنف تلقائياً من العملية إن لم يكن محدداً مسبقاً
        if (selectedOpId && !selectedCategory) {
            const found = allOperations.find(o => o.id == selectedOpId);
            if (found) selectedCategory = found.category;
        }

        let catOptions = '<option value="">' + @json(__('-- كل الأصناف --')) + '</option>';
        allCategories.forEach(function(cat) {
            const isCatSelected = selectedCategory && selectedCategory === cat ? 'selected' : '';
            catOptions += `<option value="${cat}" ${isCatSelected}>${cat}</option>`;
        });

        let opOptions = '<option value="">{{ __('-- اختر العملية --') }}</option>';
        allOperations.forEach(function(op) {
            if (!selectedCategory || op.category === selectedCategory) {
                const isSelected = selectedOpId && op.id == selectedOpId ? 'selected' : '';
                opOptions += `<option value="${op.id}" data-category="${op.category || ''}" data-fee="${op.fee || 0}" ${isSelected}>${op.text}</option>`;
            }
        });

        let feeValue = '';
        if (initialFee !== null && initialFee !== undefined && initialFee !== '') {
            feeValue = formatWithCommas(initialFee.toString());
        }

        const nextNumber = 2 + $('#operationsTableBody .additional-op-row').length;

        const rowHtml = `
            <tr class="additional-op-row align-middle bg-white" id="add_op_row_${idx}">
                <td class="text-center bg-light">
                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 op-number-badge" style="font-size: 0.8rem;">العملية (${nextNumber})</span>
                </td>
                <td>
                    <select class="form-select form-select-sm add-op-category" style="width: 100%;">
                        ${catOptions}
                    </select>
                </td>
                <td>
                    <select name="additional_operations[${idx}][surgical_operation_id]" class="form-select form-select-sm select2-add-op" required style="width: 100%;">
                        ${opOptions}
                    </select>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" name="additional_operations[${idx}][fee]" class="form-control form-control-sm add-op-fee text-end fw-bold" value="${feeValue}" placeholder="0" inputmode="numeric" required>
                        <span class="input-group-text small px-1 bg-light text-muted" style="font-size: 0.72rem;">{{ __('د.ع') }}</span>
                    </div>
                </td>
                <td>
                    <input type="text" name="additional_operations[${idx}][notes]" class="form-control form-control-sm" value="${initialNotes || ''}" placeholder="تكميلية، جهة يسرى...">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-add-op-btn py-1 px-2" title="{{ __('حذف هذه العملية') }}">
                        <i class="fas fa-trash-alt me-1"></i> {{ __('حذف') }}
                    </button>
                </td>
            </tr>
        `;

        $('#operationsTableBody').append(rowHtml);

        const $row = $(`#add_op_row_${idx}`);
        const $catSelect = $row.find('.add-op-category');
        const $opSelect = $row.find('.select2-add-op');

        if (typeof $.fn.select2 !== 'undefined') {
            $opSelect.select2({
                theme: 'bootstrap-5',
                dir: 'rtl',
                width: '100%',
                placeholder: 'اختر نوع العملية',
                allowClear: true
            });
        }

        // عند تغيير صنف العملية، يتم تصفية قائمة نوع العملية التابعة لهذا الصنف
        $catSelect.on('change', function() {
            const cat = $(this).val();
            let newOpOptions = '<option value="">{{ __('-- اختر العملية --') }}</option>';
            allOperations.forEach(function(op) {
                if (!cat || op.category === cat) {
                    newOpOptions += `<option value="${op.id}" data-category="${op.category || ''}" data-fee="${op.fee || 0}">${op.text}</option>`;
                }
            });

            if (typeof $.fn.select2 !== 'undefined') {
                $opSelect.select2('destroy');
            }
            $opSelect.html(newOpOptions);
            if (typeof $.fn.select2 !== 'undefined') {
                $opSelect.select2({
                    theme: 'bootstrap-5',
                    dir: 'rtl',
                    width: '100%',
                    placeholder: 'اختر نوع العملية',
                    allowClear: true
                });
            }
            $opSelect.val('').trigger('change');
        });

        // عند اختيار نوع العملية، يتم تعبئة السعر الافتراضي وتحديث الحسابات
        $opSelect.on('change', function() {
            const selected = $(this).find(':selected');
            const fee = selected.data('fee');
            const opCat = selected.data('category');
            const $feeInput = $row.find('.add-op-fee');
            
            // إذا لم يكن الصنف محدداً، نحدد الصنف تلقائياً
            if (opCat && !$catSelect.val()) {
                $catSelect.val(opCat);
            }

            if (!$feeInput.val() && fee !== undefined && fee !== null) {
                $feeInput.val(parseInt(fee, 10).toLocaleString('en-US'));
            }
            updateOpsFinancialSummary();
        });

        const $feeInput = $row.find('.add-op-fee');
        $feeInput.on('input', function() {
            const formatted = formatWithCommas($(this).val());
            $(this).val(formatted);
            updateOpsFinancialSummary();
        });

        updateOpsFinancialSummary();
    }

    $(document).on('click', '#addAnotherOpBtn, #addAnotherOpBtnBottom', function(e) {
        e.preventDefault();
        addAdditionalOpRow();
    });

    $(document).on('click', '.remove-add-op-btn', function(e) {
        e.preventDefault();
        $(this).closest('.additional-op-row').remove();
        renumberAllOpRows();
        updateOpsFinancialSummary();
    });

    // استرجاع المدخلات السابقة إن وجدت
    const oldAdditionalOps = @json(old('additional_operations', []));
    if (Array.isArray(oldAdditionalOps) && oldAdditionalOps.length > 0) {
        oldAdditionalOps.forEach(function(item) {
            if (item && item.surgical_operation_id) {
                addAdditionalOpRow(item.surgical_operation_id, item.fee, item.notes);
            }
        });
    }

    // نظام اختيار الغرفة المدمج أو وضع الانتظار
    const roomWaitingModeInput = document.getElementById('room_waiting_mode');
    const btnRoomDirectMode = document.getElementById('btnRoomDirectMode');
    const btnRoomWaitingMode = document.getElementById('btnRoomWaitingMode');
    const roomWaitingBanner = document.getElementById('room_waiting_banner');

    function setRoomWaitingMode(isWaiting) {
        if (!roomWaitingModeInput) return;
        roomWaitingModeInput.value = isWaiting ? '1' : '0';

        if (isWaiting) {
            if (btnRoomDirectMode) {
                btnRoomDirectMode.classList.remove('btn-primary');
                btnRoomDirectMode.classList.add('btn-outline-primary');
            }
            if (btnRoomWaitingMode) {
                btnRoomWaitingMode.classList.remove('btn-outline-warning');
                btnRoomWaitingMode.classList.add('btn-warning');
            }
            if (roomWaitingBanner) roomWaitingBanner.classList.remove('d-none');
            if (roomValidationError) roomValidationError.classList.add('d-none');
            if (floorPillsContent) floorPillsContent.style.border = '';

            document.querySelectorAll('.room-tile').forEach(t => {
                t.style.borderColor = t.dataset.origBorder || t.style.borderColor;
                t.style.borderWidth = t.dataset.origWidth || '2px';
                t.style.boxShadow = '';
                const icon = t.querySelector('.room-actions');
                if (icon) icon.style.display = 'none';
            });
            if (roomIdInput) roomIdInput.value = '';
            selectedRoomFee = 0;
            selectedRoomType = '';
            if (selectedRoomNameEl) selectedRoomNameEl.textContent = '⏳ وضع انتظار الغرفة';
            if ($('#stickyRoomName').length) $('#stickyRoomName').text('⏳ وضع انتظار الغرفة');
            if (headerRoomBadge) {
                headerRoomBadge.textContent = '⏳ وضع انتظار الغرفة';
                headerRoomBadge.className = 'badge bg-warning text-dark';
            }
            const statusSpan = document.getElementById('selected_room_status');
            if (statusSpan) {
                statusSpan.textContent = 'وضع الانتظار (تُخصص لاحقاً)';
                statusSpan.className = 'fw-bold text-warning-emphasis';
            }
            if (clearRoomSection) clearRoomSection.style.display = 'none';
            calculateRoomFee();
        } else {
            if (btnRoomDirectMode) {
                btnRoomDirectMode.classList.remove('btn-outline-primary');
                btnRoomDirectMode.classList.add('btn-primary');
            }
            if (btnRoomWaitingMode) {
                btnRoomWaitingMode.classList.remove('btn-warning');
                btnRoomWaitingMode.classList.add('btn-outline-warning');
            }
            if (roomWaitingBanner) roomWaitingBanner.classList.add('d-none');
        }
    }

    if (btnRoomDirectMode) {
        btnRoomDirectMode.addEventListener('click', function() { setRoomWaitingMode(false); });
    }
    if (btnRoomWaitingMode) {
        btnRoomWaitingMode.addEventListener('click', function() { setRoomWaitingMode(true); });
    }
    if (roomWaitingModeInput && roomWaitingModeInput.value === '1') {
        setRoomWaitingMode(true);
    }

    function validateRoomSelection() {
        if (roomWaitingModeInput && roomWaitingModeInput.value === '1') {
            if (roomValidationError) roomValidationError.classList.add('d-none');
            if (floorPillsContent) floorPillsContent.style.border = '';
            return true;
        }
        const val = roomIdInput ? roomIdInput.value : '';
        if (!val) {
            if (roomValidationError) roomValidationError.classList.remove('d-none');
            if (floorPillsContent) {
                floorPillsContent.style.border = '2.5px solid #dc3545';
                floorPillsContent.style.borderRadius = '8px';
            }

            const roomCollapse = document.getElementById('collapseRoomStay');
            if (roomCollapse) {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(roomCollapse, { toggle: false });
                bsCollapse.show();
            }

            const oldToast = document.querySelector('.room-required-toast');
            if (oldToast) oldToast.remove();
            const toast = document.createElement('div');
            toast.className = 'room-required-toast alert alert-danger position-fixed shadow-lg';
            toast.style.cssText = 'top:25px;left:50%;transform:translateX(-50%);z-index:9999;border-right:6px solid #dc3545;font-weight:bold;font-size:0.95rem;background:#fff;';
            toast.innerHTML = '<i class="fas fa-exclamation-triangle text-danger me-2"></i> يرجى اختيار غرفة متاحة أو تفعيل «وضع الانتظار للغرفة».';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4500);

            document.getElementById('collapseRoomStay')?.scrollIntoView({ behavior: 'smooth' });
            return false;
        }

        if (roomValidationError) roomValidationError.classList.add('d-none');
        if (floorPillsContent) floorPillsContent.style.border = '';
        return true;
    }

    const form = document.getElementById('surgeryForm');
    const submitBtn = document.getElementById('submitBtn');

    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            if (!validateRoomSelection()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateRoomSelection()) {
                e.preventDefault();
                return false;
            }

            if (customFeeInput && customFeeInput.value) {
                customFeeInput.value = customFeeInput.value.replace(/,/g, '');
            }
        });
    }

    document.querySelectorAll('.room-tile').forEach(t => {
        t.dataset.origBorder = t.dataset.statusColor || t.style.borderColor || '#dee2e6';
        t.dataset.origWidth  = '2px';
    });

    function calculateRoomFee() {
        if (!roomTotalFeeEl) return;
        if (!roomIdInput || !roomIdInput.value) {
            roomTotalFeeEl.textContent = '0 د.ع';
            roomTotalFeeEl.className = 'badge bg-secondary fs-6 ms-1';
            if ($('#stickyRoomFee').length) $('#stickyRoomFee').text('0 د.ع');
            updateStickySummary();
            return;
        }

        if (selectedRoomType === 'vvip') {
            roomTotalFeeEl.textContent = '200,000 د.ع (VVIP)';
            roomTotalFeeEl.className = 'badge bg-dark text-white fs-6 ms-1';
        } else if (selectedRoomType === 'vip') {
            roomTotalFeeEl.textContent = '100,000 د.ع (VIP)';
            roomTotalFeeEl.className = 'badge bg-warning text-dark fs-6 ms-1';
        } else {
            roomTotalFeeEl.textContent = '0 د.ع (مجانية أول ليلة)';
            roomTotalFeeEl.className = 'badge bg-success text-white fs-6 ms-1';
        }

        if ($('#stickyRoomFee').length) {
            $('#stickyRoomFee').text(roomTotalFeeEl.textContent);
        }
        updateStickySummary();
    }

    function selectRoom(tile) {
        if (tile.dataset.available === '0') return;

        if (typeof setRoomWaitingMode === 'function') {
            setRoomWaitingMode(false);
        }

        if (roomValidationError) roomValidationError.classList.add('d-none');
        if (floorPillsContent) floorPillsContent.style.border = '';

        document.querySelectorAll('.room-tile').forEach(t => {
            t.style.borderColor = t.dataset.origBorder;
            t.style.borderWidth = t.dataset.origWidth;
            t.style.boxShadow   = '';
            const icon = t.querySelector('.room-actions');
            if (icon) icon.style.display = 'none';
        });

        tile.style.borderColor = '#0d6efd';
        tile.style.borderWidth = '3px';
        tile.style.boxShadow   = '0 0 0 3px rgba(13,110,253,.25)';
        const icon = tile.querySelector('.room-actions');
        if (icon) icon.style.display = 'block';

        if (roomIdInput) roomIdInput.value = tile.dataset.roomId;
        selectedRoomFee  = parseFloat(tile.dataset.roomFee) || 0;
        selectedRoomType = tile.dataset.roomType || 'regular';

        const typeLabel = selectedRoomType === 'vvip' ? ' (💎 VVIP)' : (selectedRoomType === 'vip' ? ' (⭐ VIP)' : ' (عادية)');
        const roomName = 'غرفة ' + tile.dataset.roomNumber + typeLabel;
        if (selectedRoomNameEl) selectedRoomNameEl.textContent = roomName;
        if ($('#stickyRoomName').length) $('#stickyRoomName').text(roomName);
        if (headerRoomBadge) {
            headerRoomBadge.textContent = roomName;
            headerRoomBadge.className = selectedRoomType === 'vvip' ? 'badge bg-dark text-white' : (selectedRoomType === 'vip' ? 'badge bg-warning text-dark' : 'badge bg-success text-white');
        }

        const statusSpan = document.getElementById('selected_room_status');
        if (statusSpan) {
            statusSpan.textContent = 'متاحة';
            statusSpan.className   = 'fw-bold text-success';
        }

        if (clearRoomSection) clearRoomSection.style.display = 'block';
        calculateRoomFee();
    }

    document.querySelectorAll('.room-selectable').forEach(tile => {
        tile.addEventListener('click', function() { selectRoom(this); });
    });

    if (roomIdInput && roomIdInput.value) {
        const initialRoomTile = document.querySelector('.room-selectable[data-room-id="' + roomIdInput.value + '"]');
        if (initialRoomTile && initialRoomTile.dataset.available === '1') {
            selectRoom(initialRoomTile);
        }
    }

    if (stayDaysInput) {
        stayDaysInput.addEventListener('input', calculateRoomFee);
    }

    if (clearRoomBtn) {
        clearRoomBtn.addEventListener('click', function() {
            document.querySelectorAll('.room-tile').forEach(t => {
                t.style.borderColor = t.dataset.origBorder;
                t.style.borderWidth = t.dataset.origWidth;
                t.style.boxShadow   = '';
                const icon = t.querySelector('.room-actions');
                if (icon) icon.style.display = 'none';
            });
            if (roomIdInput) roomIdInput.value = '';
            selectedRoomFee   = 0;
            selectedRoomType  = '';
            if (selectedRoomNameEl) selectedRoomNameEl.textContent = 'لم يتم الاختيار';
            if ($('#stickyRoomName').length) $('#stickyRoomName').text('لم يتم الاختيار');
            if ($('#stickyRoomFee').length) $('#stickyRoomFee').text('0 د.ع');
            if (headerRoomBadge) {
                headerRoomBadge.textContent = 'لم يتم الاختيار';
                headerRoomBadge.className = 'badge bg-light text-muted border';
            }
            if (clearRoomSection) clearRoomSection.style.display = 'none';
            calculateRoomFee();
        });
    }

    // تهيئة Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // ===== نظام الأرشفة التلقائي - يعمل مع أي سكانر =====
    // ===== نظام الأرشفة والسكانر الذكي المتكامل (سكانر USB + كشف مباشر + كاميرا + رفع) =====
    const referralInput = document.getElementById('referral_letter');
    const previewContainer = document.getElementById('referral_letter_preview');
    const hiddenScanInput = document.getElementById('scan_data_receiver');

    // 1. كشف وقراءة السكانر المتصل بالحاسبة وتفعيل الزر
    window.detectScannerDevice = async function(showFeedback = true) {
        const detectBtn = document.getElementById('detect_scanner_btn');
        const spinner = document.getElementById('detect_spinner');
        const scanBtn = document.getElementById('scan_btn');
        const scanBtnLabel = document.getElementById('scan_btn_label');
        const indicator = document.getElementById('scannerStatusIndicator');

        if (detectBtn) detectBtn.disabled = true;
        if (spinner) spinner.className = 'fas fa-spinner fa-spin me-1';
        if (indicator) {
            indicator.className = 'badge bg-warning-subtle text-warning border border-warning-subtle py-0 px-2';
            indicator.innerHTML = '<span class="spinner-grow spinner-grow-sm me-1" style="width:7px;height:7px;"></span>جاري قراءة السكانر...';
        }

        const endpoints = [
            'http://127.0.0.1:5000/status',
            'http://127.0.0.1:37426/status',
            'http://localhost:5000/status',
            'http://localhost:37426/status'
        ];

        let foundScanner = null;
        let bridgeRunning = false;

        for (const url of endpoints) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 2500);
                const res = await fetch(url, { method: 'GET', mode: 'cors', signal: controller.signal });
                clearTimeout(timeoutId);

                if (res.ok) {
                    const data = await res.json();
                    bridgeRunning = true;
                    if (data.scanners && data.scanners.length > 0) {
                        foundScanner = data.scanners[0].name || data.scanners[0].id || 'سكانر متصل';
                    } else if (data.status === 'ready' || data.status === 'ok') {
                        foundScanner = data.message || 'سكانر جاهز';
                    }
                    break;
                }
            } catch (e) {
                // Ignore and try next
            }
        }

        if (detectBtn) detectBtn.disabled = false;
        if (spinner) spinner.className = 'fas fa-search me-1';

        if (foundScanner) {
            // تفعيل زر السكانر وتحويله إلى الأخضر
            if (indicator) {
                indicator.className = 'badge bg-success text-white py-0 px-2';
                indicator.innerHTML = '<i class="fas fa-check-circle me-1" style="font-size: 8px;"></i>متصل: ' + foundScanner;
            }
            if (scanBtn) {
                scanBtn.className = 'btn btn-sm btn-success flex-grow-1 shadow-sm';
            }
            if (scanBtnLabel) {
                scanBtnLabel.innerText = 'مسح من: ' + foundScanner;
            }
            if (window.toastr) {
                toastr.success('تم اكتشاف وقراءة السكانر بنجاح وهو جاهز للمسح: ' + foundScanner);
            }
        } else if (bridgeRunning) {
            if (indicator) {
                indicator.className = 'badge bg-warning text-dark py-0 px-2';
                indicator.innerHTML = '<i class="fas fa-exclamation-circle me-1" style="font-size: 8px;"></i>البرنامج يعمل ولكن لم يتم اكتشاف سكانر';
            }
            if (showFeedback) {
                if (window.toastr) {
                    toastr.warning('برنامج السكانر يعمل ولكن لم يتم العثور على جهاز سكانر متصل. تأكد من توصيل كابل USB.');
                } else {
                    alert('برنامج السكانر يعمل ولكن لم يتم العثور على جهاز سكانر متصل بالحاسبة.');
                }
            }
        } else {
            if (indicator) {
                indicator.className = 'badge bg-secondary text-white py-0 px-2';
                indicator.innerHTML = '<i class="fas fa-times-circle me-1" style="font-size: 8px;"></i>السكانر غير متصل';
            }
            if (showFeedback) {
                if (window.toastr) {
                    toastr.error('تعذر الاتصال ببرنامج السكانر المحلي (127.0.0.1:5000). تأكد من تشغيل البرنامج على الحاسبة.');
                } else {
                    alert('تعذر الاتصال ببرنامج السكانر المحلي (127.0.0.1:5000). يرجى التأكد من تشغيل البرنامج.');
                }
            }
        }
    };

    // فحص خفيف أولي في الخلفية بعد تحميل الصفحة
    setTimeout(() => {
        detectScannerDevice(false);
    }, 1200);

    // 2. سحب من سكانر USB
    window.scanFromDevice = async function() {
        const btn = document.getElementById('scan_btn');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري المسح...';

        const endpoints = [
            'http://127.0.0.1:5000/scan',
            'http://127.0.0.1:37426/scan',
            'http://localhost:5000/scan',
            'http://localhost:37426/scan'
        ];
        let response = null;
        let lastError = null;

        for (const url of endpoints) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 12000);
                const res = await fetch(url, { method: 'POST', mode: 'cors', signal: controller.signal });
                clearTimeout(timeoutId);
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
                throw lastError || new Error('تعذر الاتصال بخادم السكانر المحلي');
            }
            const blob = await response.blob();
            const reader = new FileReader();
            reader.onloadend = function() {
                const base64data = reader.result;
                if (hiddenScanInput) hiddenScanInput.value = base64data;
                const previewTxt = document.getElementById('preview_text');
                if (previewTxt) previewTxt.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i> تم سحب الوثيقة من السكانر بنجاح';
                if (previewContainer) previewContainer.style.display = 'block';
            };
            reader.readAsDataURL(blob);
        } catch (err) {
            console.warn('Scanner error:', err);
            if (window.toastr) {
                toastr.error('تعذر إجراء المسح من السكانر. تأكد من تشغيل البرنامج ووضع الورقة داخل السكانر.');
            } else {
                alert('تعذر إجراء المسح من السكانر. تأكد من تشغيل البرنامج ووضع الورقة داخل السكانر.');
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    };

    // 3. معاينة الوثيقة الممسوحة أو المرفوعة
    window.viewCapturedDocument = function() {
        const previewModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('documentPreviewModal'));
        const img = document.getElementById('documentPreviewImg');
        const pdfDiv = document.getElementById('documentPdfPreview');
        const pdfName = document.getElementById('documentPdfName');

        if (hiddenScanInput && hiddenScanInput.value) {
            img.src = hiddenScanInput.value;
            img.style.display = 'inline-block';
            if (pdfDiv) pdfDiv.style.display = 'none';
            previewModal.show();
            return;
        }

        if (referralInput && referralInput.files && referralInput.files[0]) {
            const file = referralInput.files[0];
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    img.style.display = 'inline-block';
                    if (pdfDiv) pdfDiv.style.display = 'none';
                    previewModal.show();
                };
                reader.readAsDataURL(file);
            } else {
                img.style.display = 'none';
                if (pdfDiv) {
                    pdfDiv.style.display = 'block';
                    if (pdfName) pdfName.innerText = file.name;
                }
                previewModal.show();
            }
            return;
        }

        alert('لا توجد وثيقة للمعاينة حالياً');
    };

    window.clearScannedDoc = function() {
        if (referralInput) referralInput.value = '';
        const hiddenInput = document.getElementById('scan_data_receiver');
        if (hiddenInput) hiddenInput.value = '';
        if (previewContainer) previewContainer.style.display = 'none';
    };

    if (referralInput) {
        referralInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const previewTxt = document.getElementById('preview_text');
                if (previewTxt) previewTxt.innerHTML = '<i class="fas fa-file-alt me-1 text-primary"></i> تم اختيار الملف: ' + this.files[0].name;
                if (previewContainer) previewContainer.style.display = 'block';
            }
        });
    }

    // التهيئة الأولية للملخص المالي واللوحة الثابتة
    updateStickySummary();
});
</script>
@endsection
