@extends('layouts.app')

@section('title', 'إضبارة مريض العناية المركزة - ' . ($admission->patient?->user?->name ?? $admission->patient?->name))

@section('content')
<div class="container-fluid py-3">
    <!-- التنبيهات -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- بطاقة المريض العلوية (Patient Banner) -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px; border-right: 5px solid #dc3545 !important;">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
                        <h3 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-user-injured text-danger me-2"></i>
                            {{ $admission->patient?->user?->name ?? $admission->patient?->name ?? 'مريض غير محدد' }}
                        </h3>
                        <span class="badge {{ $admission->bed?->type_badge_class }} fs-6">
                            سرير #{{ $admission->bed?->bed_number }} ({{ $admission->bed?->type_name }})
                        </span>
                        <span class="badge {{ $admission->status === 'active' ? 'bg-danger text-white' : 'bg-success text-white' }} fs-6">
                            {{ $admission->status_name }}
                        </span>
                        @if($admission->is_ventilated)
                            <span class="badge bg-info text-white fs-6">
                                <i class="fas fa-lungs me-1"></i> جهاز تنفس اصطناعي (Ventilator)
                            </span>
                        @endif
                        @if($admission->is_isolated)
                            <span class="badge bg-danger text-white fs-6">
                                <i class="fas fa-shield-virus me-1"></i> عزل ضغط سالب
                            </span>
                        @endif
                        @if($admission->is_insured)
                            <span class="badge bg-primary text-white fs-6">
                                <i class="fas fa-id-card me-1"></i> مشمول بالضمان الصحي
                            </span>
                        @endif
                    </div>

                    <div class="row g-2 text-muted small mt-1">
                        <div class="col-sm-4">
                            <i class="fas fa-stethoscope text-primary me-1"></i>
                            <strong>التشخيص:</strong>
                            @if($admission->primary_diagnosis)
                                <span class="text-dark fw-bold">{{ $admission->primary_diagnosis }}</span>
                                @if($admission->icd10_code)
                                    <span class="badge bg-info text-dark ms-1 font-monospace">{{ $admission->icd10_code }}</span>
                                @endif
                            @else
                                <span class="badge bg-warning text-dark">بانتظار تحديد الطبيب</span>
                            @endif
                        </div>
                        <div class="col-sm-4">
                            <i class="fas fa-user-md text-success me-1"></i>
                            <strong>الطبيب المشرف:</strong> {{ $admission->doctor?->user?->name ?? 'طبيب العناية المناوب' }}
                        </div>
                        <div class="col-sm-4">
                            <i class="fas fa-sign-in-alt text-warning me-1"></i>
                            <strong>مصدر الدخول:</strong> {{ $admission->admission_source_name }}
                        </div>
                        <div class="col-sm-4">
                            <i class="fas fa-calendar-check text-info me-1"></i>
                            <strong>وقت الدخول:</strong> {{ $admission->admission_date->format('Y-m-d H:i') }}
                        </div>
                        <div class="col-sm-4">
                            <i class="fas fa-clock text-secondary me-1"></i>
                            <strong>مدة الإقامة:</strong> {{ $admission->stay_days_count }} يوم ({{ $admission->stay_duration_hours }} ساعة)
                        </div>
                        <div class="col-sm-4">
                            <i class="fas fa-door-open text-dark me-1"></i>
                            <strong>الغرفة:</strong> {{ $admission->bed?->room_number ?? 'ردهة العناية' }}
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="{{ route('icu.index') }}" class="btn btn-outline-secondary btn-sm mb-1">
                        <i class="fas fa-arrow-right me-1"></i> العودة للرادار
                    </a>
                    <a href="{{ route('cashier.ccu.payment.form', $admission->id) }}" class="btn btn-success btn-sm mb-1 fw-bold shadow-sm">
                        <i class="fas fa-file-invoice-dollar me-1"></i> الكاشير وكشف الحساب
                    </a>
                    @if($admission->status === 'active')
                        <button type="button" class="btn btn-outline-primary btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#transferBedModal">
                            <i class="fas fa-exchange-alt me-1"></i> نقل لسرير آخر
                        </button>
                        <button type="button" class="btn btn-danger btn-sm mb-1" data-bs-toggle="modal" data-bs-target="#dischargeModal">
                            <i class="fas fa-sign-out-alt me-1"></i> تخريج المريض
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ================= بطاقة تحديد التشخيص الأولي والتصنيف الدولي ICD-10 (الطبيب فقط) ================= -->
    <div class="card border-0 shadow-sm mb-3 rounded-4 border-start border-4 {{ $admission->primary_diagnosis ? 'border-primary' : 'border-warning' }}" id="ccuDiagnosisCard">
        <div class="card-header bg-white border-0 py-2.5 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ $admission->primary_diagnosis ? 'bg-primary' : 'bg-warning text-dark' }} rounded-pill px-2.5 py-1">
                    <i class="fas fa-notes-medical me-1"></i> التشخيص الأولي والتصنيف الدولي (ICD-10)
                </span>
                @if($admission->primary_diagnosis)
                    <strong class="text-dark small">{{ $admission->primary_diagnosis }}</strong>
                    @if($admission->icd10_code)
                        <span class="badge bg-info-subtle text-primary border border-info-subtle font-monospace">{{ $admission->icd10_code }}</span>
                    @endif
                    @if($admission->initial_gcs)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">GCS: {{ $admission->initial_gcs }}/15</span>
                    @endif
                @else
                    <span class="text-danger fw-bold small">
                        <i class="fas fa-exclamation-circle me-1"></i> يرجى من الطبيب المشرف تحديد التشخيص السريري الأولي ومقياس الوعي (GCS) ورمز ICD-10
                    </span>
                @endif
            </div>
            @if(auth()->check() && (auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'doctor', 'icu_doctor']) || auth()->user()->can('edit ccu diagnosis')))
                <button type="button" class="btn btn-sm {{ $admission->primary_diagnosis ? 'btn-outline-primary' : 'btn-warning fw-bold' }}" data-bs-toggle="collapse" data-bs-target="#ccuInitialDiagnosisCollapse" aria-expanded="{{ $admission->primary_diagnosis ? 'false' : 'true' }}">
                    <i class="fas fa-edit me-1"></i> {{ $admission->primary_diagnosis ? 'تعديل التشخيص الأولي' : 'تحديد التشخيص الآن' }}
                </button>
            @endif
        </div>
        @if(auth()->check() && (auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'doctor', 'icu_doctor']) || auth()->user()->can('edit ccu diagnosis')))
        <div class="collapse {{ $admission->primary_diagnosis ? '' : 'show' }}" id="ccuInitialDiagnosisCollapse">
            <div class="card-body border-top bg-light bg-opacity-50 p-3">
                <form action="{{ route('ccu.admissions.initial-diagnosis.update', $admission->id) }}" method="POST">
                    @csrf
                    <div class="row g-3 align-items-start">
                        <div class="col-md-5 position-relative">
                            <label for="ccu_icd10_search" class="form-label fw-bold small text-primary mb-1">
                                <i class="fas fa-search me-1"></i> التصنيف الدولي للأمراض ICD-10 (بحث عربي وإنجليزي)
                            </label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fas fa-code-branch text-primary"></i></span>
                                <input type="text" id="ccu_icd10_search" class="form-control" autocomplete="off"
                                       placeholder="اكتب جزء من المرض بالعربي أو الإنجليزي أو الرمز (مثل: sepsis, الجلطة, J80)..."
                                       value="{{ old('icd10_code', $admission->icd10_code) }}">
                                <button type="button" class="btn btn-outline-secondary {{ $admission->icd10_code ? '' : 'd-none' }}" id="ccu_icd10_clear_btn" title="مسح">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <input type="hidden" name="icd10_code" id="ccu_icd10_code" value="{{ old('icd10_code', $admission->icd10_code) }}">
                            <div id="ccu_icd10_dropdown" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1065; max-height: 240px; overflow-y: auto; display: none;"></div>

                            <div id="ccu_selected_diagnosis_card" class="mt-2 p-2 rounded border bg-white {{ $admission->icd10_code ? '' : 'd-none' }}">
                                <div class="d-flex justify-content-between align-items-center small">
                                    <div>
                                        <span class="badge bg-primary font-monospace me-1" id="ccu_diag_card_code">{{ $admission->icd10_code }}</span>
                                        <span class="fw-bold text-dark" id="ccu_diag_card_text">{{ $admission->icd10_code ? 'التصنيف المعتمد' : '' }}</span>
                                    </div>
                                    <span class="badge bg-success-subtle text-success">معتمد</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <label for="ccu_primary_diagnosis_input" class="form-label fw-bold small text-dark mb-1">
                                التشخيص السريري الأولي <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="primary_diagnosis" id="ccu_primary_diagnosis_input" class="form-control form-control-sm" required
                                   placeholder="مثال: Severe Sepsis with Septic Shock, ARDS, Intracranial Bleed..."
                                   value="{{ old('primary_diagnosis', $admission->primary_diagnosis) }}">
                        </div>

                        <div class="col-md-2">
                            <label for="ccu_initial_gcs_input" class="form-label fw-bold small text-danger mb-1">
                                مقياس الوعي GCS (3-15)
                            </label>
                            <input type="number" name="initial_gcs" id="ccu_initial_gcs_input" class="form-control form-control-sm" min="3" max="15"
                                   placeholder="3-15" value="{{ old('initial_gcs', $admission->initial_gcs) }}">
                        </div>

                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                                <i class="fas fa-check-circle me-1"></i> اعتماد وحفظ التشخيص الأولي بالطبلة
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>

    <!-- ================= شريط الباقة المالية الحالية وتبديل الخدمة / الخدمات المخصصة ================= -->
    @if(isset($stayDetails))
    <div class="card border-0 shadow-sm mb-3 rounded-4 bg-white" id="ccuPackageStrip">
        <div class="card-body py-2.5 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center flex-wrap gap-2 small">
                <span class="badge bg-dark-subtle text-dark border px-2.5 py-1.5">
                    <i class="fas fa-layer-group text-primary me-1"></i>
                    الباقة الحالية: <strong>{{ $stayDetails['active_service']?->name ?? ($admission->ccuService?->name ?? 'تلقائي') }}</strong>
                </span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                    المدة المحتسبة: {{ $stayDetails['days_count'] }} يوم
                    @if($stayDetails['is_insured'])
                        (التراكم السنوي: {{ $stayDetails['total_annual_days_with_current'] }}/{{ $stayDetails['annual_max_days'] }} يوم)
                    @endif
                </span>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 font-monospace">
                    على المريض: {{ number_format($stayDetails['patient_grand_total']) }} د.ع
                </span>
                @if($stayDetails['is_insured'])
                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 font-monospace">
                    على الضمان: {{ number_format($stayDetails['insurance_grand_total']) }} د.ع
                </span>
                @endif
                @if(($stayDetails['bundled_reference_total'] ?? 0) > 0)
                <span class="badge bg-info-subtle text-dark border border-info-subtle px-2 py-1 font-monospace" title="القيمة الأصلية للفحوصات المشمولة بالباقة لأغراض المطالبات">
                    فحوصات مشمولة (الأصلية): {{ number_format($stayDetails['bundled_reference_total']) }} د.ع (0 على المريض)
                </span>
                @endif
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                @if($admission->status === 'active' && isset($ccuPackageServices) && $ccuPackageServices->isNotEmpty())
                <form action="{{ route('ccu.admissions.switch-service', $admission->id) }}" method="POST" class="d-flex align-items-center gap-1">
                    @csrf
                    <select name="ccu_service_id" class="form-select form-select-sm" style="max-width: 240px;" required>
                        @foreach($ccuPackageServices as $srv)
                            <option value="{{ $srv->id }}" {{ ($stayDetails['active_service']?->id ?? $admission->ccu_service_id) == $srv->id ? 'selected' : '' }}>
                                {{ $srv->name }} ({{ number_format($stayDetails['is_insured'] && $srv->is_hi_active ? $srv->hi_price : $srv->daily_fee) }} د.ع)
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary fw-bold text-nowrap">
                        <i class="fas fa-sync-alt me-1"></i> تبديل الباقة
                    </button>
                </form>

                <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold" data-bs-toggle="collapse" data-bs-target="#ccuAddonServiceCollapse">
                    <i class="fas fa-plus-circle me-1"></i> خدمة منفصلة / كاش
                </button>
                @endif
            </div>
        </div>

        @if($admission->status === 'active')
        <div class="collapse border-top bg-light p-3" id="ccuAddonServiceCollapse">
            <form action="{{ route('ccu.admissions.addon-service', $admission->id) }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label small fw-bold mb-1">اختر خدمة إضافية جاهزة (أو اكتب مخصصة)</label>
                    <select name="ccu_service_id" id="ccu_addon_select" class="form-select form-select-sm">
                        <option value="">-- خدمة حرة مخصصة --</option>
                        @foreach(($ccuAddonServices ?? collect()) as $addon)
                            <option value="{{ $addon->id }}" data-name="{{ $addon->name }}" data-price="{{ (float) ($stayDetails['is_insured'] && $addon->is_hi_active ? $addon->hi_price : $addon->daily_fee) }}">
                                {{ $addon->name }} ({{ number_format($addon->daily_fee) }} د.ع)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">اسم الخدمة / الجهاز</label>
                    <input type="text" name="custom_name" id="ccu_addon_custom_name" class="form-control form-control-sm" placeholder="مثال: جلسة دعم / تمريض خاص">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">سعر الوحدة (د.ع)</label>
                    <input type="number" name="unit_price" id="ccu_addon_unit_price" class="form-control form-control-sm" min="0" step="1000" placeholder="0">
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-bold mb-1">الكمية/أيام</label>
                    <input type="number" name="quantity" class="form-control form-control-sm" value="1" min="1">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold w-100">
                        <i class="fas fa-plus me-1"></i> إضافة للحساب
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
    @endif

    @php
        $currentActiveTab = session('active_tab', request()->query('tab', request()->get('_active_tab', 'vitalsTabPane')));
    @endphp

    <!-- ================= توجيهات وملاحظات أطباء الاختصاص المثبتة (Pinned Clinical Orders) ================= -->
    @if(isset($pinnedNotes) && $pinnedNotes->isNotEmpty())
        <div class="card border-danger border-2 shadow-sm mb-4 bg-danger bg-opacity-10" style="border-radius: 14px;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-danger-subtle">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-danger text-white p-2 rounded-circle fs-6">
                            <i class="fas fa-thumbtack"></i>
                        </span>
                        <div>
                            <h6 class="fw-bold text-danger mb-0">توجيهات وملاحظات الطبيب الاختصاص المثبتة (Pinned Clinical Directives)</h6>
                            <small class="text-muted" style="font-size: 11px;">توجيهات مشددة ذات أولوية عليا واجبة التطبيق الفوري لجميع أفراد الكادر الطبي والتمريضي</small>
                        </div>
                    </div>
                    <span class="badge bg-danger text-white rounded-pill px-3 py-1.5 fs-7">{{ $pinnedNotes->count() }} أمر مثبت</span>
                </div>

                <div class="d-flex flex-column gap-2">
                    @foreach($pinnedNotes as $pNote)
                        <div class="bg-white p-2.5 rounded-3 border border-danger-subtle shadow-xs d-flex justify-content-between align-items-start gap-3">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge {{ $pNote->priority_badge_class }} py-0.5 px-2">{{ $pNote->priority_name }}</span>
                                    <strong class="text-danger small">
                                        <i class="fas fa-user-md me-1"></i> د. {{ $pNote->doctor?->user?->name ?? $pNote->creator?->name ?? 'طبيب الاستشاري' }}
                                    </strong>
                                    <span class="text-muted small" style="font-size: 11px;">
                                        <i class="fas fa-clock me-1"></i> {{ $pNote->pinned_at ? $pNote->pinned_at->format('Y-m-d H:i') : $pNote->created_at->format('Y-m-d H:i') }}
                                    </span>
                                </div>
                                <div class="text-dark fw-bold" style="white-space: pre-line; font-size: 0.95rem;">{{ $pNote->note_text }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <form action="{{ route('ccu.doctor-notes.toggle-pin', $pNote->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2 fw-bold" title="إلغاء التثبيت من رأس الملف">
                                        <i class="fas fa-thumbtack me-1"></i> إلغاء التثبيت
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- التبويبات السريرية (Clinical Tabs) -->
    <div class="card border-0 shadow-sm" style="border-radius: 14px;">
        <div class="card-header bg-white border-bottom-0 pt-3 pb-0">
            <ul class="nav nav-tabs card-header-tabs" id="icuTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark {{ $currentActiveTab === 'vitalsTabPane' ? 'active' : '' }}" id="vitals-tab" data-bs-toggle="tab" data-bs-target="#vitalsTabPane" type="button">
                        <i class="fas fa-heartbeat text-danger me-1"></i> العلامات الحيوية والتنفس
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark {{ $currentActiveTab === 'fluidsTabPane' ? 'active' : '' }}" id="fluids-tab" data-bs-toggle="tab" data-bs-target="#fluidsTabPane" type="button">
                        <i class="fas fa-tint text-info me-1"></i> ميزان السوائل In/Out
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark {{ $currentActiveTab === 'ordersTabPane' ? 'active' : '' }}" id="orders-tab" data-bs-toggle="tab" data-bs-target="#ordersTabPane" type="button">
                        <i class="fas fa-notes-medical text-primary me-1"></i> أوامر وتوجيهات الطبيب
                        @if(isset($doctorNotes) && $doctorNotes->count() > 0)
                            <span class="badge bg-primary rounded-pill ms-1">{{ $doctorNotes->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark {{ $currentActiveTab === 'medicationTabPane' ? 'active' : '' }}" id="medication-tab" data-bs-toggle="tab" data-bs-target="#medicationTabPane" type="button">
                        <i class="fas fa-pills text-warning me-1"></i> 💊 جدول الأدوية والمضخات (MAR)
                        @if(isset($medicationSchedules) && $medicationSchedules->count() > 0)
                            <span class="badge bg-warning text-dark rounded-pill ms-1">{{ $medicationSchedules->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark {{ $currentActiveTab === 'nursingTabPane' ? 'active' : '' }}" id="nursing-tab" data-bs-toggle="tab" data-bs-target="#nursingTabPane" type="button">
                        <i class="fas fa-clipboard-check text-success me-1"></i> جدول الرعاية التمريضية
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark {{ $currentActiveTab === 'labTabPane' ? 'active' : '' }}" id="lab-tab" data-bs-toggle="tab" data-bs-target="#labTabPane" type="button">
                        <i class="fas fa-microscope text-primary me-1"></i> 🧪 تحاليل المختبر
                        @if(isset($labOrders) && $labOrders->count() > 0)
                            <span class="badge bg-primary rounded-pill ms-1">{{ $labOrders->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark {{ $currentActiveTab === 'radiologyTabPane' ? 'active' : '' }}" id="radiology-tab" data-bs-toggle="tab" data-bs-target="#radiologyTabPane" type="button">
                        <i class="fas fa-x-ray text-info me-1"></i> 🩻 الأشعة والتصوير
                        @if(isset($radiologyOrders) && $radiologyOrders->count() > 0)
                            <span class="badge bg-info rounded-pill ms-1">{{ $radiologyOrders->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark {{ $currentActiveTab === 'consultationsTabPane' ? 'active' : '' }}" id="consultations-tab" data-bs-toggle="tab" data-bs-target="#consultationsTabPane" type="button">
                        <i class="fas fa-user-md text-purple me-1" style="color: #6f42c1;"></i> 🩺 الاستشارات التخصصية
                        @if(isset($consultations) && $consultations->count() > 0)
                            <span class="badge rounded-pill ms-1 text-white" style="background-color: #6f42c1;">{{ $consultations->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold text-dark {{ $currentActiveTab === 'attachmentsTabPane' ? 'active' : '' }}" id="attachments-tab" data-bs-toggle="tab" data-bs-target="#attachmentsTabPane" type="button">
                        <i class="fas fa-folder-open text-secondary me-1"></i> 📁 المرفقات وتخطيط القلب (ECG)
                        @if(isset($attachments) && $attachments->count() > 0)
                            <span class="badge bg-secondary rounded-pill ms-1">{{ $attachments->count() }}</span>
                        @endif
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <div class="tab-content" id="icuTabsContent">
                
                <!-- ================= تبويب 1: العلامات الحيوية ومعايير العناية ================= -->
                <div class="tab-pane fade {{ $currentActiveTab === 'vitalsTabPane' ? 'show active' : '' }}" id="vitalsTabPane">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-chart-line text-danger me-2"></i>
                            شيت العلامات الحيوية ومعايير أجهزة التنفس (Flowsheet)
                        </h5>
                        @can('record icu vitals')
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#addVitalsModal">
                            <i class="fas fa-plus-circle me-1"></i> تسجيل قراءة حيوية جديدة
                        </button>
                        @endcan
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-bordered text-center">
                            <thead class="table-light small">
                                <tr>
                                    <th>التاريخ والوقت</th>
                                    <th>GCS (الوعي)</th>
                                    <th>النبض HR</th>
                                    <th>الضغط BP (MAP)</th>
                                    <th>التنفس RR</th>
                                    <th>الحرارة Temp</th>
                                    <th>الأكسجين SpO2</th>
                                    <th>جهاز التنفس / الأكسجين</th>
                                    <th>FiO2 / PEEP</th>
                                    <th>سكر الدم</th>
                                    <th>المسجل</th>
                                    <th>ملاحظات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($admission->vitals as $vital)
                                    <tr>
                                        <td class="small">{{ $vital->recorded_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <span class="badge {{ $vital->gcs_badge_class }}">
                                                {{ $vital->gcs ?? '--' }}
                                            </span>
                                        </td>
                                        <td class="{{ ($vital->heart_rate > 110 || $vital->heart_rate < 55) ? 'text-danger fw-bold' : '' }}">
                                            {{ $vital->heart_rate ?? '--' }}
                                        </td>
                                        <td>
                                            {{ $vital->systolic_bp ? $vital->systolic_bp.'/'.$vital->diastolic_bp : '--' }}
                                            @if($vital->calculated_map)
                                                <small class="text-muted d-block">({{ $vital->calculated_map }})</small>
                                            @endif
                                        </td>
                                        <td>{{ $vital->respiratory_rate ?? '--' }}</td>
                                        <td>{{ $vital->temperature ? $vital->temperature.'°C' : '--' }}</td>
                                        <td class="{{ ($vital->spo2 && $vital->spo2 < 92) ? 'text-danger fw-bold' : 'text-success fw-bold' }}">
                                            {{ $vital->spo2 ? $vital->spo2.'%' : '--' }}
                                        </td>
                                        <td>
                                            {{ $vital->oxygen_device ?? $vital->ventilator_mode ?? 'Room Air' }}
                                        </td>
                                        <td>
                                            @if($vital->fio2 || $vital->peep)
                                                <small>{{ $vital->fio2 ? $vital->fio2.'%' : '' }} / {{ $vital->peep ? 'P:'.$vital->peep : '' }}</small>
                                            @else
                                                --
                                            @endif
                                        </td>
                                        <td>{{ $vital->blood_sugar ? $vital->blood_sugar.' mg/dL' : '--' }}</td>
                                        <td class="small">{{ $vital->recordedByUser?->name ?? 'كادر العناية' }}</td>
                                        <td class="small text-start">{{ $vital->notes ?? '--' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-muted py-4">لم يتم تدوين أي قراءات حيوية حتى الآن.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ================= تبويب 2: ميزان السوائل (Fluid Balance) ================= -->
                <div class="tab-pane fade {{ $currentActiveTab === 'fluidsTabPane' ? 'show active' : '' }}" id="fluidsTabPane">
                    <div class="row mb-4 g-3">
                        <div class="col-md-4">
                            <div class="card border-0 bg-primary bg-opacity-10 text-primary p-3 rounded-3 text-center">
                                <small class="fw-bold">إجمالي السوائل الداخلة لليوم (Intake)</small>
                                <h3 class="fw-bold mb-0">{{ number_format($todayIntake) }} مل</h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 bg-danger bg-opacity-10 text-danger p-3 rounded-3 text-center">
                                <small class="fw-bold">إجمالي السوائل الخارجة لليوم (Output)</small>
                                <h3 class="fw-bold mb-0">{{ number_format($todayOutput) }} مل</h3>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 {{ $todayNet >= 0 ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-dark' }} p-3 rounded-3 text-center">
                                <small class="fw-bold">صافي ميزان السوائل (Net Balance)</small>
                                <h3 class="fw-bold mb-0">{{ ($todayNet > 0 ? '+' : '') . number_format($todayNet) }} مل</h3>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-balance-scale text-info me-2"></i>
                            سجل ميزان السوائل بالساعة (Fluid Balance Chart)
                        </h5>
                        @can('record icu fluid balance')
                        <button type="button" class="btn btn-info text-white btn-sm" data-bs-toggle="modal" data-bs-target="#addFluidModal">
                            <i class="fas fa-plus-circle me-1"></i> تسجيل قراءة سوائل جديدة
                        </button>
                        @endcan
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-bordered text-center">
                            <thead class="table-light small">
                                <tr>
                                    <th rowspan="2" class="align-middle">الوقت</th>
                                    <th colspan="4" class="bg-primary bg-opacity-10 text-primary">السوائل الداخلة (Intake - ml)</th>
                                    <th rowspan="2" class="align-middle bg-primary text-white">إجمالي الداخل</th>
                                    <th colspan="4" class="bg-danger bg-opacity-10 text-danger">السوائل الخارجة (Output - ml)</th>
                                    <th rowspan="2" class="align-middle bg-danger text-white">إجمالي الخارج</th>
                                    <th rowspan="2" class="align-middle">الصافي (Net)</th>
                                    <th rowspan="2" class="align-middle">المسجل</th>
                                </tr>
                                <tr>
                                    <th class="small">محاليل IV</th>
                                    <th class="small">دم ومشتقاته</th>
                                    <th class="small">تغذية أنبوبية</th>
                                    <th class="small">علاجات</th>
                                    <th class="small">بول بالساعة</th>
                                    <th class="small">مفارغ جراحية</th>
                                    <th class="small">رايل NG Tube</th>
                                    <th class="small">أخرى</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($admission->fluidBalances as $balance)
                                    <tr>
                                        <td class="small">{{ $balance->recorded_at->format('Y-m-d H:i') }}</td>
                                        <td>{{ $balance->intake_iv_fluids ?: '--' }}</td>
                                        <td>{{ $balance->intake_blood_products ?: '--' }}</td>
                                        <td>{{ $balance->intake_enteral_feeding ?: '--' }}</td>
                                        <td>{{ $balance->intake_medications ?: '--' }}</td>
                                        <td class="fw-bold text-primary">{{ $balance->intake_total }}</td>
                                        <td>{{ $balance->output_urine ?: '--' }}</td>
                                        <td>{{ $balance->output_drains ?: '--' }}</td>
                                        <td>{{ $balance->output_nasogastric ?: '--' }}</td>
                                        <td>{{ $balance->output_other ?: '--' }}</td>
                                        <td class="fw-bold text-danger">{{ $balance->output_total }}</td>
                                        <td>
                                            <span class="badge {{ $balance->balance_badge_class }} fs-6">
                                                {{ ($balance->net_balance > 0 ? '+' : '') . $balance->net_balance }}
                                            </span>
                                        </td>
                                        <td class="small">{{ $balance->recordedByUser?->name ?? 'كادر العناية' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-muted py-4">لا توجد قراءات سوائل مسجلة حتى الآن.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ================= تبويب 3: أوامر الطبيب ================= -->
                <div class="tab-pane fade {{ $currentActiveTab === 'ordersTabPane' ? 'show active' : '' }}" id="ordersTabPane">
                    
                    <!-- صندوق تدوين التوجيهات السريرية المشددة وتثبيتها في رأس الملف -->
                    <div class="card border-0 bg-light p-3 rounded-3 shadow-xs mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <h6 class="fw-bold text-danger mb-0">
                                <i class="fas fa-thumbtack me-1"></i> تدوين توجيهات وملاحظات الطبيب الاختصاص (Clinical Directives & Pinned Orders)
                            </h6>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">يظهر في أعلى إضبارة المريض عند تثبيته</span>
                        </div>
                        @if($admission->status === 'active')
                        <form action="{{ route('ccu.doctor-notes.store', $admission->id) }}" method="POST">
                            @csrf
                            <div class="row g-2 mb-2">
                                <div class="col-md-9">
                                    <textarea name="note_text" class="form-control form-control-sm" rows="2" required placeholder="اكتب التوجيه السريري المشدد أو خطة الإنعاش (مثل: NPO ممنوع السوائل فموياً، ضغط شرياني وسطي MAP > 65، بدء بروتوكول Weaning، تثبيت الرقبة)..."></textarea>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-2">
                                        <select name="priority" class="form-select form-select-sm fw-bold">
                                            <option value="urgent" selected>⚡ عاجل (Urgent)</option>
                                            <option value="stat">🚨 STAT فوري وحرج</option>
                                            <option value="normal">اعتيادي (Routine)</option>
                                        </select>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_pinned" id="chkPinNote" value="1" checked>
                                        <label class="form-check-label small fw-bold text-danger" for="chkPinNote">
                                            📌 تثبيت في رأس الملف
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold shadow-xs">
                                        <i class="fas fa-save me-1"></i> حفظ وتثبيت
                                    </button>
                                </div>
                            </div>
                        </form>
                        @endif

                        @if(isset($doctorNotes) && $doctorNotes->isNotEmpty())
                            <div class="mt-2 pt-2 border-top">
                                <span class="small fw-bold text-dark d-block mb-2">سجل توجيهات وملاحظات الأطباء:</span>
                                <div class="d-flex flex-column gap-2" style="max-height: 250px; overflow-y: auto;">
                                    @foreach($doctorNotes as $dNote)
                                        <div class="p-2 rounded bg-white border d-flex justify-content-between align-items-start gap-2 shadow-xs">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge {{ $dNote->priority_badge_class }}" style="font-size: 10px;">{{ $dNote->priority_name }}</span>
                                                    <strong class="small text-dark">د. {{ $dNote->doctor?->user?->name ?? $dNote->creator?->name ?? 'الاستشاري' }}</strong>
                                                    <span class="text-muted small" style="font-size: 10px;">{{ $dNote->created_at->format('Y-m-d H:i') }}</span>
                                                    @if($dNote->is_pinned)
                                                        <span class="badge bg-danger text-white" style="font-size: 9px;"><i class="fas fa-thumbtack me-1"></i>مثبت في رأس الملف</span>
                                                    @endif
                                                </div>
                                                <div class="small text-dark fw-bold" style="white-space: pre-line;">{{ $dNote->note_text }}</div>
                                            </div>
                                            <div class="d-flex gap-1 align-items-center">
                                                <form action="{{ route('ccu.doctor-notes.toggle-pin', $dNote->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs {{ $dNote->is_pinned ? 'btn-danger' : 'btn-outline-secondary' }} py-0.5 px-1.5" title="{{ $dNote->is_pinned ? 'إلغاء التثبيت' : 'تثبيت في رأس الملف' }}" style="font-size: 10px;">
                                                        <i class="fas fa-thumbtack"></i> {{ $dNote->is_pinned ? 'إلغاء' : 'تثبيت' }}
                                                    </button>
                                                </form>
                                                @if($admission->status === 'active')
                                                <form action="{{ route('ccu.doctor-notes.destroy', $dNote->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الملاحظة؟');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-outline-danger py-0.5 px-1.5" style="font-size: 10px;" title="حذف">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-file-medical text-primary me-2"></i>
                            أوامر الطبيب الاستشاري والمتابعة السريرية
                        </h5>
                        @can('manage icu medical orders')
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addOrderModal">
                            <i class="fas fa-plus-circle me-1"></i> كتابة أمر طبيب جديد
                        </button>
                        @endcan
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-bordered">
                            <thead class="table-light small text-center">
                                <tr>
                                    <th>تاريخ الأمر</th>
                                    <th>الطبيب الآمر</th>
                                    <th>الأولوية</th>
                                    <th>النوع</th>
                                    <th class="text-start">نص وتفاصيل الأمر الطبي</th>
                                    <th>الحالة</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($admission->medicalOrders as $order)
                                    <tr>
                                        <td class="small text-center">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="small text-center">{{ $order->doctor?->user?->name ?? 'طبيب العناية' }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $order->priority_badge_class }}">
                                                {{ $order->priority_name }}
                                            </span>
                                        </td>
                                        <td class="text-center small">{{ $order->order_type }}</td>
                                        <td class="fw-bold text-dark">{{ $order->order_text }}</td>
                                        <td class="text-center">
                                            <span class="badge {{ $order->status_badge_class }}">
                                                {{ $order->status_name }}
                                            </span>
                                            @if($order->status === 'completed')
                                                <small class="d-block text-muted" style="font-size: 0.7rem;">
                                                    {{ $order->completedByUser?->name }} ({{ $order->completed_at?->format('H:i') }})
                                                </small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($order->status !== 'completed')
                                                <form method="POST" action="{{ route('icu.orders.complete', $order->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="fas fa-check me-1"></i> تم التنفيذ
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-success"><i class="fas fa-check-double"></i> مكتمل</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted text-center py-4">لا توجد أوامر طبية مسجلة.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ================= تبويب 4: الرعاية التمريضية ================= -->
                <div class="tab-pane fade {{ $currentActiveTab === 'nursingTabPane' ? 'show active' : '' }}" id="nursingTabPane">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-user-nurse text-success me-2"></i>
                            جدول خطة الرعاية التمريضية بالساعة (Nursing Care Plan)
                        </h5>
                        @can('manage icu care plans')
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addCareScheduleModal">
                            <i class="fas fa-plus-circle me-1"></i> إضافة مهمة رعاية
                        </button>
                        @endcan
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-bordered text-center">
                            <thead class="table-light small">
                                <tr>
                                    <th>الوقت المجدول</th>
                                    <th class="text-start">مهمة الرعاية التمريضية</th>
                                    <th>الحالة</th>
                                    <th>المنفذ والوقت</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($admission->careSchedules as $schedule)
                                    <tr class="{{ $schedule->is_done ? 'table-light text-muted' : '' }}">
                                        <td class="fw-bold text-primary">{{ $schedule->scheduled_time }}</td>
                                        <td class="text-start fw-bold {{ $schedule->is_done ? 'text-decoration-line-through' : '' }}">
                                            {{ $schedule->care_type }}
                                            @if($schedule->notes)
                                                <small class="d-block text-muted">{{ $schedule->notes }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($schedule->is_done)
                                                <span class="badge bg-success">تمت الرعاية</span>
                                            @else
                                                <span class="badge bg-warning text-dark">بانتظار الممرض</span>
                                            @endif
                                        </td>
                                        <td class="small">
                                            @if($schedule->is_done)
                                                {{ $schedule->doneByUser?->name ?? 'الممرض' }} ({{ $schedule->done_at?->format('H:i') }})
                                            @else
                                                --
                                            @endif
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('icu.care-schedules.toggle', $schedule->id) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm {{ $schedule->is_done ? 'btn-outline-secondary' : 'btn-success' }}">
                                                    <i class="fas {{ $schedule->is_done ? 'fa-undo' : 'fa-check' }} me-1"></i>
                                                    {{ $schedule->is_done ? 'إلغاء' : 'تأكيد الإنجاز' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted py-4">لا توجد مهام رعاية مجدولة.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ================= تبويب 5: تحاليل وفحوصات المختبر ================= -->
                <div class="tab-pane fade {{ $currentActiveTab === 'labTabPane' ? 'show active' : '' }}" id="labTabPane">
                    <div class="row g-4">
                        @if($admission->status === 'active')
                        <!-- عمود تقديم طلب التحاليل المخبرية -->
                        <div class="col-xl-5 col-lg-12">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-sm">
                                <form action="{{ route('icu.lab-orders.store', $admission->id) }}" method="POST" id="icuLabOrderForm">
                                    @csrf

                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary text-white p-2 rounded-circle fs-6">
                                                <i class="fas fa-microscope"></i>
                                            </span>
                                            <div>
                                                <h5 class="mb-0 fw-bold text-primary">طلب تحاليل المختبر</h5>
                                                <small class="text-muted">ابحث أو اختر من باقات تحاليل العناية</small>
                                            </div>
                                        </div>
                                        <button type="submit" id="btnSubmitIcuLab" class="btn btn-primary btn-sm px-3 shadow-sm fw-bold">
                                            <i class="fas fa-paper-plane me-1"></i> إرسال الطلب (<span class="icu-lab-selected-count">0</span>)
                                        </button>
                                    </div>

                                    <!-- أزرار باقات العناية المركزة الشائعة بنقرة واحدة -->
                                    <div class="mb-3 p-2 bg-white rounded-3 border">
                                        <span class="small fw-bold text-dark d-block mb-1">
                                            <i class="fas fa-bolt text-warning me-1"></i> باقات العناية المركزة الشائعة (إضافة بنقرة):
                                        </span>
                                        <div class="d-flex flex-wrap gap-1">
                                            <button type="button" class="btn btn-xs btn-outline-danger icu-quick-lab-btn" data-tests='["ABG", "Arterial Blood Gas", "غازات الدم الشرياني"]'>
                                                <i class="fas fa-lungs me-1"></i> ABG غازات الدم
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-primary icu-quick-lab-btn" data-tests='["CBC", "Complete Blood Count", "صورة دم كاملة"]'>
                                                <i class="fas fa-tint me-1"></i> CBC صورة دم
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-success icu-quick-lab-btn" data-tests='["Electrolytes", "Sodium & Potassium", "Na/K", "شوارد"]'>
                                                <i class="fas fa-vial me-1"></i> Electrolytes شوارد
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-info icu-quick-lab-btn" data-tests='["Renal Function", "Urea & Creatinine", "RFT", "وظائف كلى"]'>
                                                <i class="fas fa-flask me-1"></i> RFT كلى
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-warning text-dark icu-quick-lab-btn" data-tests='["Liver Function", "LFT", "وظائف كبد"]'>
                                                <i class="fas fa-vials me-1"></i> LFT كبد
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary icu-quick-lab-btn" data-tests='["PT/INR", "Coagulation", "تخثر"]'>
                                                <i class="fas fa-stopwatch me-1"></i> PT / INR تخثر
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-danger icu-quick-lab-btn" data-tests='["Troponin", "تروبونين"]'>
                                                <i class="fas fa-heartbeat me-1"></i> Troponin تروبونين
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-dark icu-quick-lab-btn" data-tests='["Blood Culture", "زرع دم"]'>
                                                <i class="fas fa-bacterium me-1"></i> زرع دم Blood Culture
                                            </button>
                                        </div>
                                    </div>

                                    <!-- حقل البحث الذكي مع قائمة منبثقة فورية -->
                                    <div class="mb-3 position-relative">
                                        <div class="input-group shadow-sm">
                                            <span class="input-group-text bg-primary text-white"><i class="fas fa-search"></i></span>
                                            <input type="text" id="icuLabSearchInput" class="form-control" placeholder="اكتب اسم التحليل (مثل: CBC, ABG, CRP, Troponin...) واضغط Enter..." autocomplete="off">
                                        </div>
                                        <div id="icuLabAutocompleteMenu" class="dropdown-menu w-100 shadow-lg p-1 border-0" style="max-height: 260px; overflow-y: auto; display: none; position: absolute; z-index: 1050;"></div>
                                    </div>

                                    <!-- صينية التحاليل المختارة حالياً (Chips Tray) -->
                                    <div id="icuLabSelectedTray" class="mb-3 p-3 bg-white rounded-3 border d-none">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-primary">
                                                <i class="fas fa-check-circle me-1"></i> التحاليل المختارة للإرسال (<span class="icu-lab-selected-count">0</span>):
                                            </span>
                                            <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" id="icuLabClearAllBtn">
                                                <i class="fas fa-trash-alt me-1"></i> إفراغ الكل
                                            </button>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2" id="icuLabChipsContainer"></div>
                                    </div>

                                    <!-- ملاحظات وتوجيهات الطبيب للمختبر -->
                                    <div class="mb-3 p-2 bg-white rounded-3 border">
                                        <label for="icuLabDoctorNotes" class="form-label small fw-bold text-dark mb-1">
                                            <i class="fas fa-comment-medical text-primary me-1"></i> توجيهات الطبيب للمختبر:
                                        </label>
                                        <textarea class="form-control form-control-sm" id="icuLabDoctorNotes" name="doctor_notes" rows="2" placeholder="أي ملاحظة لفني المختبر (مثل: عينة من خط شرياني A-Line، نتيجة فورية للإنعاش)..."></textarea>
                                    </div>

                                    <!-- الأولوية -->
                                    <div class="mb-3 p-2 bg-white rounded-3 border">
                                        <label class="form-label small fw-bold text-dark mb-1">
                                            <i class="fas fa-stopwatch me-1 text-danger"></i> أولوية وسرعة الفحص:
                                        </label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="priority" id="icuLabPriNormal" value="normal">
                                                <label class="form-check-label small" for="icuLabPriNormal">اعتيادي (Normal)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="priority" id="icuLabPriUrgent" value="urgent">
                                                <label class="form-check-label small text-warning fw-bold" for="icuLabPriUrgent">عاجل (Urgent)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="priority" id="icuLabPriStat" value="stat" checked>
                                                <label class="form-check-label small text-danger fw-bold" for="icuLabPriStat">🚨 STAT (فوري وحرج)</label>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">
                                        <i class="fas fa-paper-plane me-1"></i> إرسال التحاليل لقسم المختبر المركزي
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endif

                        <!-- جدول طلبات ونتائج التحاليل للمريض -->
                        <div class="{{ $admission->status === 'active' ? 'col-xl-7 col-lg-12' : 'col-12' }}">
                            <div class="card border-0 shadow-sm p-3 rounded-3">
                                <h6 class="fw-bold text-dark mb-3">
                                    <i class="fas fa-list-alt text-primary me-1"></i> سجل تحاليل ونتائج المريض بالعناية
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle table-bordered text-center small">
                                        <thead class="table-light">
                                            <tr>
                                                <th>الفحص المطلوب</th>
                                                <th>الأولوية</th>
                                                <th>الحالة</th>
                                                <th>الاحتساب المالي (بضغطة زر)</th>
                                                <th>النتيجة / المعدل الطبيعي</th>
                                                <th>تاريخ الطلب</th>
                                                <th>الملاحظات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($labOrders as $order)
                                                <tr>
                                                    <td class="fw-bold text-primary text-start">
                                                        <i class="fas fa-vial me-1 text-secondary"></i>
                                                        {{ $order->item_name ?: $order->order_text }}
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $order->priority_badge_class }}">
                                                            {{ $order->priority_name }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $order->status_badge_class }}">
                                                            {{ $order->status_name }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $isBundled = (bool) ($order->is_bundled ?? false);
                                                            $origDisp = (float) ($order->original_price_display ?? $order->original_price ?? $order->price ?? 0);
                                                            $billedDisp = (float) ($order->billed_price ?? $order->price ?? 0);
                                                        @endphp
                                                        @if($isBundled)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle d-block mb-1">
                                                                0 د.ع (مشمول بالباقة)
                                                            </span>
                                                            @if($origDisp > 0)
                                                                <small class="text-muted d-block mb-1 font-monospace" style="font-size:10px;">الأصلية: {{ number_format($origDisp) }} د.ع</small>
                                                            @endif
                                                        @else
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle d-block mb-1 font-monospace">
                                                                {{ number_format($billedDisp) }} د.ع (منفرد)
                                                            </span>
                                                        @endif
                                                        <form action="{{ route('cashier.ccu.orders.toggle-billing', $order->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="billing_mode" value="{{ $isBundled ? 'extra' : 'bundled' }}">
                                                            <input type="hidden" name="_active_tab" value="labsTabPane">
                                                            <button type="submit" class="btn btn-xs {{ $isBundled ? 'btn-outline-warning text-dark' : 'btn-outline-success' }} py-0 px-2" style="font-size: 11px;">
                                                                <i class="fas fa-toggle-{{ $isBundled ? 'off' : 'on' }} me-1"></i>
                                                                {{ $isBundled ? 'احتساب منفرد' : 'شمول بالباقة (0)' }}
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        @if($order->result)
                                                            <strong class="text-success">{{ $order->result }}</strong>
                                                            @if($order->unit)<small class="text-muted">{{ $order->unit }}</small>@endif
                                                            @if($order->reference_range)
                                                                <br><small class="text-muted">({{ $order->reference_range }})</small>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">بانتظار الفحص</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted">
                                                        {{ $order->ordered_at ? $order->ordered_at->format('Y-m-d H:i') : $order->created_at->format('Y-m-d H:i') }}
                                                    </td>
                                                    <td class="small text-muted">{{ $order->instructions ?: '--' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-muted py-4">لا توجد طلبات تحاليل مسجلة لهذا المريض بعد.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= تبويب 6: الأشعة والتصوير الطبي ================= -->
                <div class="tab-pane fade {{ $currentActiveTab === 'radiologyTabPane' ? 'show active' : '' }}" id="radiologyTabPane">
                    <div class="row g-4">
                        @if($admission->status === 'active')
                        <!-- عمود تقديم طلب الأشعة والسونار -->
                        <div class="col-xl-5 col-lg-12">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-sm">
                                <form action="{{ route('icu.radiology-orders.store', $admission->id) }}" method="POST" id="icuRadOrderForm">
                                    @csrf

                                    <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-info text-white p-2 rounded-circle fs-6">
                                                <i class="fas fa-x-ray"></i>
                                            </span>
                                            <div>
                                                <h5 class="mb-0 fw-bold text-info">طلب أشعة وتصوير طبي</h5>
                                                <small class="text-muted">أشعة نقالة، سونار بجانب السرير، إيكو قلب، مفراس</small>
                                            </div>
                                        </div>
                                        <button type="submit" id="btnSubmitIcuRad" class="btn btn-info text-white btn-sm px-3 shadow-sm fw-bold">
                                            <i class="fas fa-paper-plane me-1"></i> إرسال (<span class="icu-rad-selected-count">0</span>)
                                        </button>
                                    </div>

                                    <!-- أزرار التصوير الشائع بالعناية بنقرة واحدة -->
                                    <div class="mb-3 p-2 bg-white rounded-3 border">
                                        <span class="small fw-bold text-dark d-block mb-1">
                                            <i class="fas fa-bolt text-warning me-1"></i> تصوير العناية الشائع (إضافة بنقرة):
                                        </span>
                                        <div class="d-flex flex-wrap gap-1">
                                            <button type="button" class="btn btn-xs btn-outline-info icu-quick-rad-btn" data-exams='["Portable Chest X-Ray", "أشعة صدر متنقلة", "أشعة صدر"]'>
                                                <i class="fas fa-lungs me-1"></i> أشعة صدر متنقلة Portable CXR
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-danger icu-quick-rad-btn" data-exams='["Echocardiography", "إيكو قلب", "Bedside Echo"]'>
                                                <i class="fas fa-heart me-1"></i> إيكو قلب ECHO
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-primary icu-quick-rad-btn" data-exams='["Bedside Ultrasound", "سونار بطن بجانب السرير", "FAST Scan"]'>
                                                <i class="fas fa-circle-notch me-1"></i> سونار FAST / Abdomen
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-warning text-dark icu-quick-rad-btn" data-exams='["CT Brain", "مفراس دماغ", "Brain CT"]'>
                                                <i class="fas fa-brain me-1"></i> مفراس دماغ CT Brain
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-secondary icu-quick-rad-btn" data-exams='["Pelvis X-Ray", "أشعة حوض"]'>
                                                <i class="fas fa-bone me-1"></i> أشعة حوض Pelvis
                                            </button>
                                        </div>
                                    </div>

                                    <!-- حقل البحث الذكي عن الأشعة -->
                                    <div class="mb-3 position-relative">
                                        <div class="input-group shadow-sm">
                                            <span class="input-group-text bg-info text-white"><i class="fas fa-search"></i></span>
                                            <input type="text" id="icuRadSearchInput" class="form-control" placeholder="ابحث عن الفحص (صدر، سونار، إيكو، مفراس، رنين)..." autocomplete="off">
                                        </div>
                                        <div id="icuRadAutocompleteMenu" class="dropdown-menu w-100 shadow-lg p-1 border-0" style="max-height: 260px; overflow-y: auto; display: none; position: absolute; z-index: 1050;"></div>
                                    </div>

                                    <!-- صينية الفحوصات المختارة (Chips Tray) -->
                                    <div id="icuRadSelectedTray" class="mb-3 p-3 bg-white rounded-3 border d-none">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small fw-bold text-info">
                                                <i class="fas fa-check-circle me-1"></i> الفحوصات المختارة للإرسال (<span class="icu-rad-selected-count">0</span>):
                                            </span>
                                            <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" id="icuRadClearAllBtn">
                                                <i class="fas fa-trash-alt me-1"></i> إفراغ الكل
                                            </button>
                                        </div>
                                        <div class="d-flex flex-wrap gap-2" id="icuRadChipsContainer"></div>
                                    </div>

                                    <!-- الملاحظات السريرية لقسم الأشعة -->
                                    <div class="mb-3 p-2 bg-white rounded-3 border">
                                        <label for="icuRadDoctorNotes" class="form-label small fw-bold text-dark mb-1">
                                            <i class="fas fa-comment-medical text-info me-1"></i> دواعي الفحص السريرية وموقع التصوير:
                                        </label>
                                        <textarea class="form-control form-control-sm" id="icuRadDoctorNotes" name="doctor_notes" rows="2" placeholder="بيان دواعي الفحص (مثل: التأكد من موقع أنبوب ETT، اشتباه نزف دماغي، نزف داخلي)..."></textarea>
                                    </div>

                                    <!-- الأولوية -->
                                    <div class="mb-3 p-2 bg-white rounded-3 border">
                                        <label class="form-label small fw-bold text-dark mb-1">
                                            <i class="fas fa-stopwatch me-1 text-danger"></i> أولوية وسرعة التصوير:
                                        </label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="priority" id="icuRadPriNormal" value="normal">
                                                <label class="form-check-label small" for="icuRadPriNormal">اعتيادي (Normal)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="priority" id="icuRadPriUrgent" value="urgent">
                                                <label class="form-check-label small text-warning fw-bold" for="icuRadPriUrgent">عاجل (Urgent)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="priority" id="icuRadPriStat" value="stat" checked>
                                                <label class="form-check-label small text-danger fw-bold" for="icuRadPriStat">🚨 STAT (فوري وحرج)</label>
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-info text-white w-100 py-2 fw-bold shadow-sm">
                                        <i class="fas fa-paper-plane me-1"></i> إرسال الطلب لقسم الأشعة والتصوير
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endif

                        <!-- جدول طلبات وتقارير الأشعة للمريض -->
                        <div class="{{ $admission->status === 'active' ? 'col-xl-7 col-lg-12' : 'col-12' }}">
                            <div class="card border-0 shadow-sm p-3 rounded-3">
                                <h6 class="fw-bold text-dark mb-3">
                                    <i class="fas fa-list-alt text-info me-1"></i> سجل طلبات وتقارير الأشعة والتصوير
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle table-bordered text-center small">
                                        <thead class="table-light">
                                            <tr>
                                                <th>الفحص الشعاعي</th>
                                                <th>الأولوية</th>
                                                <th>الحالة</th>
                                                <th>الاحتساب المالي (بضغطة زر)</th>
                                                <th>تقرير الأشعة والنتيجة</th>
                                                <th>تاريخ الطلب</th>
                                                <th>الملاحظات السريرية</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($radiologyOrders as $order)
                                                <tr>
                                                    <td class="fw-bold text-info text-start">
                                                        <i class="fas fa-x-ray me-1 text-secondary"></i>
                                                        {{ $order->item_name ?: $order->order_text }}
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $order->priority_badge_class }}">
                                                            {{ $order->priority_name }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge {{ $order->status_badge_class }}">
                                                            {{ $order->status_name }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $isBundledRad = (bool) ($order->is_bundled ?? false);
                                                            $origDispRad = (float) ($order->original_price_display ?? $order->original_price ?? $order->price ?? 0);
                                                            $billedDispRad = (float) ($order->billed_price ?? $order->price ?? 0);
                                                        @endphp
                                                        @if($isBundledRad)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle d-block mb-1">
                                                                0 د.ع (مشمول بالباقة)
                                                            </span>
                                                            @if($origDispRad > 0)
                                                                <small class="text-muted d-block mb-1 font-monospace" style="font-size:10px;">الأصلية: {{ number_format($origDispRad) }} د.ع</small>
                                                            @endif
                                                        @else
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle d-block mb-1 font-monospace">
                                                                {{ number_format($billedDispRad) }} د.ع (منفرد)
                                                            </span>
                                                        @endif
                                                        <form action="{{ route('cashier.ccu.orders.toggle-billing', $order->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <input type="hidden" name="billing_mode" value="{{ $isBundledRad ? 'extra' : 'bundled' }}">
                                                            <input type="hidden" name="_active_tab" value="radiologyTabPane">
                                                            <button type="submit" class="btn btn-xs {{ $isBundledRad ? 'btn-outline-warning text-dark' : 'btn-outline-success' }} py-0 px-2" style="font-size: 11px;">
                                                                <i class="fas fa-toggle-{{ $isBundledRad ? 'off' : 'on' }} me-1"></i>
                                                                {{ $isBundledRad ? 'احتساب منفرد' : 'شمول بالباقة (0)' }}
                                                            </button>
                                                        </form>
                                                    </td>
                                                    <td>
                                                        @if($order->result)
                                                            <div class="text-success text-start small" style="white-space: pre-line;">{{ $order->result }}</div>
                                                        @else
                                                            <span class="text-muted">بانتظار إجراء الفحص وكتابة التقرير</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-muted">
                                                        {{ $order->ordered_at ? $order->ordered_at->format('Y-m-d H:i') : $order->created_at->format('Y-m-d H:i') }}
                                                    </td>
                                                    <td class="small text-muted">{{ $order->instructions ?: '--' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-muted py-4">لا توجد طلبات أشعة مسجلة لهذا المريض بعد.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ================= تبويب 7: جدول إعطاء وجرعات الأدوية ومضخات التسريب الوريدي (MAR) ================= -->
                <div class="tab-pane fade {{ $currentActiveTab === 'medicationTabPane' ? 'show active' : '' }}" id="medicationTabPane">
                    <div class="row g-4">
                        @if($admission->status === 'active')
                        <!-- عمود إضافة دواء أو مضخة تسريب -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-sm">
                                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                    <span class="badge bg-warning text-dark p-2 rounded-circle fs-6">
                                        <i class="fas fa-pills"></i>
                                    </span>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark">جدولة دواء أو مضخة تسريب</h6>
                                        <small class="text-muted">نظام إعطاء الدواء التمريضي (MAR) بالساعة</small>
                                    </div>
                                </div>

                                <form action="{{ route('ccu.medication-schedules.store', $admission->id) }}" method="POST">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">نوع البند الدوائي:</label>
                                        <div class="d-flex gap-2">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="type" id="typeMed" value="medication" checked>
                                                <label class="form-check-label small" for="typeMed">علاج دوائي (Bolus)</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="type" id="typeInf" value="infusion">
                                                <label class="form-check-label small fw-bold text-danger" for="typeInf">مضخة تسريب مستمر</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">اسم الدواء أو المحلول:</label>
                                        <input type="text" name="title" class="form-control form-control-sm" placeholder="اكتب اسم الدواء أو اختر من القائمة..." list="ccuMedList" required>
                                        <datalist id="ccuMedList">
                                            @if(isset($medicines))
                                                @foreach($medicines as $med)
                                                    <option value="{{ $med->name }}">{{ $med->strength }} - {{ $med->dosage_form }}</option>
                                                @endforeach
                                            @endif
                                        </datalist>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">الجرعة / الكمية:</label>
                                            <input type="text" name="dose_or_amount" class="form-control form-control-sm" placeholder="مثال: 500 mg أو 1 vial أو 20 ml">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">مسار الإعطاء (Route):</label>
                                            <select name="route" class="form-select form-select-sm" required>
                                                <option value="حقن وريدي (IV)" selected>حقن وريدي (IV)</option>
                                                <option value="تسريب وريدي مستمر (IV Infusion)">تسريب وريدي مستمر</option>
                                                <option value="عضلي (IM)">حقن عضلي (IM)</option>
                                                <option value="فموي / رايل (NG Tube / PO)">فموي / رايل (NG Tube)</option>
                                                <option value="استنشاق رذاذ (Nebulizer)">استنشاق رذاذ (Nebulizer)</option>
                                                <option value="تحت الجلد (SC)">تحت الجلد (SC)</option>
                                                <option value="قطرات (Drops)">قطرات (Drops)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold text-primary">التكرار بالساعة:</label>
                                            <select name="interval_hours" class="form-select form-select-sm fw-bold border-primary-subtle" required>
                                                <option value="4">كل 4 ساعات (q4h)</option>
                                                <option value="6">كل 6 ساعات (q6h)</option>
                                                <option value="8" selected>كل 8 ساعات (q8h)</option>
                                                <option value="12">كل 12 ساعة (q12h)</option>
                                                <option value="24">مرة يومياً (q24h)</option>
                                                <option value="continuous">تسريب مستمر بالمضخة</option>
                                                <option value="prn">عند اللزوم (PRN)</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">معدل المضخة (إن وجد):</label>
                                            <input type="text" name="infusion_rate" class="form-control form-control-sm" placeholder="مثال: 10 ml/h أو 5 mcg/min">
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">توقيت أول جرعة:</label>
                                        <input type="datetime-local" name="start_time" class="form-control form-control-sm" value="{{ now()->format('Y-m-d\TH:i') }}">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small">تعليمات الطبيب وتوجيهات الإعطاء:</label>
                                        <textarea name="instructions" rows="2" class="form-control form-control-sm" placeholder="مثال: إعطاء ببطء على مدى 30 دقيقة، مراقبة ضغط الدم والنبض..."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-warning w-100 fw-bold shadow-sm py-2">
                                        <i class="fas fa-plus-circle me-1"></i> اعتماد جدول الدواء والتنبيهات
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endif

                        <!-- عمود الجداول الدوائية ومواعيد الإعطاء الحية للممرض -->
                        <div class="{{ $admission->status === 'active' ? 'col-xl-8 col-lg-7' : 'col-12' }}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fas fa-clipboard-list text-warning me-1"></i>
                                    سجل إعطاء الأدوية المعتمدة (MAR Flowsheet)
                                </h6>
                                <span class="badge bg-light text-dark border">
                                    إجمالي الأدوية المجدولة: {{ isset($medicationSchedules) ? $medicationSchedules->count() : 0 }}
                                </span>
                            </div>

                            @if(isset($medicationSchedules) && $medicationSchedules->isNotEmpty())
                                <div class="d-flex flex-column gap-3">
                                    @foreach($medicationSchedules as $sched)
                                        @php
                                            $isDue = $sched->is_due;
                                            $isOverdue = $sched->is_overdue;
                                            $isInfusion = $sched->type === 'infusion';
                                        @endphp
                                        <div class="card border-0 shadow-xs {{ !$sched->is_active ? 'opacity-50 bg-light' : ($isOverdue ? 'border-start border-danger border-4' : ($isDue ? 'border-start border-warning border-4' : 'border-start border-success border-4')) }}">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <div class="{{ $isInfusion ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning-emphasis' }} p-2 rounded-circle text-center" style="width: 42px; height: 42px; line-height: 26px;">
                                                            <i class="{{ $sched->icon }} fs-5"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="fw-bold text-dark mb-0">{{ $sched->title }}</h6>
                                                            <small class="text-muted">
                                                                <span class="badge {{ $isInfusion ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-dark' }} border me-1">
                                                                    {{ $sched->type_arabic }}
                                                                </span>
                                                                @if($sched->dose_or_amount)
                                                                    <strong class="text-dark">{{ $sched->dose_or_amount }}</strong> •
                                                                @endif
                                                                <span>{{ $sched->route }}</span>
                                                                @if($sched->infusion_rate)
                                                                    • <span class="badge bg-danger text-white">معدل: {{ $sched->infusion_rate }}</span>
                                                                @endif
                                                            </small>
                                                        </div>
                                                    </div>

                                                    <div class="text-end">
                                                        @if(!$sched->is_active)
                                                            <span class="badge bg-secondary">متوقف / ملغى</span>
                                                        @elseif($isOverdue)
                                                            <span class="badge bg-danger py-1 px-2">
                                                                <i class="fas fa-exclamation-circle me-1"></i> متأخر ({{ $sched->due_human }})
                                                            </span>
                                                        @elseif($isDue)
                                                            <span class="badge bg-warning text-dark py-1 px-2">
                                                                <i class="fas fa-bell me-1"></i> مستحق الآن
                                                            </span>
                                                        @else
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2">
                                                                <i class="fas fa-clock me-1"></i> القادم: {{ $sched->due_human }}
                                                            </span>
                                                        @endif
                                                        <div class="small text-muted font-monospace mt-1">
                                                            @if(is_numeric($sched->interval_hours))
                                                                كل {{ $sched->interval_hours }} ساعة
                                                            @else
                                                                {{ $sched->interval_hours }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                @if($sched->instructions)
                                                    <div class="bg-light p-2 rounded mb-2 small text-dark border">
                                                        <i class="fas fa-info-circle text-warning me-1"></i>
                                                        <strong>تعليمات الطبيب:</strong> {{ $sched->instructions }}
                                                    </div>
                                                @endif

                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 border-top">
                                                    <small class="text-muted" style="font-size: 11px;">
                                                        <i class="fas fa-calendar-alt me-1"></i>
                                                        الموعد القادم: <strong>{{ $sched->next_due_at ? $sched->next_due_at->format('Y-m-d H:i') : '—' }}</strong>
                                                        @if($sched->last_administered_at)
                                                            • آخر إعطاء: {{ $sched->last_administered_at->format('H:i') }} (بواسطة {{ $sched->administeredBy?->name ?? 'التمريض' }})
                                                        @endif
                                                    </small>

                                                    @if($admission->status === 'active')
                                                    <div class="d-flex gap-1 align-items-center">
                                                        @if($sched->is_active)
                                                            <form action="{{ route('ccu.medication-schedules.administer-quick', $sched->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm btn-success fw-bold px-3 py-1 shadow-xs" title="توثيق الإعطاء الآن">
                                                                    <i class="fas fa-check-circle me-1"></i> توثيق الإعطاء الآن
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <form action="{{ route('ccu.medication-schedules.toggle', $sched->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary px-2 py-1" title="{{ $sched->is_active ? 'إيقاف مؤقت' : 'إعادة التفعيل' }}">
                                                                <i class="fas {{ $sched->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                                            </button>
                                                        </form>

                                                        <form action="{{ route('ccu.medication-schedules.destroy', $sched->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا العلاج؟');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="حذف">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="card border-0 bg-light p-4 text-center text-muted rounded-3">
                                    <i class="fas fa-pills fa-3x mb-2 text-warning opacity-50"></i>
                                    <h6>لا توجد أدوية أو مضخات تسريب مجدولة حتى الآن</h6>
                                    <p class="small mb-0">يمكن إضافة خطة العلاج الدوائي ومضخات التسريب المستمر من النموذج الجانبي لتظهر للممرض مع مواعيد الإعطاء والتنبيهات الذكية.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- ================= تبويب 8: استشارات الأطباء التخصصية ================= -->
                <div class="tab-pane fade {{ $currentActiveTab === 'consultationsTabPane' ? 'show active' : '' }}" id="consultationsTabPane">
                    <div class="row g-4">
                        @if($admission->status === 'active')
                        <!-- عمود طلب استشارة طبية -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-sm">
                                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                    <span class="badge bg-primary text-white p-2 rounded-circle fs-6">
                                        <i class="fas fa-user-md"></i>
                                    </span>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark">طلب استشارة طبية تخصصية</h6>
                                        <small class="text-muted">استشارة سريرية داخلية أو خارجية</small>
                                    </div>
                                </div>

                                <form action="{{ route('ccu.consultations.store', $admission->id) }}" method="POST">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">نوع الاستشارة:</label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="consultation_type" id="ccuConsInternal" value="internal" checked onchange="document.getElementById('ccuInternalDocGroup').classList.remove('d-none'); document.getElementById('ccuExternalDocGroup').classList.add('d-none');">
                                                <label class="form-check-label small" for="ccuConsInternal">داخلية (طبيب المستشفى)</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="consultation_type" id="ccuConsExternal" value="external" onchange="document.getElementById('ccuInternalDocGroup').classList.add('d-none'); document.getElementById('ccuExternalDocGroup').classList.remove('d-none');">
                                                <label class="form-check-label small" for="ccuConsExternal">استشارة خارجية</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">التخصص الطبي المطلوب:</label>
                                        <input type="text" name="specialty" class="form-control form-control-sm" placeholder="مثال: باطنية وقلبية، جراحة عامة، كلى وغسيل، أعصاب..." list="ccuSpecialtiesList" required>
                                        <datalist id="ccuSpecialtiesList">
                                            <option value="باطنية وقلبية"></option>
                                            <option value="جراحة عامة"></option>
                                            <option value="جراحة جملة عصبية"></option>
                                            <option value="أمراض كلى وغسيل دموي"></option>
                                            <option value="أمراض صدرية وجهاز تنفسي"></option>
                                            <option value="تخدير وعناية مركزة"></option>
                                            <option value="جراحة عظام ومفاصل"></option>
                                            <option value="جراحة أوعية دموية"></option>
                                            <option value="غدد صماء وسكري"></option>
                                        </datalist>
                                    </div>

                                    <div class="mb-2" id="ccuInternalDocGroup">
                                        <label class="form-label small fw-bold">اختر الطبيب الاستشاري:</label>
                                        <select name="doctor_id" class="form-select form-select-sm">
                                            <option value="">-- اختر الاستشاري الداخلي --</option>
                                            @if(isset($activeDoctors))
                                                @foreach($activeDoctors as $doc)
                                                    <option value="{{ $doc->id }}">د. {{ $doc->user?->name }} ({{ $doc->specialization }})</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="mb-2 d-none" id="ccuExternalDocGroup">
                                        <label class="form-label small fw-bold">اسم الطبيب الاستشاري الخارجي:</label>
                                        <input type="text" name="external_doctor_name" class="form-control form-control-sm" placeholder="اكتب اسم الاستشاري الزائر والمستشفى...">
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">الأولوية:</label>
                                            <select name="priority" class="form-select form-select-sm fw-bold">
                                                <option value="urgent" selected>⚡ عاجل (Urgent)</option>
                                                <option value="stat">🚨 STAT فوري وحرج</option>
                                                <option value="normal">اعتيادي (Routine)</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">أجور الاستشارة (د.ع):</label>
                                            <input type="number" name="fee" class="form-control form-control-sm" placeholder="25000">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">دواعي الاستشارة والأسئلة السريرية:</label>
                                        <textarea name="reason" rows="3" class="form-control form-control-sm" required placeholder="بيان الوضع السريري للمريض والأسئلة الموجهة للاستشاري (مثل: تقييم جاهزية الجراحة، ضبط جرعات أدوية القلب، الشك بجلطة)..."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm py-2">
                                        <i class="fas fa-paper-plane me-1"></i> إرسال طلب الاستشارة
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endif

                        <!-- عمود سجل وتقارير الاستشارات -->
                        <div class="{{ $admission->status === 'active' ? 'col-xl-8 col-lg-7' : 'col-12' }}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fas fa-stethoscope text-primary me-1"></i>
                                    سجل استشارات أطباء الاختصاص
                                </h6>
                                <span class="badge bg-light text-dark border">
                                    إجمالي الاستشارات: {{ isset($consultations) ? $consultations->count() : 0 }}
                                </span>
                            </div>

                            @if(isset($consultations) && $consultations->isNotEmpty())
                                <div class="d-flex flex-column gap-3">
                                    @foreach($consultations as $cons)
                                        <div class="card border-0 shadow-xs {{ $cons->status === 'completed' ? 'border-start border-success border-4' : 'border-start border-warning border-4' }}">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2 pb-2 border-bottom">
                                                    <div>
                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                            <span class="badge {{ $cons->status_badge_class }}">{{ $cons->status_name }}</span>
                                                            <span class="badge {{ $cons->priority_badge_class }}">{{ $cons->priority_name }}</span>
                                                            <strong class="text-dark">{{ $cons->specialty }}</strong>
                                                        </div>
                                                        <small class="text-muted">
                                                            <i class="fas fa-user-md me-1 text-primary"></i>
                                                            الاستشاري المطلوب: <strong>{{ $cons->consultant_display_name }}</strong>
                                                            • بواسطة: {{ $cons->requestedByUser?->name ?? 'طبيب العناية' }}
                                                            • {{ $cons->created_at->format('Y-m-d H:i') }}
                                                        </small>
                                                    </div>
                                                    @if(($cons->original_fee ?? $cons->fee) > 0)
                                                        <div class="d-flex align-items-center gap-2">
                                                            @if($cons->is_bundled ?? false)
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle">0 د.ع (مشمول) • الأصلية: {{ number_format($cons->original_fee ?: $cons->fee) }} د.ع</span>
                                                            @else
                                                                <span class="badge bg-light text-dark border font-monospace">الأجور: {{ number_format($cons->billed_fee ?? $cons->fee) }} د.ع</span>
                                                            @endif
                                                            <form action="{{ route('cashier.ccu.consultations.toggle-billing', $cons->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                <input type="hidden" name="billing_mode" value="{{ ($cons->is_bundled ?? false) ? 'extra' : 'bundled' }}">
                                                                <input type="hidden" name="_active_tab" value="consultationsTabPane">
                                                                <button type="submit" class="btn btn-xs {{ ($cons->is_bundled ?? false) ? 'btn-outline-warning text-dark' : 'btn-outline-success' }} py-0 px-2" style="font-size: 11px;">
                                                                    {{ ($cons->is_bundled ?? false) ? 'احتساب منفرد' : 'شمول بالباقة' }}
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="mb-3">
                                                    <span class="small fw-bold text-dark d-block mb-1">دواعي الاستشارة:</span>
                                                    <div class="p-2.5 rounded bg-light small text-dark border" style="white-space: pre-line;">{{ $cons->reason }}</div>
                                                </div>

                                                @if($cons->status === 'completed' && $cons->response)
                                                    <div class="p-3 rounded-3 bg-success bg-opacity-10 border border-success-subtle">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <span class="small fw-bold text-success">
                                                                <i class="fas fa-check-circle me-1"></i> رأي وتوصيات الطبيب الاستشاري:
                                                            </span>
                                                            <small class="text-muted" style="font-size: 11px;">
                                                                <i class="fas fa-clock me-1"></i> {{ $cons->responded_at ? $cons->responded_at->format('Y-m-d H:i') : '' }}
                                                                (د. {{ $cons->respondedByUser?->name ?? $cons->consultant_display_name }})
                                                            </small>
                                                        </div>
                                                        <div class="text-dark fw-bold small" style="white-space: pre-line;">{{ $cons->response }}</div>
                                                    </div>
                                                @elseif($admission->status === 'active')
                                                    <!-- نموذج تدوين رد الاستشاري -->
                                                    <div class="mt-2 pt-2 border-top">
                                                        <button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="collapse" data-bs-target="#replyCons{{ $cons->id }}">
                                                            <i class="fas fa-pen me-1"></i> تدوين رد وتوصيات الاستشاري الآن
                                                        </button>
                                                        <div class="collapse mt-2" id="replyCons{{ $cons->id }}">
                                                            <form action="{{ route('ccu.consultations.respond', $cons->id) }}" method="POST">
                                                                @csrf
                                                                <div class="mb-2">
                                                                    <textarea name="response" rows="3" class="form-control form-control-sm" required placeholder="اكتب التقرير الطبي وتوصيات الاستشاري والخطة المقترحة لمريض العناية..."></textarea>
                                                                </div>
                                                                <button type="submit" class="btn btn-success btn-sm fw-bold">
                                                                    <i class="fas fa-check me-1"></i> اعتماد التقرير الطبي
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="card border-0 bg-light p-4 text-center text-muted rounded-3">
                                    <i class="fas fa-stethoscope fa-3x mb-2 text-primary opacity-50"></i>
                                    <h6>لا توجد طلبات استشارات مسجلة لهذا المريض حتى الآن</h6>
                                    <p class="small mb-0">يمكن طلب استشارة داخلية أو خارجية من أطباء التخصصات الأخرى لدعم الخطة العلاجية.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- ================= تبويب 9: الأرشيف والمرفقات الطبية وتخطيط القلب (ECG) ================= -->
                <div class="tab-pane fade {{ $currentActiveTab === 'attachmentsTabPane' ? 'show active' : '' }}" id="attachmentsTabPane">
                    <div class="row g-4">
                        @if($admission->status === 'active')
                        <!-- عمود رفع مرفق طبي -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card border-0 bg-light p-3 rounded-3 shadow-sm">
                                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                                    <span class="badge bg-secondary text-white p-2 rounded-circle fs-6">
                                        <i class="fas fa-paperclip"></i>
                                    </span>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark">رفع مرفق أو تخطيط قلب (ECG)</h6>
                                        <small class="text-muted">صور التخطيط، تقارير خارجية، كتب إحالة</small>
                                    </div>
                                </div>

                                <form action="{{ route('ccu.attachments.store', $admission->id) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">نوع المرفق الطبي:</label>
                                        <select name="attachment_type" class="form-select form-select-sm" required>
                                            <option value="ecg" selected>❤️ تخطيط قلب كهربائي (ECG / EKG)</option>
                                            <option value="report">📄 تقرير طبي سريري</option>
                                            <option value="imaging">🩻 أشعة / مفراس / رنين خارجي</option>
                                            <option value="lab_external">🧪 تحليل مختبري خارجي</option>
                                            <option value="referral">🚑 كتاب إحالة / نقل مريض</option>
                                            <option value="other">📁 مستند طبي آخر</option>
                                        </select>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">عنوان / وصف المرفق:</label>
                                        <input type="text" name="title" class="form-control form-control-sm" placeholder="مثال: تخطيط قلب 12-Lead عند الدخول، تقرير مفراس الدماغ..." required>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold">الملف (PDF أو صور):</label>
                                        <input type="file" name="file" class="form-control form-control-sm" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                                        <small class="text-muted d-block" style="font-size: 11px;">الحد الأقصى للملف: 25 ميغابايت</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small">ملاحظات توضيحية:</label>
                                        <textarea name="notes" rows="2" class="form-control form-control-sm" placeholder="أي تفاصيل حول مصدر المرفق أو النتائج البارزة..."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-secondary text-white w-100 fw-bold shadow-sm py-2">
                                        <i class="fas fa-upload me-1"></i> رفع وحفظ في الأرشيف
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endif

                        <!-- عمود عرض المستندات والمرفقات -->
                        <div class="{{ $admission->status === 'active' ? 'col-xl-8 col-lg-7' : 'col-12' }}">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fas fa-folder-open text-secondary me-1"></i>
                                    أرشيف المستندات والمرفقات الطبية للمريض
                                </h6>
                                <span class="badge bg-light text-dark border">
                                    إجمالي المرفقات: {{ isset($attachments) ? $attachments->count() : 0 }}
                                </span>
                            </div>

                            @if(isset($attachments) && $attachments->isNotEmpty())
                                <div class="row g-3">
                                    @foreach($attachments as $att)
                                        <div class="col-md-6">
                                            <div class="card border-0 shadow-xs h-100 p-3 rounded-3 bg-white border">
                                                <div class="d-flex align-items-start gap-2 mb-2">
                                                    <div class="p-2 rounded bg-light text-center" style="width: 40px; height: 40px;">
                                                        <i class="{{ $att->type_icon }} fs-5"></i>
                                                    </div>
                                                    <div class="flex-grow-1 overflow-hidden">
                                                        <h6 class="fw-bold text-dark mb-0 text-truncate" title="{{ $att->title }}">{{ $att->title }}</h6>
                                                        <small class="text-muted d-block">
                                                            <span class="badge bg-light text-dark border">{{ $att->type_name }}</span>
                                                            @if($att->file_size)
                                                                <span>{{ round($att->file_size / 1024, 1) }} KB</span>
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>

                                                @if($att->notes)
                                                    <p class="small text-muted mb-2 bg-light p-2 rounded" style="font-size: 11px;">{{ $att->notes }}</p>
                                                @endif

                                                <div class="d-flex justify-content-between align-items-center pt-2 mt-auto border-top">
                                                    <small class="text-muted" style="font-size: 10px;">
                                                        <i class="fas fa-clock me-1"></i> {{ $att->created_at->format('Y-m-d H:i') }}
                                                        ({{ $att->uploader?->name ?? 'الكادر' }})
                                                    </small>
                                                    <div class="d-flex gap-1">
                                                        <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank" class="btn btn-xs btn-outline-primary py-1 px-2" title="فتح ومعاينة">
                                                            <i class="fas fa-eye me-1"></i> عرض
                                                        </a>
                                                        @if($admission->status === 'active')
                                                        <form action="{{ route('ccu.attachments.destroy', $att->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المرفق؟');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-xs btn-outline-danger py-1 px-2" title="حذف">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="card border-0 bg-light p-4 text-center text-muted rounded-3">
                                    <i class="fas fa-folder fa-3x mb-2 text-secondary opacity-50"></i>
                                    <h6>لا توجد مرفقات أو تقارير طبية مرفوعة حتى الآن</h6>
                                    <p class="small mb-0">يمكن رفع تخطيط القلب ECG والتقارير الطبية الخارجية وصور الأشعة لحفظها في الأرشيف الدائم للمريض.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- مودال إضافة علامات حيوية -->
<div class="modal fade" id="addVitalsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('icu.vitals.store', $admission->id) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fs-6">تدوين علامات حيوية ومعايير جهاز التنفس</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">وقت القراءة:</label>
                            <input type="datetime-local" name="recorded_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-danger">مقياس الغيبوبة GCS (3-15):</label>
                            <input type="number" name="gcs" class="form-control" min="3" max="15" placeholder="مثال: 14">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">النبض HR (نبضة/د):</label>
                            <input type="number" name="heart_rate" class="form-control" placeholder="مثال: 85">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">الضغط العالي SBP:</label>
                            <input type="number" name="systolic_bp" class="form-control" placeholder="120">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">الضغط الواطئ DBP:</label>
                            <input type="number" name="diastolic_bp" class="form-control" placeholder="80">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">الضغط الوسطي MAP:</label>
                            <input type="number" name="mean_arterial_pressure" class="form-control" placeholder="يحسب تلقائياً">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">الأكسجين SpO2 (%):</label>
                            <input type="number" name="spo2" class="form-control" placeholder="98">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">معدل التنفس RR:</label>
                            <input type="number" name="respiratory_rate" class="form-control" placeholder="18">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">الحرارة (°C):</label>
                            <input type="number" step="0.1" name="temperature" class="form-control" placeholder="37.0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">سكر الدم (mg/dL):</label>
                            <input type="number" name="blood_sugar" class="form-control" placeholder="120">
                        </div>
                        
                        <!-- إعدادات جهاز التنفس -->
                        <div class="col-12"><hr class="my-1"><h6 class="text-primary fw-bold small">إعدادات دعم التنفس والأكسجين:</h6></div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">مصدر / جهاز الأكسجين:</label>
                            <select name="oxygen_device" class="form-select">
                                <option value="Room Air">هواء الغرفة الطبيعي (Room Air)</option>
                                <option value="Nasal Cannula">قنية أنفية (Nasal Cannula)</option>
                                <option value="Venturi Mask">قناع فنتوري (Venturi Mask)</option>
                                <option value="Non-Rebreather Mask">قناع خزان (NRB Mask)</option>
                                <option value="Mechanical Ventilator">جهاز تنفس اصطناعي (Mechanical Ventilator)</option>
                                <option value="BiPAP / CPAP">تنفس غير غازي (BiPAP / CPAP)</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">وضع التنفس (Mode):</label>
                            <input type="text" name="ventilator_mode" class="form-control" placeholder="مثال: SIMV, AC/VC">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">نسبة الأكسجين FiO2 (%):</label>
                            <input type="number" name="fio2" class="form-control" placeholder="مثال: 40">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">PEEP:</label>
                            <input type="number" step="0.5" name="peep" class="form-control" placeholder="5.0">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">ملاحظات سريرية:</label>
                            <input type="text" name="notes" class="form-control" placeholder="أي ملاحظات حول استجابة المريض أو تغيير وضعية الأنبوب...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger btn-sm">حفظ وتوثيق القراءة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال إضافة قراءة سوائل -->
<div class="modal fade" id="addFluidModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('icu.fluid-balance.store', $admission->id) }}">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title fs-6">تسجيل قراءة ميزان السوائل بالساعة (Intake / Output)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">وقت القراءة:</label>
                            <input type="datetime-local" name="recorded_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>

                        <div class="col-12"><h6 class="text-primary fw-bold small mb-1">السوائل الداخلة (Intake - مل):</h6></div>
                        <div class="col-md-3">
                            <label class="form-label small">محاليل وريدية IV:</label>
                            <input type="number" name="intake_iv_fluids" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">دم ومشتقاته:</label>
                            <input type="number" name="intake_blood_products" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">تغذية أنبوبية Enteral:</label>
                            <input type="number" name="intake_enteral_feeding" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">علاجات ومضخات:</label>
                            <input type="number" name="intake_medications" class="form-control" placeholder="0">
                        </div>

                        <div class="col-12"><h6 class="text-danger fw-bold small mb-1">السوائل الخارجة (Output - مل):</h6></div>
                        <div class="col-md-3">
                            <label class="form-label small">كمية البول بالساعة:</label>
                            <input type="number" name="output_urine" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">مفارغ جراحية Drains:</label>
                            <input type="number" name="output_drains" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">سحب رايل NG Tube:</label>
                            <input type="number" name="output_nasogastric" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">مفرزات أخرى / قيء:</label>
                            <input type="number" name="output_other" class="form-control" placeholder="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label small">ملاحظات ميزان السوائل:</label>
                            <input type="text" name="notes" class="form-control" placeholder="ملاحظات حول لون البول أو لزوجة المفرزات...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info text-white btn-sm">حفظ واحتساب الصافي</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال إضافة أمر طبيب -->
<div class="modal fade" id="addOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('icu.orders.store', $admission->id) }}">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6">إصدار أمر طبيب سريري جديد</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">درجة الأولوية والخطورة:</label>
                        <select name="priority" class="form-select" required>
                            <option value="routine">اعتيادي (Routine)</option>
                            <option value="urgent">⚡ عاجل (Urgent)</option>
                            <option value="stat">🚨 STAT (فوري وحرج جداً)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">نوع الأمر:</label>
                        <select name="order_type" class="form-select" required>
                            <option value="medication">علاج / دواء ومضخة وريدية</option>
                            <option value="investigation">تحاليل مختبرية أو أشعة مستعجلة</option>
                            <option value="ventilator_setting">تعديل معايير جهاز التنفس</option>
                            <option value="nursing_care">عناية تمريضية خاصة</option>
                            <option value="diet">حمية وتغذية أنبوبية</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">نص الأمر الطبي:</label>
                        <textarea name="order_text" class="form-control" rows="3" required placeholder="اكتب الأمر الطبي والجرعة وتوقيت التطبيق..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm">إصدار الأمر</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال إضافة مهمة رعاية تمريضية -->
<div class="modal fade" id="addCareScheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('icu.care-schedules.store', $admission->id) }}">
                @csrf
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fs-6">إضافة مهمة رعاية تمريضية مجدولة</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">مهمة الرعاية:</label>
                        <input type="text" name="care_type" class="form-control" required placeholder="مثال: تقليب المريض على الجانب الأيمن، سحب مفرزات ETT...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">التوقيت المجدول:</label>
                        <input type="text" name="scheduled_time" class="form-control" required placeholder="مثال: 10:00 صباحاً">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ملاحظات إضافية:</label>
                        <input type="text" name="notes" class="form-control" placeholder="تعليمات خاصة...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success btn-sm">حفظ المهمة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال تخريج المريض -->
<div class="modal fade" id="dischargeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('icu.admissions.discharge', $admission->id) }}">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fs-6">تخريج مريض العناية المركزة</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">وجهة التحويل / الخروج:</label>
                        <select name="discharge_destination" class="form-select" required>
                            <option value="جناح الرقود العادي (Step-down Ward)">تحويل إلى جناح الرقود العادي (Step-down)</option>
                            <option value="خروج إلى المنزل بعد استقرار الحالة">خروج إلى المنزل بعد الشفاء والاستقرار</option>
                            <option value="تحويل إلى مركز تخصصي خارجي">تحويل إلى مركز تخصصي خارجي</option>
                            <option value="وفاة (رحمه الله)">وفاة المريض (رحمه الله)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ملخص حالة المريض وتوصيات الخروج:</label>
                        <textarea name="discharge_summary" class="form-control" rows="3" required placeholder="اكتب ملخص الخطة العلاجية وتوصيات المتابعة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger btn-sm">تأكيد التخريج</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال نقل لسرير آخر -->
<div class="modal fade" id="transferBedModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('icu.admissions.transfer-bed', $admission->id) }}">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6">نقل المريض إلى سرير عناية آخر</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="mb-2">السرير الحالي: <strong>#{{ $admission->bed?->bed_number }}</strong> ({{ $admission->bed?->type_name }})</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">اختر السرير الجديد المتاح:</label>
                        <select name="new_bed_id" class="form-select" required>
                            @php
                                $availableBeds = \App\Models\IcuBed::available()->where('id', '!=', $admission->icu_bed_id)->orderByNumber()->get();
                            @endphp
                            @foreach($availableBeds as $abed)
                                <option value="{{ $abed->id }}">سرير #{{ $abed->bed_number }} - {{ $abed->type_name }} ({{ $abed->room_number }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="alert alert-info py-2 small mb-0">
                        سيتحول السرير القديم فورياً إلى مرحلة التعقيم والتطهير.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary btn-sm">تأكيد النقل</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const admissionId = {{ $admission->id }};
    const scrollKey = 'icu_scroll_' + admissionId;
    const tabKey = 'icu_tab_' + admissionId;

    // استعادة موضع التمرير بدقة عند إعادة تحميل الصفحة أو الحفظ
    try {
        const savedScroll = sessionStorage.getItem(scrollKey);
        if (savedScroll !== null) {
            sessionStorage.removeItem(scrollKey);
            const targetY = parseInt(savedScroll, 10);
            if (!isNaN(targetY) && targetY > 0) {
                window.scrollTo({ top: targetY, behavior: 'instant' });
                requestAnimationFrame(function() {
                    window.scrollTo({ top: targetY, behavior: 'instant' });
                });
                setTimeout(function() {
                    window.scrollTo({ top: targetY, behavior: 'instant' });
                }, 100);
            }
        }
    } catch (e) {}

    // استخراج معرف التبويب النشط حالياً
    function getCurrentActiveTab() {
        const activeTabBtn = document.querySelector('#icuTabs button.nav-link.active, #icuTabs a.nav-link.active');
        if (activeTabBtn) {
            const target = activeTabBtn.getAttribute('data-bs-target') || activeTabBtn.getAttribute('href');
            if (target) return target.replace('#', '');
        }
        return '{{ $currentActiveTab }}' || 'vitalsTabPane';
    }

    // حفظ التبويب النشط وتحديث رابط الصفحة
    document.querySelectorAll('#icuTabs [data-bs-toggle="tab"]').forEach(function(tabBtn) {
        tabBtn.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target') || e.target.getAttribute('href');
            if (target) {
                const tabId = target.replace('#', '');
                try {
                    sessionStorage.setItem(tabKey, tabId);
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState(null, null, '#' + tabId);
                    }
                } catch (err) {}
            }
        });
    });

    // تفعيل التبويب المذكور في رابط الـ Hash إن وُجد
    if (window.location.hash) {
        const hashTarget = window.location.hash.replace('#', '');
        const targetBtn = document.querySelector(`#icuTabs [data-bs-target="#${hashTarget}"], #icuTabs [href="#${hashTarget}"]`);
        if (targetBtn && !targetBtn.classList.contains('active')) {
            const bsTab = bootstrap.Tab.getOrCreateInstance(targetBtn);
            if (bsTab) bsTab.show();
        }
    }

    // رصد أي نقرة على أزرار الحفظ والإرسال لتسجيل موضع التمرير فوراً
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('button[type="submit"], input[type="submit"], .btn-submit-action');
        if (btn) {
            try {
                sessionStorage.setItem(scrollKey, window.scrollY || window.pageYOffset || 0);
            } catch (err) {}
        }
    }, true);

    // اعتراض إرسال أي نموذج وتمرير _active_tab وتثبيت موضع التمرير
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        try {
            sessionStorage.setItem(scrollKey, window.scrollY || window.pageYOffset || 0);
        } catch (err) {}

        const activeTab = getCurrentActiveTab();
        let tabInput = form.querySelector('input[name="_active_tab"]');
        if (!tabInput) {
            tabInput = document.createElement('input');
            tabInput.type = 'hidden';
            tabInput.name = '_active_tab';
            form.appendChild(tabInput);
        }
        tabInput.value = activeTab;
    }, true);

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ================= 1. منظومة حجز تحاليل المختبر السريعة والذكية =================
    window.icuLabTests = @json($labTests ?? []);
    
    function setupIcuLabOrder() {
        const searchInput = document.getElementById('icuLabSearchInput');
        const menu = document.getElementById('icuLabAutocompleteMenu');
        const tray = document.getElementById('icuLabSelectedTray');
        const container = document.getElementById('icuLabChipsContainer');
        const counterSpans = document.querySelectorAll('.icu-lab-selected-count');
        const clearAllBtn = document.getElementById('icuLabClearAllBtn');
        const form = document.getElementById('icuLabOrderForm');
        const quickBtns = document.querySelectorAll('.icu-quick-lab-btn');

        if (!form || !searchInput) return;

        const selectedTests = new Map();
        const customTests = new Set();

        function updateCounter() {
            const total = selectedTests.size + customTests.size;
            counterSpans.forEach(el => el.textContent = total);
            if (total > 0) {
                tray.classList.remove('d-none');
            } else {
                tray.classList.add('d-none');
            }
        }

        function renderChips() {
            container.innerHTML = '';

            selectedTests.forEach((test, id) => {
                const chip = document.createElement('span');
                chip.className = 'badge bg-white text-dark border border-primary p-2 d-inline-flex align-items-center gap-2 shadow-sm rounded-pill';
                chip.innerHTML = `
                    <i class="fas fa-vial text-primary"></i>
                    <span class="fw-bold text-dark">${escapeHtml(test.name)}</span>
                    ${test.code ? `<span class="badge bg-light text-muted border">${escapeHtml(test.code)}</span>` : ''}
                    <input type="hidden" name="lab_test_ids[]" value="${test.id}">
                    <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" aria-label="إزالة"></button>
                `;
                chip.querySelector('.btn-close').addEventListener('click', () => {
                    selectedTests.delete(id);
                    renderChips();
                });
                container.appendChild(chip);
            });

            customTests.forEach((name) => {
                const chip = document.createElement('span');
                chip.className = 'badge bg-white text-dark border border-warning p-2 d-inline-flex align-items-center gap-2 shadow-sm rounded-pill';
                chip.innerHTML = `
                    <i class="fas fa-flask text-warning"></i>
                    <span class="fw-bold text-dark">${escapeHtml(name)}</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis border" style="font-size: 10px;">مخصص</span>
                    <input type="hidden" name="custom_tests[]" value="${escapeHtml(name)}">
                    <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" aria-label="إزالة"></button>
                `;
                chip.querySelector('.btn-close').addEventListener('click', () => {
                    customTests.delete(name);
                    renderChips();
                });
                container.appendChild(chip);
            });

            updateCounter();
        }

        function addTest(test) {
            if (!test) return;
            selectedTests.set(test.id, test);
            renderChips();
        }

        function addCustomTest(name) {
            const cleanName = (name || '').trim();
            if (!cleanName) return;
            const lower = cleanName.toLowerCase();
            const matched = window.icuLabTests.find(t => (t.name && t.name.toLowerCase() === lower) || (t.code && t.code.toLowerCase() === lower));
            if (matched) {
                addTest(matched);
            } else {
                customTests.add(cleanName);
                renderChips();
            }
        }

        function showResults(query) {
            const q = (query || '').trim().toLowerCase();
            menu.innerHTML = '';

            let results = [];
            if (q.length > 0) {
                results = window.icuLabTests.filter(t => 
                    (t.name && t.name.toLowerCase().includes(q)) || 
                    (t.code && t.code.toLowerCase().includes(q)) ||
                    (t.category && t.category.toLowerCase().includes(q))
                ).slice(0, 15);
            } else {
                results = window.icuLabTests.slice(0, 15);
            }

            if (q.length > 0) {
                const customItem = document.createElement('a');
                customItem.href = 'javascript:void(0);';
                customItem.className = 'dropdown-item d-flex justify-content-between align-items-center py-2 bg-light border-bottom text-primary fw-bold';
                customItem.innerHTML = `<span><i class="fas fa-plus-circle me-1"></i> إضافة "<strong>${escapeHtml(query.trim())}</strong>" كتحليل مخصص</span><span class="badge bg-primary text-white">إضافة</span>`;
                customItem.addEventListener('click', () => {
                    addCustomTest(query.trim());
                    searchInput.value = '';
                    menu.style.display = 'none';
                });
                menu.appendChild(customItem);
            }

            if (results.length === 0 && q.length === 0) {
                menu.style.display = 'none';
                return;
            }

            results.forEach(test => {
                const item = document.createElement('a');
                item.href = 'javascript:void(0);';
                item.className = 'dropdown-item d-flex justify-content-between align-items-center py-2';
                item.innerHTML = `
                    <div>
                        <span class="fw-bold text-dark d-block">${escapeHtml(test.name)}</span>
                        <small class="text-muted">${test.code ? '[' + escapeHtml(test.code) + '] ' : ''}${test.category ? escapeHtml(test.category) : ''}</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border">اختيار</span>
                `;
                item.addEventListener('click', () => {
                    addTest(test);
                    searchInput.value = '';
                    menu.style.display = 'none';
                });
                menu.appendChild(item);
            });

            menu.style.display = 'block';
        }

        searchInput.addEventListener('input', () => {
            showResults(searchInput.value);
        });

        searchInput.addEventListener('focus', () => {
            showResults(searchInput.value);
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = searchInput.value.trim();
                if (val) {
                    const q = val.toLowerCase();
                    const matched = window.icuLabTests.find(t => (t.name && t.name.toLowerCase() === q) || (t.code && t.code.toLowerCase() === q));
                    if (matched) {
                        addTest(matched);
                    } else {
                        addCustomTest(val);
                    }
                    searchInput.value = '';
                    menu.style.display = 'none';
                }
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#icuLabSearchInput') && !e.target.closest('#icuLabAutocompleteMenu')) {
                menu.style.display = 'none';
            }
        });

        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', () => {
                selectedTests.clear();
                customTests.clear();
                renderChips();
            });
        }

        quickBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                let exams = [];
                try {
                    exams = JSON.parse(btn.getAttribute('data-tests') || '[]');
                } catch (e) {
                    exams = [btn.textContent.trim()];
                }

                let found = false;
                for (const alias of exams) {
                    const lower = alias.toLowerCase();
                    const m = window.icuLabTests.find(t => 
                        (t.name && t.name.toLowerCase().includes(lower)) || 
                        (t.code && t.code.toLowerCase().includes(lower))
                    );
                    if (m) {
                        addTest(m);
                        found = true;
                        break;
                    }
                }
                if (!found && exams.length > 0) {
                    addCustomTest(exams[0]);
                }
            });
        });

        form.addEventListener('submit', (e) => {
            if (selectedTests.size === 0 && customTests.size === 0) {
                e.preventDefault();
                alert('يرجى اختيار فحص مخبري واحد على الأقل قبل الإرسال.');
                searchInput.focus();
            }
        });
    }

    // ================= 2. منظومة حجز الأشعة والتصوير الطبي =================
    window.icuRadiologyTypes = @json($radiologyTypes ?? []);

    function setupIcuRadiologyOrder() {
        const searchInput = document.getElementById('icuRadSearchInput');
        const menu = document.getElementById('icuRadAutocompleteMenu');
        const tray = document.getElementById('icuRadSelectedTray');
        const container = document.getElementById('icuRadChipsContainer');
        const counterSpans = document.querySelectorAll('.icu-rad-selected-count');
        const clearAllBtn = document.getElementById('icuRadClearAllBtn');
        const form = document.getElementById('icuRadOrderForm');
        const quickBtns = document.querySelectorAll('.icu-quick-rad-btn');

        if (!form || !searchInput) return;

        const selectedRads = new Map();
        const customRads = new Set();

        function updateCounter() {
            const total = selectedRads.size + customRads.size;
            counterSpans.forEach(el => el.textContent = total);
            if (total > 0) {
                tray.classList.remove('d-none');
            } else {
                tray.classList.add('d-none');
            }
        }

        function renderChips() {
            container.innerHTML = '';

            selectedRads.forEach((rad, id) => {
                const chip = document.createElement('span');
                chip.className = 'badge bg-white text-dark border border-info p-2 d-inline-flex align-items-center gap-2 shadow-sm rounded-pill';
                chip.innerHTML = `
                    <i class="fas fa-x-ray text-info"></i>
                    <span class="fw-bold text-dark">${escapeHtml(rad.name)}</span>
                    ${rad.code ? `<span class="badge bg-light text-muted border">${escapeHtml(rad.code)}</span>` : ''}
                    <input type="hidden" name="radiology_type_ids[]" value="${rad.id}">
                    <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" aria-label="إزالة"></button>
                `;
                chip.querySelector('.btn-close').addEventListener('click', () => {
                    selectedRads.delete(id);
                    renderChips();
                });
                container.appendChild(chip);
            });

            customRads.forEach((name) => {
                const chip = document.createElement('span');
                chip.className = 'badge bg-white text-dark border border-warning p-2 d-inline-flex align-items-center gap-2 shadow-sm rounded-pill';
                chip.innerHTML = `
                    <i class="fas fa-radiation text-warning"></i>
                    <span class="fw-bold text-dark">${escapeHtml(name)}</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis border" style="font-size: 10px;">مخصص</span>
                    <input type="hidden" name="custom_radiology[]" value="${escapeHtml(name)}">
                    <button type="button" class="btn-close btn-close-sm ms-1" style="font-size: 0.6rem;" aria-label="إزالة"></button>
                `;
                chip.querySelector('.btn-close').addEventListener('click', () => {
                    customRads.delete(name);
                    renderChips();
                });
                container.appendChild(chip);
            });

            updateCounter();
        }

        function addRad(rad) {
            if (!rad) return;
            selectedRads.set(rad.id, rad);
            renderChips();
        }

        function addCustomRad(name) {
            const cleanName = (name || '').trim();
            if (!cleanName) return;
            const lower = cleanName.toLowerCase();
            const matched = window.icuRadiologyTypes.find(r => (r.name && r.name.toLowerCase() === lower) || (r.code && r.code.toLowerCase() === lower));
            if (matched) {
                addRad(matched);
            } else {
                customRads.add(cleanName);
                renderChips();
            }
        }

        function showResults(query) {
            const q = (query || '').trim().toLowerCase();
            menu.innerHTML = '';

            let results = [];
            if (q.length > 0) {
                results = window.icuRadiologyTypes.filter(r => 
                    (r.name && r.name.toLowerCase().includes(q)) || 
                    (r.code && r.code.toLowerCase().includes(q)) ||
                    (r.category && r.category.toLowerCase().includes(q))
                ).slice(0, 15);
            } else {
                results = window.icuRadiologyTypes.slice(0, 15);
            }

            if (q.length > 0) {
                const customItem = document.createElement('a');
                customItem.href = 'javascript:void(0);';
                customItem.className = 'dropdown-item d-flex justify-content-between align-items-center py-2 bg-light border-bottom text-info fw-bold';
                customItem.innerHTML = `<span><i class="fas fa-plus-circle me-1"></i> إضافة "<strong>${escapeHtml(query.trim())}</strong>" كفحص تصوير مخصص</span><span class="badge bg-info text-white">إضافة</span>`;
                customItem.addEventListener('click', () => {
                    addCustomRad(query.trim());
                    searchInput.value = '';
                    menu.style.display = 'none';
                });
                menu.appendChild(customItem);
            }

            if (results.length === 0 && q.length === 0) {
                menu.style.display = 'none';
                return;
            }

            results.forEach(rad => {
                const item = document.createElement('a');
                item.href = 'javascript:void(0);';
                item.className = 'dropdown-item d-flex justify-content-between align-items-center py-2';
                item.innerHTML = `
                    <div>
                        <span class="fw-bold text-dark d-block">${escapeHtml(rad.name)}</span>
                        <small class="text-muted">${rad.code ? '[' + escapeHtml(rad.code) + '] ' : ''}${rad.category ? escapeHtml(rad.category) : ''}</small>
                    </div>
                    <span class="badge bg-info-subtle text-info border">اختيار</span>
                `;
                item.addEventListener('click', () => {
                    addRad(rad);
                    searchInput.value = '';
                    menu.style.display = 'none';
                });
                menu.appendChild(item);
            });

            menu.style.display = 'block';
        }

        searchInput.addEventListener('input', () => {
            showResults(searchInput.value);
        });

        searchInput.addEventListener('focus', () => {
            showResults(searchInput.value);
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const val = searchInput.value.trim();
                if (val) {
                    const q = val.toLowerCase();
                    const matched = window.icuRadiologyTypes.find(r => (r.name && r.name.toLowerCase() === q) || (r.code && r.code.toLowerCase() === q));
                    if (matched) {
                        addRad(matched);
                    } else {
                        addCustomRad(val);
                    }
                    searchInput.value = '';
                    menu.style.display = 'none';
                }
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#icuRadSearchInput') && !e.target.closest('#icuRadAutocompleteMenu')) {
                menu.style.display = 'none';
            }
        });

        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', () => {
                selectedRads.clear();
                customRads.clear();
                renderChips();
            });
        }

        quickBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                let exams = [];
                try {
                    exams = JSON.parse(btn.getAttribute('data-exams') || '[]');
                } catch (e) {
                    exams = [btn.textContent.trim()];
                }

                let found = false;
                for (const alias of exams) {
                    const lower = alias.toLowerCase();
                    const m = window.icuRadiologyTypes.find(r => 
                        (r.name && r.name.toLowerCase().includes(lower)) || 
                        (r.code && r.code.toLowerCase().includes(lower))
                    );
                    if (m) {
                        addRad(m);
                        found = true;
                        break;
                    }
                }
                if (!found && exams.length > 0) {
                    addCustomRad(exams[0]);
                }
            });
        });

        form.addEventListener('submit', (e) => {
            if (selectedRads.size === 0 && customRads.size === 0) {
                e.preventDefault();
                alert('يرجى اختيار فحص تصوير شعاعي واحد على الأقل قبل الإرسال.');
                searchInput.focus();
            }
        });
    }

    setupIcuLabOrder();
    setupIcuRadiologyOrder();

    // ================= 3. البحث الذكي ثنائي اللغة للتصنيف الدولي ICD-10 داخل طبلة الطبيب =================
    function setupCcuIcd10Search() {
        const searchInput = document.getElementById('ccu_icd10_search');
        const hiddenInput = document.getElementById('ccu_icd10_code');
        const dropdown = document.getElementById('ccu_icd10_dropdown');
        const clearBtn = document.getElementById('ccu_icd10_clear_btn');
        const selectedCard = document.getElementById('ccu_selected_diagnosis_card');
        const cardCode = document.getElementById('ccu_diag_card_code');
        const cardText = document.getElementById('ccu_diag_card_text');
        const primaryDiagInput = document.getElementById('ccu_primary_diagnosis_input');

        if (!searchInput || !dropdown) return;

        let debounceTimer = null;

        function setIcdSelection(code, descText) {
            if (hiddenInput) hiddenInput.value = code || '';
            if (searchInput) searchInput.value = code || '';
            if (clearBtn) clearBtn.classList.toggle('d-none', !code);

            if (code && selectedCard && cardCode && cardText) {
                cardCode.textContent = code;
                cardText.textContent = descText || 'التصنيف المعتمد';
                selectedCard.classList.remove('d-none');
            } else if (selectedCard) {
                selectedCard.classList.add('d-none');
            }

            if (primaryDiagInput && !primaryDiagInput.value.trim() && descText) {
                primaryDiagInput.value = descText;
            }
            dropdown.style.display = 'none';
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                setIcdSelection('', '');
                searchInput.focus();
            });
        }

        searchInput.addEventListener('input', function() {
            const q = this.value.trim();
            if (hiddenInput) hiddenInput.value = q;
            if (clearBtn) clearBtn.classList.toggle('d-none', !q);

            clearTimeout(debounceTimer);
            if (q.length < 1) {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`{{ route('doctor.icd10.search') }}?q=${encodeURIComponent(q)}`)
                    .then(res => res.json())
                    .then(items => {
                        dropdown.innerHTML = '';
                        if (Array.isArray(items) && items.length > 0) {
                            items.forEach(item => {
                                const btn = document.createElement('button');
                                btn.type = 'button';
                                btn.className = 'list-group-item list-group-item-action py-2 px-3 text-start';
                                const arDesc = item.description_ar ? `<div class="small text-success fw-bold">${item.description_ar}</div>` : '';
                                btn.innerHTML = `
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <span class="badge bg-primary font-monospace me-1">${item.code}</span>
                                            <span class="fw-bold text-dark small">${item.description || ''}</span>
                                            ${arDesc}
                                        </div>
                                        ${item.category ? `<span class="badge bg-light text-muted border" style="font-size:10px;">${item.category}</span>` : ''}
                                    </div>
                                `;
                                btn.addEventListener('click', () => {
                                    const label = item.description_ar
                                        ? `${item.description} (${item.description_ar})`
                                        : item.description;
                                    setIcdSelection(item.code, label);
                                });
                                dropdown.appendChild(btn);
                            });
                        }

                        // خيار الاعتماد النصي الحر في حال الرمز المخصص
                        const customBtn = document.createElement('button');
                        customBtn.type = 'button';
                        customBtn.className = 'list-group-item list-group-item-action py-2 px-3 text-start bg-light text-primary small fw-bold';
                        customBtn.innerHTML = `<i class="fas fa-plus-circle me-1"></i> اعتماد النص / الرمز كما كُتب: <span class="font-monospace">${q}</span>`;
                        customBtn.addEventListener('click', () => setIcdSelection(q, ''));
                        dropdown.appendChild(customBtn);

                        dropdown.style.display = 'block';
                    })
                    .catch(() => { dropdown.style.display = 'none'; });
            }, 220);
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    }

    // تعبئة تلقائية للخدمة الإضافية المنفصلة بالطبلة
    const ccuAddonSelect = document.getElementById('ccu_addon_select');
    const ccuAddonName = document.getElementById('ccu_addon_custom_name');
    const ccuAddonPrice = document.getElementById('ccu_addon_unit_price');
    if (ccuAddonSelect) {
        ccuAddonSelect.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.value) {
                if (ccuAddonName) ccuAddonName.value = opt.dataset.name || '';
                if (ccuAddonPrice) ccuAddonPrice.value = opt.dataset.price || 0;
            }
        });
    }

    setupCcuIcd10Search();
});
</script>
@endsection
